<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class CsvTranslationService
{
    /**
     * Parse CSV file and return array of rows
     *
     * @param string $filePath
     * @param string $delimiter
     * @return array
     */
    public function parseCsv(string $filePath, string $delimiter = ';'): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            throw new \Exception('Could not open CSV file');
        }

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $data;
        }

        fclose($handle);
        
        return $rows;
    }

    /**
     * Translate CSV content from English to target languages
     *
     * @param array $csvData Array of CSV rows
     * @return array Translated CSV data
     */
    public function translateCsv(array $csvData): array
    {
        if (empty($csvData)) {
            throw new \Exception('CSV data is empty');
        }

        // First row is header
        $header = $csvData[0];
        $translatedData = [$header];

        // Find column indexes
        $keyIndex = 0; // First column is always key
        $enIndex = 1;  // Second column is English
        
        // Get target language columns (skip key and en)
        $targetLanguages = array_slice($header, 2);
        
        Log::info('Starting CSV translation', [
            'total_rows' => count($csvData) - 1,
            'target_languages' => $targetLanguages
        ]);

        // Process each row (skip header)
        for ($i = 1; $i < count($csvData); $i++) {
            $row = $csvData[$i];
            
            // Skip empty rows
            if (empty($row[$keyIndex]) || empty($row[$enIndex])) {
                $translatedData[] = $row;
                continue;
            }

            $key = $row[$keyIndex];
            $englishText = $row[$enIndex];
            
            Log::info("Translating row {$i}", [
                'key' => $key,
                'english_text' => $englishText
            ]);

            // Translate to each target language
            $translatedRow = [$key, $englishText]; // Start with key and English
            
            foreach ($targetLanguages as $langIndex => $targetLang) {
                try {
                    $translation = $this->translateText($englishText, 'en', $targetLang);
                    $translatedRow[] = $translation;
                    
                    Log::info("Translated to {$targetLang}", [
                        'key' => $key,
                        'translation' => $translation
                    ]);
                    
                    // Small delay to avoid rate limiting
                    usleep(100000); // 0.1 seconds
                    
                } catch (\Exception $e) {
                    Log::error("Translation failed for {$targetLang}", [
                        'key' => $key,
                        'error' => $e->getMessage()
                    ]);
                    // Keep empty if translation fails
                    $translatedRow[] = '';
                }
            }
            
            $translatedData[] = $translatedRow;
        }

        return $translatedData;
    }

    /**
     * Translate text using OpenAI
     *
     * @param string $text
     * @param string $sourceLang
     * @param string $targetLang
     * @return string
     */
    private function translateText(string $text, string $sourceLang, string $targetLang): string
    {
        // Map language codes to full names
        $languageNames = [
            'en' => 'English',
            'es_AR' => 'Spanish (Argentina)',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'nl' => 'Dutch',
            'ro' => 'Romanian',
            'gr' => 'Greek',
            'sk' => 'Slovak',
            'lv' => 'Latvian',
            'bg' => 'Bulgarian',
            'fi' => 'Finnish',
            'al' => 'Albanian',
            'ca' => 'Catalan',
            'pt' => 'Portuguese',
            'ru' => 'Russian',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'zh' => 'Chinese',
            'ar' => 'Arabic',
            'hi' => 'Hindi',
            'sv' => 'Swedish',
            'da' => 'Danish',
            'no' => 'Norwegian',
            'pl' => 'Polish',
            'cs' => 'Czech',
            'hu' => 'Hungarian',
            'hr' => 'Croatian',
            'sl' => 'Slovenian',
            'el' => 'Greek',
            'tr' => 'Turkish',
            'uk' => 'Ukrainian',
        ];

        $sourceLangName = $languageNames[$sourceLang] ?? $sourceLang;
        $targetLangName = $languageNames[$targetLang] ?? $targetLang;

        $response = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You are a professional translator. Translate the following text from {$sourceLangName} to {$targetLangName}. Maintain the original tone and style. Only return the translated text without any explanations."
                ],
                [
                    'role' => 'user',
                    'content' => $text
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 500,
        ]);

        $translation = $response->choices[0]->message->content ?? '';
        
        if (empty($translation)) {
            throw new \Exception('Translation returned empty result');
        }

        return trim($translation);
    }

    /**
     * Export array to CSV file
     *
     * @param array $data
     * @param string $outputPath
     * @param string $delimiter
     * @return void
     */
    public function exportToCsv(array $data, string $outputPath, string $delimiter = ';'): void
    {
        $handle = fopen($outputPath, 'w');
        
        if ($handle === false) {
            throw new \Exception('Could not create CSV file');
        }

        foreach ($data as $row) {
            fputcsv($handle, $row, $delimiter);
        }

        fclose($handle);
        
        Log::info('CSV exported successfully', ['path' => $outputPath]);
    }
}

