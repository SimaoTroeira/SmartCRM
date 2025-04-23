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

            <div class="flex gap-4" v-if="canEdit">
                <button @click="openEditModal" class="btn-edit">Editar</button>
                <button @click="openDeleteModal" class="btn-remove">Apagar</button>
            </div>

            <!-- Associar utilizadores -->
            <div class="mt-6" v-if="canEdit">
                <h3 class="text-lg font-semibold mb-2">Associar Utilizadores</h3>
                <select v-model="selectedUsers" multiple class="form-control border p-2 w-full mb-2">
                    <option v-for="user in filteredCompanyUsers" :key="user.id" :value="user.id">
                        {{ user.name }}
                    </option>
                </select>
                <button @click="associateUsers" class="btn btn-success">Associar</button>
            </div>

            <!-- Listar utilizadores já associados -->
            <div class="mt-6">
                <h3 class="text-lg font-semibold mb-2">Utilizadores Associados</h3>
                <ul>
                    <li v-for="user in campaignUsers" :key="user.id" class="flex justify-between items-center mb-2">
                        <span>{{ user.name }} – {{ user.role }}</span>
                        <button
                            v-if="canRemoveUser(user)"
                            @click="removeUser(user.id)"
                            class="text-red-600 hover:underline text-sm"
                        >
                            Remover
                        </button>
                    </li>
                </ul>
            </div>

        </div>

        <div v-else>
            <p>A carregar detalhes da campanha...</p>
        </div>

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
import { ref, computed, onMounted } from 'vue';
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
const canEdit = ref(false);

const selectedUsers = ref([]);
const companyUsers = ref([]);
const campaignUsers = ref([]);

const userId = ref(null);
const userRole = ref(null);

const goBack = () => router.push('/campaigns');

const formatDate = date => date ? new Date(date).toLocaleDateString() : 'Sem data';

const fetchCampaign = async () => {
    try {
        const { data } = await axios.get(`http://127.0.0.1:8000/api/campaigns/${route.params.id}`);
        campaign.value = data;
        form.value = { name: data.name, description: data.description };
        await fetchUserDataAndPermissions(data.company.id);
        await fetchCompanyUsers(data.company.id);
        await fetchCampaignUsers(route.params.id);
    } catch {
        toast.error('Erro ao carregar campanha.');
    }
};

const fetchUserDataAndPermissions = async (companyId) => {
    try {
        const { data: user } = await axios.get(`http://127.0.0.1:8000/api/user`);
        userId.value = user.id;

        if (user.email === 'admin@admin.com') {
            userRole.value = 'SA';
            canEdit.value = true;
        } else {
            const { data } = await axios.get(`http://127.0.0.1:8000/api/companies/${companyId}/user-role`);
            userRole.value = data.role;
            canEdit.value = data.role === 'CA';
        }
    } catch {
        toast.error('Erro ao verificar permissões.');
    }
};

const fetchCompanyUsers = async (companyId) => {
    try {
        const { data } = await axios.get(`http://127.0.0.1:8000/api/companies/${companyId}`);
        companyUsers.value = data.user_company_roles.map(ucr => ({
            id: ucr.user.id,
            name: ucr.user.name,
            role: ucr.role.code
        }));
    } catch {
        toast.error('Erro ao obter utilizadores da empresa.');
    }
};

const fetchCampaignUsers = async (campaignId) => {
    try {
        const { data } = await axios.get(`http://127.0.0.1:8000/api/campaigns/${campaignId}/users`);
        campaignUsers.value = data;
    } catch {
        toast.error('Erro ao obter utilizadores da campanha.');
    }
};

const filteredCompanyUsers = computed(() => {
    const associatedIds = campaignUsers.value.map(u => u.id);
    return companyUsers.value.filter(u => !associatedIds.includes(u.id));
});

const associateUsers = async () => {
    try {
        await axios.post(`http://127.0.0.1:8000/api/campaigns/${campaign.value.id}/users`, {
            user_ids: selectedUsers.value
        });
        toast.success('Utilizadores associados com sucesso!');
        selectedUsers.value = [];
        await fetchCampaignUsers(campaign.value.id);
    } catch {
        toast.error('Erro ao associar utilizadores.');
    }
};

const canRemoveUser = (user) => {
    const isSelf = user.id === userId.value;
    const isCU = user.role === 'CU';

    if (isSelf) return true;
    if (userRole.value === 'CA' && isCU) return true;
    return false;
};

const removeUser = async (userIdToRemove) => {
    try {
        await axios.delete(`http://127.0.0.1:8000/api/campaigns/${campaign.value.id}/users/${userIdToRemove}`);
        toast.success('Utilizador removido da campanha!');
        await fetchCampaignUsers(campaign.value.id);
    } catch {
        toast.error('Erro ao remover utilizador.');
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
    top: 2rem;
    right: 15rem;
    font-size: 4rem;
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