// Interceptador global para garantir compatibilidade com subpastas (ex: /appvilaboafuncionario/)
const originalFetch = window.fetch;
window.fetch = function(input, init) {
  if (typeof input === 'string' && input.startsWith('/api')) {
    // No painel (/admin) a API é a mesma do portal, na pasta acima
    let pathname = window.location.pathname.replace(/admin\/?$/, '');
    if (!pathname.endsWith('/')) {
      pathname += '/';
    }
    input = pathname + input.slice(1);
  }
  return originalFetch.call(this, input, init);
};

import { createApp } from 'vue';
import App from './App.vue';
import AdminApp from './AdminApp.vue';
import { ehPainelAdmin } from './utils/sessaoAdmin.js';
import './style.css';

createApp(ehPainelAdmin() ? AdminApp : App).mount('#app');
