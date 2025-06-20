<template>
  <div class="auth-container">
    <div class="auth-card">
      <h2 class="auth-title">Bem-vindo de volta</h2>
      <p class="auth-subtitle">Por favor, introduza os seus dados para iniciar sessão.</p>

      <form @submit.prevent="login">
        <div class="form-group">
          <label for="email" class="form-label">Email</label>
          <div class="input-group">
            <span class="input-icon"><i class="bi bi-person-fill"></i></span>
            <input type="email" id="email" v-model="credentials.email" required class="form-control"
              placeholder="Digite o seu email" />
          </div>
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <span class="input-icon"><i class="bi bi-lock-fill"></i></span>
            <input type="password" id="password" v-model="credentials.password" required class="form-control"
              placeholder="Digite a sua password" />
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 mt-3">Entrar</button>

        <div class="text-center mt-3">
          <small>Não tem conta? <router-link to="/register">Registe-se</router-link></small>
        </div>
      </form>
    </div>
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
    authStore.login(token);
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

    toast.success('Login bem-sucedido!');
    router.push({ name: 'Dashboard' });
  } catch (error) {
    console.error('Erro ao fazer login:', error);
    toast.error('Erro ao fazer login. Verifique suas credenciais.');
  }
};
</script>

<style scoped>
.auth-container {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding-top: 8vh;
  height: 100vh;
  background-color: #f1f5f9;
}

.auth-card {
  width: 100%;
  max-width: 420px;
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.auth-title {
  font-size: 1.8rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: #1e293b;
}

.auth-subtitle {
  font-size: 0.9rem;
  color: #64748b;
  margin-bottom: 1.5rem;
}

.form-group {
  margin-bottom: 1rem;
  text-align: left;
}

.input-group {
  display: flex;
  align-items: center;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  padding: 0 0.75rem;
  background: #fff;
}

.input-icon {
  margin-right: 0.5rem;
  color: #94a3b8;
}

.input-group input {
  border: none;
  outline: none;
  flex: 1;
  height: 44px;
  font-size: 1rem;
  background: transparent;
}

@keyframes fade-slide {
  from {
    opacity: 0;
    transform: translateY(12px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>