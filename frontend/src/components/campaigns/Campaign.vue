<template>
  <div class="campaign">
    <div v-if="!roleLoaded || (!campaignsLoaded && campaigns.length === 0)" class="mb-4">
      <h3 class="text-xl font-semibold mb-4 text-gray-600">
        A carregar campanhas<span class="dot-anim"></span>
      </h3>
    </div>

    <div v-else>
      <h2 class="text-xl font-semibold mb-4">
        {{ userRole === 'SA' ? 'Painel de Administração de Campanhas:' : 'Lista de Campanhas:' }}
      </h2>

      <div v-if="userRole !== 'SA'" class="mb-4">
        <button @click="showDialog = true" class="btn btn-primary" :disabled="!hasActiveCompanies"
          :class="{ 'opacity-50 cursor-not-allowed': !hasActiveCompanies }">
          Criar nova campanha
        </button>
      </div>

      <div v-if="filteredCampaigns.length === 0" class="text-gray-500 mb-4">
        <span v-if="userRole === 'SA'">Ainda não há campanhas registadas.</span>
        <span v-else-if="!hasActiveCompanies">Para criar campanhas, terá de ter uma empresa ativa.</span>
        <span v-else>Ainda não há campanhas registadas.</span>
      </div>

      <div v-if="filteredCampaigns.length > 0" class="overflow-x-auto">
        <table class="min-w-full table-auto border border-gray-200">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-4 py-2 border">Título</th>
              <th class="px-4 py-2 border">Descrição</th>
              <th class="px-4 py-2 border">Empresa</th>
              <th class="px-4 py-2 border">Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="campaign in paginatedCampaigns" :key="campaign.id" class="hover:bg-gray-50">
              <td class="px-4 py-2 border">
                <router-link :to="{ name: 'CampaignDetails', params: { id: campaign.id } }"
                  class="text-blue-600 underline hover:text-blue-800 cursor-pointer">
                  {{ campaign.name }}
                </router-link>
              </td>
              <td class="px-4 py-2 border">{{ campaign.description }}</td>
              <td class="px-4 py-2 border">{{ campaign.company.name }}</td>
              <td class="px-4 py-2 border">
                <span :class="{
                  'text-green-600 font-semibold': campaign.status === 'Ativo',
                  'text-gray-600 font-medium': campaign.status === 'Inativo',
                  'text-blue-600 font-semibold': campaign.status === 'Concluída'
                }">
                  {{ campaign.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="pagination-controls mt-4 flex items-center gap-4">
          <button @click="prevPage" :disabled="currentPage === 1" class="btn btn-secondary">Anterior</button>
          <span class="font-medium">Página {{ currentPage }} de {{ totalPages }}</span>
          <button @click="nextPage" :disabled="currentPage >= totalPages" class="btn btn-secondary">Próxima</button>

          <div class="ml-6 flex items-center gap-2">
            <label for="perPageSelect" class="font-medium">Ver por página:</label>
            <select id="perPageSelect" v-model="itemsPerPage" class="form-control w-24">
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Modal de criar campanha -->
      <div v-if="showDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-96">
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
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

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
    const res = await axios.get('http://127.0.0.1:8000/api/user');
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
    const res = await axios.get('http://127.0.0.1:8000/api/campaigns');
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
    const res = await axios.get('http://127.0.0.1:8000/api/companies');
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

    await axios.post('http://127.0.0.1:8000/api/campaigns', form.value);
    toast.success('Campanha criada com sucesso!');
    showDialog.value = false;
    form.value = { name: '', description: '', company_id: '' };
    fetchCampaigns();
  } catch (error) {
    toast.error('Erro ao criar campanha.');
    console.error(error);
  }
};

onMounted(async () => {
  await fetchCompanies();
  await fetchUserRole();
  await fetchCampaigns();
});
</script>


<style scoped>
.btn-primary {
  background-color: #1470ea;
  color: white;
}

.btn-primary:hover {
  background-color: #004add;
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

.form-control {
  width: 100%;
  padding: 8px 12px;
  margin-top: 4px;
  border-radius: 4px;
  border: 1px solid #ddd;
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

.bg-black {
  background-color: rgba(0, 0, 0, 0.5);
}

.flex {
  display: flex;
}

.items-center {
  align-items: center;
}

.justify-center {
  justify-content: center;
}

.z-50 {
  z-index: 50;
}

.transition-all {
  transition: all 0.3s ease-in-out;
}

.duration-300 {
  transition-duration: 300ms;
}

.ease-in-out {
  transition-timing-function: ease-in-out;
}

.max-w-lg {
  max-width: 32rem;
}

.bg-white {
  border-radius: 12px;
  border: 30px solid rgb(255, 255, 255);
}

.campaign {
  padding: 20px;
}

.pagination-container {
  display: flex;
  justify-content: flex-start;
  margin-top: 1rem;
}

.pagination-controls {
  margin-top: 1rem;
  display: flex;
  align-items: center;
  justify-content: flex-start;
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
</style>
