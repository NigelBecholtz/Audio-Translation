<?php

namespace Database\Seeders;

use App\Models\StyleInstructionPreset;
use App\Models\User;
use Illuminate\Database\Seeder;

class StylePresetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user or admin for default presets
        $firstUser = User::first();
        
        if (!$firstUser) {
            $this->command->warn('No users found - skipping style preset seeding');
            return;
        }

        $defaultPresets = [
            [
                'name' => 'Enthusiastic & Energetic',
                'instruction' => 'Speak with enthusiasm and high energy. Sound excited and passionate about the content. Use a lively, upbeat tone.',
            ],
            [
                'name' => 'Calm & Professional',
                'instruction' => 'Use a calm, measured, professional tone. Speak clearly and with confidence. Sound authoritative but approachable.',
            ],
            [
                'name' => 'Warm & Friendly',
                'instruction' => 'Speak in a warm, friendly, conversational tone. Sound like you are talking to a friend. Be approachable and engaging.',
            ],
            [
                'name' => 'British Accent - Formal',
                'instruction' => 'Speak with a formal British accent. Use received pronunciation (RP). Sound educated and articulate.',
            ],
            [
                'name' => 'British Accent - Casual',
                'instruction' => 'Speak with a casual British accent. Sound friendly and conversational with a UK English pronunciation.',
            ],
            [
                'name' => 'Storytelling Voice',
                'instruction' => 'Use a storytelling voice. Vary your pace and tone for dramatic effect. Sound engaging and captivating.',
            ],
            [
                'name' => 'News Presenter',
                'instruction' => 'Speak like a professional news presenter. Use a clear, authoritative tone. Sound confident and trustworthy.',
            ],
            [
                'name' => 'Relaxed & Soothing',
                'instruction' => 'Use a relaxed, soothing voice. Speak slowly and calmly. Sound peaceful and comforting.',
            ],
            [
                'name' => 'Motivational Speaker',
                'instruction' => 'Speak like a motivational speaker. Sound inspiring and uplifting. Use an energetic, encouraging tone.',
            ],
            [
                'name' => 'Documentary Narrator',
                'instruction' => 'Use a documentary narrator voice. Sound informative and engaging. Speak with clarity and authority on the subject.',
            ],
        ];

        foreach ($defaultPresets as $preset) {
            StyleInstructionPreset::updateOrCreate(
                [
                    'name' => $preset['name'],
                    'is_default' => true
                ],
                [
                    'user_id' => $firstUser->id,
                    'instruction' => $preset['instruction'],
                    'is_default' => true,
                ]
            );
        }

        $this->command->info('Created ' . count($defaultPresets) . ' default style presets');
    }
}
