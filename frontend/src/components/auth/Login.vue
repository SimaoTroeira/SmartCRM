<template>
  <div class="login-wrapper">
    <form class="login-form" @submit.prevent="login">
      <h1 class="mb-4 text-center">Login</h1>

      <div class="mb-3">
        <label for="inputEmail" class="form-label">Email</label>
        <input type="email" class="form-control" id="inputEmail" required v-model="credentials.email" />
      </div>

      <div class="mb-4">
        <label for="inputPassword" class="form-label">Password</label>
        <input type="password" class="form-control" id="inputPassword" required v-model="credentials.password" />
      </div>

      <div>
        <button type="submit" class="btn btn-primary">Login</button>
      </div>
    </form>
  </div>
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

<style scoped>
.login-wrapper {
  display: flex;
  justify-content: start;
  align-items: center;
  height: 80vh;
  background-color: #f8fafc;
  padding-left: 12vw;
}

.login-form {
  width: 600px;
  background-color: #ffffff;
  padding: 3rem;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.login-form input {
  width: 100% !important;
  display: block;
  height: 50px;
  font-size: 16px;
}

.login-form button {
  width: 100%;
  height: 50px;
  font-size: 16px;
}
</style>
