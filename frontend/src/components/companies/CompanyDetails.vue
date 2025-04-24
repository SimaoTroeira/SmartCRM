<template>
  <div class="company relative">
    <!-- Botão fechar -->
    <button @click="goBack" class="close-button text-gray-600 hover:text-red-600">
      ×
    </button>

    <!-- Enquanto carrega -->
    <div v-if="!roleLoaded || !company.name">
      <h3 class="text-2xl font-bold mb-4 text-left">
        Detalhes da Empresa<span class="dot-anim ml-1"></span>
      </h3>
    </div>

    <!-- Conteúdo principal -->
    <div v-else>
      <!-- Título -->
      <h2 class="text-2xl font-bold mb-4 text-left">{{ company.name }}</h2>

      <!-- Descrição -->
      <div class="pb-4">
        <p><strong>Setor:</strong> {{ company.sector }}</p>
        <p><strong>Estado Atual:</strong> {{ company.status }}</p>
      </div>

      <hr class="border-t border-gray-300 my-6" />

      <!-- Campanhas -->
      <div>
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

      <hr class="border-t border-gray-300 my-6" />

      <!-- Utilizadores -->
      <div>
        <h4 class="text-lg font-semibold mb-2">Utilizadores:</h4>
        <ul>
          <li v-for="ucr in company.user_company_roles" :key="ucr.id">
            {{ ucr.user.name }} – {{ ucr.role.code }}
            <template v-if="userRole === 'CA' && ucr.role.code === 'CU'">
              <button @click="promoteUser(ucr.id)" class="text-blue-600 ml-2 hover:underline text-sm">
                Promover a CA
              </button>
            </template>
          </li>
        </ul>
      </div>

      <hr class="border-t border-gray-300 my-6" />

      <!-- Convidar Utilizador -->
      <div v-if="userRole === 'CA'">
        <h4 class="text-lg font-semibold mb-2">Convidar Utilizador</h4>
        <form @submit.prevent="sendInvite" class="flex flex-col sm:flex-row gap-2 items-start">
          <input v-model="inviteEmail" type="email" class="form-control w-full sm:w-auto"
            placeholder="Email do utilizador" required />
          <button type="submit" class="btn btn-success">Enviar Convite</button>
        </form>
      </div>

      <hr v-if="userRole === 'CA'" class="border-t border-gray-300 my-6" />

      <!-- Convites Enviados -->
      <div v-if="company.invites?.length">
        <h4 class="text-lg font-semibold mb-2">Convites Enviados</h4>
        <ul>
          <li v-for="invite in company.invites" :key="invite.id">
            {{ invite.email }} –
            <span v-if="invite.accepted_at">Aceite</span>
            <span v-else-if="invite.cancelled_at">Cancelado</span>
            <span v-else-if="new Date(invite.expires_at) < new Date()">Expirado</span>
            <span v-else>Pendente</span>

            <template
              v-if="userRole === 'CA' && !invite.accepted_at && !invite.cancelled_at && new Date(invite.expires_at) >= new Date()">
              <button @click="resendInvite(invite.id)" class="text-blue-600 ml-2 hover:underline text-sm">
                Reenviar
              </button>
              <button @click="cancelInvite(invite.id)" class="text-red-600 ml-2 hover:underline text-sm">
                Cancelar
              </button>
            </template>
          </li>
        </ul>
      </div>

      <hr class="border-t border-gray-300 my-6" />

      <!-- Botões -->
      <div class="flex gap-4 mt-4">
        <button v-if="userRole === 'CA' || userRole === 'SA'" @click="openEditModal(company)"
          class="btn-edit text-white px-4 py-2 rounded">Editar</button>
        <button v-if="userRole === 'CA' || userRole === 'SA'" @click="openDeleteModal(company.id)"
          class="btn-remove text-white px-4 py-2 rounded">Apagar</button>
        <!-- Botão de pedido de validação -->
        <button v-if="userRole === 'CA' && company.status === 'Inativo' && !company.submitted" @click="openSubmitModal"
          class="btn-submit text-white px-4 py-2 rounded">
          Pedir validação
        </button>
        <span v-else-if="userRole === 'CA' && company.submitted && company.status === 'Inativo'"
          class="text-blue-500 font-medium mt-2">
          Aguardando aprovação
        </span>
        <span v-else-if="userRole === 'CA' && company.status === 'Ativo'" class="text-green-600 font-medium mt-2">
          Pedido aceite
        </span>
        <button v-if="userRole === 'SA' && company.status === 'Inativo'" @click="openAcceptModal"
          class="btn-accept text-white px-4 py-2 rounded">Ativar</button>
      </div>
    </div>

    <!-- Modal de Aceitação -->
    <dialog ref="acceptDialog" class="bg-white p-6 rounded-lg shadow-md w-96">
      <h3 class="text-lg font-bold mb-4">Tem certeza que deseja aceitar o registo desta empresa?</h3>
      <div class="flex justify-end gap-2">
        <button type="button" @click="closeAcceptModal" class="btn btn-secondary">Cancelar</button>
        <button type="button" @click="confirmAcceptCompany" class="btn btn-success">Aceitar</button>
      </div>
    </dialog>

    <!-- Dialog de Edição -->
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

    <!-- Modal de Confirmação de Pedido de Validação -->
    <dialog ref="submitDialog" class="bg-white p-6 rounded-lg shadow-md w-96">
      <h3 class="text-lg font-bold mb-4">Deseja pedir a validação desta empresa?</h3>
      <div class="flex justify-end gap-2">
        <button type="button" @click="closeSubmitModal" class="btn btn-secondary">Cancelar</button>
        <button type="button" @click="confirmSubmitCompany" class="btn btn-submit">Confirmar</button>
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
const acceptDialog = ref(null);
const deleteDialog = ref(null);
const inviteEmail = ref('');
const companyToDelete = ref(null);

const submitDialog = ref(null);

const goBack = () => {
  router.back();
};

// Obter role do utilizador (CA, CU ou SA)
const fetchUserRole = async () => {
  try {
    const { data: user } = await axios.get('http://127.0.0.1:8000/api/user');
    if (user.email === 'admin@admin.com') {
      userRole.value = 'SA';
    } else {
      const { data } = await axios.get(`http://127.0.0.1:8000/api/companies/${route.params.id}/user-role`);
      userRole.value = data.role;
    }
  } catch {
    toast.error('Erro ao obter papel do utilizador.');
  } finally {
    roleLoaded.value = true;
  }
};

// Obter dados da empresa
const fetchCompany = async () => {
  try {
    const { data } = await axios.get(`http://127.0.0.1:8000/api/companies/${route.params.id}`);
    company.value = data;
    companyName.value = data.name;
  } catch {
    toast.error('Erro ao carregar empresa.');
  }
};

// Modal editar
const openEditModal = (c) => {
  if (c.status === 'Ativo' && userRole.value !== 'SA') {
    toast.warning('Estado Ativo não permite. Contacte o administrador.');
    return;
  }
  editCompany.value = {
    id: c.id,
    name: c.name,
    sector: c.sector
  };
  editDialog.value?.showModal();
};

const closeEditModal = () => {
  editDialog.value?.close();
};

const openAcceptModal = () => {
  acceptDialog.value?.showModal();
};

const closeAcceptModal = () => {
  acceptDialog.value?.close();
};

const openSubmitModal = () => {
  submitDialog.value?.showModal();
};

const closeSubmitModal = () => {
  submitDialog.value?.close();
};

// Guardar alterações da empresa
const updateCompany = async () => {
  try {
    await axios.put(`http://127.0.0.1:8000/api/companies/${editCompany.value.id}`, {
      name: editCompany.value.name,
      sector: editCompany.value.sector,
    });
    toast.success('Empresa atualizada com sucesso!');
    closeEditModal();
    await fetchCompany();
  } catch {
    toast.error('Erro ao atualizar empresa.');
  }
};

// Modal apagar
const openDeleteModal = (id) => {
  if (company.value.status === 'Ativo' && userRole.value !== 'SA') {
    toast.warning('Estado Ativo não permite. Contacte o administrador.');
    return;
  }
  companyToDelete.value = id;
  deleteDialog.value?.showModal();
};

const closeDeleteModal = () => {
  deleteDialog.value?.close();
};

// Confirmar apagamento
const confirmDelete = async () => {
  try {
    await axios.delete(`http://127.0.0.1:8000/api/companies/${companyToDelete.value}`);
    toast.success('Empresa excluída com sucesso!');
    closeDeleteModal();
    router.push('/companies');
  } catch {
    toast.error('Erro ao excluir empresa.');
  }
};

// Aceitar empresa (SA)
const confirmAcceptCompany = async () => {
  try {
    await axios.post(`http://127.0.0.1:8000/api/companies/${company.value.id}/approve`);
    toast.success('Empresa aceite com sucesso!');
    closeAcceptModal();
    await fetchCompany();
  } catch {
    toast.error('Erro ao aceitar empresa.');
  }
};

const confirmSubmitCompany = async () => {
  try {
    await axios.post(`http://127.0.0.1:8000/api/companies/${company.value.id}/submit`);
    toast.success('Pedido de validação enviado com sucesso!');
    closeSubmitModal();
    await fetchCompany();
  } catch (error) {
    toast.error(error.response?.data?.error || 'Erro ao enviar pedido de validação.');
  }
};

// Enviar convite
const sendInvite = async () => {
  try {
    const response = await axios.post(`http://127.0.0.1:8000/api/companies/${company.value.id}/invite`, {
      email: inviteEmail.value
    });
    toast.success('Convite enviado com sucesso!');
    console.log(`Link de aceitação: http://localhost:5174/accept-invite/${response.data.token}`);
    inviteEmail.value = '';
    await fetchCompany();
  } catch (error) {
    toast.error('Erro ao enviar convite.');
    console.error(error);
  }
};

// Reenviar convite
const resendInvite = async (inviteId) => {
  try {
    const { data } = await axios.put(`http://127.0.0.1:8000/api/invites/${inviteId}/resend`);
    toast.success('Convite reenviado com sucesso!');
    console.log(`Novo link: http://localhost:5174/accept-invite/${data.token}`);
    await fetchCompany();
  } catch {
    toast.error('Erro ao reenviar convite.');
  }
};

// Cancelar convite
const cancelInvite = async (inviteId) => {
  try {
    await axios.delete(`http://127.0.0.1:8000/api/invites/${inviteId}/cancel`);
    toast.success('Convite cancelado com sucesso!');
    await fetchCompany();
  } catch {
    toast.error('Erro ao cancelar convite.');
  }
};

// Promover utilizador (CU -> CA)
const promoteUser = async (ucrId) => {
  try {
    await axios.put(`http://127.0.0.1:8000/api/user-company-roles/${ucrId}/promote`);
    toast.success('Utilizador promovido com sucesso!');
    await fetchCompany();
  } catch (error) {
    toast.error('Erro ao promover utilizador.');
    console.error(error);
  }
};

onMounted(async () => {
  await fetchUserRole();
  await fetchCompany();
});
</script>



<style scoped>
/* Mantém o estilo anterior */
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
  background-color: #007bff;
  color: white;
}


.btn-edit:hover {
  background-color: #0069d9;
}

.btn-accept {
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  font-size: 1rem;
  background-color: #4CAF50;
  color: white;
}

.btn-accept:hover {
  background-color: #45a049;
}

.btn-remove {
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  font-size: 1rem;
  background-color: #dc3545;
  color: white;
}

.btn-remove:hover {
  background-color: #c82333;
}

.btn-submit {
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  font-size: 1rem;
  background-color: #4CAF50;
  color: white;
}

.btn-submit:hover {
  background-color: #45a049;
}

dialog {
  border-radius: 12px;
  border: 8px solid #ffffff;
}

dialog::backdrop {
  background: rgba(0, 0, 0, 0.5);
}

@keyframes dots {
  0% {
    content: '';
  }

  25% {
    content: '.';
  }

  50% {
    content: '..';
  }

  75% {
    content: '...';
  }

  100% {
    content: '';
  }
}

.dot-anim::after {
  display: inline-block;
  animation: dots 1.5s steps(4, end) infinite;
  content: '';
  white-space: pre;
}
</style>
