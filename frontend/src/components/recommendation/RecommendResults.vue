<template>
  <div class="space-y-8">
    <!-- Regras Produto → Produto -->
    <div v-if="regrasOrdenadasProd.length" class="card-resultados">
      <div class="cabecalho-clientes mb-4">
        <div class="titulo-reset">
          <h3 class="text-xl font-semibold text-blue-700">Cross-Selling (Produto→Produto)</h3>
          <button @click="resetarOrdenacaoProd" class="btn-reset-custom">Repor ordenação</button>
        </div>
        <div class="acoes-clientes">
          <button @click="exportarParaExcel" class="btn-exportar">Exportar para Excel</button>
        </div>
      </div>

      <div class="overflow-auto">
        <table class="min-w-full text-sm border">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-3 py-2 border">Antecedentes</th>
              <th class="px-3 py-2 border">Consequentes</th>
              <th class="px-3 py-2 border cursor-pointer" @click="ordenarPorProd('support')">
                Suporte (%)
                <span v-if="colunaOrdenadaProd === 'support'">
                  {{ ordemCrescenteProd ? '▲' : '▼' }}
                </span>
              </th>
              <th class="px-3 py-2 border cursor-pointer" @click="ordenarPorProd('confidence')">
                Confiança (%)
                <span v-if="colunaOrdenadaProd === 'confidence'">
                  {{ ordemCrescenteProd ? '▲' : '▼' }}
                </span>
              </th>
              <th class="px-3 py-2 border cursor-pointer" @click="ordenarPorProd('lift')">
                Lift
                <span v-if="colunaOrdenadaProd === 'lift'">
                  {{ ordemCrescenteProd ? '▲' : '▼' }}
                </span>
              </th>
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

    <!-- Regras por Atributo -->
    <div v-for="(regras, atributo) in regrasOrdenadasAttr" :key="atributo" class="card-resultados">
      <div class="cabecalho-clientes mb-4">
        <div class="titulo-reset">
          <h3 class="text-xl font-semibold text-blue-700">Recomendações via "{{ atributo }}"</h3>
          <button @click="resetarOrdenacaoAttr(atributo)" class="btn-reset-custom">Repor ordenação</button>
        </div>
        <div class="acoes-clientes">
          <button @click="exportarAtributoParaExcel(atributo, regras)" class="btn-exportar">Exportar para Excel</button>
        </div>
      </div>

      <div class="overflow-auto">
        <table class="min-w-full text-sm border">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-3 py-2 border">Antecedentes</th>
              <th class="px-3 py-2 border">Consequentes</th>
              <th class="px-3 py-2 border cursor-pointer" @click="ordenarPorAttr(atributo, 'support')">
                Suporte (%)
                <span v-if="colunaOrdenadaAttr[atributo] === 'support'">
                  {{ ordemCrescenteAttr[atributo] ? '▲' : '▼' }}
                </span>
              </th>
              <th class="px-3 py-2 border cursor-pointer" @click="ordenarPorAttr(atributo, 'confidence')">
                Confiança (%)
                <span v-if="colunaOrdenadaAttr[atributo] === 'confidence'">
                  {{ ordemCrescenteAttr[atributo] ? '▲' : '▼' }}
                </span>
              </th>
              <th class="px-3 py-2 border cursor-pointer" @click="ordenarPorAttr(atributo, 'lift')">
                Lift
                <span v-if="colunaOrdenadaAttr[atributo] === 'lift'">
                  {{ ordemCrescenteAttr[atributo] ? '▲' : '▼' }}
                </span>
              </th>
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
  empresaId: [String, Number],
  nomeEmpresa: String,
  nomeCampanha: String
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


function normalizarNome(nome) {
  return (nome || '')
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, '') // Remove acentos
    .replace(/[^a-zA-Z0-9]/g, '') // Remove tudo menos letras e números
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

  const empresa = normalizarNome(props.nomeEmpresa)
  const campanha = normalizarNome(props.nomeCampanha)
  const nomeFicheiro = `${empresa}_${campanha}_Recomendacao_Produtos.xlsx`

  XLSX.writeFile(wb, nomeFicheiro)
}
function exportarAtributoParaExcel(atributo, regras) {
  const ws = XLSX.utils.json_to_sheet(regras.map(r => ({
    Antecedentes: traduzir(r.antecedents).join(", "),
    Consequentes: traduzir(r.consequents).join(", "),
    Suporte: (r.support * 100).toFixed(1) + "%",
    Confiança: (r.confidence * 100).toFixed(1) + "%",
    Lift: r.lift.toFixed(2)
  })))

  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, `Regras - ${atributo}`)

  const empresa = normalizarNome(props.nomeEmpresa)
  const campanha = normalizarNome(props.nomeCampanha)
  const tipo = normalizarNome(atributo)
  const nomeFicheiro = `${empresa}_${campanha}_Recomendacao_${tipo}.xlsx`

  XLSX.writeFile(wb, nomeFicheiro)
}

function resetarOrdenacaoProd() {
  colunaOrdenadaProd.value = ''
  ordemCrescenteProd.value = false
}

function resetarOrdenacaoAttr(attr) {
  colunaOrdenadaAttr[attr] = ''
  ordemCrescenteAttr[attr] = false
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
  flex-wrap: wrap;
  gap: 1rem;
}

.titulo-reset {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  align-items: flex-start;
}

.acoes-clientes {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
  align-items: flex-start;
}
</style>
