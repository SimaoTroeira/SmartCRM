<template>
  <div class="space-y-8">
    <!-- Produto→Produto -->
    <div v-if="prodRules.length" class="card-resultados">
      <h3 class="text-xl font-semibold mb-2">
        📦 Cross-Selling (Produto→Produto)
      </h3>

      <!-- Controles + Exportar PDF -->
      <div class="flex flex-wrap items-center gap-4 mb-4">
        <!-- <ControlsTable
          :coluna-ordenada.sync="colunaOrdenadaProd"
          :ordem-crescente.sync="ordemCrescenteProd"
          :limite-linhas.sync="limiteLinhasProd"
        /> -->
        <ExportPdfRecommend :dados="regrasOrdenadasProd" nome="Regras Produto-Produto" />
      </div>

      <!-- Tabela -->
      <div class="overflow-auto">
        <table class="min-w-full text-sm border">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-3 py-2 border">Antecedentes</th>
              <th class="px-3 py-2 border">Consequentes</th>
              <th @click="ordenarPorProd('support')" class="cursor-pointer px-3 py-2 border select-none">Suporte (%) ❓</th>
              <th @click="ordenarPorProd('confidence')" class="cursor-pointer px-3 py-2 border select-none">Confiança (%) ❓</th>
              <th @click="ordenarPorProd('lift')" class="cursor-pointer px-3 py-2 border select-none">Lift ❓</th>
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
    </div>

    <!-- Atributos -->
    <div v-for="(lista, atributo) in attrRules" :key="atributo" class="card-resultados">
      <h3 class="text-xl font-semibold mb-2">
        🔖 Recomendações via "{{ atributo }}"
      </h3>

      <div class="flex flex-wrap items-center gap-4 mb-4">
        <!-- <ControlsTable
          :coluna-ordenada.sync="colunaOrdenadaAttr[atributo]"
          :ordem-crescente.sync="ordemCrescenteAttr[atributo]"
          :limite-linhas.sync="limiteLinhasAttr[atributo]"
        /> -->
        <ExportPdfRecommend :dados="regrasOrdenadasAttr[atributo]" :nome="`Regras por ${atributo}`" />
      </div>

      <div class="overflow-auto">
        <table class="min-w-full text-sm border">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-3 py-2 border">Antecedentes</th>
              <th class="px-3 py-2 border">Consequentes</th>
              <th @click="ordenarPorAttr(atributo,'support')" class="cursor-pointer px-3 py-2 border select-none">Suporte (%) ❓</th>
              <th @click="ordenarPorAttr(atributo,'confidence')" class="cursor-pointer px-3 py-2 border select-none">Confiança (%) ❓</th>
              <th @click="ordenarPorAttr(atributo,'lift')" class="cursor-pointer px-3 py-2 border select-none">Lift ❓</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(r, i) in regrasOrdenadasAttr[atributo]" :key="i" class="hover:bg-gray-50">
              <td class="px-3 py-1 border">{{ r.antecedents.join(', ') }}</td>
              <td class="px-3 py-1 border">{{ r.consequents.join(', ') }}</td>
              <td class="px-3 py-1 border">{{ formatPercent(r.support) }}</td>
              <td class="px-3 py-1 border">{{ formatPercent(r.confidence) }}</td>
              <td class="px-3 py-1 border">{{ r.lift.toFixed(2) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="!lista.length" class="italic text-gray-500 mt-2">
        Nenhuma regra encontrada para {{ atributo }}.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
// import ControlsTable from '@/components/recommendation/ControlsTable.vue'
import ExportPdfRecommend from '@/components/recommendation/ExportPdfRecommend.vue'

const props = defineProps({ campanhaId: { type: Number, required: true } })

const prodRules = ref([])
const attrRules = reactive({})

const colunaOrdenadaProd = ref('')
const ordemCrescenteProd = ref(false)
const limiteLinhasProd = ref(20)

const colunaOrdenadaAttr = reactive({})
const ordemCrescenteAttr = reactive({})
const limiteLinhasAttr = reactive({})

const formatPercent = v => (v * 100).toFixed(1)

async function carregarTudo() {
  try {
    const pd = await axios.get(`/api/algoritmos/resultados_complementares/${props.campanhaId}?algoritmo=recommendation&tipo=produto`)
    prodRules.value = Array.isArray(pd.data) ? pd.data : []

    const ar = await axios.get(`/api/algoritmos/resultados_complementares/${props.campanhaId}?algoritmo=recommendation&tipo=atributos`)
    Object.assign(attrRules, ar.data)
    Object.keys(ar.data).forEach(key => {
      colunaOrdenadaAttr[key] = ''
      ordemCrescenteAttr[key] = false
      limiteLinhasAttr[key] = 10
    })
  } catch (e) {
    console.error('Erro ao carregar dados de recomendacao:', e)
  }
}

onMounted(carregarTudo)

const regrasOrdenadasProd = computed(() => {
  const col = colunaOrdenadaProd.value
  const asc = ordemCrescenteProd.value
  if (!col) return prodRules.value.slice(0, limiteLinhasProd.value)
  return [...prodRules.value]
    .sort((a,b) => (parseFloat(a[col]) || 0) - (parseFloat(b[col]) || 0) * (asc ? 1 : -1))
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
      : [...lista].sort((a,b) => (parseFloat(a[col]) || 0) - (parseFloat(b[col]) || 0) * (asc ? 1 : -1)).slice(0, lim)
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
</script>

<style scoped>
.card-resultados {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: .5rem;
  padding: 1.5rem;
  box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}
</style>
