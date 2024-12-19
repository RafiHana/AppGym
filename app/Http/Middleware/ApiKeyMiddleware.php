<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-KEY');
         $configApiKey = config('services.device.api_key');

        // Log API key yang diterima dan yang ada di konfigurasi
        logger()->info('API Key Received: ' . $apiKey);
        logger()->info('API Key from Config: ' . $configApiKey);
        
        if (!$apiKey || $apiKey !== config('services.device.api_key')) {
            return response()->json(['message' => 'Kunci API tidak valid'], 401);
        }

        return $next($request);
    }
}

        
            
        
    

        
    
