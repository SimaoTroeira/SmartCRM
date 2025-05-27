<template>
  <div class="flex items-center gap-4 mb-3">
    <label class="text-sm">Ordenar por:</label>
    <select
      :value="colunaOrdenada"
      @change="$emit('update:colunaOrdenada',$event.target.value)"
      class="border px-2 py-1 rounded text-sm"
    >
      <option value="">Nenhuma</option>
      <option value="support">Suporte</option>
      <option value="confidence">Confiança</option>
      <option value="lift">Lift</option>
    </select>

    <button
      class="text-sm underline"
      @click="$emit('update:ordemCrescente', !ordemCrescente)"
    >
      {{ ordemCrescente ? '↑ asc' : '↓ desc' }}
    </button>

    <label class="text-sm">Limite:</label>
    <input
      type="number" min="1"
      :value="limiteLinhas"
      @input="$emit('update:limiteLinhas', Number($event.target.value))"
      class="border px-2 py-1 rounded text-sm w-16"
    />
  </div>
</template>

<script setup>
defineProps({
  colunaOrdenada: String,
  ordemCrescente: Boolean,
  limiteLinhas: Number
})
defineEmits([
  'update:colunaOrdenada',
  'update:ordemCrescente',
  'update:limiteLinhas'
])
</script>
<style scoped>

</style>
