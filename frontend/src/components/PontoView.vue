<template>
  <div class="space-y-6 animate-slide-up">
    <div v-if="carregando" class="py-16 text-center text-sm text-slate-400">Carregando espelho de ponto...</div>

    <div v-else-if="!espelho" class="bg-white rounded-2xl border border-slate-200 py-16 text-center space-y-1">
      <AppIcon name="calendar" size="28" class="mx-auto text-slate-300" />
      <p class="text-sm text-slate-500">{{ erro || 'Nenhum espelho de ponto disponível no momento.' }}</p>
    </div>

    <template v-else>
      <!-- Banner da competência -->
      <div class="bg-gradient-to-br from-[#141414] to-[#000000] text-white rounded-2xl p-6 sm:p-7 flex flex-col sm:flex-row sm:items-center justify-between gap-5">
        <div class="space-y-2">
          <span
            class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-3 py-1.5 rounded-full bg-white/10 border border-white/15"
          >
            <span class="w-1.5 h-1.5 rounded-full" :class="espelho.assinatura ? 'bg-emerald-400' : 'bg-amber-400'"></span>
            {{ espelho.assinatura ? 'Espelho assinado' : 'Pendente de assinatura' }}
          </span>
          <h2 class="text-2xl sm:text-3xl font-semibold tracking-tight">Espelho de Ponto · {{ espelho.mes_nome }}</h2>
          <p class="text-xs text-white/60">
            Saldo do banco de horas: <strong class="text-white/90 font-medium">{{ espelho.banco_horas?.saldo_total }}</strong>
            · No mês: <strong class="text-white/90 font-medium">{{ espelho.banco_horas?.horas_mes_atual }}</strong>
          </p>
        </div>

        <button
          v-if="!espelho.assinatura"
          type="button"
          @click="modalAssinatura = true"
          class="h-12 px-6 rounded-full bg-[#9c2337] hover:bg-[#831b2d] active:scale-[0.98] text-white font-semibold text-sm flex items-center justify-center gap-2 transition cursor-pointer shrink-0"
        >
          <AppIcon name="pen-tool" size="16" />
          <span>Assinar espelho</span>
        </button>
        <div v-else class="text-xs text-white/60 sm:text-right space-y-0.5">
          <span class="block">Assinado em <strong class="text-white/90 font-medium">{{ espelho.assinatura.data_hora }}</strong></span>
          <span class="block font-mono text-[10px] truncate max-w-[260px]" :title="espelho.assinatura.hash_autenticidade">
            Hash {{ espelho.assinatura.hash_autenticidade.substring(0, 16) }}...
          </span>
        </div>
      </div>

      <!-- Marcações do mês -->
      <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-7">
        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800 border-b border-slate-100 pb-4">Marcações do mês</h3>
        <div class="overflow-x-auto -mx-2 mt-2">
          <table class="w-full text-sm min-w-[640px]">
            <thead>
              <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 text-left">
                <th class="py-3 px-2">Data</th>
                <th class="py-3 px-2">Entrada</th>
                <th class="py-3 px-2">Saída</th>
                <th class="py-3 px-2">Entrada</th>
                <th class="py-3 px-2">Saída</th>
                <th class="py-3 px-2">Total</th>
                <th class="py-3 px-2">Saldo</th>
                <th class="py-3 px-2">Ocorrência</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="d in espelho.dias" :key="d.data" :class="d.tipo !== 'Trabalhado' ? 'text-slate-400' : 'text-slate-700'">
                <td class="py-2.5 px-2 whitespace-nowrap">
                  <span class="font-medium" :class="d.tipo === 'Trabalhado' ? 'text-slate-900' : ''">{{ formatarData(d.data) }}</span>
                  <span class="text-xs ml-1">{{ d.dia_semana }}</span>
                </td>
                <td class="py-2.5 px-2 font-mono text-xs">{{ d.e1 }}</td>
                <td class="py-2.5 px-2 font-mono text-xs">{{ d.s1 }}</td>
                <td class="py-2.5 px-2 font-mono text-xs">{{ d.e2 }}</td>
                <td class="py-2.5 px-2 font-mono text-xs">{{ d.s2 }}</td>
                <td class="py-2.5 px-2 font-mono text-xs">{{ d.total }}</td>
                <td class="py-2.5 px-2 font-mono text-xs" :class="d.saldo.startsWith('+') ? 'text-emerald-700' : d.saldo.startsWith('-') ? 'text-rose-700' : ''">{{ d.saldo }}</td>
                <td class="py-2.5 px-2 text-xs">{{ d.tipo }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- Modal de assinatura -->
    <Teleport to="body">
    <div
      v-if="modalAssinatura"
      class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4"
      @click.self="modalAssinatura = false"
    >
      <div class="w-full sm:max-w-lg bg-white rounded-t-2xl sm:rounded-2xl p-6 space-y-4">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-base font-semibold text-slate-900">Assinar espelho de ponto</h3>
            <p class="text-xs text-slate-500 mt-0.5">{{ espelho.mes_nome }} · {{ colaborador?.nome }}</p>
          </div>
          <button type="button" @click="modalAssinatura = false" class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer" aria-label="Fechar">
            <AppIcon name="close" size="16" />
          </button>
        </div>

        <p class="text-xs text-slate-600 leading-relaxed bg-slate-50 border border-slate-200 rounded-lg p-3">
          Declaro que conferi as marcações deste espelho de ponto e que elas correspondem à minha jornada de trabalho no período.
        </p>

        <div class="space-y-1.5">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-700">Assine no quadro abaixo</span>
            <button type="button" @click="limparAssinatura" class="text-xs text-slate-500 hover:text-slate-900 cursor-pointer">Limpar</button>
          </div>
          <canvas
            ref="canvas"
            class="w-full h-40 rounded-lg border-2 border-dashed border-slate-300 bg-white touch-none cursor-crosshair"
            @pointerdown="iniciar"
            @pointermove="desenhar"
            @pointerup="finalizar"
            @pointerleave="finalizar"
          ></canvas>
        </div>

        <p v-if="erro" class="text-xs text-rose-700">{{ erro }}</p>

        <button
          type="button"
          @click="enviarAssinatura"
          :disabled="!assinou || enviando"
          class="w-full h-11 rounded-full bg-[#9c2337] hover:bg-[#831b2d] disabled:opacity-50 text-white font-semibold text-sm flex items-center justify-center gap-2 transition cursor-pointer"
        >
          <span v-if="enviando" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
          <span>{{ enviando ? 'Registrando...' : 'Confirmar assinatura' }}</span>
        </button>
      </div>
    </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, watch, nextTick, onMounted } from 'vue';
import { usarFecharComEsc } from '../utils/fecharComEsc.js';
import AppIcon from './AppIcon.vue';
import { apiFetch } from '../utils/api.js';

defineProps({
  colaborador: { type: Object, default: null }
});

const espelho = ref(null);
const carregando = ref(true);
const erro = ref('');
const modalAssinatura = ref(false);
const enviando = ref(false);
const assinou = ref(false);
const canvas = ref(null);

let ctx = null;
let desenhando = false;

function cabecalhoAuth() {
  return { 'Authorization': `Bearer ${localStorage.getItem('vb_auth_token') || ''}` };
}

function formatarData(iso) {
  const [, m, d] = iso.split('-');
  return `${d}/${m}`;
}

watch(modalAssinatura, async (aberto) => {
  if (!aberto) return;
  erro.value = '';
  assinou.value = false;
  await nextTick();
  const el = canvas.value;
  const rect = el.getBoundingClientRect();
  el.width = rect.width * 2;
  el.height = rect.height * 2;
  ctx = el.getContext('2d');
  ctx.scale(2, 2);
  ctx.strokeStyle = '#141414';
  ctx.lineWidth = 2.5;
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
});

function ponto(e) {
  const rect = canvas.value.getBoundingClientRect();
  return [e.clientX - rect.left, e.clientY - rect.top];
}

function iniciar(e) {
  desenhando = true;
  assinou.value = true;
  ctx.beginPath();
  ctx.moveTo(...ponto(e));
}

function desenhar(e) {
  if (!desenhando) return;
  ctx.lineTo(...ponto(e));
  ctx.stroke();
}

function finalizar() {
  desenhando = false;
}

function limparAssinatura() {
  ctx?.clearRect(0, 0, canvas.value.width, canvas.value.height);
  assinou.value = false;
}

async function enviarAssinatura() {
  if (!assinou.value || enviando.value) return;
  enviando.value = true;
  erro.value = '';
  try {
    const res = await apiFetch('api/ponto/assinar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', ...cabecalhoAuth() },
      body: JSON.stringify({
        competencia: espelho.value.competencia,
        assinatura_base64: canvas.value.toDataURL('image/png')
      })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.erro);
    espelho.value = data.espelho;
    modalAssinatura.value = false;
  } catch (e) {
    erro.value = e.message || 'Não foi possível registrar a assinatura.';
  } finally {
    enviando.value = false;
  }
}

usarFecharComEsc(() => { modalAssinatura.value = false; }, modalAssinatura);

onMounted(async () => {
  try {
    const res = await apiFetch('api/ponto', { headers: cabecalhoAuth() });
    const data = await res.json();
    if (!res.ok) throw new Error(data.erro);
    espelho.value = data.espelho;
  } catch (e) {
    erro.value = 'Não foi possível carregar o espelho de ponto.';
  } finally {
    carregando.value = false;
  }
});

</script>
