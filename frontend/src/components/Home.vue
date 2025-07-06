<template>
  <div class="home-container d-flex justify-content-center align-items-center">
    <div class="welcome-card text-center shadow-lg p-5 animate-fade-in">
      <h1 class="fw-bold display-8 mb-3">Bem-vindo ao <br />Smart CRM</h1>
      <p class="text-muted fs-5 mb-4">
        Por favor, faça login ou registe-se para aceder às funcionalidades da plataforma.
      </p>
      <div class="d-flex justify-content-center gap-3">
        <router-link :to="{ name: 'Login' }" class="btn btn-primary btn-lg px-4">
          <i class="bi bi-box-arrow-in-right me-2"></i> Entrar
        </router-link>
        <router-link :to="{ name: 'Register' }" class="btn btn-outline-secondary btn-lg px-4">
          <i class="bi bi-person-plus me-2"></i> Registar
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.home-container {
  min-height: calc(100vh - 80px);
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: #f1f5f9;
  padding-top: 2rem;
}

.welcome-card {
  background: white;
  padding: 2rem 3rem;
  border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  max-width: 480px;
  text-align: center;
  margin-top: -40px;
}

.animate-fade-in {
  animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
