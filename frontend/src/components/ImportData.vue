<template>
  <div class="p-6">
    <h2 class="mb-4">Escolha o local de onde quer importar os dados</h2>

    <!-- Botão para importar do computador -->
    <div class="mb-4">
      <label for="fileInput"
        class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 rounded-lg cursor-pointer hover:bg-blue-700 font-semibold text-center shadow-md transition duration-200">
        <span class="btn btn-primary">Do meu Computador</span>
      </label>
      <input type="file" id="fileInput" class="hidden" @change="handleFileUpload" accept=".csv, .xls, .xlsx">
    </div>

    <!-- Mensagem de carregamento -->
    <div v-if="isLoading" class="p-3 border border-yellow-500 rounded bg-yellow-100 text-yellow-700">
      <p>A carregar os dados...</p>
    </div>

    <!-- Mensagem de sucesso com nome da tabela e nome do arquivo -->
    <div v-if="tableData.length > 0 && !isLoading"
      class="alert alert-success p-3 border border-green-500 rounded bg-green-100 text-green-700">
      <p>O ficheiro "{{ fileName }}" foi importado!</p>
      <p>A tabela "{{ tableName }}" foi importada!</p>
      <p>Esta tabela contém {{ rowCount }} linhas e {{ columnCount }} colunas.</p>
    </div>

    <!-- Botão de Reset no topo esquerdo -->
    <div v-if="tableData.length && !isLoading" class="mt-4 mb-4 flex justify-start">
      <button @click="resetSorting"
        class="px-2 py-1 bg-red-600 rounded-lg text-black font-semibold hover:bg-red-700 text-sm">
        Remover Ordenação
      </button>
    </div>
    <!-- Barra de rolagem horizontal acima da tabela -->
    <div v-if="tableData.length && !isLoading" class="mt-6 overflow-x-auto mb-4">
      <table class="min-w-full border-collapse border border-gray-300">
        <thead>
          <tr class="bg-gray-200">
            <th v-for="(header, index) in headers" :key="'header-' + index" @click="sortTable(index)"
              class="cursor-pointer border border-gray-300 px-4 py-2 whitespace-nowrap">
              {{ header }}
              <!-- Exibe a seta de ordenação -->
              <span v-if="sortColumnIndex === index">
                <span v-if="sortOrder === 'asc'">↑</span>
                <span v-if="sortOrder === 'desc'">↓</span>
              </span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, rowIndex) in visibleRows" :key="'row-' + rowIndex">
            <td v-for="(cell, cellIndex) in row" :key="'cell-' + rowIndex + '-' + cellIndex"
              class="border border-gray-300 px-4 py-2 whitespace-nowrap">
              {{ cell }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

<!-- Paginação -->
<div v-if="tableData.length && !isLoading" class="mt-4 flex justify-center gap-4 fixed bottom-0 left-0 right-0 bg-white z-10 p-4">
  <!-- Botão para a primeira página -->
  <button :disabled="currentPage === 1" @click="currentPage = 1"
    class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 disabled:bg-gray-400">
    Primeira
  </button>

  <!-- Botão para a página anterior -->
  <button :disabled="currentPage === 1" @click="currentPage--"
    class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 disabled:bg-gray-400">
    Anterior
  </button>

  <!-- Texto de página atual -->
  <span>Página {{ currentPage }} de {{ totalPages }}</span>

  <!-- Botão para a página próxima -->
  <button :disabled="currentPage === totalPages" @click="currentPage++"
    class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 disabled:bg-gray-400">
    Próxima
  </button>

  <!-- Botão para a última página -->
  <button :disabled="currentPage === totalPages" @click="currentPage = totalPages"
    class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 disabled:bg-gray-400">
    Última
  </button>
</div>

  </div>
</template>

<script>
import * as XLSX from "xlsx";

export default {
  data() {
    return {
      tableData: [], // Armazena os dados da tabela importada
      originalData: [], // Armazena os dados originais para reset
      isLoading: false, // Estado de carregamento
      maxRows: 10, // Limita a exibição a 10 linhas por página
      rowCount: 0, // Número de linhas
      columnCount: 0, // Número de colunas
      tableName: "", // Nome da tabela
      fileName: "", // Nome do ficheiro
      currentPage: 1, // Página atual
      sortColumnIndex: null, // Índice da coluna que está sendo ordenada
      sortOrder: 'asc', // Ordem de ordenação ('asc' ou 'desc')
    };
  },
  computed: {
    visibleRows() {
      const start = (this.currentPage - 1) * this.maxRows;
      const end = start + this.maxRows;
      return this.sortedTable.slice(start, end); // Exibe as linhas da página atual
    },
    totalPages() {
      return Math.ceil(this.rowCount / this.maxRows); // Total de páginas baseado no número de linhas
    },
    sortedTable() {
      // Ordena a tabela com base na coluna e direção de ordenação
      if (this.sortColumnIndex === null) return this.tableData;

      const sortedData = [...this.tableData];
      sortedData.sort((a, b) => {
        const aValue = a[this.sortColumnIndex];
        const bValue = b[this.sortColumnIndex];

        // Verifica se os valores são números ou strings
        if (typeof aValue === 'string' && typeof bValue === 'string') {
          return this.sortOrder === 'asc'
            ? aValue.localeCompare(bValue)
            : bValue.localeCompare(aValue);
        } else {
          return this.sortOrder === 'asc'
            ? aValue - bValue
            : bValue - aValue;
        }
      });
      return sortedData;
    },
  },
  methods: {
    async handleFileUpload(event) {
      const file = event.target.files[0];
      if (!file) return;

      this.fileName = file.name; // Armazena o nome do ficheiro

      this.isLoading = true; // Inicia o estado de carregamento

      const reader = new FileReader();
      reader.onload = (e) => {
        setTimeout(() => { // Simula um tempo de processamento
          const data = new Uint8Array(e.target.result);
          const workbook = XLSX.read(data, { type: "array" });
          const sheetName = workbook.SheetNames[0];
          const sheet = workbook.Sheets[sheetName];

          // Aqui ajustamos para pegar corretamente os dados e os cabeçalhos
          const jsonData = XLSX.utils.sheet_to_json(sheet, { header: 1 });

          if (jsonData.length) {
            // Agora, a primeira linha é o cabeçalho e as demais são os dados
            this.tableData = jsonData.slice(1); // Remover a primeira linha (cabeçalho duplicado)
            this.originalData = [...this.tableData]; // Armazena os dados originais
            this.tableName = sheetName; // Atribui o nome da planilha
            this.rowCount = this.tableData.length; // Agora contamos as linhas corretamente
            this.columnCount = jsonData[0].length; // Mantemos o número correto de colunas

            // Adicionamos a primeira linha como cabeçalhos
            this.headers = jsonData[0]; // A primeira linha será tratada como cabeçalhos
          } else {
            console.error("Erro ao processar o ficheiro");
          }

          this.isLoading = false; // Finaliza o carregamento
        }, 1000); // Simula um atraso de 1 segundo
      };

      reader.readAsArrayBuffer(file);
    },

    sortTable(index) {
      // Se a coluna clicada for a mesma, inverte a direção
      if (this.sortColumnIndex === index) {
        this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortColumnIndex = index;
        this.sortOrder = 'asc'; // Começa sempre em ordem crescente
      }
    },

    resetSorting() {
      // Restaura os dados originais e reseta a ordenação
      this.tableData = [...this.originalData];
      this.sortColumnIndex = null;
      this.sortOrder = 'asc';
    },
  },
};
</script>

<style scoped>
.hidden {
  display: none;
}

.mt-4 {
  position: relative;
  text-align: left;
}

.p-6 {
  position: absolute;
  padding: 20px;
  text-align: center;
  width: 65%;
}

table {
  max-width: 100%;
  table-layout: fixed;
}

.overflow-x-auto {
  overflow-x: auto;
  /* Ativa a rolagem horizontal para as colunas */
  max-width: 100%;
}

button {
  cursor: pointer;
}
</style>
