<template>
  <div class="portugal-map-container">
    <div class="map-clusters">
      <label v-for="nivel in niveisRisco" :key="nivel" :style="{ backgroundColor: coresPorRisco[nivel] }">
        <input type="checkbox" v-model="riscosVisiveis[nivel]" />
        {{ nivel }}
      </label>
    </div>

    <v-chart ref="vChartRef" class="echart-map" :option="chartOptions" autoresize @ready="onChartReady" />

    <v-chart class="mini-map mini-acores" :option="acoresOptions" autoresize />
    <v-chart class="mini-map mini-madeira" :option="madeiraOptions" autoresize />
  </div>
</template>

<script setup>
import { ref, computed, watchEffect, onMounted } from 'vue'
import VChart from 'vue-echarts'
import * as echarts from 'echarts/core'
import { MapChart } from 'echarts/charts'
import { TooltipComponent } from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'
import geoJson from '@/assets/portugal-distritos.json'

echarts.use([MapChart, TooltipComponent, CanvasRenderer])
echarts.registerMap('portugal', geoJson)
echarts.registerMap('portugal_acores', geoJson)
echarts.registerMap('portugal_madeira', geoJson)

const props = defineProps({
  dadosRegioes: Array
})

const vChartRef = ref(null)
const chartOptions = ref({})
const zoomLevel = ref(2.5)

const niveisRisco = ['Baixo Risco', 'Médio Risco', 'Alto Risco']
const coresPorRisco = {
  'Baixo Risco': '#4ade80',
  'Médio Risco': '#facc15',
  'Alto Risco': '#f87171'
}

const riscosVisiveis = ref({})
watchEffect(() => {
  niveisRisco.forEach(n => {
    if (!(n in riscosVisiveis.value)) riscosVisiveis.value[n] = true
  })
})

function dadosFiltrados(regiaoAlvo = null) {
  return props.dadosRegioes
    .filter(r => !regiaoAlvo || r.Regiao === regiaoAlvo)
    .map(r => {
      const riscoDominante = niveisRisco
        .filter(n => riscosVisiveis.value[n])
        .map(n => [n, r[n] || 0])
        .sort((a, b) => b[1] - a[1])[0]?.[0]

      return {
        name: r.Regiao,
        value: r.ValorTotal || 0,
        itemStyle: {
          areaColor: riscoDominante ? coresPorRisco[riscoDominante] : '#ccc'
        },
        ...Object.fromEntries(niveisRisco.map(n => [n, r[n] || 0]))
      }
    })
}

const tooltipFormatter = params => {
  const { name, value, data } = params
  const linhas = niveisRisco
    .filter(n => data?.[n] > 0 && riscosVisiveis.value[n])
    .map(n => `${n}: ${data[n]}`)
    .join('<br/>') || 'Sem dados de risco'

  return `<strong>${name}</strong><br/>${linhas}`
}

const chartOptionsBase = (mapName, zoom, center, filtro = null) => ({
  tooltip: {
    trigger: 'item',
    formatter: tooltipFormatter
  },
  series: [
    {
      type: 'map',
      map: mapName,
      roam: false,
      zoom,
      center,
      label: { show: true, fontSize: 10 },
      emphasis: { label: { show: true, fontWeight: 'bold' } },
      data: dadosFiltrados(filtro)
    }
  ]
})

const chartOptionsMain = computed(() => chartOptionsBase('portugal', zoomLevel.value, [-12, 39.5]))
const acoresOptions = computed(() => chartOptionsBase('portugal_acores', 4.5, [-28, 38], 'Açores'))
const madeiraOptions = computed(() => chartOptionsBase('portugal_madeira', 6, [-17, 32.7], 'Madeira'))

watchEffect(() => {
  chartOptions.value = chartOptionsMain.value
})

function onChartReady(chart) {
  chart.on('georoam', () => {
    const option = chart.getOption()
    const zoom = option.series?.[0]?.zoom
    if (zoom) zoomLevel.value = zoom
  })
}
</script>

<style scoped>
.portugal-map-container {
  width: 100%;
  max-width: 900px;
  margin: 0 auto;
  padding: 20px;
  border: 1px solid #ccc;
  border-radius: 8px;
  background: white;
  position: relative;
  overflow-y: auto;
  max-height: 100vh;
}

.map-clusters {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: center;
  margin-bottom: 14px;
}

.map-clusters label {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  border-radius: 6px;
  font-weight: bold;
  color: white;
  font-size: 0.85rem;
}

.echart-map {
  width: 100%;
  height: 440px;
}

.mini-map {
  position: absolute;
  width: 180px;
  height: 180px;
  border: 1px solid #ddd;
  background: white;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
  z-index: 2;
}

.mini-acores {
  top: 150px;
  left: 10px;
}

.mini-madeira {
  bottom: 10px;
  left: 10px;
}
</style>