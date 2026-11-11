<template>
  <div class="space-y-6 animate-slide-up">
    <div class="sm:bg-white sm:rounded-2xl sm:border border-slate-200 sm:p-7 space-y-5">
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h2 class="text-lg font-semibold text-slate-900 tracking-tight">Comunicados</h2>
          <p class="text-xs text-slate-500">Avisos internos, campanhas, prazos e mudanças de rotina da empresa.</p>
        </div>
        <p v-if="comunicados.length" class="text-xs text-slate-400">{{ comunicados.length }} {{ comunicados.length === 1 ? 'comunicado' : 'comunicados' }}<template v-if="novos"> · <strong class="text-emerald-600">{{ novos }} {{ novos === 1 ? 'novo' : 'novos' }}</strong></template></p>
      </div>

      <div v-if="carregando" class="space-y-3" role="status">
        <span class="sr-only">Carregando comunicados...</span>
        <div v-for="n in 3" :key="n" class="h-28 rounded-2xl bg-slate-100 animate-pulse"></div>
      </div>
      <div v-else-if="erro" class="py-12 text-center text-sm text-rose-700" role="alert">{{ erro }}</div>
      <div v-else-if="!comunicados.length" class="py-12 text-center space-y-1">
        <AppIcon name="megaphone" size="28" class="mx-auto text-slate-300" />
        <p class="text-sm text-slate-500">Nenhum comunicado no momento.</p>
      </div>

      <template v-else>
        <div class="flex flex-wrap items-center gap-2">
          <div v-if="categorias.length > 1" class="flex flex-wrap gap-2">
            <button
              v-for="cat in ['Todos', ...categorias]"
              :key="cat"
              type="button"
              @click="filtro = cat"
              :aria-pressed="filtro === cat"
              class="h-8 px-3.5 rounded-full border text-xs font-semibold transition cursor-pointer"
              :class="filtro === cat ? 'bg-[#141414] border-[#141414] text-white' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300'"
            >{{ cat }}</button>
          </div>
          <input v-model="busca" type="search" placeholder="Buscar nos comunicados..." class="ml-auto h-8 w-full sm:w-56 px-3 rounded-full border border-slate-200 bg-slate-50 text-xs outline-none focus:bg-white focus:border-slate-400" aria-label="Buscar nos comunicados" />
        </div>

        <p v-if="!filtrados.length" class="py-10 text-center text-sm text-slate-400">Nenhum comunicado encontrado.</p>

        <ul class="space-y-3" role="list">
          <li
            v-for="c in filtrados"
            :key="c.id"
            class="relative rounded-2xl border bg-white overflow-hidden"
            :class="c.prioridade === 'importante' ? 'border-amber-300 shadow-sm shadow-amber-100' : 'border-slate-200'"
          >
            <span class="absolute left-0 top-0 bottom-0 w-1.5" :style="{ background: estilo(c).cor }" aria-hidden="true"></span>
            <article class="pl-5 pr-4 sm:pl-6 sm:pr-6 py-4 sm:py-5 flex gap-3.5">
              <span class="hidden sm:flex w-10 h-10 rounded-xl items-center justify-center shrink-0" :style="{ background: estilo(c).fundo, color: estilo(c).cor }" aria-hidden="true">
                <AppIcon :name="estilo(c).icone" size="18" />
              </span>
              <div class="min-w-0 flex-1 space-y-2">
                <div class="flex flex-wrap items-center gap-1.5">
                  <span class="text-[11px] font-semibold uppercase tracking-wider px-2.5 py-1 rounded-full" :style="{ background: estilo(c).fundo, color: estilo(c).cor }">{{ c.categoria }}</span>
                  <span v-if="ehNovo(c)" class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-emerald-500 text-white">Novo</span>
                  <span v-if="c.prioridade === 'importante'" class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 border border-amber-200">Importante</span>
                  <span v-if="c.fixado" class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">📌 Fixado</span>
                  <time class="ml-auto text-xs text-slate-400" :datetime="(c.publicar_em || c.publicado_em || '').slice(0, 10)" :title="dataBr(c.publicar_em || c.publicado_em)">{{ quando(c.publicar_em || c.publicado_em) }}</time>
                </div>

                <h3 class="text-base sm:text-lg font-semibold text-slate-900 leading-snug">{{ c.titulo }}</h3>

                <div
                  class="text-sm text-slate-600 leading-relaxed space-y-1 motion-safe:transition-all"
                  :class="longo(c) && !abertos[c.id] ? 'max-h-24 overflow-hidden relative corte' : ''"
                  v-html="textoParaHtml(c.texto)"
                ></div>
                <button v-if="longo(c)" type="button" @click="abertos[c.id] = !abertos[c.id]" :aria-expanded="!!abertos[c.id]" class="text-xs font-semibold text-[#9c2337] hover:underline cursor-pointer">
                  {{ abertos[c.id] ? 'Mostrar menos' : 'Ler comunicado completo' }}
                </button>

                <p v-if="c.validade" class="text-xs" :class="prazo(c).urgente ? 'text-amber-700 font-semibold' : 'text-slate-400'">
                  {{ prazo(c).texto }}
                </p>
              </div>
            </article>
          </li>
        </ul>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import AppIcon from './AppIcon.vue';
import { apiFetch } from '../utils/api.js';
import { textoParaHtml, dataBr } from '../utils/textoFormatado.js';

const comunicados = ref([]);
const carregando = ref(true);
const erro = ref('');
const filtro = ref('Todos');
const busca = ref('');
const abertos = reactive({});

const categorias = computed(() => [...new Set(comunicados.value.map((c) => c.categoria))]);
const filtrados = computed(() => {
  const termo = busca.value.trim().toLowerCase();
  return comunicados.value.filter((c) =>
    (filtro.value === 'Todos' || c.categoria === filtro.value) &&
    (!termo || `${c.titulo} ${c.texto}`.toLowerCase().includes(termo)));
});

// Cor e ícone por categoria (a cor da barra lateral e do selo)
const ESTILOS = {
  Aviso: { cor: '#0369a1', fundo: '#e0f2fe', icone: 'bell' },
  Campanha: { cor: '#047857', fundo: '#d1fae5', icone: 'megaphone' },
  Prazo: { cor: '#b45309', fundo: '#fef3c7', icone: 'calendar' },
  'Mudança de rotina': { cor: '#6d28d9', fundo: '#ede9fe', icone: 'refresh-cw' }
};
const estilo = (c) => ESTILOS[c.categoria] || { cor: '#475569', fundo: '#f1f5f9', icone: 'info' };

const tempo = (s) => Date.parse((s || '').replace(' ', 'T'));
// "Novo": publicado nos últimos 3 dias
const ehNovo = (c) => {
  const t = tempo(c.publicar_em || c.publicado_em);
  return !Number.isNaN(t) && Date.now() - t < 3 * 24 * 3600 * 1000;
};
const novos = computed(() => comunicados.value.filter(ehNovo).length);

// "hoje", "ontem", "há 5 dias"; depois de um mês, a data
function quando(s) {
  const t = tempo(s);
  if (Number.isNaN(t)) return dataBr(s);
  const dias = Math.floor((Date.now() - t) / 86400000);
  if (dias <= 0) return 'Hoje';
  if (dias === 1) return 'Ontem';
  if (dias < 30) return `Há ${dias} dias`;
  return dataBr(s);
}

// Textos longos começam recolhidos
const longo = (c) => (c.texto || '').length > 280 || (c.texto || '').split('\n').length > 5;

function prazo(c) {
  const fim = Date.parse(`${c.validade}T23:59:59`);
  const dias = Math.ceil((fim - Date.now()) / 86400000);
  if (Number.isNaN(dias)) return { texto: `Válido até ${dataBr(c.validade)}`, urgente: false };
  if (dias <= 0) return { texto: `Termina hoje (${dataBr(c.validade)})`, urgente: true };
  if (dias <= 7) return { texto: `Termina em ${dias} ${dias === 1 ? 'dia' : 'dias'} (${dataBr(c.validade)})`, urgente: true };
  return { texto: `Válido até ${dataBr(c.validade)}`, urgente: false };
}

onMounted(async () => {
  try {
    const res = await apiFetch('api/comunicados', { headers: { Authorization: `Bearer ${localStorage.getItem('vb_auth_token') || ''}` } });
    const data = await res.json();
    if (!res.ok) throw new Error();
    comunicados.value = data.comunicados || [];
  } catch (e) {
    erro.value = 'Não foi possível carregar os comunicados.';
  } finally {
    carregando.value = false;
  }
});
</script>

<style scoped>
/* Esmaece o fim do texto recolhido */
.corte::after {
  content: '';
  position: absolute;
  inset: auto 0 0 0;
  height: 2.25rem;
  background: linear-gradient(to bottom, rgba(255, 255, 255, 0), #fff);
  pointer-events: none;
}
</style>
