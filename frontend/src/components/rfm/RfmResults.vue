<template>
  <div v-if="dadosProntos" class="space-y-10">
    <!-- Controlo de visualização -->
    <div class="card-resultados mb-6">
      <div class="mb-2">
        <h3 class="text-2xl font-semibold text-blue-700">
          {{ tituloVisualizacao }}
        </h3>
        <p class="text-sm text-gray-600 mt-1">
          {{ descricaoVisualizacao }}
        </p>
      </div>

      <div class="flex items-center gap-4 mt-4 mb-4">
        <label class="font-medium text-sm">Visualizar por:</label>
        <select v-model="modoVisualizacao" class="form-control border px-2 py-1 text-sm rounded w-48">
          <option value="clientes">Clientes</option>
          <option value="regioes">Regiões</option>
        </select>

        <!-- Submodo para clientes -->
        <div v-if="modoVisualizacao === 'clientes'" class="flex items-center gap-2">
          <label class="font-medium text-sm">Tipo de gráfico:</label>
          <select v-model="submodoClientes" class="form-control border px-2 py-1 text-sm rounded w-48">
            <option value="pca">Gráfico PCA</option>
            <option value="normal">Gráfico Normal</option>
            <option value="radar">Gráfico Radar</option>
          </select>
        </div>

        <!-- Submodo para regiões -->
        <div v-else-if="modoVisualizacao === 'regioes'" class="flex items-center gap-2">
          <label class="font-medium text-sm">Tipo de gráfico:</label>
          <select v-model="submodoRegioes" class="form-control border px-2 py-1 text-sm rounded w-48">
            <option value="barras">Gráfico de Barras</option>
            <option value="mapa">Mapa de Portugal</option>
          </select>
        </div>
      </div>

      <ScatterPlotPCA v-if="modoVisualizacao === 'clientes' && submodoClientes === 'pca'"
        :scatter-clientes="scatterClientes" />
      <ScatterPlot v-if="modoVisualizacao === 'clientes' && submodoClientes === 'normal'"
        :scatter-clientes="scatterClientes" :scatter-regioes="scatterRegioes" />
      <RadarPlot v-if="modoVisualizacao === 'clientes' && submodoClientes === 'radar'"
        :scatter-clientes="scatterClientes" />

      <RegioesBarChart v-if="modoVisualizacao === 'regioes' && submodoRegioes === 'barras'"
        :scatter-regioes="scatterRegioes" />
      <PortugalMap v-if="modoVisualizacao === 'regioes' && submodoRegioes === 'mapa'" :dados-regioes="scatterRegioes" />
    </div>

    <!-- Clientes Segmentados -->
    <div class="card-resultados">
      <h3 class="text-xl font-semibold mb-3 text-blue-700">🧑‍💼 Clientes Segmentados</h3>

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

      <p class="text-sm text-gray-600 mb-2">Total de clientes: {{ totalClientes }}</p>

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
    <div class="card-resultados no-min-height">
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

    <!-- Sugestões de Ação -->
    <RfmSuggestions :clusters-resumo="results" :clientes-segmentados="clientesSegmentados" />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import ScatterPlot from './ScatterPlot.vue'
import ScatterPlotPCA from './ScatterPlotPCA.vue'
import RadarPlot from './RadarPlot.vue'
import RegioesBarChart from './RegioesBarChart.vue'
import PortugalMap from './PortugalMap.vue'
import RfmSuggestions from './RfmSuggestions.vue'

const segmentoFiltro = ref('')
const colunaOrdenada = ref('')
const ordemCrescente = ref(true)
const limiteLinhas = ref(10)

const modoVisualizacao = ref('clientes')
const submodoClientes = ref('pca')
const submodoRegioes = ref('barras')

const props = defineProps({
  results: Array,
  descricao: String,
  clientesSegmentados: Array,
  scatterClientes: Array,
  scatterRegioes: Array
})


const totalClientes = computed(() => props.clientesSegmentados.length)

const tituloVisualizacao = computed(() => {
  if (modoVisualizacao.value === 'clientes') {
    if (submodoClientes.value === 'pca') return '🎯 Dispersão dos Clientes com PCA'
    if (submodoClientes.value === 'radar') return '📊 Comparação Média dos Segmentos'
    return '📌 Dispersão dos Clientes'
  }

  if (submodoRegioes.value === 'mapa') {
    return '🗺️ Mapa Interativo de Segmentação por Região'
  }

  return '📊 Produtos Mais Comprados por Região'
})

const descricaoVisualizacao = computed(() => {
  if (modoVisualizacao.value === 'clientes') {
    if (submodoClientes.value === 'pca') {
      return 'Este gráfico aplica Análise de Componentes Principais (PCA) para condensar múltiplas variáveis dos clientes em dois eixos principais — PCA 1 e PCA 2. Estes eixos representam as direções de maior variação nos dados, facilitando a visualização de padrões e agrupamentos complexos. Clientes com comportamentos semelhantes estão mais próximos entre si. As duas dimensões encontram-se normalizadas entre 0 e 1.'
    }
    if (submodoClientes.value === 'radar') {
      return 'Este gráfico mostra como o algoritmo de segmentação RFM classifica cada cluster. Os valores de cada métrica são normalizados entre 0% e 100% para cada segmento consoante a distribuição dos valores em toda a população de clientes analisada.'
    }
    return 'Este gráfico mostra agrupamentos de clientes com base nos seus padrões de compra através da segmentação RFM. A direção vertical tende a refletir o tempo desde a última compra. A horizontal, o valor das compras. O tamanho dos pontos representa a frequência de compras, enquanto as cores representam diferentes segmentos de clientes (como Campeões, Em Risco, etc.). As três dimensões encontram-se normalizadas entre 0 e 1.'
  }

  if (submodoRegioes.value === 'mapa') {
    return 'Este mapa mostra a distribuição dos diferentes segmentos de clientes em cada distrito de Portugal. As cores representam os segmentos dominantes em cada região, facilitando a identificação de áreas com maior concentração de clientes valiosos, em risco ou inativos.'
  }

  return 'Este gráfico mostra as regiões com os produtos mais comprados. Cada barra representa a região e o seu produto mais popular, com base no volume e valor de vendas. As cores nas barras indicam a proporção de clientes pertencentes a diferentes segmentos (como Campeões, Em Risco, etc.).'
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

.no-min-height {
  min-height: auto !important;
  padding-bottom: 1 !important;
}
</style>
