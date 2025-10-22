<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAudioProcessingLimits
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Set PHP limits for audio processing
        $maxUploadSize = config('audio.max_upload_size', 100);
        
        ini_set('upload_max_filesize', $maxUploadSize . 'M');
        ini_set('post_max_size', ($maxUploadSize + 10) . 'M');
        ini_set('max_execution_time', config('audio.max_execution_time', 600));
        ini_set('max_input_time', config('audio.max_input_time', 600));
        ini_set('memory_limit', config('audio.memory_limit', '512M'));
        
        return $next($request);
    }
}