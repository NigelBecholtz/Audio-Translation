<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomValidatePostSize
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Increase PHP limits for large file uploads
        ini_set('upload_max_filesize', '50M');
        ini_set('post_max_size', '50M');
        ini_set('max_execution_time', 300);
        ini_set('max_input_time', 300);
        ini_set('memory_limit', '256M');
        
        // Check if the request is too large (50MB limit)
        $contentLength = $request->header('content-length');
        if ($contentLength && $contentLength > 50 * 1024 * 1024) {
            return response()->json([
                'error' => 'File too large. Maximum 50MB allowed.',
                'max_size' => '50MB'
            ], 413);
        }
        
        return $next($request);
    }
}
