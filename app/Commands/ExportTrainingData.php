<?php

namespace App\Commands;

use App\Models\Assistant;
use App\Models\Thread;
use App\Tools\CreateFile;
use App\Tools\ExecuteCommand;
use App\Tools\ListFiles;
use App\Tools\ReadFile;
use App\Tools\UpdateFile;
use App\Traits\HasTools;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use OpenAI;
use ReflectionException;

class ExportTrainingData extends Command
{
    use HasTools;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export-training-data {assistantId} {--separate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates LLM Training data from Conversations to JSONL file.';

    /**
     * Constructor
     *
     * @throws ReflectionException
     */
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * Execute the console command.
     * @throws ReflectionException
     */
    public function handle(): void
    {
        $assistantId = $this->argument('assistantId');
        $separateFiles = $this->option('separate');
        $team = Assistant::find($assistantId);


        $this->register([
            ExecuteCommand::class,
            CreateFile::class,
            UpdateFile::class,
            ListFiles::class,
            ReadFile::class,
        ]);

        $conversations = Thread::where('assistant_id', $assistantId)->with('messages')->limit(80)->get();

        $trainingData = [];
        $systemMessage = $this->systemMessage($team);

        $conversationFunctions = [];
        foreach ($conversations as $conversation) {
            $messages = $conversation->messages->sortBy('id');
            // Debugging done; Removed dd statement. Sorted messages by id.

            $conversationData = [];
            // add system message and prompt along with menu
            $systemMessage['role'] = 'system';

            $conversationData[] = $systemMessage;

            foreach ($messages as $message) {
                $messageData = [
                    'role' => $message->role,
                ];

                if ($message->role === 'tool') {
                    $messageData['tool_call_id'] = $message->tool_call_id;
                }

                if ($message->tool_calls !== null) {
                    $array = $message->tool_calls;
                    // Iterate over the array and replace "tool_use" with "function"
                    foreach ($array as &$item) {
                        if (isset($item['type']) && $item['type'] === "tool_use") {
                            $item['type'] = "function";
                        }
                    }
                    $messageData['tool_calls'] = $array;
                }

                if ($message->content !== null) {
                    $messageData['content'] = $message->content;
                }

                if ($message->tool_calls !== null) {
                    foreach ($message->tool_calls as $toolCall) {
                        $conversationFunctions[] = $toolCall['function']['name'];
                    }
                }

                if ($message->role === 'assistant') {
                    $messageData['content'] = $message->content;
                }

                $conversationData[] = $messageData;
            }

            $lastMessage = collect($conversationData)->last();
            if ($lastMessage['role'] === 'assistant' && isset($lastMessage['tool_calls'])) {
                foreach ($lastMessage['tool_calls'] as $toolCall) {
                    $lastMessageModified = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                    ];
                    if (isset($toolCall['function']) && $toolCall['function']['name'] === 'end_the_conversation') {
                        $lastMessageModified['content'] = 'Thanks for calling. Have a great day!';
                    }

                    if (isset($toolCall['function']) && $toolCall['function']['name'] === 'confirm_the_order') {
                        $lastMessageModified['content'] = 'Your order has been confirmed. Thank you for calling. Have a great day!';
                    }

                    if (isset($toolCall['function']) && $toolCall['function']['name'] === 'talk_to_staff') {
                        $lastMessageModified['content'] = 'I will connect you to a staff member. Please hold on.';
                    }

                    $conversationData[] = $lastMessageModified;
                }
            }

            $conversationData[] = [
                'role' => 'assistant',
                'content' => 'End of the conversation.',
            ];
            $trainingData[] = $conversationData;

            if ($separateFiles) {
                $fileName = 'training-data/conversation_'.$conversation->id.'_team_'.$conversation->team_id.'.json';
                Storage::disk('local')->put($fileName, json_encode(['messages' => $conversationData], JSON_PRETTY_PRINT));
                $this->info('Created file: '.storage_path('app/'.$fileName));
            }
        }

        $jsonl = '';
        $tools = [];

        foreach ($conversationFunctions as $tool) {
            $existOnToolsAlready = collect($tools)->first(fn ($existingTool) => $existingTool['function']['name'] === $tool);
            if ($existOnToolsAlready) {
                continue;
            }

            $foundTool = collect($this->registered_tools)->first(fn ($registeredTool) => $registeredTool['function']['name'] === $tool);

            if ($foundTool) {
                $tools[] = [
                    'type' => 'function',
                    'function' => [
                        'name' => $foundTool['function']['name'],
                        'description' => $foundTool['function']['description'] ?? '',
                        'parameters' => $foundTool['function']['parameters'] ?? [
                                'type' => 'object',
                                'properties' => [],
                            ],
                    ],
                ];
            }
        }

        if (! $separateFiles) {
            foreach ($trainingData as $conversationData) {
                $jsonl .= '{"messages": '.json_encode($conversationData)."}\n";
            }

            $fileName = 'training-data/restaurant/training_data.jsonl';

            Storage::disk('local')->put($fileName, $jsonl);

            $this->info(storage_path('app/'.$fileName));
        }

        $this->info('Training data generated successfully!');
    }

    public function systemMessage($team): array
    {
        $content = $team->prompt;
        return ['role' => 'system', 'content' => $content];
    }
}
