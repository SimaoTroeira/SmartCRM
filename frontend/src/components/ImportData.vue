  <template>
    <div class="p-6">
      <h2 class="mb-4">Escolha o local de onde quer importar os dados</h2>

      <!-- Botão para importar do computador -->
      <div class="mb-4">
        <label for="fileInput"
          class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 rounded-lg cursor-pointer hover:bg-blue-700 font-semibold text-center shadow-md transition duration-200">
          <span class="btn btn-primary">Do meu Computador</span>
        </label>
        <input type="file" id="fileInput" class="hidden" @change="handleFileUpload"
          accept=".csv, .xls, .xlsx, .zip, .json">
      </div>

      <!-- Dropdown para selecionar ficheiro dentro do ZIP -->
      <div v-if="zipFiles.length > 0" class="mb-4">
        <label for="fileSelect">Selecione um ficheiro:</label>
        <select id="fileSelect" v-model="selectedZipFile" @change="loadZipFile" class="ml-2 border p-2 rounded">
          <option v-for="file in zipFiles" :key="file" :value="file">{{ file }}</option>
        </select>
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

      <!-- Botões de Reset e Mapear no topo esquerdo -->
      <div v-if="tableData.length && !isLoading" class="mt-4 mb-4 flex justify-start gap-2">
        <button @click="resetSorting"
          class="px-2 py-1 bg-red-600 rounded-lg text-black font-semibold hover:bg-red-700 text-sm">
          Repor Ordem
        </button>
        <button @click="openMapDialog"
          class="px-2 py-1 bg-blue-600 rounded-lg text-black font-semibold hover:bg-blue-700 text-sm">
          Importar esta Tabela
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
      <div v-if="tableData.length && !isLoading"
        class="mt-4 flex justify-center gap-4 fixed bottom-0 left-0 right-0 bg-white z-10 p-4">
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
      <!-- Diálogo de Mapeamento - Versão Corrigida -->
      <div v-if="showMapDialog" class="map-dialog-overlay">
        <div class="map-dialog-wrapper">
          <div class="map-dialog">
            <div class="dialog-header">
              <h3>Para importar a tabela tem de mapear os dados</h3>
              <button @click="closeMapDialog" class="close-btn">&times;</button>
            </div>
            <div class="dialog-content">
              <MapData :headers="headers" :tableData="tableData" :tableName="tableName" @close="closeMapDialog"
                @save="handleMapSave" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

<script>
import * as XLSX from "xlsx";
import JSZip from "jszip";
import MapData from './MapData.vue';


export default {
  components: {
    MapData
  },
  data() {
    return {
      showMapDialog: false,
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
      zipFiles: [],
      selectedZipFile: "",
      zipFileData: {},
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

      this.fileName = file.name;
      this.isLoading = true;

      if (file.name.endsWith(".zip")) {
        const zip = new JSZip();
        const loadedZip = await zip.loadAsync(file);
        this.zipFiles = Object.keys(loadedZip.files).filter(
          (fileName) => !loadedZip.files[fileName].dir
        );
        this.zipFileData = loadedZip;
        this.isLoading = false;
      }
      else if (file.name.endsWith(".json")) {
        this.processJSONFile(file);
      } else {
        this.processFile(file);
      }
    },

    async loadZipFile() {
      if (!this.selectedZipFile) return;

      const fileData = await this.zipFileData.files[this.selectedZipFile].async("arraybuffer");
      const fileBlob = new Blob([fileData]);
      this.processFile(fileBlob, this.selectedZipFile);
    },

    processFile(file, fileName = "") {
      this.isLoading = true;

      const reader = new FileReader();
      reader.onload = (e) => {
        setTimeout(() => {
          const data = new Uint8Array(e.target.result);
          const workbook = XLSX.read(data, { type: "array" });
          const sheetName = workbook.SheetNames[0];
          const sheet = workbook.Sheets[sheetName];
          const jsonData = XLSX.utils.sheet_to_json(sheet, { header: 1 });

          if (jsonData.length) {
            this.headers = jsonData[0];
            this.tableData = jsonData.slice(1).map(row =>
              row.map((cell, index) => this.convertExcelDate(cell, this.headers[index]))
            );
            this.originalData = JSON.parse(JSON.stringify(this.tableData)); // Faz uma cópia profunda
            this.rowCount = this.tableData.length;
            this.columnCount = jsonData[0].length;
            if (fileName) {
              const cleanFileName = fileName.split('/').pop().replace(/\.[^/.]+$/, "");
              this.tableName = cleanFileName;
            } else {
              this.tableName = sheetName !== "Sheet1" ? sheetName : this.fileName.replace(/\.[^/.]+$/, "");
            }
          }

          this.isLoading = false;
        }, 1000);
      };

      reader.readAsArrayBuffer(file);
    },

    processJSONFile(file) {
      const reader = new FileReader();

      reader.onload = (e) => {
        try {
          let jsonString = e.target.result.trim(); // Remove espaços extras

          console.log("JSON recebido:", jsonString);

          // Verifica se o JSON está em formato JSON Lines (JSONL) e converte para um array
          if (!jsonString.startsWith("[") && !jsonString.endsWith("]")) {
            jsonString = "[" + jsonString.replace(/}\s*{/g, "},{") + "]";
          }

          const jsonData = JSON.parse(jsonString);

          console.log("JSON convertido com sucesso:", jsonData);

          // Se for um array, processa normalmente
          if (Array.isArray(jsonData) && jsonData.length > 0) {
            this.headers = Object.keys(jsonData[0]); // Obtém as colunas
            this.tableData = jsonData.map(obj => Object.values(obj)); // Obtém as linhas
            this.originalData = JSON.parse(JSON.stringify(this.tableData)); // Faz uma cópia profunda
            this.rowCount = this.tableData.length;
            this.columnCount = this.headers.length;
            this.tableName = file.name.replace(/\.[^/.]+$/, ""); // Usa o nome do ficheiro sem a extensão
          } else {
            throw new Error("Formato JSON inválido ou estrutura inesperada.");
          }
        } catch (error) {
          console.error("Erro ao processar JSON:", error.message);
          alert(`Erro ao processar JSON: ${error.message}`);
        } finally {
          this.isLoading = false;
        }
      };

      reader.readAsText(file);
    },

    convertExcelDate(excelDate, columnName = '') {
      const lowerCol = columnName.toLowerCase();
      const isDateCol = /data|date|hora|nascimento|criado|cadastro/i.test(lowerCol);

      // Trata números como datas, mesmo se o nome da coluna não indicar
      if (typeof excelDate === 'number' && excelDate > 25569 && excelDate < 2958465) {
        const correctedDate = (excelDate - 25569) * 86400 * 1000;
        const date = new Date(correctedDate);
        return date.toISOString().split('T')[0];
      }

      // Também tenta converter strings que pareçam datas
      if (typeof excelDate === 'string') {
        const parsed = Date.parse(excelDate);
        if (!isNaN(parsed)) {
          const date = new Date(parsed);
          return date.toISOString().split('T')[0];
        }
        // tenta forçar conversão de número como string (ex: "45034")
        if (!isNaN(Number(excelDate)) && Number(excelDate) > 25569 && Number(excelDate) < 2958465) {
          const correctedDate = (Number(excelDate) - 25569) * 86400 * 1000;
          const date = new Date(correctedDate);
          return date.toISOString().split('T')[0];
        }
      }

      return excelDate;
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
      this.tableData = JSON.parse(JSON.stringify(this.originalData)); // Faz uma cópia profunda
      this.sortColumnIndex = null;
      this.sortOrder = 'asc';
      this.currentPage = 1; // Reseta para a primeira página
    },
    openMapDialog() {
      this.showMapDialog = true;
    },

    closeMapDialog() {
      this.showMapDialog = false;
    },
    handleMapSave(mappedData) {
      // Aqui você pode processar os dados mapeados
      console.log("Dados mapeados:", mappedData);
      this.closeMapDialog();
      // Adicione qualquer lógica adicional para salvar os dados mapeados
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
  padding: 1.5rem;
  width: 80%;
}

table {
  max-width: 100%;
  table-layout: fixed;
}

.overflow-x-auto {
  overflow-x: auto;
  max-width: 100%;
}

button {
  cursor: pointer;
}

tbody tr:nth-child(even) {
  background-color: #ffffff;
}

tbody tr:nth-child(odd) {
  background-color: #e7e7e7;
}

/* Adicione ESTES novos estilos para o diálogo (no final) */
.map-dialog-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  display: flex;
  justify-content: center;
  align-items: center;
}

.map-dialog-wrapper {
  position: relative;
  width: 90%;
  max-width: 900px;
  max-height: 90vh;
}

.map-dialog {
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  animation: dialog-enter 0.3s ease-out;
}

@keyframes dialog-enter {
  from {
    transform: translateY(20px);
    opacity: 0;
  }

  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.dialog-header {
  padding: 16px 24px;
  background: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.dialog-header h3 {
  margin: 0;
  font-size: 1.25rem;
}

.dialog-content {
  padding: 20px;
  max-height: 80vh;
  overflow-y: auto;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6c757d;
  transition: color 0.2s;
}

.close-btn:hover {
  color: #495057;
}

/* Garante que a paginação não interfira */
.fixed {
  z-index: 100;
  /* Menor que o do diálogo */
}
</style>