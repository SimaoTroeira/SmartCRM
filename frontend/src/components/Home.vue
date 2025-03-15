<template>
  <div class="home">
    <h1>Bem-vindo ao Smart CRM</h1>
    <p>Por favor, faça login ou registe-se para aceder a mais funcionalidades.</p>
    <div class="actions">
      <!-- Corrigir a sintaxe do atributo `to` -->
      <router-link :to="{ name: 'Login' }" class="btn btn-primary">Login</router-link>
      <router-link :to="{ name: 'Register' }" class="btn btn-secondary">Register</router-link>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

onMounted(() => {
  const token = localStorage.getItem('token');
  if (token) {
    // Se o usuário estiver logado, redireciona para o painel de controle
    router.push({ name: 'Dashboard' });
  }
});
</script>

<style scoped>
.home {
  position: absolute;
  padding: 20px;
  text-align: center;
  width: 65%;
  
}
.actions {
  margin-top: 20px;
}

h1 {
  color: #333;
}
</style>