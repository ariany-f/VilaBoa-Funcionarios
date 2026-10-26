<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[70] bg-[#1c1c1e] flex flex-col select-none" role="dialog" aria-modal="true" :aria-label="titulo">
      <!-- Barra superior -->
      <header class="h-14 shrink-0 bg-[#141414] border-b border-white/10 px-2 sm:px-4 flex items-center gap-2 text-white">
        <button type="button" @click="emit('close')" class="w-9 h-9 rounded-lg hover:bg-white/10 flex items-center justify-center cursor-pointer" title="Fechar (Esc)" aria-label="Fechar">
          <AppIcon name="chevron-left" size="18" />
        </button>

        <button
          type="button"
          @click="miniaturasAbertas = !miniaturasAbertas"
          class="hidden md:flex w-9 h-9 rounded-lg hover:bg-white/10 items-center justify-center cursor-pointer"
          :class="miniaturasAbertas ? 'bg-white/10' : ''"
          title="Miniaturas das páginas"
          aria-label="Miniaturas das páginas"
        >
          <AppIcon name="menu" size="16" />
        </button>

        <div class="min-w-0 flex-1">
          <h2 class="text-sm font-semibold truncate">{{ titulo }}</h2>
          <p class="text-[11px] text-white/50 truncate">
            <template v-if="totalPaginas">
              {{ totalPaginas }} {{ totalPaginas === 1 ? 'página' : 'páginas' }} · {{ maiorPaginaVista }} {{ maiorPaginaVista === 1 ? 'lida' : 'lidas' }}
              <template v-if="leitura"> · leitura confirmada em {{ leitura.data_hora }}</template>
            </template>
          </p>
        </div>

        <!-- Navegação de páginas -->
        <div v-if="totalPaginas" class="hidden sm:flex items-center gap-1 text-xs">
          <button type="button" @click="irParaPagina(paginaAtual - 1)" :disabled="paginaAtual <= 1" class="w-8 h-8 rounded-lg hover:bg-white/10 disabled:opacity-30 flex items-center justify-center cursor-pointer" aria-label="Página anterior">
            <AppIcon name="chevron-up" size="16" />
          </button>
          <input
            :value="paginaAtual"
            @change="irParaPagina(+$event.target.value)"
            type="number"
            min="1"
            :max="totalPaginas"
            class="w-11 h-8 rounded-md bg-white/10 text-center text-white outline-none focus:bg-white/20 [appearance:textfield]"
            aria-label="Página atual"
          />
          <span class="text-white/60 px-1">de {{ totalPaginas }}</span>
          <button type="button" @click="irParaPagina(paginaAtual + 1)" :disabled="paginaAtual >= totalPaginas" class="w-8 h-8 rounded-lg hover:bg-white/10 disabled:opacity-30 flex items-center justify-center cursor-pointer" aria-label="Próxima página">
            <AppIcon name="chevron-down" size="16" />
          </button>
        </div>

        <!-- Zoom -->
        <div class="hidden md:flex items-center gap-1 text-xs border-l border-white/10 pl-2 ml-1">
          <button type="button" @click="alterarZoom(-0.25)" class="w-8 h-8 rounded-lg hover:bg-white/10 flex items-center justify-center cursor-pointer text-lg leading-none" aria-label="Diminuir zoom">−</button>
          <button type="button" @click="zoom = 1" class="h-8 px-2 rounded-lg hover:bg-white/10 cursor-pointer tabular-nums" title="Ajustar à largura">{{ Math.round(zoom * 100) }}%</button>
          <button type="button" @click="alterarZoom(0.25)" class="w-8 h-8 rounded-lg hover:bg-white/10 flex items-center justify-center cursor-pointer text-lg leading-none" aria-label="Aumentar zoom">+</button>
        </div>

        <button type="button" @click="baixar" class="w-9 h-9 rounded-lg hover:bg-white/10 flex items-center justify-center cursor-pointer" title="Baixar PDF" aria-label="Baixar PDF">
          <AppIcon name="download" size="17" />
        </button>
      </header>

      <!-- Progresso de leitura -->
      <div class="h-1 bg-white/10 shrink-0">
        <div class="h-full bg-[#9c2337] transition-all duration-300" :style="{ width: progresso + '%' }"></div>
      </div>

      <div class="flex-1 min-h-0 flex">
        <!-- Miniaturas (desktop) -->
        <aside v-if="miniaturasAbertas && totalPaginas" class="hidden md:block w-44 shrink-0 bg-[#141414] border-r border-white/10 overflow-y-auto p-3 space-y-3">
          <button
            v-for="n in totalPaginas"
            :key="n"
            type="button"
            @click="irParaPagina(n)"
            class="block w-full cursor-pointer group"
          >
            <span class="block rounded-md overflow-hidden ring-2 transition" :class="n === paginaAtual ? 'ring-[#9c2337]' : 'ring-transparent group-hover:ring-white/30'">
              <canvas :ref="el => (miniaturas[n] = el)" class="w-full block bg-white"></canvas>
            </span>
            <span class="flex items-center justify-center gap-1 text-[11px] mt-1" :class="n === paginaAtual ? 'text-white' : 'text-white/50'">
              {{ n }}
              <AppIcon v-if="n <= maiorPaginaVista" name="check" size="11" class="text-white/70" />
            </span>
          </button>
        </aside>

        <!-- Páginas -->
        <main ref="area" class="relative flex-1 min-w-0 overflow-auto" @scroll.passive="aoRolar">
          <div v-if="carregando" class="h-full flex flex-col items-center justify-center text-white/60 text-sm gap-3">
            <span class="w-8 h-8 border-2 border-white/20 border-t-white rounded-full animate-spin"></span>
            Abrindo documento...
          </div>
          <div v-else-if="erro" class="h-full flex items-center justify-center text-sm text-rose-300 px-6 text-center">{{ erro }}</div>

          <div v-else class="py-4 sm:py-6 px-2 sm:px-6 flex flex-col items-center gap-4 sm:gap-6" :style="{ minWidth: zoom > 1 ? larguraPagina + 'px' : null }">
            <div
              v-for="n in totalPaginas"
              :key="n"
              :data-pagina="n"
              :ref="el => (paginas[n] = el)"
              class="bg-white shadow-2xl relative shrink-0"
              :style="{ width: larguraPagina + 'px', height: alturas[n] ? alturas[n] + 'px' : larguraPagina * 1.414 + 'px' }"
            >
              <canvas :ref="el => (canvases[n] = el)" class="block w-full h-full"></canvas>
            </div>
          </div>
        </main>
      </div>

      <!-- Indicador de página -->
      <div v-if="totalPaginas && !carregando" class="pointer-events-none fixed left-1/2 -translate-x-1/2 bottom-24 sm:bottom-20 z-[71] px-3 py-1.5 rounded-full bg-black/70 text-white text-xs font-medium tabular-nums">
        {{ paginaAtual }} / {{ totalPaginas }}
      </div>

      <!-- Rodapé: confirmação de leitura -->
      <footer v-if="!carregando && !erro" class="shrink-0 bg-[#141414] border-t border-white/10 px-4 py-3 safe-bottom">
        <div class="max-w-3xl mx-auto flex flex-col sm:flex-row items-center gap-3">
          <template v-if="leitura">
            <span class="flex-1 text-xs text-white/70 flex items-center gap-2">
              <AppIcon name="shield-check" size="16" class="text-white/70 shrink-0" />
              Você confirmou a leitura em {{ leitura.data_hora }}.
            </span>
            <button type="button" @click="emit('close')" class="h-10 px-5 rounded-full bg-white/10 hover:bg-white/20 text-white text-sm font-semibold cursor-pointer">Fechar</button>
          </template>
          <template v-else>
            <span class="flex-1 text-xs text-white/70 text-center sm:text-left">
              <template v-if="chegouAoFim">Registrando sua leitura...</template>
              <template v-else>{{ maiorPaginaVista }} de {{ totalPaginas }} páginas lidas · ao chegar na última página, a leitura fica registrada.</template>
            </span>
            <button
              type="button"
              @click="confirmar"
              :disabled="!chegouAoFim || confirmando"
              class="h-10 px-5 rounded-full bg-[#9c2337] hover:bg-[#831b2d] disabled:bg-white/10 disabled:text-white/40 text-white text-sm font-semibold transition cursor-pointer disabled:cursor-not-allowed w-full sm:w-auto"
            >
              {{ confirmando ? 'Registrando...' : (erroConfirmacao ? 'Tentar de novo' : 'Li e estou ciente') }}
            </button>
          </template>
        </div>
        <p v-if="erroConfirmacao" class="text-xs text-rose-300 text-center mt-2">{{ erroConfirmacao }}</p>
      </footer>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted, markRaw } from 'vue';
import * as pdfjsLib from 'pdfjs-dist';
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.js?url';
import AppIcon from './AppIcon.vue';
import { usarFecharComEsc } from '../utils/fecharComEsc.js';

pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;

const props = defineProps({
  titulo: { type: String, required: true },
  arquivo: { type: String, required: true },
  blob: { type: Blob, required: true },
  leituraInicial: { type: Object, default: null }
});

const emit = defineEmits(['close', 'confirmado']);
usarFecharComEsc(() => emit('close'));

const area = ref(null);
const carregando = ref(true);
const erro = ref('');
const totalPaginas = ref(0);
const paginaAtual = ref(1);
const maiorPaginaVista = ref(1);
const zoom = ref(1);
const larguraBase = ref(800);
const alturas = ref({});
const miniaturasAbertas = ref(true);
const leitura = ref(props.leituraInicial);
const confirmando = ref(false);
const erroConfirmacao = ref('');

const paginas = {};
const canvases = {};
const miniaturas = {};
let pdf = null;
let proporcoes = {};
let renderizadas = new Map(); // página -> largura renderizada
let observador = null;

// Progresso guardado no aparelho: retoma de onde parou e mantém as páginas já lidas
const chaveProgresso = `vb_leitura_${props.arquivo}`;
function lerProgresso() {
  try { return JSON.parse(localStorage.getItem(chaveProgresso)) || {}; } catch (e) { return {}; }
}
function salvarProgresso() {
  try {
    localStorage.setItem(chaveProgresso, JSON.stringify({ lidas: maiorPaginaVista.value, ultima: paginaAtual.value, total: totalPaginas.value }));
  } catch (e) { /* armazenamento indisponível */ }
}
watch([maiorPaginaVista, paginaAtual], salvarProgresso);

const larguraPagina = computed(() => Math.round(larguraBase.value * zoom.value));
const chegouAoFim = computed(() => totalPaginas.value > 0 && maiorPaginaVista.value >= totalPaginas.value);
const progresso = computed(() => (totalPaginas.value ? Math.round((maiorPaginaVista.value / totalPaginas.value) * 100) : 0));

// Chegar à última página já registra a leitura, sem precisar apertar botão
watch(chegouAoFim, (fim) => {
  if (fim && !leitura.value) confirmar();
});

function calcularLarguraBase() {
  const disponivel = (area.value?.clientWidth || window.innerWidth) - (window.innerWidth < 640 ? 16 : 48);
  larguraBase.value = Math.max(280, Math.min(900, disponivel));
}

function atualizarAlturas() {
  const novas = {};
  for (let n = 1; n <= totalPaginas.value; n++) {
    novas[n] = Math.round(larguraPagina.value * (proporcoes[n] || 1.414));
  }
  alturas.value = novas;
}

async function renderizarPagina(n) {
  const largura = larguraPagina.value;
  if (!pdf || renderizadas.get(n) === largura || !canvases[n]) return;
  renderizadas.set(n, largura);
  const pagina = await pdf.getPage(n);
  const viewportBase = pagina.getViewport({ scale: 1 });
  const escala = (largura / viewportBase.width) * Math.min(window.devicePixelRatio || 1, 2);
  const viewport = pagina.getViewport({ scale: escala });
  const canvas = canvases[n];
  canvas.width = viewport.width;
  canvas.height = viewport.height;
  await pagina.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
}

async function renderizarMiniatura(n) {
  const canvas = miniaturas[n];
  if (!pdf || !canvas || canvas.dataset.ok) return;
  canvas.dataset.ok = '1';
  const pagina = await pdf.getPage(n);
  const viewport = pagina.getViewport({ scale: 140 / pagina.getViewport({ scale: 1 }).width });
  canvas.width = viewport.width;
  canvas.height = viewport.height;
  await pagina.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
}

function observarPaginas() {
  observador?.disconnect();
  observador = new IntersectionObserver((entradas) => {
    entradas.forEach((e) => {
      if (e.isIntersecting) renderizarPagina(+e.target.dataset.pagina);
    });
  }, { root: area.value, rootMargin: '600px 0px' });
  Object.values(paginas).forEach((el) => el && observador.observe(el));
}

function aoRolar() {
  const el = area.value;
  if (!el) return;
  const meio = el.scrollTop + el.clientHeight / 2;
  for (let n = 1; n <= totalPaginas.value; n++) {
    const p = paginas[n];
    if (p && p.offsetTop <= meio && p.offsetTop + p.offsetHeight >= meio) {
      paginaAtual.value = n;
      break;
    }
  }
  // A página conta como lida quando vira a página atual; chegar ao fim do documento conta todas
  maiorPaginaVista.value = Math.max(maiorPaginaVista.value, paginaAtual.value);
  if (el.scrollTop + el.clientHeight >= el.scrollHeight - 24) {
    maiorPaginaVista.value = totalPaginas.value;
  }
}

function irParaPagina(n) {
  if (!n || n < 1 || n > totalPaginas.value) return;
  paginas[n]?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function alterarZoom(delta) {
  zoom.value = Math.min(3, Math.max(0.5, +(zoom.value + delta).toFixed(2)));
}

function baixar() {
  const url = URL.createObjectURL(props.blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = props.arquivo;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

async function confirmar() {
  if (!chegouAoFim.value || confirmando.value) return;
  confirmando.value = true;
  erroConfirmacao.value = '';
  try {
    const res = await fetch('/api/cadernos/confirmar-leitura', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${localStorage.getItem('vb_auth_token') || ''}` },
      body: JSON.stringify({ arquivo: props.arquivo })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.erro);
    leitura.value = data.leitura;
    emit('confirmado', data.leitura);
  } catch (e) {
    erroConfirmacao.value = e.message || 'Não foi possível registrar a leitura.';
  } finally {
    confirmando.value = false;
  }
}

// Setas: se o fim da página atual já está na tela, vai para a próxima página;
// senão, rola dentro da página (o mesmo vale para cima, com o início da página).
function avancar() {
  const el = area.value;
  const p = paginas[paginaAtual.value];
  if (!el || !p) return;
  const fimVisivel = p.offsetTop + p.offsetHeight <= el.scrollTop + el.clientHeight + 4;
  if (fimVisivel && paginaAtual.value < totalPaginas.value) irParaPagina(paginaAtual.value + 1);
  else el.scrollBy({ top: el.clientHeight * 0.8, behavior: 'smooth' });
}

function voltar() {
  const el = area.value;
  const p = paginas[paginaAtual.value];
  if (!el || !p) return;
  const inicioVisivel = p.offsetTop >= el.scrollTop - 4;
  if (inicioVisivel && paginaAtual.value > 1) irParaPagina(paginaAtual.value - 1);
  else el.scrollBy({ top: -el.clientHeight * 0.8, behavior: 'smooth' });
}

function teclas(e) {
  if (e.target.tagName === 'INPUT') return;
  if (['ArrowRight', 'ArrowDown', 'PageDown', ' '].includes(e.key)) { avancar(); e.preventDefault(); }
  if (['ArrowLeft', 'ArrowUp', 'PageUp'].includes(e.key)) { voltar(); e.preventDefault(); }
  if ((e.ctrlKey || e.metaKey) && (e.key === '+' || e.key === '=')) { alterarZoom(0.25); e.preventDefault(); }
  if ((e.ctrlKey || e.metaKey) && e.key === '-') { alterarZoom(-0.25); e.preventDefault(); }
}

watch(larguraPagina, async () => {
  atualizarAlturas();
  await nextTick();
  observarPaginas();
});

watch(miniaturasAbertas, async (aberto) => {
  await nextTick();
  calcularLarguraBase();
  if (aberto) for (let n = 1; n <= totalPaginas.value; n++) renderizarMiniatura(n);
});

function aoRedimensionar() {
  calcularLarguraBase();
}

onMounted(async () => {
  document.body.style.overflow = 'hidden';
  window.addEventListener('keydown', teclas);
  window.addEventListener('resize', aoRedimensionar);
  if (window.innerWidth < 1024) miniaturasAbertas.value = false;
  try {
    pdf = markRaw(await pdfjsLib.getDocument({ data: new Uint8Array(await props.blob.arrayBuffer()) }).promise);
    totalPaginas.value = pdf.numPages;
    for (let n = 1; n <= pdf.numPages; n++) {
      const vp = (await pdf.getPage(n)).getViewport({ scale: 1 });
      proporcoes[n] = vp.height / vp.width;
    }
    carregando.value = false;
    await nextTick();
    calcularLarguraBase();
    atualizarAlturas();
    await nextTick();
    observarPaginas();
    const salvo = lerProgresso();
    if (salvo.lidas) maiorPaginaVista.value = Math.min(salvo.lidas, totalPaginas.value);
    if (leitura.value) maiorPaginaVista.value = totalPaginas.value;
    if (salvo.ultima > 1) paginas[Math.min(salvo.ultima, totalPaginas.value)]?.scrollIntoView({ block: 'start' });
    aoRolar();
    if (miniaturasAbertas.value) for (let n = 1; n <= totalPaginas.value; n++) renderizarMiniatura(n);
  } catch (e) {
    carregando.value = false;
    erro.value = 'Não foi possível abrir este PDF. Use o botão de download para abri-lo no seu aparelho.';
  }
});

onUnmounted(() => {
  document.body.style.overflow = '';
  window.removeEventListener('keydown', teclas);
  window.removeEventListener('resize', aoRedimensionar);
  observador?.disconnect();
  pdf?.destroy();
});
</script>
