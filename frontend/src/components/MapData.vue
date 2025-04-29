<template>
  <div>
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
          <tr v-for="(header, index) in headers" :key="index"
            :class="{ 'underline text-red-500': rejectedColumns[index] }">
            <td class="border border-gray-300 px-4 py-2">{{ header }}</td>
            <td class="border border-gray-300 px-4 py-2">
              <select v-model="mappedColumns[index]" class="border p-1 rounded w-full"
                :disabled="!selectedTable || rejectedColumns[index]">
                <option value="">Selecione a coluna</option>

                <optgroup label="Identificação do Cliente">
                  <option value="customer_identifier">Identificador do Cliente</option>
                  <option value="name">Nome</option>
                  <option value="email">Email</option>
                  <option value="phone">Telefone</option>
                </optgroup>

                <optgroup label="Localização">
                  <option value="region">Região</option>
                  <option value="country">País</option>
                  <option value="city">Cidade</option>
                </optgroup>

                <optgroup label="Produto ou Serviço">
                  <option value="item_identifier">Identificador do Produto</option>
                  <option value="item_name">Nome do Produto</option>
                  <option value="item_category">Categoria</option>
                  <option value="item_subcategory">Subcategoria</option>
                  <option value="item_price">Preço</option>
                </optgroup>

                <optgroup label="Transação">
                  <option value="transaction_identifier">Identificador da Transação</option>
                  <option value="transaction_date">Data da Transação</option>
                  <option value="quantity">Quantidade</option>
                  <option value="total_amount">Valor Total</option>
                  <option value="purchase_channel">Canal de Compra</option>
                  <option value="payment_method">Método de Pagamento</option>
                </optgroup>

                <optgroup label="Atributos Flexíveis">
                  <option value="attribute_1">Atributo 1</option>
                  <option value="attribute_2">Atributo 2</option>
                  <option value="attribute_3">Atributo 3</option>
                  <option value="attribute_4">Atributo 4</option>
                  <option value="attribute_5">Atributo 5</option>
                  <option value="numeric_attribute_1">Atributo Numérico 1</option>
                  <option value="numeric_attribute_2">Atributo Numérico 2</option>
                  <option value="numeric_attribute_3">Atributo Numérico 3</option>
                </optgroup>
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
              <button @click.stop="previewColumn(index)" class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
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
              <th v-for="(header, index) in previewHeaders" :key="index" class="border border-gray-300 px-4 py-2">
                {{ header }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, rowIndex) in previewData.slice(0, 5)" :key="rowIndex">
              <td v-for="(cell, cellIndex) in row" :key="cellIndex" class="border border-gray-300 px-4 py-2">
                {{ cell }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p class="text-sm text-gray-500 mt-2">Amostra de 5 de {{ previewData.length }} linhas</p>
    </div>

    <div class="flex items-center gap-2 mb-2 p-4 rounded bg-white shadow">
      <span class="font-semibold whitespace-nowrap">Os dados serão importados para a campanha</span>
      <select v-model="campaign_id" class="border p-2 rounded min-w-[200px]">
        <option value="">Selecione uma campanha</option>
        <option v-for="campaign in campaigns" :key="campaign.id" :value="campaign.id">
          {{ campaign.name }}
        </option>
      </select>
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
    selectedTable: "datasets",
    tableColumns: [],
    mappedColumns: [],
    columnTypes: [],
    rejectedColumns: [],
    previewData: [],
    previewHeaders: [],
    campaign_id: "",
    campaigns: []
  }),
  async created() {
    this.initMappings();
    await this.loadTableColumns(); // já usa 'datasets'
    await this.loadCampaigns();
  },
  methods: {
    initMappings() {
      const smartMappingKeywords = {
        customer_identifier: ['id', 'cliente', 'nº cliente', 'identificador'],
        name: ['nome', 'cliente', 'utilizador', 'user', 'name'],
        email: ['email', 'e-mail'],
        phone: ['telefone', 'telemóvel', 'contacto'],
        region: ['região', 'regiao'],
        country: ['país', 'pais'],
        city: ['cidade'],
        gender: ['sexo', 'género', 'genero'],
        date_of_birth: ['data nascimento', 'nascimento', 'dob'],
        item_identifier: ['id produto', 'id item', 'produto', 'sku'],
        item_name: ['nome produto', 'descrição', 'descricao'],
        item_category: ['categoria'],
        item_subcategory: ['subcategoria'],
        item_price: ['preço', 'valor unitário'],
        transaction_identifier: ['transação', 'transacao', 'id transação', 'id compra'],
        transaction_date: ['data', 'data compra', 'data transação'],
        quantity: ['quantidade', 'qtd'],
        total_amount: ['total', 'valor total', 'montante'],
        purchase_channel: ['canal', 'origem'],
        payment_method: ['pagamento', 'método pagamento'],
        attribute_1: ['nota', 'comentário', 'observações'],
        numeric_attribute_1: ['pontuação', 'score']
      };

      this.mappedColumns = this.headers.map((header) => {
        const normalizedHeader = header.toLowerCase().trim();
        for (const [targetField, keywords] of Object.entries(smartMappingKeywords)) {
          if (keywords.some(keyword => normalizedHeader.includes(keyword))) {
            return targetField;
          }
        }
        return '';
      });

      this.columnTypes = this.headers.map((header, index) => {
        const values = this.tableData.map(row => (row[index] || '').toString().trim().toLowerCase());

        const isDate = values.every(val => val === '' || /^\d{4}-\d{2}-\d{2}$/.test(val) || /^\d{2}\/\d{2}\/\d{4}$/.test(val));
        if (isDate) return 'date';

        const isNumber = values.every(val => val === '' || !isNaN(val));
        if (isNumber) return 'number';

        const isBoolean = values.every(val => ['true', 'false', 'sim', 'não', 'yes', 'no', '1', '0', ''].includes(val));
        if (isBoolean) return 'boolean';

        return 'text';
      });

      this.rejectedColumns = this.headers.map(() => false);
      this.$emit('showToast', {
        type: 'info',
        message: 'Mapeamento automático sugerido com base nas colunas detectadas.'
      });
    }
    ,

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
    async loadCampaigns() {
      try {
        this.campaigns = await this.apiRequest({ url: '/campaigns' });
      } catch (error) {
        console.error("Erro ao carregar campanhas:", error);
      }
    },
    async loadTableColumns() {
      try {
        this.tableColumns = await this.apiRequest({
          url: `/table-columns/datasets`
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
      const rows = this.tableData.map(row => {
        const mappedRow = {};
        this.headers.forEach((header, index) => {
          if (this.mappedColumns[index] && !this.rejectedColumns[index]) {
            mappedRow[this.mappedColumns[index]] = row[index];
          }
        });
        return mappedRow;
      });
      const payload = {
        campaign_id: this.campaign_id,
        data: rows
      };

      try {
        await this.apiRequest({
          method: 'post',
          url: '/import/mapped-data',
          data: payload
        });
        this.$emit('close');
      } catch (error) {
        alert('Erro ao importar: ' + (error.response?.data?.error || error.message));
      }
    }
    ,
    cancel() {
      this.$emit('close');
    }
  },
};
</script>
