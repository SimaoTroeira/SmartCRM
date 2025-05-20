<template>
    <div>
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

const coresPorSegmento = {
  "Campeões": "#8e44ad",
  "Clientes Valiosos": "#2980b9",
  "Clientes Regulares": "#27ae60",
  "Em Risco": "#f39c12",
  "Clientes Perdidos": "#c0392b",
  "Pouca Frequência": "#7f8c8d",
  "Baixo Valor": "#d35400",
  "Inativos": "#95a5a6"
}


// Detectar todos os nomes de segmentos (exceto campos padrão)
const camposPadrao = ['Regiao', 'ProdutoID', 'ProdutoMaisComprado', 'Categoria', 'Marca', 'QuantidadeTotal', 'ValorTotal']
const todosSegmentos = Array.from(new Set(
    props.scatterRegioes.flatMap(r =>
        Object.keys(r).filter(key => !camposPadrao.includes(key))
    )
))

const datasets = todosSegmentos.map(segmento => ({
  label: segmento,
  data: props.scatterRegioes.map(r => {
    const totalClientesSegmento = r[segmento] || 0
    const totalClientes = todosSegmentos.reduce((acc, seg) => acc + (r[seg] || 0), 0)
    const percentagem = totalClientes ? (totalClientesSegmento / totalClientes) : 0
    return percentagem * r.ValorTotal
  }),
  backgroundColor: coresPorSegmento[segmento] || '#ccc',
  stack: 'stack1'
}))


const chartData = {
    labels: props.scatterRegioes.map(r => r.Regiao),
    datasets
}

const chartOptions = {
    responsive: true,
    plugins: {
        legend: {
            position: 'top'
        },
        tooltip: {
            callbacks: {
                label: function (ctx) {
                    const regiao = props.scatterRegioes[ctx.dataIndex]
                    const segmento = ctx.dataset.label
                    const valorSegmento = ctx.parsed.y
                    const totalClientesSegmento = regiao[segmento] || 0
                    const totalClientes = todosSegmentos.reduce((acc, seg) => acc + (regiao[seg] || 0), 0)
                    const percentagem = totalClientes ? ((totalClientesSegmento / totalClientes) * 100).toFixed(1) : 0

                    return [
                        `Segmento: ${segmento}`,
                        `Clientes: ${totalClientesSegmento}`,
                        `Percentagem: ${percentagem}%`,
                        `Valor Estimado: €${valorSegmento.toFixed(2)}`,
                        `Produto: ${regiao.ProdutoMaisComprado}`,
                        `Categoria: ${regiao.Categoria}`,
                        `Marca: ${regiao.Marca}`
                    ]
                }
            }
        }
    },
    scales: {
        x: {
            stacked: true,
            title: {
                display: true,
                text: 'Região'
            }
        },
        y: {
            stacked: true,
            title: {
                display: true,
                text: 'Valor Total (€)'
            },
            beginAtZero: true
        }
    }
}

</script>

<style scoped>
</style>
