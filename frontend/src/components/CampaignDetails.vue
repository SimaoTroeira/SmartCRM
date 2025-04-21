<template>
    <div class="campaign p-4">
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

            <!-- Botões de ação -->
            <div class="flex gap-4">
                <button @click="openEditModal" class="btn-edit">Editar</button>
                <button @click="openDeleteModal" class="btn-remove">Apagar</button>
            </div>

        </div>

        <div v-else>
            <p>A carregar detalhes da campanha...</p>
        </div>

        <!-- Modal de Edição -->
        <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-md w-96">
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
            </div>
        </div>

        <!-- Modal de Exclusão -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-md w-96">
                <h3 class="text-lg font-bold mb-4">Tem certeza que deseja apagar esta campanha?</h3>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="closeDeleteModal" class="btn btn-secondary">Cancelar</button>
                    <button type="button" @click="deleteCampaign" class="btn btn-danger">Apagar</button>
                </div>
            </div>
        </div>
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
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const form = ref({ name: '', description: '' });

const formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString() : 'Sem data';
};

const fetchCampaign = async () => {
    try {
        const response = await axios.get(`http://127.0.0.1:8000/api/campaigns/${route.params.id}`);
        campaign.value = response.data;
        form.value = { name: campaign.value.name, description: campaign.value.description };
    } catch (error) {
        toast.error('Erro ao carregar campanha.');
    }
};

const openEditModal = () => {
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
};

const updateCampaign = async () => {
    try {
        await axios.put(`http://127.0.0.1:8000/api/campaigns/${campaign.value.id}`, form.value);
        toast.success('Campanha atualizada com sucesso!');
        showEditModal.value = false;
        await fetchCampaign();
    } catch (error) {
        toast.error('Erro ao atualizar campanha.');
    }
};

const openDeleteModal = () => {
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
};

const deleteCampaign = async () => {
    try {
        await axios.delete(`http://127.0.0.1:8000/api/campaigns/${campaign.value.id}`);
        toast.success('Campanha excluída com sucesso!');
        router.push({ name: 'CampaignList' });
    } catch (error) {
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
</style>