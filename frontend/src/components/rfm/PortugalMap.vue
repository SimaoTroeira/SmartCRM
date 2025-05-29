<template>
    <div class="portugal-map-container">
        <!-- Botões topo -->
        <div class="map-toolbar">
            <button class="btn-toggle-modo" @click="alternarModoValorTotal">
                {{ modoValorTotal ? '📌 Modo Clusters' : '📊 Modo Valor Total' }}
            </button>
            <button class="btn-reset-zoom" @click="resetZoom">🔄 Repor Zoom</button>
        </div>

        <!-- Checkboxes por segmento -->
        <div class="map-clusters">
            <label v-for="segmento in todosSegmentos" :key="segmento"
                :style="{ backgroundColor: coresPorSegmento[segmento] || '#ccc' }">
                <input type="checkbox" v-model="segmentosVisiveis[segmento]" :disabled="modoValorTotal" />
                {{ segmento }}
            </label>
        </div>

        <!-- Mapa -->
        <v-chart ref="vChartRef" class="echart-map" :option="chartOptions" autoresize @ready="onChartReady" />
    </div>

</template>

<script setup>
import { ref, onMounted, computed, watchEffect } from 'vue'
import VChart from 'vue-echarts'
import * as echarts from 'echarts/core'
import { MapChart } from 'echarts/charts'
import { TooltipComponent, VisualMapComponent } from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'
import geoJson from '@/assets/portugal-distritos.json'

echarts.use([MapChart, TooltipComponent, VisualMapComponent, CanvasRenderer])
echarts.registerMap('portugal', geoJson)

const props = defineProps({
    dadosRegioes: Array
})

const vChartRef = ref(null)
const chartInstance = ref(null)
const chartOptions = ref({})
const zoomLevel = ref(1.2)
const modoValorTotal = ref(false)

const coresPorSegmento = {
    'Campeões': '#8e44ad',
    'Clientes Valiosos': '#2980b9',
    'Clientes Regulares': '#27ae60',
    'Em Risco': '#f39c12',
    'Clientes Perdidos': '#c0392b',
    'Pouca Frequência': '#7f8c8d',
    'Baixo Valor': '#d35400',
    'Inativos': '#95a5a6'
}

const todosSegmentos = computed(() =>
    Array.from(new Set(
        props.dadosRegioes.flatMap(r =>
            Object.keys(r).filter(k =>
                !['Regiao', 'ProdutoID', 'ProdutoMaisComprado', 'Categoria', 'Marca', 'QuantidadeTotal', 'ValorTotal'].includes(k)
            )
        )
    ))
)

const segmentosVisiveis = ref({})
watchEffect(() => {
    todosSegmentos.value.forEach(s => {
        if (!(s in segmentosVisiveis.value)) {
            segmentosVisiveis.value[s] = true
        }
    })
})

function alternarModoValorTotal() {
    modoValorTotal.value = !modoValorTotal.value
    if (modoValorTotal.value) {
        Object.keys(segmentosVisiveis.value).forEach(s => segmentosVisiveis.value[s] = false)
    } else {
        Object.keys(segmentosVisiveis.value).forEach(s => segmentosVisiveis.value[s] = true)
    }
}

function resetZoom() {
    zoomLevel.value = 1.2
    chartOptions.value = { ...chartOptions.value }
}

function onChartReady(chart) {
    chartInstance.value = chart
    chart.on('georoam', () => {
        const option = chart.getOption()
        const zoom = option.series?.[0]?.zoom
        if (zoom) zoomLevel.value = zoom
    })
}

onMounted(() => {
    watchEffect(() => {
        if (!props.dadosRegioes || props.dadosRegioes.length === 0) return

        const valores = props.dadosRegioes.map(r => {
            const segmentos = todosSegmentos.value.reduce((acc, key) => {
                acc[key] = r[key] || 0
                return acc
            }, {})

            const dominante = Object.entries(segmentos)
                .filter(([s]) => segmentosVisiveis.value[s])
                .sort((a, b) => b[1] - a[1])[0]?.[0]

            return {
                name: r.Regiao,
                value: r.ValorTotal || 0,
                itemStyle: modoValorTotal.value
                    ? undefined
                    : { areaColor: dominante ? coresPorSegmento[dominante] : '#ccc' },
                ...segmentos
            }
        })

        chartOptions.value = {
            tooltip: {
                trigger: 'item',
                formatter: params => {
                    const { name, value, data } = params
                    if (modoValorTotal.value) {
                        return `<strong>${name}</strong><br/>Valor Total: €${value?.toLocaleString('pt-PT')}`
                    }
                    const segmentos = todosSegmentos.value.filter(s => data?.[s] > 0 && segmentosVisiveis.value[s])
                    const lines = segmentos.length
                        ? segmentos.map(c => `${c}: ${data[c]}`).join('<br/>')
                        : 'Sem dados de cluster'
                    return `
                        <strong>${name}</strong><br/>
                        Valor Total: €${value?.toLocaleString('pt-PT')}<br/>
                        ${lines}`
                }
            },
            visualMap: modoValorTotal.value
                ? {
                    min: Math.min(...valores.map(v => v.value)),
                    max: Math.max(...valores.map(v => v.value)),
                    left: 'left',
                    top: 'bottom',
                    text: ['Mais Valor', 'Menos Valor'],
                    inRange: {
                        color: ['#aec7e8', '#084594']
                    },
                    calculable: true
                }
                : undefined,
            series: [
                {
                    name: 'Regiões',
                    type: 'map',
                    map: 'portugal',
                    roam: true,
                    zoom: chartInstance.value?.getOption()?.series?.[0]?.zoom ?? zoomLevel.value,
                    label: {
                        show: true,
                        fontSize: 10
                    },
                    emphasis: {
                        label: { show: true, fontWeight: 'bold' }
                    },
                    data: valores
                }
            ]
        }
    })

    const chartDom = vChartRef.value?.$el;
    if (chartDom) {
        chartDom.addEventListener('wheel', (event) => {
            if (Math.abs(event.deltaY) > Math.abs(event.deltaX)) {
                event.preventDefault();
            }
        }, { passive: false });
    }
})
</script>

<style scoped>
.portugal-map-container {
    width: 100%;
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
    border: 1px solid #ccc;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    background-color: white;
}

/* Botões em linha horizontal */
.map-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

/* Checkboxes por segmento abaixo dos botões */
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

/* Botões */
.btn-reset-zoom,
.btn-toggle-modo {
    background-color: white;
    border: 2px solid #2563eb;
    color: #2563eb;
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.875rem;
    transition: all 0.2s ease-in-out;
    cursor: pointer;
}

.btn-reset-zoom:hover,
.btn-toggle-modo:hover {
    background-color: #e0ecff;
}

.echart-map {
    width: 100%;
    height: 440px;
}
</style>
