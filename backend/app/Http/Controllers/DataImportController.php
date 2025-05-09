<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DataImportController extends Controller
{

    public function storeMappedData(Request $request)
    {
        $request->validate([
            'table_name' => 'required|string',
            'data' => 'required|array',
            'types' => 'nullable|array',
            'company_id' => 'required|integer|exists:companies,id',
        ]);

        try {
            $user = Auth::user();
            $companyId = $request->company_id;

            // Verificar acesso
            $hasAccess = DB::table('user_company_roles')
                ->where('user_id', $user->id)
                ->where('company_id', $companyId)
                ->exists();

            if (!$hasAccess) {
                return response()->json(['error' => 'Acesso negado à empresa selecionada.'], 403);
            }

            $tableName = preg_replace('/[^a-zA-Z0-9_]/', '_', $request->table_name);
            $data = $request->data;

            // Obter caminho base e normalizar
            $configuredPath = config('smartcrm.storage_path');
            // $basePath = realpath($configuredPath) ?: storage_path('app/smartcrm');
            $basePath = config('smartcrm.storage_path');

            if (!$basePath || !is_string($basePath)) {
                $basePath = storage_path('app/smartcrm');
            }

            Log::info("Base path resolvido: $basePath");

            $importPath = $basePath . DIRECTORY_SEPARATOR . "empresa_id_$companyId" . DIRECTORY_SEPARATOR . "dados_importados";

            if (!File::exists($importPath)) {
                Log::info("Criando diretório: $importPath");
                File::makeDirectory($importPath, 0755, true);
            }

            $filePath = $importPath . DIRECTORY_SEPARATOR . "{$tableName}.json";

            Log::info("Tentando guardar ficheiro JSON em: $filePath");

            File::put($filePath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            Log::info("Ficheiro guardado com sucesso!");

            return response()->json([
                'message' => "Ficheiro JSON guardado com sucesso como '{$tableName}.json'",
                'path' => $filePath
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao importar dados: ' . $e->getMessage());

            return response()->json([
                'error' => 'Erro ao importar dados.',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    public function getUserCompanies()
    {
        try {
            $userId = Auth::id();

            // Traz empresas válidas (ativas e submetidas) associadas ao utilizador
            $companies = DB::table('companies')
                ->join('user_company_roles', 'companies.id', '=', 'user_company_roles.company_id')
                ->where('user_company_roles.user_id', $userId)
                ->where('companies.status', 'Ativo')
                ->where('companies.submitted', true)
                ->select('companies.id', 'companies.name')
                ->get();

            return response()->json($companies);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar empresas do utilizador: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao buscar empresas.'], 500);
        }
    }
}
