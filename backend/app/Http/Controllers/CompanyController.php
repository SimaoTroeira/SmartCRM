<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();

            if ($user->hasRole('SA')) {
                $companies = Company::all();
            } else {
                $companies = Company::whereHas('userCompanyRoles', function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->whereNotNull('company_id');
                })->get();
            }

            return response()->json($companies);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        // Bloqueia Super Admin de registar empresas
        if (Auth::user()->hasRole('SA')) {
            return response()->json(['error' => 'Super Admin não pode criar empresas.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sector' => 'required|string|max:255',
            'draft' => 'nullable|boolean',
        ]);

        if (Company::count() === 0) {
            DB::statement('ALTER TABLE companies AUTO_INCREMENT = 1');
        }

        DB::beginTransaction();

        try {
            $company = Company::create([
                'name' => $validated['name'],
                'sector' => $validated['sector'],
                'draft' => $validated['draft'] ?? 0,
                'status' => 'Inativo',
            ]);

            DB::table('user_company_roles')->insert([
                'user_id' => Auth::id(),
                'company_id' => $company->id,
                'role_id' => $this->getRoleId('CA'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Empresa criada com sucesso.',
                'company' => $company
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao criar empresa: ' . $e->getMessage()], 500);
        }
    }

    private function getRoleId($roleCode)
    {
        $role = \App\Models\Role::where('code', $roleCode)->first();
        return $role ? $role->id : null;
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $company = Company::findOrFail($id);

        if ($company->status === 'Ativo') {
            if (!$user->hasRole('SA')) {
                return response()->json(['error' => 'Apenas o SuperAdmin pode editar empresas ativas.'], 403);
            }
        } else {
            // empresa inativa
            $hasAccess = DB::table('user_company_roles')
                ->where('user_id', $user->id)
                ->where('company_id', $id)
                ->where('role_id', $this->getRoleId('CA'))
                ->exists();

            if (!$hasAccess) {
                return response()->json(['error' => 'Apenas o Company Admin pode editar empresas inativas.'], 403);
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sector' => 'required|string|max:255',
            'draft' => 'nullable|boolean',
        ]);

        $company->update($validated);

        return response()->json($company);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $company = Company::findOrFail($id);

        // Se for SA, tem acesso total, mas depende do estado da empresa
        if ($user->hasRole('SA')) {
            if ($company->status === 'Ativo') {
                // Soft delete
                $company->delete();
                return response()->json(['message' => 'Empresa desativada (soft delete).']);
            } else {
                // Hard delete - remove relações também
                DB::table('user_company_roles')
                    ->where('company_id', $id)
                    ->delete();

                $company->forceDelete();
                return response()->json(['message' => 'Empresa removida permanentemente (hard delete).']);
            }
        }

        // Se não for SA, só pode apagar se for CA e a empresa estiver inativa
        if ($company->status !== 'Inativo') {
            return response()->json(['error' => 'Apenas empresas inativas podem ser apagadas por Company Admin.'], 403);
        }

        $hasAccess = DB::table('user_company_roles')
            ->where('user_id', $user->id)
            ->where('company_id', $id)
            ->where('role_id', $this->getRoleId('CA'))
            ->exists();

        if (!$hasAccess) {
            return response()->json(['error' => 'Apenas o Company Admin pode apagar empresas inativas.'], 403);
        }

        // Hard delete e remoção da linha de relação
        DB::table('user_company_roles')
            ->where('company_id', $id)
            ->delete();

        $company->forceDelete();
        return response()->json(['message' => 'Empresa removida permanentemente (hard delete).']);
    }

    public function approveCompany($id)
    {
        $company = Company::findOrFail($id);
    
        $company->status = 'Ativo';
        $company->save();
    
        // Verifica se já existe um user com role CA associado a esta empresa
        $existingCA = DB::table('user_company_roles')
            ->where('company_id', $company->id)
            ->where('role_id', $this->getRoleId('CA'))
            ->first();
    
        // Se não existir, busca o primeiro user ligado à empresa (quem criou) e atribui a role CA
        if (!$existingCA) {
            $creator = DB::table('user_company_roles')
                ->where('company_id', $company->id)
                ->first();
    
            if ($creator) {
                DB::table('user_company_roles')->updateOrInsert(
                    [
                        'user_id' => $creator->user_id,
                        'company_id' => $company->id,
                    ],
                    [
                        'role_id' => $this->getRoleId('CA'),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    
        return response()->json(['message' => 'Empresa aprovada com sucesso.']);
    }
    

    public function rejectCompany($id)
    {
        $company = Company::findOrFail($id);
        $company->delete();
        return response()->json(['message' => 'Empresa rejeitada e removida.']);
    }

    public function getUserRole(Request $request, $companyId)
    {
        try {
            $user = Auth::user();

            $userRole = DB::table('user_company_roles')
                ->where('user_id', $user->id)
                ->where('company_id', $companyId)
                ->first();

            if ($userRole) {
                $role = \App\Models\Role::find($userRole->role_id);
                return response()->json(['role' => $role->code]);
            } else {
                return response()->json(['error' => 'Nenhum papel encontrado'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
