<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Credenciais inválidas'], 401);
        }

        // Salva fingerprint do cliente
        $fingerprint = $request->userAgent() . $request->ip();
        Redis::setex("fingerprint:{$user->id}", 3600 * 24, $fingerprint);

        $token = JWTAuth::fromUser($user, [
            'jti' => uniqid(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        
        // Revoga token
        Redis::setex("revoked:{$user->id}", 3600, '1');
        
        JWTAuth::parseToken()->invalidate();

        return response()->json(['message' => 'Logout realizado com sucesso']);
    }
}