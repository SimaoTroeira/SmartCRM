<template>
  <div>
    <button class="btn-exportar-pdf" @click="abrirModal">
      Exportar PDF
    </button>

    <dialog ref="dialogRef" class="modal-exportar">
      <div class="modal-conteudo">
        <button class="fechar" @click="fecharModal">✖</button>
        <h2 class="titulo-modal">Exportar Relatório PDF</h2>

        <div class="mt-4 space-y-2">
          <div class="text-sm font-medium">Selecionar gráficos para exportar:</div>
          <div class="flex flex-col gap-1">
            <label><input type="checkbox" v-model="graficosSelecionados" value="pizza" /> Gráfico Pizza</label>
            <label><input type="checkbox" v-model="graficosSelecionados" value="barras" /> Gráfico de Barras</label>
            <label><input type="checkbox" v-model="graficosSelecionados" value="mapa" /> Mapa de Portugal</label>
          </div>

          <div class="mt-2">
            <label>
              <input type="checkbox" v-model="incluirSugestoes" />
              Incluir sugestões de ação
            </label>
          </div>

          <button class="btn-confirmar" @click="gerarPdf" :disabled="aGerar">
            <span v-if="!aGerar">Gerar Relatório</span>
            <span v-else>A gerar PDF...</span>
          </button>
        </div>
      </div>
    </dialog>

    <!-- Conteúdo invisível -->
    <div style="position: absolute; top: -9999px; left: -9999px; width: 1000px;">
      <div v-show="graficosSelecionados.includes('pizza')">
        <div ref="pizzaRef">
          <h4>Distribuição de Risco</h4>
          <PieChart :data="dadosPizza" />
        </div>
      </div>
      <div v-show="graficosSelecionados.includes('barras')">
        <div ref="barrasRef">
          <h4>Risco por Região</h4>
          <BarChart :data="dadosBarras" :x-key="'Regiao'" :y-keys="['Alto Risco', 'Médio Risco', 'Baixo Risco']" />
        </div>
      </div>
      <!-- Mapa: renderizado dentro do viewport com opacity 0 -->
      <div v-show="graficosSelecionados.includes('mapa')"
        style="position: fixed; top: 0; left: 0; opacity: 0; pointer-events: none; z-index: -1;">
        <div ref="mapaRef" style="width: 1000px; height: 600px;">
          <h4>Risco no Mapa</h4>

          <!-- Legenda manual antes do mapa -->
          <div style="display: flex; gap: 10px; margin-bottom: 10px; font-size: 12px;">
            <div><span
                style="background-color: #f87171; width: 12px; height: 12px; display: inline-block; margin-right: 4px;"></span>
              Alto Risco</div>
            <div><span
                style="background-color: #facc15; width: 12px; height: 12px; display: inline-block; margin-right: 4px;"></span>
              Médio Risco</div>
            <div><span
                style="background-color: #4ade80; width: 12px; height: 12px; display: inline-block; margin-right: 4px;"></span>
              Baixo Risco</div>
          </div>

          <PortugalMapChurn :dados-regioes="dadosBarras" />
        </div>
      </div>

      <!-- Sugestões invisíveis mas renderizadas corretamente -->
      <div style="position: absolute; top: -9999px; left: -9999px; width: 1000px;">
        <ChurnSuggestions ref="churnSuggestionsRef" :clientes="clientes" :nomeEmpresa="nomeEmpresa"
          :nomeCampanha="nomeCampanha" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import jsPDF from 'jspdf'
import html2canvas from 'html2canvas'
import PieChart from './PieChart.vue'
import BarChart from './BarChart.vue'
import PortugalMapChurn from './PortugalMapChurn.vue'
import ChurnSuggestions from './ChurnSuggestions.vue'

const props = defineProps({
  nomeEmpresa: String,
  nomeCampanha: String,
  dadosPizza: Array,
  dadosBarras: Array,
  clientes: Array,
  refMapa: Object
})

const churnSuggestionsRef = ref(null)
const dialogRef = ref(null)
const graficosSelecionados = ref([])
const incluirSugestoes = ref(true)
const aGerar = ref(false)

const pizzaRef = ref(null)
const barrasRef = ref(null)
const mapaRef = ref(null)
const sugestoesRef = ref(null)

const refObjs = {
  pizza: pizzaRef,
  barras: barrasRef,
  mapa: mapaRef,
  sugestoes: sugestoesRef
}

function abrirModal() {
  dialogRef.value?.showModal()
}

function fecharModal() {
  dialogRef.value?.close()
}

function obterDescricao(grafico) {
  if (grafico === 'pizza') {
    return 'Este gráfico mostra a proporção de clientes em cada categoria de risco de churn (Alto, Médio ou Baixo). É útil para compreender rapidamente a saúde geral da base de clientes e identificar se há uma concentração perigosa de clientes em risco de abandono.'
  } else if (grafico === 'barras') {
    return 'Este gráfico apresenta o número de clientes por região, segmentados de acordo com o seu nível de risco de churn. Permite identificar áreas geográficas com maior concentração de clientes em risco, ajudando na definição de estratégias regionais de retenção.'
  } else if (grafico === 'mapa') {
    return 'Este mapa destaca visualmente a distribuição de risco de cancelamento por região em Portugal. Permite identificar geograficamente as áreas mais afetadas e apoiar estratégias localizadas.'
  }
  return ''
}

async function gerarPdf() {
  if (!graficosSelecionados.value.length) return
  aGerar.value = true

  const doc = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' })
  let y = 40

  const normalizar = (str) => str.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-zA-Z0-9]/g, '')
  const nomeFicheiro = `${normalizar(props.nomeEmpresa)}_${normalizar(props.nomeCampanha)}_Churn_Relatorio.pdf`

  doc.setFontSize(18)
  doc.setFont('Helvetica', 'bold')
  doc.text(props.nomeEmpresa, 40, y)
  y += 24
  doc.setFontSize(14)
  doc.setFont('Helvetica', 'normal')
  doc.text(props.nomeCampanha, 40, y)
  y += 24

  doc.setFontSize(12)
  doc.setFont('Helvetica', 'italic')
  doc.text('Algoritmo: Predição de Churn (Risco de Cancelamento)', 40, y)
  y += 40

  await nextTick()
  await new Promise(r => setTimeout(r, 1000))

  for (const grafico of graficosSelecionados.value) {
    const refElemento = refObjs[grafico]
    const wrapper = refElemento?.value?.$el || refElemento?.value
    if (!wrapper) continue

    const titulo = wrapper.querySelector('h4')?.innerText || ''
    const descricao = obterDescricao(grafico)

    if (y + 100 > 750) {
      doc.addPage()
      y = 40
    }

    doc.setFontSize(16)
    doc.setFont('Helvetica', 'bold')
    doc.text(titulo, 40, y)
    y += 20

    doc.setFontSize(12)
    doc.setFont('Helvetica', 'normal')
    const linhas = doc.splitTextToSize(descricao, 500)
    doc.text(linhas, 40, y)
    y += linhas.length * 16 + 10

    let imagemCapturada
    try {
      const canvas = wrapper.querySelector('canvas')
      if (canvas) {
        imagemCapturada = await html2canvas(canvas, { scale: 2 })
      } else {
        imagemCapturada = await html2canvas(wrapper, { scale: 2 })
      }
    } catch (e) {
      console.error(`Erro ao capturar ${grafico}:`, e)
      continue
    }

    if (imagemCapturada) {
      const imgData = imagemCapturada.toDataURL('image/png')
      const imgWidth = 500
      const imgHeight = (imagemCapturada.height * imgWidth) / imagemCapturada.width

      if (y + imgHeight > 750) {
        doc.addPage()
        y = 40
      }

      doc.addImage(imgData, 'PNG', 40, y, imgWidth, imgHeight)

      if (grafico === 'mapa') {
        // Legenda sobre a imagem do mapa
        const legendaX = 50
        let legendaY = y + 20
        const legendaItens = [
          { cor: '#f87171', texto: 'Alto Risco' },
          { cor: '#facc15', texto: 'Médio Risco' },
          { cor: '#4ade80', texto: 'Baixo Risco' }
        ]
        for (const item of legendaItens) {
          doc.setFillColor(item.cor)
          doc.rect(legendaX, legendaY, 10, 10, 'F')
          doc.setTextColor(0)
          doc.setFontSize(10)
          doc.text(item.texto, legendaX + 15, legendaY + 9)
          legendaY += 16
        }
      }

      y += imgHeight + 30
    }
  }

  if (incluirSugestoes.value && churnSuggestionsRef.value?.sugestoes?.length) {
    const sugestoes = churnSuggestionsRef.value.sugestoes

    if (y + 200 > 750) {
      doc.addPage()
      y = 40
    }

    doc.setFontSize(16)
    doc.setFont('Helvetica', 'bold')
    doc.text('Sugestões de Ação', 40, y)
    y += 20

    doc.setFontSize(12)
    doc.setFont('Helvetica', 'normal')

    for (const { classificacao, pontos } of sugestoes) {
      if (y + 80 > 750) {
        doc.addPage()
        y = 40
      }

      doc.setFont('Helvetica', 'bold')
      doc.text(classificacao, 40, y)
      y += 16

      doc.setFont('Helvetica', 'normal')
      for (const ponto of pontos) {
        const linhas = doc.splitTextToSize(`• ${ponto}`, 500)
        doc.text(linhas, 50, y)
        y += linhas.length * 14 + 4
      }

      y += 10
    }
  }

  doc.save(nomeFicheiro)
  aGerar.value = false
}
</script>

<style scoped>
.btn-exportar-pdf {
  background-color: #10b981;
  color: white;
  padding: 6px 16px;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.875rem;
  cursor: pointer;
  border: none;
}

.modal-exportar {
  padding: 0;
  border: none;
  border-radius: 12px;
  width: 500px;
  max-width: 90vw;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-conteudo {
  padding: 24px;
  position: relative;
  background: white;
}

.fechar {
  position: absolute;
  top: 10px;
  right: 14px;
  font-size: 18px;
  border: none;
  background: none;
  cursor: pointer;
}

.titulo-modal {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 16px;
}

.btn-confirmar {
  background-color: #2563eb;
  color: white;
  padding: 6px 16px;
  border-radius: 6px;
  font-weight: 500;
  margin-top: 16px;
  border: none;
  cursor: pointer;
}

.btn-confirmar:hover {
  background-color: #1d4ed8;
}
</style>
