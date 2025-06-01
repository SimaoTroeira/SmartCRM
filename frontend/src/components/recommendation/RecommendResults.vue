<template>
  <div class="space-y-8">
    <!-- Produto→Produto -->
    <div v-if="regrasProduto.length" class="card-resultados">
      <h3 class="text-xl font-semibold mb-2">
        📦 Cross-Selling (Produto→Produto)
      </h3>

      <!-- Controlo -->
      <div class="flex items-center gap-4 mb-3 flex-wrap">
        <div class="flex items-center gap-2">
          <label class="text-sm font-medium">Ordenar por:</label>
          <select v-model="colunaOrdenadaProduto" class="border px-2 py-1 rounded text-sm">
            <option value="">Nenhuma</option>
            <option value="support">Suporte (%)</option>
            <option value="confidence">Confiança (%)</option>
            <option value="lift">Lift</option>
          </select>

          <button
            class="text-sm underline"
            @click="ordemCrescenteProduto = !ordemCrescenteProduto"
            title="Alternar entre ordem ascendente e descendente"
          >
            {{ ordemCrescenteProduto ? '↑ asc' : '↓ desc' }}
          </button>
        </div>

        <div class="flex items-center gap-2">
          <label class="text-sm font-medium">Limite:</label>
          <input
            type="number"
            min="1"
            v-model.number="limiteProduto"
            class="border px-2 py-1 rounded text-sm w-16"
          />
        </div>
      </div>

      <!-- Tabela -->
      <div class="overflow-auto">
        <table class="min-w-full text-sm border">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-3 py-2 border">Antecedentes</th>
              <th class="px-3 py-2 border">Consequentes</th>
              <th class="px-3 py-2 border cursor-pointer" @click="ordenarPorProduto('support')">Suporte (%) ❔</th>
              <th class="px-3 py-2 border cursor-pointer" @click="ordenarPorProduto('confidence')">Confiança (%) ❔</th>
              <th class="px-3 py-2 border cursor-pointer" @click="ordenarPorProduto('lift')">Lift ❔</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(r, i) in regrasOrdenadasProduto" :key="i" class="hover:bg-gray-50">
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
    <div v-else class="italic text-gray-500">Sem regras de produto–produto.</div>

    <!-- Recomendações por Atributo -->
    <div v-for="(regras, atributo) in regrasAtributos" :key="atributo" class="card-resultados">
      <h3 class="text-xl font-semibold mb-2">
        🔖 Recomendações via "{{ atributo }}"
      </h3>

      <!-- Controlo -->
      <div class="flex items-center gap-4 mb-3 flex-wrap">
        <div class="flex items-center gap-2">
          <label class="text-sm font-medium">Ordenar por:</label>
          <select v-model="colunaOrdenadaAtributos[atributo]" class="border px-2 py-1 rounded text-sm">
            <option value="">Nenhuma</option>
            <option value="support">Suporte (%)</option>
            <option value="confidence">Confiança (%)</option>
            <option value="lift">Lift</option>
          </select>

          <button
            class="text-sm underline"
            @click="ordemCrescenteAtributos[atributo] = !ordemCrescenteAtributos[atributo]"
            title="Alternar entre ordem ascendente e descendente"
          >
            {{ ordemCrescenteAtributos[atributo] ? '↑ asc' : '↓ desc' }}
          </button>
        </div>

        <div class="flex items-center gap-2">
          <label class="text-sm font-medium">Limite:</label>
          <input
            type="number"
            min="1"
            v-model.number="limiteAtributos[atributo]"
            class="border px-2 py-1 rounded text-sm w-16"
          />
        </div>
      </div>

      <!-- Tabela -->
      <div class="overflow-auto">
        <table class="min-w-full text-sm border">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-3 py-2 border">Antecedentes</th>
              <th class="px-3 py-2 border">Consequentes</th>
              <th class="px-3 py-2 border cursor-pointer" @click="ordenarPorAtributo(atributo, 'support')">Suporte (%) ❔</th>
              <th class="px-3 py-2 border cursor-pointer" @click="ordenarPorAtributo(atributo, 'confidence')">Confiança (%) ❔</th>
              <th class="px-3 py-2 border cursor-pointer" @click="ordenarPorAtributo(atributo, 'lift')">Lift ❔</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(r, i) in regrasOrdenadasAtributos[atributo]" :key="i" class="hover:bg-gray-50">
              <td class="px-3 py-1 border">{{ r.antecedents.join(', ') }}</td>
              <td class="px-3 py-1 border">{{ r.consequents.join(', ') }}</td>
              <td class="px-3 py-1 border">{{ formatPercent(r.support) }}</td>
              <td class="px-3 py-1 border">{{ formatPercent(r.confidence) }}</td>
              <td class="px-3 py-1 border">{{ r.lift.toFixed(2) }}</td>
            </tr>
          </tbody>
        </table>

        <div v-if="!regras.length" class="italic text-gray-500 mt-2">
          Nenhuma regra encontrada para {{ atributo }}.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({ campanhaId: Number })

const regrasProduto = ref([])
const regrasAtributos = reactive({})
const colunaOrdenadaProduto = ref('')
const ordemCrescenteProduto = ref(false)
const limiteProduto = ref(20)
const colunaOrdenadaAtributos = reactive({})
const ordemCrescenteAtributos = reactive({})
const limiteAtributos = reactive({})

const formatPercent = v => (v * 100).toFixed(1)

async function carregarTudo() {
  try {
    const pd = await axios.get(`/algoritmos/resultados_complementares/${props.campanhaId}?algoritmo=recommendation&tipo=produto`)
    regrasProduto.value = Array.isArray(pd.data) ? pd.data : []

    const ar = await axios.get(`/algoritmos/resultados_complementares/${props.campanhaId}?algoritmo=recommendation&tipo=atributos`)
    Object.assign(regrasAtributos, ar.data)

    for (const key of Object.keys(ar.data)) {
      colunaOrdenadaAtributos[key] = ''
      ordemCrescenteAtributos[key] = false
      limiteAtributos[key] = 10
    }
  } catch (err) {
    console.error('Erro ao carregar dados de recomendacao:', err)
  }
}

onMounted(carregarTudo)

const regrasOrdenadasProduto = computed(() => {
  const col = colunaOrdenadaProduto.value
  const asc = ordemCrescenteProduto.value
  if (!col) return regrasProduto.value.slice(0, limiteProduto.value)
  return [...regrasProduto.value]
    .sort((a, b) => (asc ? a[col] - b[col] : b[col] - a[col]))
    .slice(0, limiteProduto.value)
})

const regrasOrdenadasAtributos = computed(() => {
  const out = {}
  for (const [atributo, lista] of Object.entries(regrasAtributos)) {
    const col = colunaOrdenadaAtributos[atributo]
    const asc = ordemCrescenteAtributos[atributo]
    const lim = limiteAtributos[atributo]
    out[atributo] = !col
      ? lista.slice(0, lim)
      : [...lista].sort((a, b) => (asc ? a[col] - b[col] : b[col] - a[col])).slice(0, lim)
  }
  return out
})

function ordenarPorProduto(col) {
  if (colunaOrdenadaProduto.value === col) {
    ordemCrescenteProduto.value = !ordemCrescenteProduto.value
  } else {
    colunaOrdenadaProduto.value = col
    ordemCrescenteProduto.value = true
  }
}

function ordenarPorAtributo(attr, col) {
  if (colunaOrdenadaAtributos[attr] === col) {
    ordemCrescenteAtributos[attr] = !ordemCrescenteAtributos[attr]
  } else {
    colunaOrdenadaAtributos[attr] = col
    ordemCrescenteAtributos[attr] = true
  }
}
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
