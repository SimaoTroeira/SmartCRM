<template>
  <div class="profile-container">
    <div class="profile-card">
      <h2 class="profile-title">Perfil do Utilizador</h2>
      <p class="profile-subtitle">Pode atualizar o seu nome e alterar a password.</p>

      <form @submit.prevent="updateProfile">
        <div class="form-group">
          <label for="name">Nome</label>
          <input
            type="text"
            id="name"
            v-model="user.name"
            class="form-control"
            required
          />
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input
            type="email"
            id="email"
            v-model="user.email"
            class="form-control"
            disabled
          />
        </div>

        <button type="submit" class="btn btn-primary w-100 mt-3">Guardar Alterações</button>

        <router-link
          to="/change-password"
          class="btn btn-outline-secondary w-100 mt-2"
        >
          Alterar Password
        </router-link>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const toast = useToast();

const user = ref({
  name: '',
  email: '',
});

const getUser = async () => {
  try {
    const res = await axios.get('/user');
    user.value = res.data;
  } catch (err) {
    toast.error('Erro ao carregar dados do utilizador.');
  }
};

const updateProfile = async () => {
  try {
    await axios.put('/user', { name: user.value.name });
    toast.success('Perfil atualizado com sucesso!');
  } catch (err) {
    toast.error('Erro ao atualizar o perfil.');
  }
};

onMounted(() => {
  getUser();
});
</script>

<style scoped>
.profile-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 78vh;
  background-color: #f1f5f9;
  padding-top: 4%;
}

.profile-card {
  width: 100%;
  max-width: 500px;
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.profile-title {
  font-size: 1.8rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: #1e293b;
}

.profile-subtitle {
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
</style>
