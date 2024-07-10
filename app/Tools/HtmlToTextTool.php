<?php

namespace App\Tools;

use App\Attributes\Description;
use GuzzleHttp\Client;

#[Description('Fetch HTML content from a URL and return it as plain text.')]
final class HtmlToTextTool
{
    public function handle(
        #[Description('URL to fetch HTML content from.')]
        string $url,
    ): string {
        try {
            $client = new Client();
            $response = $client->get($url);
            $htmlContent = (string) $response->getBody();
            $plainText = strip_tags($htmlContent);

            return trim($plainText);
        } catch (\Exception $e) {
            return "Error fetching the URL: " . $e->getMessage();
        }
    }
}
