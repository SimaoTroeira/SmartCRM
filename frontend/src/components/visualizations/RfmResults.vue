<template>
  <div v-if="dadosProntos" class="space-y-10">
    <!-- Controlo de visualização -->
    <div class="card-resultados mb-6">
      <h3 class="text-xl font-semibold mb-4 text-blue-700">Visualização dos Clusters</h3>
      <div class="flex items-center gap-4 mb-4">
        <label class="font-medium text-sm">Visualizar por:</label>
        <select v-model="modoVisualizacao" class="form-control border px-2 py-1 text-sm rounded w-48">
          <option value="clientes">Clientes</option>
          <option value="regioes">Regiões</option>
        </select>
      </div>

      <ScatterPlot v-if="modoVisualizacao === 'clientes'" :scatter-clientes="scatterClientes"
        :scatter-regioes="scatterRegioes" />
      <RegioesBarChart v-else :scatter-regioes="scatterRegioes" />
    </div>

    <!-- Clientes Segmentados -->
    <div class="card-resultados">
      <h3 class="text-xl font-semibold mb-3 text-blue-700">Clientes Segmentados</h3>

      <div class="controles-tabela-clientes mb-4">
        <button @click="resetarOrdenacao" class="btn-reset-custom">
          Repor ordenação
        </button>

        <div class="filtro-box">
          <label class="text-sm font-medium">Filtrar por segmento:</label>
          <select v-model="segmentoFiltro" class="form-control border border-gray-300 rounded px-2 py-1 text-sm w-64">
            <option value="">Todos</option>
            <option v-for="seg in segmentosUnicos" :key="seg">{{ seg }}</option>
          </select>
        </div>

        <div class="limite-box">
          <label class="text-sm font-medium text-gray-700 mr-2">Linhas por página:</label>
          <input v-model.number="limiteLinhas" type="number" min="1"
            class="border border-gray-300 rounded px-2 py-1 text-sm w-20" />
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full table-auto border border-gray-200 text-sm">
          <thead class="bg-gray-100">
            <tr>
              <th v-for="key in Object.keys(clientesSegmentados[0])" :key="key" v-if="key !== 'ClienteID'"
                @click="ordenarPor(key)" class="cursor-pointer px-4 py-2 border hover:bg-gray-100 select-none">
                {{ key === 'Nome' ? 'Nome do Cliente' : key }}
                <span v-if="colunaOrdenada === key">
                  {{ ordemCrescente ? '▲' : '▼' }}
                </span>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in clientesFiltrados" :key="index">
              <td v-for="(val, key) in item" :key="key" v-if="key !== 'ClienteID'" class="px-4 py-2 border">
                {{ val }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Tabela Resumo -->
    <div class="card-resultados">
      <h3 class="text-xl font-semibold mb-3 text-blue-700">Tabela</h3>
      <p class="text-sm text-gray-600 mb-2">Apresenta dados estatísticos por cluster.</p>
      <p class="text-sm text-gray-500 mb-4">{{ descricao }}</p>

      <div class="overflow-x-auto">
        <table class="min-w-full table-auto border border-gray-200 text-sm">
          <thead class="bg-gray-100">
            <tr>
              <th v-for="(val, key) in results[0]" :key="key" class="px-4 py-2 border text-left">
                {{ nomeColunasCluster[key] || key }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in results" :key="idx">
              <td v-for="(val, key) in row" :key="key" class="px-4 py-2 border">
                {{ formatarValor(key, val) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import ScatterPlot from '@/components/visualizations/ScatterPlot.vue'
import RegioesBarChart from '@/components/visualizations/RegioesBarChart.vue'

const segmentoFiltro = ref('')
const colunaOrdenada = ref('')
const ordemCrescente = ref(true)
const limiteLinhas = ref(10)

const modoVisualizacao = ref('clientes')
const clustersClientes = computed(() =>
  Array.isArray(props.scatterClientes) ? props.scatterClientes : []
)
const clustersRegioes = computed(() =>
  Array.isArray(props.scatterRegioes) ? props.scatterRegioes : []
)


const cores = [
  "#8884d8", "#82ca9d", "#ffc658", "#ff7300", "#d0ed57", "#a4de6c", "#8dd1e1", "#83a6ed"
]


const props = defineProps({
  results: Array,
  descricao: String,
  clientesSegmentados: Array,
  scatterClientes: Array,
  scatterRegioes: Array
})

const nomeColunasCluster = {
  Cluster: "Cluster",
  Segmento: "Segmento",
  FrequencyMedia: "Frequência Média",
  MonetaryMedia: "Valor Médio (€)",
  MonetaryTotal: "Valor Total (€)",
  QtdClientes: "Nº de Clientes",
  RecencyMedia: "Recência Média (Em Dias)"
}

const formatarValor = (key, val) => {
  if (typeof val !== 'number') return val
  const lower = key.toLowerCase()
  if (['monetarymedia', 'valormédio', 'valortotal'].some(k => lower.includes(k))) {
    return new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'EUR' }).format(val)
  }
  if (lower.includes('recency')) return `${Math.round(val)} dias`
  if (lower.includes('frequency')) return val.toFixed(2)
  return val
}

const segmentosUnicos = computed(() => [...new Set(props.clientesSegmentados.map(c => c.Segmento))])

const clientesFiltrados = computed(() => {
  let filtrados = segmentoFiltro.value
    ? props.clientesSegmentados.filter(c => c.Segmento === segmentoFiltro.value)
    : [...props.clientesSegmentados]

  if (colunaOrdenada.value) {
    filtrados.sort((a, b) => {
      const valA = a[colunaOrdenada.value]
      const valB = b[colunaOrdenada.value]
      if (typeof valA === 'number' && typeof valB === 'number') {
        return ordemCrescente.value ? valA - valB : valB - valA
      }
      return ordemCrescente.value
        ? String(valA).localeCompare(String(valB))
        : String(valB).localeCompare(String(valA))
    })
  }

  return filtrados.slice(0, limiteLinhas.value || 50)
})

function ordenarPor(coluna) {
  if (colunaOrdenada.value === coluna) {
    ordemCrescente.value = !ordemCrescente.value
  } else {
    colunaOrdenada.value = coluna
    ordemCrescente.value = true
  }
}

function resetarOrdenacao() {
  colunaOrdenada.value = ''
  ordemCrescente.value = true
}

const dadosProntos = computed(() => {
  return props.results.length && props.clientesSegmentados.length
})


const gruposScatter = computed(() => {
  if (modoVisualizacao.value === 'clientes') {
    const grupos = {}
    for (const item of clustersClientes.value) {
      const nome = item.Segmento || `Cluster ${item.Cluster}`
      if (!grupos[nome]) grupos[nome] = []
      grupos[nome].push({ ...item })
    }
    const resultado = Object.entries(grupos).map(([nome, dados]) => ({ nome, dados }))
    console.log('gruposScatter (clientes):', resultado)
    return resultado
  } else {
    const resultado = clustersRegioes.value.map((regiao, i) => ({
      nome: regiao.ProdutoMaisComprado,
      dados: [{
        x: i * 5,
        y: i * 7 + Math.random() * 10,
        ...regiao
      }]
    }))
    console.log('gruposScatter (regiões):', resultado)
    return resultado
  }
})



</script>

<style scoped>
.controles-tabela-clientes {
  display: flex;
  align-items: center;
  gap: 2rem;
  flex-wrap: wrap;
}

.filtro-box,
.limite-box {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Botão personalizado */
.btn-reset-custom {
  background-color: white;
  border: 2px solid #2563eb;
  color: #2563eb;
  padding: 6px 16px;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.875rem;
  transition: all 0.2s ease-in-out;
  cursor: pointer;
}

.btn-reset-custom:hover {
  background-color: #e0ecff;
}

.card-resultados {
  background-color: #ffffff;
  border: 1px solid #e5e7eb;
  /* gray-200 */
  border-radius: 12px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
  padding: 24px;
  min-height: 500px;
  background-color: #fff;
}

.tooltip-box {
  background-color: white;
  padding: 10px;
  border: 1px solid #ccc;
  font-size: 0.875rem;
  border-radius: 6px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}
</style>