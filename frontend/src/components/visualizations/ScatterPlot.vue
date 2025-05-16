<template>
    <div>
        <h4 class="text-xl font-semibold mb-4 text-blue-700">Dispersão dos Clientes</h4>
        <div class="text-sm text-gray-600 bg-blue-50 border border-blue-200 rounded p-3 mt-2">
            Este gráfico mostra agrupamentos de clientes com base nos seus padrões de compra.
            A posição horizontal e vertical representa combinações de recência, frequência e valor monetário (RFM).
            Clientes próximos entre si tendem a ter comportamentos semelhantes. A direção vertical tende a refletir o
            tempo desde a última compra. A horizontal, o valor e frequência das compras.
        </div>
        <Scatter v-if="modo === 'clientes'" :data="chartData" :options="chartOptions" />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Scatter } from 'vue-chartjs'
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    PointElement,
    LinearScale
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, PointElement, LinearScale)

const props = defineProps({
    scatterClientes: Array,
    scatterRegioes: Array
})

const modo = ref('clientes')

const cores = [
    '#8884d8', '#82ca9d', '#ffc658', '#ff7300',
    '#d0ed57', '#a4de6c', '#8dd1e1', '#83a6ed'
]

const datasets = computed(() => {
    const grupos = {}
    const dadosOrigem = modo.value === 'clientes' ? props.scatterClientes : props.scatterRegioes

    dadosOrigem.forEach((ponto, i) => {
        const grupo = modo.value === 'clientes'
            ? (ponto.Segmento || `Cluster ${ponto.Cluster}`)
            : (ponto.ProdutoMaisComprado || `Grupo ${i}`)

        if (!grupos[grupo]) grupos[grupo] = []
        grupos[grupo].push({ x: ponto.x, y: ponto.y, ...ponto })
    })

    return Object.entries(grupos).map(([label, data], i) => ({
        label,
        data,
        backgroundColor: cores[i % cores.length]
    }))
})

const chartData = computed(() => ({
    datasets: datasets.value
}))

const chartOptions = computed(() => ({
    responsive: true,
    plugins: {
        legend: {
            position: 'top'
        },
        tooltip: {
            callbacks: {
                title: ctx => {
                    const p = ctx[0].raw
                    return p.Nome || p.Regiao || `Cluster ${p.Cluster}`
                },
                label: ctx => {
                    const p = ctx.raw
                    const linhas = []
                    if (p.Segmento) linhas.push(`Segmento: ${p.Segmento}`)
                    if (p.ProdutoMaisComprado) linhas.push(`Produto: ${p.ProdutoMaisComprado}`)
                    if (p.Categoria) linhas.push(`Categoria: ${p.Categoria}`)
                    if (p.Marca) linhas.push(`Marca: ${p.Marca}`)

                    // Ocultamos x/y e mostramos só RFM
                    if (p.Recência !== undefined) linhas.push(`Recência: ${p.Recência} dias`)
                    if (p.Frequência !== undefined) linhas.push(`Frequência: ${p.Frequência}`)
                    if (p.Monetário !== undefined) linhas.push(`Monetário: €${p.Monetário}`)

                    return linhas
                }
            }
        }
    },
    scales: {
        x: {
            type: 'linear',
            position: 'bottom',
            title: {
                display: true,
                text: 'Valor Monetário/Frequência'
            },
            grid: {
                color: '#e0e0e0'
            }
        },
        y: {
            title: {
                display: true,
                text: 'Recência'
            },
            grid: {
                color: '#e0e0e0'
            }
        }
    }

}))
</script>

<style scoped>
select {
    border: 1px solid #ccc;
    padding: 4px;
    border-radius: 4px;
}
</style>
