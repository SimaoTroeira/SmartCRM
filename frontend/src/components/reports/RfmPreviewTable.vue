<template>
    <div class="overflow-x-auto relative border rounded bg-gray-50 text-xs">
        <div class="flex items-center justify-end mb-2 px-2">
            <label class="mr-2">Linhas:</label>
            <select v-model.number="limiteLinhas" class="form-control w-20">
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
            </select>
        </div>
        <table class="min-w-full table-auto">
            <thead class="bg-gray-100 text-left">
                <tr>
                    <th class="px-3 py-1 relative">Nome</th>

                    <th class="px-3 py-1 relative">
                        <div class="relative inline-block">
                            Segmento
                            <button @click="toggleDropdown('segmento')" :data-dropdown-button="'segmento'"
                                class="text-xs ml-1">▼</button>
                            <div v-if="dropdowns.segmento" :data-dropdown-panel="'segmento'" class="dropdown-panel">
                                <select v-model="filtrosRfm.segmento" class="form-control">
                                    <option value="">Todos</option>
                                    <option v-for="s in segmentosRfmDisponiveis" :key="s" :value="s">{{ s }}</option>
                                </select>
                            </div>
                        </div>
                    </th>

                    <th class="px-3 py-1 relative">
                        <div class="relative inline-block">
                            Recência
                            <button @click="toggleDropdown('recencia')" :data-dropdown-button="'recencia'"
                                class="text-xs ml-1">▼</button>
                            <div v-if="dropdowns.recencia" :data-dropdown-panel="'recencia'" class="dropdown-panel">
                                <label>Mín:</label>
                                <input type="number" v-model.number="filtrosRfm.recenciaMin" class="form-control mb-1">
                                <label>Máx:</label>
                                <input type="number" v-model.number="filtrosRfm.recenciaMax" class="form-control mb-1">
                                <label>Ordenar:</label>
                                <select v-model="ordenacao.recencia" class="form-control">
                                    <option value="">Nenhuma</option>
                                    <option value="asc">Crescente</option>
                                    <option value="desc">Decrescente</option>
                                </select>
                            </div>
                        </div>
                    </th>

                    <th class="px-3 py-1 relative">
                        <div class="relative inline-block">
                            Frequência
                            <button @click="toggleDropdown('frequencia')" :data-dropdown-button="'frequencia'"
                                class="text-xs ml-1">▼</button>
                            <div v-if="dropdowns.frequencia" :data-dropdown-panel="'frequencia'" class="dropdown-panel">
                                <label>Mín:</label>
                                <input type="number" v-model.number="filtrosRfm.comprasMin" class="form-control mb-1">
                                <label>Máx:</label>
                                <input type="number" v-model.number="filtrosRfm.comprasMax" class="form-control mb-1">
                                <label>Ordenar:</label>
                                <select v-model="ordenacao.frequencia" class="form-control">
                                    <option value="">Nenhuma</option>
                                    <option value="asc">Crescente</option>
                                    <option value="desc">Decrescente</option>
                                </select>
                            </div>
                        </div>
                    </th>

                    <th class="px-3 py-1 relative">
                        <div class="relative inline-block">
                            Monetário
                            <button @click="toggleDropdown('monetario')" :data-dropdown-button="'monetario'"
                                class="text-xs ml-1">▼</button>
                            <div v-if="dropdowns.monetario" :data-dropdown-panel="'monetario'" class="dropdown-panel">
                                <label>Ordenar:</label>
                                <select v-model="ordenacao.monetario" class="form-control">
                                    <option value="">Nenhuma</option>
                                    <option value="asc">Crescente</option>
                                    <option value="desc">Decrescente</option>
                                </select>
                            </div>
                        </div>
                    </th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="(c, idx) in clientesRFMFiltrados" :key="idx">
                    <td class="px-3 py-1">{{ c.Nome || 'Cliente' }}</td>
                    <td class="px-3 py-1">{{ c.Segmento || '-' }}</td>
                    <td class="px-3 py-1">{{ c.Recência ?? '-' }}</td>
                    <td class="px-3 py-1">{{ c.Frequência ?? '-' }}</td>
                    <td class="px-3 py-1">{{ c.Monetário?.toFixed(2) ?? '-' }}</td>
                </tr>
                <tr v-if="clientesRFMFiltrados.length === 0">
                    <td colspan="5" class="px-3 py-2 text-center text-red-600">Nenhum resultado encontrado</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { reactive, computed, ref, nextTick, onMounted, onBeforeUnmount } from 'vue';



const limiteLinhas = ref(5);


const props = defineProps({
    resultadosAlgoritmos: Object,
    segmentosDisponiveis: Array
});

const filtrosRfm = reactive({
    segmento: '', recenciaMin: null, recenciaMax: null,
    comprasMin: null, comprasMax: null
});

const ordenacao = reactive({
    recencia: '', frequencia: '', monetario: ''
});

const dropdowns = reactive({
    segmento: false, recencia: false, frequencia: false, monetario: false
});

function toggleDropdown(coluna) {
    for (const key in dropdowns) dropdowns[key] = false;
    dropdowns[coluna] = true;

    nextTick(() => {
        const btn = document.querySelector(`[data-dropdown-button="${coluna}"]`);
        const panel = document.querySelector(`[data-dropdown-panel="${coluna}"]`);
        if (btn && panel) {
            const rect = btn.getBoundingClientRect();
            panel.style.top = `${rect.bottom}px`;
            panel.style.left = `${rect.left}px`;
        }
    });
}

const segmentosRfmDisponiveis = computed(() => {
    const clientes = props.resultadosAlgoritmos?.rfm?.clientes || [];
    return [...new Set(clientes.map(c => c.Segmento))];
});

const clientesRFMFiltrados = computed(() => {
    const dados = props.resultadosAlgoritmos?.rfm?.clientes || [];

    let filtrados = dados.filter(item => {
        const matchSegmento = !filtrosRfm.segmento || item.Segmento === filtrosRfm.segmento;
        const matchRecenciaMin = filtrosRfm.recenciaMin == null || item.Recência >= filtrosRfm.recenciaMin;
        const matchRecenciaMax = filtrosRfm.recenciaMax == null || item.Recência <= filtrosRfm.recenciaMax;
        const matchComprasMin = filtrosRfm.comprasMin == null || item.Frequência >= filtrosRfm.comprasMin;
        const matchComprasMax = filtrosRfm.comprasMax == null || item.Frequência <= filtrosRfm.comprasMax;
        return matchSegmento && matchRecenciaMin && matchRecenciaMax && matchComprasMin && matchComprasMax;
    });

    if (ordenacao.recencia) filtrados.sort((a, b) => ordenacao.recencia === 'asc' ? a.Recência - b.Recência : b.Recência - a.Recência);
    if (ordenacao.frequencia) filtrados.sort((a, b) => ordenacao.frequencia === 'asc' ? a.Frequência - b.Frequência : b.Frequência - a.Frequência);
    if (ordenacao.monetario) filtrados.sort((a, b) => ordenacao.monetario === 'asc' ? a.Monetário - b.Monetário : b.Monetário - a.Monetário);

    return filtrados.slice(0, limiteLinhas.value);
});

defineExpose({
    clientesRFMFiltrados,
    limiteLinhas,
    ordenacao,
    filtrosRfm
});


function fecharDropdownsAoClicarFora(e) {
    const painéis = document.querySelectorAll('.dropdown-panel');
    const botões = document.querySelectorAll('[data-dropdown-button]');

    const clicouDentroDePainel = Array.from(painéis).some(panel => panel.contains(e.target));
    const clicouNumBotao = Array.from(botões).some(btn => btn.contains(e.target));

    if (!clicouDentroDePainel && !clicouNumBotao) {
        for (const key in dropdowns) dropdowns[key] = false;
    }
}

onMounted(() => {
    window.addEventListener('mousedown', fecharDropdownsAoClicarFora);
});

onBeforeUnmount(() => {
    window.removeEventListener('mousedown', fecharDropdownsAoClicarFora);
});
</script>

<style scoped>
.dropdown-panel {
    position: fixed;
    z-index: 999;
    background: white;
    border: 1px solid #ccc;
    padding: 0.5rem;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    min-width: 180px;
    font-size: 0.75rem;
}

.form-control {
    width: 100%;
    padding: 0.25rem;
    font-size: 0.75rem;
    border: 1px solid #ccc;
    border-radius: 4px;
}
</style>
