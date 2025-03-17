import { createRouter, createWebHistory } from 'vue-router';
import Login from '../components/auth/Login.vue';
import Register from '../components/auth/Register.vue';
import Home from '../components/Home.vue';
import Dashboard from '../components/Dashboard.vue';
import Profile from '../components/Profile.vue';
import ChangePassword from '../components/auth/ChangePassword.vue';
import { useAuthStore } from '../stores/auth';

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home,
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
  },
  {
    path: '/profile',
    name: 'Profile',
    component: Profile,
  },
  {
    path: '/change-password',
    name: 'ChangePassword',
    component: ChangePassword,
  },
  // Adicionar outras rotas aqui
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Navigation Guard
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  const publicPages = ['Home', 'Login', 'Register'];
  const authRequired = !publicPages.includes(to.name);

  if (authRequired && !authStore.isAuthenticated) {
    // Se a rota requer autenticação e o utilizador não está autenticado, redireciona para a página inicial
    next({ name: 'Home' });
  } else {
    next(); // Caso contrário, permite a navegação
  }
});

export default router;