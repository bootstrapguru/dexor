    public function createNewAssistant(): Assistant
    {
        // ... existing code ...
        
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