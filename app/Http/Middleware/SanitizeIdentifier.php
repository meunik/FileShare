<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeIdentifier
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $identifier = $request->route('identifier');
        
        if ($identifier) {
            // Remove caracteres perigosos e limita o tamanho
            $sanitized = preg_replace('/[^a-zA-Z0-9\-_]/', '', $identifier);
            $sanitized = substr($sanitized, 0, 255);
            
            // Se o identificador foi alterado, redireciona
            if ($sanitized !== $identifier) {
                if (empty($sanitized)) {
                    return redirect('/')->with('error', 'Identificador inválido.');
                }
                return redirect("/{$sanitized}");
            }
            
            // Validação adicional - não pode estar vazio após sanitização
            if (empty($sanitized)) {
                return redirect('/')->with('error', 'Identificador não pode estar vazio.');
            }
        }
        
        return $next($request);
    }
}
