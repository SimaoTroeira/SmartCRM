<template>
    <div class="dashboard">
      <h1>Painel de Controlo</h1>
      <p>Bem-vindo ao seu painel de controlo. Aqui pode aceder a funcionalidades exclusivas para utilizadores logados.</p>
      <!-- Adicione mais conteúdo ou funcionalidades específicas do painel aqui -->
    </div>
  </template>
  
  <script setup>
  import { onMounted } from 'vue';
  import { useRouter } from 'vue-router';
  
  const router = useRouter();
  
  </script>
  
  <style scoped>
  .dashboard {
    padding: 20px;
  }
  
  h1 {
    color: #333;
  }
  </style>