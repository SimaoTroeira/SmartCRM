<template>
  <div class="company">

    <div v-if="!roleLoaded || !companiesLoaded">
      <h3 class="text-xl font-semibold mb-4 text-gray-600">
        A carregar empresas<span class="dot-anim"></span>
      </h3>
    </div>

    <div v-else>
      <h2 class="text-xl font-semibold mb-4">
        {{ userRole === 'SA' ? 'Painel de Administração de Empresas:' : 'Lista de Empresas:' }}
      </h2>

      <div v-if="userRole === 'SA' && companies.length > 0" class="filters-container mb-4 flex gap-4">
        <div>
          <label class="block font-medium mb-1">Filtrar por estado</label>
          <select v-model="filterState" class="form-control">
            <option value="Todos">Todos</option>
            <option value="Ativo">Ativo</option>
            <option value="Inativo">Inativo</option>
          </select>
        </div>
      </div>

      <div v-if="userRole !== 'SA'" class="mb-4 ">
        <button @click="showDialog = true" class="btn btn-primary">
          Registar nova empresa
        </button>
      </div>

      <div v-if="companies.length === 0" class="text-gray-500 mb-4">
        Ainda não há empresas registadas.
      </div>

      <div v-else-if="filteredCompanies.length === 0" class="text-gray-500 mb-4">
        Nenhuma empresa corresponde aos filtros aplicados.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full table-auto border border-gray-200">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-4 py-2 border">Empresa</th>
              <th class="px-4 py-2 border">Setor</th>
              <th class="px-4 py-2 border">Estado Atual</th>
              <th class="px-4 py-2 border" v-if="userRole !== 'SA'">Pedido de Validação</th>
              <th class="px-4 py-2 border" v-if="userRole === 'SA'">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="company in paginatedCompanies" :key="company.id" class="hover:bg-gray-50">
              <td class="px-4 py-2 border">
                <router-link :to="{ name: 'CompanyDetails', params: { id: company.id } }"
                  class="text-blue-600 underline hover:text-blue-800 cursor-pointer">
                  {{ company.name }}
                </router-link>
              </td>
              <td class="px-4 py-2 border">{{ company.sector }}</td>
              <td class="px-4 py-2 border">
                <span
                  :class="company.status === 'Ativo' ? 'text-green-600 font-semibold' : 'text-yellow-600 font-semibold'">
                  {{ company.status }}
                </span>
              </td>

              <td v-if="userRole !== 'SA'" class="px-4 py-2 border text-center">
                <span v-if="company.status === 'Ativo'" class="text-green-600 font-medium">
                  Pedido aceite
                </span>
                <button v-else-if="company.status === 'Inativo' && !company.submitted"
                  @click="openSubmitModal(company.id)" class="btn btn-secondary text-white text-sm px-3 py-1 rounded">
                  Pedir validação
                </button>
                <span v-else-if="company.status === 'Inativo' && company.submitted" class="text-blue-500 font-medium">
                  Aguardando aprovação
                </span>
              </td>

              <td v-if="userRole === 'SA'" class="px-4 py-2 border text-center">
                <button v-if="company.status === 'Inativo'" @click="openAcceptModal(company.id)"
                  class="btn-success text-white px-4 py-2 rounded mr-2">
                  Ativar
                </button>
                <button v-else @click="openDeactivateModal(company.id)" class="btn-remove text-white px-4 py-2 rounded">
                  Desativar
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="filteredCompanies.length > 0" class="pagination-left mt-4 gap-4 items-center">
        <button @click="currentPage--" :disabled="currentPage === 1" class="btn btn-secondary">
          Anterior
        </button>

        <span class="mx-2">Página {{ currentPage }} de {{ totalPages }}</span>

        <button @click="currentPage++" :disabled="currentPage >= totalPages" class="btn btn-secondary">
          Próxima
        </button>

        <div class="ml-6">
          <label class="mr-2 font-medium">Ver por página:</label>
          <select v-model="itemsPerPage" class="form-control w-24 inline-block">
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
      </div>
    </div>
    <div v-if="showDialog && userRole !== 'SA'"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-start justify-center z-50 transition-all duration-300 ease-in-out">
      <div class="modal-wrapper">
        <h3 class="text-lg font-bold mb-4 text-center">Registar nova empresa</h3>

        <div class="modal-scroll">
          <fieldset class="mb-6">
            <legend class="section-title">Informações Gerais</legend>

            <div class="grid grid-cols-12 gap-4">
              <div class="col-span-6">
                <label class="label">Nome da Empresa</label>
                <input v-model="companyForm.name" class="form-control" required />
              </div>

              <div class="col-span-6">
                <label class="label">Setor de atividade</label>
                <input v-model="companyForm.sector" class="form-control" required />
              </div>

              <div class="col-span-6">
                <label class="label">Tipo de Empresa</label>
                <select v-model="companyForm.company_type" class="form-control" required>
                  <option disabled value="">Selecione…</option>
                  <option value="Freelancer">Freelancer</option>
                  <option value="Startup">Startup</option>
                  <option value="PME">PME</option>
                  <option value="Corporação">Corporação</option>
                </select>
              </div>

              <div class="col-span-6">
                <label class="label">Website</label>
                <input v-model="companyForm.website" class="form-control" type="url" placeholder="https://…" />
              </div>
            </div>
          </fieldset>

          <fieldset>
            <legend class="section-title">Contactos e Outros Dados</legend>

            <div class="grid grid-cols-12 gap-4">
              <div class="col-span-6">
                <label class="label">NIF</label>
                <input v-model="companyForm.nif" class="form-control" />
              </div>
              <div class="col-span-6">
                <label class="label">Telefone</label>
                <input v-model="companyForm.phone_contact" class="form-control" />
              </div>
              <div class="col-span-6">
                <label class="label">Email</label>
                <input v-model="companyForm.email_contact" class="form-control" type="email" />
              </div>

              <div class="col-span-6">
                <label class="label">País</label>
                <input v-model="companyForm.country" class="form-control" />
              </div>

              <div class="col-span-6">
                <label class="label">Cidade</label>
                <input v-model="companyForm.city" class="form-control" />
              </div>

              <div class="col-span-6">
                <label class="label">Ano da Fundação</label>
                <input v-model="companyForm.founded_year" class="form-control" type="number" min="1800" max="2099" />
              </div>

              <div class="col-span-6">
                <label class="label">Número de colaboradores</label>
                <input v-model="companyForm.num_employees" class="form-control" type="number" min="1" />
              </div>

              <div class="col-span-12">
                <label class="label">Intervalo de faturação</label>
                <select v-model="companyForm.revenue_range" class="form-control">
                  <option disabled value="">Selecione…</option>
                  <option value="0-1M">0-1 M €</option>
                  <option value="1M-10M">1-10 M €</option>
                  <option value="10M-100M">10-100 M €</option>
                  <option value="100M+">+100 M €</option>
                </select>
              </div>

              <div class="col-span-12">
                <label class="label">Notas Internas</label>
                <textarea v-model="companyForm.notes" class="form-control" rows="3"></textarea>
              </div>
            </div>
          </fieldset>
        </div>

        <div class="modal-footer">
          <button type="button" @click="showDialog = false" class="btn btn-secondary text-white">Cancelar</button>
          <button type="submit" class="btn btn-success text-white" @click="registerCompany">Registar</button>
        </div>
      </div>
    </div>

    <div v-if="showAcceptModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 transition-all duration-300 ease-in-out">
      <div class="bg-white p-6 rounded-lg shadow-md w-96 transform transition-all duration-300"
        :class="{ 'scale-100 opacity-100': showAcceptModal, 'scale-95 opacity-0': !showAcceptModal }">
        <h3 class="text-lg font-bold mb-4">Tem certeza que deseja aceitar o registo desta empresa?</h3>
        <div class="flex justify-end gap-2">
          <button type="button" @click="closeAcceptModal" class="btn btn-secondary">Cancelar</button>
          <button type="button" @click="acceptCompany" class="btn btn-success">Aceitar</button>
        </div>
      </div>
    </div>
  </div>
  <div v-if="showSubmitModal"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 transition-all duration-300 ease-in-out">
    <div class="bg-white p-6 rounded-lg shadow-md w-96 transform transition-all duration-300"
      :class="{ 'scale-100 opacity-100': showSubmitModal, 'scale-95 opacity-0': !showSubmitModal }">
      <h3 class="text-lg font-bold mb-4">Deseja pedir a validação desta empresa?</h3>
      <div class="flex justify-end gap-2">
        <button type="button" @click="closeSubmitModal" class="btn btn-secondary">Cancelar</button>
        <button type="button" @click="confirmRequestValidation" class="btn btn-success">Confirmar</button>
      </div>
    </div>
  </div>

  <div v-if="showDeactivateModal"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 transition-all duration-300 ease-in-out">
    <div class="bg-white p-6 rounded-lg shadow-md w-96 transform transition-all duration-300"
      :class="{ 'scale-100 opacity-100': showDeactivateModal, 'scale-95 opacity-0': !showDeactivateModal }">
      <h3 class="text-lg font-bold mb-4">Tem certeza que deseja desativar esta empresa?</h3>
      <div class="flex justify-end gap-2">
        <button type="button" @click="closeDeactivateModal" class="btn btn-secondary">Cancelar</button>
        <button type="button" @click="deactivateCompany" class="btn btn-danger">Desativar</button>
      </div>
    </div>
  </div>

</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const toast = useToast();
const showDialog = ref(false);
const showAcceptModal = ref(false);
const companyToAccept = ref(null);
const companies = ref([]);
const companyForm = ref({
  name: '',
  sector: '',
  company_type: '',
  website: '',
  email_contact: '',
  phone_contact: '',
  nif: '',
  country: '',
  city: '',
  founded_year: '',
  num_employees: '',
  revenue_range: '',
  notes: '',
});

const userRole = ref('');
const roleLoaded = ref(false);
const filterState = ref(localStorage.getItem('filterState') || 'Todos');
const currentPage = ref(1);
const itemsPerPage = ref(10);
const companiesLoaded = ref(false);

const showSubmitModal = ref(false);
const companyToSubmit = ref(null);

const showDeactivateModal = ref(false);
const companyToDeactivate = ref(null);

const refreshAll = async () => {
  await fetchCompanies();
  await fetchUserRole();
};

watch(filterState, (newVal) => {
  localStorage.setItem('filterState', newVal);
});


watch(itemsPerPage, () => {
  currentPage.value = 1;
});


const fetchUserRole = async () => {
  try {
    const res = await axios.get('http://127.0.0.1:8000/api/user');
    const email = res.data.email;
    if (email === 'admin@admin.com') {
      userRole.value = 'SA';
    } else {
      const companiesRes = await axios.get('http://127.0.0.1:8000/api/companies');
      const myCompanies = companiesRes.data;
      userRole.value = myCompanies.length > 0 ? 'CA' : '';
    }
  } catch (error) {
    console.error('Erro ao obter o papel do usuário:', error);
  } finally {
    roleLoaded.value = true;
  }
};

const fetchCompanies = async () => {
  try {
    const response = await axios.get('http://127.0.0.1:8000/api/companies');
    companies.value = Array.isArray(response.data) ? response.data : [];
  } catch (error) {
    toast.error('Erro ao carregar empresas.');
    companies.value = [];
  } finally {
    companiesLoaded.value = true;
  }
};


const registerCompany = async () => {
  try {
    await axios.post('http://127.0.0.1:8000/api/companies', companyForm.value);
    toast.success('Empresa registrada com sucesso!');
    showDialog.value = false;
    companyForm.value = { name: '', sector: '' };
    await refreshAll();
  } catch (error) {
    if (error.response?.data?.error) {
      toast.error(error.response.data.error);
    } else if (error.response?.data?.errors) {
      toast.error('Já existe uma empresa registada com esse nome.');
      toast.error(firstError);
    } else {
      toast.error('Erro ao registrar empresa. Verifique os dados inseridos.');
    }
  }


};

const acceptCompany = async () => {
  try {
    await axios.post(`http://127.0.0.1:8000/api/companies/${companyToAccept.value}/approve`);
    toast.success('Empresa aceite com sucesso!');
    await refreshAll();
    closeAcceptModal();
  } catch (error) {
    toast.error('Erro ao aceitar empresa.');
  }
};

const requestValidation = async (companyId) => {
  try {
    await axios.post(`http://127.0.0.1:8000/api/companies/${companyId}/submit`);
    toast.success('Pedido de validação enviado.');
    await fetchCompanies();
  } catch (error) {
    toast.error(
      error.response?.data?.error || 'Erro ao enviar pedido de validação.'
    );
  }
};

const confirmRequestValidation = async () => {
  if (!companyToSubmit.value) return;
  await requestValidation(companyToSubmit.value);
  closeSubmitModal();
};


const openAcceptModal = (companyId) => {
  companyToAccept.value = companyId;
  showAcceptModal.value = true;
};

const closeAcceptModal = () => {
  companyToAccept.value = null;
  showAcceptModal.value = false;
};

const openSubmitModal = (companyId) => {
  companyToSubmit.value = companyId;
  showSubmitModal.value = true;
};

const closeSubmitModal = () => {
  companyToSubmit.value = null;
  showSubmitModal.value = false;
};


const filteredCompanies = computed(() => {
  return companies.value.filter(company => {
    return filterState.value === 'Todos' || company.status === filterState.value;
  });
});

const paginatedCompanies = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return filteredCompanies.value.slice(start, end);
});

const totalPages = computed(() => {
  return Math.ceil(filteredCompanies.value.length / itemsPerPage.value);
});

const openDeactivateModal = (companyId) => {
  companyToDeactivate.value = companyId;
  showDeactivateModal.value = true;
};

const closeDeactivateModal = () => {
  companyToDeactivate.value = null;
  showDeactivateModal.value = false;
};

const deactivateCompany = async () => {
  try {
    await axios.put(`http://127.0.0.1:8000/api/companies/${companyToDeactivate.value}/deactivate`);
    toast.success('Empresa desativada com sucesso!');
    await refreshAll();
    closeDeactivateModal();
  } catch (error) {
    toast.error('Erro ao desativar empresa.');
    console.error(error);
  }
};



onMounted(refreshAll);
</script>

<style scoped>
.btn-primary {
  background-color: #4CAF50;
  color: white;
}

.btn-primary:hover {
  background-color: #45a049;
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

.btn-remove {
  background-color: #dc3545 !important;
  color: white !important;
}

.btn-remove:hover {
  background-color: #c82333 !important;
}

.form-control {
  width: 100%;
  padding: 8px 12px;
  margin-top: 4px;
  border-radius: 4px;
  border: 1px solid #ddd;
  text-align: center;
}

.bg-opacity-50 {
  background-color: rgba(0, 0, 0, 0.5);
}

.fixed {
  position: fixed;
}

.inset-0 {
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

.z-50 {
  z-index: 50;
}

.bg-white {
  background-color: white;
}

.p-6 {
  padding: 1.5rem;
}

.rounded-lg {
  border-radius: 0.5rem;
}

.shadow-md {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.w-96 {
  width: 24rem;
}

.flex {
  display: flex;
  align-items: center;
  justify-content: center;
}

.scale-95 {
  transform: scale(0.95);
  opacity: 0;
}

.scale-100 {
  transform: scale(1);
  opacity: 1;
}

.transition-all {
  transition: all 0.3s ease-in-out;
}

.company {
  padding: 20px;
}

.filters-container {
  justify-content: flex-start;
  align-items: flex-start;
  margin-left: 0;
}

.pagination-left {
  display: flex;
  justify-content: flex-start;
  align-items: center;
  margin-left: 0;
}

.modal-wrapper {
  background: white;
  padding: 1rem 2rem 1.5rem 2rem;
  /* top, right, bottom, left */
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  width: 500px;
  max-width: 90vw;
  max-height: 85vh;
  transform: scale(1);
  opacity: 1;
  overflow: hidden;
  margin-top: 60px;
  display: flex;
  flex-direction: column;
}


@keyframes dots {
  0% {
    content: "";
  }

  33% {
    content: ".";
  }

  66% {
    content: "..";
  }

  100% {
    content: "...";
  }
}

.dot-anim::after {
  content: ".";
  animation: dots 1.2s steps(3, end) infinite;
}

textarea.form-control {
  resize: vertical;
  min-height: 80px;
}

.modal-container {
  width: 600px;
  max-height: 80vh;
  background: #fff;
  border-radius: 0.5rem;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, .1);
  display: flex;
  flex-direction: column;
}

.modal-scroll {
  overflow-y: auto;
  max-height: calc(80vh - 96px);
  padding-right: 4px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding-top: 1rem;
  border-top: 1px solid #eee;
  background: #fff;
}
</style>
