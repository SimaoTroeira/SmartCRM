<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            // Ajustar o AUTO_INCREMENT com base no maior ID atual
            $lastId = DB::table('users')->max('id');
            $nextId = $lastId ? $lastId + 1 : 1;
            DB::statement("ALTER TABLE users AUTO_INCREMENT = $nextId");

            // Validação dos dados
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
            ], [
                'email.unique' => 'Este email já está em uso.',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // Criação do usuário
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Gerar token com Sanctum (plainTextToken)
            $token = $user->createToken('LaravelAuthApp')->plainTextToken;

            // Retorna o token e, se desejar, os dados do usuário
            return response()->json(['token' => $token, 'user' => $user], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('LaravelSanctumAuth')->plainTextToken;
            return response()->json(['token' => $token], 200);
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}
