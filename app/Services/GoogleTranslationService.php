<?php

namespace App\Services;

use App\Support\LanguageCode;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Translates text with Google Cloud Translation API v3 — the app's single translation provider.
 */
class GoogleTranslationService
{
    /** Google recommends keeping one translateText request under 30,000 codepoints. */
    private const MAX_REQUEST_CODEPOINTS = 30000;

    /** translateText accepts at most 1024 entries in `contents`. */
    private const MAX_REQUEST_TEXTS = 1024;

    /** App codes Google does not accept as-is (used as CSV column headers). */
    private const GOOGLE_LANGUAGE_CODES = [
        'es_AR' => 'es-AR',
        'gr' => 'el',
        'al' => 'sq',
    ];

    public function __construct(private GoogleOAuthService $oauthService) {}

    /**
     * @param  string|null  $sourceLanguage  null lets Google detect the source language
     */
    public function translateText(string $text, string $targetLanguage, ?string $sourceLanguage = null): string
    {
        return $this->translateBatch([$text], $targetLanguage, $sourceLanguage)[0];
    }

    /**
     * @param  array<int, string>  $texts
     * @param  string|null  $sourceLanguage  null lets Google detect the source language
     * @return list<string> translations in input order; blank texts stay empty
     *
     * @throws RuntimeException
     */
    public function translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        $texts = array_map('strval', array_values($texts));
        $target = $this->toGoogleLanguageCode($targetLanguage);
        $source = $sourceLanguage === null ? null : $this->toGoogleLanguageCode($sourceLanguage);

        // Google rejects equal source and target languages; an accent change (en-gb → en-us) needs no translation.
        if ($source !== null && LanguageCode::isSameLanguage($source, $target)) {
            return $texts;
        }

        $results = array_fill(0, count($texts), '');
        $pending = array_filter($texts, fn (string $text) => trim($text) !== '');

        foreach ($this->chunkForRequests($pending) as $chunk) {
            $translations = $this->requestTranslations(array_values($chunk), $target, $source);

            foreach (array_keys($chunk) as $position => $index) {
                $results[$index] = $this->capitalizeSentences($translations[$position]);
            }
        }

        return $results;
    }

    /**
     * @param  array<int, string>  $texts  keyed by their position in the batch
     * @return list<array<int, string>>
     */
    private function chunkForRequests(array $texts): array
    {
        $chunks = [];
        $chunk = [];
        $codepoints = 0;

        foreach ($texts as $index => $text) {
            $length = mb_strlen($text);

            if ($chunk !== [] && (count($chunk) >= self::MAX_REQUEST_TEXTS || $codepoints + $length > self::MAX_REQUEST_CODEPOINTS)) {
                $chunks[] = $chunk;
                $chunk = [];
                $codepoints = 0;
            }

            $chunk[$index] = $text;
            $codepoints += $length;
        }

        if ($chunk !== []) {
            $chunks[] = $chunk;
        }

        return $chunks;
    }

    /**
     * @param  list<string>  $contents
     * @return list<string>
     */
    private function requestTranslations(array $contents, string $target, ?string $source): array
    {
        $endpoint = $this->endpoint();
        $payload = [
            'contents' => $contents,
            'mimeType' => 'text/plain',
            'targetLanguageCode' => $target,
        ];

        if ($source !== null) {
            $payload['sourceLanguageCode'] = $source;
        }

        $response = Http::withToken($this->oauthService->getAccessToken())
            ->acceptJson()
            ->timeout(120)
            ->retry(3, 200, fn (Throwable $exception) => $this->isTransient($exception), throw: false)
            ->post($endpoint, $payload);

        if ($response->failed()) {
            Log::error('Google Translation API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'target_language' => $target,
            ]);

            throw new RuntimeException('Translation failed: '.($response->json('error.message') ?? $response->body()));
        }

        $translations = $response->json('translations');

        if (! is_array($translations) || count($translations) !== count($contents)) {
            throw new RuntimeException('Translation failed: Google returned an unexpected response.');
        }

        return array_map(fn (array $translation) => (string) ($translation['translatedText'] ?? ''), $translations);
    }

    private function endpoint(): string
    {
        $projectId = $this->oauthService->projectId();

        if (! $projectId) {
            throw new RuntimeException('Translation failed: Google Cloud project ID not configured. Add project_id to the service account file or set GOOGLE_CLOUD_PROJECT_ID.');
        }

        return "https://translation.googleapis.com/v3/projects/{$projectId}:translateText";
    }

    private function isTransient(Throwable $exception): bool
    {
        return $exception instanceof ConnectionException
            || ($exception instanceof RequestException
                && ($exception->response->status() === 429 || $exception->response->serverError()));
    }

    private function toGoogleLanguageCode(string $code): string
    {
        return self::GOOGLE_LANGUAGE_CODES[$code] ?? LanguageCode::base($code);
    }

    /**
     * Capitalize the first letter of each sentence in the text
     */
    private function capitalizeSentences(string $text): string
    {
        if (empty(trim($text))) {
            return $text;
        }

        // Trim whitespace
        $text = trim($text);

        // Always capitalize the first letter of the text
        // This ensures every translation starts with a capital letter
        if (mb_strlen($text, 'UTF-8') > 0) {
            // Find the first letter in the string (might be after quotes or other characters)
            $textLength = mb_strlen($text, 'UTF-8');
            $firstLetterPos = -1;
            $firstLetter = '';

            for ($i = 0; $i < $textLength; $i++) {
                $char = mb_substr($text, $i, 1, 'UTF-8');
                // Check if it's any letter (lowercase or uppercase)
                if (preg_match('/\p{L}/u', $char)) {
                    $firstLetterPos = $i;
                    $firstLetter = $char;
                    break;
                }
            }

            // If we found a letter, capitalize it (if it's not already uppercase)
            if ($firstLetterPos >= 0) {
                // Only capitalize if it's lowercase
                if (preg_match('/\p{Ll}/u', $firstLetter)) {
                    $before = mb_substr($text, 0, $firstLetterPos, 'UTF-8');
                    $after = mb_substr($text, $firstLetterPos + 1, null, 'UTF-8');
                    $text = $before.mb_strtoupper($firstLetter, 'UTF-8').$after;
                }
            }
        }

        // Pattern to match sentence endings (. ! ?) followed by whitespace and then a lowercase letter
        // \p{Ll} matches any lowercase letter in any language (Unicode)
        $pattern = '/([.!?])\s+(\p{Ll})/u';
        $text = preg_replace_callback($pattern, function ($matches) {
            return $matches[1].' '.mb_strtoupper($matches[2], 'UTF-8');
        }, $text);

        // Handle cases where sentence ends and next sentence starts immediately (no space)
        $pattern = '/([.!?])(\p{Ll})/u';
        $text = preg_replace_callback($pattern, function ($matches) {
            return $matches[1].mb_strtoupper($matches[2], 'UTF-8');
        }, $text);

        return $text;
    }
}
