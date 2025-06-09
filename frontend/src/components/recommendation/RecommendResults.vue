<template>

  <div class="space-y-8">

    <div v-if="regrasOrdenadasProd.length" class="card-resultados">
      <h3 class="text-xl font-semibold mb-2">📦 Cross-Selling (Produto→Produto)</h3>

      <div class="overflow-auto">

        <div class="mb-4">
          <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" @click="exportarParaExcel">
            Exportar para Excel
          </button>
        </div>

        <table class="min-w-full text-sm border">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-3 py-2 border">Antecedentes</th>
              <th class="px-3 py-2 border">Consequentes</th>
              <th class="px-3 py-2 border cursor-pointer" @click="ordenarPorProd('support')">Suporte (%)</th>
              <th class="px-3 py-2 border cursor-pointer" @click="ordenarPorProd('confidence')">Confiança (%)</th>
              <th class="px-3 py-2 border cursor-pointer" @click="ordenarPorProd('lift')">Lift</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(r, i) in regrasOrdenadasProd" :key="i" class="hover:bg-gray-50">
              <td class="px-3 py-1 border">{{ r.antecedents.join(', ') }}</td>
              <td class="px-3 py-1 border">{{ r.consequents.join(', ') }}</td>
              <td class="px-3 py-1 border">{{ formatPercent(r.support) }}</td>
              <td class="px-3 py-1 border">{{ formatPercent(r.confidence) }}</td>
              <td class="px-3 py-1 border">{{ r.lift.toFixed(2) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="bg-blue-50 border border-blue-200 text-sm text-blue-800 rounded-lg p-4 mb-6">
        <p><strong>Antecedentes</strong>: produtos que foram comprados antes ou em conjunto com outros.</p>
        <p><strong>Consequentes</strong>: produtos recomendados com base nos antecedentes.</p>
        <p><strong>Suporte (%)</strong>: frequência com que essa combinação de produtos ocorre no total de transações.
        </p>
        <p><strong>Confiança (%)</strong>: probabilidade de o consequente ser comprado quando os antecedentes o são.</p>
        <p><strong>Lift</strong>: mede a força da associação; valores superiores a 1 indicam uma relação positiva entre
          os
          itens.</p>
      </div>
    </div>

    <div v-for="(regras, atributo) in regrasOrdenadasAttr" :key="atributo" class="card-resultados">
      <h3 class="text-xl font-semibold mb-2">🔖 Recomendações via "{{ atributo }}"</h3>

      <div class="mb-4">
        <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" @click="exportarParaExcel">
          Exportar para Excel
        </button>
      </div>


      <div class="overflow-auto">
        <table class="min-w-full text-sm border">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-3 py-2 border">Antecedentes</th>
              <th class="px-3 py-2 border">Consequentes</th>
              <th class="px-3 py-2 border cursor-pointer" @click="ordenarPorAttr(atributo, 'support')">Suporte (%)</th>
              <th class="px-3 py-2 border cursor-pointer" @click="ordenarPorAttr(atributo, 'confidence')">Confiança (%)
              </th>
              <th class="px-3 py-2 border cursor-pointer" @click="ordenarPorAttr(atributo, 'lift')">Lift</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(r, i) in regras" :key="i" class="hover:bg-gray-50">
              <td class="px-3 py-1 border">{{ r.antecedents.join(', ') }}</td>
              <td class="px-3 py-1 border">{{ r.consequents.join(', ') }}</td>
              <td class="px-3 py-1 border">{{ formatPercent(r.support) }}</td>
              <td class="px-3 py-1 border">{{ formatPercent(r.confidence) }}</td>
              <td class="px-3 py-1 border">{{ r.lift.toFixed(2) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  campanhaId: { type: Number, required: true },
  results: Array,
  descricao: String,
  empresaId: [String, Number]
})

const prodRulesRaw = ref([])
const attrRules = reactive({})
const produtosMap = ref({}) // ProdutoID -> NomeProduto

const colunaOrdenadaProd = ref('')
const ordemCrescenteProd = ref(false)
const limiteLinhasProd = ref(20)

const colunaOrdenadaAttr = reactive({})
const ordemCrescenteAttr = reactive({})
const limiteLinhasAttr = reactive({})

const formatPercent = v => (v * 100).toFixed(1)

function traduzir(lista) {
  return lista.map(id => produtosMap.value[id] || id)
}

const regrasOrdenadasProd = computed(() => {
  const col = colunaOrdenadaProd.value
  const asc = ordemCrescenteProd.value
  const regrasTraduzidas = prodRulesRaw.value.map(r => ({
    ...r,
    antecedents: traduzir(r.antecedents),
    consequents: traduzir(r.consequents)
  }))
  if (!col) return regrasTraduzidas.slice(0, limiteLinhasProd.value)
  return [...regrasTraduzidas]
    .sort((a, b) => (parseFloat(a[col]) || 0) - (parseFloat(b[col]) || 0) * (asc ? 1 : -1))
    .slice(0, limiteLinhasProd.value)
})

const regrasOrdenadasAttr = computed(() => {
  const out = {}
  for (const [key, lista] of Object.entries(attrRules)) {
    const col = colunaOrdenadaAttr[key]
    const asc = ordemCrescenteAttr[key]
    const lim = limiteLinhasAttr[key]
    out[key] = !col
      ? lista.slice(0, lim)
      : [...lista].sort((a, b) => (parseFloat(a[col]) || 0) - (parseFloat(b[col]) || 0) * (asc ? 1 : -1)).slice(0, lim)
  }
  return out
})

function ordenarPorProd(col) {
  if (colunaOrdenadaProd.value === col) {
    ordemCrescenteProd.value = !ordemCrescenteProd.value
  } else {
    colunaOrdenadaProd.value = col
    ordemCrescenteProd.value = true
  }
}

function ordenarPorAttr(attr, col) {
  if (colunaOrdenadaAttr[attr] === col) {
    ordemCrescenteAttr[attr] = !ordemCrescenteAttr[attr]
  } else {
    colunaOrdenadaAttr[attr] = col
    ordemCrescenteAttr[attr] = true
  }
}

async function carregarTudo() {
  try {
    // Regras Produto
    const pd = await axios.get(`/algoritmos/resultados_complementares/${props.campanhaId}?algoritmo=recommendation&tipo=produto`)
    prodRulesRaw.value = Array.isArray(pd.data) ? pd.data : []

    // Regras Atributos
    const ar = await axios.get(`/algoritmos/resultados_complementares/${props.campanhaId}?algoritmo=recommendation&tipo=atributos`)
    Object.assign(attrRules, ar.data)
    Object.keys(ar.data).forEach(key => {
      colunaOrdenadaAttr[key] = ''
      ordemCrescenteAttr[key] = false
      limiteLinhasAttr[key] = 10
    })

    // Preencher produtosMap a partir dos dados das regras de produto
    const nomeMap = {}
    for (const regra of prodRulesRaw.value) {
      const todosIDs = [...(regra.antecedents || []), ...(regra.consequents || [])]
      for (const id of todosIDs) {
        if (!(id in nomeMap) && typeof id === 'string') {
          const match = id.match(/^\[(\d+)\]\s*(.*)$/)
          if (match) {
            nomeMap[match[1]] = match[2]
          }
        }
      }
    }
    produtosMap.value = nomeMap

  } catch (e) {
    console.error('Erro ao carregar dados de recomendacao:', e)
  }
}

import * as XLSX from "xlsx"

function exportarParaExcel() {
  const ws = XLSX.utils.json_to_sheet(regrasOrdenadasProd.value.map(r => ({
    Antecedentes: r.antecedents.join(", "),
    Consequentes: r.consequents.join(", "),
    Suporte: (r.support * 100).toFixed(1) + "%",
    Confiança: (r.confidence * 100).toFixed(1) + "%",
    Lift: r.lift.toFixed(2)
  })))

  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, "Regras de Produto")
  XLSX.writeFile(wb, "regras_produto.xlsx")
}


onMounted(carregarTudo)
</script>



<style scoped>
.card-resultados {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: .5rem;
  padding: 1.5rem;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}
</style>
