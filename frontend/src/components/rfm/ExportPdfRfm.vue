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
            <label><input type="checkbox" v-model="graficosSelecionados" value="pca" /> Gráfico PCA</label>
            <label><input type="checkbox" v-model="graficosSelecionados" value="normal" /> Gráfico Normal</label>
            <label><input type="checkbox" v-model="graficosSelecionados" value="radar" /> Gráfico Radar</label>
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
      <div v-if="graficosSelecionados.includes('pca')">
        <div ref="pcaRef">
          <h4>{{ descricoesGraficos.pca.titulo }}</h4>
          <p>{{ descricoesGraficos.pca.descricao }}</p>
          <ScatterPlotPCA :scatter-clientes="scatterClientes" />
        </div>
      </div>
      <div v-if="graficosSelecionados.includes('normal')">
        <div ref="normalRef">
          <h4>{{ descricoesGraficos.normal.titulo }}</h4>
          <p>{{ descricoesGraficos.normal.descricao }}</p>
          <ScatterPlot :scatter-clientes="scatterClientes" :scatter-regioes="scatterRegioes" />
        </div>
      </div>
      <div v-if="graficosSelecionados.includes('radar')">
        <div ref="radarRef">
          <h4>{{ descricoesGraficos.radar.titulo }}</h4>
          <p>{{ descricoesGraficos.radar.descricao }}</p>
          <RadarPlot :scatter-clientes="scatterClientes" />
        </div>
      </div>
      <div v-if="graficosSelecionados.includes('barras')">
        <div ref="barrasRef">
          <h4>{{ descricoesGraficos.barras.titulo }}</h4>
          <p>{{ descricoesGraficos.barras.descricao }}</p>
          <RegioesBarChart :scatter-regioes="scatterRegioes" />
        </div>
      </div>
      <div v-if="graficosSelecionados.includes('mapa')">
        <div ref="mapaRef">
          <h4>{{ descricoesGraficos.mapa.titulo }}</h4>
          <p>{{ descricoesGraficos.mapa.descricao }}</p>
          <div style="display: inline-block; padding: 10px; border: 1px solid #ccc;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; font-size: 12px;">
              <div v-for="(cor, segmento) in coresSegmentos" :key="segmento">
                <span
                  :style="{ backgroundColor: cor, width: '12px', height: '12px', display: 'inline-block', marginRight: '4px', borderRadius: '2px' }"></span>
                {{ segmento }}
              </div>
            </div>
            <PortugalMap :dados-regioes="scatterRegioes" />
          </div>
        </div>
      </div>
    </div>

    <RfmSuggestions ref="sugestoesRef" :clientes-segmentados="props.clientesSegmentados" style="display: none;" />
  </div>
</template>


<script setup>
import { ref, nextTick, computed } from 'vue'
import jsPDF from 'jspdf'
import html2canvas from 'html2canvas'
import ScatterPlot from './ScatterPlot.vue'
import ScatterPlotPCA from './ScatterPlotPCA.vue'
import RadarPlot from './RadarPlot.vue'
import RegioesBarChart from './RegioesBarChart.vue'
import PortugalMap from './PortugalMap.vue'
import RfmSuggestions from './RfmSuggestions.vue'

const dialogRef = ref(null)
const graficosSelecionados = ref([])
const aGerar = ref(false)
const incluirSugestoes = ref(true)
const sugestoesRef = ref(null)

const props = defineProps({
  nomeEmpresa: String,
  nomeCampanha: String,
  scatterClientes: Array,
  scatterRegioes: Array,
  clientesSegmentados: Array
})


const segmentosPresentes = computed(() => {
  const clientes = props.clientesSegmentados || []
  const unicos = new Set(clientes.map(c => c.Segmento))
  return [...unicos].filter(s => !!coresSegmentos[s])
})

function abrirModal() {
  dialogRef.value?.showModal()
}

function fecharModal() {
  dialogRef.value?.close()
}

const descricoesGraficos = {
  pca: {
    titulo: 'Gráfico PCA',
    descricao: 'Este gráfico aplica Análise de Componentes Principais (PCA).'
  },
  normal: {
    titulo: 'Gráfico Normal',
    descricao: 'Gráfico de dispersão com base em métricas RFM originais...'
  },
  radar: {
    titulo: 'Gráfico Radar',
    descricao: 'Compara visualmente os diferentes clusters com base nos três pilares do RFM.'
  },
  barras: {
    titulo: 'Gráfico de Barras',
    descricao: 'Mostra a distribuição dos clientes por região e por segmento/cluster.'
  },
  mapa: {
    titulo: 'Mapa de Portugal',
    descricao: 'Representação geográfica dos clusters por distrito ou região.'
  }
}

const pcaRef = ref(null)
const normalRef = ref(null)
const radarRef = ref(null)
const barrasRef = ref(null)
const mapaRef = ref(null)

const refObjs = {
  pca: pcaRef,
  normal: normalRef,
  radar: radarRef,
  barras: barrasRef,
  mapa: mapaRef
}

function normalizarNome(nome) {
  if (!nome) return 'desconhecido'
  return nome
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/\u00e7/g, 'c')
    .replace(/[^a-zA-Z0-9]/g, '')
}

async function gerarPdf() {
  if (!graficosSelecionados.value.length) return
  aGerar.value = true

  const doc = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' })
  let y = 40

  const nomeEmpresaLimpo = normalizarNome(props.nomeEmpresa)
  const nomeCampanhaLimpo = normalizarNome(props.nomeCampanha)
  const nomeFicheiro = `${nomeEmpresaLimpo}_${nomeCampanhaLimpo}_Rfm_Relatorio.pdf`

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
  doc.text('Algoritmo: Análise RFM', 40, y)
  y += 40

  await nextTick()

  for (const grafico of graficosSelecionados.value) {
    const refElemento = refObjs[grafico]
    const wrapper = refElemento?.value?.$el || refElemento?.value
    if (!wrapper) continue

    await new Promise(resolve => setTimeout(resolve, 800))
    const canvasImagem = await html2canvas(wrapper, {
      scale: 2,
      useCORS: true
    })
    const imgData = canvasImagem.toDataURL('image/png')

    // 🔧 Ajuste de tamanho personalizado para o mapa
    const imgWidth = grafico === 'mapa' ? 520 : 500
    const imgHeight = (canvasImagem.height * imgWidth) / canvasImagem.width

    if (y + imgHeight > 750) {
      doc.addPage()
      y = 40
    }

    doc.addImage(imgData, 'PNG', 40, y, imgWidth, imgHeight)
    y += imgHeight + 30
  }


  if (incluirSugestoes.value && sugestoesRef.value?.sugestoesFiltradas) {
    const sugestoes = sugestoesRef.value.sugestoesFiltradas

    doc.addPage()
    y = 40

    doc.setFontSize(16)
    doc.setFont('Helvetica', 'bold')
    doc.text('Sugestões de Ação', 40, y)
    y += 20

    doc.setFontSize(12)
    doc.setFont('Helvetica', 'normal')
    const introducao = 'Recomendações de campanhas com base nos segmentos identificados pela análise RFM. Estas sugestões visam melhorar o relacionamento com os clientes e maximizar o seu valor ao longo do tempo.'
    const introLinhas = doc.splitTextToSize(introducao, 500)
    doc.text(introLinhas, 40, y)
    y += introLinhas.length * 16 + 10

    for (const sugestao of sugestoes) {
      if (y > 750) {
        doc.addPage()
        y = 40
      }

      doc.setFont('Helvetica', 'bold')
      doc.setFontSize(13)
      doc.text(sugestao.segmento, 40, y)
      y += 18

      doc.setFont('Helvetica', 'normal')
      doc.setFontSize(11)
      for (const ponto of sugestao.pontos) {
        const linhas = doc.splitTextToSize('• ' + ponto, 500)
        if (y + linhas.length * 14 > 750) {
          doc.addPage()
          y = 40
        }
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