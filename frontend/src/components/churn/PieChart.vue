<template>
  <div>
    <Doughnut :data="chartData" :options="options" />
  </div>
</template>

<script setup>
import { Doughnut } from 'vue-chartjs'
import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend,
    Title
} from 'chart.js'

ChartJS.register(ArcElement, Tooltip, Legend, Title)

const props = defineProps({
    data: Array
})

const cores = ['#f87171', '#facc15', '#4ade80',]

const chartData = {
    labels: props.data.map(d => d.name),
    datasets: [{
        data: props.data.map(d => d.value),
        backgroundColor: cores.slice(0, props.data.length)
    }]
}

const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        title: {
            display: true,
            text: 'Distribuição de Risco de Churn',
            font: {
                    size: 14
                }
        },
        legend: {
            position: 'bottom',
            labels: {
                color: '#333',
                font: {
                    size: 14
                }
            }
        },
        tooltip: {
            callbacks: {
                label: (context) => {
                    const label = context.label || ''
                    const value = context.raw || 0
                    return `${label}: ${value} clientes`
                }
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
