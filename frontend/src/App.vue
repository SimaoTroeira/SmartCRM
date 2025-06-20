<template>
  <div id="app">
    <!-- Navbar -->
    <nav v-if="!['Home', 'Login', 'Register'].includes($route.name)" class="navbar navbar-expand-md bg-dark shadow-sm">
      <div class="container-fluid">
        <router-link class="navbar-brand d-flex align-items-center gap-2" :to="{ name: 'Dashboard' }">
          <img src="@/assets/logo.svg" alt="Logo" width="34" height="34" />
          <span class="fw-bold fs-5">Smart CRM</span>
        </router-link>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
          aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
          <ul class="navbar-nav d-flex align-items-center gap-2">
            <li v-if="isAuthenticated" class="nav-item">
              <a class="nav-link" href="#" @click.prevent="redirectToCompany">
                <i class="bi bi-building"></i> Empresas
              </a>
            </li>
            <li v-if="isAuthenticated" class="nav-item">
              <a class="nav-link" href="#" @click.prevent="redirectToCampaign">
                <i class="bi bi-megaphone"></i> Campanhas
              </a>
            </li>
            <li v-if="isAuthenticated" class="nav-item">
              <a class="nav-link" href="#" @click.prevent="redirectToAlgorithms">
                <i class="bi bi-diagram-3"></i> Algoritmos
              </a>
            </li>
          </ul>

          <ul class="navbar-nav d-flex align-items-center gap-3">
            <li v-if="!isAuthenticated" class="nav-item">
              <router-link class="nav-link" :to="{ name: 'Register' }">
                <i class="bi bi-person-plus"></i> Registar
              </router-link>
            </li>
            <li v-if="!isAuthenticated" class="nav-item">
              <router-link class="nav-link" :to="{ name: 'Login' }">
                <i class="bi bi-box-arrow-in-right"></i> Entrar
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
              <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="navbarDropdown">
                <li><router-link class="dropdown-item" :to="{ name: 'Profile' }">Perfil</router-link></li>
                <li><a class="dropdown-item" href="#" @click.prevent="handleLogout">Logout</a></li>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Conteúdo principal -->
    <main class="app-content">
      <div class="content-wrapper">
        <router-view></router-view>
      </div>
    </main>

    <!-- Modal de confirmação de logout -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true" ref="logoutModalRef">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
          <div class="modal-header border-0">
            <h5 class="modal-title fw-bold" id="logoutModalLabel">Confirmar Logout</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <div class="modal-body">
            Tem a certeza de que pretende terminar a sessão?
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-danger" @click="confirmLogout">Sair</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from './stores/auth';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import axios from 'axios';
import { ref, onMounted } from 'vue';
import { Modal } from 'bootstrap';

const authStore = useAuthStore();
const { isAuthenticated } = storeToRefs(authStore);
const router = useRouter();
const user = ref({});
const logoutModalRef = ref(null);
let logoutModalInstance = null;

const loadUser = async () => {
  if (!authStore.isAuthenticated) return;

  try {
    const res = await axios.get('/user');
    user.value = res.data;
  } catch (error) {
    user.value = {};
    if (error.response?.status === 401) {
      authStore.logout();
      router.push({ name: 'Login' });
    }
  }
};

onMounted(() => {
  loadUser();
  if (logoutModalRef.value) {
    logoutModalInstance = new Modal(logoutModalRef.value);
  }
});

router.afterEach(() => {
  authStore.isAuthenticated ? loadUser() : user.value = {};
});

const handleLogout = () => {
  if (logoutModalInstance) {
    logoutModalInstance.show();
  }
};

const confirmLogout = () => {
  authStore.logout();
  router.push({ name: 'Home' });
  if (logoutModalInstance) {
    logoutModalInstance.hide();
  }
};

const redirectToImport = () => router.push({ name: 'ImportData' });
const redirectToCompany = () => router.push({ name: 'Companies' });
const redirectToCampaign = () => router.push({ name: 'Campaigns' });
const redirectToAlgorithms = () => router.push({ name: 'Algorithms' });
</script>

<style>
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: #f1f5f9;
  color: #1e293b;
}

.navbar {
  padding: 1rem 2rem;
  background-color: #1e293b !important;
  border-bottom: 2px solid #334155;
}

.navbar-brand {
  font-size: 1.25rem;
  color: white !important;
}

.nav-link {
  font-size: 1rem;
  color: #cbd5e1 !important;
  transition: 0.3s ease;
}

.nav-link:hover {
  color: white !important;
}

.dropdown-menu {
  background-color: #1e293b;
  border-radius: 8px;
  padding: 0.5rem 0;
}

.dropdown-item {
  color: #f8fafc;
  padding: 0.5rem 1rem;
}

.dropdown-item:hover {
  background-color: #334155;
  color: white;
}

.app-content {
  padding-top: 2rem;
  padding-bottom: 4rem;
  display: flex;
  justify-content: center;
}

.content-wrapper {
  width: 100%;
  max-width: 1200px;
  padding: 0 1.5rem;
}

@media (max-width: 768px) {
  .navbar-nav {
    text-align: center;
  }

  .nav-link {
    padding: 0.75rem 0;
  }

  .content-wrapper {
    padding: 0 1rem;
  }
}
</style>
