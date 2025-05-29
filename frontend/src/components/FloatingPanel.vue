<template>
  <teleport to="body">
    <transition name="slide-left">
      <div
        v-if="showFloatingPanel"
        class="z-[9999] bg-white shadow-lg border border-gray-300 rounded-xl px-4 py-3 pointer-events-auto cursor-pointer hover:shadow-xl transition-all"
        style="width: 260px; max-width: 90vw; position: fixed; top: 120px; left: 16px;"
        @click="scrollToTop"
      >
        <p class="text-sm text-gray-600 font-semibold">Empresa: {{ empresaSelecionada?.name || 'N/D' }}</p>
        <p class="text-sm text-gray-600 font-semibold">Campanha: {{ campanhaSelecionada?.name || 'N/D' }}</p>
        <p class="text-sm text-gray-600 font-semibold">Algoritmo: {{ algoritmoLabel }}</p>
        <p class="text-xs text-blue-500 mt-1">Clique para alterar</p>
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
        showFloatingPanel.value = val > 200 // ou até val > 50 para aparecer mais cedo
    })
})


const empresaSelecionada = computed(() => {
    return props.companies?.find(c => c.id === parseInt(props.selectedCompanyId))
})

const campanhaSelecionada = computed(() => {
    return props.campaigns?.find(c => c.id === parseInt(props.selectedCampaignId))
})

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
</style>

