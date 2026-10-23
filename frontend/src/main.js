// Interceptador global para garantir compatibilidade com subpastas (ex: /appvilaboafuncionario/)
const originalFetch = window.fetch;
window.fetch = function(input, init) {
  if (typeof input === 'string' && input.startsWith('/api')) {
    let pathname = window.location.pathname;
    if (!pathname.endsWith('/')) {
      pathname += '/';
    }
    input = pathname + input.slice(1);
  }
  return originalFetch.call(this, input, init);
};

import { createApp } from 'vue';
import App from './App.vue';
import './style.css';

createApp(App).mount('#app');
