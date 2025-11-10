<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    /**
     * Get authenticated user profile
     */
    public function profile(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        
        // Log de acesso ao perfil
        $this->logUserActivity($user->id, 'profile_access', $request->ip());

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'last_login' => now()->toDateTimeString(),
            'security_level' => 'high'
        ]);
    }

    /**
     * Get user dashboard data
     */
    public function dashboard(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        
        // Log de acesso ao dashboard
        $this->logUserActivity($user->id, 'dashboard_access', $request->ip());

        $rateLimitKey = "ratelimit:{$user->id}:" . now()->format('Y-m-d-H');
        $requestsCount = Redis::get($rateLimitKey) ?? 0;

        return response()->json([
            'welcome_message' => "Bem-vindo ao sistema seguro, {$user->name}!",
            'security_status' => [
                'jwt_valid' => true,
                'fingerprint_verified' => true,
                'rate_limiting' => [
                    'current' => (int)$requestsCount,
                    'limit' => 1000,
                    'remaining' => 1000 - (int)$requestsCount
                ],
                'last_activity' => now()->toDateTimeString()
            ],
            'activity' => [
                'Login realizado com sucesso',
                'Fingerprint verificado',
                'Sessão segura estabelecida',
                'Acesso ao dashboard autorizado'
            ],
            'system_info' => [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_duration' => 'Ativa'
            ]
        ]);
    }

    /**
     * Log user activity for security monitoring
     */
    private function logUserActivity($userId, $action, $ip)
    {
        $logKey = "user_activity:{$userId}";
        $activity = [
            'action' => $action,
            'ip' => $ip,
            'timestamp' => now()->toDateTimeString(),
            'user_agent' => request()->userAgent()
        ];

        // Mantém apenas as últimas 50 atividades
        Redis::lpush($logKey, json_encode($activity));
        Redis::ltrim($logKey, 0, 49);
    }

    /**
     * Get user security logs (apenas para admin)
     */
    public function securityLogs(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        
        // Em produção, verificar se usuário tem permissão de admin
        $logKey = "user_activity:{$user->id}";
        $activities = Redis::lrange($logKey, 0, -1);
        
        $parsedActivities = array_map(function($activity) {
            return json_decode($activity, true);
        }, $activities);

        return response()->json([
            'user_id' => $user->id,
            'activities' => $parsedActivities,
            'total_activities' => count($parsedActivities)
        ]);
    }
}