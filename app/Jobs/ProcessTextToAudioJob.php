<?php

namespace App\Jobs;

use App\Jobs\Concerns\HandlesProcessingFailure;
use App\Models\TextToAudio;
use App\Services\AudioProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessTextToAudioJob implements ShouldQueue
{
    use Dispatchable, HandlesProcessingFailure, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 300;

    public function __construct(
        public TextToAudio $textToAudio
    ) {}

    public function handle(AudioProcessingService $processingService): void
    {
        $audioPath = $processingService->generateAudio(
            $this->textToAudio->text_content,
            $this->textToAudio->language,
            $this->textToAudio->voice,
            $this->textToAudio->style_instruction
        );

        $this->textToAudio->update([
            'audio_path' => $audioPath,
            'status' => 'completed',
        ]);

        $processingService->deductCredits(
            $this->textToAudio->user,
            config('stripe.default_cost_per_translation'),
            'Credits used for text to audio conversion'
        );
    }

    protected function processingRecord(): ?Model
    {
        return $this->textToAudio;
    }
}
