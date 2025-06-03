<template>
  <div v-if="dadosProntos" class="space-y-6">
    <!-- Card: Gráficos -->
    <div class="card-resultados mb-6">
      <!-- Título e Descrição -->
      <div class="mb-2">
        <h3 class="text-2xl font-semibold text-blue-700">
          {{ tituloVisualizacao }}
        </h3>
        <p class="text-sm text-gray-600 mt-1">
          {{ descricaoVisualizacao }}
        </p>
      </div>

      <!-- Dropdown -->
      <div class="flex items-center gap-4 mb-4">
        <label class="font-medium text-sm">Visualizar:</label>
        <select v-model="graficoSelecionado" class="form-control border px-2 py-1 text-sm rounded w-48">
          <option value="pizza">Distribuição de Risco</option>
          <option value="barras">Risco por Região</option>
        </select>
      </div>

      <!-- Gráficos -->
      <div v-if="graficoSelecionado === 'pizza' && dadosPizza.length">
        <PieChart :data="dadosPizza" />
      </div>
      <div v-else-if="graficoSelecionado === 'barras' && dadosBarras.length">
        <BarChart :data="dadosBarras" :x-key="'Regiao'" :y-keys="['Alto Risco', 'Médio Risco', 'Baixo Risco']" />
      </div>
      <div v-else class="text-gray-500 italic">Sem dados suficientes para gráfico.</div>
    </div>

    <!-- Lista de Clientes -->
    <div class="card-resultados">
      <div class="cabecalho-clientes mb-4">
        <h3 class="text-xl font-semibold mb-3 text-blue-700">🧑‍💼 Risco de cancelamento por cliente</h3>
        <button @click="exportarParaExcel" class="btn-exportar">
          📥 Exportar Excel
        </button>
      </div>

      <div class="conteudo-centrado">
        <div class="controles-tabela-clientes mb-4">
          <button @click="resetarOrdenacao" class="btn-reset-custom">Repor ordenação</button>

          <div class="filtro-box">
            <label class="text-sm font-medium">Filtrar por risco:</label>
            <select v-model="filtroRisco" class="form-control border border-gray-300 rounded px-2 py-1 text-sm w-64">
              <option value="">Todos</option>
              <option v-for="tipo in tiposRisco" :key="tipo">{{ tipo }}</option>
            </select>
          </div>

          <div class="limite-box">
            <label class="text-sm font-medium text-gray-700 mr-2">Linhas por página:</label>
            <input v-model.number="limiteLinhas" type="number" min="1"
              class="border border-gray-300 rounded px-2 py-1 text-sm w-20" />
          </div>
        </div>
        <p class="text-sm text-gray-600 mb-2">Total de clientes: {{ clientesFiltrados.length }}</p>
        <div class="overflow-x-auto">
          <table class="min-w-full table-auto border border-gray-200 text-sm">
            <thead class="bg-gray-100">
              <tr>
                <th v-for="col in colunasTabela" :key="col.key" @click="ordenarPor(col.key)"
                  class="cursor-pointer px-4 py-2 border hover:bg-gray-100 select-none">
                  {{ col.label }}
                  <span v-if="colunaOrdenada === col.key">
                    {{ ordemCrescente ? '▲' : '▼' }}
                  </span>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cliente in clientesFiltrados" :key="cliente.ClienteID" class="hover:bg-gray-50">
                <td class="border px-4 py-2">{{ cliente.ClienteID }}</td>
                <td class="border px-4 py-2">{{ cliente.Nome || '-' }}</td>
                <td class="border px-4 py-2">{{ cliente.ScoreChurn }}</td>
                <td class="border px-4 py-2">{{ cliente.Classificacao }}</td>
                <td class="border px-4 py-2">{{ cliente.Regiao ?? '-' }}</td>
                <td class="border px-4 py-2">{{ cliente.Localidade ?? '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>

    <!-- Sugestões de Ação -->
    <ChurnSuggestions :clientes="clientes" :nomeEmpresa="nomeEmpresa" :nomeCampanha="nomeCampanha" />

    <!-- Botão de exportação no fundo da página -->
    <div class="card-resultados card-pequeno text-center mt-10">
      <h3 class="text-base font-medium text-gray-700 mb-2">Exportar Relatório PDF</h3>
      <ExportPdfChurn :nome-empresa="props.nomeEmpresa" :nome-campanha="props.nomeCampanha" :dados-pizza="dadosPizza"
        :dados-barras="dadosBarras" :clientes="clientes" />
    </div>

  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import PieChart from './PieChart.vue'
import BarChart from './BarChart.vue'
import ChurnSuggestions from './ChurnSuggestions.vue'
import ExportPdfChurn from './ExportPdfChurn.vue'

import axios from 'axios'

const props = defineProps({
  results: Object,
  descricao: String,
  campanhaId: [String, Number],
  nomeEmpresa: String,
  nomeCampanha: String
})


const clientes = ref([])
const graficoSelecionado = ref('pizza')

const dadosPizza = ref([])
const dadosBarras = ref([])
const dadosProntos = computed(() => clientes.value.length && dadosPizza.value.length && dadosBarras.value.length)

const tituloVisualizacao = computed(() =>
  graficoSelecionado.value === 'pizza'
    ? '🥧 Distribuição de Risco'
    : '📊 Risco por Região'
)

const descricaoVisualizacao = computed(() =>
  graficoSelecionado.value === 'pizza'
    ? 'Este gráfico mostra a proporção de clientes em cada categoria de risco de churn (Alto, Médio ou Baixo). É útil para compreender rapidamente a saúde geral da base de clientes e identificar se há uma concentração perigosa de clientes em risco de abandono.'
    : 'Este gráfico apresenta o número de clientes por região, segmentados de acordo com o seu nível de risco de churn. Permite identificar áreas geográficas com maior concentração de clientes em risco, ajudando na definição de estratégias regionais de retenção.'
)

const colunasTabela = [
  { key: 'ClienteID', label: 'ClienteID' },
  { key: 'Nome', label: 'Nome' },
  { key: 'ScoreChurn', label: 'ScoreChurn' },
  { key: 'Classificacao', label: 'Classificação' },
  { key: 'Regiao', label: 'Região' },
  { key: 'Localidade', label: 'Localidade' }
]


const filtroRisco = ref('')
const colunaOrdenada = ref('')
const ordemCrescente = ref(true)
const limiteLinhas = ref(10)

const tiposRisco = computed(() => [...new Set(clientes.value.map(c => c.Classificacao))])

const clientesFiltrados = computed(() => {
  let filtrados = filtroRisco.value
    ? clientes.value.filter(c => c.Classificacao === filtroRisco.value)
    : [...clientes.value]

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

  return filtrados.slice(0, limiteLinhas.value || 10)
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

watch(
  () => props.results,
  async () => {
    const campanhaId = props.campanhaId || props.results?.campanha_id
    if (!campanhaId) return

    try {
      const res = await axios.get(`http://127.0.0.1:8000/api/algoritmos/resultados_complementares/${campanhaId}?algoritmo=churn&tipo=clientes`)
      const data = res.data

      if (Array.isArray(data)) {
        clientes.value = data
        prepararGraficos()
      } else {
        console.warn('Resposta inesperada ao buscar clientes churn:', data)
      }
    } catch (e) {
      console.error('Erro ao buscar clientes churn:', e)
    }
  },
  { immediate: true }
)

function prepararGraficos() {
  if (!clientes.value.length) return

  const grupo = clientes.value.reduce((acc, c) => {
    if (!c.Classificacao) return acc
    acc[c.Classificacao] = (acc[c.Classificacao] || 0) + 1
    return acc
  }, {})

  dadosPizza.value = Object.entries(grupo).map(([tipo, valor]) => ({
    name: tipo,
    value: valor
  }))

  const mapa = {}
  for (const c of clientes.value) {
    if (!c.Regiao || !c.Classificacao) continue
    if (!mapa[c.Regiao]) {
      mapa[c.Regiao] = { Regiao: c.Regiao, 'Alto Risco': 0, 'Médio Risco': 0, 'Baixo Risco': 0 }
    }
    mapa[c.Regiao][c.Classificacao] += 1
  }

  dadosBarras.value = Object.values(mapa)
}

async function exportarParaExcel() {
  if (!clientes.value.length) return

  const XLSX = await import('xlsx')
  const data = clientes.value.map(c => ({ ...c }))
  const worksheet = XLSX.utils.json_to_sheet(data)
  const workbook = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(workbook, worksheet, 'Risco de Cancelamento')

  // Usar props ou valores disponíveis
  const nomeEmpresaLimpo = (props.nomeEmpresa || 'Empresa')
    .normalize("NFD").replace(/[\u0300-\u036f]/g, "") // remover acentos
    .replace(/\s+/g, '') // remover espaços
    .replace(/[^a-zA-Z0-9]/g, '') // remover outros símbolos

  const nomeCampanhaLimpo = (props.nomeCampanha || 'Campanha')
    .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
    .replace(/\s+/g, '')
    .replace(/[^a-zA-Z0-9]/g, '')

  const nomeFicheiro = `${nomeEmpresaLimpo}_${nomeCampanhaLimpo}_RiscoCancelamento_Churn.xlsx`

  XLSX.writeFile(workbook, nomeFicheiro)
}


</script>

<style scoped>
.card-resultados {
  background-color: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
  padding: 24px;
  min-height: 500px;
}

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

.cabecalho-clientes {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.btn-exportar {
  background-color: #2563eb;
  color: white;
  padding: 6px 16px;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.875rem;
  transition: all 0.2s ease-in-out;
  cursor: pointer;
  border: none;
}

.btn-exportar:hover {
  background-color: #1e40af;
}

.conteudo-centrado {
  max-width: 900px;
  margin: 0 auto;
}

.card-pequeno {
  padding: 12px 16px;
  min-height: auto;
  box-shadow: none;
  border-radius: 8px;
  background-color: #f9fafb;
}

.card-pequeno h3 {
  font-size: 1rem;
  margin-bottom: 8px;
}

</style>
