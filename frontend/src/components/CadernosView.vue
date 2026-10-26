<template>
  <div class="space-y-6 animate-slide-up">
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-7">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
        <div>
          <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800">Cadernos do Colaborador</h3>
          <p class="text-xs text-slate-400 mt-0.5">Manuais, programas e políticas da Vila Boa para consulta.</p>
        </div>
        <div class="relative sm:w-64">
          <AppIcon name="search" size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            v-model="busca"
            type="text"
            placeholder="Buscar caderno..."
            class="w-full h-9 pl-9 pr-3 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-800 placeholder-slate-400 outline-none focus:border-slate-400 focus:bg-white transition"
          />
        </div>
      </div>

      <div v-if="carregando" class="py-12 text-center text-sm text-slate-400">Carregando cadernos...</div>

      <div v-else-if="erro" class="py-12 text-center text-sm text-rose-700">{{ erro }}</div>

      <div v-else-if="!cadernosFiltrados.length" class="py-12 text-center space-y-1">
        <AppIcon name="document" size="28" class="mx-auto text-slate-300" />
        <p class="text-sm text-slate-500">{{ busca ? 'Nenhum caderno encontrado para a busca.' : 'Nenhum caderno disponível no momento.' }}</p>
      </div>

      <div v-else class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3 pt-5">
        <button
          v-for="c in cadernosFiltrados"
          :key="c.arquivo"
          type="button"
          @click="abrir(c)"
          :disabled="abrindo === c.arquivo"
          class="flex items-center gap-3.5 p-4 rounded-xl border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-left transition cursor-pointer group disabled:opacity-60"
        >
          <span class="w-10 h-12 rounded-md bg-[#9c2337] text-white text-[10px] font-semibold flex items-end justify-center pb-1.5 shrink-0 shadow-sm">PDF</span>
          <span class="min-w-0 flex-1">
            <span class="block text-sm font-medium text-slate-800 group-hover:text-[#9c2337] transition line-clamp-2">{{ c.titulo }}</span>
            <span class="block text-xs text-slate-400 mt-0.5">{{ formatarTamanho(c.tamanho_bytes) }} · atualizado em {{ c.atualizado_em }}</span>
          </span>
          <span v-if="abrindo === c.arquivo" class="w-4 h-4 border-2 border-slate-300 border-t-transparent rounded-full animate-spin shrink-0"></span>
          <AppIcon v-else name="chevron-right" size="16" class="text-slate-300 group-hover:text-slate-500 shrink-0" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppIcon from './AppIcon.vue';
import { apiFetch } from '../utils/api.js';

const props = defineProps({
  buscaInicial: { type: String, default: '' }
});

const cadernos = ref([]);
const carregando = ref(true);
const erro = ref('');
const busca = ref(props.buscaInicial);
const abrindo = ref('');

const cadernosFiltrados = computed(() => {
  const termo = busca.value.trim().toLowerCase();
  if (!termo) return cadernos.value;
  return cadernos.value.filter(c => c.titulo.toLowerCase().includes(termo));
});

function cabecalhoAuth() {
  return { 'Authorization': `Bearer ${localStorage.getItem('vb_auth_token') || ''}` };
}

function formatarTamanho(bytes) {
  if (bytes >= 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1).replace('.', ',') + ' MB';
  return Math.max(1, Math.round(bytes / 1024)) + ' KB';
}

// O PDF exige o token, então é baixado como blob e aberto em nova aba
async function abrir(caderno) {
  const janela = window.open('', '_blank');
  abrindo.value = caderno.arquivo;
  try {
    const res = await apiFetch(`api/cadernos/arquivo?nome=${encodeURIComponent(caderno.arquivo)}`, { headers: cabecalhoAuth() });
    if (!res.ok) throw new Error();
    const url = URL.createObjectURL(await res.blob());
    if (janela) janela.location.href = url;
    else window.location.href = url;
  } catch (e) {
    janela?.close();
    erro.value = 'Não foi possível abrir o caderno. Tente novamente.';
  } finally {
    abrindo.value = '';
  }
}

onMounted(async () => {
  try {
    const res = await apiFetch('api/cadernos', { headers: cabecalhoAuth() });
    const data = await res.json();
    if (!res.ok) throw new Error(data.erro);
    cadernos.value = data.cadernos || [];
  } catch (e) {
    erro.value = 'Não foi possível carregar os cadernos.';
  } finally {
    carregando.value = false;
  }
});
</script>
