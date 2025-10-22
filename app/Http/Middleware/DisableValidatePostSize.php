<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisableValidatePostSize
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Override PHP limits to allow large uploads (from config)
        $maxSize = config('audio.max_file_size', 50);
        @ini_set('upload_max_filesize', $maxSize . 'M');
        @ini_set('post_max_size', $maxSize . 'M');
        @ini_set('max_execution_time', config('audio.max_execution_time', 600));
        @ini_set('max_input_time', config('audio.max_input_time', 600));
        @ini_set('memory_limit', config('audio.memory_limit', '512M'));
        
        // Skip validation for audio uploads - let the controller handle it
        if ($request->is('audio') && $request->isMethod('POST')) {
            return $next($request);
        }
        
        // For other requests, check content length
        $contentLength = $request->header('content-length');
        $maxBytes = $maxSize * 1024 * 1024;
        if ($contentLength && $contentLength > $maxBytes) {
            return response()->json([
                'error' => 'File too large. Maximum ' . $maxSize . 'MB allowed.',
                'max_size' => $maxSize . 'MB',
                'received_size' => round($contentLength / 1024 / 1024, 2) . 'MB'
            ], 413);
        }
        
        return $next($request);
    }
}
