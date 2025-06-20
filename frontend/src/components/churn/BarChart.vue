<template>
    <div>
        <Bar :data="chartData" :options="options" />
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Bar } from 'vue-chartjs'
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Tooltip,
    Legend
} from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend)

const props = defineProps({
    data: Array,
    xKey: String,
    yKeys: Array
})

const cores = ['#f87171', '#facc15', '#4ade80'] // Vermelho, amarelo, verde

const chartData = computed(() => {
    if (!props.data || props.data.length === 0) return { labels: [], datasets: [] }

    const xKey = props.xKey
    const yKeys = props.yKeys

    const keyPrincipal = yKeys[0]

    const dataOrdenada = [...props.data].sort((a, b) => {
        return (b[keyPrincipal] || 0) - (a[keyPrincipal] || 0)
    })

    const labels = dataOrdenada.map(d => d[xKey])

    const datasets = yKeys.map((key, index) => ({
        label: key,
        data: dataOrdenada.map(d => d[key] || 0),
        backgroundColor: cores[index % cores.length]
    }))

    return { labels, datasets }
})


const options = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        x: {
            title: {
                display: true,
                text: 'Região'
            }
        },
        y: {
            beginAtZero: true,
            title: {
                display: true,
                text: 'Número de Clientes'
            }
        }
    }
}

</script>

<style scoped>
div {
    height: 300px;
}
</style>
