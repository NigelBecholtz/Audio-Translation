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

            // Translate to each target language - ONLY if cell is empty
            $translatedRow = $row; // Start with original row
            
            foreach ($targetLanguages as $langIndex => $targetLang) {
                $columnIndex = $langIndex + 2; // +2 because we skip key(0) and en(1)
                $currentValue = $row[$columnIndex] ?? '';
                
                // Only translate if the cell is empty
                if (empty(trim($currentValue))) {
                    try {
                        $translation = $this->translateText($englishText, 'en', $targetLang);
                        $translatedRow[$columnIndex] = $translation;
                        
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
                        
                        // Keep original value (empty) for failed translation
                    }
                } else {
                    // Keep existing value if cell is not empty
                    Log::info("Skipping translation for {$targetLang} - cell not empty", [
                        'key' => $key,
                        'existing_value' => $currentValue
                    ]);
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

        try {
            // Use Google Translate API with Service Account
            $serviceAccountPath = storage_path('app/google-service-account.json');
            
            if (!file_exists($serviceAccountPath)) {
                throw new \Exception('Google Service Account JSON file not found');
            }

            $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
            
            // Get access token using service account
            $accessToken = $this->getAccessToken($serviceAccount);
            
            $url = 'https://translation.googleapis.com/language/translate/v2';
            
            $data = [
                'q' => $text,
                'source' => $sourceLang,
                'target' => $targetLang,
                'format' => 'text'
            ];

            $response = $this->makeHttpRequestWithAuth($url, $data, $accessToken);
            
            if (!isset($response['data']['translations'][0]['translatedText'])) {
                throw new \Exception('Invalid response from Google Translate API');
            }

            $translation = trim($response['data']['translations'][0]['translatedText']);
            
            if (empty($translation)) {
                throw new \Exception('Empty translation received');
            }

            return $translation;

        } catch (\Exception $e) {
            Log::error('Google Translate API failed', [
                'text' => $text,
                'source_language' => $sourceLang,
                'target_language' => $targetLang,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Get access token using Google Service Account
     *
     * @param array $serviceAccount
     * @return string
     */
    private function getAccessToken(array $serviceAccount): string
    {
        $cacheKey = 'google_translate_access_token';
        
        // Check cache first
        $cachedToken = cache()->get($cacheKey);
        if ($cachedToken) {
            return $cachedToken;
        }

        $jwt = $this->createJWT($serviceAccount);
        
        $url = 'https://oauth2.googleapis.com/token';
        $data = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("cURL error: {$error}");
        }

        if ($httpCode !== 200) {
            throw new \Exception("HTTP error: {$httpCode}");
        }

        $tokenData = json_decode($response, true);
        
        if (!isset($tokenData['access_token'])) {
            throw new \Exception('No access token received');
        }

        $accessToken = $tokenData['access_token'];
        $expiresIn = $tokenData['expires_in'] ?? 3600;
        
        // Cache token (expires in 1 hour, but we cache for 50 minutes to be safe)
        cache()->put($cacheKey, $accessToken, now()->addMinutes(50));

        return $accessToken;
    }

    /**
     * Create JWT for Google Service Account
     *
     * @param array $serviceAccount
     * @return string
     */
    private function createJWT(array $serviceAccount): string
    {
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];

        $now = time();
        $payload = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/cloud-translation',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
        $signatureData = $headerEncoded . '.' . $payloadEncoded;
        
        $privateKey = $serviceAccount['private_key'];
        openssl_sign($signatureData, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        
        $signatureEncoded = $this->base64UrlEncode($signature);
        
        return $signatureData . '.' . $signatureEncoded;
    }

    /**
     * Base64 URL encode
     *
     * @param string $data
     * @return string
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Make HTTP request to Google Translate API with authentication
     *
     * @param string $url
     * @param array $data
     * @param string $accessToken
     * @return array
     */
    private function makeHttpRequestWithAuth(string $url, array $data, string $accessToken): array
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Bearer ' . $accessToken,
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            throw new \Exception("cURL error: {$error}");
        }

        if ($httpCode !== 200) {
            throw new \Exception("HTTP error: {$httpCode}");
        }

        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response from Google Translate API');
        }

        return $decodedResponse;
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

    /**
     * Export array to beautifully formatted CSV file with aligned columns
     *
     * @param array $data
     * @param string $outputPath
     * @param string $delimiter
     * @return void
     */
    public function exportToPrettyCsv(array $data, string $outputPath, string $delimiter = ';'): void
    {
        if (empty($data)) {
            throw new \Exception('No data to export');
        }

        // Calculate max width for each column
        $columnWidths = [];
        $columnCount = count($data[0]);
        
        for ($col = 0; $col < $columnCount; $col++) {
            $maxWidth = 0;
            foreach ($data as $row) {
                if (isset($row[$col])) {
                    $length = mb_strlen($row[$col]);
                    $maxWidth = max($maxWidth, $length);
                }
            }
            // Add padding
            $columnWidths[$col] = min($maxWidth + 2, 100); // Max 100 chars per column
        }

        $handle = fopen($outputPath, 'w');
        
        if ($handle === false) {
            throw new \Exception('Could not create CSV file');
        }

        // Write each row with padding
        foreach ($data as $rowIndex => $row) {
            $formattedRow = [];
            
            for ($col = 0; $col < $columnCount; $col++) {
                $value = $row[$col] ?? '';
                $width = $columnWidths[$col];
                
                // Pad the value to column width
                $paddedValue = str_pad($value, $width, ' ', STR_PAD_RIGHT);
                
                // Trim if too long
                if (mb_strlen($paddedValue) > $width) {
                    $paddedValue = mb_substr($paddedValue, 0, $width - 3) . '...';
                }
                
                $formattedRow[] = $paddedValue;
            }
            
            // Write row with delimiter
            fwrite($handle, implode($delimiter, $formattedRow) . "\n");
        }

        fclose($handle);
        
        Log::info('Pretty CSV exported successfully', ['path' => $outputPath]);
    }

    /**
     * Export array to Markdown table format
     *
     * @param array $data
     * @param string $outputPath
     * @return void
     */
    public function exportToMarkdown(array $data, string $outputPath): void
    {
        if (empty($data)) {
            throw new \Exception('No data to export');
        }

        $handle = fopen($outputPath, 'w');
        
        if ($handle === false) {
            throw new \Exception('Could not create markdown file');
        }

        // Calculate max width for each column
        $columnWidths = [];
        $columnCount = count($data[0]);
        
        for ($col = 0; $col < $columnCount; $col++) {
            $maxWidth = 0;
            foreach ($data as $row) {
                if (isset($row[$col])) {
                    $length = mb_strlen($row[$col]);
                    $maxWidth = max($maxWidth, $length);
                }
            }
            $columnWidths[$col] = max($maxWidth, 10); // Min 10 chars
        }

        // Write header
        $header = $data[0];
        fwrite($handle, "| ");
        for ($col = 0; $col < $columnCount; $col++) {
            $value = $header[$col] ?? '';
            $width = $columnWidths[$col];
            fwrite($handle, str_pad($value, $width, ' ', STR_PAD_RIGHT) . " | ");
        }
        fwrite($handle, "\n");

        // Write separator
        fwrite($handle, "|");
        for ($col = 0; $col < $columnCount; $col++) {
            fwrite($handle, str_repeat('-', $columnWidths[$col] + 2) . "|");
        }
        fwrite($handle, "\n");

        // Write data rows
        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];
            fwrite($handle, "| ");
            for ($col = 0; $col < $columnCount; $col++) {
                $value = $row[$col] ?? '';
                $width = $columnWidths[$col];
                
                // Truncate long values
                if (mb_strlen($value) > 50) {
                    $value = mb_substr($value, 0, 47) . '...';
                }
                
                fwrite($handle, str_pad($value, $width, ' ', STR_PAD_RIGHT) . " | ");
            }
            fwrite($handle, "\n");
        }

        fclose($handle);
        
        Log::info('Markdown exported successfully', ['path' => $outputPath]);
    }
}

