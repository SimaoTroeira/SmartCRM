<template>
  <div class="space-y-8">
    <!-- Produto→Produto -->
    <div v-if="prodRules.length" class="card-resultados">
      <h3 class="text-xl font-semibold mb-2">
        📦 Cross-Selling (Produto→Produto)
      </h3>
      <ControlsTable :coluna-ordenada.sync="colunaOrdenadaProd" :ordem-crescente.sync="ordemCrescenteProd"
        :limite-linhas.sync="limiteLinhasProd" />
      <RulesTable :regras="regrasOrdenadasProd" :format-percent="formatPercent" />
    </div>
    <div v-else class="italic text-gray-500">
      Sem regras de produto–produto.
    </div>

    <!-- Atributos -->
    <div v-for="(lista, atributo) in attrRules" :key="atributo" class="card-resultados">
      <h3 class="text-xl font-semibold mb-2">
        🔖 Recomendações via “{{ atributo }}”
      </h3>
      <ControlsTable :coluna-ordenada.sync="colunaOrdenadaAttr[atributo]"
        :ordem-crescente.sync="ordemCrescenteAttr[atributo]" :limite-linhas.sync="limiteLinhasAttr[atributo]" />
      <RulesTable :regras="regrasOrdenadasAttr[atributo]" :format-percent="formatPercent" />
      <div v-if="!lista.length" class="italic text-gray-500 mt-2">
        Nenhuma regra encontrada para {{ atributo }}.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'

import ControlsTable from '@/components/recommendation/ControlsTable.vue'
import RulesTable from '@/components/recommendation/RulesTable.vue'

const props = defineProps({
  campanhaId: { type: Number, required: true }
})

const prodRules = ref([])
const attrRules = reactive({})

const colunaOrdenadaProd = ref('')
const ordemCrescenteProd = ref(false)
const limiteLinhasProd = ref(20)

const colunaOrdenadaAttr = reactive({})
const ordemCrescenteAttr = reactive({})
const limiteLinhasAttr = reactive({})

const formatPercent = v => `${(v * 100).toFixed(1)}%`

async function carregarTudo() {
  const pd = await axios.get(`/algoritmos/resultados_complementares/${props.campanhaId}?algoritmo=recommendation&tipo=produto`)
  prodRules.value = Array.isArray(pd.data) ? pd.data : []

  const ar = await axios.get(`/algoritmos/resultados_complementares/${props.campanhaId}?algoritmo=recommendation&tipo=atributos`)
  Object.assign(attrRules, ar.data)

  Object.keys(ar.data).forEach(key => {
    colunaOrdenadaAttr[key] = ''
    ordemCrescenteAttr[key] = false
    limiteLinhasAttr[key] = 10
  })
}

onMounted(carregarTudo)

const regrasOrdenadasProd = computed(() => {
  const col = colunaOrdenadaProd.value
  const ordem = ordemCrescenteProd.value

  if (!col) return prodRules.value.slice(0, limiteLinhasProd.value)

  return [...prodRules.value]
    .sort((a, b) => {
      const valA = typeof a[col] === 'string' ? parseFloat(a[col]) : a[col]
      const valB = typeof b[col] === 'string' ? parseFloat(b[col]) : b[col]
      return ordem ? valA - valB : valB - valA
    })
    .slice(0, limiteLinhasProd.value)
})


const regrasOrdenadasAttr = computed(() => {
  const resultado = {}

  for (const [atributo, lista] of Object.entries(attrRules)) {
    const col = colunaOrdenadaAttr[atributo]
    const ordem = ordemCrescenteAttr[atributo]
    const limite = limiteLinhasAttr[atributo]

    if (!col) {
      resultado[atributo] = lista.slice(0, limite)
      continue
    }

    resultado[atributo] = [...lista]
      .sort((a, b) => {
        const valA = typeof a[col] === 'string' ? parseFloat(a[col]) : a[col]
        const valB = typeof b[col] === 'string' ? parseFloat(b[col]) : b[col]
        return ordem ? valA - valB : valB - valA
      })
      .slice(0, limite)
  }

  return resultado
})


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
