<template>
  <form class="row g-3 needs-validation" novalidate @submit.prevent="register">
    <h3 class="mt-5 mb-3">Register</h3>
    <hr>
    <div class="mb-3">
      <label for="inputName" class="form-label">Name</label>
      <input type="text" class="form-control" id="inputName" required v-model="user.name">
    </div>
    <div class="mb-3">
      <label for="inputEmail" class="form-label">Email</label>
      <input type="email" class="form-control" id="inputEmail" required v-model="user.email">
    </div>
    <div class="mb-3">
      <label for="inputPassword" class="form-label">Password</label>
      <input type="password" class="form-control" id="inputPassword" required v-model="user.password">
    </div>
    <div class="mb-3">
      <label for="inputPasswordConfirmation" class="form-label">Confirm Password</label>
      <input type="password" class="form-control" id="inputPasswordConfirmation" required v-model="user.password_confirmation">
    </div>
    <div class="mb-3 d-flex justify-content-center">
      <button type="submit" class="btn btn-primary px-5">Register</button>
    </div>
  </form>
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

const register = async () => {
  try {
    const response = await axios.post('/register', user.value);
    const token = response.data.token;
    authStore.login(token); // Atualizar o estado de autenticação
    toast.success('Registro bem-sucedido!');
    router.push({ name: 'Dashboard' }); // Redirecionar para o Dashboard
  } catch (error) {
    console.error('Erro ao registrar:', error);
    toast.error('Erro ao registrar. Verifique suas informações.');
  }
};
</script>