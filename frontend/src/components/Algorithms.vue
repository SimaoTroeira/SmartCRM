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
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                    <div>
                        <label class="block mb-1 font-medium">Selecione uma empresa:</label>
                        <select v-model="selectedCompanyId" class="form-control w-full max-w-xs">
                            <option disabled value="">-- Escolha uma empresa --</option>
                            <option v-for="company in companies" :key="company.id" :value="company.id"
                                :disabled="company.status === 'Inativo'">
                                {{ company.name }}
                                <span v-if="company.status === 'Inativo'">(Desativada)</span>
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1 font-medium">Selecione uma campanha:</label>
                        <select v-model="selectedCampaignId" class="form-control w-full max-w-xs"
                            :disabled="!selectedCompanyId">
                            <option disabled value="">-- Escolha uma campanha --</option>
                            <option v-for="campaign in campanhasDaCompany" :key="campaign.id" :value="campaign.id">
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
                            <option value="recommendation">Recomendação Apriori</option>
                        </select>
                    </div>
                </div>



                <AlgorithmWizard v-if="mostrarWizard && selectedCampaignId && selectedAlgorithm"
                    :campanha-id="selectedCampaignId" :algoritmo="selectedAlgorithm" @valido="handleValid"
                    class="mt-10 mb-6" />

                <!-- Botões -->
                <div class="mt-10 mb-6 flex flex-wrap gap-4 border-b border-gray-300 pb-4">
                    <button class="executar-btn"
                        :disabled="!selectedCampaignId || !selectedAlgorithm || loading || empresaInativa"
                        @click="reiniciarWizard">
                        Executar algoritmo
                    </button>
                    <button class="visualizar-btn"
                        :disabled="!selectedCampaignId || !selectedAlgorithm || empresaInativa" @click="fetchResults">
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
                    <p v-else-if="selectedAlgorithm === 'recommendation'" class="text-sm text-gray-700">
                        A <strong>Recomendação de Cross-Selling</strong> identifica produtos frequentemente comprados
                        juntos,
                        usando análise de regras de associação (Apriori).
                    </p>
                </div>

                <div v-if="errorMessage" class="text-red-600 font-medium mt-4">
                    {{ errorMessage }}
                </div>

                <!-- Visualizações por componente -->
                <div v-if="showResults">

                    <RfmResults v-if="selectedAlgorithm === 'rfm'" :results="results" :descricao="descricao"
                        :clientes-segmentados="clientesSegmentados" :scatter-clientes="scatterClientes"
                        :scatter-regioes="scatterRegioes" :nome-empresa="empresaSelecionada?.name"
                        :nome-campanha="campanhaSelecionada?.name" />

                    <ChurnResults v-if="selectedAlgorithm === 'churn'" :results="results" :descricao="descricao"
                        :campanha-id="selectedCampaignId" :nome-empresa="empresaSelecionada?.name"
                        :nome-campanha="campanhaSelecionada?.name" />

                    <RecommendResults v-else-if="selectedAlgorithm === 'recommendation'" :results="results"
                        :descricao="descricao" :empresa-id="empresaId" :campanha-id="selectedCampaignId"
                        :nome-empresa="empresaSelecionada?.name" :nome-campanha="campanhaSelecionada?.name" />
                </div>
            </div>
        </div>
    </div>
    <FloatingPanel :companies="companies" :campaigns="campaigns" :selectedCompanyId="selectedCompanyId"
        :selectedCampaignId="selectedCampaignId" :selectedAlgorithm="selectedAlgorithm" />

</template>


<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import AlgorithmWizard from '@/components/AlgorithmWizard.vue'
import FloatingPanel from '@/components/FloatingPanel.vue'
import RfmResults from '@/components/rfm/RfmResults.vue'
import ChurnResults from '@/components/churn/ChurnResults.vue'
import RecommendResults from '@/components/recommendation/RecommendResults.vue'


const companies = ref([])
const selectedCompanyId = ref('')

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


// Computed para obter company_id da campanha selecionada
const empresaId = computed(() => {
    const camp = campaigns.value.find(c => c.id === selectedCampaignId.value)
    return camp ? camp.company_id : null
})

const fetchCompanies = async () => {
    try {
        const res = await axios.get('http://127.0.0.1:8000/api/companies') // endpoint deve devolver apenas companies associadas ao user
        companies.value = res.data || []
    } catch (error) {
        toast.error('Erro ao carregar empresas.')
    }
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

let campaignStoredTemp = null

onMounted(() => {
    fetchCompanies()
    fetchCampaigns().then(() => {
        const algorithmStored = localStorage.getItem('selectedAlgorithm')
        const companyStored = localStorage.getItem('selectedCompanyId')
        campaignStoredTemp = localStorage.getItem('selectedCampaignId') // guardar temporariamente

        if (companyStored && companies.value.find(c => c.id === parseInt(companyStored))) {
            selectedCompanyId.value = parseInt(companyStored)
        }

        if (algorithmStored && ['rfm', 'churn', 'recommendation'].includes(algorithmStored)) {
            selectedAlgorithm.value = algorithmStored
        }
    })
})

const empresaSelecionada = computed(() => {
    return companies.value.find(c => c.id === parseInt(selectedCompanyId.value))
})

const empresaInativa = computed(() => {
    return empresaSelecionada.value?.status === 'Inativo'
})

const campanhaSelecionada = computed(() => {
    return campaigns.value.find(c => c.id === parseInt(selectedCampaignId.value))
})



const campanhasDaCompany = computed(() => {
    return campaigns.value.filter(c => {
        return c.company_id === parseInt(selectedCompanyId.value) && c.status !== 'Inativo'
    })
})


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

    if (empresaInativa.value) {
        toast.warning('Esta empresa está desativada. Não é possível executar algoritmos.')
        return
    }

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
        if (selectedAlgorithm.value === 'churn') {
            const churnRes = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados_complementares/${selectedCampaignId.value}?algoritmo=churn&tipo=clientes`)
            if (Array.isArray(churnRes.data) && churnRes.data.length > 0) {
                results.value = churnRes.data
                descricao.value = 'Previsão de churn baseada em recência, frequência, valor e tempo como cliente.'
                showResults.value = true
                toast.success('Resultados carregados automaticamente.')
                loading.value = false
                return
            }
        } else {
            const res = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados/${selectedCampaignId.value}?algoritmo=${selectedAlgorithm.value}`)

            const isRfmOk = selectedAlgorithm.value === 'rfm' && res.data?.dados?.length
            const isRecOk = selectedAlgorithm.value === 'recommendation' && Array.isArray(res.data)

            if (isRfmOk || isRecOk) {
                if (selectedAlgorithm.value === 'rfm') {
                    results.value = res.data.dados || []
                    descricao.value = res.data.descricao || ''

                    const empresaId = campaigns.value.find(c => c.id === selectedCampaignId.value)?.company_id
                    if (empresaId) {
                        const resClientes = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados_complementares/${selectedCampaignId.value}?algoritmo=rfm&tipo=clientes`)
                        clientesSegmentados.value = resClientes.data || []

                        const resScatterClientes = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados_complementares/${selectedCampaignId.value}?algoritmo=rfm&tipo=scatter_clientes`)
                        scatterClientes.value = resScatterClientes.data || []

                        const resScatterRegioes = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados_complementares/${selectedCampaignId.value}?algoritmo=rfm&tipo=scatter_regioes`)
                        scatterRegioes.value = resScatterRegioes.data || []
                    }
                } else if (selectedAlgorithm.value === 'recommendation') {
                    results.value = res.data || []
                    descricao.value = ''
                }

                showResults.value = true
                toast.success('Resultados carregados automaticamente.')
                loading.value = false
                return
            }
        }
    } catch (err) {
        console.error('Erro ao buscar resultados:', err)
    }

    if (tentativas < maxTentativas) {
        setTimeout(() => esperarResultados(tentativas + 1), intervalo)
    } else {
        toast.error('Os resultados ainda não estão prontos ou houve uma falha. Tente novamente mais tarde.')
        loading.value = false
    }
}


const fetchResults = async () => {
    if (!selectedCampaignId.value || !selectedAlgorithm.value) return

    if (empresaInativa.value) {
        toast.warning('Esta empresa está desativada. Não é possível visualizar resultados.')
        return
    }

    loading.value = true
    errorMessage.value = ''
    results.value = []
    descricao.value = ''
    showResults.value = false

    try {
        if (selectedAlgorithm.value === 'churn') {
            const res = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados_complementares/${selectedCampaignId.value}?algoritmo=churn&tipo=clientes`)

            if (!Array.isArray(res.data) || res.data.length === 0) {
                toast.error('O algoritmo ainda não foi executado para esta campanha.')
                loading.value = false
                return
            }

            results.value = res.data
            descricao.value = 'Previsão de churn baseada em recência, frequência, valor e tempo como cliente.'
            showResults.value = true
            toast.info('Resultados de churn carregados com sucesso.')
            return
        }


        const res = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados/${selectedCampaignId.value}?algoritmo=${selectedAlgorithm.value}`)

        if (selectedAlgorithm.value === 'rfm') {
            if (!res.data?.dados || res.data.dados.length === 0) {
                toast.error('O algoritmo ainda não foi executado para esta campanha.')
                loading.value = false
                return
            }
            results.value = res.data.dados
            descricao.value = res.data.descricao || ''
        } else if (selectedAlgorithm.value === 'recommendation') {
            if (!Array.isArray(res.data)) {
                toast.error('O algoritmo ainda não foi executado para esta campanha.')
                loading.value = false
                return
            }
            results.value = res.data
            descricao.value = ''
        }

        showResults.value = true

        const empresaId = campaigns.value.find(c => c.id === selectedCampaignId.value)?.company_id
        if (empresaId && selectedAlgorithm.value === 'rfm') {
            const resClientes = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados_complementares/${selectedCampaignId.value}?algoritmo=rfm&tipo=clientes`)
            clientesSegmentados.value = resClientes.data || []

            const resScatterClientes = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados_complementares/${selectedCampaignId.value}?algoritmo=rfm&tipo=scatter_clientes`)
            scatterClientes.value = resScatterClientes.data || []

            const resScatterRegioes = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados_complementares/${selectedCampaignId.value}?algoritmo=rfm&tipo=scatter_regioes`)
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


watch(selectedCampaignId, (novo) => {
    localStorage.setItem('selectedCampaignId', novo || '')
})

watch(selectedAlgorithm, (novo) => {
    localStorage.setItem('selectedAlgorithm', novo || '')
})

watch(selectedCompanyId, () => {
    selectedCampaignId.value = ''
    localStorage.setItem('selectedCompanyId', selectedCompanyId.value || '')

    const empresa = companies.value.find(c => c.id === parseInt(selectedCompanyId.value))
    if (empresa?.status === 'Inativo') {
        toast.warning('Esta empresa está desativada. Não é possível executar algoritmos nem ver resultados.')
    }
})


watch([selectedCompanyId, campaignsLoaded], ([companyId, loaded]) => {
    if (
        campaignStoredTemp &&
        companyId &&
        loaded &&
        campaigns.value.find(c => c.id === parseInt(campaignStoredTemp) && c.company_id === parseInt(companyId))
    ) {
        selectedCampaignId.value = parseInt(campaignStoredTemp)
        campaignStoredTemp = null
    }
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
    background: #fff;
    padding: 8px 16px;
    border-radius: 4px;
    transition: .3s;
    font-weight: 500;
    cursor: pointer;
}

.executar-btn:hover {
    background: #16a34a;
    color: #fff;
}

.visualizar-btn {
    margin-left: 8px;
    border: 2px solid #2563eb;
    color: #2563eb;
    background: #fff;
    padding: 8px 16px;
    border-radius: 4px;
    transition: .3s;
    font-weight: 500;
    cursor: pointer;
}

.visualizar-btn:hover {
    background: #2563eb;
    color: #fff;
}

.mt-10 {
    margin-top: 1rem !important;
}
</style>