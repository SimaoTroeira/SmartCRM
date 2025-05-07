<template>
  <div id="app">
    <nav class="navbar navbar-expand-md navbar-dark bg-dark sticky-top flex-md-nowrap p-0 shadow">
      <div class="container-fluid">
        <router-link class="navbar-brand col-md-3 col-lg-2 me-0 px-3" :to="{ name: 'Home' }">
          <img src="@/assets/logo.svg" alt="Logo" width="30" height="24" class="d-inline-block align-text-top">
          Smart CRM
        </router-link>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
          aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
          <ul class="navbar-nav w-100 d-flex align-items-center">
            <!-- "Empresas" à esquerda -->
            <li v-if="isAuthenticated" class="nav-item">
              <a class="nav-link d-flex align-items-center" href="#" @click.prevent="redirectToCompany">
                <i class="bi bi-building me-2"></i> Empresas
              </a>
            </li>
            <li v-if="isAuthenticated" class="nav-item">
              <a class="nav-link d-flex align-items-center" href="#" @click.prevent="redirectToCampaign">
                <i class="bi bi-megaphone me-2"></i> Campanhas
              </a>
            </li>
            <li v-if="isAuthenticated" class="nav-item">
              <a class="nav-link d-flex align-items-center" href="#" @click.prevent="redirectToAlgorithms">
                <i class="bi bi-diagram-3 me-2"></i> Algoritmos
              </a>
            </li>
            <!-- Separador flexível entre esquerda e direita -->
            <li class="flex-grow-1"></li>

            <!-- Itens à direita -->
            <li v-if="!isAuthenticated" class="nav-item">
              <router-link class="nav-link" :class="{ active: $route.name === 'Register' }" :to="{ name: 'Register' }">
                <i class="bi bi-person-check-fill"></i>
                Register
              </router-link>
            </li>
            <li v-if="!isAuthenticated" class="nav-item">
              <router-link class="nav-link" :class="{ active: $route.name === 'Login' }" :to="{ name: 'Login' }">
                <i class="bi bi-box-arrow-in-right"></i>
                Login
              </router-link>
            </li>
            <li v-if="isAuthenticated" class="nav-item">
              <a class="nav-link" href="#" @click.prevent="redirectToImport">
                <i class="bi bi-upload"></i> Importar Dados
              </a>
            </li>
            <li v-if="isAuthenticated" class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown"
                aria-expanded="false">
                <i class="bi bi-person-circle"></i> {{ user.name || 'Conta' }}
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <li><router-link class="dropdown-item" :to="{ name: 'Profile' }">Perfil</router-link></li>
                <li><a class="dropdown-item" href="#" @click="handleLogout">Logout</a></li>
              </ul>
            </li>
          </ul>

        </div>
      </div>
    </nav>

    <div class="container-fluid">
      <div class="row">
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
          <router-view></router-view>
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from './stores/auth';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import axios from 'axios'
import { ref, onMounted } from 'vue'

const authStore = useAuthStore();
const { isAuthenticated } = storeToRefs(authStore); // <- Torna reativo
const router = useRouter();

const user = ref({})

const loadUser = async () => {
  if (!authStore.isAuthenticated) return; // <- evita chamada sem autenticação

  try {
    const response = await axios.get('http://127.0.0.1:8000/api/user');
    user.value = response.data;
  } catch (error) {
    user.value = {};
    // Podes opcionalmente deslogar se o token for inválido
    if (error.response?.status === 401) {
      authStore.logout();
      router.push({ name: 'Login' });
    }
  }
};


onMounted(() => {
  loadUser()
})

router.afterEach(() => {
  if (authStore.isAuthenticated) {
    loadUser();
  } else {
    user.value = {}; // limpa se não autenticado
  }
});


const handleLogout = () => {
  authStore.logout();
  router.push({ name: 'Home' });
};

const redirectToImport = () => {
  router.push({ name: 'ImportData' });
};
const redirectToCompany = () => {
  router.push({ name: 'Companies' });
};
const redirectToCampaign = () => {
  router.push({ name: 'Campaigns' });
};
const redirectToAlgorithms = () => {
  router.push({ name: 'Algorithms' });
};
</script>


<style>
@import "./assets/dashboard.css";
</style>
