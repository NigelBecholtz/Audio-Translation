<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AudioFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessAudioJob;
use Tests\TestCase;

class AudioUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Queue::fake();
    }

    public function test_user_can_view_audio_index_page()
    {
        $user = User::factory()->create(['credits' => 1.0]);

        $response = $this->actingAs($user)->get(route('audio.index'));

        $response->assertStatus(200);
        $response->assertViewIs('audio.index');
    }

    public function test_user_can_upload_audio_file_with_free_translations()
    {
        $user = User::factory()->create([
            'translations_used' => 0,
            'translations_limit' => 2,
            'credits' => 0
        ]);

        $file = UploadedFile::fake()->create('test.mp3', 1000);

        $response = $this->actingAs($user)->post(route('audio.store'), [
            'audio' => $file,
            'source_language' => 'en',
            'target_language' => 'nl',
            'voice' => 'kore',
            'style_instruction' => null,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('audio_files', [
            'user_id' => $user->id,
            'source_language' => 'en',
            'target_language' => 'nl',
            'status' => 'uploaded',
        ]);

        // Verify job was dispatched
        Queue::assertPushed(ProcessAudioJob::class);
    }

    public function test_user_cannot_upload_without_credits()
    {
        $user = User::factory()->create([
            'translations_used' => 2,
            'translations_limit' => 2,
            'credits' => 0
        ]);

        $file = UploadedFile::fake()->create('test.mp3', 1000);

        $response = $this->actingAs($user)->post(route('audio.store'), [
            'audio' => $file,
            'source_language' => 'en',
            'target_language' => 'nl',
            'voice' => 'kore',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('audio_files', 0);
    }

    public function test_user_can_only_view_their_own_audio_files()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $audioFile = AudioFile::create([
            'user_id' => $user1->id,
            'original_filename' => 'test.mp3',
            'file_path' => 'audio/test.mp3',
            'file_size' => 1000,
            'source_language' => 'en',
            'target_language' => 'nl',
            'voice' => 'kore',
            'status' => 'completed',
        ]);

        // User2 tries to access User1's file
        $response = $this->actingAs($user2)->get(route('audio.show', $audioFile->id));

        $response->assertForbidden();
    }

    public function test_validation_fails_with_invalid_languages()
    {
        $user = User::factory()->create(['credits' => 1.0]);
        $file = UploadedFile::fake()->create('test.mp3', 1000);

        $response = $this->actingAs($user)->post(route('audio.store'), [
            'audio' => $file,
            'source_language' => 'invalid',
            'target_language' => 'nl',
            'voice' => 'kore',
        ]);

        $response->assertSessionHasErrors(['source_language']);
    }

    public function test_user_can_delete_their_audio_file()
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $audioFile = AudioFile::create([
            'user_id' => $user->id,
            'original_filename' => 'test.mp3',
            'file_path' => 'audio/test.mp3',
            'file_size' => 1000,
            'source_language' => 'en',
            'target_language' => 'nl',
            'voice' => 'kore',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->delete(route('audio.destroy', $audioFile->id));

        $response->assertRedirect(route('audio.index'));
        $this->assertDatabaseMissing('audio_files', ['id' => $audioFile->id]);
    }
}
