<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RateLimitFileUploads
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $identifier = $request->route('identifier');
        
        // Limite por IP: máximo 10 uploads por hora
        $ipKey = "upload_limit_ip_{$ip}";
        $ipCount = Cache::get($ipKey, 0);
        
        if ($ipCount >= 10) {
            return response()->json([
                'success' => false,
                'message' => 'Limite de uploads por hora excedido. Tente novamente mais tarde.'
            ], 429);
        }
        
        // Limite por identificador: máximo 5 uploads por hora para evitar spam
        $identifierKey = "upload_limit_identifier_{$identifier}";
        $identifierCount = Cache::get($identifierKey, 0);
        
        if ($identifierCount >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Muitos uploads para esta página. Tente novamente mais tarde.'
            ], 429);
        }
        
        $response = $next($request);
        
        // Se o upload foi bem-sucedido, incrementa os contadores
        if ($response->getStatusCode() === 200) {
            Cache::put($ipKey, $ipCount + 1, now()->addHour());
            Cache::put($identifierKey, $identifierCount + 1, now()->addHour());
        }
        
        return $response;
    }
}
