<template>
  <div class="profile">
    <div v-if="!profileLoaded">
      <h3 class="text-xl font-semibold text-gray-700">
        A carregar perfil<span class="dot-anim"></span>
      </h3>
    </div>

    <div v-else>
      <h2>Perfil do Utilizador</h2>
      <p><strong>Nome:</strong> {{ user.name }}</p>
      <p><strong>Email:</strong> {{ user.email }}</p>
      <div class="button-group">
        <button @click="goToChangePassword" class="btn btn-primary">Alterar Password</button>
        <router-link :to="{ name: 'Dashboard' }" class="btn btn-secondary">Retroceder</router-link>
      </div>
    </div>
  </div>
</template>

  
  <script setup>
  import { ref, onMounted } from 'vue';
  import { useRouter } from 'vue-router';
  import axios from 'axios';
  
  const router = useRouter();
  const user = ref({});
  const profileLoaded = ref(false);
  
  onMounted(async () => {
    try {
      const response = await axios.get('/user');
      user.value = response.data;
      profileLoaded.value = true;
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

.dot-anim::after {
  content: ".";
  animation: dots 1.2s steps(3, end) infinite;
}

@keyframes dots {
  0% {
    content: "";
  }
  33% {
    content: ".";
  }
  66% {
    content: "..";
  }
  100% {
    content: "...";
  }
}

  </style>

  