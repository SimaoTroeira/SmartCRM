import { createRouter, createWebHistory } from 'vue-router';
import Login from '../components/auth/Login.vue';
import Register from '../components/auth/Register.vue';
import Home from '../components/Home.vue';
import Dashboard from '../components/Dashboard.vue';
import Profile from '../components/Profile.vue';
import { useAuthStore } from '../stores/auth';
import ChangePassword from '../components/auth/ChangePassword.vue';

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
    meta: { requiresAuth: true }, // Meta campo para indicar que a rota requer autenticação
  },
  {
    path: '/profile',
    name: 'Profile',
    component: Profile,
    meta: { requiresAuth: true }, // Meta campo para indicar que a rota requer autenticação
  },
  {
    path: '/change-password',
    name: 'ChangePassword',
    component: ChangePassword,
    meta: { requiresAuth: true },
  },
  // Adicione outras rotas conforme necessário
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});


// Guardião de navegação global
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    // Se a rota requer autenticação e o usuário não está autenticado, redireciona para a página inicial
    next({ name: 'Home' });
  } else {
    next(); // Caso contrário, permite a navegação
  }
});

export default router;