<template>
  <div class="company relative">
    <!-- Botão fechar -->
    <button @click="goBack" class="close-button text-gray-600 hover:text-red-600">
      ×
    </button>

    <div v-if="!roleLoaded || !company.name">
      <h3 class="text-2xl font-bold mb-4 text-left">Detalhes da Empresa</h3>
    </div>

    <!-- Quando já carregou role e company, mostra tudo -->
    <div v-if="roleLoaded && company">
      <!-- Detalhes da empresa -->
      <div class="mb-6">
        <p><strong>Nome:</strong> {{ company.name }}</p>
        <p><strong>Setor:</strong> {{ company.sector }}</p>
        <p><strong>Status:</strong> {{ company.status }}</p>
        <p><strong>Rascunho:</strong> {{ company.draft ? 'Sim' : 'Não' }}</p>
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

      <!-- Utilizadores -->
      <div class="mb-6">
        <h3 class="text-lg font-semibold">Utilizadores</h3>
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


      <!-- Formulário de convite -->
      <div class="mb-6">
        <h3 class="text-lg font-semibold">Convidar Utilizador</h3>
        <form @submit.prevent="sendInvite" class="flex flex-col sm:flex-row gap-2 items-start">
          <input v-model="inviteEmail" type="email" class="form-control w-full sm:w-auto"
            placeholder="Email do utilizador" required />
          <button type="submit" class="btn btn-success">Enviar Convite</button>
        </form>
      </div>

      <div v-if="company.invites?.length" class="mb-6">
        <h3 class="text-lg font-semibold">Convites Enviados</h3>
        <ul>
          <li v-for="invite in company.invites" :key="invite.id">
            {{ invite.email }} –
            <span v-if="invite.accepted_at">Aceite</span>
            <span v-else-if="invite.cancelled_at">Cancelado</span>
            <span v-else-if="new Date(invite.expires_at) < new Date()">Expirado</span>
            <span v-else>Pendente</span>

            <!-- Botões apenas se ainda não foi aceite ou cancelado -->
            <template v-if="!invite.accepted_at && !invite.cancelled_at">
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


      <!-- Botões de ação -->
      <div class="flex gap-4 mt-4">
        <button v-if="userRole === 'CA' || userRole === 'SA'" @click="openEditModal(company)"
          class="btn-edit text-white px-4 py-2 rounded">
          Editar
        </button>
        <button v-if="userRole === 'CA' || userRole === 'SA'" @click="openDeleteModal(company.id)"
          class="btn-remove text-white px-4 py-2 rounded">
          Apagar
        </button>
        <button v-if="userRole === 'SA' && company.status === 'Inativo'" @click="acceptCompany(company.id)"
          class="btn-accept text-white px-4 py-2 rounded">
          Aceitar
        </button>
      </div>
    </div>

    <!-- Mensagem de loading -->
    <div v-else>
      <p>A carregar detalhes da empresa...</p>
    </div>

    <!-- Dialogs -->
    <!-- ... (não alterado) -->
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
const inviteEmail = ref('');
const companyToDelete = ref(null);

const goBack = () => {
  router.back();
};

// const fetchUserRole = async () => {
//   try {
//     const { data } = await axios.get('http://127.0.0.1:8000/api/user');
//     userRole.value = data.email === 'admin@admin.com' ? 'SA' : 'CA';
//   } catch {
//     toast.error('Erro ao obter papel do utilizador.');
//   } finally {
//     roleLoaded.value = true;
//   }
// };
const fetchUserRole = async () => {
  try {
    const { data } = await axios.get(`http://127.0.0.1:8000/api/companies/${route.params.id}/user-role`);
    userRole.value = data.role; // Ex: 'CU', 'CA', etc.
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

const cancelInvite = async (inviteId) => {
  try {
    await axios.delete(`http://127.0.0.1:8000/api/invites/${inviteId}/cancel`);
    toast.success('Convite cancelado com sucesso!');
    await fetchCompany();
  } catch {
    toast.error('Erro ao cancelar convite.');
  }
};

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
