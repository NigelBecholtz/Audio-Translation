<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ThrottleWebhooks
{
    /**
     * Maximum webhook requests per minute per IP
     */
    private const MAX_ATTEMPTS = 60;
    
    /**
     * Time window in minutes
     */
    private const DECAY_MINUTES = 1;
    
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $key = 'webhook_throttle:' . $ip;
        
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= self::MAX_ATTEMPTS) {
            Log::warning('Webhook rate limit exceeded', [
                'ip' => $ip,
                'attempts' => $attempts,
                'path' => $request->path()
            ]);
            
            return response()->json([
                'error' => 'Too many requests. Please try again later.'
            ], 429);
        }
        
        // Increment counter
        Cache::put($key, $attempts + 1, now()->addMinutes(self::DECAY_MINUTES));
        
        return $next($request);
    }
}

