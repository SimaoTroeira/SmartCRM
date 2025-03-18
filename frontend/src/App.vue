<template>
  <div id="app">
    <nav class="navbar navbar-expand-md navbar-dark bg-dark sticky-top flex-md-nowrap p-0 shadow">
      <div class="container-fluid">
        <router-link class="navbar-brand col-md-3 col-lg-2 me-0 px-3" :to="{ name: 'Home' }">
          <img src="@/assets/logo.svg" alt="Logo" width="30" height="24" class="d-inline-block align-text-top">
          Smart CRM
        </router-link>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
          data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
          <ul class="navbar-nav">
            <li v-if="!authStore.isAuthenticated" class="nav-item">
              <router-link class="nav-link" :class="{ active: $route.name === 'Register' }"
                            :to="{ name: 'Register' }">
                <i class="bi bi-person-check-fill"></i>
                Register
              </router-link>
            </li>
            <li v-if="!authStore.isAuthenticated" class="nav-item">
              <router-link class="nav-link" :class="{ active: $route.name === 'Login' }"
                            :to="{ name: 'Login' }">
                <i class="bi bi-box-arrow-in-right"></i>
                Login
              </router-link>
            </li>
            <li v-if="authStore.isAuthenticated" class="nav-item">
              <a class="nav-link" href="#" @click.prevent="redirectToImport">
                <i class="bi bi-upload"></i> Importar Dados
              </a>
            </li>
            <li v-if="authStore.isAuthenticated" class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle"></i> Conta
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

const authStore = useAuthStore();
const router = useRouter();

const handleLogout = () => {
  authStore.logout();
  router.push({ name: 'Home' });
};

const redirectToImport = () => {
  router.push({ name: 'ImportData' });
};
</script>

<style>
@import "./assets/dashboard.css";
</style>
