    public function createNewAssistant(): Assistant
    {
        $path = getcwd();
        $folderName = basename($path);

        $service = $this->selectService();
        $this->ensureAPIKey($service);
        $models = $this->getModels($service);

        $assistant = form()
            ->text(label: 'What is the name of the assistant?', default: ucfirst($folderName+' Project'), required: true, name: 'name')
            ->text(label: 'What is the description of the assistant? (optional)', name: 'description')
            ->search(
                label: 'Choose the Model for the assistant',
                options: fn (string $value) => $this->filterModels($models, $value),
                name: 'model'
            )
            ->textarea(
                label: 'Customize the prompt for the assistant?',
                default: config('dexor.default_prompt', ''),
                required: true,
                hint: 'Include any project details that the assistant should know about.',
                rows: 20,
                name: 'prompt'
            )
            ->submit();

        $assistant = Assistant::create([
            'name' => $assistant['name'],
            'description' => $assistant['description'],
            'model' => $assistant['model'],
            'prompt' => $assistant['prompt'],
            'service' => $service,
        ]);

        // Update the current project with the newly created assistant ID
        $project = $this->getCurrentProject();
        $project->assistant_id = $assistant->id;
        $project->save();

        return $assistant;
    }