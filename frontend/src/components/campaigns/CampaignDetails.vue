<template>
    <div class="campaign p-4 relative">
        <button @click="goBack" class="close-button text-gray-600 hover:text-red-600">×</button>

        <h2 class="text-2xl font-bold mb-4 text-left">
            {{ campaign?.name || 'Detalhes da Campanha' }}
        </h2>

        <div v-if="campaign">
            <p><strong>Descrição:</strong> {{ campaign.description }}</p>
            <p><strong>Início:</strong> {{ formatDate(campaign.start_date || campaign.created_at) }}</p>
            <p><strong>Fim:</strong> {{ formatDate(campaign.end_date) }}</p>
            <p><strong>Status:</strong> {{ campaign.status }}</p>

            <div class="mb-4">
                <strong>Empresa:</strong>
                <router-link :to="{ name: 'CompanyDetails', params: { id: campaign.company.id } }"
                    class="text-blue-600 underline hover:text-blue-800">
                    {{ campaign.company.name }}
                </router-link>
            </div>

            <!-- Botões de ação (visíveis apenas para CA ou SA) -->
            <div class="flex gap-4" v-if="canEdit">
                <button @click="openEditModal" class="btn-edit">Editar</button>
                <button @click="openDeleteModal" class="btn-remove">Apagar</button>
            </div>
        </div>

        <div v-else>
            <p>A carregar detalhes da campanha...</p>
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

        <!-- Dialog de Exclusão -->
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
const canEdit = ref(false); // Controla a exibição de botões

const goBack = () => {
    router.back();
};

const formatDate = date => date ? new Date(date).toLocaleDateString() : 'Sem data';

const fetchCampaign = async () => {
    try {
        const { data } = await axios.get(`http://127.0.0.1:8000/api/campaigns/${route.params.id}`);
        campaign.value = data;
        form.value = { name: data.name, description: data.description };

        await checkUserRoleForCompany(data.company.id);
    } catch {
        toast.error('Erro ao carregar campanha.');
    }
};

const checkUserRoleForCompany = async (companyId) => {
    try {
        const { data } = await axios.get(`http://127.0.0.1:8000/api/companies/${companyId}/user-role`);
        canEdit.value = data.role === 'CA' || data.role === 'SA';
    } catch {
        canEdit.value = false;
        toast.error('Erro ao verificar permissões.');
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
        closeDeleteDialog();
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
}

.btn-edit,
.btn-remove {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 1rem;
}

.btn-edit {
    background-color: #4CAF50;
    color: white;
}

.btn-edit:hover {
    background-color: #45a049;
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
</style>
