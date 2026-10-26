import { watch, onMounted, onUnmounted } from 'vue';

// Pilha de modais abertos: o Esc fecha apenas o último que foi aberto
const pilha = [];

function aoPressionarTecla(e) {
  if (e.key !== 'Escape' || !pilha.length) return;
  e.preventDefault();
  pilha[pilha.length - 1]();
}

function empilhar(fechar) {
  if (!pilha.length) window.addEventListener('keydown', aoPressionarTecla);
  pilha.push(fechar);
}

function desempilhar(fechar) {
  const i = pilha.lastIndexOf(fechar);
  if (i !== -1) pilha.splice(i, 1);
  if (!pilha.length) window.removeEventListener('keydown', aoPressionarTecla);
}

/**
 * Registra a tecla Esc para fechar um modal.
 * Sem `aberto`, vale enquanto o componente estiver montado;
 * com `aberto` (getter ou ref), vale enquanto ele for verdadeiro.
 */
export function usarFecharComEsc(fechar, aberto = null) {
  if (aberto === null) {
    onMounted(() => empilhar(fechar));
  } else {
    watch(aberto, (estaAberto) => (estaAberto ? empilhar(fechar) : desempilhar(fechar)), { immediate: true });
  }
  onUnmounted(() => desempilhar(fechar));
}
