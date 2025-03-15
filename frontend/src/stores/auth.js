import { defineStore } from 'pinia';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    isAuthenticated: !!localStorage.getItem('token'),
  }),
  actions: {
    login(token) {
      this.isAuthenticated = true;
      localStorage.setItem('token', token);
    },
    logout() {
      this.isAuthenticated = false;
      localStorage.removeItem('token');
    },
  },
});