<template>
  <div class="password-container">
    <div class="password-card">
      <h2 class="password-title">Alterar Password</h2>
      <p class="password-subtitle">Atualize a sua palavra-passe com segurança.</p>

      <form @submit.prevent="changePassword">
        <div class="form-group">
          <label for="currentPassword">Password Atual</label>
          <input
            type="password"
            id="currentPassword"
            v-model="currentPassword"
            class="form-control"
            required
          />
        </div>

        <div class="form-group">
          <label for="newPassword">Nova Password</label>
          <input
            type="password"
            id="newPassword"
            v-model="newPassword"
            class="form-control"
            required
          />
        </div>

        <div class="form-group">
          <label for="confirmNewPassword">Confirmar Nova Password</label>
          <input
            type="password"
            id="confirmNewPassword"
            v-model="confirmNewPassword"
            class="form-control"
            required
          />
        </div>

        <button type="submit" class="btn btn-primary w-100 mt-3">Registar Nova Password</button>

        <div class="text-center mt-3">
          <button type="button" class="btn btn-outline-secondary w-100" @click="handleDiscardClick">
            Descartar Alterações
          </button>
        </div>
      </form>
      <div v-if="showConfirmationDialog" class="confirmation-dialog">
        <div class="dialog-content">
          <h3>Descartar Alterações</h3>
          <p>Tem a certeza de que pretende descartar as alterações?</p>
          <div class="dialog-actions">
            <button class="btn btn-outline-secondary" @click="showConfirmationDialog = false">Cancelar</button>
            <button class="btn btn-primary" @click="discardChanges">Confirmar</button>
          </div>
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

const validatePassword = (password) => {
  const messages = [];

  if (password.length < 8) {
    messages.push('A password deve ter pelo menos 8 caracteres.');
  }
  if (!/[A-Z]/.test(password)) {
    messages.push('A password deve conter pelo menos uma letra maiúscula.');
  }
  if (!/[0-9]/.test(password)) {
    messages.push('A password deve conter pelo menos um número.');
  }
  if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
    messages.push('A password deve conter pelo menos um caracter especial.');
  }

  return messages;
};

const changePassword = async () => {
  if (newPassword.value !== confirmNewPassword.value) {
    toast.error('As passwords não coincidem.');
    return;
  }

  const passwordErrors = validatePassword(newPassword.value);
  if (passwordErrors.length > 0) {
    passwordErrors.forEach((msg) => toast.error(msg));
    return;
  }

  try {
    const res = await axios.post('/change-password', {
      current_password: currentPassword.value,
      new_password: newPassword.value,
      new_password_confirmation: confirmNewPassword.value,
    });

    toast.success(res.data.message || 'Senha alterada com sucesso!');
    router.push({ name: 'Profile' });
  } catch (err) {
    toast.error('Erro ao alterar a senha. Verifique as informações.');
    console.error(err);
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
  if (currentPassword.value || newPassword.value || confirmNewPassword.value) {
    showConfirmationDialog.value = true;
  } else {
    discardChanges();
  }
};
</script>

<style scoped>
.password-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 78vh;
  background-color: #f1f5f9;
  padding-top: 4%;
}

.password-card {
  width: 100%;
  max-width: 500px;
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.password-title {
  font-size: 1.8rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: #1e293b;
}

.password-subtitle {
  font-size: 0.9rem;
  color: #64748b;
  margin-bottom: 1.5rem;
}

.form-group {
  margin-bottom: 1rem;
  text-align: left;
}

.form-control {
  width: 100%;
  height: 44px;
  font-size: 1rem;
  padding: 0.5rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
}

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
