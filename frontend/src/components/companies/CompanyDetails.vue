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
      <!-- Detalhes -->
      <div class="pb-4 grid grid-cols-2 gap-4">
        <p><strong>Setor:</strong> {{ company.sector }}</p>
        <p><strong>Estado Atual:</strong> {{ company.status }}</p>
        <p><strong>Tipo de Empresa:</strong> {{ company.company_type || '—' }}</p>
        <p><strong>Website:</strong>
          <a v-if="company.website" :href="company.website" target="_blank"
            class="text-blue-600 underline hover:text-blue-800">{{ company.website }}</a>
          <span v-else>—</span>
        </p>
        <p><strong>NIF:</strong> {{ company.nif || '—' }}</p>
        <p><strong>Telefone:</strong> {{ company.phone_contact || '—' }}</p>
        <p><strong>Email:</strong> {{ company.email_contact || '—' }}</p>
        <p><strong>País:</strong> {{ company.country || '—' }}</p>
        <p><strong>Cidade:</strong> {{ company.city || '—' }}</p>
        <p><strong>Ano da Fundação:</strong> {{ company.founded_year || '—' }}</p>
        <p><strong>Nº de Empregados:</strong> {{ company.num_employees || '—' }}</p>
        <p><strong>Faixa de Receita:</strong> {{ company.revenue_range || '—' }}</p>
        <p class="col-span-2"><strong>Notas Internas:</strong><br>{{ company.notes || '—' }}</p>
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
              <button @click="openPromoteUserModal(ucr.id)" class="text-blue-600 ml-2 hover:underline text-sm">
                Promover a CA
              </button>
              <button @click="openRemoveUserModal(ucr.id)" class="text-red-600 ml-2 hover:underline text-sm">
                Remover
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
        <!-- <button v-if="userRole === 'CA' || userRole === 'SA'" @click="openDeleteModal(company.id)"
          class="btn-remove text-white px-4 py-2 rounded">Apagar</button> -->
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
        <!-- botão apenas para SA -->
        <button v-if="userRole === 'SA' && company.status === 'Ativo'" @click="openDeactivateModal"
          class="btn-remove text-white px-4 py-2 rounded">
          Desativar
        </button>
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
    <dialog ref="editDialog" class="bg-white p-6 rounded-lg shadow-md w-[700px] max-w-[95vw]">
      <h3 class="text-xl font-bold mb-4 text-center">Editar Empresa</h3>
      <form @submit.prevent="updateCompany">
        <fieldset class="mb-6">
          <legend class="section-title mb-2">Informações Gerais</legend>

          <div class="grid grid-cols-12 gap-4">
            <div class="col-span-6">
              <label class="label">Nome da Empresa <span class="text-red-600">*</span></label>
              <input v-model="editCompany.name" class="form-control" required />
            </div>

            <div class="col-span-6">
              <label class="label">Setor de atividade <span class="text-red-600">*</span></label>
              <input v-model="editCompany.sector" class="form-control" required />
            </div>

            <div class="col-span-6">
              <label class="label">Tipo de Empresa <span class="text-red-600">*</span></label>
              <select v-model="editCompany.company_type" class="form-control" required>
                <option disabled value="">Selecione…</option>
                <option value="Freelancer">Freelancer</option>
                <option value="Startup">Startup</option>
                <option value="ME">Média Empresa</option>
                <option value="PE">Pequena Empresa</option>
                <option value="Corporação">Corporação</option>
              </select>

            </div>

            <div class="col-span-6">
              <label class="label">Website</label>
              <input v-model="editCompany.website" class="form-control" type="url" placeholder="https://…" />
            </div>
          </div>
        </fieldset>

        <fieldset>
          <legend class="section-title mb-2">Contactos e Outros Dados</legend>

          <div class="grid grid-cols-12 gap-4">
            <div class="col-span-6">
              <label class="label">NIF</label>
              <input v-model="editCompany.nif" class="form-control" />
            </div>

            <div class="col-span-6">
              <label class="label">Telefone</label>
              <input v-model="editCompany.phone_contact" class="form-control" />
            </div>

            <div class="col-span-6">
              <label class="label">Email</label>
              <input v-model="editCompany.email_contact" class="form-control" type="email" />
            </div>

            <div class="col-span-6">
              <label class="label">País</label>
              <input v-model="editCompany.country" class="form-control" />
            </div>

            <div class="col-span-6">
              <label class="label">Cidade</label>
              <input v-model="editCompany.city" class="form-control" />
            </div>

            <div class="col-span-6">
              <label class="label">Ano da Fundação</label>
              <input v-model="editCompany.founded_year" class="form-control" type="number" min="1800" max="2099" />
            </div>

            <div class="col-span-6">
              <label class="label">Número de colaboradores</label>
              <input v-model="editCompany.num_employees" class="form-control" type="number" min="1" />
            </div>

            <div class="col-span-12">
              <label class="label">Intervalo de faturação</label>
              <select v-model="editCompany.revenue_range" class="form-control">
                <option disabled value="">Selecione…</option>
                <option value="0-1M">0-1 M €</option>
                <option value="1M-10M">1-10 M €</option>
                <option value="10M-100M">10-100 M €</option>
                <option value="100M+">+100 M €</option>
              </select>
            </div>

            <div class="col-span-12">
              <label class="label">Notas Internas</label>
              <textarea v-model="editCompany.notes" class="form-control" rows="3"></textarea>
            </div>
          </div>
        </fieldset>

        <div class="flex justify-end gap-3 mt-6">
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

    <!-- Modal de Desativação (SA) -->
    <dialog ref="deactivateDialog" class="bg-white p-6 rounded-lg shadow-md w-96">
      <h3 class="text-lg font-bold mb-4">Tem certeza que deseja desativar esta empresa?</h3>
      <div class="flex justify-end gap-2">
        <button type="button" @click="closeDeactivateModal" class="btn btn-secondary">Cancelar</button>
        <button type="button" @click="confirmDeactivateCompany" class="btn btn-danger">Desativar</button>
      </div>
    </dialog>

    <!-- Modal: Confirmar Promoção -->
    <dialog ref="promoteDialog" class="bg-white p-6 rounded-lg shadow-md w-96">
      <h3 class="text-lg font-bold mb-4">Tem certeza que deseja promover este utilizador a Company Admin?</h3>
      <div class="flex justify-end gap-2">
        <button type="button" @click="closePromoteUserModal" class="btn btn-secondary">Cancelar</button>
        <button type="button" @click="confirmPromoteUser" class="btn btn-success">Promover</button>
      </div>
    </dialog>

    <!-- Modal: Confirmar Remoção -->
    <dialog ref="removeUserDialog" class="bg-white p-6 rounded-lg shadow-md w-96">
      <h3 class="text-lg font-bold mb-4">Tem certeza que deseja remover este utilizador da empresa?</h3>
      <div class="flex justify-end gap-2">
        <button type="button" @click="closeRemoveUserModal" class="btn btn-secondary">Cancelar</button>
        <button type="button" @click="confirmRemoveUser" class="btn btn-danger">Remover</button>
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
    sector: c.sector,
    company_type: c.company_type,
    website: c.website,
    nif: c.nif,
    phone_contact: c.phone_contact,
    email_contact: c.email_contact,
    country: c.country,
    city: c.city,
    founded_year: c.founded_year,
    num_employees: c.num_employees,
    revenue_range: c.revenue_range,
    notes: c.notes
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
      company_type: editCompany.value.company_type,
      website: editCompany.value.website,
      nif: editCompany.value.nif,
      phone_contact: editCompany.value.phone_contact,
      email_contact: editCompany.value.email_contact,
      country: editCompany.value.country,
      city: editCompany.value.city,
      founded_year: editCompany.value.founded_year,
      num_employees: editCompany.value.num_employees,
      revenue_range: editCompany.value.revenue_range,
      notes: editCompany.value.notes
    });

    toast.success('Empresa atualizada com sucesso!');
    closeEditModal();
    await fetchCompany(); // Atualiza os dados
  } catch (error) {
    toast.error('Erro ao atualizar empresa.');
    console.error(error);
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
    await axios.post(`http://127.0.0.1:8000/api/companies/${company.value.id}/invite`, {
      email: inviteEmail.value
    });
    toast.success('Convite enviado para o email com sucesso!');
    inviteEmail.value = '';
    await fetchCompany();
  } catch (error) {
    toast.error(error.response?.data?.error || 'Erro ao enviar convite.');
    console.error(error);
  }
};

// Reenviar convite
const resendInvite = async (inviteId) => {
  try {
    await axios.put(`http://127.0.0.1:8000/api/invites/${inviteId}/resend`);
    toast.success('Convite reenviado com sucesso para o email!');
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

const removeUserFromCompany = async (ucrId) => {
  try {
    await axios.delete(`http://127.0.0.1:8000/api/user-company-roles/${ucrId}`);
    toast.success('Utilizador removido com sucesso!');
    await fetchCompany();
  } catch (error) {
    toast.error('Erro ao remover utilizador da empresa.');
    console.error(error);
  }
};

const deactivateDialog = ref(null);

const openDeactivateModal = () => {
  deactivateDialog.value?.showModal();
};

const closeDeactivateModal = () => {
  deactivateDialog.value?.close();
};

const confirmDeactivateCompany = async () => {
  try {
    await axios.put(`http://127.0.0.1:8000/api/companies/${company.value.id}/deactivate`);
    toast.success('Empresa desativada com sucesso!');
    closeDeactivateModal();
    await fetchCompany();
  } catch {
    toast.error('Erro ao desativar empresa.');
  }
};

const promoteDialog = ref(null);
const removeUserDialog = ref(null);
const userToPromote = ref(null);
const userToRemove = ref(null);

const openPromoteUserModal = (ucrId) => {
  userToPromote.value = ucrId;
  promoteDialog.value?.showModal();
};

const closePromoteUserModal = () => {
  userToPromote.value = null;
  promoteDialog.value?.close();
};

const confirmPromoteUser = async () => {
  if (!userToPromote.value) return;
  await promoteUser(userToPromote.value);
  closePromoteUserModal();
};

const openRemoveUserModal = (ucrId) => {
  userToRemove.value = ucrId;
  removeUserDialog.value?.showModal();
};

const closeRemoveUserModal = () => {
  userToRemove.value = null;
  removeUserDialog.value?.close();
};

const confirmRemoveUser = async () => {
  if (!userToRemove.value) return;
  await removeUserFromCompany(userToRemove.value);
  closeRemoveUserModal();
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
  background-color: #ffc107;
  color: white;
}

.btn-edit:hover {
  background-color: #e0a800;
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
