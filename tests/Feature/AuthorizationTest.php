<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AudioFile;
use App\Models\TextToAudio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_another_users_audio_file()
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

        $response = $this->actingAs($user2)->get(route('audio.show', $audioFile->id));

        $response->assertForbidden();
    }

    public function test_user_cannot_download_another_users_audio_file()
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
            'translated_audio_path' => 'audio/translated.mp3',
        ]);

        $response = $this->actingAs($user2)->get(route('audio.download', $audioFile->id));

        $response->assertForbidden();
    }

    public function test_user_cannot_delete_another_users_audio_file()
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

        $response = $this->actingAs($user2)->delete(route('audio.destroy', $audioFile->id));

        $response->assertForbidden();
        $this->assertDatabaseHas('audio_files', ['id' => $audioFile->id]);
    }

    public function test_non_admin_cannot_access_admin_dashboard()
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_admin_dashboard()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }
}
