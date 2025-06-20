<template>
  <div class="card-resultados relative">
    <div data-sugestoes>
      <h3 class="text-xl font-semibold mb-3 text-blue-700">Sugestões de Ação</h3>
      <p class="text-sm text-gray-600 mb-4">
        Recomendações práticas baseadas nos níveis de risco de churn, com o objetivo de melhorar a retenção de clientes e evitar perdas comerciais significativas.
      </p>

      <div v-for="(sugestao, index) in sugestoes" :key="index" class="mb-6">
        <h4 class="text-md font-semibold text-gray-800 mb-1">{{ sugestao.classificacao }}</h4>
        <ul class="list-disc list-inside text-sm text-gray-700">
          <li v-for="(ponto, i) in sugestao.pontos" :key="i">{{ ponto }}</li>
        </ul>
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref, computed, watchEffect, defineExpose } from 'vue'

const props = defineProps({
  clientes: Array,
  nomeEmpresa: String,
  nomeCampanha: String
})

const sugestoes = ref([])

watchEffect(() => {
  const clientes = props.clientes || []

  const alto = clientes.filter(c => c.Classificacao === 'Alto Risco').length
  const medio = clientes.filter(c => c.Classificacao === 'Médio Risco').length
  const baixo = clientes.filter(c => c.Classificacao === 'Baixo Risco').length

  sugestoes.value = [
    {
      classificacao: 'Alto Risco',
      pontos: [
        `${alto} clientes encontram-se numa situação crítica, com elevada probabilidade de abandono.`,
        'Implemente campanhas urgentes com ofertas altamente apelativas e com prazo limitado.',
        'Considere aplicar descontos significativos como incentivo ao retorno.',
        'Estabeleça contacto directo, preferencialmente personalizado, via telefone ou email.'
      ]
    },
    {
      classificacao: 'Médio Risco',
      pontos: [
        `${medio} clientes demonstram sinais de possível desinteresse ou afastamento iminente.`,
        'Mantenha o envolvimento através de comunicações com valor claro, como dicas úteis ou recomendações personalizadas.',
        'Utilize lembretes regulares para reforçar a presença da marca.',
        'Ofereça benefícios acumulativos (ex: pontos, selos ou brindes por frequência).'
      ]
    },
    {
      classificacao: 'Baixo Risco',
      pontos: [
        `${baixo} clientes apresentam baixo risco de churn e demonstram fidelização.`,
        'Reforce a lealdade através de programas de pontos, campanhas VIP ou acesso antecipado a novidades.',
        'Envie newsletters regulares com conteúdos relevantes e exclusivos.',
        'Mostre reconhecimento com mensagens de agradecimento e pequenos gestos como brindes ou vouchers.'
      ]
    }
  ]
})

defineExpose({ sugestoes })
</script>


<style scoped>
.card-resultados {
  background-color: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
  padding: 24px;
}


</style>
