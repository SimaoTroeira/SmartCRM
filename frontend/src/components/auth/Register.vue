<template>
  <div class="register-wrapper">
    <form class="register-form" @submit.prevent="register">
      <h1 class="mb-4 text-center">Registar</h1>

      <div class="mb-3">
        <label for="inputName" class="form-label">Nome</label>
        <input type="text" class="form-control" id="inputName" required v-model="user.name">
        <div v-if="errors.name && errors.name.length" class="text-danger">
          {{ errors.name[0] }}
        </div>
      </div>

      <div class="mb-3">
        <label for="inputEmail" class="form-label">Email</label>
        <input type="email" class="form-control" id="inputEmail" required v-model="user.email">
        <div v-if="errors.email && errors.email.length" class="text-danger">
          {{ errors.email[0] }}
        </div>
      </div>

      <div class="mb-3">
        <label for="inputPassword" class="form-label">Password</label>
        <input type="password" class="form-control" id="inputPassword" required v-model="user.password">
        <div v-if="errors.password && errors.password.length" class="text-danger">
          {{ errors.password[0] }}
        </div>
      </div>

      <div class="mb-3">
        <label for="inputPasswordConfirmation" class="form-label">Confirmar Password</label>
        <input type="password" class="form-control" id="inputPasswordConfirmation" required v-model="user.password_confirmation">
        <div v-if="errors.password_confirmation && errors.password_confirmation.length" class="text-danger">
          {{ errors.password_confirmation[0] }}
        </div>
      </div>

      <div class="mb-3 d-flex justify-content-center">
        <button type="submit" class="btn btn-primary px-5">Registar</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth'; // Certifique-se de que o caminho está correto
import { useToast } from 'vue-toastification';

const toast = useToast();
const router = useRouter();
const authStore = useAuthStore();

const user = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const errors = ref({});

const register = async () => {
  // Limpa erros anteriores
  errors.value = {};

  // Validações frontend
  if (!user.value.name) {
    errors.value.name = ['O nome é obrigatório.'];
  }

  if (!user.value.email) {
    errors.value.email = ['O email é obrigatório.'];
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(user.value.email)) {
    errors.value.email = ['O email não é válido.'];
  }

  if (!user.value.password) {
    errors.value.password = ['A password é obrigatória.'];
  } else if (user.value.password.length < 8) {
    errors.value.password = ['A password deve ter pelo menos 8 caracteres.'];
  } else if (!/[!@#$%^&*(),.?":{}|<>]/.test(user.value.password)) {
    errors.value.password = ['A password deve conter pelo menos um caractere especial.'];
  }

  if (user.value.password !== user.value.password_confirmation) {
    errors.value.password_confirmation = ['As passwords não coincidem.'];
  }

  // Se houver erros, para aqui
  if (Object.keys(errors.value).length > 0) {
    return;
  }

  // Envia o pedido ao backend
  try {
    const response = await axios.post('/register', user.value);
    const token = response.data.token;

    authStore.login(token);
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

    toast.success('Registro bem-sucedido!');
    router.push({ name: 'Dashboard' });
  } catch (error) {
    if (error.response && error.response.status === 422) {
      errors.value = error.response.data || {};
    } else {
      toast.error('Erro ao registrar. Verifique suas informações.');
    }
  }

};
</script>

<style scoped>
.register-wrapper {
  display: flex;
  justify-content: start;
  align-items: center;
  height: 78vh;
  background-color: #f8fafc;
  padding-left: 8vw;
  border-width: 10;
  padding-top: 5%;
}

.register-form {
  width: 750px;
  background-color: #ffffff;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.register-form input {
  width: 100% !important;
  display: block;
  height: 50px;
  font-size: 16px;
}

.register-form button {
  width: 100%;
  height: 50px;
  font-size: 16px;
}
</style>
