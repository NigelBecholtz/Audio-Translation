<?php

namespace App\Constants;

class AudioConstants
{
    // File Size Limits (in MB)
    public const WHISPER_MAX_FILE_SIZE_MB = 25;
    public const MAX_UPLOAD_SIZE_MB = 100;
    
    // Processing Timeouts (in seconds)
    public const MAX_EXECUTION_TIME = 600;
    public const MAX_INPUT_TIME = 600;
    
    // Memory Limits
    public const MEMORY_LIMIT = '512M';
    
    // Audio Compression Settings
    public const COMPRESSION_BITRATE_HIGH = 64; // kbps
    public const COMPRESSION_BITRATE_MEDIUM = 48; // kbps
    public const COMPRESSION_BITRATE_LOW = 32; // kbps
    
    // File Size Thresholds for Compression
    public const COMPRESSION_THRESHOLD_MEDIUM = 50; // MB
    public const COMPRESSION_THRESHOLD_LOW = 75; // MB
    
    // Audio Extraction Settings
    public const EXTRACTION_BITRATE = 128; // kbps for video to audio extraction
    public const EXTRACTION_TIMEOUT = 300; // seconds
    
    // TTS Settings
    public const TTS_CHUNK_SIZE_BYTES = 900; // Gemini TTS limit
    public const TTS_CHUNK_DELAY_SECONDS = 2; // Delay between chunks
    public const TTS_SAMPLE_RATE = 24000; // Hz
    
    // Text Length Limits
    public const MAX_TEXT_LENGTH = 50000; // characters
    public const MAX_STYLE_INSTRUCTION_LENGTH = 5000; // characters
    public const MIN_TEXT_LENGTH = 10; // characters
    
    // Audio Formats
    public const AUDIO_FORMATS = ['mp3', 'wav', 'm4a', 'ogg', 'flac'];
    public const VIDEO_FORMATS = ['mp4', 'avi', 'mov', 'mkv', 'flv', 'wmv', 'webm'];
    
    // Processing Status
    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_TRANSCRIBING = 'transcribing';
    public const STATUS_TRANSLATING = 'translating';
    public const STATUS_GENERATING_AUDIO = 'generating_audio';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PROCESSING = 'processing';
    
    // Queue Settings
    public const QUEUE_TIMEOUT = 600; // seconds
    public const QUEUE_TRIES = 3;
    public const QUEUE_SLEEP = 3; // seconds
}
