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
            $target = UserCompanyRole::with('company', 'role')->findOrFail($id);

            if ($target->role->code !== 'CU') {
                return response()->json(['error' => 'Apenas utilizadores CU podem ser promovidos.'], 403);
            }

            $isCA = UserCompanyRole::where('user_id', $currentUser->id)
                ->where('company_id', $target->company_id)
                ->whereHas('role', fn($q) => $q->where('code', 'CA'))
                ->exists();

            if (!$isCA) {
                return response()->json(['error' => 'Apenas Company Admins podem promover utilizadores da mesma empresa.'], 403);
            }

            $target->role_id = \App\Models\Role::where('code', 'CA')->first()->id;
            $target->save();

            return response()->json(['message' => 'Utilizador promovido com sucesso.']);
        } catch (\Exception $e) {
            Log::error('Erro ao promover utilizador: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao promover utilizador.'], 500);
        }
    }

    /**
     * Remove um utilizador CU da empresa (soft delete).
     */
    public function destroy($id)
    {
        try {
            $currentUser = Auth::user();
            $ucr = UserCompanyRole::with('role')->findOrFail($id);

            if ($ucr->role->code !== 'CU') {
                return response()->json(['error' => 'Apenas utilizadores CU podem ser removidos.'], 403);
            }

            $isCA = UserCompanyRole::where('user_id', $currentUser->id)
                ->where('company_id', $ucr->company_id)
                ->whereHas('role', fn($q) => $q->where('code', 'CA'))
                ->exists();

            if (!$isCA) {
                return response()->json(['error' => 'Apenas Company Admins podem remover utilizadores da empresa.'], 403);
            }

            $ucr->delete();

            return response()->json(['message' => 'Utilizador removido da empresa.']);
        } catch (\Exception $e) {
            Log::error('Erro ao remover utilizador: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao remover utilizador.'], 500);
        }
    }
}
