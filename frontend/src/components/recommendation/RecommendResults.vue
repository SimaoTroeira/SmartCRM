<template>
  <div v-if="regras.length" class="space-y-6">
    <!-- Card: Tabela de Regras -->
    <div class="card-resultados mb-6">
      <div class="mb-2">
        <h3 class="text-2xl font-semibold text-blue-700">📦 Recomendações de Cross-Selling</h3>
        <p class="text-sm text-gray-600 mt-1">
          Identifica produtos frequentemente comprados juntos, com métricas de suporte, confiança e lift.
        </p>
      </div>

      <!-- Controles de Visualização -->
      <div class="flex items-center gap-4 mb-4">
        <label class="font-medium text-sm">Ordenar por:</label>
        <select v-model="colunaOrdenada" class="form-control border px-2 py-1 text-sm rounded w-40">
          <option value="">Nenhuma</option>
          <option value="support">Suporte</option>
          <option value="confidence">Confiança</option>
          <option value="lift">Lift</option>
        </select>
        <label class="font-medium text-sm">Limite linhas:</label>
        <input v-model.number="limiteLinhas" type="number" min="1" class="border rounded px-2 py-1 text-sm w-20" />
      </div>

      <!-- Tabela de Regras -->
      <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200 text-sm">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-4 py-2 border">Antecedentes</th>
              <th class="px-4 py-2 border">Consequentes</th>
              <th class="px-4 py-2 border cursor-pointer" @click="ordenarPor('support')">Suporte</th>
              <th class="px-4 py-2 border cursor-pointer" @click="ordenarPor('confidence')">Confiança</th>
              <th class="px-4 py-2 border cursor-pointer" @click="ordenarPor('lift')">Lift</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(regra, idx) in regrasOrdenadas" :key="idx" class="hover:bg-gray-50">
              <td class="px-4 py-2 border">{{ regra.antecedents.join(', ') }}</td>
              <td class="px-4 py-2 border">{{ regra.consequents.join(', ') }}</td>
              <td class="px-4 py-2 border">{{ formatPercent(regra.support) }}</td>
              <td class="px-4 py-2 border">{{ formatPercent(regra.confidence) }}</td>
              <td class="px-4 py-2 border">{{ regra.lift.toFixed(2) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Card: Aviso sem Regras -->
  </div>
  <div v-else class="text-gray-500 italic">Nenhuma regra de associação encontrada para esta campanha.</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  empresaId: { type: Number, required: true },
  campanhaId: { type: Number, required: true }
})

const regras = ref([])
const colunaOrdenada = ref('')
const ordemCrescente = ref(false)
const limiteLinhas = ref(20)

async function carregarRegras() {
  try {
    const url = `/algoritmos/resultados_complementares/${props.campanhaId}?algoritmo=recommendation&tipo=produto`
    console.log('[DEBUG] Requisição para:', url)

    const res = await axios.get(url)
    
    console.log('[DEBUG] Resposta da API:', res)
    console.log('[DEBUG] Dados da API:', res.data)

    if (!Array.isArray(res.data)) {
      console.warn('[WARNING] Dados recebidos não são um array!')
      regras.value = []
    } else {
      // Opcional: verificação dos dados individuais
      res.data.forEach((regra, idx) => {
        if (!Array.isArray(regra.antecedents) || !Array.isArray(regra.consequents)) {
          console.warn(`[WARNING] Regra ${idx} com antecedents ou consequents que não são arrays`, regra)
        }
      })
      regras.value = res.data
    }
  } catch (e) {
    console.error('[ERROR] Erro ao carregar regras:', e)
    regras.value = []
  }
}


onMounted(carregarRegras)

function ordenarPor(col) {
  if (colunaOrdenada.value === col) ordemCrescente.value = !ordemCrescente.value
  else {
    colunaOrdenada.value = col
    ordemCrescente.value = false
  }
}

const regrasOrdenadas = computed(() => {
  let arr = [...regras.value]
  if (colunaOrdenada.value) {
    arr.sort((a, b) => ordemCrescente.value
      ? a[colunaOrdenada.value] - b[colunaOrdenada.value]
      : b[colunaOrdenada.value] - a[colunaOrdenada.value]
    )
  }
  return arr.slice(0, limiteLinhas.value)
})

const formatPercent = v => `${(v * 100).toFixed(1)}%`
</script>

<style scoped>
.card-resultados { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.04); padding: 24px; }
.controles-tabela-clientes { display: flex; align-items: center; gap: 2rem; flex-wrap: wrap; }
.btn-reset-custom { background-color: white; border: 2px solid #2563eb; color: #2563eb; padding: 6px 16px; border-radius: 6px; cursor: pointer; }
.btn-reset-custom:hover { background-color: #e0ecff; }
</style>
