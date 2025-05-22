<template>
    <div class="radar-container">
        <div class="checkboxes">
            <label v-for="(visivel, nome) in clustersVisiveis" :key="nome"
                :style="{ backgroundColor: coresFixas[nome] || '#ccc' }">
                <input type="checkbox" v-model="clustersVisiveis[nome]" />
                {{ nome }}
            </label>

        </div>

        <Radar :data="radarData" :options="radarOptions" />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Radar } from 'vue-chartjs'
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    RadialLinearScale,
    PointElement,
    LineElement,
    Filler
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, RadialLinearScale, PointElement, LineElement, Filler)

const props = defineProps({
    scatterClientes: Array
})

const coresFixas = {
    "Campeões": '#8e44ad',
    "Clientes Valiosos": '#2980b9',
    "Clientes Regulares": '#27ae60',
    "Em Risco": '#f39c12',
    "Clientes Perdidos": '#c0392b',
    "Pouca Frequência": '#7f8c8d',
    "Baixo Valor": '#d35400',
    "Inativos": '#95a5a6'
}

const clustersVisiveis = ref({})

// Agrupamento por segmento
const segmentosRaw = computed(() => {
    const grupos = {}
    props.scatterClientes.forEach(cliente => {
        const grupo = cliente.Segmento || `Cluster ${cliente.Cluster}`
        if (!grupos[grupo]) grupos[grupo] = []
        grupos[grupo].push(cliente)

        if (!(grupo in clustersVisiveis.value)) {
            clustersVisiveis.value[grupo] = true
        }
    })

    return Object.entries(grupos).map(([label, clientes]) => {
        return {
            label,
            recencia: clientes.reduce((sum, c) => sum + (c['Recência'] || 0), 0) / clientes.length,
            frequencia: clientes.reduce((sum, c) => sum + (c['Frequência'] || 0), 0) / clientes.length,
            monetario: clientes.reduce((sum, c) => sum + (c['Monetário'] || 0), 0) / clientes.length
        }
    })
})

const segmentos = computed(() => {

    const recs = segmentosRaw.value.map(s => s.recencia)
    const freqs = segmentosRaw.value.map(s => s.frequencia)
    const mons = segmentosRaw.value.map(s => s.monetario)

    const [minR, maxR] = [Math.min(...recs), Math.max(...recs)]
    const [minF, maxF] = [Math.min(...freqs), Math.max(...freqs)]
    const [minM, maxM] = [Math.min(...mons), Math.max(...mons)]

    const norm = (v, min, max) => max - min === 0 ? 0.5 : (v - min) / (max - min)

    return segmentosRaw.value.map(s => ({
        label: s.label,
        recencia: norm(s.recencia, minR, maxR),
        frequencia: norm(s.frequencia, minF, maxF),
        monetario: norm(s.monetario, minM, maxM)
    }))
})

const radarData = computed(() => ({
    labels: ['Recência', 'Frequência', 'Monetário'],
    datasets: segmentos.value
        .filter(seg => clustersVisiveis.value[seg.label])
        .map(seg => ({
            label: seg.label,
            fill: true,
            borderWidth: 2,
            borderColor: coresFixas[seg.label] || '#ccc',
            backgroundColor: (coresFixas[seg.label] || '#ccc') + '33',
            pointBackgroundColor: coresFixas[seg.label] || '#ccc',
            data: [seg.recencia, seg.frequencia, seg.monetario]
        }))
}))

const radarOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            callbacks: {
                label: ctx => `${ctx.dataset.label}: ${(ctx.raw * 100).toFixed(0)}%`
            }
        }
    },
    scales: {
        r: {
            suggestedMin: 0,
            suggestedMax: 1,
            ticks: {
                stepSize: 0.2,
                backdropColor: 'transparent',
                callback: val => `${Math.round(val * 100)}%`
            },
            pointLabels: {
                font: { size: 12 }
            },
            angleLines: { color: '#ccc' },
            grid: { color: '#e0e0e0' }
        }
    }
}
</script>

<style scoped>
.radar-container {
    max-width: 700px;
    height: 480px;
    margin: 0 auto;
}

.checkboxes {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
    margin-bottom: 1rem;
}

.checkboxes label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: bold;
    font-size: 0.9rem;
    padding: 4px 8px;
    border-radius: 6px;
    color: #fff;
}
</style>
