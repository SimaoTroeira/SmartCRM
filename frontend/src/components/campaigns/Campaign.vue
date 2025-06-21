<template>
  <div class="campaign-container">
    <div class="campaign-card">
      <div v-if="!roleLoaded || (!campaignsLoaded && campaigns.length === 0)" class="mb-4">
        <h3 class="text-xl font-semibold mb-4 text-gray-600">
          A carregar campanhas<span class="dot-anim"></span>
        </h3>
      </div>

      <div v-else>
        <h2 class="campaign-title">
          {{ userRole === 'SA' ? 'Painel de Administração de Campanhas:' : 'Lista de Campanhas:' }}
        </h2>

        <div v-if="userRole !== 'SA'" class="mb-4 text-right">
          <button @click="showDialog = true" class="btn btn-primary" :disabled="!hasActiveCompanies"
            :class="{ 'opacity-50 cursor-not-allowed': !hasActiveCompanies }">
            Criar nova campanha
          </button>
        </div>

        <div v-if="filteredCampaigns.length === 0" class="text-muted">
          <span v-if="userRole === 'SA'">Ainda não há campanhas registadas.</span>
          <span v-else-if="!hasActiveCompanies">Para criar campanhas, terá de ter uma empresa ativa.</span>
          <span v-else>Ainda não há campanhas registadas.</span>
        </div>

        <div v-else class="table-wrapper">
          <table class="campaign-table">
            <thead>
              <tr>
                <th>Título</th>
                <th>Descrição</th>
                <th>Empresa</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="campaign in paginatedCampaigns" :key="campaign.id" @click="goToCampaignDetails(campaign.id)"
                class="cursor-pointer hover:bg-gray-100 transition">
                <td>{{ campaign.name }}</td>
                <td>{{ campaign.description }}</td>
                <td>{{ campaign.company.name }}</td>
                <td>
                  <span :class="{
                    'status-active': campaign.status === 'active',
                    'status-pending': campaign.status === 'drafted',
                    'status-info': campaign.status === 'completed'
                  }">
                    {{ traduzirStatus(campaign.status) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="pagination-controls" v-if="filteredCampaigns.length > 0">
          <button @click="prevPage" :disabled="currentPage === 1" class="btn btn-secondary">Anterior</button>
          <span>Página {{ currentPage }} de {{ totalPages }}</span>
          <button @click="nextPage" :disabled="currentPage >= totalPages" class="btn btn-secondary">Próxima</button>

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

  <div v-if="showDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="modal-wrapper">
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
            <option disabled value="">Selecione uma empresa válida</option>
            <option v-for="company in companies.filter(c => c.status === 'Ativo')" :key="company.id"
              :value="company.id">
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
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { useRouter } from 'vue-router';
const router = useRouter();

const toast = useToast();
const showDialog = ref(false);
const campaigns = ref([]);
const companies = ref([]);
const userRole = ref('');
const roleLoaded = ref(false);
const form = ref({ name: '', description: '', company_id: '' });
const STATUS_ATIVO = 'Ativo';
const currentPage = ref(1);
const itemsPerPage = ref(10);
const campaignsLoaded = ref(false);

const hasActiveCompanies = computed(() => {
  return companies.value.some(company => company.status === STATUS_ATIVO);
});

const totalPages = computed(() => {
  const filtered = userRole.value === 'SA'
    ? campaigns.value.filter(c => c.company?.status === 'Ativo')
    : campaigns.value;

  return Math.ceil(filtered.length / itemsPerPage.value);
});

const paginatedCampaigns = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  return filteredCampaigns.value.slice(start, start + itemsPerPage.value);
});

const filteredCampaigns = computed(() => {
  return userRole.value === 'SA'
    ? campaigns.value.filter(c => c.company?.status === 'Ativo')
    : campaigns.value;
});
const goToCampaignDetails = (id) => {
  router.push({ name: 'CampaignDetails', params: { id } });
};

watch(itemsPerPage, () => {
  currentPage.value = 1;
});

const nextPage = () => {
  if (currentPage.value < totalPages.value) currentPage.value++;
};

const prevPage = () => {
  if (currentPage.value > 1) currentPage.value--;
};

async function fetchUserRole() {
  try {
    const res = await axios.get('/user');
    userRole.value = res.data.email === 'admin@admin.com' ? 'SA' : 'CA';
  } catch {
    toast.error('Erro ao obter papel do usuário.');
  } finally {
    roleLoaded.value = true;
  }
}

const fetchCampaigns = async () => {
  if (companies.value.length === 0) {
    campaigns.value = [];
    campaignsLoaded.value = true;
    return;
  }

  try {
    const res = await axios.get('/campaigns');
    let allCampaigns = Array.isArray(res.data) ? res.data : [];

    if (userRole.value === 'SA') {
      allCampaigns = allCampaigns.filter(c => c.company?.status === 'Ativo');
    }

    campaigns.value = allCampaigns;
  } catch (error) {
    toast.error('Erro ao carregar campanhas.');
    console.error(error);
  } finally {
    campaignsLoaded.value = true;
  }
};

const fetchCompanies = async () => {
  try {
    const res = await axios.get('/companies');
    companies.value = Array.isArray(res.data) ? res.data : [];
  } catch (error) {
    toast.error('Erro ao carregar empresas.');
    console.error(error);
  }
};

const createCampaign = async () => {
  try {
    const selectedCompany = companies.value.find(c => c.id === form.value.company_id);
    if (!selectedCompany || selectedCompany.status !== STATUS_ATIVO) {
      toast.error('Apenas empresas ativas podem ter campanhas.');
      return;
    }

    await axios.post('/campaigns', form.value);
    toast.success('Campanha criada com sucesso!');
    showDialog.value = false;
    form.value = { name: '', description: '', company_id: '' };
    fetchCampaigns();
  } catch (error) {
    toast.error('Erro ao criar campanha.');
    console.error(error);
  }
};

const traduzirStatus = (status) => {
  switch (status) {
    case 'draft': return 'Rascunho';
    case 'active': return 'Ativo';
    case 'completed': return 'Concluída';
    default: return status;
  }
};

onMounted(async () => {
  await fetchCompanies();
  await fetchUserRole();
  await fetchCampaigns();
});
</script>


<style scoped>
.campaign-container {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  background: #f1f5f9;
  min-height: 90vh;
  padding: 2rem;
}

.campaign-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
  width: 100%;
  max-width: 1100px;
}

.campaign-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  color: #1e293b;
}

.table-wrapper {
  overflow-x: auto;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}

.campaign-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 0.95rem;
}

.campaign-table th,
.campaign-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.campaign-table th {
  background-color: #f8fafc;
  font-weight: 600;
  color: #334155;
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

.btn-primary {
  background-color: #3b82f6;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-weight: 500;
  transition: background-color 0.2s ease;
}

.btn-primary:hover {
  background-color: #2563eb;
}

.btn-success {
  background-color: #22c55e;
  color: white;
  transition: background-color 0.2s ease;
}

.btn-success:hover {
  background-color: #15803d;
}

.btn-secondary {
  background-color: #3b82f6;
  color: white;
  transition: background-color 0.2s ease;
}

.btn-secondary:hover {
  background-color: #2563eb;
}

.form-control {
  width: 100%;
  padding: 8px 12px;
  margin-top: 4px;
  border-radius: 4px;
  border: 1px solid #ddd;
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

.transition-all {
  transition: all 0.3s ease-in-out;
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

.campaign-table tbody tr {
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.campaign-table tbody tr:hover {
  background-color: #f1f5f9;
}
</style>
