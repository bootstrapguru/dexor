<?php

namespace App\Services\Request;

class ChatRequest
{
    protected $endpoint = '/chat/completions';
    protected $apiKey;
    protected $baseUrl;

    public function __construct($apiKey, $baseUrl = 'https://api.openai.com/v1')
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
    }

    public function createPayload($prompt, $model = 'gpt-3.5-turbo', $maxTokens = 100, $temperature = 0.7)
    {
        return [
            'model' => $model,
            'prompt' => $prompt,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ];
    }

    public function send($payload)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $headers = [];
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception('Error:' . curl_error($ch));
        }
        curl_close($ch);

        return json_decode($result, true);
    }
}
