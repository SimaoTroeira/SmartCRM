<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class CampaignController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        if ($user->hasRole('SA')) {
            $campaigns = Campaign::with('company')->get();
        } else {
            $userCompanies = $user->companyRoles()->pluck('company_id')->toArray();

            if (empty($userCompanies)) {
                return response()->json(['error' => 'Você não tem empresas associadas.'], 403);
            }

            $campaigns = Campaign::with('company')
                ->whereIn('company_id', $userCompanies)
                ->get();
        }

        return response()->json($campaigns);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'company_id' => 'required|exists:companies,id',
        ]);

        $company = Company::find($validated['company_id']);

        if ($company->status !== 'Ativo') {
            return response()->json(['error' => 'Só é possível criar campanhas para empresas ativas.'], 403);
        }

        $user = Auth::user();
        $hasAccess = DB::table('user_company_roles')
            ->where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->where('role_id', 2) // CA
            ->exists();

        if (!$hasAccess && !$user->hasRole('SA')) {
            return response()->json(['error' => 'Apenas Company Admins podem criar campanhas.'], 403);
        }

        if (Campaign::count() === 0) {
            DB::statement('ALTER TABLE campaigns AUTO_INCREMENT = 1');
        }

        try {
            // Criar campanha na base de dados
            $campaign = Campaign::create($validated);

            // Criar estrutura de pastas
            $basePath = config('smartcrm.storage_path');
            $companyFolder = 'empresa_id_' . $company->id;
            $campaignFolder = 'campanha_id_' . $campaign->id;

            $campaignPath = $basePath
                . DIRECTORY_SEPARATOR . $companyFolder
                . DIRECTORY_SEPARATOR . 'campanhas'
                . DIRECTORY_SEPARATOR . $campaignFolder;

            if (!File::exists($campaignPath)) {
                File::makeDirectory($campaignPath, 0755, true);
            }

            return response()->json($campaign, 201);
        } catch (\Exception $e) {
            Log::error('Erro ao criar campanha: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Erro interno no servidor'], 500);
        }
    }


    public function show(Campaign $campaign)
    {
        $campaign->load('company');
        return response()->json($campaign);
    }

    public function update(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:draft,active,completed',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $companyId = $validated['company_id'] ?? $campaign->company_id;
        $company = Company::find($companyId);

        if (!$company || $company->status !== 'Ativo') {
            return response()->json(['error' => 'Só é possível atualizar campanhas de empresas ativas.'], 403);
        }

        $user = Auth::user();
        $hasAccess = DB::table('user_company_roles')
            ->where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->where('role_id', 2)
            ->exists();

        if (!$hasAccess && !$user->hasRole('SA')) {
            return response()->json(['error' => 'Apenas Company Admins podem editar campanhas.'], 403);
        }

        try {
            $campaign->update($validated);
            Log::info('Campanha atualizada com sucesso', ['campaign_id' => $campaign->id]);
            return response()->json($campaign);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar campanha: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao atualizar campanha.'], 500);
        }
    }

    public function destroy(Campaign $campaign)
    {
        $user = Auth::user();
        $company = Company::find($campaign->company_id);

        if (!$company || $company->status !== 'Ativo') {
            return response()->json(['error' => 'Não é possível eliminar campanhas de empresas inativas.'], 403);
        }

        $hasAccess = DB::table('user_company_roles')
            ->where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->where('role_id', 2)
            ->exists();

        if (!$hasAccess && !$user->hasRole('SA')) {
            return response()->json(['error' => 'Apenas Company Admins podem apagar campanhas.'], 403);
        }

        try {
            $campaign->delete();
            Log::info('Campanha excluída com sucesso', ['campaign_id' => $campaign->id]);
            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Erro ao excluir campanha: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao excluir campanha.'], 500);
        }
    }

    public function addUsers(Request $request, $id)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $campaign = Campaign::findOrFail($id);
        $user = Auth::user();
        $company = $campaign->company;

        $isCA = DB::table('user_company_roles')
            ->where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->where('role_id', 2)
            ->exists();

        if (!$isCA && !$user->hasRole('SA')) {
            return response()->json(['error' => 'Sem permissões para associar utilizadores.'], 403);
        }

        $campaign->users()->syncWithoutDetaching($request->user_ids);

        return response()->json(['message' => 'Utilizadores associados com sucesso.']);
    }

    public function getUsers(Campaign $campaign)
    {
        $users = $campaign->users()->with(['companyRoles' => function ($query) use ($campaign) {
            $query->where('company_id', $campaign->company_id);
        }, 'companyRoles.role'])->get();

        return $users->map(function ($user) use ($campaign) {
            $role = $user->companyRoles->firstWhere('company_id', $campaign->company_id);
            return [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $role ? $role->role->code : 'CU',
            ];
        });
    }

    public function removeUser(Campaign $campaign, User $user)
    {
        $authUser = Auth::user();
        $company = $campaign->company;

        if ($company->status !== 'Ativo') {
            return response()->json(['error' => 'Empresa inativa. Operação não permitida.'], 403);
        }

        $isCA = DB::table('user_company_roles')
            ->where('user_id', $authUser->id)
            ->where('company_id', $company->id)
            ->where('role_id', 2)
            ->exists();

        if (!$isCA && !$authUser->hasRole('SA')) {
            return response()->json(['error' => 'Sem permissões para remover utilizadores.'], 403);
        }

        if (!$campaign->users()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Utilizador não está associado à campanha.'], 404);
        }

        $campaign->users()->detach($user->id);

        return response()->json(['message' => 'Utilizador removido da campanha com sucesso.']);
    }

    public function concludeCampaign($id)
    {
        try {
            $campaign = Campaign::findOrFail($id);

            // Verifica se já está concluída
            if ($campaign->status === 'completed') {
                return response()->json(['message' => 'A campanha já está concluída.'], 400);
            }

            $campaign->status = 'completed';
            $campaign->end_date = now();
            $campaign->save();

            return response()->json(['message' => 'Campanha concluída com sucesso!', 'campaign' => $campaign]);
        } catch (\Exception $e) {
            Log::error('Erro ao concluir campanha: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao concluir campanha.'], 500);
        }
    }
}
