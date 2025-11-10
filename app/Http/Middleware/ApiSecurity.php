<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiSecurity
{
    public function handle(Request $request, Closure $next)
    {
        try {
            // 1. VALIDA JWT
            $token = $request->bearerToken();
            if (!$token) {
                return response()->json(['error' => 'Token não fornecido'], 401);
            }

            $user = JWTAuth::parseToken()->authenticate();
            
            // 2. VERIFICA REVOCAÇÃO (Redis)
            $isRevoked = Redis::get("revoked:{$user->id}");
            if ($isRevoked) {
                return response()->json(['error' => 'Token revogado'], 401);
            }

            // 3. VALIDA FINGERPRINT DO CLIENTE
            $clientFingerprint = $request->userAgent() . $request->ip();
            $expectedFp = $this->getUserFingerprint($user->id);

            if ($clientFingerprint !== $expectedFp) {
                Log::warning('Atividade suspeita detectada', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
                return response()->json(['error' => 'Atividade suspeita detectada'], 401);
            }

            // 4. RATE LIMITING POR USUÁRIO
            $requests = $this->incrementRateLimit($user->id);
            if ($requests > 1000) {
                return response()->json(['error' => 'Limite de requisições excedido'], 429);
            }

            return $next($request);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Falha na autenticação'], 401);
        }
    }

    private function getUserFingerprint($userId)
    {
        return Redis::get("fingerprint:{$userId}");
    }

    private function incrementRateLimit($userId)
    {
        $key = "ratelimit:{$userId}:" . now()->format('Y-m-d-H');
        $requests = Redis::incr($key);
        Redis::expire($key, 3600);
        return $requests;
    }
}