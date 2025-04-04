import { defineStore } from 'pinia';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    isAuthenticated: !!localStorage.getItem('token'),
    token: localStorage.getItem('token') || null,
  }),
  actions: {
    login(token) {
      this.isAuthenticated = true;
      this.token = token;
      localStorage.setItem('token', token);
    },
    logout() {
      this.isAuthenticated = false;
      this.token = null;
      localStorage.removeItem('token');
    },
  },
});
