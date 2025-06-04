<template>
  <div>
    <button @click="exportarPDF" class="btn btn-primary mb-4">
      Exportar Gráficos (PDF)
    </button>
    <div ref="pdfContent">
      <h2 class="text-xl font-bold mb-2">📦 Regras Produto–Produto</h2>
      <canvas ref="canvasProduto" class="mb-6"></canvas>

      <div
        v-for="(lista, atributo) in attrRules"
        :key="atributo"
        class="mb-8"
      >
        <h3 class="text-lg font-semibold mb-2">
          🔖 Regras por {{ atributo }}
        </h3>
        <canvas :ref="el => canvasAtributos[atributo] = el"></canvas>
      </div>
    </div>
  </div>
</template>

<script setup>
import html2canvas from 'html2canvas'
import jsPDF from 'jspdf'
import { ref, watch, nextTick } from 'vue'

const props = defineProps({
  attrRules: Object,
})

const pdfContent = ref(null)
const canvasProduto = ref(null)
const canvasAtributos = ref({})

async function exportarPDF() {
  try {
    await nextTick()

    const pdf = new jsPDF({ unit: 'px', format: 'a4' })
    let yOffset = 20

    // Canvas principal (produto-produto)
    const canvas1 = canvasProduto.value
    if (canvas1) {
      const img = canvas1.toDataURL('image/png')
      pdf.text('📦 Regras Produto–Produto', 20, yOffset)
      yOffset += 10
      pdf.addImage(img, 'PNG', 20, yOffset, 555, 300)
      yOffset += 320
    }

    // Canvas por atributo
    for (const [attr, canvas] of Object.entries(canvasAtributos.value)) {
      if (canvas) {
        const img = canvas.toDataURL('image/png')
        pdf.text(`🔖 Regras por ${attr}`, 20, yOffset)
        yOffset += 10
        pdf.addImage(img, 'PNG', 20, yOffset, 555, 300)
        yOffset += 320
      }
    }

    pdf.save('recomendacao_graficos.pdf')
  } catch (err) {
    console.error('Erro ao exportar PDF:', err)
  }
}
</script>

<style scoped>
canvas {
  width: 100%;
  max-width: 600px;
  height: auto;
  border: 1px solid #ccc;
  border-radius: 8px;
}
.btn {
  background-color: #2563eb;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-weight: 500;
  cursor: pointer;
}
</style>
