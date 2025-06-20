<template>
  <div class="bg-white p-4 rounded-md shadow-sm border border-gray-200 mb-6 max-w-3xl">
    <div v-if="!carregando && validado === false">
      <h5 class="text-lg font-semibold mb-2">Dados em falta!</h5>
      <div v-if="expectativas && Object.keys(expectativas).length" class="mb-4">
        <h6 class="text-gray-700 font-medium">Tabelas e colunas esperadas:</h6>
        <ul class="ml-4 list-disc text-gray-600 text-sm">
          <li v-for="(colunas, tabela) in expectativas" :key="tabela">
            📄 <strong>{{ tabela.replace('.json', '') }}</strong>
            <ul class="ml-4 list-disc">
              <li v-for="grupo in colunas" :key="grupo.join('-')">
                {{ grupo.join(' / ') }}
              </li>
            </ul>
          </li>
        </ul>
      </div>



      <div v-if="ficheirosEmFalta.length || Object.keys(colunasEmFalta).length" class="mb-3">
        <p class="text-red-600 font-medium">
          Faltam tabelas e/ou colunas necessárias para executar o algoritmo:
        </p>

        <ul class="ml-4 list-disc text-red-600">
          <li v-for="ficheiro in ficheirosEmFalta" :key="ficheiro">
            ❌ Tabela em falta: {{ ficheiro }}
          </li>
        </ul>

        <div v-for="(colunas, ficheiro) in colunasEmFalta" :key="ficheiro" class="mb-2">
          <p class="text-red-600 font-medium">
            ❌ Colunas em falta na tabela <strong>{{ ficheiro }}</strong>:
          </p>
          <ul class="ml-6 list-disc text-red-600">
            <li v-for="grupo in colunas" :key="grupo.join('-')">
              {{ grupo.join(' / ') }}
            </li>
          </ul>
        </div>
      </div>


      <div class="text-red-600 font-medium">
        Garanta que as colunas e as tabelas têm os nomes como um dos indicados acima.
        Pode renomear as tabelas e colunas se necessário. Sem estes dados, o algoritmo não irá funcionar.
      </div>
    </div>

    <div v-if="carregando" class="text-blue-600 font-medium">
      A verificar os dados necessários...
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  campanhaId: Number,
  algoritmo: String,
  mostrarSoCard: Boolean
})


const emit = defineEmits(['valido', 'faltas'])

const ficheirosPresentes = ref({})
const validado = ref(null)
const ficheirosEmFalta = ref([])
const colunasEmFalta = ref({})
const carregando = ref(false)
const expectativas = ref({})


const verificar = async () => {
  validado.value = null
  ficheirosEmFalta.value = []
  ficheirosPresentes.value = {}
  colunasEmFalta.value = {}
  carregando.value = true



  try {
    const res = await axios.get(
      `/algoritmos/verificar_colunas/${props.campanhaId}?algoritmo=${props.algoritmo}`
    )
    ficheirosPresentes.value = res.data.ficheiros_presentes || {}
    ficheirosEmFalta.value = res.data.ficheiros_em_falta || []
    colunasEmFalta.value = res.data.colunas_em_falta || {}
    expectativas.value = res.data.expectativas || {}

    const ok = ficheirosEmFalta.value.length === 0 && Object.keys(colunasEmFalta.value).length === 0
    validado.value = ok
    emit('valido', ok)

    if (props.mostrarSoCard || !ok) {
      emit('faltas', {
        ficheiros: ficheirosEmFalta.value,
        colunas: colunasEmFalta.value,
        expectativas: expectativas.value
      })
    }

  } catch (err) {
    console.error('Erro ao verificar colunas:', err)
    validado.value = false
    emit('valido', false)
    emit('faltas', { ficheiros: [], colunas: {} })
  } finally {
    carregando.value = false
  }
}

watch(() => props.campanhaId, () => {
  if (props.campanhaId && props.algoritmo) {
    verificar()
  }
})

watch(() => props.algoritmo, () => {
  if (props.campanhaId && props.algoritmo) {
    verificar()
  }
})

onMounted(() => {
  if (props.campanhaId && props.algoritmo) {
    verificar()
  }
})
</script>

<style scoped>
ul li {
  font-size: 14px;
}
</style>
