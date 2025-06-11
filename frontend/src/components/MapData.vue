<template>
  <div class="flex flex-col px-4 pt-0">
    <!-- Nome da Tabela (readonly) -->
    <div class="inline-flex items-center gap-4 mt-4">
      <div class="flex flex-col">
        <label class="font-bold mb-1">Quer importar a tabela </label>
        <input type="text" :value="tableName" class="w-64 p-2 border rounded text-center bg-gray-100" readonly>
      </div>
    </div>

    <!-- Tipo de Ficheiro -->
    <div class="inline-flex items-center gap-4 mt-4">
      <div class="flex flex-col">
        <label class="font-bold mb-1">Qual destes ficheiros corresponde ao que importou? </label>
        <select v-model="fileType" class="w-64 p-2 border rounded">
          <option disabled value="">-- Escolha o tipo --</option>
          <option value="vendas">Vendas</option>
          <option value="clientes">Clientes</option>
          <option value="produtos">Produtos</option>
        </select>
      </div>
    </div>

    <!-- Empresa e Nome Final -->
    <div class="inline-flex items-center gap-4 mt-6">
      <div class="flex flex-col">
        <label class="font-bold mb-1">Empresa:</label>
        <select v-model="selectedCompanyId" class="w-64 p-2 border rounded">
          <option v-for="company in companies" :key="company.id" :value="company.id">
            {{ company.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Bloco condicional: mostra as colunas esperadas por tipo de ficheiro -->
    <div v-if="fileType" class="bg-white p-4 rounded-md shadow-sm border border-gray-200 mb-6 max-w-3xl mt-4">
      <h5 class="text-lg font-semibold mb-2">Tabelas e colunas esperadas:</h5>
      <ul class="ml-4 list-disc text-gray-600 text-sm">
        <li v-if="fileType === 'vendas'">
          📄 <strong>vendas</strong>
          <ul class="ml-4 list-disc">
            <li>ClienteID</li>
            <li>ValorTotal</li>
            <li>DataVenda</li>
            <li>ProdutoID</li>
          </ul>
        </li>
        <li v-else-if="fileType === 'clientes'">
          📄 <strong>clientes</strong>
          <ul class="ml-4 list-disc">
            <li>ClienteID</li>
            <li>DataCadastro</li>
            <li>Regiao</li>
          </ul>
        </li>
        <li v-else-if="fileType === 'produtos'">
          📄 <strong>produtos</strong>
          <ul class="ml-4 list-disc">
            <li>ProdutoID</li>
            <li>NomeProduto</li>
            <li>Categoria</li>
            <li>Marca</li>
          </ul>
        </li>
      </ul>

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
                  :class="{ 'coluna-rejeitada': rejectedColumns[index] }" :disabled="rejectedColumns[index]">
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

const monthMap = [
  'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
  'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'
];

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
      fileType: '',
      startMonth: '',
      endMonth: '',
      onlyYear: '',
      prepararNormalizacao: false,
      faltas: null,
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
          headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
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
      if (!this.selectedCompanyId || !this.fileType) {
        toast.error('Preencha todos os campos obrigatórios.');
        return;
      }
      this.prepararNormalizacao = true;
      const rows = this.tableData.map(row => {
        const obj = {};
        this.headers.forEach((header, index) => {
          if (!this.rejectedColumns[index]) {
            obj[this.mappedColumns[index]] = row[index];
          }
        });
        return obj;
      });

      const nomeFinal = this.gerarNomeFicheiro();

      try {
        await axios.post('/import/mapped-data', {
          table_name: nomeFinal,
          data: rows,
          types: this.columnTypes,
          company_id: this.selectedCompanyId,
        }, {
          headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
        });

        this.$emit('close');
      } catch (err) {
        alert('Erro ao guardar: ' + err.message);
      }

      localStorage.setItem('lastSelectedCompanyId', this.selectedCompanyId);
    },
    gerarNomeFicheiro() {
      return this.fileType;
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