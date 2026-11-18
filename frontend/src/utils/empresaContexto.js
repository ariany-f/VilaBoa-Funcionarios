import { ref, computed, watch } from 'vue';
import { urlLogoEmpresa } from './logoEmpresa.js';

// Empresa em que o usuário do RH está trabalhando no painel. Ele escolhe no login (quando tem mais
// de uma) e troca no menu lateral. Todas as telas do RH filtram por ela e o logo do painel é o dela.
// '' = nenhuma escolhida ainda (tela de login / escolha de empresa, sem logo).
const CHAVE = 'vb_rh_empresa_contexto';

function lerSalvo() {
  try { return localStorage.getItem(CHAVE) || ''; } catch (e) { return ''; }
}

export const empresaContexto = ref('');
// Empresas que o usuário logado pode escolher (nomes) e a versão do logo de cada uma
export const empresasContexto = ref([]);
const versoesLogo = ref({});

// Lembra a última empresa (mesmo depois de sair) para sugerir no próximo login
watch(empresaContexto, (v) => {
  try { if (v) localStorage.setItem(CHAVE, v); } catch (e) { /* só não lembra */ }
});

// Recebe as empresas do usuário (login ou sessão restaurada): [{ nome, logo }]
export function definirEmpresasDoUsuario(lista) {
  empresasContexto.value = (lista || []).map((e) => e.nome);
  versoesLogo.value = Object.fromEntries((lista || []).map((e) => [e.nome, e.logo]));
}

// Última empresa usada, se o usuário ainda tem acesso a ela
export function ultimaEmpresa() {
  const salva = lerSalvo();
  return empresasContexto.value.includes(salva) ? salva : '';
}

// Empresa para entrar direto: a última usada ou, com uma empresa só, ela mesma
export function empresaSugerida() {
  const salva = lerSalvo();
  if (empresasContexto.value.includes(salva)) return salva;
  return empresasContexto.value.length === 1 ? empresasContexto.value[0] : '';
}

export function limparEmpresaContexto() {
  empresaContexto.value = '';
  empresasContexto.value = [];
  versoesLogo.value = {};
}

// Logo da empresa escolhida (null = sem empresa ou empresa sem logo cadastrado)
export const logoEmpresaContexto = computed(() => {
  const nome = empresaContexto.value;
  return nome ? urlLogoEmpresa(nome, versoesLogo.value[nome]) : null;
});

// Item de "todas as empresas" aparece em qualquer empresa escolhida
export const doContexto = (empresaDoItem) => !empresaContexto.value || !empresaDoItem || empresaDoItem === 'todas' || empresaDoItem === empresaContexto.value;
