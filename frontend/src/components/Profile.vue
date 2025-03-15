<template>
    <div class="profile">
      <h1>Perfil do Utilizador</h1>
      <p><strong>Nome:</strong> {{ user.name }}</p>
      <p><strong>Email:</strong> {{ user.email }}</p>
      <div class="button-group">
        <button @click="goToChangePassword" class="btn btn-primary">Alterar Senha</button>

        <router-link :to="{ name: 'Dashboard' }" class="btn btn-secondary">Retroceder</router-link>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue';
  import { useRouter } from 'vue-router';
  import axios from 'axios';
  
  const router = useRouter();
  const user = ref({});
  
  onMounted(async () => {
    try {
      const response = await axios.get('/user');
      user.value = response.data;
    } catch (error) {
      console.error('Erro ao buscar informações do perfil:', error);
    }
  });
  
  const goToChangePassword = () => {
    router.push({ name: 'ChangePassword' });
  };
  </script>
  
  <style scoped>
  .profile {
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

  