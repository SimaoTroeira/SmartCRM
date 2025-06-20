<template>
  <div class="auth-container">
    <div class="auth-card shadow">
      <h2 class="text-center fw-bold mb-3">Criar Conta</h2>
      <p class="text-center text-muted mb-4">Preencha os campos abaixo para se registar.</p>

      <form @submit.prevent="register">
        <div class="form-group mb-3">
          <label for="name" class="form-label">Nome</label>
          <input v-model="user.name" type="text" id="name" class="form-control" required />
          <div v-if="errors.name" class="text-danger">{{ errors.name[0] }}</div>
        </div>

        <div class="form-group mb-3">
          <label for="email" class="form-label">Email</label>
          <input v-model="user.email" type="email" id="email" class="form-control" required />
          <div v-if="errors.email" class="text-danger">{{ errors.email[0] }}</div>
        </div>

        <div class="form-group mb-3">
          <label for="password" class="form-label">Password</label>
          <input v-model="user.password" type="password" id="password" class="form-control" required />
          <div v-if="errors.password" class="text-danger">{{ errors.password[0] }}</div>
        </div>

        <div class="form-group mb-4">
          <label for="password_confirmation" class="form-label">Confirmar Password</label>
          <input v-model="user.password_confirmation" type="password" id="password_confirmation" class="form-control"
            required />
          <div v-if="errors.password_confirmation" class="text-danger">{{ errors.password_confirmation[0] }}</div>
        </div>

        <button type="submit" class="btn btn-primary w-100">Registar</button>
      </form>

      <p class="mt-3 text-center">
        Já tem conta?
        <router-link :to="{ name: 'Login' }" class="text-decoration-none fw-semibold">Entrar</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const toast = useToast();
const authStore = useAuthStore();

const user = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
});

const errors = ref({});

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
    messages.push('A password deve conter pelo menos um caractere especial.');
  }

  return messages;
};

const register = async () => {
  errors.value = {};

  if (user.value.password !== user.value.password_confirmation) {
    errors.value.password_confirmation = ['As passwords não coincidem.'];
  }

  const passwordErrors = validatePassword(user.value.password);
  if (passwordErrors.length > 0) {
    errors.value.password = passwordErrors;
  }

  if (Object.keys(errors.value).length > 0) {
    Object.values(errors.value).forEach((errArr) => {
      toast.error(errArr[0]);
    });
    return;
  }

  try {

    await axios.post('/register', user.value);

    const loginRes = await axios.post('/login', {
      email: user.value.email,
      password: user.value.password
    });

    const token = loginRes.data.token;

    authStore.login(token);
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

    toast.success('Registo e login efetuados com sucesso!');
    router.push({ name: 'Dashboard' });
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
      Object.values(errors.value).forEach((errArr) => {
        toast.error(errArr[0]);
      });
    } else if (error.response?.data?.message) {
      toast.error(error.response.data.message);
    } else {
      toast.error('Erro ao registar. Tente novamente.');
    }
  }
};
</script>



<style scoped>
.auth-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 88vh;
  background-color: #f1f5f9;
}

.auth-card {
  background-color: white;
  padding: 2rem;
  border-radius: 12px;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
}

.form-label {
  font-weight: 500;
}

.text-danger {
  font-size: 0.875rem;
}
</style>
