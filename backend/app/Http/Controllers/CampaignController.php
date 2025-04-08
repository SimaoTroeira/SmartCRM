<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CampaignController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }
    
        // Obter todas as campanhas
        $campaigns = Campaign::with('company')->get(); // 'company' assume que você tem a relação no modelo Campaign
        return response()->json($campaigns);
    }
    
    
    public function store(Request $request)
    {
        // Validação dos dados
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'company_id' => 'required|exists:companies,id',
        ]);

                // Verifica se não existem campanhas
                if (Campaign::count() === 0) {
                    // Resetar o AUTO_INCREMENT para 1
                    DB::statement('ALTER TABLE campaigns AUTO_INCREMENT = 1');
                }

        try {
            // Criação da campanha
            $campaign = Campaign::create($validated);

            return response()->json($campaign, 201);
        } catch (\Exception $e) {
            // Loga o erro para facilitar o diagnóstico
            Log::error('Erro ao criar campanha: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            
            return response()->json(['error' => 'Erro interno no servidor'], 500);
        }
    }
    

    public function show(Campaign $campaign)
    {
        return response()->json($campaign);
    }

    public function update(Request $request, Campaign $campaign)
    {
    
        // Validar os dados
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:draft,active,completed',
        ]);
    
        // Log para verificar os dados validados
        Log::info('Dados validados', $validated);
    
        try {
            // Atualizar a campanha com os dados validados
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
    
        try {
            // Deletar a campanha
            $campaign->delete();
    
            // Log para registrar a exclusão
            Log::info('Campanha excluída com sucesso', ['campaign_id' => $campaign->id]);
    
            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Erro ao excluir campanha: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao excluir campanha.'], 500);
        }
    }
    
}
