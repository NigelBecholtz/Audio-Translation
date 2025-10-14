<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAITranslationService
{
    // Language code mapping voor OpenAI translations
    private array $languageMapping = [
        'en' => 'English',
        'es' => 'Spanish',
        'sq' => 'Albanian',
        'bg' => 'Bulgarian',
        'sk' => 'Slovak',
        'lv' => 'Latvian',
        'fi' => 'Finnish',
        'el' => 'Greek',
        'nl' => 'Dutch',
        'fr' => 'French',
        'it' => 'Italian',
        'ro' => 'Romanian',
        'ca' => 'Catalan',
        // Additional languages
        'de' => 'German',
        'pt' => 'Portuguese',
        'ru' => 'Russian',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'zh' => 'Chinese',
        'ar' => 'Arabic',
        'hi' => 'Hindi',
        'pl' => 'Polish',
        'tr' => 'Turkish',
        'sv' => 'Swedish',
        'da' => 'Danish',
        'no' => 'Norwegian',
        'cs' => 'Czech',
        'hu' => 'Hungarian',
        'hr' => 'Croatian',
        'sl' => 'Slovenian',
        'uk' => 'Ukrainian',
        'lt' => 'Lithuanian',
        'et' => 'Estonian',
    ];

    /**
     * Translate a batch of texts to target language using OpenAI
     *
     * @param array $texts Array of texts to translate
     * @param string $targetLanguage Target language code
     * @param string $sourceLanguage Source language code (default: 'en')
     * @return array Array of translated texts
     * @throws \Exception
     */
    public function translateBatch(array $texts, string $targetLanguage, string $sourceLanguage = 'en'): array
    {
        if (empty($texts)) {
            return [];
        }

        // Filter out empty texts but keep track of positions
        $nonEmptyTexts = [];
        $positions = [];
        foreach ($texts as $index => $text) {
            if (!empty($text)) {
                $nonEmptyTexts[] = $text;
                $positions[] = $index;
            }
        }

        if (empty($nonEmptyTexts)) {
            return array_fill(0, count($texts), '');
        }

        try {
            // Get language names
            $sourceLangName = $this->languageMapping[$sourceLanguage] ?? ucfirst($sourceLanguage);
            $targetLangName = $this->languageMapping[$targetLanguage] ?? ucfirst($targetLanguage);
            
            Log::info('OpenAI batch translation started', [
                'source_language' => $sourceLangName,
                'target_language' => $targetLangName,
                'texts_count' => count($nonEmptyTexts)
            ]);

            // Process in smaller batches to avoid timeouts
            $batchSize = 5; // Process 5 texts at a time (faster response)
            $allTranslations = [];
            $chunks = array_chunk($nonEmptyTexts, $batchSize);
            
            foreach ($chunks as $chunkIndex => $chunk) {
                Log::info("Processing translation chunk", [
                    'chunk' => $chunkIndex + 1,
                    'total_chunks' => count($chunks),
                    'chunk_size' => count($chunk)
                ]);

                // Create a numbered list for batch translation
                $numberedTexts = [];
                foreach ($chunk as $idx => $text) {
                    $numberedTexts[] = ($idx + 1) . ". " . $text;
                }
                $combinedText = implode("\n", $numberedTexts);

                $response = OpenAI::chat()->create([
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "You are a professional translator. Translate the following numbered list from {$sourceLangName} to {$targetLangName}. Return ONLY the translated texts in numbered format."
                        ],
                        [
                            'role' => 'user',
                            'content' => $combinedText
                        ]
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 2000,
                ]);

                $translatedContent = $response->choices[0]->message->content ?? '';
                
                if (empty($translatedContent)) {
                    throw new \Exception('Translation returned empty result');
                }

                // Parse the numbered responses
                $lines = explode("\n", $translatedContent);
                $chunkTranslations = [];
                
                foreach ($lines as $line) {
                    // Match numbered lines like "1. Text" or "1) Text"
                    if (preg_match('/^\d+[\.\)]\s*(.+)$/u', $line, $matches)) {
                        $chunkTranslations[] = trim($matches[1]);
                    }
                }

                // If parsing failed, fall back to splitting by newlines
                if (count($chunkTranslations) !== count($chunk)) {
                    Log::warning('Numbered parsing failed, using fallback method', [
                        'expected' => count($chunk),
                        'got' => count($chunkTranslations)
                    ]);
                    $chunkTranslations = array_slice($lines, 0, count($chunk));
                }

                $allTranslations = array_merge($allTranslations, $chunkTranslations);
            }

            // Build result array with empty strings for skipped positions
            $result = array_fill(0, count($texts), '');
            foreach ($positions as $idx => $originalIndex) {
                if (isset($allTranslations[$idx])) {
                    $result[$originalIndex] = $allTranslations[$idx];
                }
            }

            Log::info('Batch translation completed', [
                'count' => count($nonEmptyTexts),
                'target_language' => $targetLangName
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Translation batch failed', [
                'target_language' => $targetLanguage,
                'texts_count' => count($nonEmptyTexts),
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Translation failed: ' . $e->getMessage());
        }
    }

    /**
     * Detect language of text using OpenAI
     *
     * @param string $text Text to detect language for
     * @return string Detected language code
     */
    public function detectLanguage(string $text): string
    {
        if (empty(trim($text))) {
            return 'en';
        }

        try {
            Log::info('Detecting language with OpenAI', [
                'text_length' => strlen($text),
                'text_sample' => substr($text, 0, 100)
            ]);

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a language detection expert. Respond ONLY with the ISO 639-1 two-letter language code (e.g., "en", "es", "fr", "nl", "de"). No explanations, just the code.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Detect the language of this text: " . substr($text, 0, 500)
                    ]
                ],
                'temperature' => 0.1,
                'max_tokens' => 10,
            ]);

            $detectedCode = strtolower(trim($response->choices[0]->message->content ?? 'en'));
            
            // Validate it's a 2-letter code
            if (strlen($detectedCode) !== 2) {
                Log::warning('Invalid language code detected, defaulting to English', [
                    'detected' => $detectedCode
                ]);
                return 'en';
            }

            Log::info('Language detected', [
                'language_code' => $detectedCode
            ]);

            return $detectedCode;

        } catch (\Exception $e) {
            Log::error('Language detection failed', [
                'error' => $e->getMessage()
            ]);
            return 'en'; // Fallback to English
        }
    }

    /**
     * Get preset language configuration
     * Order: EN | ES | AL | BG | SK | LV | FI | GR | NL | FR | IT | RO | CA
     *
     * @return array Array of language codes in specific order
     */
    public function getPresetLanguages(): array
    {
        return [
            'en',  // English
            'es',  // Spanish
            'sq',  // Albanian (AL)
            'bg',  // Bulgarian
            'sk',  // Slovak
            'lv',  // Latvian
            'fi',  // Finnish
            'el',  // Greek (GR)
            'nl',  // Dutch
            'fr',  // French
            'it',  // Italian
            'ro',  // Romanian
            'ca'   // Catalan
        ];
    }
}

