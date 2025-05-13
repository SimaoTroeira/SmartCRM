<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Jobs\ExecutarScriptPython;
use App\Models\Campaign;
use Illuminate\Http\Request;

class AlgorithmsController extends Controller
{
    public function executarScript(Request $request)
    {
        $request->validate([
            'campanha_id' => 'required|exists:campaigns,id',
            'algoritmo' => 'required|string'
        ]);

        $algoritmo = $request->input('algoritmo');
        $scriptMap = [
            'rfm' => 'rfm_segmentation.py',
            'churn' => 'churn_prediction.py',
        ];

        if (!array_key_exists($algoritmo, $scriptMap)) {
            return response()->json(['error' => 'Algoritmo inválido.'], 400);
        }

        $campanhaId = $request->input('campanha_id');
        $campanha = Campaign::with('company')->findOrFail($campanhaId);
        $empresaId = $campanha->company->id;

        $script = $scriptMap[$algoritmo];

        Log::info("✅ Disparando script {$script} para empresa {$empresaId}, campanha {$campanhaId}");
        ExecutarScriptPython::dispatch($script, $empresaId, $campanhaId);

        return response()->json([
            'message' => 'Script iniciado com sucesso',
            'empresa_id' => $empresaId,
            'campanha_id' => $campanhaId,
            'script' => $script
        ]);
    }

    public function obterResultadoPrincipal($campanhaId, Request $request)
    {
        $algoritmo = $request->query('algoritmo', 'rfm');

        $filenameMap = [
            'rfm' => 'resultados_rfm.json',
            'churn' => 'resultados_churn.json',
        ];

        if (!isset($filenameMap[$algoritmo])) {
            return response()->json(['error' => 'Algoritmo inválido.'], 400);
        }

        $campanha = Campaign::with('company')->findOrFail($campanhaId);
        $empresaId = $campanha->company->id;

        $basePath = config('smartcrm.storage_path');
        $jsonPath = $basePath . "/empresa_id_{$empresaId}/campanhas/campanha_id_{$campanhaId}/" . $filenameMap[$algoritmo];

        if (!File::exists($jsonPath)) {
            return response()->json(['message' => 'Ficheiro ainda não disponível.'], 202);
        }

        return response()->json(json_decode(File::get($jsonPath), true));
    }

    public function obterResultadoComplementar($campanhaId, Request $request)
    {
        $tipo = $request->query('tipo');

        $map = [
            'clientes' => 'clientes_segmentados_rfm.json',
            'clusters' => 'clusters_rfm.json',
        ];

        if (!isset($map[$tipo])) {
            return response()->json(['error' => 'Tipo inválido.'], 400);
        }

        $campanha = Campaign::with('company')->findOrFail($campanhaId);
        $empresaId = $campanha->company->id;

        $basePath = config('smartcrm.storage_path');
        $jsonPath = $basePath . "/empresa_id_{$empresaId}/campanhas/campanha_id_{$campanhaId}/" . $map[$tipo];

        if (!File::exists($jsonPath)) {
            return response()->json(['message' => 'Ficheiro ainda não disponível.'], 202);
        }

        return response()->json(json_decode(File::get($jsonPath), true));
    }

    public function verificarColunas($campanhaId, Request $request)
    {
        $algoritmo = $request->query('algoritmo', 'rfm'); // default para RFM

        $requisitosPorAlgoritmo = [
            'rfm' => [
                'vendas.json' => [
                    ['ClienteID', 'cliente_id', 'IDCliente'],
                    ['ValorTotal', 'Total', 'valor_total'],
                ],
                'clientes.json' => [
                    ['ClienteID', 'cliente_id', 'IDCliente'],
                ]
            ],
            'churn' => [
                'clientes.json' => [
                    ['ClienteID', 'cliente_id', 'IDCliente'],
                    ['Email', 'email'],
                    ['Cancelado', 'cancelado'],
                ],
            ],
        ];

        if (!isset($requisitosPorAlgoritmo[$algoritmo])) {
            return response()->json(['error' => 'Algoritmo inválido.'], 400);
        }

        $requisitos = $requisitosPorAlgoritmo[$algoritmo];

        $campanha = Campaign::with('company')->findOrFail($campanhaId);
        $empresaId = $campanha->company->id;

        $basePath = config('smartcrm.storage_path');
        $dadosPath = $basePath . "/empresa_id_{$empresaId}/dados_importados";

        if (!File::exists($dadosPath)) {
            return response()->json(['error' => 'Pasta de dados não encontrada.'], 404);
        }

        $ficheiros_presentes = [];
        $colunas_em_falta = [];

        $files = File::files($dadosPath);
        foreach ($files as $file) {
            if ($file->getExtension() !== 'json') continue;
            $filename = $file->getFilename();

            try {
                $conteudo = File::get($file->getPathname());
                $json = json_decode($conteudo, true);

                if (is_array($json) && count($json) > 0) {
                    $primeiraLinha = $json[0];
                    if (is_array($primeiraLinha)) {
                        $colunas = array_keys($primeiraLinha);
                        $nomeSemExtensao = pathinfo($filename, PATHINFO_FILENAME);
                        $ficheiros_presentes[$nomeSemExtensao] = $colunas;


                        if (isset($requisitos[$nomeSemExtensao . '.json'])) {
                            foreach ($requisitos[$filename] as $grupo) {
                                $encontrado = false;
                                foreach ($grupo as $alternativa) {
                                    if (in_array($alternativa, $colunas)) {
                                        $encontrado = true;
                                        break;
                                    }
                                }
                                if (!$encontrado) {
                                    $colunas_em_falta[$nomeSemExtensao][] = $grupo;
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                $ficheiros_presentes[$filename] = ['erro ao ler ficheiro'];
            }
        }

        $ficheiros_obrigatorios = array_keys($requisitos);
        $ficheirosPresentesSemExtensao = array_map(function ($f) {
            return pathinfo($f, PATHINFO_FILENAME);
        }, array_keys($ficheiros_presentes));
        $ficheiros_em_falta = [];

        foreach ($ficheiros_obrigatorios as $ficheiro) {
            $semExt = pathinfo($ficheiro, PATHINFO_FILENAME);
            if (!in_array($semExt, $ficheirosPresentesSemExtensao)) {
                $ficheiros_em_falta[] = $semExt;
            }
        }


        return response()->json([
            'ficheiros_presentes' => $ficheiros_presentes,
            'ficheiros_em_falta' => array_values($ficheiros_em_falta),
            'colunas_em_falta' => $colunas_em_falta,
        ]);
    }
}
