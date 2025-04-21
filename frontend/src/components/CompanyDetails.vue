<template>
  <div class="company relative">
    <!-- Botão fechar -->
    <button @click="goBack" class="close-button text-gray-600 hover:text-red-600">
      ×
    </button>

    <!-- Título sempre visível -->
    <h2 class="text-2xl font-bold mb-4 text-left">
      {{ companyName || 'Detalhes da Empresa' }}
    </h2>


    <!-- Quando já carregou role e company, mostra tudo -->
    <div v-if="roleLoaded && company">
      <!-- Detalhes da empresa -->
      <div class="mb-6">
        <p><strong>Nome:</strong> {{ company.name }}</p>
        <p><strong>Setor:</strong> {{ company.sector }}</p>
        <p><strong>Status:</strong> {{ company.status }}</p>
        <p><strong>Rascunho:</strong> {{ company.draft ? 'Sim' : 'Não' }}</p>
      </div>

      <!-- Campanhas -->
      <div class="mb-6">
        <h3 class="text-lg font-semibold">Campanhas</h3>
        <ul>
          <li v-for="camp in company.campaigns" :key="camp.id" class="mb-2">
            <router-link :to="{ name: 'CampaignDetails', params: { id: camp.id } }"
              class="text-blue-600 underline hover:text-blue-800">
              <strong>{{ camp.name }}</strong>
            </router-link>
            ({{ camp.status }})
            <p>{{ camp.description }}</p>
            <p>
              {{ camp.created_at
                ? new Date(camp.created_at).toLocaleDateString()
                : 'Sem data de início' }}
              –
              {{ camp.end_date
                ? new Date(camp.end_date).toLocaleDateString()
                : 'Sem data de fim' }}
            </p>
          </li>
        </ul>
      </div>

      <!-- Utilizadores -->
      <div class="mb-6">
        <h3 class="text-lg font-semibold">Utilizadores</h3>
        <ul>
          <li v-for="ucr in company.user_company_roles" :key="ucr.id">
            {{ ucr.user.name }} – {{ ucr.role.code }}
          </li>
        </ul>
      </div>


      <!-- Botões de ação -->
      <div class="flex gap-4 mt-4">
        <button v-if="userRole === 'CA'" @click="openEditModal(company)" class="btn-edit text-white px-4 py-2 rounded">
          Editar
        </button>
        <button v-if="userRole === 'CA'" @click="openDeleteModal(company.id)"
          class="btn-remove text-white px-4 py-2 rounded">
          Apagar
        </button>

        <button v-if="userRole === 'SA'" @click="openEditModal(company)" class="btn-edit text-white px-4 py-2 rounded">
          Editar
        </button>
        <button v-if="userRole === 'SA'" @click="openDeleteModal(company.id)"
          class="btn-remove text-white px-4 py-2 rounded">
          Apagar
        </button>
        <button v-if="userRole === 'SA' && company.status === 'Inativo'" @click="acceptCompany(company.id)"
          class="btn-accept text-white px-4 py-2 rounded">
          Aceitar
        </button>
      </div>
    </div>

    <!-- Mensagem de loading enquanto não tiver roleLoaded ou company -->
    <div v-else>
      <p>A carregar detalhes da empresa...</p>
    </div>

    <!-- Dialog de Editar -->
    <dialog ref="editDialog" class="bg-white p-6 rounded-lg shadow-md w-96">
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
    </dialog>

    <!-- Dialog de Apagar -->
    <dialog ref="deleteDialog" class="bg-white p-6 rounded-lg shadow-md w-96">
      <h3 class="text-lg font-bold mb-4">Tem certeza que deseja apagar esta empresa?</h3>
      <div class="flex justify-end gap-2">
        <button type="button" @click="closeDeleteModal" class="btn btn-secondary">Cancelar</button>
        <button type="button" @click="confirmDelete" class="btn btn-danger">Apagar</button>
      </div>
    </dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const company = ref({});
const companyName = ref('');
const userRole = ref('');
const roleLoaded = ref(false);
const editCompany = ref({});
const companyToDel = ref(null);

const editDialog = ref(null);
const deleteDialog = ref(null);

const goBack = () => {
  router.push('/companies');
};

const fetchUserRole = async () => {
  try {
    const { data } = await axios.get('http://127.0.0.1:8000/api/user');
    userRole.value = data.email === 'admin@admin.com' ? 'SA' : 'CA';
  } catch {
    toast.error('Erro ao obter papel do utilizador.');
  } finally {
    roleLoaded.value = true;
  }
};

const fetchCompany = async () => {
  try {
    const { data } = await axios.get(`http://127.0.0.1:8000/api/companies/${route.params.id}`);
    company.value = data;
    companyName.value = data.name;
  } catch {
    toast.error('Erro ao carregar empresa.');
  }
};

const openEditModal = (c) => {
  if (c.status === 'Ativo' && userRole.value !== 'SA') {
    toast.warning('Estado Ativo não permite. Contacte o administrador.');
    return;
  }
  editCompany.value = { ...c, draft: !!c.draft };
  editDialog.value.showModal();
};

const closeEditModal = () => {
  editDialog.value.close();
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
  } catch {
    toast.error('Erro ao atualizar empresa.');
  }
};

const openDeleteModal = (id) => {
  if (company.value.status === 'Ativo' && userRole.value !== 'SA') {
    toast.warning('Estado Ativo não permite. Contacte o administrador.');
    return;
  }
  companyToDel.value = id;
  deleteDialog.value.showModal();
};

const closeDeleteModal = () => {
  deleteDialog.value.close();
};

const confirmDelete = async () => {
  try {
    await axios.delete(`http://127.0.0.1:8000/api/companies/${companyToDel.value}`);
    toast.success('Empresa excluída com sucesso!');
    closeDeleteModal();
    router.push('/companies');
    await fetchCompany();
  } catch {
    toast.error('Erro ao excluir empresa.');
  }
};

const acceptCompany = async (id) => {
  try {
    await axios.post(`http://127.0.0.1:8000/api/companies/${id}/approve`);
    toast.success('Empresa aceite com sucesso!');
    await fetchCompany();
  } catch {
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

.close-button {
  position: absolute;
  top: 2rem;
  right: 15rem;
  font-size: 4rem;
  background: transparent;
  border: none;
  cursor: pointer;
}

.btn-edit {
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  font-size: 1rem;
}

.btn-remove {
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  font-size: 1rem;
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

dialog {
  border-radius: 12px;
  border: 8px solid #ffffff;
}

dialog::backdrop {
  background: rgba(0, 0, 0, 0.5);
}
</style>
