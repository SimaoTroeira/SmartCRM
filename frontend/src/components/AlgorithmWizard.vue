<template>
    <div v-if="!validado"> <!-- só aparece se houver erro -->
        <div class="mt-4">
            <h4 class="text-lg font-semibold mb-2">Verificação de dados</h4>

            <div v-if="carregando" class="text-blue-600 font-medium">
                A verificar os dados necessários...
            </div>

            <div v-else-if="validado !== null">
                <div v-if="ficheirosEmFalta.length" class="mb-3">
                    <p class="text-red-600 font-medium">
                        <span v-if="Object.keys(colunasEmFalta).length">Faltam tabelas e colunas:</span>
                        <span v-else>Faltam tabelas:</span>
                    </p>
                    <ul class="ml-4 list-disc text-red-600">
                        <li v-for="ficheiro in ficheirosEmFalta" :key="ficheiro">
                            ❌ {{ ficheiro }}
                        </li>
                    </ul>
                </div>

                <div v-if="Object.keys(colunasEmFalta).length && ficheirosEmFalta.length === 0" class="mb-3">
                    <p class="text-red-600 font-medium">Faltam colunas:</p>
                    <ul class="ml-4 list-disc text-red-600">
                        <li v-for="(colunas, ficheiro) in colunasEmFalta" :key="ficheiro">
                            {{ ficheiro }}:
                            <ul class="ml-4 list-disc">
                                <li v-for="grupo in colunas" :key="grupo.join('-')">
                                    ❌ {{ grupo.join(" / ") }}
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>

                <div v-if="!validado" class="text-red-600 font-medium">
                    Existem dados em falta. Corrija-os antes de continuar.
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
    campanhaId: Number,
    algoritmo: String
})

const emit = defineEmits(['valido'])

const ficheirosPresentes = ref({})
const validado = ref(null)
const ficheirosEmFalta = ref([])
const colunasEmFalta = ref({})
const carregando = ref(false)

const verificar = async () => {
    validado.value = null
    ficheirosEmFalta.value = []
    ficheirosPresentes.value = {}
    colunasEmFalta.value = {}
    carregando.value = true

    try {
        const res = await axios.get(
            `http://127.0.0.1:8000/api/algoritmos/verificar-colunas/${props.campanhaId}?algoritmo=${props.algoritmo}`
        )
        ficheirosPresentes.value = res.data.ficheiros_presentes || {}
        ficheirosEmFalta.value = res.data.ficheiros_em_falta || []
        colunasEmFalta.value = res.data.colunas_em_falta || {}

        const ok = ficheirosEmFalta.value.length === 0 && Object.keys(colunasEmFalta.value).length === 0
        validado.value = ok
        emit('valido', ok)
    } catch (err) {
        console.error('Erro ao verificar colunas:', err)
        validado.value = false
        emit('valido', false)
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