<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAgentToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $providedToken = $request->header('X-Agent-Token');
        $expectedToken = config('services.agente.token') ?: env('AGENT_API_TOKEN', 'ugel_agente_hardware_secret_token_2026');

        if (empty($providedToken) || !hash_equals((string) $expectedToken, (string) $providedToken)) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Token de agente no proporcionado o inválido (X-Agent-Token).',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
