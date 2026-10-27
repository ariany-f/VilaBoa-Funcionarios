<template>
  <div class="space-y-6 animate-slide-up">
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-7">
      <div class="flex justify-end border-b border-slate-100 pb-4">
        <div class="relative w-full sm:w-64">
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

      <template v-else>
        <!-- Progresso de leitura (pontos, nível e conquistas) -->
        <div
          v-if="progresso.total || progresso.opcionaisTotal"
          class="mt-5 rounded-2xl text-white p-5 sm:p-6 bg-gradient-to-br transition-colors"
          :class="progresso.percentual === 100 ? 'from-emerald-700 to-emerald-900' : 'from-[#141414] to-black'"
        >
          <div class="flex flex-col sm:flex-row sm:items-center gap-5">
            <div class="relative w-24 h-24 shrink-0 self-center">
              <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90" aria-hidden="true">
                <circle cx="50" cy="50" r="42" fill="none" stroke="rgba(255,255,255,0.12)" stroke-width="9" />
                <circle
                  cx="50" cy="50" r="42" fill="none" :stroke="progresso.percentual === 100 ? '#6ee7b7' : '#9c2337'" stroke-width="9" stroke-linecap="round"
                  :stroke-dasharray="264" :stroke-dashoffset="264 - (264 * progresso.percentual) / 100"
                  class="transition-all duration-700"
                />
              </svg>
              <span class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-xl font-semibold leading-none">{{ progresso.percentual }}%</span>
                <span class="text-[10px] text-white/50 mt-1">{{ progresso.lidos }}/{{ progresso.total }}</span>
              </span>
            </div>

            <div class="flex-1 min-w-0 space-y-2 text-center sm:text-left">
              <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-3 py-1 rounded-full bg-white/10 border border-white/15">
                Nível · {{ progresso.nivel }}
              </span>
              <p class="text-lg font-semibold leading-snug">
                <template v-if="progresso.percentual === 100">Parabéns! Você está em dia com seus cadernos.</template>
                <template v-else>Faltam {{ progresso.total - progresso.lidos }} {{ progresso.total - progresso.lidos === 1 ? 'caderno' : 'cadernos' }} para ficar em dia.</template>
              </p>
              <p class="text-xs text-white/60">{{ progresso.pontos }} de {{ progresso.pontosMaximos }} pontos · cada caderno lido vale 100, cada leitura opcional vale 50.</p>
            </div>

            <button
              v-if="progresso.proximo"
              type="button"
              @click="abrir(progresso.proximo)"
              class="h-11 px-5 rounded-full bg-[#9c2337] hover:bg-[#831b2d] text-white text-sm font-semibold shrink-0 cursor-pointer"
            >
              Ler próximo: {{ progresso.proximo.titulo.length > 28 ? progresso.proximo.titulo.slice(0, 28) + '…' : progresso.proximo.titulo }}
            </button>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-5">
            <div
              v-for="c in progresso.conquistas"
              :key="c.id"
              class="rounded-xl px-3 py-2.5 border flex items-center gap-2.5 transition"
              :class="c.ok ? 'bg-white/10 border-white/20' : 'bg-white/[0.03] border-white/10 opacity-50'"
              :title="c.descricao"
            >
              <AppIcon :name="c.icone" size="18" class="shrink-0" :class="c.ok ? 'text-white' : 'text-white/40'" />
              <span class="min-w-0">
                <span class="block text-xs font-semibold leading-tight">{{ c.nome }}</span>
                <span class="block text-[10px] text-white/50 truncate">{{ c.ok ? 'Conquistado' : 'A conquistar' }}</span>
              </span>
            </div>
          </div>
        </div>

        <section
          v-for="grupo in grupos"
          :key="grupo.chave"
          :class="grupo.opcional ? 'mt-8 -mx-6 sm:-mx-7 -mb-6 sm:-mb-7 px-6 sm:px-7 py-6 bg-slate-50 border-t border-slate-200 rounded-b-2xl' : 'pt-6'"
        >
          <template v-if="grupo.opcional">
            <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">{{ grupo.categoria }}</h4>
            <p class="text-xs text-slate-400 mb-3">Materiais complementares, para ler quando quiser.</p>
          </template>
          <!-- Filtro de categorias -->
          <div v-else-if="categorias.length > 1" class="flex flex-wrap gap-2 mb-4">
            <button
              v-for="cat in ['Todas', ...categorias]"
              :key="cat"
              type="button"
              @click="categoriaAtiva = cat"
              class="h-8 px-3.5 rounded-full border text-xs font-semibold transition cursor-pointer"
              :class="categoriaAtiva === cat ? 'bg-[#141414] border-[#141414] text-white' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300'"
            >{{ cat }}</button>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-3">
        <button
          v-for="c in grupo.itens"
          :key="c.arquivo"
          type="button"
          @click="abrir(c)"
          :disabled="abrindo === c.arquivo"
          class="flex items-center gap-3.5 p-4 rounded-xl border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-left transition cursor-pointer group disabled:opacity-60"
        >
          <span class="w-10 h-12 rounded-md bg-[#9c2337] text-white text-[10px] font-semibold flex items-end justify-center pb-1.5 shrink-0 shadow-sm">PDF</span>
          <span class="min-w-0 flex-1">
            <span class="block text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ c.categoria || 'Outros' }}</span>
            <span class="block text-sm font-medium text-slate-800 group-hover:text-[#9c2337] transition line-clamp-2">{{ c.titulo }}</span>
            <span class="block text-xs text-slate-400 mt-0.5"><template v-if="c.paginas">{{ c.paginas }} {{ c.paginas === 1 ? 'página' : 'páginas' }} · </template>{{ formatarTamanho(c.tamanho_bytes) }} · atualizado em {{ c.atualizado_em }}</span>
            <span
              class="inline-flex items-center gap-1.5 mt-1.5 text-[11px] font-medium px-2 py-0.5 rounded-full border"
              :class="c.leitura ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : (c.obrigatorio ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-slate-50 text-slate-500 border-slate-200')"
            >
              <span class="w-1.5 h-1.5 rounded-full" :class="c.leitura ? 'bg-emerald-500' : (c.obrigatorio ? 'bg-amber-500' : 'bg-slate-400')"></span>
              {{ c.leitura ? 'Lido em ' + c.leitura.data_hora.slice(0, 10) : rotuloPendente(c) }}
            </span>
          </span>
          <span v-if="abrindo === c.arquivo" class="w-4 h-4 border-2 border-slate-300 border-t-transparent rounded-full animate-spin shrink-0"></span>
          <AppIcon v-else name="chevron-right" size="16" class="text-slate-300 group-hover:text-slate-500 shrink-0" />
        </button>
          </div>
        </section>
      </template>
    </div>

    <PdfReader
      v-if="leitor"
      :titulo="leitor.caderno.titulo"
      :arquivo="leitor.caderno.arquivo"
      :blob="leitor.blob"
      :leituraInicial="leitor.caderno.leitura"
      @confirmado="aoConfirmar"
      @close="leitor = null; versaoProgresso++"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, defineAsyncComponent } from 'vue';
import AppIcon from './AppIcon.vue';
// O leitor (pdf.js) só é baixado quando um caderno é aberto
const PdfReader = defineAsyncComponent(() => import('./PdfReader.vue'));
import { resumirProgresso } from '../utils/progressoCadernos.js';
import { apiFetch } from '../utils/api.js';

const props = defineProps({
  buscaInicial: { type: String, default: '' },
  // Caderno vindo de um link de aviso: abre direto no leitor
  abrirArquivo: { type: String, default: '' }
});
const emit = defineEmits(['progresso', 'aberto']);

const cadernos = ref([]);
const carregando = ref(true);
const erro = ref('');
const busca = ref(props.buscaInicial);
const abrindo = ref('');
const leitor = ref(null);
const versaoProgresso = ref(0); // força reler o progresso ao fechar o leitor

// Cadernos do colaborador num único grid (filtrável por categoria); os opcionais ficam numa seção à parte no fim
const categoriaAtiva = ref('Todas');
const categorias = computed(() => [...new Set(cadernos.value.filter((c) => c.obrigatorio).map((c) => c.categoria || 'Outros'))].sort());

const grupos = computed(() => {
  const ordenar = (lista) => lista.sort((x, y) => (!!x.leitura - !!y.leitura) || x.titulo.localeCompare(y.titulo));
  const meus = cadernosFiltrados.value.filter((c) => c.obrigatorio && (categoriaAtiva.value === 'Todas' || (c.categoria || 'Outros') === categoriaAtiva.value));
  const opcionais = cadernosFiltrados.value.filter((c) => !c.obrigatorio);
  const lista = [];
  if (meus.length) lista.push({ chave: '__meus', categoria: 'Meus cadernos', itens: ordenar(meus) });
  if (opcionais.length) lista.push({ chave: '__opcionais', categoria: 'Leituras opcionais', opcional: true, itens: ordenar(opcionais) });
  return lista;
});

const progresso = computed(() => resumirProgresso(cadernos.value));

// Mantém o progresso do menu lateral atualizado
watch(progresso, (p) => { if (!carregando.value) emit('progresso', { lidos: p.lidos, total: p.total }); }, { immediate: true });

const cadernosFiltrados = computed(() => {
  const termo = busca.value.trim().toLowerCase();
  if (!termo) return cadernos.value;
  return cadernos.value.filter(c => c.titulo.toLowerCase().includes(termo) || (c.categoria || '').toLowerCase().includes(termo));
});

function cabecalhoAuth() {
  return { 'Authorization': `Bearer ${localStorage.getItem('vb_auth_token') || ''}` };
}

function formatarTamanho(bytes) {
  if (bytes >= 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1).replace('.', ',') + ' MB';
  return Math.max(1, Math.round(bytes / 1024)) + ' KB';
}

// O PDF exige o token, então é baixado como blob e aberto no leitor do portal
async function abrir(caderno) {
  abrindo.value = caderno.arquivo;
  erro.value = '';
  try {
    const res = await apiFetch(`api/cadernos/arquivo?nome=${encodeURIComponent(caderno.arquivo)}`, { headers: cabecalhoAuth() });
    if (!res.ok) throw new Error();
    leitor.value = { caderno, blob: await res.blob() };
  } catch (e) {
    erro.value = 'Não foi possível abrir o caderno. Tente novamente.';
  } finally {
    abrindo.value = '';
  }
}

// Mostra o progresso guardado pelo leitor neste aparelho
function rotuloPendente(c) {
  versaoProgresso.value;
  try {
    const p = JSON.parse(localStorage.getItem(`vb_leitura_${c.arquivo}`) || '{}');
    if (p.lidas && p.total) return `${p.lidas} de ${p.total} páginas lidas`;
  } catch (e) { /* sem progresso salvo */ }
  return 'Leitura pendente';
}

function aoConfirmar(leitura) {
  const c = cadernos.value.find(x => x.arquivo === leitor.value?.caderno.arquivo);
  if (c) c.leitura = leitura;
}

onMounted(async () => {
  try {
    const res = await apiFetch('api/cadernos', { headers: cabecalhoAuth() });
    const data = await res.json();
    if (!res.ok) throw new Error(data.erro);
    cadernos.value = data.cadernos || [];
    const doLink = props.abrirArquivo && cadernos.value.find((c) => c.arquivo === props.abrirArquivo);
    if (doLink) {
      abrir(doLink);
      emit('aberto');
    }
  } catch (e) {
    erro.value = 'Não foi possível carregar os cadernos.';
  } finally {
    carregando.value = false;
  }
});
</script>
