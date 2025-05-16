<template>
    <div>
        <h4 class="text-xl font-semibold mb-4 text-blue-700">Produtos Mais Comprados por Região</h4>
        <div class="text-sm text-gray-600 bg-blue-50 border border-blue-200 rounded p-3 mt-2">
            Este gráfico mostra as regiões com os produtos mais comprados. Cada barra representa a região e o seu
            produto mais popular, com base no volume e valor de vendas.
        </div>

        <Bar :data="chartData" :options="chartOptions" />
    </div>
</template>

<script setup>
import { Bar } from 'vue-chartjs'
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const props = defineProps({
    scatterRegioes: Array
})

const cores = [
    '#8884d8', '#82ca9d', '#ffc658', '#ff7300', '#d0ed57',
    '#a4de6c', '#8dd1e1', '#83a6ed', '#ffb6b9', '#d1c4e9'
]

const chartData = {
    labels: props.scatterRegioes.map(r => r.Regiao),
    datasets: [
        {
            label: 'Valor Total (€)',
            data: props.scatterRegioes.map(r => r.ValorTotal),
            backgroundColor: cores[0]
        }
    ]
}

const chartOptions = {
    responsive: true,
    plugins: {
        legend: {
            position: 'top'
        },
        tooltip: {
            callbacks: {
                label: ctx => {
                    const data = props.scatterRegioes[ctx.dataIndex]
                    return [
                        `Produto: ${data.ProdutoMaisComprado}`,
                        `Categoria: ${data.Categoria}`,
                        `Marca: ${data.Marca}`,
                        `Valor Total: €${data.ValorTotal.toFixed(2)}`
                    ]
                }
            }
        }
    },
    scales: {
        x: {
            title: {
                display: true,
                text: 'Região'
            }
        },
        y: {
            title: {
                display: true,
                text: 'Valor Total (€)'
            },
            beginAtZero: true
        }
    }
}
</script>
