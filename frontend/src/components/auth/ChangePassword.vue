<template>
  <div class="change-password">
    <h1>Alterar Senha</h1>
    <form @submit.prevent="changePassword">
      <div class="mb-3">
        <label for="currentPassword" class="form-label">Senha Atual</label>
        <input type="password" class="form-control" id="currentPassword" v-model="currentPassword" required>
      </div>
      <div class="mb-3">
        <label for="newPassword" class="form-label">Nova Senha</label>
        <input type="password" class="form-control" id="newPassword" v-model="newPassword" required>
      </div>
      <div class="mb-3">
        <label for="confirmNewPassword" class="form-label">Confirmar Nova Senha</label>
        <input type="password" class="form-control" id="confirmNewPassword" v-model="confirmNewPassword" required>
      </div>
      <div class="button-group">
        <button type="submit" class="btn btn-primary">Alterar Senha</button>
        <button type="button" class="btn btn-secondary" @click="showConfirmationDialog = true">Descartar Alterações</button>
      </div>
    </form>

    <ConfirmationDialog
      v-if="showConfirmationDialog"
      :visible="showConfirmationDialog"
      title="Descartar Alterações"
      message="Tem certeza de que deseja descartar as alterações?"
      @confirm="discardChanges"
      @cancel="showConfirmationDialog = false"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import ConfirmationDialog from '../global/ConfirmationDialog.vue';

const toast = useToast();
const router = useRouter();
const currentPassword = ref('');
const newPassword = ref('');
const confirmNewPassword = ref('');
const showConfirmationDialog = ref(false);

const changePassword = async () => {
  if (newPassword.value !== confirmNewPassword.value) {
    toast.error('As senhas não coincidem.');
    return;
  }

  try {
    const response = await axios.post('/change-password', {
      current_password: currentPassword.value,
      new_password: newPassword.value,
      new_password_confirmation: confirmNewPassword.value,
    });

    if (response.status === 200) {
      toast.success(response.data.message || 'Senha alterada com sucesso!');
      router.push({ name: 'Profile' }); // Redirecionar para a página de perfil
    } else {
      toast.error('Erro ao alterar a senha. Verifique suas informações.');
    }
  } catch (error) {
    console.error('Erro ao alterar a senha:', error);
    toast.error('Erro ao alterar a senha. Verifique suas informações.');
  }
};

const discardChanges = () => {
  currentPassword.value = '';
  newPassword.value = '';
  confirmNewPassword.value = '';
  showConfirmationDialog.value = false;
  toast.info('Alterações descartadas.');
  router.push({ name: 'Profile' }); // Redirecionar para a página de perfil
};
</script>

<style scoped>
.change-password {
  padding: 20px;
}

h1 {
  color: #333;
}

.button-group {
  display: flex;
  gap: 10px; /* Adiciona espaço entre os botões */
}
</style>