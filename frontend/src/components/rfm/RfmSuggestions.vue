<template>
  <div class="card-resultados">
    <h3 class="text-xl font-semibold mb-3 text-blue-700">Sugestões de Ação</h3>
    <p class="text-sm text-gray-600 mb-4">
      Recomendações de campanhas com base nos segmentos identificados pela análise RFM. Estas sugestões visam melhorar o relacionamento com os clientes e maximizar o seu valor ao longo do tempo.
    </p>

    <div v-for="(sugestao, index) in sugestoesFiltradas" :key="index" class="mb-6">
      <h4 class="text-md font-semibold text-gray-800 mb-1">{{ sugestao.emoji }} {{ sugestao.segmento }}</h4>
      <ul class="list-disc list-inside text-sm text-gray-700">
        <li v-for="(ponto, i) in sugestao.pontos" :key="i">{{ ponto }}</li>
      </ul>
    </div>
  </div>
</template>


<script setup>
import { computed } from 'vue'

const props = defineProps({
  clientesSegmentados: Array
})

const sugestoesFiltradas = computed(() => {
  const clientes = props.clientesSegmentados || []

  const segmentos = [
    "Campeões",
    "Clientes Valiosos",
    "Clientes Regulares",
    "Em Risco",
    "Clientes Perdidos",
    "Pouca Frequência",
    "Baixo Valor",
    "Inativos"
  ]

  const contagem = Object.fromEntries(
    segmentos.map(seg => [seg, clientes.filter(c => c.Segmento === seg).length])
  )

  const sugestoes = []

  if (contagem["Campeões"]) {
    sugestoes.push({
      segmento: 'Campeões',
      emoji: '🏆',
      pontos: [
        `${contagem["Campeões"]} clientes fazem parte dos melhores da sua base de dados.`,
        'Campanha recomendada: Campanha de Fidelização.',
        'Recompense com acesso exclusivo a novos produtos, eventos privados ou brindes de valor.',
        'Fortaleça o vínculo com comunicações personalizadas e reconhecimento especial.'
      ]
    })
  }

  if (contagem["Clientes Valiosos"]) {
    sugestoes.push({
      segmento: 'Clientes Valiosos',
      emoji: '💎',
      pontos: [
        `${contagem["Clientes Valiosos"]} clientes com histórico de valor significativo.`,
        'Campanha recomendada: Campanha de Manutenção Premium.',
        'Ofereça vantagens adicionais como atendimento preferencial ou condições exclusivas.',
        'Incentive com convites para programas de fidelização ou iniciativas de embaixadores.'
      ]
    })
  }

  if (contagem["Clientes Regulares"]) {
    sugestoes.push({
      segmento: 'Clientes Regulares',
      emoji: '🔁',
      pontos: [
        `${contagem["Clientes Regulares"]} clientes compram de forma consistente.`,
        'Campanha recomendada: Campanha de Recompensa por Frequência.',
        'Implemente campanhas como “compre 3, receba 1 grátis”.',
        'Aumente o envolvimento com sugestões de produtos personalizadas.'
      ]
    })
  }

  if (contagem["Em Risco"]) {
    sugestoes.push({
      segmento: 'Em Risco',
      emoji: '⚠️',
      pontos: [
        `${contagem["Em Risco"]} clientes demonstram sinais de afastamento.`,
        'Campanha recomendada: Campanha de Reativação.',
        'Ofereça benefícios limitados no tempo e use frases como “sentimos a sua falta”.',
        'Realce o valor e diferenciação da sua marca.'
      ]
    })
  }

  if (contagem["Clientes Perdidos"]) {
    sugestoes.push({
      segmento: 'Clientes Perdidos',
      emoji: '❌',
      pontos: [
        `${contagem["Clientes Perdidos"]} clientes deixaram de comprar há bastante tempo.`,
        'Campanha recomendada: Campanha de Última Tentativa.',
        'Apresente uma proposta irrepetível ou um incentivo forte para recuperar o cliente.',
        'Utilize linguagem emocional e directa, apelando à nostalgia ou ligação anterior.'
      ]
    })
  }

  if (contagem["Pouca Frequência"]) {
    sugestoes.push({
      segmento: 'Pouca Frequência',
      emoji: '📉',
      pontos: [
        `${contagem["Pouca Frequência"]} clientes compram de forma esporádica.`,
        'Campanha recomendada: Campanha de Estímulo à Repetição.',
        'Implemente incentivos progressivos (“Na 2.ª compra, -15%; na 3.ª, -25%”).',
        'Recomende produtos relacionados ou packs promocionais.'
      ]
    })
  }

  if (contagem["Baixo Valor"]) {
    sugestoes.push({
      segmento: 'Baixo Valor',
      emoji: '🪙',
      pontos: [
        `${contagem["Baixo Valor"]} clientes têm um volume de compras reduzido.`,
        'Campanha recomendada: Campanha de Optimização de Custos.',
        'Evite custos elevados – use emails automatizados, SMS curtos ou notificações.',
        'Experimente campanhas de upselling com sugestões de produtos complementares.'
      ]
    })
  }

  if (contagem["Inativos"]) {
    sugestoes.push({
      segmento: 'Inativos',
      emoji: '🛑',
      pontos: [
        `${contagem["Inativos"]} clientes não demonstram actividade há bastante tempo.`,
        'Campanha recomendada: Campanha de Reengajamento Total.',
        'Apresente um último apelo com benefício exclusivo e data limite para acção.',
        'Se possível, combine com questionário para perceber razões do afastamento.'
      ]
    })
  }

  return sugestoes
})
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
