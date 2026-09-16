<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests render Blade layouts without a Vite build present.
        $this->withoutVite();
    }

    /**
     * An upload with real MPEG frame headers, so finfo reports audio/mpeg.
     * UploadedFile::fake()->create() produces an empty file (application/x-empty).
     */
    protected function fakeMp3(string $name = 'test.mp3'): UploadedFile
    {
        $frame = "\xFF\xFB\x90\x64".str_repeat("\x00", 413);

        return UploadedFile::fake()->createWithContent($name, "ID3\x03\x00\x00\x00\x00\x00\x00".str_repeat($frame, 20));
    }
}
