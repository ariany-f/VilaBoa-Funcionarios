<template>
  <div class="space-y-6 animate-slide-up">
    <!-- Treinamentos aparecem dentro do cartão da Academia: sem borda nem recuo próprios -->
    <div :class="ehTreinamento ? 'space-y-4' : 'sm:bg-white sm:rounded-2xl sm:border border-slate-200 sm:p-6 space-y-4'">
      <!-- Diretrizes: título, slogan e pesquisa (por título, categoria e tags) -->
      <div v-if="!ehTreinamento && !carregando && !erro" class="space-y-2.5">
        <div class="space-y-0.5">
          <h2 class="text-lg font-semibold text-slate-900 tracking-tight">Diretrizes Vila Boa</h2>
          <p class="text-xs text-slate-500">Conhecer as regras, direitos, deveres e benefícios.</p>
        </div>
        <div class="relative">
          <AppIcon name="search" size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            v-model="busca"
            type="search"
            placeholder="Pesquisar diretrizes: férias, vale transporte, benefícios..."
            class="w-full h-10 pl-10 pr-4 rounded-xl border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400"
          />
        </div>
        <div v-if="tagsDisponiveis.length" class="flex flex-wrap items-center gap-1.5">
          <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mr-1">Assuntos</span>
          <button
            v-for="t in tagsDisponiveis"
            :key="t"
            type="button"
            @click="busca = semAcento(busca) === semAcento(t) ? '' : t"
            class="h-6 px-2.5 rounded-full border text-[11px] font-semibold transition cursor-pointer"
            :class="semAcento(busca) === semAcento(t) ? 'bg-[#9c2337] border-[#9c2337] text-white' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300'"
          >{{ t }}</button>
        </div>
      </div>

      <div v-if="carregando" class="py-12 text-center text-sm text-slate-400">Carregando...</div>

      <div v-else-if="erro" class="py-12 text-center text-sm text-rose-700">{{ erro }}</div>

      <div v-else-if="!cadernosFiltrados.length" class="py-12 text-center space-y-1">
        <AppIcon name="document" size="28" class="mx-auto text-slate-300" />
        <p class="text-sm text-slate-500">{{ busca ? 'Nenhum item encontrado para a busca.' : (ehTreinamento ? 'Nenhum treinamento disponível no momento.' : 'Nenhum caderno disponível no momento.') }}</p>
      </div>

      <template v-else>
        <!-- Progresso de leitura (pontos, nível e conquistas) -->
        <div
          v-if="progresso.total || progresso.opcionaisTotal"
          class="rounded-2xl text-white p-3.5 sm:p-4 bg-gradient-to-br transition-colors"
          :class="progresso.percentual === 100 ? 'from-emerald-700 to-emerald-900' : 'from-[#141414] to-black'"
        >
          <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
            <div class="relative w-16 h-16 shrink-0 self-center">
              <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90" aria-hidden="true">
                <circle cx="50" cy="50" r="42" fill="none" stroke="rgba(255,255,255,0.12)" stroke-width="9" />
                <circle
                  cx="50" cy="50" r="42" fill="none" :stroke="progresso.percentual === 100 ? '#6ee7b7' : '#9c2337'" stroke-width="9" stroke-linecap="round"
                  :stroke-dasharray="264" :stroke-dashoffset="264 - (264 * progresso.percentual) / 100"
                  class="transition-all duration-700"
                />
              </svg>
              <span class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-base font-semibold leading-none">{{ progresso.percentual }}%</span>
                <span class="text-[9px] text-white/50 mt-0.5">{{ progresso.lidos }}/{{ progresso.total }}</span>
              </span>
            </div>

            <div class="flex-1 min-w-0 space-y-1 text-center sm:text-left">
              <span v-if="!ehTreinamento" class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-3 py-1 rounded-full bg-white/10 border border-white/15">
                Nível · {{ progresso.nivel }}
              </span>
              <p class="text-base font-semibold leading-snug">
                <template v-if="progresso.percentual === 100">{{ ehTreinamento ? 'Parabéns! Você concluiu todos os seus treinamentos.' : 'Parabéns! Você está em dia com suas leituras.' }}</template>
                <template v-else>{{ ehTreinamento ? 'Conclua seus treinamentos e emita o certificado.' : 'Fique em dia com as informações da empresa. Sua leitura é importante!' }}</template>
              </p>
              <p class="text-xs text-white/60">{{ ehTreinamento ? 'Cada treinamento concluído gera um certificado com código de validação.' : 'Consulte os manuais, as políticas e as orientações da empresa.' }}</p>
            </div>

            <button
              v-if="progresso.proximo"
              type="button"
              @click="abrir(progresso.proximo)"
              class="h-10 px-5 rounded-full bg-[#9c2337] hover:bg-[#831b2d] text-white text-sm font-semibold shrink-0 cursor-pointer"
            >
              Ler próximo: {{ progresso.proximo.titulo.length > 28 ? progresso.proximo.titulo.slice(0, 28) + '…' : progresso.proximo.titulo }}
            </button>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-3">
            <div
              v-for="c in progresso.conquistas"
              :key="c.id"
              class="rounded-xl px-2.5 py-1.5 border flex items-center gap-2 transition"
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
          :class="grupo.opcional ? (ehTreinamento ? 'py-5 px-4 rounded-2xl bg-slate-50 border border-slate-200' : 'sm:-mx-6 sm:-mb-6 sm:px-6 py-5 bg-slate-50 border-t border-slate-200 sm:rounded-b-2xl') : ''"
        >
          <template v-if="grupo.opcional">
            <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">{{ grupo.categoria }}</h4>
            <p class="text-xs text-slate-400 mb-3">Materiais complementares, para ler quando quiser.</p>
          </template>
          <!-- Filtro de categorias -->
          <div v-else-if="!busca.trim()" class="flex flex-wrap gap-2 mb-4">
            <button
              v-for="cat in categorias"
              :key="cat"
              type="button"
              @click="categoriaAtiva = cat"
              class="h-8 px-3.5 rounded-full border text-xs font-semibold transition cursor-pointer"
              :class="categoriaAtiva === cat ? 'bg-[#141414] border-[#141414] text-white' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300'"
            >{{ cat }}</button>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
        <button
          v-for="c in grupo.itens"
          :key="c.arquivo"
          type="button"
          @click="abrir(c)"
          :disabled="abrindo === c.arquivo"
          class="flex flex-col rounded-2xl border border-slate-200 bg-white overflow-hidden text-left transition cursor-pointer group hover:shadow-lg hover:border-slate-300 disabled:opacity-60"
        >
          <span class="relative flex items-center justify-center px-8 pt-8 pb-6" :class="c.leitura ? 'bg-emerald-50' : (c.obrigatorio ? 'bg-amber-50' : 'bg-slate-100')">
            <img v-if="c.capa" :src="urlCapa(c)" alt="" loading="lazy" class="w-full max-w-[220px] aspect-[3/4] rounded-md object-cover shadow-xl border border-slate-200" />
            <span v-else class="w-full max-w-[220px] aspect-[3/4] rounded-md bg-[#9c2337] text-white text-sm font-semibold flex items-end justify-center pb-3 shadow-xl">PDF</span>
            <span v-if="c.leitura" class="absolute top-3 right-3 w-7 h-7 rounded-full bg-emerald-500 text-white flex items-center justify-center text-sm">✓</span>
            <span v-if="abrindo === c.arquivo" class="absolute top-3 left-3 w-5 h-5 border-2 border-slate-400 border-t-transparent rounded-full animate-spin"></span>
          </span>
          <span class="flex flex-col flex-1 p-5 gap-2">
            <span class="self-start text-[11px] font-semibold uppercase tracking-wider px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">{{ c.categoria || 'Outros' }}</span>
            <span class="block text-lg font-semibold text-slate-900 group-hover:text-[#9c2337] transition leading-snug line-clamp-2">{{ tituloSemEmpresa(c.titulo) }}</span>
            <span v-if="c.tags && c.tags.length" class="flex flex-wrap gap-1">
              <span v-for="t in c.tags.slice(0, 4)" :key="t" class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">{{ t }}</span>
            </span>
            <span class="block text-sm text-slate-400"><template v-if="c.paginas">{{ c.paginas }} {{ c.paginas === 1 ? 'página' : 'páginas' }}</template><template v-if="c.paginas"> · </template>{{ formatarTamanho(c.tamanho_bytes) }}</span>
            <span
              class="self-start inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full border"
              :class="c.leitura ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : (c.obrigatorio ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-slate-50 text-slate-500 border-slate-200')"
            >
              <span class="w-1.5 h-1.5 rounded-full" :class="c.leitura ? 'bg-emerald-500' : (c.obrigatorio ? 'bg-amber-500' : 'bg-slate-400')"></span>
              {{ c.leitura ? (ehTreinamento ? 'Treinamento concluído' : 'Leitura concluída') : rotuloPendente(c) }}
            </span>
            <span v-if="prazoInfo(c.prazo, !!c.leitura)" class="self-start text-[11px] font-semibold px-2.5 py-1 rounded-full border" :class="prazoInfo(c.prazo, !!c.leitura).classe">{{ prazoInfo(c.prazo, !!c.leitura).texto }}</span>
            <span v-if="!c.leitura && percentualLido(c)" class="block h-2 rounded-full bg-slate-200 overflow-hidden">
              <span class="block h-full bg-[#9c2337] rounded-full" :style="{ width: percentualLido(c) + '%' }"></span>
            </span>
            <span
              class="mt-auto pt-2 space-y-2"
            >
              <span
                v-if="ehTreinamento && c.leitura"
                role="button"
                tabindex="0"
                @click.stop="emitirCertificado(c)"
                @keydown.enter.stop.prevent="emitirCertificado(c)"
                class="flex items-center justify-center gap-2 h-11 rounded-xl text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition"
              >Emitir certificado</span>
              <span
                class="flex items-center justify-center gap-2 h-11 rounded-xl text-sm font-semibold border transition"
                :class="c.leitura ? 'border-[#9c2337] text-[#9c2337] bg-white group-hover:bg-rose-50' : 'bg-[#9c2337] border-[#9c2337] text-white group-hover:bg-[#831b2d]'"
              >{{ c.leitura ? (ehTreinamento ? 'Rever treinamento' : 'Reler caderno') : (percentualLido(c) ? 'Continuar leitura' : (ehTreinamento ? 'Iniciar treinamento' : 'Ler caderno')) }} →</span>
            </span>
          </span>
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
    <CertificadoModal v-if="certModal" :html="certModal.html" :nome="certModal.nome" @fechar="certModal = null" />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, defineAsyncComponent } from 'vue';
import AppIcon from './AppIcon.vue';
import { urlCapa } from '../utils/capas.js';
import { prazoInfo } from '../utils/prazo.js';
import { logoEmpresa } from '../utils/logoEmpresa.js';
import { htmlCertificadoCaderno } from '../utils/certificados.js';
import CertificadoModal from './CertificadoModal.vue';
// O leitor (pdf.js) só é baixado quando um caderno é aberto
const PdfReader = defineAsyncComponent(() => import('./PdfReader.vue'));
import { resumirProgresso } from '../utils/progressoCadernos.js';
import { apiFetch } from '../utils/api.js';

const props = defineProps({
  buscaInicial: { type: String, default: '' },
  // Caderno vindo de um link de aviso: abre direto no leitor
  abrirArquivo: { type: String, default: '' },
  // 'politica' (Diretrizes Vila Boa) ou 'treinamento' (leitura + certificado ao concluir)
  tipo: { type: String, default: 'politica' },
  colaborador: { type: Object, default: null }
});
const ehTreinamento = props.tipo === 'treinamento';
const emit = defineEmits(['progresso', 'aberto']);

const cadernos = ref([]);
const carregando = ref(true);
const erro = ref('');
const busca = ref(props.buscaInicial);
const abrindo = ref('');
const leitor = ref(null);
const versaoProgresso = ref(0); // força reler o progresso ao fechar o leitor

// Cadernos do colaborador num único grid (filtrável por categoria); os opcionais ficam numa seção à parte no fim
const CATEGORIAS_FIXAS = ehTreinamento ? ['Todos'] : ['Manuais', 'Políticas', 'Programas'];
const categoriaAtiva = ref(CATEGORIAS_FIXAS[0]);
// Abas fixas (Manuais | Políticas | Programas); outras categorias só aparecem se tiverem cadernos
const categorias = computed(() => {
  const extras = [...new Set(cadernos.value.filter((c) => c.obrigatorio).map((c) => c.categoria || 'Outros'))].filter((c) => !CATEGORIAS_FIXAS.includes(c)).sort();
  return [...CATEGORIAS_FIXAS, ...extras];
});
const tituloSemEmpresa = (t) => t.replace(/\s*Vila Boa/gi, '').replace(/\s+/g, ' ').trim();

const grupos = computed(() => {
  const ordenar = (lista) => lista.sort((x, y) => (!!x.leitura - !!y.leitura) || x.titulo.localeCompare(y.titulo));
  const pesquisando = !!busca.value.trim();
  const meus = cadernosFiltrados.value.filter((c) => c.obrigatorio && (pesquisando || categoriaAtiva.value === 'Todos' || (c.categoria || 'Outros') === categoriaAtiva.value));
  const opcionais = cadernosFiltrados.value.filter((c) => !c.obrigatorio);
  const lista = [];
  if (meus.length) lista.push({ chave: '__meus', categoria: 'Meus cadernos', itens: ordenar(meus) });
  if (opcionais.length) lista.push({ chave: '__opcionais', categoria: 'Leituras opcionais', opcional: true, itens: ordenar(opcionais) });
  return lista;
});

const progresso = computed(() => resumirProgresso(cadernos.value));

// Mantém o progresso do menu lateral atualizado
watch(progresso, (p) => { if (!carregando.value) emit('progresso', { lidos: p.lidos, total: p.total }); }, { immediate: true });

// Busca sem diferenciar maiúsculas nem acentos
const semAcento = (t) => String(t || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();

const cadernosFiltrados = computed(() => {
  const termo = semAcento(busca.value);
  if (!termo) return cadernos.value;
  return cadernos.value.filter((c) => [c.titulo, c.categoria, ...(c.tags || [])].some((t) => semAcento(t).includes(termo)));
});

// Assuntos (tags) mais usados nas diretrizes, como atalhos de pesquisa
const tagsDisponiveis = computed(() => {
  const contagem = {};
  cadernos.value.forEach((c) => (c.tags || []).forEach((t) => { contagem[t] = (contagem[t] || 0) + 1; }));
  return Object.keys(contagem).sort((a, b) => contagem[b] - contagem[a] || a.localeCompare(b)).slice(0, 14);
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

// Certificado de conclusão: abre uma página A4 (paisagem) para imprimir ou salvar em PDF
const certModal = ref(null);
function emitirCertificado(c) {
  certModal.value = { html: htmlCertificadoCaderno(c, props.colaborador, logoEmpresa.value), nome: c.titulo };
}

function percentualLido(c) {
  versaoProgresso.value;
  try {
    const p = JSON.parse(localStorage.getItem(`vb_leitura_${c.arquivo}`) || '{}');
    if (p.lidas && p.total) return Math.round((p.lidas / p.total) * 100);
  } catch (e) { /* sem progresso salvo */ }
  return 0;
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
    // Cada tela mostra só o seu tipo: políticas internas ou treinamentos
    cadernos.value = (data.cadernos || []).filter((c) => (c.tipo === 'treinamento') === ehTreinamento);
    const primeira = categorias.value.find((cat) => cadernos.value.some((c) => c.obrigatorio && (c.categoria || 'Outros') === cat));
    if (primeira) categoriaAtiva.value = primeira;
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
