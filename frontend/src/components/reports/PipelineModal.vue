<template>
    <div v-if="visible" class="modal-backdrop">
        <div class="modal-content">
            <h2 class="text-lg font-semibold mb-6">Personalizar Exportação</h2>

            <!-- SECÇÃO RFM -->
            <div class="section-block">
                <label class="section-title">
                    <input type="checkbox" v-model="local.pipeline.algoritmos.rfm.incluir" />
                    📊 Segmentação RFM
                </label>

                <div v-if="local.pipeline.algoritmos.rfm.incluir" class="pl-4 space-y-3 mt-3">
                    <label class="block text-sm font-medium">Formatos:</label>
                    <div class="flex gap-4">
                        <label><input type="checkbox" value="pdf" v-model="local.pipeline.algoritmos.rfm.formatos" />
                            PDF</label>
                        <label><input type="checkbox" value="excel" v-model="local.pipeline.algoritmos.rfm.formatos" />
                            Excel</label>
                    </div>

                    <div class="text-sm mt-4 font-semibold">
                        Pré-visualização:
                    </div>
                    <RfmPreviewTable ref="previewRef" :resultadosAlgoritmos="resultadosAlgoritmos" />
                </div>
            </div>

            <!-- SECÇÃO CHURN -->
            <div class="section-block">
                <label class="section-title">
                    <input type="checkbox" v-model="local.pipeline.algoritmos.churn.incluir" />
                    🔁 Previsão de Churn
                </label>

                <div v-if="local.pipeline.algoritmos.churn.incluir" class="pl-4 space-y-3 mt-3">
                    <label class="block text-sm font-medium">Formatos:</label>
                    <div class="flex gap-4">
                        <label><input type="checkbox" value="pdf" v-model="local.pipeline.algoritmos.churn.formatos" />
                            PDF</label>
                        <label><input type="checkbox" value="excel"
                                v-model="local.pipeline.algoritmos.churn.formatos" /> Excel</label>
                    </div>

                    <label class="block text-sm font-medium">Limite de resultados:</label>
                    <input type="number" class="form-control w-full"
                        v-model.number="local.pipeline.algoritmos.churn.limite" min="1" />
                </div>
            </div>

            <!-- SECÇÃO RECOMENDAÇÃO -->
            <div class="section-block">
                <label class="section-title">
                    <input type="checkbox" v-model="local.pipeline.algoritmos.recommendation.incluir" />
                    🧠 Recomendação
                </label>

                <div v-if="local.pipeline.algoritmos.recommendation.incluir" class="pl-4 space-y-3 mt-3">
                    <label class="block text-sm font-medium">Formatos:</label>
                    <div class="flex gap-4">
                        <label><input type="checkbox" value="pdf"
                                v-model="local.pipeline.algoritmos.recommendation.formatos" /> PDF</label>
                        <label><input type="checkbox" value="excel"
                                v-model="local.pipeline.algoritmos.recommendation.formatos" /> Excel</label>
                    </div>

                    <label class="block text-sm font-medium">Limite de resultados:</label>
                    <input type="number" class="form-control w-full"
                        v-model.number="local.pipeline.algoritmos.recommendation.limite" min="1" />
                </div>
            </div>

            <!-- SEPARADOR FINAL -->
            <div class="border-t border-gray-200 mt-6 mb-4"></div>

            <!-- AÇÕES -->
            <div class="flex justify-end gap-4">
                <button class="btn-cancel" @click="$emit('close')">Cancelar</button>
                <button class="btn-save" @click="guardar">Guardar</button>
            </div>
            <button class="btn-save" @click="transferirExportacoes">
                Transferir
            </button>
        </div>

    </div>
</template>

<script setup>
import { reactive, watch, ref } from 'vue';
import RfmPreviewTable from './RfmPreviewTable.vue';

const emit = defineEmits(['close', 'save']);

const previewRef = ref(null);

const rfmClientes = previewRef.value?.clientesRFMFiltrados ?? [];

const props = defineProps({
    visible: Boolean,
    pipeline: Object,
    resultadosAlgoritmos: Object
});

const local = reactive({
    pipeline: {
        campanhaId: '',
        algoritmos: {
            rfm: {
                incluir: true,
                formatos: ['pdf'],
                segmentos: [],
                limite: 10,
                query: ''
            },
            churn: {
                incluir: false,
                formatos: [],
                limite: 10
            },
            recommendation: {
                incluir: false,
                formatos: [],
                limite: 10
            }
        }
    }
});

watch(() => props.pipeline, (val) => {
    if (val) {
        local.pipeline = {
            campanhaId: val.campanhaId || '',
            algoritmos: {
                rfm: {
                    incluir: val.algoritmos?.rfm?.incluir ?? true,
                    formatos: val.algoritmos?.rfm?.formatos ?? ['pdf'],
                    segmentos: val.algoritmos?.rfm?.segmentos ?? [],
                    limite: val.algoritmos?.rfm?.limite ?? 10,
                    query: val.algoritmos?.rfm?.query ?? ''
                },
                churn: {
                    incluir: val.algoritmos?.churn?.incluir ?? false,
                    formatos: val.algoritmos?.churn?.formatos ?? [],
                    limite: val.algoritmos?.churn?.limite ?? 10
                },
                recommendation: {
                    incluir: val.algoritmos?.recommendation?.incluir ?? false,
                    formatos: val.algoritmos?.recommendation?.formatos ?? [],
                    limite: val.algoritmos?.recommendation?.limite ?? 10
                }
            }
        };
    }
}, { immediate: true, deep: true });


function transferirExportacoes() {
    const campanhaId = local.pipeline.campanhaId;

    if (!campanhaId) {
        alert('Campanha não definida.');
        return;
    }

    // Exportação de RFM
    if (local.pipeline.algoritmos.rfm.incluir && local.pipeline.algoritmos.rfm.formatos.includes('pdf')) {
        const rfmData = {
            segmentos: local.pipeline.algoritmos.rfm.segmentos,
            limite: local.pipeline.algoritmos.rfm.limite,
            ordenacao: previewRef.value?.ordenacao ?? {},
            filtros: normalizarFiltros(previewRef.value?.filtrosRfm ?? {})
        };


        iniciarDownload(`/exportar/rfm/${campanhaId}`, {
            clientes: rfmClientes
        });
    }

    // Exportação de Churn
    if (local.pipeline.algoritmos.churn.incluir && local.pipeline.algoritmos.churn.formatos.includes('pdf')) {
        iniciarDownload(`/exportar/churn/${campanhaId}`);
    }

    // Exportação de Recomendação
    if (local.pipeline.algoritmos.recommendation.incluir && local.pipeline.algoritmos.recommendation.formatos.includes('pdf')) {
        iniciarDownload(`/exportar/recommendation/${campanhaId}`);
    }
}

function normalizarFiltros(filtros) {
    const mapeamento = {
        comprasMin: 'frequenciaMin',
        comprasMax: 'frequenciaMax',
        recenciaMin: 'recenciaMin',
        recenciaMax: 'recenciaMax',
        segmento: 'segmento'
    };

    const resultado = {};
    for (const key in filtros) {
        const novoNome = mapeamento[key] || key;
        resultado[novoNome] = filtros[key];
    }
    return resultado;
}



function iniciarDownload(baseUrl, params = {}, filename = 'exportacao.pdf') {
    const url = new URL(`http://localhost:8000${baseUrl}`);

    // Serializar o array de clientes como JSON base64
    if (params.clientes && Array.isArray(params.clientes)) {
        const json = JSON.stringify(params.clientes);
        const base64 = btoa(unescape(encodeURIComponent(json)));
        url.searchParams.append('clientesBase64', base64);
        delete params.clientes;
    }

    // Outros parâmetros normais
    Object.entries(params).forEach(([key, value]) => {
        if (typeof value === 'object' && value !== null) {
            Object.entries(value).forEach(([subKey, subValue]) => {
                if (subValue !== null && subValue !== '') {
                    url.searchParams.append(`${key}[${subKey}]`, subValue);
                }
            });
        } else if (value !== null && value !== '') {
            url.searchParams.append(key, value);
        }
    });

    const token = localStorage.getItem('token');

    fetch(url.toString(), {
        method: 'GET',
        headers: {
            'Authorization': `Bearer ${token}`
        }
    })
        .then(response => {
            if (!response.ok) throw new Error("Erro na exportação");

            const disposition = response.headers.get('Content-Disposition');
            let filename = 'exportacao.pdf';

            if (disposition && disposition.includes('filename=')) {
                filename = disposition
                    .split('filename=')[1]
                    .split(';')[0]
                    .replace(/["']/g, '');
            }

            return response.blob().then(blob => ({ blob, filename }));
        })
        .then(({ blob, filename }) => {
            const downloadUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
}


</script>

<style scoped>
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
    max-width: 900px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.btn-cancel {
    background-color: #e5e7eb;
    color: #111827;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 500;
}

.btn-cancel:hover {
    background-color: #d1d5db;
}

.btn-save {
    background-color: #16a34a;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 500;
}

.section-block {
    border-top: 1px solid #e5e7eb;
    padding-top: 1.5rem;
    margin-top: 1.5rem;
}

.section-title {
    font-weight: 600;
    font-size: 1.1rem;
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.form-control {
    width: 100% !important;
}
</style>
