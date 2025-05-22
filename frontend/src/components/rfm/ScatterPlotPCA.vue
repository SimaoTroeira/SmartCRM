<template>
  <div>
    <!-- Checkboxes por cluster -->
    <div class="checkboxes">
      <label
        v-for="(visivel, nome) in gruposVisiveis"
        :key="nome"
        :style="{ backgroundColor: coresFixas[nome] || '#ccc' }"
      >
        <input type="checkbox" v-model="gruposVisiveis[nome]" />
        {{ nome }}
      </label>
    </div>

    <!-- Gráfico -->
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
  scatterClientes: Array
})

const modo = ref('clientes')
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

  props.scatterClientes.forEach((ponto, i) => {
    const grupo = ponto.Segmento || `Cluster ${ponto.Cluster}`
    if (!grupos[grupo]) grupos[grupo] = []
    grupos[grupo].push({
      ...ponto,
      x: ponto.x_pca,
      y: ponto.y_pca
    })

    if (!(grupo in gruposVisiveis.value)) {
      gruposVisiveis.value[grupo] = true
    }
  })

  return Object.entries(grupos)
    .filter(([label]) => gruposVisiveis.value[label])
    .map(([label, data]) => ({
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
    legend: { display: false },
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
      title: {
        display: true,
        text: 'PCA 1 (aplicado às variáveis de frequência e monetário)'
      },
      min: -0.1,
      max: 1.1,
      ticks: { stepSize: 0.1 },
      grid: { color: '#e0e0e0' }
    },
    y: {
      title: {
        display: true,
        text: 'PCA 2 (aplicado à variável de recência)'
      },
      min: -0.1,
      max: 1.1,
      ticks: { stepSize: 0.1 },
      grid: { color: '#e0e0e0' }
    }
  }
}))
</script>

<style scoped>
.checkboxes {
  margin: 10px auto 20px;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
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
</style>
