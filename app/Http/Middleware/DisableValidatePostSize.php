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
        // Override PHP limits to allow large uploads
        @ini_set('upload_max_filesize', '50M');
        @ini_set('post_max_size', '50M');
        @ini_set('max_execution_time', 300);
        @ini_set('max_input_time', 300);
        @ini_set('memory_limit', '256M');
        
        // Skip validation for audio uploads - let the controller handle it
        if ($request->is('audio') && $request->isMethod('POST')) {
            return $next($request);
        }
        
        // For other requests, check content length
        $contentLength = $request->header('content-length');
        if ($contentLength && $contentLength > 50 * 1024 * 1024) {
            return response()->json([
                'error' => 'File too large. Maximum 50MB allowed.',
                'max_size' => '50MB',
                'received_size' => round($contentLength / 1024 / 1024, 2) . 'MB'
            ], 413);
        }
        
        return $next($request);
    }
}
