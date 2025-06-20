<template>
  <div class="dashboard-wrapper animate">
    <div class="dashboard-card">
      <h2 class="mb-3">👋 Bem-vindo ao Smart CRM</h2>
      <p class="lead">Esta é a sua área principal. Aqui pode gerir tudo o que precisa.</p>

      <div class="dashboard-section">
        <h4>🏢 Empresas</h4>
        <p>
          Registe a sua empresa e visualize os detalhes associados.
          Cada utilizador pode pertencer a diferentes empresas com permissões distintas.
        </p>
      </div>

      <div class="dashboard-section">
        <h4>📣 Campanhas</h4>
        <p>
          Crie campanhas de marketing ou vendas. Pode associar utilizadores da empresa a cada campanha.
        </p>
      </div>

      <div class="dashboard-section">
        <h4>📂 Importar Dados</h4>
        <p>
          Importe dados de clientes, produtos ou vendas. Estes dados serão utilizados nos algoritmos inteligentes integrados.
        </p>
      </div>

      <div class="dashboard-section">
        <h4>🧠 Algoritmos Inteligentes</h4>
        <p>
          Analise os dados com algoritmos de <strong>segmentação RFM</strong>, <strong>previsão de churn</strong> e
          <strong>recomendações de produtos</strong>.
          <!-- Os resultados podem ser visualizados em PDF ou diretamente na aplicação. -->
        </p>
      </div>

      <router-link to="/companies" class="btn-start mt-3">Comece agora → Registar Empresa</router-link>
    </div>
  </div>
</template>

<style scoped>
.dashboard-wrapper {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  min-height: calc(100vh - 100px);
  padding: 4vh 2rem 6vh;
  background-color: #f1f5f9;
  overflow: hidden; /* elimina scroll vertical */
}

.dashboard-card {
  background: #ffffff;
  padding: 2.5rem 3rem;
  border-radius: 1rem;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
  max-width: 850px;
  width: 100%;
  text-align: left;
  margin-top: 3vh;
}

h2 {
  font-weight: 700;
  color: #0f172a;
}

.lead {
  font-size: 1.05rem;
  color: #475569;
  margin-bottom: 1.5rem;
}

.dashboard-section {
  margin-bottom: 1.5rem;
}

.dashboard-section h4 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 0.3rem;
}

.dashboard-section p {
  font-size: 0.95rem;
  color: #475569;
}

.btn-start {
  display: inline-block;
  background-color: #2563eb;
  color: #ffffff;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  border-radius: 0.5rem;
  text-decoration: none;
  transition: all 0.3s ease;
}

.btn-start:hover {
  background-color: #1e40af;
}

.animate {
  animation: fade-slide 0.5s ease-out both;
}

@keyframes fade-slide {
  from {
    opacity: 0;
    transform: translateY(16px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
