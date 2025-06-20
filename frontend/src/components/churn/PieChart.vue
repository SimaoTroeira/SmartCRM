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

const corPorRisco = {
    'Alto Risco': '#f87171',   // vermelho
    'Médio Risco': '#facc15',  // amarelo
    'Baixo Risco': '#4ade80'   // verde
}

const chartData = {
    labels: props.data.map(d => d.name),
    datasets: [{
        data: props.data.map(d => d.value),
        backgroundColor: props.data.map(d => corPorRisco[d.name] || '#d1d5db')
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
