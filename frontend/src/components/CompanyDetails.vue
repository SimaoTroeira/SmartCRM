<template>
  <div class="company">
    <div v-if="roleLoaded">
      <!-- Título -->
      <h2 class="text-2xl font-bold mb-4 text-left">
        {{ companyName ? companyName : 'Detalhes da Empresa' }}
      </h2>

      <!-- Detalhes da empresa -->
      <div class="mb-6">
        <p><strong>Nome:</strong> {{ company?.name }}</p>
        <p><strong>Setor:</strong> {{ company?.sector }}</p>
        <p><strong>Status:</strong> {{ company?.status }}</p>
        <p><strong>Rascunho:</strong> {{ company?.draft ? 'Sim' : 'Não' }}</p>
      </div>

      <!-- Campanhas -->
      <div class="mb-6">
        <h3 class="text-lg font-semibold">Campanhas</h3>
        <ul>
          <li v-for="campaign in company?.campaigns" :key="campaign.id" class="mb-2">
            <p>
              <router-link :to="{ name: 'CampaignDetails', params: { id: campaign.id } }"
                class="text-blue-600 underline hover:text-blue-800 cursor-pointer">
                <strong>{{ campaign.name }}</strong>
              </router-link>
              ({{ campaign.status }})
            </p>
            <p>{{ campaign.description }}</p>
            <p>
              {{ campaign.created_at ? new Date(campaign.created_at).toLocaleDateString() : 'Sem data de início' }} -
              {{ campaign.end_date ? new Date(campaign.end_date).toLocaleDateString() : 'Sem data de fim' }}
            </p>
          </li>
        </ul>
      </div>


      <!-- Utilizadores -->
      <div class="mb-6">
        <h3 class="text-lg font-semibold">Utilizadores</h3>
        <ul>
          <li v-for="ucr in company?.user_company_roles" :key="ucr.id">
            {{ ucr.user.name }} - {{ ucr.role.code }}
          </li>
        </ul>
      </div>

      <!-- Botões de ação -->
      <div class="flex gap-4 mt-4">
        <!-- Colaborador -->
        <button v-if="userRole === 'CA'" @click="openEditModal(company)" class="btn-edit text-white px-4 py-2 rounded">
          Editar
        </button>
        <button v-if="userRole === 'CA'" @click="openDeleteModal(company.id)"
          class="btn-remove text-white px-4 py-2 rounded">
          Apagar
        </button>

        <!-- Super Admin -->
        <button v-if="userRole === 'SA'" @click="openEditModal(company)" class="btn-edit text-white px-4 py-2 rounded">
          Editar
        </button>
        <button v-if="userRole === 'SA'" @click="openDeleteModal(company.id)"
          class="btn-remove text-white px-4 py-2 rounded">
          Apagar
        </button>
        <button v-if="userRole === 'SA' && company?.status === 'Inativo'" @click="acceptCompany(company.id)"
          class="btn-accept text-white px-4 py-2 rounded">
          Aceitar
        </button>
      </div>

      <!-- Mensagem de bloqueio -->
      <!-- <p
          v-if="company?.status === 'Ativo' && userRole !== 'SA'"
          class="mt-4 p-2 text-sm text-yellow-800 bg-yellow-100 border border-yellow-400 rounded"
        >
          Estado "Ativo" não permite. Contacte o administrador.
        </p> -->
    </div>

    <!-- Modal Editar -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg shadow-md w-96">
        <h3 class="text-lg font-bold mb-4">Editar Empresa</h3>
        <form @submit.prevent="updateCompany">
          <div class="mb-3">
            <label class="block font-medium">Nome da Empresa</label>
            <input v-model="editCompany.name" class="form-control w-full border px-2 py-1" required />
          </div>
          <div class="mb-3">
            <label class="block font-medium">Setor</label>
            <input v-model="editCompany.sector" class="form-control w-full border px-2 py-1" required />
          </div>
          <div class="mb-3 flex items-center">
            <input type="checkbox" id="edit-draft" v-model="editCompany.draft" class="mr-2" />
            <label for="edit-draft" class="font-medium">Guardar como rascunho</label>
          </div>
          <div class="flex justify-end gap-2">
            <button type="button" @click="closeEditModal" class="btn btn-secondary">Cancelar</button>
            <button type="submit" class="btn btn-success">Guardar alterações</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Apagar -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg shadow-md w-96">
        <h3 class="text-lg font-bold mb-4">Tem certeza que deseja apagar esta empresa?</h3>
        <div class="flex justify-end gap-2">
          <button type="button" @click="closeDeleteModal" class="btn btn-secondary">Cancelar</button>
          <button type="button" @click="deleteCompany" class="btn btn-danger">Apagar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const route = useRoute();
const toast = useToast();
const company = ref(null);
const companyName = ref('');
const userRole = ref('');
const roleLoaded = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const editCompany = ref({});
const companyToDelete = ref(null);

const fetchUserRole = async () => {
  try {
    const res = await axios.get('http://127.0.0.1:8000/api/user');
    const email = res.data.email;
    userRole.value = email === 'admin@admin.com' ? 'SA' : 'CA';
  } catch (e) {
    toast.error('Erro ao obter papel do utilizador.');
  } finally {
    roleLoaded.value = true;
  }
};

const fetchCompany = async () => {
  try {
    const response = await axios.get(`http://127.0.0.1:8000/api/companies/${route.params.id}`);
    company.value = response.data;
    companyName.value = response.data.name;
  } catch (e) {
    toast.error('Erro ao carregar empresa.');
  }
};

const openEditModal = (companyData) => {
  if (companyData.status === 'Ativo' && userRole.value !== 'SA') {
    toast.warning('Estado Ativo não permite. Contacte o administrador.');
    return;
  }
  editCompany.value = { ...companyData, draft: !!companyData.draft };
  showEditModal.value = true;
};

const closeEditModal = () => {
  showEditModal.value = false;
  editCompany.value = {};
};

const updateCompany = async () => {
  try {
    await axios.put(`http://127.0.0.1:8000/api/companies/${editCompany.value.id}`, {
      name: editCompany.value.name,
      sector: editCompany.value.sector,
      draft: editCompany.value.draft ? 1 : 0,
    });
    toast.success('Empresa atualizada com sucesso!');
    closeEditModal();
    await fetchCompany();
  } catch (error) {
    toast.error('Erro ao atualizar empresa.');
  }
};

const openDeleteModal = (companyId) => {
  if (company.value.status === 'Ativo' && userRole.value !== 'SA') {
    toast.warning('Estado Ativo não permite. Contacte o administrador.');
    return;
  }
  companyToDelete.value = companyId;
  showDeleteModal.value = true;
};

const closeDeleteModal = () => {
  companyToDelete.value = null;
  showDeleteModal.value = false;
};

const deleteCompany = async () => {
  try {
    await axios.delete(`http://127.0.0.1:8000/api/companies/${companyToDelete.value}`);
    toast.success('Empresa excluída com sucesso!');
    closeDeleteModal();
    await fetchCompany();
  } catch (error) {
    toast.error('Erro ao excluir empresa.');
  }
};

const acceptCompany = async (companyId) => {
  try {
    await axios.post(`http://127.0.0.1:8000/api/companies/${companyId}/approve`);
    toast.success('Empresa aceite com sucesso!');
    await fetchCompany();
  } catch (error) {
    toast.error('Erro ao aceitar empresa.');
  }
};

onMounted(async () => {
  await fetchUserRole();
  await fetchCompany();
});
</script>

<style scoped>
.company {
  padding: 20px;
  max-width: 800px;
  margin-left: 0;
}

.btn-edit {
  background-color: #4CAF50;
  color: white;
}

.btn-edit:hover {
  background-color: #45a049;
}

.btn-remove {
  background-color: #dc3545;
  color: white;
}

.btn-remove:hover {
  background-color: #c82333;
}

.btn-accept {
  background-color: #007bff;
  color: white;
}

.btn-accept:hover {
  background-color: #0069d9;
}
</style>