<template>
  <div class="min-h-screen bg-[#f7f7f8] text-slate-800">
    <!-- Verificando sessão -->
    <div v-if="verificando" class="min-h-screen flex items-center justify-center">
      <LogoLoader />
    </div>

    <!-- Login do painel -->
    <!-- Mesmo layout do login do colaborador; o lado institucional em vinho marca a área do RH -->
    <div v-else-if="!usuario" class="min-h-[100dvh] flex items-stretch sm:items-center justify-center sm:p-6 lg:p-8 bg-white sm:bg-slate-100">
      <div class="w-full sm:max-w-xl lg:max-w-5xl bg-white sm:rounded-2xl lg:rounded-3xl sm:shadow-2xl overflow-hidden flex flex-col lg:flex-row sm:border border-slate-200">
        <div class="lg:w-1/2 bg-[#9c2337] text-white p-6 pt-[calc(1.5rem+env(safe-area-inset-top,0px))] sm:p-8 lg:p-12 flex flex-col justify-between">
          <div class="space-y-5">
            <div class="flex items-center justify-between gap-3">
              <div class="inline-flex items-center bg-white/10 px-4 py-2.5 rounded-2xl border border-white/20">
                <img src="/img/logo-vilaboa-white.png" alt="Vila Boa Construções e Serviços" class="h-8 sm:h-9 w-auto object-contain" />
              </div>
              <span class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider bg-white text-[#9c2337] px-3 py-1.5 rounded-full whitespace-nowrap shrink-0">
                <AppIcon name="shield-check" size="12" />
                Área restrita · RH
              </span>
            </div>
            <div class="space-y-2 pt-1">
              <h1 class="text-2xl sm:text-3xl lg:text-4xl font-semibold leading-tight tracking-tight">Painel do RH</h1>
              <p class="text-white/80 text-xs sm:text-sm leading-relaxed">
                Gestão de holerites, espelhos de ponto, cadernos e acompanhamento dos colaboradores.
              </p>
            </div>
            <div class="hidden lg:grid gap-3 pt-4 border-t border-white/20">
              <div v-for="t in ['Acompanhamento de ciência e pendências', 'Envio de espelho de ponto com Autentique', 'Publicação de holerites e cadernos']" :key="t" class="flex items-center gap-3 text-xs text-white/90">
                <span class="w-6 h-6 rounded-lg bg-white/15 border border-white/15 flex items-center justify-center shrink-0"><AppIcon name="check" size="14" /></span>
                <span>{{ t }}</span>
              </div>
            </div>
          </div>
          <div class="hidden lg:block pt-6 text-[11px] text-white/60 font-medium">Acesso exclusivo da equipe de Recursos Humanos</div>
        </div>

        <form class="lg:w-1/2 p-6 sm:p-8 lg:p-10 space-y-5 bg-white" @submit.prevent="entrar">
          <div>
            <h2 class="text-xl sm:text-2xl font-semibold text-slate-900 tracking-tight">Entrar no painel</h2>
            <p class="text-xs sm:text-sm text-slate-500">Use seu e-mail e senha corporativos</p>
          </div>

          <div v-if="erro" class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-start gap-2">
            <AppIcon name="alert-triangle" size="16" class="shrink-0 mt-0.5" />
            <span class="font-medium">{{ erro }}</span>
          </div>

          <label class="block">
            <span class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">E-mail</span>
            <input v-model="email" type="email" inputmode="email" autocomplete="username" autocapitalize="off" required class="w-full px-4 py-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold text-base focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#9c2337] focus:border-transparent transition" />
          </label>
          <label class="block">
            <span class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Senha</span>
            <input v-model="senha" type="password" autocomplete="current-password" required class="w-full px-4 py-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold text-base focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#9c2337] focus:border-transparent transition" />
          </label>

          <button type="submit" :disabled="entrando" class="w-full py-4 px-6 rounded-2xl bg-[#141414] hover:bg-black disabled:opacity-50 active:scale-[0.98] text-white font-bold text-sm tracking-wide shadow-lg shadow-black/20 transition flex items-center justify-center gap-2 cursor-pointer">
            <span v-if="entrando" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span>{{ entrando ? 'Entrando...' : 'Entrar no painel' }}</span>
            <span v-if="!entrando">→</span>
          </button>

          <!-- Acessos de demonstração (somente enquanto ADMIN_DEMO_ATIVO) -->
          <div v-if="demos.length" class="pt-4 border-t border-slate-100 space-y-2">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Acessos de demonstração</p>
            <button
              v-for="d in demos"
              :key="d.email"
              type="button"
              @click="email = d.email; senha = senhaDemo"
              class="w-full text-left p-3 rounded-2xl border border-slate-200/90 hover:border-[#9c2337] bg-slate-50/80 hover:bg-rose-50/40 active:scale-[0.99] transition flex items-center gap-3 cursor-pointer"
            >
              <span class="w-9 h-9 rounded-xl bg-white border border-slate-200 flex items-center justify-center font-semibold text-xs text-[#9c2337] shrink-0">{{ d.nome.slice(0, 2).toUpperCase() }}</span>
              <span class="min-w-0">
                <span class="block text-xs font-bold text-slate-800 truncate">{{ d.nome }}</span>
                <span class="block text-[10px] text-slate-400 truncate">{{ d.email }} · {{ d.perfil }}</span>
              </span>
            </button>
          </div>

          <div class="text-center pt-2">
            <a :href="urlPortal" class="inline-flex items-center gap-1.5 h-9 px-4 rounded-full border border-slate-300 text-[11px] text-slate-700 hover:border-slate-400 hover:text-slate-900 font-semibold transition">
              <AppIcon name="user" size="13" />
              Sou colaborador: ir para o portal
            </a>
          </div>
        </form>
      </div>
    </div>

    <!-- Painel -->
    <div v-else class="flex h-screen overflow-hidden">
      <aside class="hidden md:flex w-60 shrink-0 flex-col bg-white border-r border-slate-200">
        <div class="h-16 px-4 flex items-center border-b border-slate-200">
          <img src="/img/logo-vilaboa-color.png" alt="Vila Boa Construções e Serviços" class="h-8 w-auto object-contain" />
        </div>
        <span class="block px-6 pt-5 pb-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Painel do RH</span>
        <nav class="px-3 space-y-1">
          <button
            v-for="s in secoes"
            :key="s.id"
            type="button"
            @click="secao = s.id"
            class="w-full flex items-center gap-3 px-3 py-3 rounded-xl text-xs font-bold transition cursor-pointer"
            :class="secao === s.id ? 'bg-slate-100 text-[#9c2337]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
          >
            <AppIcon :name="s.icone" size="18" class="text-slate-400" />
            {{ s.rotulo }}
          </button>
        </nav>
        <div class="mt-auto p-3 border-t border-slate-200">
          <div class="flex items-center gap-2.5 p-2 rounded-xl bg-slate-50 border border-slate-200">
            <div class="w-8 h-8 rounded-lg bg-[#141414] text-white flex items-center justify-center text-xs font-semibold shrink-0">{{ usuario.nome.slice(0, 2).toUpperCase() }}</div>
            <div class="min-w-0 flex-1">
              <p class="text-xs font-bold text-slate-900 truncate">{{ usuario.nome }}</p>
              <p class="text-[10px] text-slate-400 truncate">{{ usuario.email }}</p>
            </div>
            <button type="button" @click="sair" class="w-7 h-7 rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-700 flex items-center justify-center cursor-pointer" title="Sair" aria-label="Sair">
              <AppIcon name="log-out" size="13" />
            </button>
          </div>
        </div>
      </aside>

      <div class="flex-1 min-w-0 flex flex-col">
        <header class="h-16 shrink-0 bg-[#141414] px-4 sm:px-6 flex items-center gap-3">
          <!-- Busca de colaborador -->
          <div class="relative flex-1 max-w-md">
            <div class="flex items-center h-9 rounded-lg bg-white overflow-hidden">
              <AppIcon name="search" size="15" class="text-slate-400 ml-3 shrink-0" />
              <input
                v-model="busca"
                @focus="buscaAberta = true; carregarColaboradores()"
                @blur="fecharBusca"
                @keydown.enter.prevent="resultados[0] && abrirResumo(resultados[0])"
                type="text"
                placeholder="Buscar colaborador por nome, matrícula ou cargo..."
                class="flex-1 min-w-0 h-full px-3 text-sm text-slate-800 placeholder-slate-400 outline-none"
              />
            </div>
            <div v-if="buscaAberta && busca.trim()" class="absolute left-0 right-0 top-full mt-2 bg-white rounded-xl border border-slate-200 shadow-xl py-2 z-50 max-h-96 overflow-y-auto">
              <p v-if="!resultados.length" class="px-4 py-3 text-sm text-slate-500">Nenhum colaborador encontrado.</p>
              <button
                v-for="c in resultados"
                :key="c.matricula"
                type="button"
                @mousedown.prevent="abrirResumo(c)"
                class="w-full px-4 py-2.5 flex items-center gap-3 text-left hover:bg-slate-50 cursor-pointer"
              >
                <span class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center shrink-0"><AppIcon name="user" size="15" /></span>
                <span class="min-w-0">
                  <span class="block text-sm font-medium text-slate-800 truncate">{{ c.nome }}</span>
                  <span class="block text-xs text-slate-400 truncate">{{ c.matricula }} · {{ c.cargo }}</span>
                </span>
              </button>
            </div>
          </div>
          <button type="button" @click="sair" class="md:hidden ml-auto text-xs font-semibold text-white/80 hover:text-white cursor-pointer">Sair</button>
        </header>

        <!-- Seções no celular -->
        <div class="md:hidden bg-white border-b border-slate-200 px-3 py-2 overflow-x-auto">
          <div class="flex gap-1 w-max">
            <button
              v-for="s in secoes"
              :key="s.id"
              type="button"
              @click="secao = s.id"
              class="h-9 px-4 rounded-full text-xs font-semibold cursor-pointer"
              :class="secao === s.id ? 'bg-[#141414] text-white' : 'bg-slate-100 text-slate-600'"
            >{{ s.rotulo }}</button>
          </div>
        </div>

        <main class="flex-1 min-h-0 overflow-y-auto p-4 sm:p-6 lg:p-8">
          <RhView :colaborador="usuario" :aba-externa="secao" />
        </main>
      </div>
    </div>

    <ResumoColaboradorModal
      v-if="colaboradorResumo"
      :colaborador="colaboradorResumo"
      :ano="new Date().getFullYear()"
      :pode-cobrar="pode(usuario, 'rh_acompanhamento', 'cobrar_pendencias')"
      @close="colaboradorResumo = null"
      @abrir-painel="colaboradorResumo = null; secao = 'acompanhamento'"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import AppIcon from './components/AppIcon.vue';
import LogoLoader from './components/LogoLoader.vue';
import RhView from './components/RhView.vue';
import ResumoColaboradorModal from './components/ResumoColaboradorModal.vue';
import { pode } from './utils/permissoes.js';
import { tokenAdmin, CHAVE_TOKEN_ADMIN } from './utils/sessaoAdmin.js';

const verificando = ref(true);
const usuario = ref(null);
const email = ref('');
const senha = ref('');
const erro = ref('');
const entrando = ref(false);
// Cada seção do painel tem sua rota: /admin#/acompanhamento, #/cadernos, #/ponto, #/holerites
const SECOES_VALIDAS = ['acompanhamento', 'cadernos', 'ponto', 'holerites'];
const secaoDaUrl = () => {
  const h = window.location.hash.replace(/^#\/?/, '').split('?')[0];
  return SECOES_VALIDAS.includes(h) ? h : '';
};
const secao = ref(secaoDaUrl() || 'acompanhamento');

watch(secao, (s) => {
  if (`#/${s}` !== window.location.hash) window.history.pushState({ secao: s }, '', `#/${s}`);
});
window.addEventListener('popstate', () => {
  const s = secaoDaUrl();
  if (s) secao.value = s;
});

// Abre a seção da URL se o perfil tiver acesso a ela; senão, a primeira permitida
function escolherSecaoInicial() {
  const daUrl = secaoDaUrl();
  secao.value = secoes.value.some((s) => s.id === daUrl) ? daUrl : (secoes.value[0]?.id || 'acompanhamento');
}
const demos = ref([]);
const senhaDemo = ref('');

async function carregarDemos() {
  try {
    const data = await (await fetch('/api/admin/usuarios-demo')).json();
    demos.value = data.usuarios_para_teste || [];
    senhaDemo.value = data.senha || '';
  } catch (e) {
    demos.value = [];
  }
}

// Portal do colaborador fica na mesma pasta, sem o "/admin"
const urlPortal = window.location.pathname.replace(/admin\/?$/, '');

const secoes = computed(() => [
  { id: 'acompanhamento', rotulo: 'Acompanhamento', icone: 'shield-check', tela: 'rh_acompanhamento' },
  { id: 'cadernos', rotulo: 'Cadernos', icone: 'landmark', tela: 'rh_cadernos' },
  { id: 'ponto', rotulo: 'Espelho de ponto', icone: 'calendar', tela: 'rh_ponto' },
  { id: 'holerites', rotulo: 'Holerites', icone: 'document', tela: 'rh_holerites' }
].filter((s) => pode(usuario.value, s.tela, 'visualizar')));

async function entrar() {
  entrando.value = true;
  erro.value = '';
  try {
    const res = await fetch('/api/admin/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: email.value, senha: senha.value })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.erro);
    localStorage.setItem(CHAVE_TOKEN_ADMIN, data.admin_token);
    senha.value = '';
    usuario.value = data.usuario;
    escolherSecaoInicial();
  } catch (e) {
    erro.value = e.message || 'Não foi possível entrar.';
  } finally {
    entrando.value = false;
  }
}

function sair() {
  localStorage.removeItem(CHAVE_TOKEN_ADMIN);
  usuario.value = null;
  colaboradores.value = null;
  carregarDemos();
}

// Busca de colaborador (usa o resumo do RH)
const busca = ref('');
const buscaAberta = ref(false);
const colaboradores = ref(null);
const colaboradorResumo = ref(null);

async function carregarColaboradores() {
  if (colaboradores.value) return;
  try {
    const res = await fetch(`/api/rh/resumo?ano=${new Date().getFullYear()}`, { headers: { 'Authorization': `Bearer ${tokenAdmin()}` } });
    const data = await res.json();
    colaboradores.value = res.ok ? data.colaboradores || [] : [];
  } catch (e) {
    colaboradores.value = [];
  }
}

const resultados = computed(() => {
  const termo = busca.value.trim().toLowerCase();
  if (!termo) return [];
  return (colaboradores.value || [])
    .filter((c) => [c.nome, c.matricula, c.cargo, c.departamento].some((t) => (t || '').toLowerCase().includes(termo)))
    .slice(0, 8);
});

function abrirResumo(c) {
  colaboradorResumo.value = c;
  busca.value = '';
  buscaAberta.value = false;
}

function fecharBusca() {
  setTimeout(() => { buscaAberta.value = false; }, 120);
}

onMounted(async () => {
  const token = tokenAdmin();
  if (token) {
    try {
      const res = await fetch('/api/admin/me', { headers: { 'Authorization': `Bearer ${token}` } });
      if (res.ok) {
        usuario.value = (await res.json()).usuario;
        escolherSecaoInicial();
      } else {
        localStorage.removeItem(CHAVE_TOKEN_ADMIN);
      }
    } catch (e) {
      // segue para o login
    }
  }
  verificando.value = false;
  if (!usuario.value) carregarDemos();
});
</script>
