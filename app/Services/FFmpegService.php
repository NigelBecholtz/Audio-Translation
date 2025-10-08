<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class FFmpegService
{
    /**
     * Check if FFmpeg is installed
     */
    public function isInstalled(): bool
    {
        $process = new Process(['ffmpeg', '-version']);
        $process->run();
        
        return $process->isSuccessful();
    }

    /**
     * Get audio file size in MB
     */
    public function getFileSizeMB(string $path): float
    {
        $fullPath = Storage::disk('public')->path($path);
        return filesize($fullPath) / 1024 / 1024;
    }

    /**
     * Extract and compress audio from video/audio file
     * 
     * @param string $inputPath Storage path (e.g., 'audio/file.mp4')
     * @param int $targetBitrate Target bitrate in kbps (default: 64)
     * @return string Path to compressed file
     */
    public function compressAudio(string $inputPath, int $targetBitrate = 64): string
    {
        $disk = Storage::disk('public');
        $fullInputPath = $disk->path($inputPath);
        
        // Generate output filename
        $pathInfo = pathinfo($inputPath);
        $outputFilename = $pathInfo['filename'] . '_compressed.mp3';
        $outputPath = $pathInfo['dirname'] . '/' . $outputFilename;
        $fullOutputPath = $disk->path($outputPath);

        // FFmpeg command to extract audio and compress
        // -i: input file
        // -vn: no video
        // -acodec libmp3lame: use MP3 codec
        // -b:a: audio bitrate
        // -ac 1: mono (saves space, good for speech)
        // -ar 22050: sample rate (lower = smaller file, still good for speech)
        $process = new Process([
            'ffmpeg',
            '-i', $fullInputPath,
            '-vn',                          // No video
            '-acodec', 'libmp3lame',        // MP3 codec
            '-b:a', $targetBitrate . 'k',   // Bitrate
            '-ac', '1',                     // Mono
            '-ar', '22050',                 // 22.05kHz sample rate
            '-y',                           // Overwrite output file
            $fullOutputPath
        ]);

        $process->setTimeout(300); // 5 minutes max
        
        try {
            $process->mustRun();
            
            Log::info('FFmpeg compression successful', [
                'input' => $inputPath,
                'output' => $outputPath,
                'input_size' => $this->getFileSizeMB($inputPath) . 'MB',
                'output_size' => $this->getFileSizeMB($outputPath) . 'MB',
                'bitrate' => $targetBitrate . 'kbps'
            ]);
            
            return $outputPath;
            
        } catch (ProcessFailedException $e) {
            Log::error('FFmpeg compression failed', [
                'input' => $inputPath,
                'error' => $e->getMessage(),
                'output' => $process->getErrorOutput()
            ]);
            
            throw new \Exception('Audio compression failed: ' . $e->getMessage());
        }
    }

    /**
     * Compress audio if it exceeds size limit
     * 
     * @param string $inputPath Storage path
     * @param float $maxSizeMB Maximum size in MB (default: 25)
     * @return string Path to file (original or compressed)
     */
    public function compressIfNeeded(string $inputPath, float $maxSizeMB = 25): string
    {
        $currentSize = $this->getFileSizeMB($inputPath);
        
        if ($currentSize <= $maxSizeMB) {
            Log::info('File within size limit, no compression needed', [
                'path' => $inputPath,
                'size' => $currentSize . 'MB',
                'limit' => $maxSizeMB . 'MB'
            ]);
            return $inputPath;
        }

        Log::info('File exceeds size limit, compressing', [
            'path' => $inputPath,
            'size' => $currentSize . 'MB',
            'limit' => $maxSizeMB . 'MB'
        ]);

        // Calculate target bitrate to get under limit
        // Start with 64kbps, go lower if needed
        $targetBitrate = 64;
        if ($currentSize > 50) {
            $targetBitrate = 48;
        }
        if ($currentSize > 75) {
            $targetBitrate = 32;
        }

        $compressedPath = $this->compressAudio($inputPath, $targetBitrate);
        
        // Delete original file to save space
        Storage::disk('public')->delete($inputPath);
        
        return $compressedPath;
    }

    /**
     * Check if file is a video format and needs audio extraction
     * 
     * @param string $path Storage path
     * @return bool
     */
    public function isVideoFile(string $path): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, ['mp4', 'avi', 'mov', 'mkv', 'flv', 'wmv', 'webm']);
    }

    /**
     * Extract audio from video file
     * 
     * @param string $inputPath Storage path to video file
     * @return string Path to extracted audio file
     */
    public function extractAudioFromVideo(string $inputPath): string
    {
        $disk = Storage::disk('public');
        $fullInputPath = $disk->path($inputPath);
        
        // Generate output filename
        $pathInfo = pathinfo($inputPath);
        $outputFilename = $pathInfo['filename'] . '_audio.mp3';
        $outputPath = $pathInfo['dirname'] . '/' . $outputFilename;
        $fullOutputPath = $disk->path($outputPath);

        Log::info('Extracting audio from video', [
            'input' => $inputPath,
            'output' => $outputPath
        ]);

        // FFmpeg command to extract audio only
        $process = new Process([
            'ffmpeg',
            '-i', $fullInputPath,
            '-vn',                          // No video
            '-acodec', 'libmp3lame',        // MP3 codec
            '-b:a', '128k',                 // 128kbps bitrate
            '-y',                           // Overwrite output file
            $fullOutputPath
        ]);

        $process->setTimeout(300); // 5 minutes max
        
        try {
            $process->mustRun();
            
            Log::info('Audio extraction successful', [
                'input' => $inputPath,
                'output' => $outputPath,
                'input_size' => $this->getFileSizeMB($inputPath) . 'MB',
                'output_size' => $this->getFileSizeMB($outputPath) . 'MB'
            ]);
            
            // Delete original video file to save space
            $disk->delete($inputPath);
            
            return $outputPath;
            
        } catch (ProcessFailedException $e) {
            Log::error('Audio extraction failed', [
                'input' => $inputPath,
                'error' => $e->getMessage(),
                'output' => $process->getErrorOutput()
            ]);
            
            throw new \Exception('Audio extraction failed: ' . $e->getMessage());
        }
    }

    /**
     * Get audio duration in seconds
     */
    public function getDuration(string $path): ?float
    {
        $fullPath = Storage::disk('public')->path($path);
        
        $process = new Process([
            'ffprobe',
            '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $fullPath
        ]);

        try {
            $process->mustRun();
            $duration = trim($process->getOutput());
            return $duration ? (float) $duration : null;
        } catch (ProcessFailedException $e) {
            Log::warning('Failed to get audio duration', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get audio file info
     */
    public function getAudioInfo(string $path): array
    {
        $fullPath = Storage::disk('public')->path($path);
        
        $process = new Process([
            'ffprobe',
            '-v', 'error',
            '-show_format',
            '-show_streams',
            '-print_format', 'json',
            $fullPath
        ]);

        try {
            $process->mustRun();
            $output = $process->getOutput();
            return json_decode($output, true) ?? [];
        } catch (ProcessFailedException $e) {
            Log::warning('Failed to get audio info', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
