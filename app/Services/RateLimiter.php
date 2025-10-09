<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RateLimiter
{
    private $cacheKeyPrefix = 'rate_limit:';
    
    /**
     * Check if rate limit is exceeded
     * 
     * @param string $key Unique identifier for the rate limit (e.g., 'gemini_tts')
     * @param int $maxAttempts Maximum number of attempts allowed
     * @param int $decayMinutes Time window in minutes
     * @return bool True if rate limit is exceeded
     */
    public function tooManyAttempts(string $key, int $maxAttempts, int $decayMinutes): bool
    {
        $cacheKey = $this->cacheKeyPrefix . $key;
        $attempts = Cache::get($cacheKey, 0);
        
        if ($attempts >= $maxAttempts) {
            Log::warning('Rate limit exceeded', [
                'key' => $key,
                'attempts' => $attempts,
                'max_attempts' => $maxAttempts,
                'decay_minutes' => $decayMinutes
            ]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Increment the counter for a given key
     * 
     * @param string $key
     * @param int $decayMinutes
     * @return int Current number of attempts
     */
    public function hit(string $key, int $decayMinutes = 1): int
    {
        $cacheKey = $this->cacheKeyPrefix . $key;
        $attempts = Cache::get($cacheKey, 0) + 1;
        
        Cache::put($cacheKey, $attempts, now()->addMinutes($decayMinutes));
        
        return $attempts;
    }
    
    /**
     * Get remaining attempts
     * 
     * @param string $key
     * @param int $maxAttempts
     * @return int
     */
    public function remaining(string $key, int $maxAttempts): int
    {
        $cacheKey = $this->cacheKeyPrefix . $key;
        $attempts = Cache::get($cacheKey, 0);
        
        return max(0, $maxAttempts - $attempts);
    }
    
    /**
     * Get available time until rate limit resets
     * 
     * @param string $key
     * @param int $decayMinutes Default decay time if TTL cannot be determined
     * @return int Seconds until reset, 0 if not rate limited
     */
    public function availableIn(string $key, int $decayMinutes = 1): int
    {
        $cacheKey = $this->cacheKeyPrefix . $key;
        
        if (!Cache::has($cacheKey)) {
            return 0;
        }
        
        // Try to get TTL from cache store if Redis is available
        try {
            $store = Cache::getStore();
            
            // Check if Redis driver is being used
            if (method_exists($store, 'getRedis')) {
                $ttl = $store->getRedis()->ttl($cacheKey);
                return $ttl > 0 ? $ttl : 0;
            }
            
            // For non-Redis drivers (database, file, etc), estimate TTL
            // Return the decay time in seconds as approximation
            Log::debug('RateLimiter: Non-Redis cache driver detected, using estimated TTL', [
                'driver' => config('cache.default')
            ]);
            return $decayMinutes * 60;
            
        } catch (\Exception $e) {
            Log::warning('RateLimiter: Failed to get TTL from cache', [
                'error' => $e->getMessage(),
                'driver' => config('cache.default')
            ]);
            // Return estimated time
            return $decayMinutes * 60;
        }
    }
    
    /**
     * Clear rate limit for a key
     * 
     * @param string $key
     * @return void
     */
    public function clear(string $key): void
    {
        $cacheKey = $this->cacheKeyPrefix . $key;
        Cache::forget($cacheKey);
        
        Log::info('Rate limit cleared', ['key' => $key]);
    }
    
    /**
     * Execute a callback with rate limiting
     * 
     * @param string $key
     * @param int $maxAttempts
     * @param int $decayMinutes
     * @param callable $callback
     * @return mixed
     * @throws \Exception
     */
    public function attempt(string $key, int $maxAttempts, int $decayMinutes, callable $callback)
    {
        if ($this->tooManyAttempts($key, $maxAttempts, $decayMinutes)) {
            $availableIn = $this->availableIn($key, $decayMinutes);
            throw new \Exception(
                "Rate limit exceeded for '{$key}'. Try again in {$availableIn} seconds. " .
                "Remaining: {$this->remaining($key, $maxAttempts)}/{$maxAttempts}"
            );
        }
        
        $this->hit($key, $decayMinutes);
        
        return $callback();
    }
}
