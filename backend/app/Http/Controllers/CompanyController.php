<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

class CompanyController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();

            $relations = [
                'campaigns',
                'invites',
                'userCompanyRoles.user',
                'userCompanyRoles.role'
            ];

            if ($user->hasRole('SA')) {
                $companies = Company::with($relations)
                    ->where('submitted', true)
                    ->get();
            } else {
                $companies = Company::with($relations)
                    ->whereHas('userCompanyRoles', function ($query) use ($user) {
                        $query->where('user_id', $user->id)
                            ->whereNotNull('company_id');
                    })
                    ->get();
            }

            return response()->json($companies);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar empresas: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao carregar empresas. Verifique o log do servidor.'], 500);
        }
    }

    public function store(Request $request)
    {
        if (Auth::user()->hasRole('SA')) {
            return response()->json(['error' => 'Super Admin não pode criar empresas.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:companies,name',
            'sector' => 'required|string|max:255',
        ]);

        if (Company::where('name', $validated['name'])->exists()) {
            return response()->json(['error' => 'Já existe uma empresa com esse nome.'], 422);
        }

        if (Company::count() === 0) {
            DB::statement('ALTER TABLE companies AUTO_INCREMENT = 1');
        }

        DB::beginTransaction();

        try {
            $company = Company::create([
                'name' => $validated['name'],
                'sector' => $validated['sector'],
                'status' => 'Inativo',
                'submitted' => false,
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

    public function submit($id)
    {
        $company = Company::findOrFail($id);

        if ($company->status === 'Ativo') {
            return response()->json(['error' => 'Empresa já está ativa.'], 400);
        }

        if ($company->submitted) {
            return response()->json(['error' => 'Empresa já foi submetida.'], 400);
        }

        $user = Auth::user();

        $hasAccess = DB::table('user_company_roles')
            ->where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->where('role_id', $this->getRoleId('CA'))
            ->exists();

        if (!$hasAccess) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $company->submitted = true;
        $company->save();

        return response()->json(['message' => 'Empresa submetida com sucesso.']);
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
        ]);

        $company->update($validated);

        return response()->json($company);
    }

    // public function destroy($id)
    // {
    //     $user = Auth::user();
    //     $company = Company::findOrFail($id);

    //     if ($user->hasRole('SA')) {
    //         if ($company->status === 'Ativo') {
    //             $company->delete();
    //             return response()->json(['message' => 'Empresa desativada (soft delete).']);
    //         } else {
    //             DB::table('user_company_roles')
    //                 ->where('company_id', $id)
    //                 ->delete();

    //             $company->forceDelete();
    //             return response()->json(['message' => 'Empresa removida permanentemente (hard delete).']);
    //         }
    //     }

    //     if ($company->status !== 'Inativo') {
    //         return response()->json(['error' => 'Apenas empresas inativas podem ser apagadas por Company Admin.'], 403);
    //     }

    //     $hasAccess = DB::table('user_company_roles')
    //         ->where('user_id', $user->id)
    //         ->where('company_id', $id)
    //         ->where('role_id', $this->getRoleId('CA'))
    //         ->exists();

    //     if (!$hasAccess) {
    //         return response()->json(['error' => 'Apenas o Company Admin pode apagar empresas inativas.'], 403);
    //     }

    //     DB::table('user_company_roles')
    //         ->where('company_id', $id)
    //         ->delete();

    //     $company->forceDelete();
    //     return response()->json(['message' => 'Empresa removida permanentemente (hard delete).']);
    // }

    public function approveCompany($id)
    {
        $company = Company::findOrFail($id);

        $company->status = 'Ativo';
        $company->save();

        $existingCA = DB::table('user_company_roles')
            ->where('company_id', $company->id)
            ->where('role_id', $this->getRoleId('CA'))
            ->first();

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

        // Criar pastas locais a partir da configuração
        try {
            // a usar config/smartcrm.php
            $basePath = config('smartcrm.storage_path');
            if (!File::exists($basePath)) {
                File::makeDirectory($basePath, 0755, true);
            }

            $companyFolderName = 'empresa_id_' . $company->id;
            $companyPath = $basePath . DIRECTORY_SEPARATOR . $companyFolderName;


            if (!File::exists($companyPath)) {
                File::makeDirectory($companyPath, 0755, true);
            }

            foreach (['campanhas', 'dados_importados'] as $sub) {
                $subPath = $companyPath . DIRECTORY_SEPARATOR . $sub;
                if (!File::exists($subPath)) {
                    File::makeDirectory($subPath, 0755, true);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erro ao criar diretórios para empresa: ' . $e->getMessage());
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

    public function show($id)
    {
        $company = Company::with([
            'campaigns',
            'userCompanyRoles.user',
            'userCompanyRoles.role',
            'invites'
        ])->findOrFail($id);

        return response()->json($company);
    }

    public function deactivate($id)
    {
        $company = Company::findOrFail($id);

        if ($company->status === 'Inativo') {
            return response()->json(['error' => 'Empresa já se encontra inativa.'], 400);
        }

        $company->status = 'Inativo';
        $company->submitted = false; // opcional: resetar pedido de validação
        $company->save();

        return response()->json(['message' => 'Empresa desativada com sucesso.']);
    }
}
