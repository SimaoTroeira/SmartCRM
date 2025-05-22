<template>
    <div>
        <!-- Checkboxes por cluster -->
        <div class="checkboxes">
            <label v-for="(visivel, nome) in gruposVisiveis" :key="nome"
                :style="{ backgroundColor: coresFixas[nome] || '#ccc' }">
                <input type="checkbox" v-model="gruposVisiveis[nome]" />
                {{ nome }}
            </label>

        </div>

        <!-- Gráfico -->
        <Scatter ref="chartComponent" v-if="modo === 'clientes'" :data="chartData" :options="chartOptions" />

        <!-- Botão de reset -->
        <button @click="resetZoom">Repor Zoom</button>
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
import zoomPlugin from 'chartjs-plugin-zoom'

ChartJS.register(Title, Tooltip, Legend, PointElement, LinearScale, zoomPlugin)

const props = defineProps({
    scatterClientes: Array,
    scatterRegioes: Array
})

const modo = ref('clientes')
const chartComponent = ref(null)
const gruposVisiveis = ref({})

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
        grupos[grupo].push({
            x: ponto.x,
            y: ponto.y,
            r: Math.max(8, ponto.r * 60),
            ...ponto
        })

        // Inicializa os checkboxes se ainda não tiverem valor
        if (!(grupo in gruposVisiveis.value)) {
            gruposVisiveis.value[grupo] = true
        }
    })

    return Object.entries(grupos)
        .filter(([label]) => gruposVisiveis.value[label])
        .map(([label, data]) => {
            const maxR = Math.max(...data.map(p => p.r))
            const minR = Math.min(...data.map(p => p.r))
            const escala = 14
            const minimoVisual = 4

            return {
                label,
                data,
                backgroundColor: coresFixas[label] || '#ccc',
                pointStyle: 'circle',
                parsing: false,
                showLine: false,
                clip: false, // <- Isto é o importante
                pointRadius: ctx => {
                    const p = ctx.raw
                    const normalizado = (p.r - minR) / (maxR - minR || 1)
                    return Math.max(minimoVisual, normalizado * escala)
                },
                pointHoverRadius: ctx => {
                    const p = ctx.raw
                    const normalizado = (p.r - minR) / (maxR - minR || 1)
                    return Math.max(minimoVisual + 1, normalizado * escala + 2)
                }
            }

        })
})

const chartData = computed(() => ({
    datasets: datasets.value
}))

const chartOptions = computed(() => ({
    responsive: true,
    plugins: {
        legend: {
            display: false // ⛔ desativa legenda original
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
                    if (p.Recência !== undefined) linhas.push(`Recência: ${p.Recência} dias`)
                    if (p.Frequência !== undefined) linhas.push(`Frequência: ${p.Frequência}`)
                    if (p.Monetário !== undefined) linhas.push(`Monetário: €${p.Monetário}`)
                    return linhas
                }
            }
        },
        zoom: {
            pan: {
                enabled: true,
                mode: 'xy',
            },
            zoom: {
                wheel: { enabled: true },
                pinch: { enabled: true },
                mode: 'xy'
            }
        }

    },
    scales: {
        x: {
            min: -0.1,
            max: 1.1,
            title: {
                display: true,
                text: 'Valor Monetário (normalizado)'
            },
            ticks: { stepSize: 0.1 },
            grid: { color: '#e0e0e0' }
        },
        y: {
            min: -0.1,
            max: 1.1,
            title: {
                display: true,
                text: 'Recência'
            },
            ticks: { stepSize: 0.1 },
            grid: { color: '#e0e0e0' }
        }
    }
}))

function resetZoom() {
    if (chartComponent.value?.chart?.resetZoom) {
        chartComponent.value.chart.resetZoom()
    }
}
</script>

<style scoped>
.checkboxes {
    margin: 10px auto 20px;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    /* centraliza no container */
    gap: 10px;
}

.checkboxes label {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 4px 8px;
    border-radius: 6px;
    font-weight: bold;
    color: white;
    font-size: 0.9rem;
}

select {
    border: 1px solid #ccc;
    padding: 4px;
    border-radius: 4px;
}
</style>
