<?php

require_once 'vendor/autoload.php';

use App\Services\SimpleTtsService;

// Test OpenAI TTS fallback
echo "=== OpenAI TTS Fallback Test ===\n";

$openaiTts = new SimpleTtsService();

$testText = "Dit is een test van de OpenAI TTS fallback service. Deze wordt gebruikt als Gemini quota exceeded is.";

echo "Tekst: " . $testText . "\n";
echo "Lengte: " . strlen($testText) . " karakters\n\n";

try {
    echo "🚀 Starting OpenAI TTS generation...\n";
    $startTime = microtime(true);
    
    $audioPath = $openaiTts->generateAudio($testText, 'nl', 'Puck');
    
    $endTime = microtime(true);
    $totalTime = round($endTime - $startTime, 2);
    
    echo "✅ Success!\n";
    echo "⏱️  Time: {$totalTime} seconds\n";
    echo "📁 Path: {$audioPath}\n";
    
    if (file_exists('storage/app/public/' . $audioPath)) {
        $fileSize = filesize('storage/app/public/' . $audioPath);
        echo "📊 File size: " . round($fileSize / 1024, 2) . " KB\n";
    }
    
    echo "\n✅ OpenAI TTS fallback werkt perfect!\n";
    echo "🎧 Listen to: storage/app/public/{$audioPath}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
