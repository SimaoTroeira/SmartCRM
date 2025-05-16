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
                <!-- Dropdowns -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
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

                <!-- Botões -->
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

                <!-- Descrição -->
                <div class="bg-white p-4 rounded-md shadow-sm border border-gray-200 mb-6 max-w-3xl">
                    <p v-if="selectedAlgorithm === 'rfm'" class="text-sm text-gray-700">
                        A segmentação <strong>RFM</strong> (Recência, Frequência e Valor Monetário) é uma técnica usada
                        para categorizar clientes com base no tempo da última compra, frequência de compras e valor
                        gasto.
                    </p>
                    <p v-else-if="selectedAlgorithm === 'churn'" class="text-sm text-gray-700">
                        A <strong>Previsão de Churn</strong> estima a probabilidade de um cliente deixar de interagir ou
                        comprar.
                    </p>
                </div>

                <div v-if="errorMessage" class="text-red-600 font-medium mt-4">
                    {{ errorMessage }}
                </div>

                <!-- Visualizações por componente -->
                <div v-if="showResults">
                    <RfmResults v-if="selectedAlgorithm === 'rfm'" :results="results" :descricao="descricao"
                        :clientes-segmentados="clientesSegmentados" :scatter-clientes="scatterClientes"
                        :scatter-regioes="scatterRegioes" />
                    <ChurnResults v-if="selectedAlgorithm === 'churn'" :results="results" :descricao="descricao" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import AlgorithmWizard from '@/components/AlgorithmWizard.vue'
import RfmResults from '@/components/visualizations/RfmResults.vue'
import ChurnResults from '@/components/visualizations/ChurnResults.vue'

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
const clientesSegmentados = ref([])
const scatterClientes = ref([])
const scatterRegioes = ref([])


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
        esperarResultados()
    } catch (err) {
        errorMessage.value = err.response?.data?.error || 'Erro ao processar dados da campanha.'
        loading.value = false
    }
}

const esperarResultados = async (tentativas = 0) => {
    const maxTentativas = 20
    const intervalo = 3000

    try {
        const res = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados/${selectedCampaignId.value}?algoritmo=${selectedAlgorithm.value}`)
        if (res.data?.dados?.length) {
            results.value = res.data.dados
            descricao.value = res.data.descricao || ''
            showResults.value = true

            const empresaId = campaigns.value.find(c => c.id === selectedCampaignId.value)?.company_id
            if (empresaId && selectedAlgorithm.value === 'rfm') {
                const resClientes = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados_complementares/${selectedCampaignId.value}?algoritmo=${selectedAlgorithm.value}&tipo=clientes`)
                clientesSegmentados.value = resClientes.data || []

                const resScatterClientes = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados_complementares/${selectedCampaignId.value}?algoritmo=${selectedAlgorithm.value}&tipo=scatter_clientes`)
                scatterClientes.value = resScatterClientes.data || []

                const resScatterRegioes = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados_complementares/${selectedCampaignId.value}?algoritmo=${selectedAlgorithm.value}&tipo=scatter_regioes`)
                scatterRegioes.value = resScatterRegioes.data || []
            }

            toast.success('Resultados carregados automaticamente.')
            loading.value = false
            return
        }
    } catch (err) {
        console.error('Erro ao buscar resultados:', err)
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
        if (empresaId && selectedAlgorithm.value === 'rfm') {
            const resClientes = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados_complementares/${selectedCampaignId.value}?algoritmo=${selectedAlgorithm.value}&tipo=clientes`)
            clientesSegmentados.value = resClientes.data || []

            const resScatterClientes = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados_complementares/${selectedCampaignId.value}?algoritmo=${selectedAlgorithm.value}&tipo=scatter_clientes`)
            scatterClientes.value = resScatterClientes.data || []

            const resScatterRegioes = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados_complementares/${selectedCampaignId.value}?algoritmo=${selectedAlgorithm.value}&tipo=scatter_regioes`)
            scatterRegioes.value = resScatterRegioes.data || []
        }

        toast.info('Resultados carregados com sucesso.')
    } catch (err) {
        if (err.response?.status === 202) {
            toast.info('Os resultados ainda estão a ser processados.')
        } else {
            errorMessage.value = err.response?.data?.error || 'Erro ao buscar resultados.'
        }
    } finally {
        loading.value = false
    }
}


watch([selectedCampaignId, selectedAlgorithm], () => {
    valid.value = false
    mostrarWizard.value = false
    results.value = []
    descricao.value = ''
    showResults.value = false
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