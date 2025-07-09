  <template>
    <div class="p-6">
      <h2 class="mb-4">Escolha o local de onde quer importar os dados</h2>
      <div class="mb-4">
        <label for="fileInput"
          class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 rounded-lg cursor-pointer hover:bg-blue-700 font-semibold text-center shadow-md transition duration-200">
          <span class="btn btn-primary">Do meu Computador</span>
        </label>
        <input type="file" id="fileInput" class="hidden" @change="handleFileUpload"
          accept=".csv, .xls, .xlsx, .zip, .json">
      </div>
      <div v-if="zipFiles.length > 0" class="mb-4">
        <label for="fileSelect">Selecione um ficheiro:</label>
        <select id="fileSelect" v-model="selectedZipFile" @change="loadZipFile" class="ml-2 border p-2 rounded">
          <option v-for="file in zipFiles" :key="file" :value="file">{{ file }}</option>
        </select>
      </div>
      <div v-if="isLoading" class="p-3 border border-yellow-500 rounded bg-yellow-100 text-yellow-700">
        <p>A carregar os dados...</p>
      </div>
      <div v-if="tableData.length > 0 && !isLoading"
        class="alert alert-success p-3 border border-green-500 rounded bg-green-100 text-green-700">
        <p>O ficheiro "{{ fileName }}" está pronto a importar!</p> <!-- foi importado  -->
        <p>A tabela "{{ tableName }}" está pronta a importar!</p>
        <p>Esta tabela contém {{ rowCount }} linhas e {{ columnCount }} colunas.</p>
      </div>
      <div v-if="tableData.length && !isLoading" class="mt-4 mb-4 flex justify-between items-center gap-4">
        <div class="flex justify-between items-center w-full mb-4">
          <button @click="openMapDialog"
            class="btn btn-success px-5 py-2 bg-green-600 text-white rounded-xl font-semibold shadow hover:bg-green-700 transition-all duration-200 text-base flex items-center gap-2">
            Importar esta Tabela
          </button>


          <button @click="resetSorting"
            class="btn btn-warning ml-auto px-3 py-2 bg-red-600 rounded-xl text-white font-semibold hover:bg-red-700 text-sm shadow-md">
            Repor Ordem
          </button>
        </div>

        <div v-if="sheetNames.length" class="flex items-center gap-2">
          <label for="sheetSelect" class="font-medium">Folha:</label>
          <select id="sheetSelect" v-model="selectedSheet" @change="loadSelectedSheet"
            class="border border-gray-300 p-2 rounded">
            <option v-for="name in sheetNames" :key="name" :value="name">{{ name }}</option>
          </select>
        </div>
      </div>
      <div v-if="tableData.length && !isLoading" class="mt-6 overflow-x-auto mb-4">
        <table class="min-w-full border-collapse border border-gray-300">
          <thead>
            <tr class="bg-gray-200">
              <th v-for="(header, index) in headers" :key="'header-' + index" @click="sortTable(index)"
                class="cursor-pointer border border-gray-300 px-4 py-2 whitespace-nowrap">
                {{ header }}
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
      <div v-if="tableData.length && !isLoading"
        class="mt-6 mb-4 flex justify-center gap-4 sticky bottom-0 left-0 right-0 bg-white z-20 p-4 shadow border-t border-gray-200">
        <button :disabled="currentPage === 1" @click="currentPage = 1"
          class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 disabled:bg-gray-400 disabled:cursor-not-allowed">
          Primeira
        </button>
        <button :disabled="currentPage === 1" @click="currentPage--"
          class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 disabled:bg-gray-400 disabled:cursor-not-allowed">
          Anterior
        </button>

        <span class="px-2 font-semibold text-gray-700">Página {{ currentPage }} de {{ totalPages }}</span>

        <button :disabled="currentPage === totalPages" @click="currentPage++"
          class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 disabled:bg-gray-400 disabled:cursor-not-allowed">
          Próxima
        </button>
        <button :disabled="currentPage === totalPages" @click="currentPage = totalPages"
          class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 disabled:bg-gray-400 disabled:cursor-not-allowed">
          Última
        </button>
      </div>

      <div v-if="showMapDialog" class="map-dialog-overlay">
        <div class="map-dialog-wrapper">
          <div class="map-dialog">
            <div class="dialog-header">
              <h3>Para importar a tabela terá de mapear os dados de acordo com os nossos</h3>
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
import { useToast } from 'vue-toastification';


export default {
  components: {
    MapData
  },
  setup() {
    const toast = useToast();
    return { toast };
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
      sortOrder: 'asc', //ordenação
      zipFiles: [],
      selectedZipFile: "",
      zipFileData: {},
      sheetNames: [],
      selectedSheet: '',
      workbook: null, // novo para armazenar workbook excel
    };
  },
  computed: {
    visibleRows() {
      const start = (this.currentPage - 1) * this.maxRows;
      const end = start + this.maxRows;
      return this.sortedTable.slice(start, end);
    },
    totalPages() {
      return Math.ceil(this.rowCount / this.maxRows);
    },
    sortedTable() {
      if (this.sortColumnIndex === null) return this.tableData;

      const sortedData = [...this.tableData];
      sortedData.sort((a, b) => {
        const aValue = a[this.sortColumnIndex];
        const bValue = b[this.sortColumnIndex];

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
          this.workbook = XLSX.read(data, { type: "array" });
          this.sheetNames = this.workbook.SheetNames;
          this.selectedSheet = this.sheetNames[0] || '';
          const sheetName = this.workbook.SheetNames[0];
          const sheet = this.workbook.Sheets[sheetName];
          const jsonData = XLSX.utils.sheet_to_json(sheet, { header: 1 });

          if (jsonData.length) {
            this.headers = jsonData[0];
            this.tableData = jsonData.slice(1).map(row =>
              row.map((cell, index) => this.convertExcelDate(cell, this.headers[index]))
            );
            this.originalData = JSON.parse(JSON.stringify(this.tableData));
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

    loadSelectedSheet() {
      if (!this.workbook || !this.selectedSheet) return;
      const sheet = this.workbook.Sheets[this.selectedSheet];
      const jsonData = XLSX.utils.sheet_to_json(sheet, { header: 1 });
      if (jsonData.length) {
        this.headers = jsonData[0];
        this.tableData = jsonData.slice(1).map(row =>
          row.map((cell, index) => this.convertExcelDate(cell, this.headers[index]))
        );
        this.originalData = JSON.parse(JSON.stringify(this.tableData));
        this.rowCount = this.tableData.length;
        this.columnCount = this.headers.length;
        this.tableName = this.selectedSheet;
      }
    },

    processJSONFile(file) {
      const reader = new FileReader();

      reader.onload = (e) => {
        try {
          let jsonString = e.target.result.trim();

          console.log("JSON recebido:", jsonString);
          if (!jsonString.startsWith("[") && !jsonString.endsWith("]")) {
            jsonString = "[" + jsonString.replace(/}\s*{/g, "},{") + "]";
          }

          const jsonData = JSON.parse(jsonString);

          console.log("JSON convertido com sucesso:", jsonData);

          if (Array.isArray(jsonData) && jsonData.length > 0) {
            this.headers = Object.keys(jsonData[0]);
            this.tableData = jsonData.map(obj => Object.values(obj));
            this.originalData = JSON.parse(JSON.stringify(this.tableData));
            this.rowCount = this.tableData.length;
            this.columnCount = this.headers.length;
            this.tableName = file.name.replace(/\.[^/.]+$/, "");
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

    convertExcelDate(value, columnName = '') {
      const col = columnName.trim().toLowerCase();

      const allowedDateCols = ['datacadastro', 'data nascimento', 'ultimacompra', 'datavenda'];
      const isDateCol = allowedDateCols.includes(col.replace(/\s+/g, ''));

      if (typeof value === 'number' && isDateCol) {
        if (value > 25569 && value < 60000) {
          const date = new Date((value - 25569) * 86400 * 1000);
          return this.formatDate(date);
        }
      }

      if (typeof value === 'string') {
        if (isDateCol && !isNaN(value)) {
          const num = parseFloat(value);
          if (num > 25569 && num < 60000) {
            const date = new Date((num - 25569) * 86400 * 1000);
            return this.formatDate(date);
          }
        }

        const parsed = Date.parse(value);
        if (isDateCol && !isNaN(parsed)) {
          const date = new Date(parsed);
          return this.formatDate(date);
        }
      }

      return value;
    },

    formatDate(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    }
    ,

    sortTable(index) {
      if (this.sortColumnIndex === index) {
        this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortColumnIndex = index;
        this.sortOrder = 'asc';
      }
    },

    resetSorting() {
      this.tableData = JSON.parse(JSON.stringify(this.originalData));
      this.sortColumnIndex = null;
      this.sortOrder = 'asc';
      this.currentPage = 1;
    },
    openMapDialog() {
      this.showMapDialog = true;
    },

    closeMapDialog() {
      this.showMapDialog = false;
    },

    handleMapSave() {
      this.showMapDialog = false;
      this.tableData = [];
      this.originalData = [];
      this.headers = [];
      this.rowCount = 0;
      this.columnCount = 0;
      this.fileName = "";
      this.tableName = "";
      this.sheetNames = [];
      this.selectedSheet = "";

      this.toast.success('Tabela importada com sucesso!');
      //setTimeout(() => window.location.reload(), 1000);
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
  width: 100%;
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

.fixed {
  z-index: 100;
}

.sticky {
  position: sticky;
}

@keyframes toast-in {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(20px);
  }

  to {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
}

@keyframes toast-out {
  from {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }

  to {
    opacity: 0;
    transform: translateX(-50%) translateY(20px);
  }
}

.animate-toast-in {
  animation: toast-in 0.4s ease-out forwards;
}

.animate-toast-out {
  animation: toast-out 0.4s ease-in forwards;
}
</style>