<template>
  <div>
    <h2 class="text-xl font-semibold mb-4">As minhas empresas</h2>

    <!-- Botão sempre visível, alinhado à direita -->
    <div class="mb-4">
      <button @click="showDialog = true" class="btn btn-primary">
        Registar nova empresa
      </button>
    </div>

    <!-- Se não houver empresas -->
    <div v-if="companies.length === 0" class="text-gray-500 mb-4">
      Você não tem empresas ainda.
    </div>

    <!-- Se houver empresas -->
    <div v-else class="overflow-x-auto">
      <table class="min-w-full table-auto border border-gray-200">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-4 py-2 border">Nome</th>
            <th class="px-4 py-2 border">Setor</th>
            <th class="px-4 py-2 border">Ações</th> <!-- Coluna de ações -->
          </tr>
        </thead>
        <tbody>
          <tr v-for="company in companies" :key="company.id" class="hover:bg-gray-50">
            <td class="px-4 py-2 border">{{ company.name }}</td>
            <td class="px-4 py-2 border">{{ company.sector }}</td>
            <td class="px-4 py-2 border text-center">
              <!-- Botão Editar com margem direita -->
              <button @click="openEditModal(company)" class="btn-edit text-white px-4 py-2 rounded mr-4">
                Editar
              </button>
              <!-- Botão Remover -->
              <button @click="openDeleteModal(company.id)" class="btn-remove text-white px-4 py-2 rounded">
                Apagar
              </button>
            </td>

          </tr>
        </tbody>
      </table>
    </div>

    <!-- Dialog de registro -->
    <div v-if="showDialog"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 transition-all duration-300 ease-in-out">
      <div class="bg-white p-6 rounded-lg shadow-md w-96 transform transition-all duration-300"
        :class="{ 'scale-100 opacity-100': showDialog, 'scale-95 opacity-0': !showDialog }">
        <h3 class="text-lg font-bold mb-4">Registar nova empresa</h3>
        <form @submit.prevent="registerCompany">
          <div class="mb-3">
            <label class="block font-medium">Nome da Empresa</label>
            <input v-model="companyForm.name" class="form-control" required />
          </div>
          <div class="mb-3">
            <label class="block font-medium">Setor</label>
            <input v-model="companyForm.sector" class="form-control" required />
          </div>
          <div class="flex justify-end gap-2">
            <button type="button" @click="showDialog = false" class="btn btn-secondary text-white">Cancelar</button>
            <button type="submit" class="btn btn-success text-white">Registar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal de Edição -->
    <div v-if="showEditModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 transition-all duration-300 ease-in-out">
      <div class="bg-white p-6 rounded-lg shadow-md w-96 transform transition-all duration-300"
        :class="{ 'scale-100 opacity-100': showEditModal, 'scale-95 opacity-0': !showEditModal }">
        <h3 class="text-lg font-bold mb-4">Editar Empresa</h3>
        <form @submit.prevent="updateCompany">
          <div class="mb-3">
            <label class="block font-medium">Nome da Empresa</label>
            <input v-model="editCompany.name" class="form-control" required />
          </div>
          <div class="mb-3">
            <label class="block font-medium">Setor</label>
            <input v-model="editCompany.sector" class="form-control" required />
          </div>
          <div class="flex justify-end gap-2">
            <button type="button" @click="closeEditModal" class="btn btn-secondary">Cancelar</button>
            <button type="submit" class="btn btn-success">Guardar alterações</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal de Exclução -->
    <div v-if="showDeleteModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 transition-all duration-300 ease-in-out">
      <div class="bg-white p-6 rounded-lg shadow-md w-96 transform transition-all duration-300"
        :class="{ 'scale-100 opacity-100': showDeleteModal, 'scale-95 opacity-0': !showDeleteModal }">
        <h3 class="text-lg font-bold mb-4">Tem certeza que deseja apagar esta empresa?</h3>
        <div class="flex justify-end gap-2">
          <button type="button" @click="closeDeleteModal" class="btn btn-secondary">Cancelar</button>
          <button type="button" @click="deleteCompany" class="btn btn-danger">Apagar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const toast = useToast();
const showDialog = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const companies = ref([]);
const companyForm = ref({
  name: '',
  sector: ''
});
const editCompany = ref({});
const companyToDelete = ref(null);

// Função para carregar as empresas do backend
const fetchCompanies = async () => {
  try {
    const response = await axios.get('http://127.0.0.1:8000/api/companies');
    companies.value = Array.isArray(response.data) ? response.data : [];
  } catch (error) {
    toast.error('Erro ao carregar empresas.');
    console.error('Erro ao carregar empresas:', error);
    companies.value = [];
  }
};

// Função para registrar uma nova empresa
const registerCompany = async () => {
  try {
    await axios.post('http://127.0.0.1:8000/api/companies', companyForm.value);
    toast.success('Empresa registrada com sucesso!');
    showDialog.value = false;
    companyForm.value = { name: '', sector: '' };
    fetchCompanies(); // Recarrega a lista de empresas após o registro
  } catch (error) {
    toast.error('Erro ao registrar empresa.');
    console.log('Detalhe do erro:', error.response?.data);
  }
};

// Função para abrir o modal de edição
const openEditModal = (company) => {
  editCompany.value = { ...company };
  showEditModal.value = true;
};

// Função para fechar o modal de edição
const closeEditModal = () => {
  showEditModal.value = false;
  editCompany.value = {};
};

// Função para atualizar a empresa
const updateCompany = async () => {
  try {
    await axios.put(`http://127.0.0.1:8000/api/companies/${editCompany.value.id}`, editCompany.value);
    toast.success('Empresa atualizada com sucesso!');
    fetchCompanies(); // Recarrega a lista de empresas após a atualização
    closeEditModal(); // Fecha o modal
  } catch (error) {
    toast.error('Erro ao atualizar empresa.');
  }
};

// Função para abrir o modal de exclusão
const openDeleteModal = (companyId) => {
  companyToDelete.value = companyId;
  showDeleteModal.value = true;
};

// Função para fechar o modal de exclusão
const closeDeleteModal = () => {
  showDeleteModal.value = false;
  companyToDelete.value = null;
};

// Função para excluir a empresa
const deleteCompany = async () => {
  try {
    await axios.delete(`http://127.0.0.1:8000/api/companies/${companyToDelete.value}`);
    toast.success('Empresa excluída com sucesso!');
    fetchCompanies(); // Recarrega a lista de empresas após a exclusão
    closeDeleteModal(); // Fecha o modal
  } catch (error) {
    toast.error('Erro ao excluir empresa.');
  }
};

// Carrega as empresas no momento em que o componente é montado
onMounted(fetchCompanies);
</script>

<style scoped>
/* Estilo para o botão */

/* Estilo para o botão */
.btn-primary {
  background-color: #4CAF50;
  /* Verde para o botão de registro */
  color: white;
  /* Cor do texto do botão */
}

.btn-primary:hover {
  background-color: #45a049;
  /* Tom mais escuro de verde no hover */
}

.btn-success {
  background-color: #28a745;
  /* Verde para salvar */
  color: white;
  /* Cor do texto do botão */
}

.btn-success:hover {
  background-color: #218838;
  /* Tom mais escuro de verde no hover */
}

.btn-danger {
  background-color: #e13849;
  /* Vermelho para excluir */
  color: white;
  /* Cor do texto do botão */
}

.btn-danger:hover {
  background-color: #c82333;
  /* Tom mais escuro de vermelho no hover */
}

.btn-secondary {
  background-color: #3659f4;
  /* Vermelho para cancelar */
  color: white;
  /* Cor do texto do botão */
}

.btn-secondary:hover {
  background-color: #357be5;
  /* Tom mais escuro de vermelho no hover */
}

/* Estilo para os botões Editar e Remover */
.btn-edit {
  background-color: #4CAF50 !important;
  /* Verde para editar */
  color: white !important;
}

.btn-edit:hover {
  background-color: #45a049 !important;
  /* Tom mais escuro de verde no hover */
}

.btn-remove {
  background-color: #dc3545 !important;
  /* Vermelho para excluir */
  color: white !important;
}

.btn-remove:hover {
  background-color: #c82333 !important;
  /* Tom mais escuro de vermelho no hover */
}


/* Estilo para o formulário */
.form-control {
  width: 100%;
  padding: 8px 12px;
  margin-top: 4px;
  border-radius: 4px;
  border: 1px solid #ddd;
}

/* Estilo para o fundo do modal */
.bg-opacity-50 {
  background-color: rgba(0, 0, 0, 0.5);
}

/* Centralização do modal */
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

/* Estilo do modal */
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

/* Adicionando flex para centralizar o modal corretamente */
.flex {
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Animação do modal */
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
</style>
