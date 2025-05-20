<template>
  <div class="recommend-results">
    <h2>Recomendações de Cross-Selling</h2>

    <div v-if="loading">A carregar recomendações...</div>
    <div v-else-if="error">{{ error }}</div>
    <div v-else>
      <div v-if="regras.length === 0">Nenhuma recomendação encontrada.</div>
      <table v-else class="table-auto border-collapse border border-gray-300 w-full">
        <thead>
          <tr>
            <th class="border border-gray-300 p-2">Antecedentes</th>
            <th class="border border-gray-300 p-2">Consequentes</th>
            <th class="border border-gray-300 p-2">Suporte</th>
            <th class="border border-gray-300 p-2">Confiança</th>
            <th class="border border-gray-300 p-2">Lift</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(regra, idx) in regras" :key="idx">
            <td class="border border-gray-300 p-2">{{ regra.antecedents.join(", ") }}</td>
            <td class="border border-gray-300 p-2">{{ regra.consequents.join(", ") }}</td>
            <td class="border border-gray-300 p-2">{{ regra.support.toFixed(3) }}</td>
            <td class="border border-gray-300 p-2">{{ regra.confidence.toFixed(3) }}</td>
            <td class="border border-gray-300 p-2">{{ regra.lift.toFixed(3) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    empresaId: { type: Number, required: true },
    campanhaId: { type: Number, required: true }
  },
  data() {
    return {
      regras: [],
      loading: false,
      error: null
    }
  },
  methods: {
    async carregarRecomendacoes() {
      this.loading = true;
      this.error = null;
      try {
        // Ajusta URL conforme API/backend que sirva o JSON gerado
        const url = `/api/recommendations/empresa_id_${this.empresaId}/campanha_id_${this.campanhaId}/recomendacoes_produto.json`;
        const response = await fetch(url);
        if (!response.ok) throw new Error("Erro ao carregar recomendações");
        this.regras = await response.json();
      } catch(err) {
        this.error = err.message;
      } finally {
        this.loading = false;
      }
    }
  },
  mounted() {
    this.carregarRecomendacoes();
  }
}
</script>

<style scoped>
table {
  border-collapse: collapse;
  width: 100%;
}
th, td {
  border: 1px solid #ccc;
  padding: 0.5em;
  text-align: left;
}
</style>
