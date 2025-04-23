<template>
  <div class="change-password">
    <h1>Alterar Password</h1>
    <form @submit.prevent="changePassword">
      <div class="mb-3">
        <label for="currentPassword" class="form-label">Password Atual</label>
        <input type="password" class="form-control" id="currentPassword" v-model="currentPassword" required>
      </div>
      <div class="mb-3">
        <label for="newPassword" class="form-label">Nova Password</label>
        <input type="password" class="form-control" id="newPassword" v-model="newPassword" required>
      </div>
      <div class="mb-3">
        <label for="confirmNewPassword" class="form-label">Confirmar Nova Password</label>
        <input type="password" class="form-control" id="confirmNewPassword" v-model="confirmNewPassword" required>
      </div>
      <div class="button-group">
        <button type="submit" class="btn btn-primary">Registar Nova Password</button>
        <button type="button" class="btn btn-secondary" @click="handleDiscardClick">
          Descartar Alterações
        </button>
      </div>
    </form>

    <!-- Modal de confirmação -->
    <div v-if="showConfirmationDialog" class="confirmation-dialog">
      <div class="dialog-content">
        <h2>Descartar Alterações</h2>
        <p>Tem certeza de que deseja descartar as alterações?</p>
        <div class="dialog-actions">
          <button class="btn btn-secondary" @click="showConfirmationDialog = false">Cancelar</button>
          <button class="btn btn-primary" @click="discardChanges">Descartar alterações</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useToast } from 'vue-toastification';

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
      router.push({ name: 'Profile' });
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
  router.push({ name: 'Profile' });
};

const handleDiscardClick = () => {
  if (
    currentPassword.value ||
    newPassword.value ||
    confirmNewPassword.value
  ) {
    // Se houver alguma alteração, mostrar a modal
    showConfirmationDialog.value = true;
  } else {
    // Se nenhum campo tiver sido alterado, descartar direto
    discardChanges();
  }
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
  gap: 10px;
}

/* Modal de confirmação inline */
.confirmation-dialog {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 100;
}

.dialog-content {
  background: white;
  padding: 20px;
  border-radius: 12px;
  text-align: center;
  width: 400px;
}

.dialog-actions {
  margin-top: 20px;
  display: flex;
  justify-content: center;
  gap: 10px;
}
</style>
