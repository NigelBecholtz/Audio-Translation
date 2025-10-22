<?php

namespace App\Services;

class SanitizationService
{
    /**
     * Sanitize text input (remove HTML, XSS protection)
     */
    public function sanitizeText(string $text): string
    {
        // Remove HTML tags
        $text = strip_tags($text);
        
        // Remove null bytes
        $text = str_replace("\0", '', $text);
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Trim
        $text = trim($text);
        
        return $text;
    }

    /**
     * Sanitize style instruction (allow some formatting but prevent XSS)
     */
    public function sanitizeStyleInstruction(?string $styleInstruction): ?string
    {
        if (empty($styleInstruction)) {
            return null;
        }

        // Remove dangerous tags but allow basic formatting
        $allowedTags = '<b><i><em><strong>';
        $styleInstruction = strip_tags($styleInstruction, $allowedTags);
        
        // Remove null bytes
        $styleInstruction = str_replace("\0", '', $styleInstruction);
        
        // Remove potentially dangerous attributes
        $styleInstruction = preg_replace('/<(\w+)[^>]*?(on\w+\s*=|javascript:|data:)[^>]*?>/i', '<$1>', $styleInstruction);
        
        // Normalize whitespace
        $styleInstruction = preg_replace('/\s+/', ' ', $styleInstruction);
        
        // Trim
        $styleInstruction = trim($styleInstruction);
        
        return $styleInstruction;
    }

    /**
     * Sanitize filename
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove path traversal attempts
        $filename = basename($filename);
        
        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Prevent double extensions
        $filename = preg_replace('/\.+/', '.', $filename);
        
        // Limit length
        if (strlen($filename) > 255) {
            $filename = substr($filename, 0, 255);
        }
        
        return $filename;
    }

    /**
     * Validate and sanitize language code
     */
    public function sanitizeLanguageCode(string $language): string
    {
        // Only allow alphanumeric and hyphen
        $language = preg_replace('/[^a-z-]/', '', strtolower($language));
        
        // Limit length
        if (strlen($language) > 10) {
            $language = substr($language, 0, 10);
        }
        
        return $language;
    }

    /**
     * Validate and sanitize voice name
     */
    public function sanitizeVoiceName(string $voice): string
    {
        // Only allow alphanumeric
        $voice = preg_replace('/[^a-zA-Z]/', '', $voice);
        
        // Limit length
        if (strlen($voice) > 50) {
            $voice = substr($voice, 0, 50);
        }
        
        return $voice;
    }
}

