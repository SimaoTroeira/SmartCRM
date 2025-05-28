<template>
  <div class="flex items-center gap-4 mb-3 flex-wrap">
    <div class="flex items-center gap-2">
      <label class="text-sm font-medium">Ordenar por:</label>
      <select
        :value="colunaOrdenada"
        @change="$emit('update:colunaOrdenada', $event.target.value)"
        class="border px-2 py-1 rounded text-sm"
      >
        <option value="">Nenhuma</option>
        <option value="support">Suporte (%)</option>
        <option value="confidence">Confiança (%)</option>
        <option value="lift">Lift</option>
      </select>

      <button
        class="text-sm underline"
        @click="$emit('update:ordemCrescente', !ordemCrescente)"
        title="Alternar entre ordem ascendente e descendente"
      >
        {{ ordemCrescente ? '↑ asc' : '↓ desc' }}
      </button>
    </div>

    <div class="flex items-center gap-2">
      <label class="text-sm font-medium">Limite:</label>
      <input
        type="number"
        min="1"
        :value="limiteLinhas"
        @input="$emit('update:limiteLinhas', Number($event.target.value))"
        class="border px-2 py-1 rounded text-sm w-16"
      />
    </div>
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
select {
  min-width: 130px;
}
</style>
