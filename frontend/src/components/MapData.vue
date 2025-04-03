<template>
  <div>
    <div class="flex flex-wrap items-end justify-between gap-4 mb-2">
      <div>
        <h5 class="font-semibold">A tabela {{ tableName }} corresponde a
          <select v-model="selectedTable" class="border p-2 rounded w-full">
            <option value="">Selecione uma tabela</option>
            <option v-for="table in availableTables" :key="table" :value="table">
              {{ table }}
            </option>
          </select>
        </h5>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full border-collapse border border-gray-300">
        <thead>
          <tr class="bg-gray-200">
            <th class="border border-gray-300 px-4 py-2">Coluna Original</th>
            <th class="border border-gray-300 px-4 py-2">Coluna SmartCRM</th>
            <th class="border border-gray-300 px-4 py-2">Tipo de Dado</th>
            <th class="border border-gray-300 px-4 py-2">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(header, index) in headers" :key="index" :class="{'underline text-red-500': rejectedColumns[index]}">
            <td class="border border-gray-300 px-4 py-2">{{ header }}</td>
            <td class="border border-gray-300 px-4 py-2">
              <select v-model="mappedColumns[index]" class="border p-1 rounded w-full" :disabled="!selectedTable || rejectedColumns[index]">
                <option value="">Selecione a coluna</option>
                <option v-for="column in tableColumns" :key="column" :value="column">
                  {{ column }}
                </option>
              </select>
            </td>
            <td class="border border-gray-300 px-4 py-2">
              <select v-model="columnTypes[index]" class="border p-1 rounded" :disabled="rejectedColumns[index]">
                <option value="text">Texto</option>
                <option value="number">Número</option>
                <option value="date">Data</option>
                <option value="boolean">Booleano</option>
              </select>
            </td>
            <td class="border border-gray-300 px-4 py-2">
              <button @click.stop="previewColumn(index)"
                class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                Pré-visualizar
              </button>
              <button @click.stop="toggleColumnState(index)"
                class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs mt-2">
                {{ rejectedColumns[index] ? 'Aceitar coluna' : 'Descartar coluna' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="previewData.length > 0" class="mt-6">
      <h4 class="font-semibold mb-2">Pré-visualização dos Dados</h4>
      <div class="overflow-x-auto">
        <table class="min-w-full border-collapse border border-gray-300">
          <thead>
            <tr class="bg-gray-200">
              <th v-for="(header, index) in previewHeaders" :key="index"
                class="border border-gray-300 px-4 py-2">
                {{ header }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, rowIndex) in previewData.slice(0, 5)" :key="rowIndex">
              <td v-for="(cell, cellIndex) in row" :key="cellIndex"
                class="border border-gray-300 px-4 py-2">
                {{ cell }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p class="text-sm text-gray-500 mt-2">Amostra de 5 de {{ previewData.length }} linhas</p>
    </div>

    <div class="mt-6 flex justify-end gap-2">
      <button @click="cancel" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">
        Cancelar
      </button>
      <button @click="applyMapping" class="px-4 py-2 bg-green-600 text-black rounded-lg hover:bg-green-700">
        Aplicar Mapeamento
      </button>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  props: {
    headers: Array,
    tableData: Array,
    tableName: String
  },
  data: () => ({
    selectedTable: "",
    availableTables: [],
    tableColumns: [],
    mappedColumns: [],
    columnTypes: [],
    rejectedColumns: [],
    previewData: [],
    previewHeaders: []
  }),
  async created() {
    this.initMappings();
    await this.loadAvailableTables();
  },
  methods: {
    initMappings() {
      this.columnTypes = this.headers.map(() => 'text');
      this.mappedColumns = this.headers.map(() => '');
      this.rejectedColumns = this.headers.map(() => false); // Marca todas as colunas como não rejeitadas inicialmente
    },

    async apiRequest(config) {
      try {
        const response = await axios({
          ...config,
          headers: {
            Authorization: `Bearer ${localStorage.getItem('token')}`,
            ...config.headers
          }
        });
        return response.data;
      } catch (error) {
        console.error(`Erro na requisição ${config.url}:`, error.response || error);
        throw error;
      }
    },

    async loadAvailableTables() {
      try {
        const tables = await this.apiRequest({ url: '/available-tables' });
        this.availableTables = tables.filter(table => 
          !['users', 'password_resets', 'migrations'].includes(table) &&
          !table.startsWith('oauth_')
        );
      } catch (error) {
        alert("Erro ao carregar tabelas. Verifique o console.");
      }
    },

    async loadTableColumns(tableName) {
      try {
        this.tableColumns = await this.apiRequest({ 
          url: `/table-columns/${tableName}` 
        });
      } catch (error) {
        this.tableColumns = [];
      }
    },

    previewColumn(index) {
      this.previewHeaders = [
        this.headers[index],
        this.mappedColumns[index] || '(Não mapeado)',
        this.columnTypes[index]
      ];
      
      this.previewData = this.tableData.map(row => [
        row[index],
        this.mappedColumns[index] ? `[${this.mappedColumns[index]}]` : '',
        `[${this.columnTypes[index]}]`
      ]);
    },

    toggleColumnState(index) {
      if (this.rejectedColumns[index]) {
        // Aceitar a coluna, restaurar o mapeamento e tipo de dado
        this.rejectedColumns[index] = false;
        this.mappedColumns[index] = ''; // Limpa o mapeamento
        this.columnTypes[index] = 'text'; // Restaura o tipo de dado para texto
      } else {
        // Rejeitar a coluna
        this.rejectedColumns[index] = true;
        this.mappedColumns[index] = ''; // Limpa o mapeamento
        this.columnTypes[index] = 'text'; // Restaura o tipo de dado para texto
      }
    },

    async applyMapping() {
      const mappedData = {
        target_table: this.selectedTable,
        mappings: this.headers.map((header, index) => ({
          csv_column: header,
          db_column: this.mappedColumns[index],
          data_type: this.columnTypes[index]
        })),
        rows: this.tableData.map(row => {
          const mappedRow = {};
          this.headers.forEach((header, index) => {
            if (this.mappedColumns[index] && !this.rejectedColumns[index]) {
              mappedRow[this.mappedColumns[index]] = row[index];
            }
          });
          return mappedRow;
        })
      };

      try {
        await this.apiRequest({
          method: 'post',
          url: '/import/mapped-data',
          data: mappedData
        });
        this.$emit('close');
      } catch (error) {
        alert('Erro ao importar: ' + (error.response?.data?.error || error.message));
      }
    },

    cancel() {
      this.$emit('close');
    }
  },
  watch: {
    selectedTable(newTable) {
      if (newTable) this.loadTableColumns(newTable);
      else this.tableColumns = [];
    }
  }
};
</script>
