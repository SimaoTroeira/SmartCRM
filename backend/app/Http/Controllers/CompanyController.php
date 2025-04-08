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
        return Company::all();
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
    
        // Criação da empresa
        $company = Company::create([
            'name' => $validated['name'],
            'sector' => $validated['sector'],
        ]);
    
        return response()->json($company, 201);
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
