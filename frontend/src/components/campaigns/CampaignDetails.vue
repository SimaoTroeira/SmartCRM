<template>
    <div class="campaign p-4 relative">
        <!-- Botão fechar -->
        <button @click="goBack" class="close-button text-gray-600 hover:text-red-600">
            ×
        </button>

        <h3 class="text-2xl font-bold mb-4 text-left">
            {{ campaign?.name || 'Detalhes da Campanha' }}
        </h3>

        <div v-if="campaign" class="space-y-6">
            <!-- Secção de Informações da Campanha -->
            <div class="border rounded-lg p-4 shadow-sm bg-white">
                <h3 class="text-lg font-semibold mb-2">Informações da Campanha</h3>
                <div class="space-y-1">
                    <p><strong>Descrição:</strong> {{ campaign.description }}</p>
                    <p><strong>Início:</strong> {{ formatDate(campaign.start_date || campaign.created_at) }}</p>
                    <p><strong>Fim:</strong> {{ formatDate(campaign.end_date) }}</p>
                    <p><strong>Status:</strong> {{ campaign.status }}</p>
                </div>
            </div>

            <!-- Secção da Empresa -->
            <div class="border rounded-lg p-4 shadow-sm bg-white">
                <h3 class="text-lg font-semibold mb-2">Empresa Associada</h3>
                <p>
                    <strong>Nome:</strong>
                    <router-link :to="{ name: 'CompanyDetails', params: { id: campaign.company.id } }"
                        class="text-blue-600 underline hover:text-blue-800">
                        {{ campaign.company.name }}
                    </router-link>
                </p>
            </div>

            <!-- Ações -->
            <div class="flex gap-4">
                <button @click="openEditModal" class="btn-edit">Editar</button>
                <button @click="openDeleteModal" class="btn-remove">Apagar</button>
            </div>
        </div>

        <!-- Dialog de Edição -->
        <dialog ref="editDialog" class="bg-white p-6 rounded-lg shadow-md w-96">
            <h3 class="text-lg font-bold mb-4">Editar Campanha</h3>
            <form @submit.prevent="updateCampaign">
                <div class="mb-3">
                    <label class="block font-medium">Título</label>
                    <input v-model="form.name" class="form-control w-full border px-2 py-1" required />
                </div>
                <div class="mb-3">
                    <label class="block font-medium">Descrição</label>
                    <textarea v-model="form.description" class="form-control w-full border px-2 py-1"
                        required></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="closeEditModal" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar alterações</button>
                </div>
            </form>
        </dialog>

        <!-- Dialog de Exclução -->
        <dialog ref="deleteDialog" class="bg-white p-6 rounded-lg shadow-md w-96">
            <h3 class="text-lg font-bold mb-4">Tem certeza que deseja apagar esta campanha?</h3>
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

const campaign = ref(null);
const form = ref({ name: '', description: '' });
const editDialog = ref(null);
const deleteDialog = ref(null);

const goBack = () => {
    router.back();
};


const formatDate = date => date
    ? new Date(date).toLocaleDateString()
    : 'Sem data';

const fetchCampaign = async () => {
    try {
        const { data } = await axios.get(`http://127.0.0.1:8000/api/campaigns/${route.params.id}`);
        campaign.value = data;
        form.value = { name: data.name, description: data.description };
    } catch {
        toast.error('Erro ao carregar campanha.');
    }
};

const openEditModal = () => editDialog.value.showModal();
const closeEditModal = () => editDialog.value.close();
const openDeleteModal = () => deleteDialog.value.showModal();
const closeDeleteModal = () => deleteDialog.value.close();

const updateCampaign = async () => {
    try {
        await axios.put(`http://127.0.0.1:8000/api/campaigns/${campaign.value.id}`, form.value);
        toast.success('Campanha atualizada com sucesso!');
        closeEditModal();
        await fetchCampaign();
    } catch {
        toast.error('Erro ao atualizar campanha.');
    }
};

const confirmDelete = async () => {
    try {
        await axios.delete(`http://127.0.0.1:8000/api/campaigns/${campaign.value.id}`);
        toast.success('Campanha excluída com sucesso!');
        closeDeleteModal();
        router.push('/campaigns');
    } catch {
        toast.error('Erro ao excluir campanha.');
    }
};

onMounted(fetchCampaign);
</script>

<style scoped>
.campaign {
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

.btn-remove {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 1rem;
}

.btn-edit {
    background-color: #007bff;
    color: white;
}

.btn-edit:hover {
    background-color: #0069d9;
}

.btn-remove {
    background-color: #dc3545;
    color: white;
}

.btn-remove:hover {
    background-color: #c82333;
}

dialog {
    border-radius: 12px;
    border: 8px solid #ffffff;
}

dialog::backdrop {
    background: rgba(0, 0, 0, 0.5);
}

h3.text-lg::before {
    content: '';
    display: inline-block;
    width: 6px;
    height: 1.2em;
    background-color: #007bff;
    margin-right: 8px;
    border-radius: 2px;
    vertical-align: middle;
}
</style>