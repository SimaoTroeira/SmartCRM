<template>
  <div class="company-container">
    <div class="company-card">
      <div v-if="!roleLoaded || !companiesLoaded" class="mb-4">
        <h3 class="text-xl font-semibold mb-4 text-gray-600">
          A carregar empresas<span class="dot-anim"></span>
        </h3>
      </div>

      <div v-else>
        <h2 class="company-title">
          {{ userRole === 'SA' ? 'Painel de Administração de Empresas:' : 'Lista de Empresas:' }}
        </h2>

        <div v-if="userRole === 'SA' && companies.length > 0" class="filter-row">
          <label class="filter-label">Filtrar por estado</label>
          <select v-model="filterState" class="form-control">
            <option value="Todos">Todos</option>
            <option value="Ativo">Ativo</option>
            <option value="Inativo">Inativo</option>
          </select>
        </div>

        <div v-if="userRole !== 'SA'" class="mb-4 text-right">
          <button @click="showDialog = true" class="btn btn-primary">
            Registar nova empresa
          </button>
        </div>

        <div v-if="companies.length === 0" class="text-muted">Ainda não há empresas registadas.</div>
        <div v-else-if="filteredCompanies.length === 0" class="text-muted">Nenhuma empresa corresponde aos filtros
          aplicados.</div>

        <div v-else class="table-wrapper">
          <table class="company-table">
            <thead>
              <tr>
                <th>Empresa</th>
                <th>Setor</th>
                <th>Estado Atual</th>
                <th v-if="userRole !== 'SA'">Pedido</th>
                <th v-if="userRole === 'SA'">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="company in paginatedCompanies" :key="company.id" @click="goToCompanyDetails(company.id)"
                class="hover:bg-gray-50 transition cursor-pointer">
                <td>{{ company.name }}</td>
                <td>{{ company.sector }}</td>
                <td>
                  <span :class="company.status === 'Ativo' ? 'status-active' : 'status-pending'">
                    {{ company.status }}
                  </span>
                </td>

                <td v-if="userRole !== 'SA'">
                  <span v-if="company.status === 'Ativo'" class="status-active">Pedido aceite</span>

                  <button v-else-if="company.status === 'Inativo' && !company.submitted"
                    @click.stop="openSubmitModal(company.id)" class="btn-sm btn-secondary">
                    Pedir validação
                  </button>

                  <span v-else-if="company.status === 'Inativo' && company.submitted" class="status-info">
                    Aguardando aprovação
                  </span>
                </td>

                <td v-if="userRole === 'SA'">
                  <button v-if="company.status === 'Inativo'" @click.stop="openAcceptModal(company.id)"
                    class="btn-sm btn-success">
                    Ativar
                  </button>

                  <button v-else @click.stop="openDeactivateModal(company.id)" class="btn-sm btn-danger">
                    Desativar
                  </button>
                </td>
              </tr>

            </tbody>
          </table>
        </div>

        <div class="pagination-controls" v-if="filteredCompanies.length > 0">
          <button @click="currentPage--" :disabled="currentPage === 1" class="btn btn-secondary">Anterior</button>
          <span>Página {{ currentPage }} de {{ totalPages }}</span>
          <button @click="currentPage++" :disabled="currentPage >= totalPages"
            class="btn btn-secondary">Próxima</button>

          <div class="page-size-select">
            <label>Ver por página:</label>
            <select v-model="itemsPerPage" class="form-control">
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div v-if="showDialog && userRole !== 'SA'"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 transition-all duration-300 ease-in-out">
    <div class="modal-wrapper">
      <h3 class="text-lg font-bold mb-4 text-center">Registar nova empresa</h3>

      <div class="modal-scroll">
        <fieldset class="mb-6">
          <legend class="section-title">Informações Gerais</legend>

          <div class="grid grid-cols-12 gap-4">
            <div class="col-span-6">
              <label class="label">Nome da Empresa <span class="text-red-600">*</span></label>
              <input v-model="companyForm.name" class="form-control" />
              <p v-if="companyFormErrors.name" class="text-red-600 text-sm mt-1">
                {{ companyFormErrors.name }}
              </p>
            </div>

            <div class="col-span-6">
              <label class="label">Setor de atividade <span class="text-red-600">*</span></label>
              <input v-model="companyForm.sector" class="form-control" />
              <p v-if="companyFormErrors.sector" class="text-red-600 text-sm mt-1">
                {{ companyFormErrors.sector }}
              </p>
            </div>

            <div class="col-span-6">
              <label class="label">Tipo de Empresa <span class="text-red-600">*</span></label>
              <select v-model="companyForm.company_type" class="form-control">
                <option disabled value="">Selecione…</option>
                <option value="Freelancer">Freelancer</option>
                <option value="Startup">Startup</option>
                <option value="ME">Média Empresa</option>
                <option value="PE">Pequena Empresa</option>
                <option value="GE">Grande Empresa</option>
              </select>
              <p v-if="companyFormErrors.company_type" class="text-red-600 text-sm mt-1">
                {{ companyFormErrors.company_type }}
              </p>
            </div>
          </div>
        </fieldset>
        <!-- Apenas a parte do formulário atualizada com validações para campos opcionais -->
        <fieldset class="mb-6">
          <legend class="section-title">Informações Gerais</legend>
          <div class="grid grid-cols-12 gap-4">
            <div class="col-span-6">
              <label class="label">Website</label>
              <input v-model="companyForm.website" class="form-control" type="url" placeholder="https://…" />
              <p v-if="companyFormErrors.website" class="text-red-600 text-sm mt-1">{{ companyFormErrors.website }}</p>
              <p v-else-if="companyForm.website" class="text-green-600 text-sm mt-1">Website válido</p>
            </div>
          </div>
        </fieldset>

        <fieldset>
          <legend class="section-title">Contactos e Outros Dados</legend>

          <div class="grid grid-cols-12 gap-4">
            <div class="col-span-6">
              <label class="label">NIF</label>
              <input v-model="companyForm.nif" class="form-control" />
              <p v-if="companyFormErrors.nif" class="text-red-600 text-sm mt-1">{{ companyFormErrors.nif }}</p>
              <p v-else-if="companyForm.nif" class="text-green-600 text-sm mt-1">NIF válido</p>
            </div>
            <div class="col-span-6">
              <label class="label">Telefone</label>
              <input v-model="companyForm.phone_contact" class="form-control" />
              <p v-if="companyFormErrors.phone_contact" class="text-red-600 text-sm mt-1">{{
                companyFormErrors.phone_contact }}</p>
              <p v-else-if="companyForm.phone_contact" class="text-green-600 text-sm mt-1">Telefone válido</p>
            </div>
            <div class="col-span-6">
              <label class="label">Email</label>
              <input v-model="companyForm.email_contact" class="form-control" type="email" />
              <p v-if="companyFormErrors.email_contact" class="text-red-600 text-sm mt-1">{{
                companyFormErrors.email_contact }}</p>
              <p v-else-if="companyForm.email_contact" class="text-green-600 text-sm mt-1">Email válido</p>
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
              <p v-if="companyFormErrors.founded_year" class="text-red-600 text-sm mt-1">{{
                companyFormErrors.founded_year }}</p>
              <p v-else-if="companyForm.founded_year" class="text-green-600 text-sm mt-1">Ano válido</p>
            </div>

            <div class="col-span-6">
              <label class="label">Número de colaboradores</label>
              <input v-model="companyForm.num_employees" class="form-control" type="number" min="1" />
            </div>

            <div class="col-span-12">
              <label class="label">Intervalo de faturacão</label>
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

  <div v-if="showAcceptModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="modal-wrapper">
      <h3 class="text-lg font-bold mb-4 text-center">
        Tem certeza que deseja aceitar o registo desta empresa?
      </h3>
      <div class="flex justify-end gap-2">
        <button type="button" @click="closeAcceptModal" class="btn btn-secondary">Cancelar</button>
        <button type="button" @click="acceptCompany" class="btn btn-success">Aceitar</button>
      </div>
    </div>
  </div>



  <div v-if="showSubmitModal"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 transition-all duration-300 ease-in-out">
    <div class="modal-wrapper w-96">
      <h3 class="text-lg font-bold mb-4">Deseja pedir a validação desta empresa?</h3>
      <div class="flex justify-end gap-2">
        <button type="button" @click="closeSubmitModal" class="btn btn-secondary">Cancelar</button>
        <button type="button" @click="confirmRequestValidation" class="btn btn-success">Confirmar</button>
      </div>
    </div>
  </div>

  <div v-if="showDeactivateModal"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 transition-all duration-300 ease-in-out">
    <div class="modal-wrapper w-96">
      <h3 class="text-lg font-bold mb-4 text-center">Tem certeza que deseja desativar esta empresa?</h3>
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
import { useRouter } from 'vue-router';

const router = useRouter();
const toast = useToast();

const goToCompanyDetails = (id) => {
  router.push({ name: 'CompanyDetails', params: { id } });
};

const showDialog = ref(false);
const showAcceptModal = ref(false);
const companyToAccept = ref(null);
const companies = ref([]);
const showSubmitModal = ref(false);
const companyToSubmit = ref(null);
const showDeactivateModal = ref(false);
const companyToDeactivate = ref(null);
const userRole = ref('');
const roleLoaded = ref(false);
const filterState = ref(localStorage.getItem('filterState') || 'Todos');
const currentPage = ref(1);
const itemsPerPage = ref(10);
const companiesLoaded = ref(false);
const dadosProntos = computed(() => companiesLoaded.value && roleLoaded.value)


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

const companyFormErrors = ref({
  name: '',
  sector: '',
  company_type: '',
});

watch(filterState, (newVal) => {
  localStorage.setItem('filterState', newVal);
});

watch(itemsPerPage, () => {
  currentPage.value = 1;
});

watch(() => companyForm.value.website, (val) => {
  if (!val) return companyFormErrors.value.website = '';
  const pattern = /^https?:\/\/[\w\-\.]+\.[a-z]{2,}.*$/i;
  companyFormErrors.value.website = pattern.test(val.trim()) ? '' : 'URL inválido.';
});

watch(() => companyForm.value.email_contact, (val) => {
  if (!val) return companyFormErrors.value.email_contact = '';
  const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  companyFormErrors.value.email_contact = pattern.test(val.trim()) ? '' : 'Email inválido.';
});

watch(() => companyForm.value.phone_contact, (val) => {
  if (!val) return companyFormErrors.value.phone_contact = '';
  const pattern = /^\+?[0-9]{9,15}$/;
  companyFormErrors.value.phone_contact = pattern.test(val.trim()) ? '' : 'Telefone inválido.';
});

watch(() => companyForm.value.nif, (val) => {
  if (!val) return companyFormErrors.value.nif = '';
  const pattern = /^[0-9]{9}$/;
  companyFormErrors.value.nif = pattern.test(val.trim()) ? '' : 'NIF inválido (9 dígitos).';
});

watch(() => companyForm.value.founded_year, (val) => {
  if (!val) return companyFormErrors.value.founded_year = '';
  companyFormErrors.value.founded_year = val >= 1800 && val <= 2099 ? '' : 'Ano inválido.';
});


const refreshAll = async () => {
  await fetchCompanies();
  await fetchUserRole();
};

const fetchUserRole = async () => {
  try {
    const res = await axios.get('/user');
    const email = res.data.email;
    if (email === 'admin@admin.com') {
      userRole.value = 'SA';
    } else {
      const companiesRes = await axios.get('/companies');
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
    const response = await axios.get('/companies');
    companies.value = Array.isArray(response.data) ? response.data : [];
  } catch (error) {
    toast.error('Erro ao carregar empresas.');
    companies.value = [];
  } finally {
    companiesLoaded.value = true;
  }
};

const registerCompany = async () => {
  companyFormErrors.value = {
    name: '',
    sector: '',
    company_type: '',
  };

  let valid = true;

  if (!companyForm.value.name) {
    companyFormErrors.value.name = 'O nome da empresa é obrigatório.';
    valid = false;
  }
  if (!companyForm.value.sector) {
    companyFormErrors.value.sector = 'O setor de atividade é obrigatório.';
    valid = false;
  }
  if (!companyForm.value.company_type) {
    companyFormErrors.value.company_type = 'Por favor, selecione o tipo de empresa.';
    valid = false;
  }

  if (!valid) return;

  try {
    await axios.post('/companies', companyForm.value);
    toast.success('Empresa registrada com sucesso!');
    showDialog.value = false;
    companyForm.value = {
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
    };
    await refreshAll();
  } catch (error) {
    toast.error(error.response?.data?.error || 'Erro ao registrar empresa.');
  }
};

const acceptCompany = async () => {
  try {
    await axios.post(`/companies/${companyToAccept.value}/approve`);
    toast.success('Empresa aceite com sucesso!');
    await refreshAll();
    closeAcceptModal();
  } catch (error) {
    toast.error('Erro ao aceitar empresa.');
  }
};

const requestValidation = async (companyId) => {
  try {
    await axios.post(`/companies/${companyId}/submit`);
    toast.success('Pedido de validação enviado.');
    await fetchCompanies();
  } catch (error) {
    toast.error(error.response?.data?.error || 'Erro ao enviar pedido de validação.');
  }
};

const confirmRequestValidation = async () => {
  if (!companyToSubmit.value) {
    toast.error('Empresa inválida.');
    return;
  }
  await requestValidation(companyToSubmit.value);
  closeSubmitModal();
};

const deactivateCompany = async () => {
  try {
    await axios.put(`/companies/${companyToDeactivate.value}/deactivate`);
    toast.success('Empresa desativada com sucesso!');
    await refreshAll();
    closeDeactivateModal();
  } catch (error) {
    toast.error('Erro ao desativar empresa.');
    console.error(error);
  }
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

const openDeactivateModal = (companyId) => {
  companyToDeactivate.value = companyId;
  showDeactivateModal.value = true;
};

const closeDeactivateModal = () => {
  companyToDeactivate.value = null;
  showDeactivateModal.value = false;
};

const filteredCompanies = computed(() => {
  return companies.value.filter(company => {

    if (filterState.value === 'Todos') return true;

    if (company.status === filterState.value) return true;

    if (userRole.value === 'SA' && filterState.value === 'Inativo' && company.submitted === false) return true;

    return false;
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

onMounted(refreshAll);
</script>


<style scoped>
.company-container {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  background: #f1f5f9;
  min-height: 90vh;
  padding: 2rem;
}

.company-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
  width: 100%;
  max-width: 1100px;
}

.company-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  color: #1e293b;
}

.filter-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.filter-label {
  font-weight: 500;
}

.table-wrapper {
  overflow-x: auto;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}

.company-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 0.95rem;
}

.company-table th,
.company-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.company-table th {
  background-color: #f8fafc;
  font-weight: 600;
  color: #334155;
}

.link {
  color: #3b82f6;
  text-decoration: underline;
}

.link:hover {
  color: #1d4ed8;
}

.status-active {
  color: #16a34a;
  font-weight: 600;
}

.status-pending {
  color: #ca8a04;
  font-weight: 600;
}

.status-info {
  color: #0ea5e9;
  font-weight: 500;
}

.btn-sm {
  font-size: 0.875rem;
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
  border: none;
  cursor: pointer;
}

.btn-success {
  background-color: #22c55e;
  color: white;
}

.btn-danger {
  background-color: #ef4444;
  color: white;
}

.btn-secondary {
  background-color: #3b82f6;
  color: white;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-top: 1.5rem;
  flex-wrap: wrap;
}

.page-size-select {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-wrapper {
  background: white;
  padding: 1rem 2rem 1.5rem 2rem;
  border-radius: 0.75rem;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
  width: 600px;
  max-width: 90vw;
  max-height: 85vh;
  overflow: hidden;

  display: flex;
  flex-direction: column;

  position: relative;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}


.modal-scroll {
  overflow-y: auto;
  max-height: calc(80vh - 120px);
  padding-right: 6px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
  background: white;
}

.scale-95 {
  transform: scale(0.95);
  opacity: 0;
  transition: all 0.3s ease-in-out;
}

.scale-100 {
  transform: scale(1);
  opacity: 1;
  transition: all 0.3s ease-in-out;
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

select.form-control {
  width: 180px;
  padding: 0.4rem 0.6rem;
}

.link {
  color: #3b82f6;
  font-weight: 500;
  text-decoration: none;
  transition: color 0.2s ease;
}

.link:hover {
  color: #1d4ed8;
  text-decoration: underline;
}

.company-title {
  margin-bottom: 0.75rem;
}

.filter-row {
  margin-bottom: 1.25rem;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-weight: 500;
}

.btn-primary:hover {
  background-color: #2563eb;
}

.filter-row select.form-control {
  min-width: 160px;
  margin-left: 0.5rem;
}

.company-table tbody tr {
  cursor: pointer;
}

.company-table tbody tr button {
  cursor: default;
}

button {
  cursor: pointer;
}

.btn-sm,
.btn-primary,
.btn-secondary,
.btn-success,
.btn-danger {
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.btn-sm.btn-secondary:hover {
  background-color: #2563eb;
  color: white;
}

.company-table tbody tr button {
  cursor: pointer;
}

tr:hover {
  background-color: #f1f5f9;
}

.dot-anim::after {
  content: ".";
  animation: dots 1.2s steps(3, end) infinite;
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
</style>