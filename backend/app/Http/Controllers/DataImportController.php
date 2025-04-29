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
                'companies',
                'campaigns',
                'roles',
                'user_company_roles',
                'company_invites',
                'campaign_user',
                'accounts'
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
                'user_id',
                'campaign_id',
                'company_id',
            ]);
        });

        return response()->json(array_values($filteredColumns));
    }

    public function storeMappedData(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required|integer',
            'data' => 'required|array',
        ]);

        try {
            $campaignId = $request->campaign_id;
            $data = $request->data;

            // Buscar o company_id associado à campanha
            $campaign = DB::table('campaigns')->where('id', $campaignId)->first();

            if (!$campaign) {
                return response()->json([
                    'error' => 'Campanha não encontrada.'
                ], 404);
            }

            $companyId = $campaign->company_id; // Pega o company_id certo

            $successCount = 0;
            $updateCount = 0;

            foreach ($data as $index => $row) {
                $rowToUpsert = $row;
                $rowToUpsert['company_id'] = $companyId;
                $rowToUpsert['campaign_id'] = $campaignId;

                unset($rowToUpsert['id'], $rowToUpsert['created_at'], $rowToUpsert['updated_at'], $rowToUpsert['deleted_at']);

                // Tenta encontrar uma linha existente
                $query = DB::table('datasets')
                    ->where('company_id', $companyId)
                    ->where('customer_identifier', $row['customer_identifier'] ?? null);

                if (isset($row['transaction_identifier'])) {
                    $query->where('transaction_identifier', $row['transaction_identifier']);
                }
                if (isset($row['item_identifier'])) {
                    $query->where('item_identifier', $row['item_identifier']);
                }

                $existingRow = $query->first();

                if ($existingRow) {
                    // Atualiza apenas os campos novos
                    DB::table('datasets')
                        ->where('id', $existingRow->id)
                        ->update(array_filter($rowToUpsert)); // Só atualiza campos preenchidos
                    $updateCount++;
                } else {
                    // Se não existir, insere novo
                    DB::table('datasets')->insert($rowToUpsert);
                    $successCount++;
                }
            }

            return response()->json([
                'message' => "$successCount novos registros inseridos, $updateCount registros atualizados."
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao importar dados: ' . $e->getMessage());

            return response()->json([
                'error' => 'Erro ao importar dados.',
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
