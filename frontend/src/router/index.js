import { createRouter, createWebHistory } from 'vue-router';
import Login from '../components/auth/Login.vue';
import Register from '../components/auth/Register.vue';
import Home from '../components/Home.vue';
import Dashboard from '../components/Dashboard.vue';
import Profile from '../components/users/Profile.vue';
import ChangePassword from '../components/users/ChangePassword.vue';
import { useAuthStore } from '../stores/auth';
import ImportData from '../components/ImportData.vue';
import Company from '../components/companies/Company.vue';
import Campaign from '../components/campaigns/Campaign.vue';
import CompanyDetails from '../components/companies/CompanyDetails.vue';
import CampaignDetails from '../components/campaigns/CampaignDetails.vue';
import Algorithms from '../components/Algorithms.vue';

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
  {
    path: '/import-data',
    name: 'ImportData',
    component: ImportData,
  },
  {
    path: '/companies',
    name: 'Companies',
    component: Company,
  },
  {
    path: '/campaigns',
    name: 'Campaigns',
    component: Campaign,
  },
  {
    path: '/companies/:id',
    name: 'CompanyDetails',
    component: CompanyDetails,
  },
  {
    path: '/campaigns/:id',
    name: 'CampaignDetails',
    component: CampaignDetails,
  },
  {
    path: '/accept-invite/:token',
    name: 'AcceptInvite',
    component: () => import('@/components/AcceptInvite.vue'),
    meta: { requiresAuth: true } // garantir que o utilizador esteja autenticado
  },
  {
    path: '/algorithms',
    name: 'Algorithms',
    component: Algorithms,
  },


  // Adicionar outras rotas aqui
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Navigation Guard
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  const publicPages = ['Home', 'Login', 'Register'];
  const authRequired = !publicPages.includes(to.name);

  if (authStore.isAuthenticated) {
    try {
      // Verifica se o utilizador ainda existe na BD
      await axios.get('/api/user'); // ou '/user' se for essa a tua rota
    } catch (error) {
      // Se for 401, 403, ou 404, é porque o token está inválido ou o user foi apagado
      if ([401, 403, 404].includes(error.response?.status)) {
        authStore.logout();
        return next({ name: 'Home' });
      }
    }
  }

  // Se a rota requer login e o user não está autenticado, redireciona
  if (authRequired && !authStore.isAuthenticated) {
    return next({ name: 'Home' });
  }

  next();
});

export default router;