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
            // Recupera o usuário logado
            $user = Auth::user();
    
            // Verifica se o usuário é um SuperAdmin (SA)
            if ($user->hasRole('SA')) {
                // Se for SuperAdmin, retorna todas as empresas
                $companies = Company::all();
            } else {
                // Caso contrário, retorna apenas as empresas associadas ao usuário
                $companies = Company::whereHas('userCompanyRoles', function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->whereNotNull('company_id'); // Ignora registros com company_id NULL
                })->get();
            }
    
            // Retorna a resposta com as empresas
            return response()->json($companies);
        } catch (\Exception $e) {
            // Caso ocorra um erro, retorna a mensagem de erro
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    

    public function store(Request $request)
    {
        // Validação
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sector' => 'required|string|max:255',
        ]);

        // Verifica se não existem empresas
        if (Company::count() === 0) {
            // Resetar o AUTO_INCREMENT para 1
            DB::statement('ALTER TABLE companies AUTO_INCREMENT = 1');
        }

        DB::beginTransaction(); // Inicia a transação

        try {
            // Criação da empresa
            $company = Company::create([
                'name' => $validated['name'],
                'sector' => $validated['sector'],
            ]);

            // Regista a relação do usuário com a empresa (role CA)
            DB::table('user_company_roles')->insert([
                'user_id' => Auth::id(),
                'company_id' => $company->id,
                'role_id' => $this->getRoleId('CA'), // Método para obter o ID da role CA
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit(); // Confirma a transação

            return response()->json([
                'message' => 'Empresa criada com sucesso.',
                'company' => $company
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Reverte a transação em caso de erro
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
        $company = Company::findOrFail($id); // sem filtrar por Auth

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sector' => 'required|string|max:255',
        ]);

        $company->update($validated);

        return response()->json($company);
    }

    public function destroy($id)
    {
        $company = Company::findOrFail($id); // sem filtrar por Auth
        $company->delete();

        return response()->json(['message' => 'Empresa removida com sucesso.']);
    }
}
