<template>
    <div class="p-6">
        <h1 class="text-2xl font-bold mb-4">
            Algoritmos<span v-if="loading" class="dot-anim ml-1"></span>
        </h1>

        <div v-if="!campaignsLoaded" class="text-gray-500">
            A carregar dados da campanha<span class="dot-anim ml-1"></span>
        </div>

        <div v-else>
            <div v-if="!campaigns.length" class="text-gray-500">
                Ainda não tem campanhas para utilizar os algoritmos.
            </div>

            <div v-else class="space-y-4">
                <!-- DROPDOWNS -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8"> <!-- 👈 adiciona mb-8 aqui -->
                    <div>
                        <label class="block mb-1 font-medium">Selecione uma campanha:</label>
                        <select v-model="selectedCampaignId" class="form-control w-full max-w-xs">
                            <option disabled value="">-- Escolha uma campanha --</option>
                            <option v-for="campaign in campaigns" :key="campaign.id" :value="campaign.id">
                                {{ campaign.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1 font-medium">Selecione um algoritmo:</label>
                        <select v-model="selectedAlgorithm" class="form-control w-full max-w-xs">
                            <option disabled value="">-- Escolha um algoritmo --</option>
                            <option value="rfm">Segmentação RFM</option>
                            <option value="churn">Previsão de Churn</option>
                        </select>
                    </div>
                </div>

                <AlgorithmWizard v-if="mostrarWizard && selectedCampaignId && selectedAlgorithm"
                    :campanha-id="selectedCampaignId" :algoritmo="selectedAlgorithm" @valido="handleValid"
                    class="mt-10 mb-6" />

                <div class="flex flex-wrap gap-4 mt-6 pb-4 border-b border-gray-300"></div>
                <!-- BOTÕES -->
                <div class="mt-10 mb-6 flex flex-wrap gap-4 border-b border-gray-300 pb-4">
                    <button class="executar-btn" :disabled="!selectedCampaignId || !selectedAlgorithm || loading"
                        @click="reiniciarWizard">
                        Executar algoritmo
                    </button>

                    <button class="visualizar-btn" :disabled="!selectedCampaignId || !selectedAlgorithm"
                        @click="fetchResults">
                        Visualizar resultados
                    </button>
                </div>
                <!-- Descrição Geral do Algoritmo -->
                <div class="bg-white p-4 rounded-md shadow-sm border border-gray-200 mb-6 max-w-3xl">
                    <p v-if="selectedAlgorithm === 'rfm'" class="text-sm text-gray-700">
                        A segmentação <strong>RFM</strong> (Recência, Frequência e Valor Monetário) é uma técnica usada
                        para categorizar
                        clientes com base no tempo da última compra, frequência de compras e valor gasto. Com ela, é
                        possível identificar
                        os melhores clientes, os clientes inativos e aqueles com potencial de crescimento.
                    </p>
                    <p v-else-if="selectedAlgorithm === 'churn'" class="text-sm text-gray-700">
                        A <strong>Previsão de Churn</strong> utiliza análise de comportamento para estimar a
                        probabilidade de um cliente
                        deixar de interagir ou comprar, ajudando a antecipar estratégias de retenção e marketing
                        direcionado.
                    </p>
                </div>

                <div v-if="showResults" class="mt-6 flex justify-end items-center gap-4">
                    <label class="text-sm font-medium">Modo de visualização:</label>
                    <select v-model="visualizacao" class="form-control w-56">
                        <option value="resumo">Tabela</option>
                        <option value="graficos">Gráfico de Barras</option>
                        <option value="clusters">Estatísticas por Cluster</option>
                        <option value="clientes">Clientes Segmentados</option>
                    </select>
                </div>

                <div v-if="errorMessage" class="text-red-600 font-medium mt-4">
                    {{ errorMessage }}
                </div>

                <!-- Tabela -->
                <div v-if="visualizacao === 'resumo' && results.length" class="mt-6">
                    <h3 class="text-xl font-semibold mb-3 text-blue-700">Tabela</h3>
                    <p class="text-sm text-gray-600 mb-2">
                        Esta tabela apresenta as médias, totais e outros dados estatísticos de cada cluster gerado com
                        base nas métricas RFM.
                    </p>
                    <p class="text-sm text-gray-500 mb-4">{{ descricao }}</p>
                    <div class="overflow-x-auto">

                        <table class="min-w-full table-auto border border-gray-200 text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th v-for="(val, key) in results[0]" :key="key" class="px-4 py-2 border text-left">
                                        {{ nomeColunasCluster[key] || key }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, idx) in results" :key="idx">
                                    <td v-for="(val, key) in row" :key="key" class="px-4 py-2 border">
                                        {{ formatarValor(key, val) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Gráfico de Barras -->
                <div v-if="visualizacao === 'graficos' && results.length" class="mt-6">
                    <h3 class="text-xl font-semibold mb-3 text-blue-700">Gráfico de Barras</h3>
                    <p class="text-sm text-gray-600 mb-2">
                        Representação gráfica da quantidade de clientes pertencentes a cada segmento identificado na
                        análise RFM.
                    </p>
                    <div class="mb-3 text-sm text-gray-600">
                        Este gráfico mostra a quantidade de clientes por segmento identificado pelo algoritmo.
                    </div>
                    <div style="max-width: 600px; margin: auto;">
                        <canvas ref="graficoCanvas" class="grafico-canvas"></canvas>
                    </div>
                </div>

                <!-- Estatísticas por Cluster -->
                <div v-if="visualizacao === 'clusters' && clustersData.length" class="mt-6">
                    <h3 class="text-xl font-semibold mb-3 text-blue-700">Estatísticas por Cluster</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Abaixo estão os valores estatísticos agregados de cada cluster, como médias de recência,
                        frequência e valor monetário, para melhor comparação entre segmentos.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="(cluster, idx) in clustersData" :key="idx" class="p-4 bg-gray-50 border rounded-lg">
                            <h4 class="text-lg font-semibold text-green-700 mb-2">
                                Cluster {{ cluster.Cluster }} — {{ cluster.Segmento }}
                            </h4>
                            <p class="text-sm mb-2 text-gray-600">
                                Clientes: <strong>{{ cluster.QtdClientes }}</strong>
                            </p>
                            <div v-for="(valor, chave) in cluster"
                                v-if="chave !== 'Cluster' && chave !== 'Segmento' && chave !== 'QtdClientes'"
                                :key="chave">
                                <p class="text-sm text-gray-700">
                                    {{ nomeColunasCluster[chave] || chave }}:
                                    <strong>{{ formatarValor(chave, valor) }}</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Clientes Segmentados -->
                <div v-if="visualizacao === 'clientes' && clientesSegmentados.length" class="mt-6">
                    <h3 class="text-xl font-semibold mb-3 text-blue-700">Clientes Segmentados</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Esta visualização lista cada cliente individualmente, com o respetivo segmento atribuído e
                        métricas de compra, permitindo análises personalizadas.
                    </p>
                    <div class="mb-4">
                        <label class="text-sm font-medium">Filtrar por segmento:</label>
                        <select v-model="segmentoFiltro" class="form-control w-64">
                            <option value="">Todos</option>
                            <option v-for="seg in segmentosUnicos" :key="seg">{{ seg }}</option>
                        </select>
                    </div>

                    <div class="overflow-x-auto">
                        <!-- Controlo de ordenação e linhas por página -->
                        <div class="tabela-controles flex items-center justify-between mt-4">
                            <button @click="resetarOrdenacao" class="btn-reset">
                                Repor ordenação
                            </button>
                            <!-- Controle de linhas por página -->
                            <div class="tabela-footer">
                                <div class="linhas-por-pagina">
                                    <label class="text-sm font-medium text-gray-700 mr-2">Linhas por página:</label>
                                    <input v-model.number="limiteLinhas" type="number" min="1"
                                        class="border border-gray-300 rounded px-2 py-1 text-sm w-20" />
                                </div>
                            </div>
                        </div>

                        <table class="min-w-full table-auto border border-gray-200 text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th v-for="key in Object.keys(clientesSegmentados[0])" :key="key"
                                        v-if="key !== 'ClienteID'" @click="ordenarPor(key)"
                                        class="cursor-pointer px-4 py-2 border hover:bg-gray-100 select-none">
                                        {{ key === 'Nome' ? 'Nome do Cliente' : key }}
                                        <span v-if="colunaOrdenada === key">
                                            {{ ordemCrescente ? '▲' : '▼' }}
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in clientesFiltrados" :key="index">
                                    <td v-for="(val, key) in item" :key="key" v-if="key !== 'ClienteID'"
                                        class="px-4 py-2 border">
                                        {{ val }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                    </div>

                    <!-- Insights abaixo de qualquer visualização -->
                    <div v-if="showResults" class="mt-10">
                        <Insights :modo="visualizacao" :dados="obterDadosParaInsights" />
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>


<script setup>
import { ref, onMounted, watch, nextTick, computed } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import AlgorithmWizard from './AlgorithmWizard.vue'
import Chart from 'chart.js/auto'
import Insights from './Insights.vue'

const campaigns = ref([])
const campaignsLoaded = ref(false)
const selectedCampaignId = ref('')
const selectedAlgorithm = ref('rfm')
const results = ref([])
const descricao = ref('')
const loading = ref(false)
const errorMessage = ref('')
const toast = useToast()
const valid = ref(false)
const showResults = ref(false)
const mostrarWizard = ref(false)
const visualizacao = ref('resumo')
const clustersData = ref([])
const clientesSegmentados = ref([])
const segmentoFiltro = ref('')
const graficoCanvas = ref(null)
let chartInstance = null
const limiteLinhas = ref(50) // valor inicial
const colunaOrdenada = ref('')
const ordemCrescente = ref(true)


const nomeColunasCluster = {
    Cluster: "Cluster",
    Segmento: "Segmento",
    FrequencyMedia: "Frequência Média",
    MonetaryMedia: "Valor Médio (€)",
    MonetaryTotal: "Valor Total (€)",
    QtdClientes: "Nº de Clientes",
    RecencyMedia: "Recência Média (Em Dias)"
}

const fetchCampaigns = async () => {
    try {
        const res = await axios.get('http://127.0.0.1:8000/api/campaigns')
        campaigns.value = res.data || []
    } catch {
        toast.error('Erro ao carregar campanhas.')
    } finally {
        campaignsLoaded.value = true
    }
}

const handleValid = (value) => {
    valid.value = value

    if (value) {
        mostrarWizard.value = false
        runAlgorithm()
    }
}

function reiniciarWizard() {
    mostrarWizard.value = false
    nextTick(() => {
        mostrarWizard.value = true
    })
}


const runAlgorithm = async () => {
    if (!selectedCampaignId.value || !selectedAlgorithm.value) return
    mostrarWizard.value = true
    loading.value = true
    errorMessage.value = ''
    results.value = []
    descricao.value = ''
    showResults.value = false

    try {
        await axios.post(`http://127.0.0.1:8000/api/algoritmos/gerar`, {
            campanha_id: selectedCampaignId.value,
            algoritmo: selectedAlgorithm.value
        })
        toast.success('Algoritmo executado com sucesso. A aguardar resultados...')
        esperarResultados() // 👈 Inicia polling após execução
    } catch (err) {
        errorMessage.value = err.response?.data?.error || 'Erro ao processar dados da campanha.'
        loading.value = false
    }
}

const esperarResultados = async (tentativas = 0) => {
    const maxTentativas = 20
    const intervalo = 3000 // milissegundos

    try {
        const res = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados/${selectedCampaignId.value}?algoritmo=${selectedAlgorithm.value}`)
        if (res.data?.dados?.length) {
            results.value = res.data.dados
            descricao.value = res.data.descricao || ''
            showResults.value = true

            const empresaId = campaigns.value.find(c => c.id === selectedCampaignId.value)?.company_id
            if (empresaId) {
                const resClusters = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados/complementares/${selectedCampaignId.value}?tipo=clusters`)
                const resClientes = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados/complementares/${selectedCampaignId.value}?tipo=clientes`)
                clustersData.value = resClusters.data || []
                clientesSegmentados.value = resClientes.data || []
            }

            toast.success('Resultados carregados automaticamente.')
            if (visualizacao.value === 'graficos') await desenharGrafico()
            loading.value = false
            return
        }
    } catch (err) {
        // Continua tentando se status 202 ou erro temporário
    }

    if (tentativas < maxTentativas) {
        setTimeout(() => esperarResultados(tentativas + 1), intervalo)
    } else {
        toast.warning('Os resultados ainda não estão prontos. Tente novamente mais tarde.')
        loading.value = false
    }
}


const fetchResults = async () => {
    if (!selectedCampaignId.value || !selectedAlgorithm.value) return
    loading.value = true
    errorMessage.value = ''
    results.value = []
    descricao.value = ''
    showResults.value = false

    try {
        const res = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados/${selectedCampaignId.value}?algoritmo=${selectedAlgorithm.value}`)

        if (!res.data?.dados || res.data.dados.length === 0) {
            toast.error('O algoritmo ainda não foi executado para esta campanha.')
            loading.value = false
            return
        }

        results.value = res.data.dados
        descricao.value = res.data.descricao || ''
        showResults.value = true

        const empresaId = campaigns.value.find(c => c.id === selectedCampaignId.value)?.company_id
        if (empresaId) {
            const resClusters = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados/complementares/${selectedCampaignId.value}?tipo=clusters`)
            const resClientes = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados/complementares/${selectedCampaignId.value}?tipo=clientes`)
            clustersData.value = resClusters.data || []
            clientesSegmentados.value = resClientes.data || []
        }

        toast.info('Resultados carregados com sucesso.')

        if (visualizacao.value === 'graficos') await desenharGrafico()

    } catch (err) {
        if (err.response?.status === 202) {
            toast.info('Os resultados ainda estão a ser processados. Aguarde mais um momento.')
        } else {
            errorMessage.value = err.response?.data?.error || 'Erro ao buscar resultados.'
        }
    } finally {
        loading.value = false
    }
}



const desenharGrafico = async () => {
    await nextTick()
    const canvas = graficoCanvas.value
    if (!canvas || !results.value.length) {
        console.warn("Canvas ou dados ausentes para o gráfico.")
        return
    }

    const labels = results.value.map(item => item.Segmento || `Cluster ${item.Cluster}`)
    const valores = results.value.map(item =>
        Number(item.QtdClientes || item['Quantidade de Compras'] || 0)
    )


    console.log("Labels:", labels)
    console.log("Valores:", valores)

    if (chartInstance) {
        chartInstance.destroy()
        chartInstance = null
    }

    chartInstance = new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Clientes por Segmento',
                data: valores,
                backgroundColor: '#3b82f6'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: 'Distribuição de Clientes por Segmento'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    })
}

function formatarValor(key, val) {
    if (typeof val !== 'number') return val

    const lower = key.toLowerCase()

    // Formatação apenas para colunas esperadas no resumo
    if (['monetarymedia', 'valormédio', 'valortotal'].some(k => lower.includes(k))) {
        return new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'EUR' }).format(val)
    }

    if (lower.includes('recency')) {
        return `${Math.round(val)} dias`
    }

    if (lower.includes('frequency')) {
        return val.toFixed(2)
    }

    return val
}


const segmentosUnicos = computed(() => [...new Set(clientesSegmentados.value.map(c => c.Segmento))])

const clientesFiltrados = computed(() => {
    let filtrados = segmentoFiltro.value
        ? clientesSegmentados.value.filter(c => c.Segmento === segmentoFiltro.value)
        : [...clientesSegmentados.value]

    if (colunaOrdenada.value) {
        filtrados.sort((a, b) => {
            const valA = a[colunaOrdenada.value]
            const valB = b[colunaOrdenada.value]

            if (typeof valA === 'number' && typeof valB === 'number') {
                return ordemCrescente.value ? valA - valB : valB - valA
            }

            return ordemCrescente.value
                ? String(valA).localeCompare(String(valB))
                : String(valB).localeCompare(String(valA))
        })
    }

    return filtrados.slice(0, limiteLinhas.value || 50)
})


const obterDadosParaInsights = computed(() => {
    switch (visualizacao.value) {
        case 'clientes':
            return clientesSegmentados.value
        case 'resumo':
        case 'graficos':
        case 'clusters':
        default:
            return results.value
    }
})


function ordenarPor(coluna) {
    if (colunaOrdenada.value === coluna) {
        ordemCrescente.value = !ordemCrescente.value
    } else {
        colunaOrdenada.value = coluna
        ordemCrescente.value = true
    }
}

function resetarOrdenacao() {
    colunaOrdenada.value = ''
    ordemCrescente.value = true
}


watch(visualizacao, async (modo) => {
    if (modo === 'graficos' && showResults.value) {
        await desenharGrafico()
    }
})

watch([selectedCampaignId, selectedAlgorithm], () => {
    valid.value = false
    mostrarWizard.value = false

    results.value = []
    descricao.value = ''
    showResults.value = false
    clustersData.value = []
    clientesSegmentados.value = []
})


onMounted(() => {
    fetchCampaigns()
    const storedCampaign = localStorage.getItem('selectedCampaignId')
    const storedAlgorithm = localStorage.getItem('selectedAlgorithm')
    if (storedCampaign) selectedCampaignId.value = parseInt(storedCampaign)
    if (storedAlgorithm) selectedAlgorithm.value = storedAlgorithm
})
</script>


<style scoped>
.p-6 {
    padding: 20px;
}

.dot-anim::after {
    content: '.';
    animation: dots 1.2s steps(3, end) infinite;
}

@keyframes dots {
    0% {
        content: "";
    }

    33% {
        content: ".";
    }

    66% {
        content: "..";
    }

    100% {
        content: "...";
    }
}

.executar-btn {
    border: 2px solid #16a34a;
    color: #16a34a;
    background-color: white;
    padding: 8px 16px;
    border-radius: 4px;
    transition: all 0.3s ease;
    font-weight: 500;
    cursor: pointer;
}

.executar-btn:hover {
    background-color: #16a34a;
    color: white;
}

.visualizar-btn {
    margin-left: 8px;
    border: 2px solid #2563eb;
    color: #2563eb;
    background-color: white;
    padding: 8px 16px;
    border-radius: 4px;
    transition: all 0.3s ease;
    font-weight: 500;
    cursor: pointer;
}

.visualizar-btn:hover {
    background-color: #2563eb;
    color: white;
}

.flex.gap-4.mt-2 {
    align-items: center;
}

.grafico-canvas {
    width: 100% !important;
    max-width: 600px;
    height: 300px !important;
}

.mt-10 {
    margin-top: 1rem !important;
}

.tabela-controles {
    display: flex;
    gap: 20px;
}


.btn-reset {
    color: #2563eb;
    border: 1px solid #2563eb;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    transition: all 0.3s ease;
    background-color: white;
}

.btn-reset:hover {
    background-color: #e0ecff;
}
</style>