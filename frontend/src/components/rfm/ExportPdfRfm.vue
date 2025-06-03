<template>
  <div>
    <button class="btn-exportar-pdf" @click="abrirModal">
      🧾 Exportar PDF
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
            <span v-if="!aGerar">📄 Gerar Relatório</span>
            <span v-else>⏳ A gerar PDF...</span>
          </button>
        </div>
      </div>
    </dialog>

    <!-- Conteúdo invisível -->
    <div style="position: absolute; top: -9999px; left: -9999px; width: 1000px;">
      <div v-if="graficosSelecionados.includes('pca')">
        <div ref="pcaRef">
          <h4>Gráfico PCA</h4>
          <ScatterPlotPCA :scatter-clientes="scatterClientes" />
        </div>
      </div>
      <div v-if="graficosSelecionados.includes('normal')">
        <div ref="normalRef">
          <h4>Gráfico Normal</h4>
          <ScatterPlot :scatter-clientes="scatterClientes" :scatter-regioes="scatterRegioes" />
        </div>
      </div>
      <div v-if="graficosSelecionados.includes('radar')">
        <div ref="radarRef">
          <h4>Gráfico Radar</h4>
          <RadarPlot :scatter-clientes="scatterClientes" />
        </div>
      </div>
      <div v-if="graficosSelecionados.includes('barras')">
        <div ref="barrasRef">
          <h4>Gráfico de Barras</h4>
          <RegioesBarChart :scatter-regioes="scatterRegioes" />
        </div>
      </div>
      <div v-if="graficosSelecionados.includes('mapa')">
        <div ref="mapaRef">
          <h4>Mapa de Portugal</h4>
          <PortugalMap :dados-regioes="scatterRegioes" />
        </div>
      </div>
      <div ref="sugestoesRef" v-show="incluirSugestoes">
        <RfmSuggestions :clientes-segmentados="props.clientesSegmentados" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue'
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

const props = defineProps({
  nomeEmpresa: String,
  nomeCampanha: String,
  scatterClientes: Array,
  scatterRegioes: Array,
  clientesSegmentados: Array
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
        descricao: `Este gráfico aplica Análise de Componentes Principais (PCA) para condensar múltiplas variáveis dos clientes em dois eixos principais — PCA 1 e PCA 2. Estes eixos representam as direções de maior variação nos dados, facilitando a visualização de padrões e agrupamentos complexos. Clientes com comportamentos semelhantes estão mais próximos entre si. As duas dimensões encontram-se normalizadas entre 0 e 1.`
    },
    normal: {
        titulo: 'Gráfico Normal',
        descricao: `Gráfico de dispersão com base em métricas RFM originais. Permite visualizar padrões diretamente sobre as dimensões Recência, Frequência e Monetário, antes de qualquer redução de dimensionalidade.`
    },
    radar: {
        titulo: 'Gráfico Radar',
        descricao: `Compara visualmente os diferentes clusters com base nos três pilares do RFM: Recência (tempo desde a última compra), Frequência (número de compras) e Valor Monetário (gasto total).`
    },
    barras: {
        titulo: 'Gráfico de Barras',
        descricao: `Mostra a distribuição dos clientes por região e por segmento/cluster. É útil para perceber a concentração geográfica de certos perfis.`
    },
    mapa: {
        titulo: 'Mapa de Portugal',
        descricao: `Representação geográfica dos clusters por distrito ou região, oferecendo uma visão espacial da segmentação dos clientes no território nacional.`
    }
}

const pcaRef = ref(null)
const normalRef = ref(null)
const radarRef = ref(null)
const barrasRef = ref(null)
const mapaRef = ref(null)
const sugestoesRef = ref(null)

const refObjs = {
    pca: pcaRef,
    normal: normalRef,
    radar: radarRef,
    barras: barrasRef,
    mapa: mapaRef,
    sugestoes: sugestoesRef
}


function normalizarNome(nome) {
    if (!nome) return 'desconhecido'
    return nome
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/ç/g, 'c')
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
        const canvas = wrapper.querySelector('canvas')

        const { titulo, descricao } = descricoesGraficos[grafico] || { titulo: '', descricao: '' }

        // Página nova se necessário
        if (y + 100 > 750) {
            doc.addPage()
            y = 40
        }

        // Título e descrição principal
        doc.setFontSize(16)
        doc.setFont('Helvetica', 'bold')
        doc.text(titulo, 40, y)
        y += 20

        doc.setFontSize(12)
        doc.setFont('Helvetica', 'normal')
        const linhas = doc.splitTextToSize(descricao, 500)
        doc.text(linhas, 40, y)
        y += linhas.length * 16 + 10

        // ➕ Textos adicionais visíveis no DOM (<p> e <li>)
        const paragrafos = Array.from(wrapper.querySelectorAll('p')).map(p => p.innerText.trim())
        const listas = Array.from(wrapper.querySelectorAll('li')).map(li => '• ' + li.innerText.trim())
        const textosAdicionais = [...paragrafos, ...listas]

        for (const texto of textosAdicionais) {
            const extraLinhas = doc.splitTextToSize(texto, 500)
            if (y + extraLinhas.length * 16 > 750) {
                doc.addPage()
                y = 40
            }
            doc.setFont('Helvetica', 'normal')
            doc.setFontSize(11)
            doc.text(extraLinhas, 50, y)
            y += extraLinhas.length * 14 + 5
        }

        // Imagem do gráfico
        if (canvas) {
            const canvasImagem = await html2canvas(canvas)
            const imgData = canvasImagem.toDataURL('image/png')

            const imgWidth = 500
            const imgHeight = (canvasImagem.height * imgWidth) / canvasImagem.width

            if (y + imgHeight > 750) {
                doc.addPage()
                y = 40
            }

            doc.addImage(imgData, 'PNG', 40, y, imgWidth, imgHeight)
            y += imgHeight + 30
        }
    }


    if (incluirSugestoes.value && sugestoesRef.value) {
        const sugestoesWrapper = sugestoesRef.value

        await nextTick()
        await new Promise(resolve => setTimeout(resolve, 1000))

        const canvas = await html2canvas(sugestoesWrapper)
        const imgData = canvas.toDataURL('image/png')

        const imgWidth = 500
        const imgHeight = (canvas.height * imgWidth) / canvas.width

        if (y + imgHeight > 750) {
            doc.addPage()
            y = 40
        }
        doc.addImage(imgData, 'PNG', 40, y, imgWidth, imgHeight)
        y += imgHeight + 30
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
