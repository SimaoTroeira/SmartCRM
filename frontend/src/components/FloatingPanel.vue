<template>
  <teleport to="body">
    <transition name="slide-left">
      <div v-if="showFloatingPanel" class="floating-panel">
        <div class="painel-conteudo">
          <p class="text-sm text-gray-600 font-semibold">Empresa: {{ empresaSelecionada?.name || 'N/D' }}</p>
          <p class="text-sm text-gray-600 font-semibold">Campanha: {{ campanhaSelecionada?.name || 'N/D' }}</p>
          <p class="text-sm text-gray-600 font-semibold">Algoritmo: {{ algoritmoLabel }}</p>
        </div>
        <button @click="scrollToTop" title="Voltar ao topo" class="scroll-top-button">
          Alterar 
        </button>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { onMounted, ref, computed, watch } from 'vue'
import { useScroll } from '@vueuse/core'

const props = defineProps({
  companies: Array,
  campaigns: Array,
  selectedCompanyId: [String, Number],
  selectedCampaignId: [String, Number],
  selectedAlgorithm: String,
})

const showFloatingPanel = ref(false)
const { y } = useScroll(window)

onMounted(() => {
  watch(y, (val) => {
    showFloatingPanel.value = val > 200
  })
})

const empresaSelecionada = computed(() =>
  props.companies?.find(c => c.id === parseInt(props.selectedCompanyId))
)

const campanhaSelecionada = computed(() =>
  props.campaigns?.find(c => c.id === parseInt(props.selectedCampaignId))
)

const algoritmoLabel = computed(() => {
  switch (props.selectedAlgorithm) {
    case 'rfm': return 'Segmentação RFM'
    case 'churn': return 'Previsão de Churn'
    case 'recommendation': return 'Recomendação'
    default: return 'N/D'
  }
})

const scrollToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
</script>


<style scoped>
.slide-left-enter-active,
.slide-left-leave-active {
  transition: transform 0.4s ease, opacity 0.4s ease;
}

.slide-left-enter-from,
.slide-left-leave-to {
  transform: translateX(-120%);
  opacity: 0;
  box-shadow: 0 4px 10px rgba(59, 130, 246, 0.1);
}

.floating-panel {
  position: fixed;
  top: 120px;
  left: 16px;
  width: 260px;
  max-width: 90vw;
  background-color: white;
  border: 1px solid #d1d5db;
  border-radius: 12px;
  padding: 16px;
  z-index: 9999;
  box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
  pointer-events: auto;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 130px;
}

.painel-conteudo {
  margin-bottom: 12px;
}

.scroll-top-button {
  align-self: flex-start;
  font-size: 0.875rem;
  background: none;
  border: none;
  color: #3b82f6;
  cursor: pointer;
  transition: color 0.3s ease;
  padding: 0;
}

.scroll-top-button:hover {
  color: #1d4ed8;
}

</style>
