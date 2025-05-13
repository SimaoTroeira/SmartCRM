<template>
  <div class="flex flex-col px-4 pt-0">
    <!-- Empresa e Nome -->
    <div class="inline-flex items-center gap-4 mt-2">
      <div class="flex flex-col">
        <label class="font-bold mb-1">Nome da Tabela:</label>
        <input type="text" v-model="localTableName" class="w-64 p-2 border rounded text-center">
      </div>
      <div class="flex flex-col">
        <label class="font-bold mb-1">Empresa:</label>
        <select v-model="selectedCompanyId" class="w-64 p-2 border rounded">
          <option v-for="company in companies" :key="company.id" :value="company.id">
            {{ company.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Wizard de validação -->
    <div v-if="selectedCompanyId" class="mt-4 max-w-4xl">
      <AlgorithmWizard :campanha-id="selectedCompanyId" algoritmo="rfm" mostrar-so-card />
    </div>

    <!-- Tabela de Mapeamento -->
    <div class="overflow-x-auto mb-4 mt-4">
      <table class="min-w-full border-collapse border border-gray-300">
        <thead class="bg-gray-100">
          <tr>
            <th v-for="(header, index) in headers" :key="'header-' + index"
              :class="['px-4 py-2 border text-center align-top', rejectedColumns[index] ? 'coluna-rejeitada' : '']"
              style="vertical-align: top; position: relative;">
              <div class="flex flex-col gap-1 items-stretch mt-2">
                <label class="text-left text-xs mt-1">Nome da Coluna:</label>
                <input type="text" v-model="mappedColumns[index]" class="p-1 border rounded w-full text-center"
                  :disabled="rejectedColumns[index]">
                <label class="text-left text-xs mt-2">Tipo de Dado:</label>
                <select v-model="columnTypes[index]" class="p-1 border rounded w-full"
                  :class="{ 'coluna-rejeitada': rejectedColumns[index] }" :disabled="rejectedColumns[index]"
                  @change="autoDetectType(index)">
                  :disabled="rejectedColumns[index]" @change="autoDetectType(index)">
                  <option value="text">Texto</option>
                  <option value="number">Número</option>
                  <option value="date">Data</option>
                  <option value="boolean">Booleano</option>
                </select>
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="rowIndex in previewRowCount" :key="'row-' + rowIndex">
            <td v-for="(cell, colIndex) in tableData[rowIndex - 1]" :key="'cell-' + rowIndex + '-' + colIndex"
              class="border px-4 py-2 text-center" :class="rejectedColumns[colIndex] ? 'coluna-rejeitada' : ''">
              {{ cell }}
            </td>
          </tr>
          <tr>
            <td v-for="(header, index) in headers" :key="'footer-' + index" class="border text-center">
              <button @click="toggleColumnState(index)" class="text-sm">
                <span v-if="!rejectedColumns[index]" class="text-red-600 hover:text-red-800">&times;</span>
                <span v-else class="text-green-600 hover:text-green-800">&#8634;</span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Ações -->
    <div class="mt-10 flex justify-end gap-2">
      <button @click="cancel" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancelar</button>
      <button @click="applyMapping" class="px-4 py-2 bg-green-600 text-black rounded hover:bg-green-700">
        Guardar Tabela
      </button>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { useToast } from 'vue-toastification';
import AlgorithmWizard from './AlgorithmWizard.vue';

const toast = useToast();

export default {
  components: { AlgorithmWizard },
  props: {
    headers: Array,
    tableData: Array,
    tableName: String,
  },
  data() {
    return {
      localTableName: this.tableName,
      mappedColumns: [],
      columnTypes: [],
      rejectedColumns: [],
      previewRowCount: 5,
      companies: [],
      selectedCompanyId: null,
    };
  },
  created() {
    this.initMapping();
    this.loadCompanies();
  },
  methods: {
    async loadCompanies() {
      try {
        const response = await axios.get('/user/companies', {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('token')}`,
          },
        });

        this.companies = response.data;

        const lastCompanyId = localStorage.getItem('lastSelectedCompanyId');

        if (lastCompanyId && this.companies.some(c => c.id === parseInt(lastCompanyId))) {
          this.selectedCompanyId = parseInt(lastCompanyId);
        } else if (this.companies.length > 0) {
          this.selectedCompanyId = this.companies[0].id;
        }
      } catch (err) {
        console.error('Erro ao carregar empresas:', err);
      }
    },
    initMapping() {
      this.mappedColumns = this.headers.map(h => h);
      this.rejectedColumns = this.headers.map(() => false);
      this.columnTypes = this.headers.map((_, index) => this.detectType(index));
    },
    detectType(index) {
      const values = this.tableData.map(row => (row[index] || '').toString().toLowerCase().trim());
      const isDate = values.every(v => v === '' || /^\d{4}-\d{2}-\d{2}$/.test(v) || /^\d{2}\/\d{2}\/\d{4}$/.test(v));
      if (isDate) return 'date';
      const isNumber = values.every(v => v === '' || !isNaN(Number(v)));
      if (isNumber) return 'number';
      const isBoolean = values.every(v => ['true', 'false', '1', '0', 'sim', 'nao', 'não', 'yes', 'no'].includes(v));
      if (isBoolean) return 'boolean';
      return 'text';
    },
    autoDetectType(index) {
      this.columnTypes[index] = this.detectType(index);
    },
    toggleColumnState(index) {
      this.rejectedColumns[index] = !this.rejectedColumns[index];
    },
    async applyMapping() {
      if (!this.selectedCompanyId) {
        toast.error('Selecione uma empresa válida.');
        return;
      }
      const rows = this.tableData.map(row => {
        const obj = {};
        this.headers.forEach((header, index) => {
          if (!this.rejectedColumns[index]) {
            obj[this.mappedColumns[index]] = row[index];
          }
        });
        return obj;
      });
      try {
        await axios.post('/import/mapped-data', {
          table_name: this.localTableName,
          data: rows,
          types: this.columnTypes,
          company_id: this.selectedCompanyId,
        }, {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('token')}`,
          }
        });
        this.$emit('close');
      } catch (err) {
        alert('Erro ao guardar: ' + err.message);
      }
      localStorage.setItem('lastSelectedCompanyId', this.selectedCompanyId);
    },

    cancel() {
      this.$emit('close');
    },
  }
};
</script>

<style scoped>
.coluna-rejeitada {
  background-color: #fee2e2 !important;
}
</style>