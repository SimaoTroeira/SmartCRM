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
            'recommendation' => 'recomendacao.py',
        ];

        if (!array_key_exists($algoritmo, $scriptMap)) {
            return response()->json(['error' => 'Algoritmo inválido.'], 400);
        }

        $campanhaId = $request->input('campanha_id');
        $campanha = Campaign::with('company')->findOrFail($campanhaId);
        $empresaId = $campanha->company->id;

        $script = $scriptMap[$algoritmo];

        Log::info("Disparando script {$script} para empresa {$empresaId}, campanha {$campanhaId}");
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
            'recommendation' => 'recomendacoes_produto.json',
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
        $algoritmo = $request->query('algoritmo', 'rfm');

        $map = [
            'rfm' => [
                'clientes' => 'clientes_segmentados_rfm.json',
                'scatter_clientes' => 'clusters.json',
                'scatter_regioes' => 'clusters_por_regiao.json',
            ],
            'churn' => [
                'clientes' => 'clientes_churn.json',
            ],
            'recommendation' => [
                'produto' => 'recomendacoes_produto.json'
            ],
        ];

        if (!isset($map[$algoritmo][$tipo])) {
            Log::warning("Algoritmo ou tipo inválido: algoritmo={$algoritmo}, tipo={$tipo}");
            return response()->json(['error' => 'Tipo ou algoritmo inválido.'], 400);
        }

        $campanha = Campaign::with('company')->findOrFail($campanhaId);
        $empresaId = $campanha->company->id;

        $basePath = config('smartcrm.storage_path');
        $jsonPath = $basePath . "/empresa_id_{$empresaId}/campanhas/campanha_id_{$campanhaId}/" . $map[$algoritmo][$tipo];

        Log::info("A verificar ficheiro complementar em: {$jsonPath}");

        if (!File::exists($jsonPath)) {
            Log::warning("Ficheiro não encontrado em: {$jsonPath}");
            return response()->json(['message' => 'Ficheiro ainda não disponível.'], 202);
        }

        $conteudo = File::get($jsonPath);
        Log::info("Conteúdo lido (primeiros 200 caracteres): " . substr($conteudo, 0, 200));

        $data = json_decode($conteudo, true);

        if (empty($data)) {
            Log::warning("Ficheiro lido com sucesso, mas sem dados interpretáveis. Caminho: {$jsonPath}");
        }

        return response()->json($data);
    }

    public function verificarColunas($campanhaId, Request $request)
    {
        $algoritmo = $request->query('algoritmo', 'rfm');

        if ($algoritmo === 'recomendacao') {
            $algoritmo = 'recommendation';
        }

        $requisitosPorAlgoritmo = [
            'rfm' => [
                'vendas.json' => [
                    ['ClienteID'],
                    ['ValorTotal'],
                ],
                'clientes.json' => [
                    ['ClienteID'],
                    ['Regiao'],
                ],
                'produtos.json' => [
                    ['ProdutoID'],
                    ['NomeProduto'],
                    ['Categoria'],
                    ['Marca'],
                ],
            ],
            'churn' => [
                'clientes.json' => [
                    ['ClienteID'],
                    ['DataCadastro'],
                    ['UltimaCompra'],
                    ['TotalCompras'],
                    ['ValorTotalGasto'],
                ],
                'vendas.json' => [
                    ['ClienteID'],
                    ['DataVenda'],
                ],
            ],
            'recommendation' => [
                'vendas.json' => [
                    ['ClienteID'],
                ],
                'produtos.json' => [
                    ['ProdutoID'],
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
