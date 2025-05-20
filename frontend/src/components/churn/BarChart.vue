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
    yKeys: Array // Ex: ['Baixo Risco', 'Médio Risco', 'Alto Risco']
})

const cores = ['#f87171', '#facc15', '#4ade80'] // Vermelho, amarelo, verde

const chartData = computed(() => {
    return {
        labels: props.data.map(d => d[props.xKey]),
        datasets: props.yKeys.map((key, index) => ({
            label: key,
            data: props.data.map(d => d[key] || 0),
            backgroundColor: cores[index % cores.length]
        }))
    }
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
