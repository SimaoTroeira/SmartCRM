<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DataImportController extends Controller
{
    public function getAvailableTables()
    {
        try {
            $tables = DB::select("SHOW TABLES");
            $databaseName = config('database.connections.mysql.database');
            $keyName = 'Tables_in_' . $databaseName;


            $excludedTables = [
                'users',
                'password_resets',
                'oauth_access_tokens',
                'oauth_auth_codes',
                'oauth_clients',
                'oauth_personal_access_clients',
                'oauth_refresh_tokens',
                'personal_access_tokens',
                'failed_jobs',
                'migrations',
                'password_reset_tokens',
            ];

            $filteredTables = array_filter($tables, function ($table) use ($keyName, $excludedTables) {
                $tableName = $table->{$keyName};
                return !in_array($tableName, $excludedTables);
            });

            $tableNames = array_map(function ($table) use ($keyName) {
                return $table->{$keyName};
            }, $filteredTables);

            return response()->json(array_values($tableNames));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'database_error',
                'message' => 'Failed to retrieve tables list'
            ], 500);
        }
    }

    public function getTableColumns($table)
    {
        if (!Schema::hasTable($table)) {
            return response()->json(['error' => 'Tabela não encontrada'], 404);
        }

        $columns = Schema::getColumnListing($table);

        // Filtra colunas padrão
        $filteredColumns = array_filter($columns, function ($column) {
            return !in_array($column, [
                'created_at',
                'updated_at',
                'deleted_at',
                'user_id'
            ]);
        });

        return response()->json(array_values($filteredColumns));
    }

    public function storeMappedData(Request $request)
    {
        $request->validate([
            'target_table' => 'required|string',
            'mappings' => 'required|array',
            'rows' => 'required|array'
        ]);
    
        // Verifica se a tabela existe
        if (!Schema::hasTable($request->target_table)) {
            return response()->json(['error' => 'Tabela não encontrada'], 404);
        }
    
        $user = Auth::user();
        $successCount = 0;
    
        try {
            // Reseta o AUTO_INCREMENT para o valor correto se a tabela estiver vazia
            if (DB::table($request->target_table)->count() === 0) {
                try {
                    DB::statement('ALTER TABLE ' . $request->target_table . ' AUTO_INCREMENT = 1');
                } catch (\Exception $e) {
                    Log::error("Erro ao redefinir AUTO_INCREMENT: " . $e->getMessage());
                }
            } else {
                // Se a tabela não estiver vazia, ajusta o AUTO_INCREMENT para o maior valor de 'id' + 1
                DB::statement('ALTER TABLE ' . $request->target_table . ' AUTO_INCREMENT = ' . (DB::table($request->target_table)->max('id') + 1));
            }
    
            // Insere os dados mapeados
            foreach ($request->rows as $index => $row) {
                $columns = Schema::getColumnListing($request->target_table);
                $filteredData = array_intersect_key($row, array_flip($columns));
    
                // Remove qualquer user_id vindo do CSV
                unset($filteredData['user_id']);
    
                // Insere os dados para a tabela com o user_id do usuário logado
                DB::table($request->target_table)->insert(
                    array_merge($filteredData, ['user_id' => $user->id])
                );
    
                $successCount++;
                Log::info("Linha $index inserida para user_id: " . $user->id);
            }
    
            return response()->json([
                'message' => "$successCount registros importados com sucesso"
            ]);
        } catch (\Exception $e) {
            Log::error("Erro ao importar dados: " . $e->getMessage());
    
            return response()->json([
                'error' => 'Erro ao importar dados',
                'details' => $e->getMessage()
            ], 500);
        }
    }
    

    public function getUserData($tableName)
    {
        $user = Auth::user();

        if (!Schema::hasTable($tableName)) {
            return response()->json(['error' => 'Tabela não encontrada'], 404);
        }

        $data = DB::table($tableName)
            ->where('user_id', $user->id)
            ->get();

        return response()->json($data);
    }
}
