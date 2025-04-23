<?php

namespace App\Http\Controllers;

use App\Models\UserCompanyRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserCompanyRoleController extends Controller
{
    /**
     * Promove um utilizador (CU -> CA) — só permitido para CA da mesma empresa.
     */
    public function promote($id)
    {
        try {
            $currentUser = Auth::user();
            $target = UserCompanyRole::with('company')->findOrFail($id);

            // Verifica se o papel atual é CU
            if ($target->role->code !== 'CU') {
                return response()->json(['error' => 'Apenas utilizadores CU podem ser promovidos.'], 403);
            }

            // Verifica se o current user é CA nesta empresa
            $isCA = UserCompanyRole::where('user_id', $currentUser->id)
                ->where('company_id', $target->company_id)
                ->whereHas('role', fn($q) => $q->where('code', 'CA'))
                ->exists();

            if (!$isCA) {
                return response()->json(['error' => 'Apenas Company Admins podem promover utilizadores da mesma empresa.'], 403);
            }

            // Promove o utilizador
            $target->role_id = \App\Models\Role::where('code', 'CA')->first()->id;
            $target->save();

            return response()->json(['message' => 'Utilizador promovido com sucesso.']);
        } catch (\Exception $e) {
            Log::error('Erro ao promover utilizador: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao promover utilizador.'], 500);
        }
    }
}
