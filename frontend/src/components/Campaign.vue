<template>
  <div>
    <h2 class="text-xl font-semibold mb-4">As minhas campanhas</h2>

    <!-- Botão de criar campanha -->
    <div class="mb-4">
      <button @click="showDialog = true" class="btn btn-primary" :disabled="companies.length === 0"
        :class="{ 'opacity-50 cursor-not-allowed': companies.length === 0 }">
        Criar nova campanha
      </button>
    </div>

    <!-- Mensagem caso não haja campanhas -->
    <div v-if="campaigns.length === 0" class="text-gray-500 mb-4">
      Você não tem campanhas ainda.
    </div>

    <!-- Tabela de campanhas -->
    <div v-else class="overflow-x-auto">
      <table class="min-w-full table-auto border border-gray-200">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-4 py-2 border">Título</th>
            <th class="px-4 py-2 border">Descrição</th>
            <th class="px-4 py-2 border">Empresa</th>
            <th class="px-4 py-2 border">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="campaign in campaigns" :key="campaign.id" class="hover:bg-gray-50">
            <td class="px-4 py-2 border">{{ campaign.name }}</td>
            <td class="px-4 py-2 border">{{ campaign.description }}</td>
            <td class="px-4 py-2 border">{{ campaign.company.name }}</td>
            <td class="px-4 py-2 border text-center">
              <button @click="editCampaign(campaign)" class="btn btn-secondary">Editar</button>
              <button @click="confirmDeleteCampaign(campaign.id)" class="btn btn-danger">Apagar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal de criar campanha -->
    <div v-if="showDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg shadow-lg w-96 border-9 border-gray-500">
        <h3 class="text-lg font-bold mb-4">Criar nova campanha</h3>
        <form @submit.prevent="createCampaign">
          <div class="mb-3">
            <label class="block font-medium">Título</label>
            <input v-model="form.name" class="form-control" required />
          </div>
          <div class="mb-3">
            <label class="block font-medium">Descrição</label>
            <textarea v-model="form.description" class="form-control" required></textarea>
          </div>
          <div class="mb-3">
            <label class="block font-medium">Empresa</label>
            <select v-model="form.company_id" class="form-control" required>
              <option disabled value="">Selecione uma empresa</option>
              <option v-for="company in companies" :key="company.id" :value="company.id">
                {{ company.name }}
              </option>
            </select>
          </div>
          <div class="flex justify-end gap-2">
            <button type="button" @click="showDialog = false" class="btn btn-secondary">Cancelar</button>
            <button type="submit" class="btn btn-success">Criar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal de Edição -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg shadow-lg w-96 border-9 border-gray-500">
        <h3 class="text-lg font-bold mb-4">Editar Campanha</h3>
        <form @submit.prevent="updateCampaign">
          <div class="mb-3">
            <label class="block font-medium">Título</label>
            <input v-model="form.name" class="form-control" required />
          </div>
          <div class="mb-3">
            <label class="block font-medium">Descrição</label>
            <textarea v-model="form.description" class="form-control" required></textarea>
          </div>
          <div class="mb-3">
            <label class="block font-medium">Empresa</label>
            <select v-model="form.company_id" class="form-control" required>
              <option disabled value="">Selecione uma empresa</option>
              <option v-for="company in companies" :key="company.id" :value="company.id">
                {{ company.name }}
              </option>
            </select>
          </div>
          <div class="flex justify-end gap-2">
            <button type="button" @click="showEditModal = false" class="btn btn-secondary">Cancelar</button>
            <button type="submit" class="btn btn-success">Salvar alterações</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal de Exclução -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg shadow-lg w-96 border-9 border-gray-500">
        <h3 class="text-lg font-bold mb-4">Tem certeza que deseja apagar esta campanha?</h3>
        <div class="flex justify-end gap-2">
          <button type="button" @click="closeDeleteModal" class="btn btn-secondary">Cancelar</button>
          <button type="button" @click="deleteCampaign" class="btn btn-danger">Apagar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const toast = useToast();
const showDialog = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const campaigns = ref([]);
const companies = ref([]);
const form = ref({
  name: '',
  description: '',
  company_id: ''
});
const campaignToDelete = ref(null); // Para armazenar a campanha a ser excluída
const selectedCampaignId = ref(null); // Variável para armazenar o ID da campanha selecionada

const STATUS_ATIVO = 'ativo';


// Carregar campanhas
const fetchCampaigns = async () => {
  try {
    const res = await axios.get('http://127.0.0.1:8000/api/campaigns');
    campaigns.value = Array.isArray(res.data) ? res.data : [];
  } catch (error) {
    toast.error('Erro ao carregar campanhas.');
    console.error(error);
  }
};

// Carregar empresas
const fetchCompanies = async () => {
  try {
    const res = await axios.get('http://127.0.0.1:8000/api/companies');
    companies.value = Array.isArray(res.data) ? res.data : [];
  } catch (error) {
    toast.error('Erro ao carregar empresas.');
    console.error(error);
  }
};

// Criar campanha
const createCampaign = async () => {
  try {
    const selectedCompany = companies.value.find(c => c.id === form.value.company_id);
    if (!selectedCompany || selectedCompany.status !== STATUS_ATIVO) {
      toast.error('Apenas empresas ativas podem ter campanhas.');
      return;
    }

    await axios.post('http://127.0.0.1:8000/api/campaigns', form.value);
    toast.success('Campanha criada com sucesso!');
    showDialog.value = false;
    form.value = { name: '', description: '', company_id: '' }; // Limpar o formulário
    fetchCampaigns(); // Atualiza as campanhas na lista
  } catch (error) {
    toast.error('Erro ao criar campanha.');
    console.error(error);
  }
};


// Editar campanha
const editCampaign = (campaign) => {
  selectedCampaignId.value = campaign.id; // Definir o ID da campanha selecionada
  form.value = { name: campaign.name, description: campaign.description, company_id: campaign.company.id };
  showEditModal.value = true; // Mostrar o modal de edição
};


// Atualizar campanha
const updateCampaign = async () => {
  try {
    if (selectedCampaignId.value) {
      // Envia os dados da campanha para o backend com o ID correto
      await axios.put(`http://127.0.0.1:8000/api/campaigns/${selectedCampaignId.value}`, form.value);
      toast.success('Campanha atualizada com sucesso!');
      showEditModal.value = false; // Fechar o modal de edição
      form.value = { name: '', description: '', company_id: '' }; // Limpar o formulário
      fetchCampaigns(); // Atualiza as campanhas na lista
    } else {
      toast.error('ID da campanha não encontrado.');
    }
  } catch (error) {
    toast.error('Erro ao atualizar campanha.');
    console.error(error);
  }
};



// Confirmar exclusão de campanha
const confirmDeleteCampaign = (campaignId) => {
  campaignToDelete.value = campaignId;
  showDeleteModal.value = true; // Mostrar o modal de exclusão
};

// Excluir campanha
const deleteCampaign = async () => {
  try {
    await axios.delete(`http://127.0.0.1:8000/api/campaigns/${campaignToDelete.value}`);
    toast.success('Campanha excluída com sucesso!');
    showDeleteModal.value = false;
    fetchCampaigns(); // Atualiza as campanhas na lista
  } catch (error) {
    toast.error('Erro ao excluir campanha.');
    console.error(error);
  }
};

// Fechar modal de exclusão
const closeDeleteModal = () => {
  showDeleteModal.value = false;
  campaignToDelete.value = null; // Limpar a campanha selecionada
};

onMounted(() => {
  fetchCompanies();
  fetchCampaigns();
});
</script>

<style scoped>
/* Reaproveita os estilos de Company.vue */
.btn-primary {
  background-color: #1470ea;
  color: white;
}

.btn-primary:hover {
  background-color: #004add;
}

.btn-success {
  background-color: #28a745;
  color: white;
}

.btn-success:hover {
  background-color: #218838;
}

.btn-secondary {
  background-color: #3659f4;
  color: white;
}

.btn-secondary:hover {
  background-color: #357be5;
}

.form-control {
  width: 100%;
  padding: 8px 12px;
  margin-top: 4px;
  border-radius: 4px;
  border: 1px solid #ddd;
}

/* Estilos para o modal */
.fixed {
  position: fixed;
}

.inset-0 {
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

.bg-black {
  background-color: rgba(0, 0, 0, 0.5);
  /* 50% de opacidade */
}

.flex {
  display: flex;
}

.items-center {
  align-items: center;
}

.justify-center {
  justify-content: center;
}

.z-50 {
  z-index: 50;
}

.transition-all {
  transition: all 0.3s ease-in-out;
}

.duration-300 {
  transition-duration: 300ms;
}

.ease-in-out {
  transition-timing-function: ease-in-out;
}

.max-w-lg {
  max-width: 32rem;
  /* 512px */
}

/* Garantir borda arredondada e visível nas modais */
.bg-white {
  border-radius: 12px;
  /* Aumentando o arredondamento */
  border: 30px solid rgb(255, 255, 255);
  /* Borda de 9px */
}
</style>
