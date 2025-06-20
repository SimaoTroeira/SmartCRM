<?php

namespace App\Http\Controllers;

use App\Models\Role;
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
            // Ajustar o AUTO_INCREMENT da tabela users
            $lastUserId = DB::table('users')->max('id');
            $nextUserId = $lastUserId ? $lastUserId + 1 : 1;
            DB::statement("ALTER TABLE users AUTO_INCREMENT = $nextUserId");

            // Validação dos dados
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                    'confirmed',
                ],
            ], [
                'email.unique' => 'Este email já está em uso.',
                'password.regex' => 'A password deve conter pelo menos uma letra maiúscula, uma minúscula e um número.',
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

            // Super Admin automático por email
            if ($user->email === 'admin@admin.com') {
                $superAdminRole = Role::where('code', 'SA')->first();

                if (!$superAdminRole) {
                    return response()->json(['error' => 'Role SA não encontrado.'], 500);
                }

                // Ajustar o AUTO_INCREMENT da tabela user_company_roles
                $lastRoleId = DB::table('user_company_roles')->max('id');
                $nextRoleId = $lastRoleId ? $lastRoleId + 1 : 1;
                DB::statement("ALTER TABLE user_company_roles AUTO_INCREMENT = $nextRoleId");

                DB::table('user_company_roles')->insert([
                    'user_id' => $user->id,
                    'company_id' => null,
                    'role_id' => $superAdminRole->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Gerar token com Sanctum (plainTextToken)
            $token = $user->createToken('LaravelAuthApp')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user->only(['id', 'name', 'email']),
            ], 200);
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
