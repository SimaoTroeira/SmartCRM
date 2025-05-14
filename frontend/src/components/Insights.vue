<template>
    <div class="bg-white p-4 rounded-md shadow-sm border border-gray-200 mb-6 max-w-3xl">
        <h3 class="text-lg font-semibold mb-3 text-blue-700">Insights</h3>

        <div v-if="modo === 'resumo' && dados.length">
            <ul class="list-disc ml-5 text-sm text-gray-700">
                <li>
                    O cluster com maior valor total de compras é: <strong>{{ clusterMaiorGasto?.Segmento || 'N/A'
                        }}</strong>.
                </li>
                <li>
                    O cluster com menor recência média (mais recente) é: <strong>{{ clusterMaisRecente?.Segmento ||
                        'N/A' }}</strong>.
                </li>
                <li>
                    O cluster com mais compras no total é: <strong>{{ clusterMaisCompras?.Segmento || 'N/A' }}</strong>.
                </li>
            </ul>
        </div>

        <div v-else-if="modo === 'clientes' && dados.length">
            <ul class="list-disc ml-5 text-sm text-gray-700">
                <li>
                    Top 1 cliente em valor gasto: <strong>{{ topCliente?.Nome || topCliente?.ClienteID || 'N/A'
                        }}</strong> com {{ topCliente?.Monetário?.toFixed(2) }}€.
                </li>
                <li>
                    Cliente mais frequente: <strong>{{ clienteMaisFrequente?.Nome || clienteMaisFrequente?.ClienteID ||
                        'N/A' }}</strong> com {{ clienteMaisFrequente?.Frequência }} compras.
                </li>
                <li>
                    Cliente mais inativo: <strong>{{ clienteMaisInativo?.Nome || clienteMaisInativo?.ClienteID || 'N/A'
                        }}</strong>, última compra há {{ clienteMaisInativo?.Recência }} dias.
                </li>
            </ul>
        </div>

        <div v-else class="text-sm text-gray-500">
            Nenhum insight disponível para esta visualização.
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    modo: String, // 'resumo', 'clientes', etc.
    dados: Array // os dados do modo atual
})

const clusterMaiorGasto = computed(() => {
    return [...props.dados].sort((a, b) => (b['Total Monetário'] || 0) - (a['Total Monetário'] || 0))[0]
})

const clusterMaisRecente = computed(() => {
    return [...props.dados].filter(c => 'Recência Média' in c).sort((a, b) => (a['Recência Média'] || Infinity) - (b['Recência Média'] || Infinity))[0]
})

const clusterMaisCompras = computed(() => {
    return [...props.dados].sort((a, b) => (b['Quantidade de Compras'] || 0) - (a['Quantidade de Compras'] || 0))[0]
})

const topCliente = computed(() => {
    return [...props.dados].sort((a, b) => (b['Monetário'] || 0) - (a['Monetário'] || 0))[0]
})

const clienteMaisFrequente = computed(() => {
    return [...props.dados].sort((a, b) => (b['Frequência'] || 0) - (a['Frequência'] || 0))[0]
})

const clienteMaisInativo = computed(() => {
    return [...props.dados].filter(c => 'Recência' in c).sort((a, b) => (b['Recência'] || 0) - (a['Recência'] || 0))[0]
})
</script>
