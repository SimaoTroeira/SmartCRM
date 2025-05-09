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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                    :campanha-id="selectedCampaignId" :algoritmo="selectedAlgorithm" @valido="handleValid" />

                <div class="flex flex-wrap gap-4 mt-6 pb-4 border-b border-gray-300">
                    <button class="executar-btn" :disabled="!selectedCampaignId || !selectedAlgorithm || loading"
                        @click="verificarDadosAntesDeExecutar">
                        Executar algoritmo
                    </button>

                    <button class="visualizar-btn" :disabled="!selectedCampaignId || !selectedAlgorithm"
                        @click="fetchResults">
                        Visualizar resultados
                    </button>
                </div>

                <div v-if="showResults" class="mt-6 flex justify-end items-center">
                    <div class="text-sm font-medium mr-2">Modo de visualização:</div>
                    <select v-model="visualizacao" class="form-control w-40">
                        <option value="tabela">Tabela</option>
                        <option value="graficos">Gráficos</option>
                    </select>
                </div>

                <div v-if="errorMessage" class="text-red-600 font-medium mt-4">
                    {{ errorMessage }}
                </div>

                <div v-if="results.length > 0 && showResults">
                    <!-- Tabela -->
                    <div v-if="visualizacao === 'tabela'"
                        class="mt-10 bg-white shadow-md rounded-lg p-6 border border-gray-200">
                        <h3 class="text-xl font-semibold mb-4 text-blue-700">
                            Tabela: {{ selectedAlgorithm === 'rfm' ? 'Segmentação RFM' : 'Previsão de Churn' }}
                        </h3>

                        <div class="mb-3 text-sm text-gray-600">{{ descricao }}</div>

                        <div class="overflow-x-auto" style="max-height: 500px; overflow-y: auto">
                            <table class="min-w-full table-auto border border-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th v-for="(value, key) in results[0]" :key="key"
                                            class="px-4 py-2 border text-left">
                                            {{ key }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in results" :key="index">
                                        <td v-for="(value, key) in item" :key="key" class="px-4 py-2 border">
                                            {{ value }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Gráficos -->
                    <div v-else-if="visualizacao === 'graficos'"
                        class="mt-10 bg-white shadow-md rounded-lg p-6 border border-gray-200">
                        <h3 class="text-xl font-semibold mb-4 text-blue-700">
                            Gráfico: {{ selectedAlgorithm === 'rfm' ? 'Segmentação RFM' : 'Previsão de Churn' }}
                        </h3>

                        <div class="mb-3 text-sm text-gray-600">Este gráfico de barras representa uma análise visual do algoritmo
                            selecionado.</div>

                        <canvas id="graficoCanvas" class="grafico-canvas mx-auto"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import AlgorithmWizard from './AlgorithmWizard.vue'
import Chart from 'chart.js/auto'

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
const visualizacao = ref('tabela')
let chartInstance = null

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
    if (value) runAlgorithm()
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
        toast.success('Algoritmo executado com sucesso.', { timeout: 3000, closeOnClick: true })
    } catch (err) {
        errorMessage.value = err.response?.data?.error || 'Erro ao processar dados da campanha.'
    } finally {
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
        if (selectedAlgorithm.value === 'rfm' && res.data.segmentos) {
            results.value = res.data.segmentos
        } else {
            results.value = res.data.dados || []
        }
        descricao.value = res.data.descricao || ''
        showResults.value = results.value.length > 0

        if (results.value.length > 0) {
            toast.info('Resultados carregados com sucesso.', { timeout: 3000, closeOnClick: true })
            if (visualizacao.value === 'graficos') await desenharGrafico()
        }
    } catch (err) {
        if (err.response?.status === 202) {
            errorMessage.value = 'Os resultados do algoritmo ainda não foram realizados, aguarde uns segundos e tente mais tarde.'
        } else {
            errorMessage.value = err.response?.data?.error || 'Erro ao buscar os resultados.'
        }
    } finally {
        loading.value = false
    }
}

const desenharGrafico = async () => {
    await nextTick()
    const ctx = document.getElementById('graficoCanvas')
    if (!ctx || !results.value.length) return
    if (chartInstance) chartInstance.destroy()

    const labels = results.value.map(item => item.Segmento || `Cluster ${item.Cluster}`)
    const valores = results.value.map(item => item.QtdClientes || 0)

    chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Clientes',
                data: valores,
                backgroundColor: '#60a5fa'
            }]
        }
    })
}

watch(visualizacao, (modo) => {
    if (modo === 'graficos' && showResults.value) desenharGrafico()
})

const verificarDadosAntesDeExecutar = () => {
    if (!selectedCampaignId.value || !selectedAlgorithm.value) return
    mostrarWizard.value = true
}

watch(selectedCampaignId, (val) => {
    if (val) localStorage.setItem('selectedCampaignId', val)
})

watch(selectedAlgorithm, (val) => {
    if (val) localStorage.setItem('selectedAlgorithm', val)
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
    width: 100%;
    max-width: 600px;
    height: 300px;
}
</style>