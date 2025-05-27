<template>
    <div class="overflow-auto">
        <table class="min-w-full text-sm border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-3 py-2 border">Antecedentes</th>
                    <th class="px-3 py-2 border">Consequentes</th>
                    <th class="px-3 py-2 border">
                        Suporte (%)
                        <span title="Frequência da regra entre todas as transações"
                            class="ml-1 cursor-help text-gray-400">❔</span>
                    </th>
                    <th class="px-3 py-2 border">
                        Confiança (%)
                        <span title="Probabilidade de o consequente ocorrer dado o antecedente"
                            class="ml-1 cursor-help text-gray-400">❔</span>
                    </th>
                    <th class="px-3 py-2 border">
                        Lift
                        <span title="Força da associação em relação à ocorrência independente"
                            class="ml-1 cursor-help text-gray-400">❔</span>
                    </th>

                </tr>
            </thead>
            <tbody>
                <tr v-for="(r, i) in regras" :key="i" class="hover:bg-gray-50">
                    <td class="px-3 py-1 border">{{ r.antecedents.join(', ') }}</td>
                    <td class="px-3 py-1 border">{{ r.consequents.join(', ') }}</td>
                    <td class="px-3 py-1 border">{{ formatPercent(r.support) }}</td>
                    <td class="px-3 py-1 border">{{ formatPercent(r.confidence) }}</td>
                    <td class="px-3 py-1 border">{{ r.lift.toFixed(2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
const props = defineProps({
    regras: Array,
    formatPercent: Function,
    colunaOrdenada: String,
    ordemCrescente: Boolean,
})

const emit = defineEmits(['update:colunaOrdenada', 'update:ordemCrescente'])

function ordenarPor(coluna) {
    if (props.colunaOrdenada === coluna) {
        emit('update:ordemCrescente', !props.ordemCrescente)
    } else {
        emit('update:colunaOrdenada', coluna)
        emit('update:ordemCrescente', false)
    }
}
</script>
