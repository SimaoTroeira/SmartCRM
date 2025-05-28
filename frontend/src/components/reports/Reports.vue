<template>
    <div class="p-6">
        <h1 class="text-2xl font-bold text-blue-700 mb-6">Relatórios Personalizados</h1>

        <div class="mb-8">
            <button class="btn-add" @click="adicionarPipeline">
                <i class="bi bi-plus-circle"></i> Nova Exportação
            </button>
        </div>

        <div v-for="(pipe, index) in pipelines" :key="index" class="card-pipeline">
            <div class="flex justify-end mb-2">
                <button class="btn-remove btn-remove-top" @click="confirmarRemocao(index)" title="Remover exportação">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
            <div class="flex justify-between items-start">
                <div class="w-full md:w-3/4">
                    <p class="text-sm text-gray-600 mb-1 font-medium">Campanha:</p>
                    <select v-model="pipe.campanhaId" class="form-control mb-4 w-full">
                        <option disabled value="">-- Escolha uma campanha --</option>
                        <option v-for="c in campaigns" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>

                <div class="flex flex-col gap-2 items-end mt-4 md:mt-0">
                    <button class="btn-edit" @click="editarPipeline(index)">
                        <i class="bi bi-pencil"></i> Editar
                    </button>
                    <button class="btn-download" @click="exportarPipeline(index)">
                        <i class="bi bi-download"></i> Transferir
                    </button>
                </div>
            </div>
        </div>

        <PipelineModal
            :visible="mostrarModal"
            :pipeline="pipelineAtual"
            :segmentosDisponiveis="segmentosUnicos"
            :resultadosAlgoritmos="resultadosAlgoritmos"
            @close="fecharModal"
            @save="guardarEdicao"
        />

        <div v-if="mostrarConfirmacao" class="modal-backdrop">
            <div class="modal-content">
                <p class="text-md font-medium mb-4">Tem a certeza que deseja remover esta exportação?</p>
                <div class="flex justify-end gap-4">
                    <button class="btn-cancel" @click="mostrarConfirmacao = false">Cancelar</button>
                    <button class="btn-confirm" @click="removerPipeline(confirmarIndice)">Sim, remover</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';
import PipelineModal from './PipelineModal.vue';

const campaigns = ref([]);
const pipelines = ref(JSON.parse(localStorage.getItem('pipelines') || '[]'));
const mostrarModal = ref(false);
const pipelineAtual = ref({});
const pipelineEditIndex = ref(-1);
const mostrarConfirmacao = ref(false);
const confirmarIndice = ref(-1);

const resultadosAlgoritmos = ref({
    rfm: {
        resumo: [],
        clientes: [],
        scatterClientes: [],
        scatterRegioes: []
    },
    churn: {
        clientes: []
    },
    recommendation: {
        produtos: []
    }
});

const segmentosUnicos = computed(() => {
    const segmentosRfm = new Set(resultadosAlgoritmos.value.rfm.clientes.map(c => c.Segmento))
    return [...segmentosRfm];
});

onMounted(async () => {
    const res = await axios.get('/campaigns');
    campaigns.value = res.data || [];
});

watch(pipelines, (val) => {
    localStorage.setItem('pipelines', JSON.stringify(val));
}, { deep: true });

const adicionarPipeline = () => {
    pipelines.value.push({
        campanhaId: '',
        algoritmo: '',
        formatos: ['pdf'],
        segmentos: [],
        limite: 10,
        query: ''
    });
};

const editarPipeline = async (index) => {
  const pipeline = pipelines.value[index]
  const campanhaId = pipeline.campanhaId
  if (!campanhaId) return

  try {
    const [resResumo, resClientes, resScatterClientes, resScatterRegioes] = await Promise.all([
      axios.get(`/algoritmos/resultados/${campanhaId}?algoritmo=rfm`),
      axios.get(`/algoritmos/resultados_complementares/${campanhaId}?algoritmo=rfm&tipo=clientes`),
      axios.get(`/algoritmos/resultados_complementares/${campanhaId}?algoritmo=rfm&tipo=scatter_clientes`),
      axios.get(`/algoritmos/resultados_complementares/${campanhaId}?algoritmo=rfm&tipo=scatter_regioes`),
    ])

    resultadosAlgoritmos.value.rfm = {
      resumo: resResumo.data.dados || [],
      clientes: resClientes.data || [],
      scatterClientes: resScatterClientes.data || [],
      scatterRegioes: resScatterRegioes.data || []
    }
  } catch (e) {
    console.warn('Erro ao buscar resultados RFM:', e)
  }

  pipelineAtual.value = JSON.parse(JSON.stringify(pipeline))
  pipelineEditIndex.value = index
  mostrarModal.value = true
};

const guardarEdicao = (dadosAtualizados) => {
    pipelines.value[pipelineEditIndex.value] = { ...dadosAtualizados };
    mostrarModal.value = false;
};

const fecharModal = () => {
    mostrarModal.value = false;
};

const exportarPipeline = async (index) => {
    const pipe = pipelines.value[index];
    console.log('Exportar:', pipe);
};

const confirmarRemocao = (index) => {
    confirmarIndice.value = index;
    mostrarConfirmacao.value = true;
};

const removerPipeline = (index) => {
    pipelines.value.splice(index, 1);
    mostrarConfirmacao.value = false;
};
</script>

<style scoped>
.p-6 {
    padding: 20px;
}

.card-pipeline {
    position: relative;
    width: 100%;
    padding: 1rem;
    padding-top: 2rem;
    background-color: #ffffff;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    margin-top: 1.5rem;
}

.btn-add {
    background-color: #22c55e;
    color: white;
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 8px;
}

.btn-edit {
    background-color: #fbbf24;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
}

.btn-download {
    background-color: #2563eb;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
}

.btn-remove {
    background-color: #ef4444;
    color: white;
    padding: 6px 12px;
    border-radius: 9999px;
    font-size: 1rem;
    line-height: 1;
    z-index: 10;
    cursor: pointer;
}

.btn-remove:hover {
    background-color: #dc2626;
}

.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 50;
}

.modal-content {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    width: 100%;
    max-width: 500px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.modal-content p {
    font-size: 1rem;
    color: #374151;
}

.btn-cancel {
    background-color: #e5e7eb;
    color: #1f2937;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 500;
}

.btn-cancel:hover {
    background-color: #d1d5db;
}

.btn-confirm {
    background-color: #ef4444;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 500;
}

.btn-confirm:hover {
    background-color: #dc2626;
}

.btn-save {
    background-color: #16a34a;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
}

.btn-remove-top {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
}
</style>
