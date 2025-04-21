<template>
    <div class="invite p-4 max-w-xl mx-auto">
      <h2 class="text-2xl font-bold mb-4">Aceitar Convite</h2>
  
      <div v-if="loading" class="text-gray-500">A verificar convite...</div>
  
      <div v-else-if="successMessage" class="text-green-600 font-semibold">
        {{ successMessage }}<br />
        A redirecionar...
      </div>
  
      <div v-else-if="errorMessage" class="text-red-600 font-semibold">
        {{ errorMessage }}
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';
  import { useToast } from 'vue-toastification';
  
  const route = useRoute();
  const router = useRouter();
  const toast = useToast();
  
  const token = route.params.token;
  const loading = ref(true);
  const successMessage = ref('');
  const errorMessage = ref('');
  
  onMounted(async () => {
    try {
      const response = await axios.get(`/invites/accept/${token}`);
      successMessage.value = response.data.message;
      toast.success(response.data.message);
  
      setTimeout(() => {
        router.push({ name: 'Dashboard' });
      }, 3000);
    } catch (error) {
      if (error.response?.data?.error) {
        errorMessage.value = error.response.data.error;
      } else {
        errorMessage.value = 'Ocorreu um erro ao aceitar o convite.';
      }
      toast.error(errorMessage.value);
    } finally {
      loading.value = false;
    }
  });
  </script>
  
  <style scoped>
  .invite {
    padding: 20px;
    margin-top: 50px;
    text-align: center;
  }
  </style>
  