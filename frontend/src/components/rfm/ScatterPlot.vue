<template>
    <div>
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

const coresFixas = {
    "Campeões": "#8e44ad",
    "Clientes Valiosos": "#2980b9",
    "Clientes Regulares": "#27ae60",
    "Em Risco": "#f39c12",
    "Clientes Perdidos": "#c0392b",
    "Pouca Frequência": "#7f8c8d",
    "Baixo Valor": "#d35400",
    "Inativos": "#95a5a6"
}



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

    return Object.entries(grupos).map(([label, data]) => ({
        label,
        data,
        backgroundColor: coresFixas[label] || '#ccc'
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
            min: -0.1,
            max: 1.1,
            ticks: {
                stepSize: 0.1
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
            min: -0.1,
            max: 1.1,
            ticks: {
                stepSize: 0.1
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
