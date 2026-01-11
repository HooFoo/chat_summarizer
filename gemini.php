<?php

require_once 'config.php';

class GeminiClient {
    private $apiKey;
    private $model;

    public function __construct($apiKey = GEMINI_API_KEY, $model = GEMINI_MODEL) {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function generateSummary($text, $systemPrompt) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . $this->model . ":generateContent?key=" . $this->apiKey;

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => "System Instruction: " . $systemPrompt . "\n\nData to process:\n" . $text]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 4096,
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            log_message("Gemini API error (HTTP $httpCode): " . $response);
            return "Ошибка при обращении к Gemini API.";
        }

        $result = json_decode($response, true);
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return $result['candidates'][0]['content']['parts'][0]['text'];
        }

        log_message("Unexpected Gemini API response structure: " . $response);
        return "Не удалось получить ответ от Gemini.";
    }
}
