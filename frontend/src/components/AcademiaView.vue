<template>
  <div class="space-y-6 animate-slide-up">
    <div class="sm:bg-white sm:rounded-2xl sm:border border-slate-200 sm:p-6 space-y-4">
      <div class="space-y-0.5">
        <h2 class="text-lg font-semibold text-slate-900 tracking-tight">Academia Vila Boa</h2>
        <p class="text-xs text-slate-500">Aprenda no seu ritmo: trilhas obrigatórias, trilhas da sua função, cadernos e vídeos. Ao concluir uma trilha, você emite o certificado.</p>
      </div>

      <!-- CONQUISTAS: visão geral da Academia (só o progresso pessoal, sem comparação entre colaboradores) -->
      <div v-if="!carregando && trilhas.length" class="rounded-2xl border border-slate-200 bg-white p-3.5 space-y-3">
        <div class="flex flex-wrap items-center gap-3">
          <span class="w-10 h-10 rounded-full bg-[#9c2337] text-white flex items-center justify-center shrink-0 ring-4 ring-[#9c2337]/10"><AppIcon name="graduation-cap" size="18" /></span>
          <div class="min-w-0 flex-1 basis-48 space-y-1">
            <p class="text-sm font-semibold text-slate-900 leading-tight">{{ nivel.atual.nome }} <span class="text-xs font-normal text-slate-400">· {{ pontos }} pontos</span></p>
            <div v-if="nivel.proximo" class="flex items-center gap-2">
              <span class="flex-1 h-1.5 rounded-full bg-slate-200 overflow-hidden"><span class="block h-full rounded-full bg-[#9c2337] transition-all duration-700" :style="{ width: nivel.percentual + '%' }"></span></span>
              <span class="text-[10px] text-slate-400 shrink-0">faltam {{ nivel.proximo.min - pontos }} p/ {{ nivel.proximo.nome }}</span>
            </div>
            <p v-else class="text-[11px] text-emerald-600 font-semibold">Nível máximo. Parabéns!</p>
          </div>
          <ul class="flex items-center gap-1">
            <li v-for="c in conquistas" :key="c.id" class="w-8 h-8 rounded-full flex items-center justify-center" :class="c.ganha ? 'bg-[#9c2337] text-white' : 'bg-slate-100 text-slate-300'" :title="c.titulo + (c.ganha ? '' : ' (bloqueada)')">
              <AppIcon :name="c.ganha ? c.icone : 'lock'" size="14" />
            </li>
          </ul>
          <button type="button" @click="mostrarConquistas = !mostrarConquistas" class="h-8 px-3 rounded-full border border-slate-200 text-[11px] font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer shrink-0">
            {{ conquistas.filter((c) => c.ganha).length }}/{{ conquistas.length }} conquistas {{ mostrarConquistas ? '▴' : '▾' }}
          </button>
        </div>
        <div v-if="mostrarConquistas" class="space-y-2 border-t border-slate-100 pt-3">
          <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
            <li v-for="c in conquistas" :key="c.id" class="rounded-xl border p-2.5 flex items-center gap-2.5" :class="c.ganha ? 'bg-white border-[#9c2337]/30' : 'bg-slate-50 border-slate-200'">
              <span class="w-9 h-9 rounded-full flex items-center justify-center shrink-0" :class="c.ganha ? 'bg-[#9c2337] text-white' : 'bg-slate-200 text-slate-400'"><AppIcon :name="c.ganha ? c.icone : 'lock'" size="15" /></span>
              <span class="min-w-0 flex-1">
                <span class="block text-xs font-semibold truncate" :class="c.ganha ? 'text-slate-900' : 'text-slate-500'">{{ c.titulo }}</span>
                <span class="block text-[10px] text-slate-500 leading-snug">{{ c.descricao }}</span>
                <span v-if="!c.ganha && c.meta > 1" class="block text-[10px] font-semibold text-slate-400">{{ Math.min(c.atual, c.meta) }}/{{ c.meta }}</span>
              </span>
            </li>
          </ul>
          <p class="text-[11px] text-slate-400">Cada etapa concluída vale 10 pontos e cada trilha, 50, a menos que o RH defina outro valor. É só o seu progresso: não há comparação com outros colaboradores.</p>
        </div>
      </div>

      <div class="flex gap-2">
        <button
          v-for="a in abas"
          :key="a.id"
          type="button"
          @click="aba = a.id"
          class="h-9 px-4 rounded-full border text-xs font-semibold transition cursor-pointer"
          :class="aba === a.id ? 'bg-[#141414] border-[#141414] text-white' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300'"
        >{{ a.rotulo }}</button>
      </div>

      <!-- TRILHAS -->
      <template v-if="aba === 'trilhas'">
        <div v-if="carregando" class="py-12 text-center text-sm text-slate-400">Carregando trilhas...</div>
        <div v-else-if="erro" class="py-4 text-center text-sm text-rose-700">{{ erro }}</div>
        <div v-else-if="!trilhas.length" class="py-12 text-center space-y-1">
          <AppIcon name="graduation-cap" size="28" class="mx-auto text-slate-300" />
          <p class="text-sm text-slate-500">Nenhuma trilha disponível para você no momento.</p>
        </div>

        <template v-else>
          <!-- Progresso geral nas trilhas -->
          <div class="rounded-2xl text-white p-3.5 sm:p-4 bg-gradient-to-br transition-colors" :class="progresso.percentual === 100 ? 'from-emerald-700 to-emerald-900' : 'from-[#141414] to-black'">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
              <div class="relative w-16 h-16 shrink-0 self-center">
                <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90" aria-hidden="true">
                  <circle cx="50" cy="50" r="42" fill="none" stroke="rgba(255,255,255,0.12)" stroke-width="9" />
                  <circle cx="50" cy="50" r="42" fill="none" :stroke="progresso.percentual === 100 ? '#6ee7b7' : '#9c2337'" stroke-width="9" stroke-linecap="round" :stroke-dasharray="264" :stroke-dashoffset="264 - (264 * progresso.percentual) / 100" class="transition-all duration-700" />
                </svg>
                <span class="absolute inset-0 flex flex-col items-center justify-center">
                  <span class="text-base font-semibold leading-none">{{ progresso.percentual }}%</span>
                  <span class="text-[9px] text-white/50 mt-0.5">{{ progresso.etapasFeitas }}/{{ progresso.etapas }}</span>
                </span>
              </div>
              <div class="flex-1 min-w-0 space-y-1 text-center sm:text-left">
                <p class="text-base font-semibold leading-snug">{{ progresso.percentual === 100 ? 'Parabéns! Você concluiu todas as suas trilhas.' : 'Continue sua jornada de aprendizado na Vila Boa.' }}</p>
                <p class="text-xs text-white/60">{{ progresso.completas }} de {{ trilhas.length }} {{ trilhas.length === 1 ? 'trilha concluída' : 'trilhas concluídas' }} · {{ progresso.certificados }} {{ progresso.certificados === 1 ? 'certificado emitido' : 'certificados emitidos' }}<template v-if="progresso.obrigatoriasPendentes"> · <strong class="text-white">{{ progresso.obrigatoriasPendentes }} {{ progresso.obrigatoriasPendentes === 1 ? 'obrigatória pendente' : 'obrigatórias pendentes' }}</strong></template></p>
              </div>
              <button v-if="progresso.proxima" type="button" @click="irParaTrilha(progresso.proxima)" class="h-10 px-5 rounded-full bg-[#9c2337] hover:bg-[#831b2d] text-white text-sm font-semibold shrink-0 cursor-pointer">
                Continuar: {{ progresso.proxima.titulo.length > 26 ? progresso.proxima.titulo.slice(0, 26) + '…' : progresso.proxima.titulo }}
              </button>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <button v-for="f in filtrosStatus" :key="f.id" type="button" @click="filtroStatus = f.id" class="h-8 px-3.5 rounded-full border text-xs font-semibold transition cursor-pointer"
              :class="filtroStatus === f.id ? 'bg-[#9c2337] border-[#9c2337] text-white' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300'">{{ f.rotulo }} <span class="opacity-60">{{ contaStatus(f.id) }}</span></button>
            <input v-model="busca" type="search" placeholder="Buscar trilha…" class="ml-auto w-full sm:w-56 h-8 px-3 rounded-full border border-slate-200 bg-slate-50 text-xs outline-none focus:bg-white focus:border-slate-400" />
          </div>
          <p v-if="!grupos.length" class="py-8 text-center text-sm text-slate-400">Nenhuma trilha encontrada com esse filtro.</p>

          <section v-for="grupo in grupos" :key="grupo.titulo" class="space-y-3">
            <div>
              <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800">{{ grupo.titulo }}</h3>
              <p class="text-xs text-slate-500">{{ grupo.descricao }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <article
                v-for="t in grupo.itens"
                :key="t.id"
                :id="'trilha-' + t.id"
                class="rounded-2xl border bg-white overflow-hidden scroll-mt-4"
                :class="t.obrigatoria && !t.completa ? 'border-[#9c2337]' : 'border-slate-200'"
              >
                <div v-if="urlCapaTrilha(t)" class="h-32 sm:h-36 bg-slate-100"><img :src="urlCapaTrilha(t)" alt="" class="w-full h-full object-cover" loading="lazy" /></div>
                <div class="p-5 space-y-4">
                <div class="space-y-1.5">
                  <div class="flex flex-wrap items-center gap-2">
                    <span v-if="t.obrigatoria" class="text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full" :class="t.completa ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-[#fdf2f4] text-[#9c2337] border border-rose-200'">
                      {{ t.completa ? 'Obrigatória · concluída' : 'Obrigatória' }}
                    </span>
                    <span v-else-if="t.completa" class="text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">Concluída</span>
                    <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full" :class="{ 'bg-slate-100 text-slate-600': statusDe(t) === 'nova', 'bg-amber-50 text-amber-800 border border-amber-200': statusDe(t) === 'andamento', 'bg-emerald-50 text-emerald-800 border border-emerald-200': statusDe(t) === 'concluida' }">{{ rotuloStatus[statusDe(t)] }}</span>
                    <span v-if="t.carga_horaria" class="text-[11px] text-slate-400">{{ t.carga_horaria }}</span>
                    <span v-if="prazoInfo(t)" class="text-[11px] font-semibold px-2.5 py-1 rounded-full border" :class="prazoInfo(t).classe">{{ prazoInfo(t).texto }}</span>
                    <span v-if="!t.completa && minutosRestantes(t)" class="text-[11px] text-slate-400">· faltam ~{{ minutosRestantes(t) }} min de vídeo</span>
                  </div>
                  <h4 class="text-lg font-semibold text-slate-900 leading-snug">{{ t.titulo }}</h4>
                  <p v-if="t.descricao" class="text-sm text-slate-500">{{ t.descricao }}</p>
                </div>

                <div class="space-y-1.5">
                  <div class="flex items-center justify-between text-xs text-slate-500">
                    <span>{{ t.concluidos }} de {{ t.total }} {{ t.total === 1 ? 'etapa' : 'etapas' }}</span>
                    <span class="font-semibold" :class="t.completa ? 'text-emerald-600' : 'text-slate-600'">{{ Math.round((t.concluidos / t.total) * 100) }}%</span>
                  </div>
                  <div class="h-2 rounded-full bg-slate-200 overflow-hidden">
                    <div class="h-full rounded-full transition-all" :class="t.completa ? 'bg-emerald-500' : 'bg-[#9c2337]'" :style="{ width: Math.round((t.concluidos / t.total) * 100) + '%' }"></div>
                  </div>
                </div>

                <button v-if="proximoItem(t)" type="button" @click="abrirItem(t, proximoItem(t))" class="w-full h-10 rounded-xl bg-[#9c2337] hover:bg-[#831b2d] text-white text-sm font-semibold cursor-pointer truncate px-3">
                  {{ t.concluidos ? 'Continuar' : 'Começar' }}: {{ proximoItem(t).titulo }}
                </button>
                <button type="button" @click="alternarEtapas(t)" class="w-full flex items-center justify-between text-xs font-semibold text-slate-500 hover:text-slate-800 cursor-pointer border-t border-slate-100 pt-3">
                  <span>{{ etapasAbertas(t) ? 'Ocultar etapas' : 'Ver as ' + t.total + ' etapas' }}</span>
                  <AppIcon :name="etapasAbertas(t) ? 'chevron-up' : 'chevron-down'" size="14" />
                </button>
                <ol v-show="etapasAbertas(t)" class="divide-y divide-slate-100">
                  <li v-for="(i, n) in t.itens" :key="i.id" class="py-2.5 flex items-center gap-3 rounded-lg" :class="proximoItem(t) === i ? 'bg-[#fdf2f4]/60 -mx-2 px-2' : ''">
                    <span class="w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center shrink-0" :class="i.concluido_em ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-500'">{{ i.concluido_em ? '✓' : n + 1 }}</span>
                    <span class="min-w-0 flex-1">
                      <span class="block text-sm font-medium text-slate-800 truncate">{{ i.titulo }}<span v-if="proximoItem(t) === i" class="ml-2 text-[10px] font-bold uppercase tracking-wider text-[#9c2337]">Próxima</span></span>
                      <span class="block text-xs text-slate-400">
                        {{ i.tipo === 'video' ? 'Vídeo' + (i.duracao ? ' · ' + i.duracao : '') : 'Caderno' + (i.paginas ? ' · ' + i.paginas + ' páginas' : '') }}
                        <template v-if="i.concluido_em"> · concluído em {{ i.concluido_em.slice(0, 10) }}</template>
                      </span>
                      <span v-if="i.descricao" class="block text-xs text-slate-500 mt-0.5 leading-snug">{{ i.descricao }}</span>
                    </span>
                    <button
                      type="button"
                      @click="abrirItem(t, i)"
                      :disabled="abrindo === t.id + i.id"
                      class="h-8 px-3.5 rounded-full text-xs font-semibold cursor-pointer shrink-0 disabled:opacity-60"
                      :class="i.concluido_em ? 'border border-slate-300 text-slate-600 hover:bg-slate-50' : 'bg-[#9c2337] hover:bg-[#831b2d] text-white'"
                    >{{ i.concluido_em ? 'Rever' : (i.tipo === 'video' ? 'Assistir' : 'Ler') }}</button>
                  </li>
                </ol>

                <div v-if="t.completa" class="pt-1">
                  <button v-if="t.certificado" type="button" @click="imprimirCertificado(t.certificado)" class="w-full h-11 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold cursor-pointer">Ver certificado</button>
                  <button v-else type="button" @click="emitir(t)" :disabled="emitindo === t.id" class="w-full h-11 rounded-xl bg-[#141414] hover:bg-black disabled:opacity-60 text-white text-sm font-semibold cursor-pointer">
                    {{ emitindo === t.id ? 'Emitindo...' : 'Assinar e emitir certificado' }}
                  </button>
                  <p v-if="!t.certificado" class="text-[11px] text-slate-400 mt-1.5 leading-snug">Ao emitir, você assina digitalmente o certificado e a empresa o assina em seguida.</p>
                </div>
                </div>
              </article>
            </div>
          </section>
        </template>
      </template>

      <!-- MEUS CERTIFICADOS -->
      <template v-else-if="aba === 'certificados'">
        <div v-if="carregandoCertificados" class="py-12 text-center text-sm text-slate-400">Carregando certificados...</div>
        <div v-else-if="!certificados.length" class="py-12 text-center space-y-1">
          <AppIcon name="shield-check" size="28" class="mx-auto text-slate-300" />
          <p class="text-sm text-slate-500">Você ainda não tem certificados.</p>
          <p class="text-xs text-slate-400">Conclua uma trilha ou um caderno da Academia para emitir o seu.</p>
        </div>
        <div v-else class="space-y-3">
          <p class="text-xs text-slate-500">{{ certificados.length }} {{ certificados.length === 1 ? 'certificado' : 'certificados' }} · abra para imprimir ou salvar em PDF.</p>
          <ul class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2.5">
            <li v-for="c in certificados" :key="c.chave" class="rounded-xl border border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm transition p-3 flex items-center gap-3">
              <span class="w-10 h-10 rounded-full bg-[#9c2337] text-white flex items-center justify-center shrink-0 ring-4 ring-[#9c2337]/10"><AppIcon name="graduation-cap" size="18" /></span>
              <span class="min-w-0 flex-1">
                <span class="block text-sm font-semibold text-slate-900 truncate" :title="c.titulo">{{ c.titulo }}</span>
                <span class="flex items-center gap-1.5 text-[11px] text-slate-400">
                  <span class="font-bold uppercase tracking-wider" :class="c.tipo === 'Trilha' ? 'text-[#9c2337]' : 'text-slate-600'">{{ c.tipo }}</span>
                  · {{ c.data.slice(0, 10) }}
                  <AppIcon name="shield-check" size="12" class="text-emerald-600" title="Assinado digitalmente" />
                </span>
              </span>
              <button type="button" @click="c.abrir()" class="h-8 w-8 rounded-full bg-[#141414] hover:bg-black text-white flex items-center justify-center cursor-pointer shrink-0" title="Abrir / imprimir" aria-label="Abrir / imprimir"><AppIcon name="printer" size="14" /></button>
            </li>
          </ul>
        </div>
        <p v-if="erro" class="text-sm text-rose-700">{{ erro }}</p>
      </template>

      <!-- CADERNOS E TREINAMENTOS AVULSOS -->
      <CadernosView v-else tipo="treinamento" :colaborador="colaborador" />
    </div>

    <CertificadoModal v-if="certModal" :html="certModal.html" :nome="certModal.nome" :validacao="certModal.validacao" @fechar="certModal = null" />

    <!-- Player de vídeo -->
    <Teleport to="body">
      <div v-if="video" class="fixed inset-0 z-[65] flex items-center justify-center p-0 sm:p-6 bg-black/80" role="dialog" aria-modal="true">
        <div class="bg-white w-full max-w-3xl sm:rounded-2xl overflow-hidden flex flex-col max-h-full">
          <div class="px-5 py-3 flex items-center justify-between gap-3 border-b border-slate-100">
            <h4 class="text-sm font-semibold text-slate-900 truncate">{{ video.item.titulo }}</h4>
            <button type="button" @click="video = null" class="w-9 h-9 rounded-full hover:bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer" aria-label="Fechar">
              <AppIcon name="x" size="16" />
            </button>
          </div>
          <div class="bg-black aspect-video">
            <iframe v-if="video.embed" :src="video.embed" class="w-full h-full" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen title="Vídeo da trilha"></iframe>
            <video v-else :src="video.item.url" class="w-full h-full" controls autoplay playsinline @ended="concluirVideo()"></video>
          </div>
          <div class="px-5 py-4 flex items-center justify-between gap-3">
            <p class="text-xs text-slate-500">{{ video.item.concluido_em ? 'Você já concluiu este vídeo.' : 'Assista até o final e confirme a conclusão.' }}</p>
            <button v-if="!video.item.concluido_em" type="button" @click="concluirVideo()" class="h-10 px-5 rounded-full bg-[#9c2337] hover:bg-[#831b2d] text-white text-sm font-semibold cursor-pointer shrink-0">Concluir vídeo</button>
          </div>
        </div>
      </div>
    </Teleport>

    <PdfReader
      v-if="leitor"
      :titulo="leitor.item.titulo"
      :arquivo="leitor.item.arquivo"
      :blob="leitor.blob"
      :leituraInicial="leitor.item.concluido_em ? { data_hora: leitor.item.concluido_em } : null"
      @confirmado="aoConfirmarLeitura"
      @close="leitor = null"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, defineAsyncComponent } from 'vue';
import AppIcon from './AppIcon.vue';
import CadernosView from './CadernosView.vue';
import { apiFetch } from '../utils/api.js';
import { logoEmpresa } from '../utils/logoEmpresa.js';
import { urlCapaTrilha } from '../utils/capas.js';
import { htmlCertificadoTrilha, htmlCertificadoCaderno, urlValidacaoCertificado } from '../utils/certificados.js';
import CertificadoModal from './CertificadoModal.vue';

const PdfReader = defineAsyncComponent(() => import('./PdfReader.vue'));
const emit = defineEmits(['atualizou']);
const props = defineProps({ colaborador: { type: Object, default: null } });

const abas = [{ id: 'trilhas', rotulo: 'Trilhas' }, { id: 'avulsos', rotulo: 'Cadernos e treinamentos' }, { id: 'certificados', rotulo: 'Meus certificados' }];
const aba = ref('trilhas');
const trilhas = ref([]);
const carregando = ref(true);
const erro = ref('');
const abrindo = ref('');
const emitindo = ref('');
const video = ref(null);
const leitor = ref(null);

// Status de cada trilha, busca e filtros (estilo LMS)
const statusDe = (t) => (t.completa ? 'concluida' : t.concluidos > 0 ? 'andamento' : 'nova');
const rotuloStatus = { nova: 'Não iniciada', andamento: 'Em andamento', concluida: 'Concluída' };
const filtroStatus = ref('todas');
const busca = ref('');
const filtrosStatus = [{ id: 'todas', rotulo: 'Todas' }, { id: 'andamento', rotulo: 'Em andamento' }, { id: 'nova', rotulo: 'Não iniciadas' }, { id: 'concluida', rotulo: 'Concluídas' }];
const contaStatus = (id) => trilhas.value.filter((t) => id === 'todas' || statusDe(t) === id).length;
const trilhasVisiveis = computed(() => {
  const q = busca.value.trim().toLowerCase();
  return trilhas.value.filter((t) => (filtroStatus.value === 'todas' || statusDe(t) === filtroStatus.value)
    && (!q || t.titulo.toLowerCase().includes(q) || (t.descricao || '').toLowerCase().includes(q)));
});
const proximoItem = (t) => t.itens.find((i) => !i.concluido_em) || null;
const expandida = ref({});
const etapasAbertas = (t) => expandida.value[t.id] ?? !t.completa;
const alternarEtapas = (t) => { expandida.value[t.id] = !etapasAbertas(t); };
function minutosRestantes(t) {
  let total = 0;
  for (const i of t.itens) {
    if (i.concluido_em || i.tipo !== 'video') continue;
    const m = (i.duracao || '').toLowerCase();
    const h = m.match(/(\d+)\s*h/);
    const mi = m.match(/(\d+)\s*m/);
    total += (h ? +h[1] * 60 : 0) + (mi ? +mi[1] : (!h && /^\d+$/.test(m.trim()) ? +m : 0));
  }
  return total;
}

const grupos = computed(() => [
  { titulo: 'Trilhas obrigatórias', descricao: 'Valem para todos os colaboradores e precisam ser concluídas.', itens: trilhasVisiveis.value.filter((t) => t.obrigatoria) },
  { titulo: 'Trilhas da sua função', descricao: 'Selecionadas para o seu cargo.', itens: trilhasVisiveis.value.filter((t) => !t.obrigatoria) }
].filter((g) => g.itens.length));

const auth = () => ({ Authorization: `Bearer ${localStorage.getItem('vb_auth_token') || ''}` });
const json = (corpo) => ({ method: 'POST', headers: { 'Content-Type': 'application/json', ...auth() }, body: JSON.stringify(corpo) });

async function carregar() {
  try {
    const res = await apiFetch('api/academia', { headers: auth() });
    const data = await res.json();
    if (!res.ok) throw new Error();
    trilhas.value = data.trilhas || [];
    erro.value = '';
  } catch (e) {
    erro.value = 'Não foi possível carregar as trilhas.';
  } finally {
    carregando.value = false;
    emit('atualizou');
  }
}

// Link do YouTube/Vimeo vira player embutido; arquivo de vídeo (mp4...) usa o player do navegador
function urlEmbed(url) {
  const yt = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{6,})/i);
  if (yt) return `https://www.youtube-nocookie.com/embed/${yt[1]}?rel=0`;
  const vm = url.match(/vimeo\.com\/(?:video\/)?(\d+)/i);
  if (vm) return `https://player.vimeo.com/video/${vm[1]}`;
  return /\.(mp4|webm|ogg)(\?|$)/i.test(url) ? '' : url;
}

async function abrirItem(trilha, item) {
  if (item.tipo === 'video') {
    video.value = { trilha, item, embed: urlEmbed(item.url) };
    return;
  }
  abrindo.value = trilha.id + item.id;
  erro.value = '';
  try {
    const res = await apiFetch(`api/cadernos/arquivo?nome=${encodeURIComponent(item.arquivo)}`, { headers: auth() });
    if (!res.ok) throw new Error();
    leitor.value = { trilha, item, blob: await res.blob() };
  } catch (e) {
    erro.value = 'Não foi possível abrir o caderno. Tente novamente.';
  } finally {
    abrindo.value = '';
  }
}

async function concluirVideo() {
  const { trilha, item } = video.value;
  if (!item.concluido_em) {
    const res = await apiFetch('api/academia/video-concluido', json({ trilha: trilha.id, item: item.id }));
    if (!res.ok) {
      erro.value = (await res.json().catch(() => ({}))).erro || 'Não foi possível registrar a conclusão.';
    }
  }
  video.value = null;
  await carregar();
}

async function aoConfirmarLeitura() {
  await carregar();
}

async function emitir(trilha) {
  emitindo.value = trilha.id;
  erro.value = '';
  try {
    const res = await apiFetch('api/academia/certificado', json({ trilha: trilha.id }));
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.erro);
    await carregar().catch(() => {});
    imprimirCertificado(data.certificado);
  } catch (e) {
    erro.value = e.message || 'Não foi possível emitir o certificado.';
  } finally {
    emitindo.value = '';
  }
}

// Progresso geral: etapas concluídas de todas as trilhas do colaborador
// Prazo da trilha (opcional): só aparece enquanto ela não foi concluída
function prazoInfo(t) {
  if (!t.prazo || t.completa) return null;
  const [a, m, d] = t.prazo.split('-').map(Number);
  const hoje = new Date();
  const dias = Math.round((new Date(a, m - 1, d) - new Date(hoje.getFullYear(), hoje.getMonth(), hoje.getDate())) / 86400000);
  const data = `${String(d).padStart(2, '0')}/${String(m).padStart(2, '0')}/${a}`;
  if (dias < 0) return { texto: `Prazo vencido em ${data}`, classe: 'bg-rose-50 text-rose-700 border-rose-200' };
  if (dias === 0) return { texto: 'Prazo termina hoje', classe: 'bg-rose-50 text-rose-700 border-rose-200' };
  if (dias <= 7) return { texto: `Prazo: ${dias} ${dias === 1 ? 'dia' : 'dias'} (${data})`, classe: 'bg-amber-50 text-amber-800 border-amber-200' };
  return { texto: `Prazo: ${data}`, classe: 'bg-slate-50 text-slate-600 border-slate-200' };
}

// Gamificação individual: tudo calculado a partir das trilhas do próprio colaborador
const etapasFeitasPor = (tipo) => trilhas.value.reduce((n, t) => n + t.itens.filter((i) => i.concluido_em && (!tipo || i.tipo === tipo)).length, 0);
// Pontos: o RH pode configurar por etapa e por trilha; sem configuração, todas valem o mesmo (10 por etapa, 50 por trilha)
const pontos = computed(() => trilhas.value.reduce((soma, t) => soma
  + t.itens.filter((i) => i.concluido_em).reduce((n, i) => n + (i.pontos ?? 10), 0)
  + (t.completa ? (t.pontos ?? 50) : 0), 0));
const NIVEIS = [{ nome: 'Iniciante', min: 0 }, { nome: 'Aprendiz', min: 50 }, { nome: 'Dedicado', min: 150 }, { nome: 'Especialista', min: 350 }, { nome: 'Mestre Vila Boa', min: 700 }];
const mostrarConquistas = ref(false);
const nivel = computed(() => {
  const i = NIVEIS.reduce((acc, n, idx) => (pontos.value >= n.min ? idx : acc), 0);
  const atual = NIVEIS[i];
  const proximo = NIVEIS[i + 1] || null;
  return { atual, proximo, percentual: proximo ? Math.round(((pontos.value - atual.min) / (proximo.min - atual.min)) * 100) : 100 };
});
const conquistas = computed(() => {
  const completas = trilhas.value.filter((t) => t.completa);
  const obrig = trilhas.value.filter((t) => t.obrigatoria);
  const defs = [
    { id: 'primeiro', titulo: 'Primeiro passo', descricao: 'Conclua a sua primeira etapa.', icone: 'check', meta: 1, atual: etapasFeitasPor() },
    { id: 'trilha1', titulo: 'Trilha concluída', descricao: 'Conclua uma trilha inteira.', icone: 'graduation-cap', meta: 1, atual: completas.length },
    { id: 'certificado', titulo: 'Certificado na mão', descricao: 'Emita o seu primeiro certificado.', icone: 'shield-check', meta: 1, atual: trilhas.value.filter((t) => t.certificado).length },
    { id: 'emdia', titulo: 'Em dia com a empresa', descricao: 'Conclua todas as trilhas obrigatórias.', icone: 'hard-hat', meta: Math.max(obrig.length, 1), atual: obrig.filter((t) => t.completa).length },
    { id: 'espectador', titulo: 'Maratona de vídeos', descricao: 'Assista a 5 vídeos.', icone: 'eye', meta: 5, atual: etapasFeitasPor('video') },
    { id: 'leitor', titulo: 'Leitor dedicado', descricao: 'Leia 5 cadernos.', icone: 'document', meta: 5, atual: etapasFeitasPor('caderno') },
    { id: 'etapas', titulo: 'Em ritmo', descricao: 'Conclua 15 etapas.', icone: 'calendar', meta: 15, atual: etapasFeitasPor() },
    { id: 'trilhas3', titulo: 'Colecionador', descricao: 'Conclua 3 trilhas.', icone: 'pen-tool', meta: 3, atual: completas.length }
  ];
  return defs.map((c) => ({ ...c, ganha: c.atual >= c.meta && !(c.id === 'emdia' && !obrig.length) }));
});

const progresso = computed(() => {
  const etapas = trilhas.value.reduce((n, t) => n + t.total, 0);
  const etapasFeitas = trilhas.value.reduce((n, t) => n + t.concluidos, 0);
  const pendentes = trilhas.value.filter((t) => !t.completa);
  return {
    etapas,
    etapasFeitas,
    percentual: etapas ? Math.round((etapasFeitas / etapas) * 100) : 0,
    completas: trilhas.value.filter((t) => t.completa).length,
    certificados: trilhas.value.filter((t) => t.certificado).length,
    obrigatoriasPendentes: pendentes.filter((t) => t.obrigatoria).length,
    // Próxima: a obrigatória mais adiantada; sem obrigatória, a mais adiantada das outras
    proxima: [...pendentes].sort((a, b) => (b.obrigatoria - a.obrigatoria) || (b.concluidos / b.total - a.concluidos / a.total))[0] || null
  };
});
function irParaTrilha(t) {
  document.getElementById('trilha-' + t.id)?.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
}

// Certificado em modal (ver, imprimir/salvar em PDF e baixar)
const certModal = ref(null);
function imprimirCertificado(c) {
  certModal.value = { html: htmlCertificadoTrilha(c, logoEmpresa.value), nome: c.trilha, validacao: urlValidacaoCertificado(c.codigo) };
}

// Meus certificados: os de trilha (guardados no servidor) e os de caderno da Academia (leitura concluída)
const certificados = ref([]);
const carregandoCertificados = ref(false);
async function carregarCertificados() {
  carregandoCertificados.value = true;
  try {
    const [rt, rc] = await Promise.all([
      apiFetch('api/academia/certificados', { headers: auth() }),
      apiFetch('api/cadernos', { headers: auth() })
    ]);
    const trilhasCert = rt.ok ? (await rt.json()).certificados || [] : [];
    const cadernos = rc.ok ? (await rc.json()).cadernos || [] : [];
    const lista = [
      ...trilhasCert.map((c) => ({ chave: 't' + c.codigo, tipo: 'Trilha', titulo: c.trilha, data: c.emitido_em, codigo: c.codigo, abrir: () => imprimirCertificado(c) })),
      ...cadernos.filter((c) => c.tipo === 'treinamento' && c.leitura).map((c) => ({
        chave: 'c' + c.arquivo, tipo: 'Caderno', titulo: c.titulo, data: c.leitura.data_hora, codigo: (c.leitura.comprovante || '').slice(0, 20),
        abrir: () => { certModal.value = { html: htmlCertificadoCaderno(c, props.colaborador, logoEmpresa.value), nome: c.titulo, validacao: '' }; }
      }))
    ];
    const iso = (d) => d.replace(/^(\d{2})\/(\d{2})\/(\d{4})/, '$3-$2-$1');
    lista.sort((x, y) => iso(y.data).localeCompare(iso(x.data)));
    certificados.value = lista;
  } finally {
    carregandoCertificados.value = false;
  }
}
watch(aba, (a) => { if (a === 'certificados') carregarCertificados(); });

onMounted(carregar);
</script>
