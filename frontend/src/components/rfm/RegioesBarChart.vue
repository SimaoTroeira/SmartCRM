<template>
  <div class="bar-chart-container">
    <!-- Checkboxes por segmento -->
    <div class="checkboxes">
      <label v-for="segmento in todosSegmentos" :key="segmento"
        :style="{ backgroundColor: coresPorSegmento[segmento] || '#ccc' }">
        <input type="checkbox" v-model="segmentosVisiveis[segmento]" />
        {{ segmento }}
      </label>
    </div>

    <!-- Gráfico -->
    <Bar :data="chartData" :options="chartOptions" />
  </div>
</template>


<script setup>
import { ref, computed, watchEffect } from 'vue'
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

const camposPadrao = ['Regiao', 'ProdutoID', 'ProdutoMaisComprado', 'Categoria', 'Marca', 'QuantidadeTotal', 'ValorTotal']

const todosSegmentos = computed(() =>
  Array.from(new Set(
    props.scatterRegioes.flatMap(r =>
      Object.keys(r).filter(key => !camposPadrao.includes(key))
    )
  ))
)

const segmentosVisiveis = ref({})

// Inicializa os checkboxes quando os segmentos forem detectados
watchEffect(() => {
  todosSegmentos.value.forEach(s => {
    if (!(s in segmentosVisiveis.value)) {
      segmentosVisiveis.value[s] = true
    }
  })
})

const filteredDatasets = computed(() =>
  todosSegmentos.value
    .filter(seg => segmentosVisiveis.value[seg])
    .map(segmento => ({
      label: segmento,
      data: props.scatterRegioes.map(r => {
        const totalClientesSegmento = r[segmento] || 0
        const totalClientes = todosSegmentos.value.reduce((acc, seg) => acc + (r[seg] || 0), 0)
        const percentagem = totalClientes ? (totalClientesSegmento / totalClientes) : 0
        return percentagem * r.ValorTotal
      }),
      backgroundColor: coresPorSegmento[segmento] || '#ccc',
      stack: 'stack1'
    }))
)

const chartData = computed(() => ({
  labels: props.scatterRegioes.map(r => r.Regiao),
  datasets: filteredDatasets.value
}))

const chartOptions = {
  responsive: true,
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: function (ctx) {
          const regiao = props.scatterRegioes[ctx.dataIndex]
          const segmento = ctx.dataset.label
          const valorSegmento = ctx.parsed.y
          const totalClientesSegmento = regiao[segmento] || 0
          const totalClientes = todosSegmentos.value.reduce((acc, seg) => acc + (regiao[seg] || 0), 0)
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
      title: { display: true, text: 'Região' }
    },
    y: {
      stacked: true,
      beginAtZero: true,
      title: { display: true, text: 'Valor Total (€)' }
    }
  }
}
</script>


<style scoped>

.bar-chart-container {
  max-width: 900px;
  margin: 0 auto;
}

.checkboxes {
  margin-bottom: 1rem;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 10px;
}

.checkboxes label {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  border-radius: 6px;
  font-weight: bold;
  color: white;
  font-size: 0.9rem;
}

</style>
