<template>
  <div class="radar-container">
    <!-- Top bar com botão e checkboxes -->
    <div class="top-bar">
      <div class="toolbar-row">
        <button @click="resetZoom" class="btn-reset">Repor Zoom</button>

        <div class="checkboxes">
          <label v-for="(visivel, nome) in gruposVisiveis" :key="nome"
            :style="{ backgroundColor: coresFixas[nome] || '#ccc' }">
            <input type="checkbox" v-model="gruposVisiveis[nome]" />
            {{ nome }}
          </label>
        </div>
      </div>
    </div>

    <!-- Gráfico -->
    <Scatter ref="chartComponent" :data="chartData" :options="chartOptions" />

    <!-- Legenda abaixo do gráfico -->
    <div class="legend-info">
      <p><strong>PCA 1</strong>: Representa a direção com maior variância explicada entre as variáveis analisadas.</p>
      <p><strong>PCA 2</strong>: Representa uma direção ortogonal à primeira, capturando a segunda maior variação nos
        dados.</p>
    </div>

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
  scatterClientes: Array
})

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
      backgroundColor: coresFixas[label] || '#ccc',
      pointStyle: 'circle'
    }))
})

const chartData = computed(() => ({
  datasets: datasets.value
}))

const chartOptions = computed(() => ({
  responsive: true,
  animation: {
    duration: 700,
    easing: 'easeOutQuart'
  },
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
    },
    zoom: {
      pan: {
        enabled: true,
        mode: 'xy'
      },
      zoom: {
        wheel: { enabled: true },
        pinch: { enabled: true },
        mode: 'xy'
      },
      limits: {
        x: { min: -1, max: 2 },
        y: { min: -1, max: 2 }
      }
    }
  },
  scales: {
    x: {
      min: -0.1,
      max: 1.1,
      title: {
        display: true,
        text: 'PCA 1'
      },
      grid: { color: '#e0e0e0' }
    },
    y: {
      min: -0.1,
      max: 1.1,
      title: {
        display: true,
        text: 'PCA 2'
      },
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
.radar-container {
  max-width: 900px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.legend-info {
  font-size: 0.75rem;
  color: #555;
  background-color: #f8f8f8;
  padding: 8px 16px;
  border-left: 4px solid #2563eb;
  border-radius: 4px;
  text-align: left;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  width: 90%;
  /* ocupa a largura do gráfico */
  max-width: 900px;
  /* mesmo valor que o radar-container */
  margin-top: 14px;
  /* espaço entre gráfico e legenda */
}


.legend-info p {
  margin: 4px 0;
}

.top-bar {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.toolbar-row {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  justify-content: center;
}

.checkboxes {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
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

.btn-reset {
  background-color: #fff;
  border: 2px solid #2563eb;
  color: #2563eb;
  padding: 6px 12px;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.875rem;
  height: 36px;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
}

.btn-reset:hover {
  background-color: #e0ecff;
}
</style>
