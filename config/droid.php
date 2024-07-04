<?php

return [
    /*
     * AI Service
     */
   'ai_service' => env('DROID_AI_SERVICE', 'openai'),

    /*
     * API Key
     */
    'api_key' => env('DROID_API_KEY'),

    /*
     * OpenAI Model
     */
    'ai_model' => env('DROID_MODEL', 'gpt-4o'),

    /*
     * OpenAI Assistant ID
     */
    'assistant_id' => env('DROID_ASSISTANT_ID'),

    /*
     * Prompt for the Assistant
     */
    'prompt' => env('DROID_PROMPT')

];
