<template>
  <div class="company relative">
    <!-- Botão fechar -->
    <button @click="goBack" class="close-button text-gray-600 hover:text-red-600">
      ×
    </button>

    <div v-if="!roleLoaded || !company.name">
      <h3 class="text-2xl font-bold mb-4 text-left">Detalhes da Empresa</h3>
    </div>

    <div v-else>
      <h2 class="text-2xl font-bold mb-4 text-left">
        {{ company.name }}
      </h2>

      <!-- Secção: Informações da Empresa -->
      <div class="mb-6 border rounded-lg p-4 bg-gray-50">
        <h4 class="text-lg font-semibold mb-2">Informações da Empresa</h4>
        <table class="table-auto w-full text-left">
          <tbody>
            <tr>
              <td class="font-medium w-32">Nome:</td>
              <td>{{ company.name }}</td>
            </tr>
            <tr>
              <td class="font-medium">Setor:</td>
              <td>{{ company.sector }}</td>
            </tr>
            <tr>
              <td class="font-medium">Status:</td>
              <td>{{ company.status }}</td>
            </tr>
            <tr>
              <td class="font-medium">Rascunho:</td>
              <td>{{ company.draft ? 'Sim' : 'Não' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Secção: Campanhas -->
      <div class="mb-6 border rounded-lg p-4 bg-gray-50">
        <h4 class="text-lg font-semibold mb-2">Campanhas Associadas:</h4>
        <ul>
          <li v-for="camp in company.campaigns" :key="camp.id" class="mb-2">
            <router-link :to="{ name: 'CampaignDetails', params: { id: camp.id } }"
              class="text-blue-600 underline hover:text-blue-800">
              <strong>{{ camp.name }}</strong>
            </router-link>
            ({{ camp.status }})
            <p>{{ camp.description }}</p>
            <p>
              {{ camp.created_at ? new Date(camp.created_at).toLocaleDateString() : 'Sem data de início' }} –
              {{ camp.end_date ? new Date(camp.end_date).toLocaleDateString() : 'Sem data de fim' }}
            </p>
          </li>
        </ul>
      </div>

      <!-- Secção: Convite -->
      <div class="mb-6 border rounded-lg p-4 bg-gray-50">
        <h4 class="text-lg font-semibold mb-2">Convidar Utilizador</h4>
        <form @submit.prevent="sendInvite" class="flex flex-col sm:flex-row gap-2 items-start">
          <input v-model="inviteEmail" type="email" class="form-control w-full sm:w-auto"
            placeholder="Email do utilizador" required />
          <button type="submit" class="btn btn-success">Enviar Convite</button>
        </form>
      </div>

      <!-- Secção: Utilizadores -->
      <div class="mb-6 border rounded-lg p-4 bg-gray-50">
        <h4 class="text-lg font-semibold mb-2">Utilizadores:</h4>
        <ul>
          <li v-for="ucr in company.user_company_roles" :key="ucr.id">
            {{ ucr.user.name }} – {{ ucr.role.code }}
          </li>
        </ul>
      </div>


      <!-- Botões de ação -->
      <div class="flex gap-4 mt-4">
        <button v-if="userRole === 'CA'" @click="openEditModal(company)"
          class="btn-edit text-white px-4 py-2 rounded">Editar</button>
        <button v-if="userRole === 'CA'" @click="openDeleteModal(company.id)"
          class="btn-remove text-white px-4 py-2 rounded">Apagar</button>

        <button v-if="userRole === 'SA'" @click="openEditModal(company)"
          class="btn-edit text-white px-4 py-2 rounded">Editar</button>
        <button v-if="userRole === 'SA'" @click="openDeleteModal(company.id)"
          class="btn-remove text-white px-4 py-2 rounded">Apagar</button>
        <button v-if="userRole === 'SA' && company.status === 'Inativo'" @click="openAcceptModal"
          class="btn-accept text-white px-4 py-2 rounded">Ativar</button>
      </div>
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

    <!-- Dialog de Aceitar -->
    <dialog ref="acceptDialog" class="bg-white p-6 rounded-lg shadow-md w-96">
      <h3 class="text-lg font-bold mb-4">Tem certeza que deseja aceitar o registo desta empresa?</h3>
      <div class="flex justify-end gap-2">
        <button type="button" @click="closeAcceptModal" class="btn btn-secondary">Cancelar</button>
        <button type="button" @click="acceptCompany" class="btn btn-success">Aceitar</button>
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

const editDialog = ref(null);
const deleteDialog = ref(null);
const acceptDialog = ref(null);

const goBack = () => {
  router.back();
};

const companyToDelete = ref(null);
const inviteEmail = ref('');

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

const sendInvite = async () => {
  try {
    const response = await axios.post(
      `http://127.0.0.1:8000/api/companies/${company.value.id}/invite`,
      { email: inviteEmail.value }
    );

    toast.success('Convite enviado com sucesso!');

    //Mostra o link na consola (para testar sem email)
    console.log(
      `Link de aceitação: http://localhost:5173/accept-invite/${response.data.token}`
    );


    inviteEmail.value = '';
  } catch (error) {
    toast.error('Erro ao enviar convite.');
    console.error(error);
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

const openAcceptModal = () => {
  acceptDialog.value?.showModal();
};

const closeAcceptModal = () => {
  acceptDialog.value?.close();
};

const openDeleteModal = (id) => {
  if (company.value.status === 'Ativo' && userRole.value !== 'SA') {
    toast.warning('Estado Ativo não permite. Contacte o administrador.');
    return;
  }
  companyToDelete.value = id;
  deleteDialog.value.showModal();
};

const closeDeleteModal = () => {
  deleteDialog.value.close();
};


const confirmAcceptCompany = async () => {
  try {
    await acceptCompany(company.value.id);
    showAcceptModal.value = false;
  } catch (error) {
    toast.error('Erro ao aceitar empresa.');
  }
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




const confirmDelete = async () => {
  try {
    await axios.delete(`http://127.0.0.1:8000/api/companies/${companyToDelete.value}`);
    toast.success('Empresa excluída com sucesso!');
    closeDeleteModal();
    router.push('/companies');
    await fetchCompany();
  } catch {
    toast.error('Erro ao excluir empresa.');
  }
};

const acceptCompany = async () => {
  try {
    await axios.post(`http://127.0.0.1:8000/api/companies/${company.value.id}/approve`);
    toast.success('Empresa aceite com sucesso!');
    closeAcceptModal();
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
  top: 1.5rem;
  right: 1rem;
  font-size: 2.5rem;
  background: transparent;
  border: none;
  cursor: pointer;
  z-index: 10;
}

@media (min-width: 1024px) {
  .close-button {
    right: 15rem;
    font-size: 4rem;
  }
}

.btn-edit {
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  font-size: 1rem;
}

.btn-accept {
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  font-size: 1rem;
}

.btn-remove {
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  font-size: 1rem;
}

.btn-accept {
  background-color: #4CAF50;
  color: white;
}

.btn-accept:hover {
  background-color: #45a049;
}

.btn-remove {
  background-color: #dc3545;
  color: white;
}

.btn-remove:hover {
  background-color: #c82333;
}

.btn-edit {
  background-color: #007bff;
  color: white;
}

.btn-edit:hover {
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
