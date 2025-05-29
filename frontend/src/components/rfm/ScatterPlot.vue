<template>
  <div class="radar-container">
    <!-- Botão + Checkboxes juntos em linha compacta -->
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
    <Scatter ref="chartComponent" v-if="modo === 'clientes'" :data="chartData" :options="chartOptions" />
    <!-- Tooltip abaixo do gráfico -->
    <div class="legend-info">
      <p><strong>Recência</strong>: Mede quantos dias passaram desde a última compra do cliente. Valores mais baixos
        indicam clientes mais recentes.</p>
      <p><strong>Frequência</strong>: Refere-se ao número total de compras feitas. Valores mais altos representam
        clientes mais regulares.</p>
      <p><strong>Valor Monetário</strong>: Corresponde ao total gasto pelos clientes. Quanto maior, maior o valor
        financeiro do cliente para a empresa.</p>
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

  const allR = dadosOrigem.map(p => p.r)
  const maxR = Math.max(...allR)
  const minR = Math.min(...allR)
  const escala = 14
  const minimoVisual = 4

  dadosOrigem.forEach((ponto, i) => {
    const grupo = modo.value === 'clientes'
      ? (ponto.Segmento || `Cluster ${ponto.Cluster}`)
      : (ponto.ProdutoMaisComprado || `Grupo ${i}`)

    const normalizado = (ponto.r - minR) / (maxR - minR || 1)
    const radius = Math.max(minimoVisual, normalizado * escala)

    if (!grupos[grupo]) grupos[grupo] = []
    grupos[grupo].push({
      x: ponto.x,
      y: ponto.y,
      radius,
      ...ponto
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
      pointStyle: 'circle',
      parsing: {
        xAxisKey: 'x',
        yAxisKey: 'y',
        r: 'radius'
      },
      showLine: false,
      pointRadius: ctx => ctx.raw.radius,
      pointHoverRadius: ctx => ctx.raw.radius + 2
    }))
})

const chartData = computed(() => ({
  datasets: datasets.value
}))

const chartOptions = computed(() => ({
  responsive: true,
  normalized: true,
  animation: {
    duration: 700,
    easing: 'easeOutQuart'
  },
  plugins: {
    legend: {
      display: false
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
        onPanComplete: () => {
          chartComponent.value?.chart?.update('none')
        }
      },
      zoom: {
        wheel: { enabled: true },
        pinch: { enabled: true },
        mode: 'xy',
        onZoomComplete: () => {
          chartComponent.value?.chart?.update('none')
        }
      }
    },
    decimation: false
  },
  scales: {
    x: {
      min: -0.1,
      max: 1.1,
      title: {
        display: true,
        text: 'Valor Monetário'
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
.radar-container {
  max-width: 900px;
  margin: 0 auto;
}

.top-bar {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 12px;
  padding: 0 4px;
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

.toolbar-row {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  justify-content: center;
}

.legend-info {
  font-size: 0.75rem;
  color: #555;
  background-color: #f8f8f8;
  padding: 10px 16px;
  border-left: 4px solid #2563eb;
  border-radius: 4px;
  text-align: left;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  width: 90%;
  max-width: 900px;
  margin-top: 8px;
  margin-bottom: 16px;
  margin-left: auto;
  margin-right: auto;
}


.legend-info p {
  margin: 4px 0;
}

</style>
