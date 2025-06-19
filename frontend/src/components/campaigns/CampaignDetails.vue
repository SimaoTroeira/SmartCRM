<template>
  <div class="campaign p-4 relative">
    <button @click="goBack" class="close-button text-gray-600 hover:text-red-600">×</button>

    <h3 v-if="loading" class="text-2xl font-bold mb-4 text-left">
      Detalhes da Campanha<span class="dot-anim ml-1"></span>
    </h3>

    <div v-else>
      <h2 class="text-2xl font-bold mb-4 text-left">{{ campaign.name }}</h2>
      <p><strong>Descrição:</strong> {{ campaign.description }}</p>
      <p><strong>Início:</strong> {{ formatDate(campaign.start_date || campaign.created_at) }}</p>
      <p><strong>Fim:</strong> {{ formatDate(campaign.end_date) }}</p>
      <p>
        <strong>Status:</strong>
        <span :class="{
          'text-green-600 font-semibold': campaign.status === 'active',
          'text-gray-600 font-medium': campaign.status === 'draft',
          'text-blue-600 font-semibold': campaign.status === 'completed'
        }">
          {{ traduzirStatus(campaign.status) }}
        </span>
      </p>

      <div class="mb-4" v-if="campaign.company">
        <strong>Empresa:</strong>
        <router-link :to="{ name: 'CompanyDetails', params: { id: campaign.company.id } }"
          class="text-blue-600 underline hover:text-blue-800">
          {{ campaign.company.name }}
        </router-link>
      </div>

      <div class="flex gap-4" v-if="canEdit">
        <button @click="openEditModal" class="btn-edit" :disabled="campaign.status === 'completed'">
          Editar
        </button>
        <button @click="openDeleteModal" class="btn-remove">Apagar</button>
        <button v-if="campaign.status === 'active'" @click="openConcludeModal" class="btn-conclude">
          Concluir Campanha
        </button>
      </div>

      <!-- Associar utilizadores -->
      <div class="mt-6" v-if="canEdit">
        <h3 class="text-lg font-semibold mb-2">Associar Utilizadores</h3>
        <select v-model="selectedUsers" multiple class="form-control border p-2 w-full mb-2">
          <option v-for="user in filteredCompanyUsers" :key="user.id" :value="user.id">
            {{ user.name }}
          </option>
        </select>
        <button @click="associateUsers" class="btn btn-success">Associar</button>
      </div>

      <!-- Listar utilizadores já associados -->
      <div class="mt-6">
        <h3 class="text-lg font-semibold mb-2">Utilizadores Associados</h3>
        <ul>
          <li v-for="user in campaignUsers" :key="user.id" class="flex justify-between items-center mb-2">
            <span>{{ user.name }} – {{ user.role }}</span>
            <button v-if="canRemoveUser(user)" @click="removeUser(user.id)"
              class="text-red-600 hover:underline text-sm">
              Remover
            </button>
          </li>
        </ul>
      </div>
    </div>

    <!-- Modal Editar -->
    <dialog ref="editDialog" class="bg-white p-6 rounded-lg shadow-md w-96">
      <h3 class="text-lg font-bold mb-4">Editar Campanha</h3>
      <form @submit.prevent="updateCampaign">
        <div class="mb-3">
          <label class="block font-medium">Título</label>
          <input v-model="form.name" class="form-control w-full border px-2 py-1" required />
        </div>
        <div class="mb-3">
          <label class="block font-medium">Descrição</label>
          <textarea v-model="form.description" class="form-control w-full border px-2 py-1" required></textarea>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" @click="closeEditModal" class="btn btn-secondary">Cancelar</button>
          <button type="submit" class="btn btn-success">Guardar alterações</button>
        </div>
      </form>
    </dialog>

    <!-- Modal Apagar -->
    <dialog ref="deleteDialog" class="bg-white p-6 rounded-lg shadow-md w-96">
      <h3 class="text-lg font-bold mb-4">Tem certeza que deseja apagar esta campanha?</h3>
      <div class="flex justify-end gap-2">
        <button type="button" @click="closeDeleteModal" class="btn btn-secondary">Cancelar</button>
        <button type="button" @click="confirmDelete" class="btn btn-danger">Apagar</button>
      </div>
    </dialog>

    <!-- Modal Concluir -->
    <dialog ref="concludeDialog" class="bg-white p-6 rounded-lg shadow-md w-96">
      <h3 class="text-lg font-bold mb-4">Tem certeza que deseja concluir esta campanha?</h3>
      <div class="flex justify-end gap-2">
        <button type="button" @click="closeConcludeModal" class="btn btn-secondary">Cancelar</button>
        <button type="button" @click="confirmConclude" class="btn btn-success">Concluir</button>
      </div>
    </dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const campaign = ref(null);
const form = ref({ name: '', description: '' });
const editDialog = ref(null);
const deleteDialog = ref(null);
const concludeDialog = ref(null);
const canEdit = ref(false);

const selectedUsers = ref([]);
const companyUsers = ref([]);
const campaignUsers = ref([]);

const userId = ref(null);
const userRole = ref(null);
const loading = ref(true);

const goBack = () => router.back();
const formatDate = date => date ? new Date(date).toLocaleDateString() : 'Sem data';

const traduzirStatus = (status) => {
  switch (status) {
    case 'draft': return 'Rascunho';
    case 'active': return 'Ativo';
    case 'completed': return 'Concluída';
    default: return status;
  }
};

const fetchCampaign = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get(`http://127.0.0.1:8000/api/campaigns/${route.params.id}`);
    campaign.value = data;
    form.value = { name: data.name, description: data.description };

    await Promise.all([
      fetchUserDataAndPermissions(data.company.id),
      fetchCompanyUsers(data.company.id),
      fetchCampaignUsers(data.id)
    ]);
  } catch (error) {
    toast.error('Erro ao carregar campanha.');
  } finally {
    loading.value = false;
  }
};

const fetchUserDataAndPermissions = async (companyId) => {
  try {
    const { data: user } = await axios.get(`http://127.0.0.1:8000/api/user`);
    userId.value = user.id;

    if (user.email === 'admin@admin.com') {
      userRole.value = 'SA';
      canEdit.value = true;
    } else {
      const { data } = await axios.get(`http://127.0.0.1:8000/api/companies/${companyId}/user-role`);
      userRole.value = data.role;
      canEdit.value = data.role === 'CA';
    }
  } catch {
    toast.error('Erro ao verificar permissões.');
  }
};

const fetchCompanyUsers = async (companyId) => {
  try {
    const { data } = await axios.get(`http://127.0.0.1:8000/api/companies/${companyId}`);
    companyUsers.value = data.user_company_roles.map(ucr => ({
      id: ucr.user.id,
      name: ucr.user.name,
      role: ucr.role.code
    }));
  } catch {
    toast.error('Erro ao obter utilizadores da empresa.');
  }
};

const fetchCampaignUsers = async (campaignId) => {
  try {
    const { data } = await axios.get(`http://127.0.0.1:8000/api/campaigns/${campaignId}/users`);
    campaignUsers.value = data;
  } catch {
    toast.error('Erro ao obter utilizadores da campanha.');
  }
};

const filteredCompanyUsers = computed(() => {
  const associatedIds = campaignUsers.value.map(u => u.id);
  return companyUsers.value.filter(u => !associatedIds.includes(u.id));
});

const associateUsers = async () => {
  try {
    await axios.post(`http://127.0.0.1:8000/api/campaigns/${campaign.value.id}/users`, {
      user_ids: selectedUsers.value
    });
    toast.success('Utilizadores associados com sucesso!');
    selectedUsers.value = [];
    await fetchCampaignUsers(campaign.value.id);
  } catch {
    toast.error('Erro ao associar utilizadores.');
  }
};

const canRemoveUser = (user) => {
  const isSelf = user.id === userId.value;
  const isCU = user.role === 'CU';
  return isSelf || (userRole.value === 'CA' && isCU);
};

const removeUser = async (userIdToRemove) => {
  try {
    await axios.delete(`http://127.0.0.1:8000/api/campaigns/${campaign.value.id}/users/${userIdToRemove}`);
    toast.success('Utilizador removido da campanha!');
    await fetchCampaignUsers(campaign.value.id);
  } catch {
    toast.error('Erro ao remover utilizador.');
  }
};

const openEditModal = () => editDialog.value.showModal();
const closeEditModal = () => editDialog.value.close();

const openDeleteModal = () => deleteDialog.value.showModal();
const closeDeleteModal = () => deleteDialog.value.close();

const openConcludeModal = () => concludeDialog.value.showModal();
const closeConcludeModal = () => concludeDialog.value.close();

const updateCampaign = async () => {
  try {
    await axios.put(`http://127.0.0.1:8000/api/campaigns/${campaign.value.id}`, form.value);
    toast.success('Campanha atualizada com sucesso!');
    closeEditModal();
    await fetchCampaign();
  } catch {
    toast.error('Erro ao atualizar campanha.');
  }
};

const confirmDelete = async () => {
  try {
    await axios.delete(`http://127.0.0.1:8000/api/campaigns/${campaign.value.id}`);
    toast.success('Campanha excluída com sucesso!');
    closeDeleteModal();
    router.push('/campaigns');
  } catch {
    toast.error('Erro ao excluir campanha.');
  }
};

const confirmConclude = async () => {
  try {
    await axios.post(`http://127.0.0.1:8000/api/campaigns/${campaign.value.id}/concluir`);
    toast.success('Campanha concluída com sucesso!');
    closeConcludeModal();
    await fetchCampaign();
  } catch {
    toast.error('Erro ao concluir campanha.');
  }
};

onMounted(fetchCampaign);
</script>


<style scoped>
.campaign {
  padding: 20px;
  max-width: 800px;
  margin-left: 0;
}

.close-button {
  position: absolute;
  top: 2rem;
  right: 15rem;
  font-size: 4rem;
  background: transparent;
  border: none;
  cursor: pointer;
}

.btn-edit,
.btn-remove,
.btn-conclude {
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  font-size: 1rem;
}

.btn-edit {
  background-color: #ffc107;
  color: white;
}
.btn-edit:hover {
  background-color: #e0a800;
}
.btn-edit:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

.btn-remove {
  background-color: #dc3545;
  color: white;
}
.btn-remove:hover {
  background-color: #c82333;
}

.btn-conclude {
  background-color: #007bff;
  color: white;
}
.btn-conclude:hover {
  background-color: #0056b3;
}

dialog {
  border-radius: 12px;
  border: 8px solid #ffffff;
}
dialog::backdrop {
  background: rgba(0, 0, 0, 0.5);
}

@keyframes dots {
  0% { content: ''; }
  25% { content: '.'; }
  50% { content: '..'; }
  75% { content: '...'; }
  100% { content: ''; }
}
.dot-anim::after {
  display: inline-block;
  animation: dots 1.5s steps(4, end) infinite;
  content: '';
  white-space: pre;
}
</style>
