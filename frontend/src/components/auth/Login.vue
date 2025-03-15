<template>
  <form class="row g-3 needs-validation" novalidate @submit.prevent="login">
    <h3 class="mt-5 mb-3">Login</h3>
    <hr>
    <div class="mb-3">
      <label for="inputEmail" class="form-label">Email</label>
      <input type="email" class="form-control" id="inputEmail" required v-model="credentials.email">
    </div>
    <div class="mb-3">
      <label for="inputPassword" class="form-label">Password</label>
      <input type="password" class="form-control" id="inputPassword" required v-model="credentials.password">
    </div>
    <div class="mb-3 d-flex justify-content-center">
      <button type="submit" class="btn btn-primary px-5">Login</button>
    </div>
  </form>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { useAuthStore } from '../../stores/auth';

const toast = useToast();
const router = useRouter();
const authStore = useAuthStore();

const credentials = ref({
  email: '',
  password: '',
});

const login = async () => {
  try {
    const response = await axios.post('/login', credentials.value);
    const token = response.data.token;
    authStore.login(token); // Atualizar o estado de autenticação
    toast.success('Login bem-sucedido!');
    router.push({ name: 'Dashboard' });
  } catch (error) {
    console.error('Erro ao fazer login:', error);
    toast.error('Erro ao fazer login. Verifique suas credenciais.');
  }
};
</script>