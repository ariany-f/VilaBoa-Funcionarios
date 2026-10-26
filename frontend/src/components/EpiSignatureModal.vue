<template>
  <div v-if="entrega" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/70 backdrop-blur-sm transition-all">
    <div 
      class="bg-white w-full max-w-xl lg:max-w-2xl max-h-[95vh] sm:max-h-[92vh] rounded-t-3xl sm:rounded-2xl shadow-2xl flex flex-col overflow-hidden animate-slide-up border border-slate-200"
      @click.stop
    >
      <!-- Cabeçalho -->
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-2xl bg-rose-50 text-[#9c2337] border border-rose-200 flex items-center justify-center font-bold text-lg">
            <AppIcon name="shield-check" size="20" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-sm font-semibold text-slate-900">Termo de Recebimento de EPI</h3>
              <span class="text-[10px] bg-rose-100 text-[#9c2337] font-bold px-2 py-0.5 rounded">NR-06</span>
            </div>
            <p class="text-xs text-slate-500">Ref: {{ entrega.id }} • {{ entrega.data_entrega_formatada }}</p>
          </div>
        </div>

        <button 
          @click="$emit('close')" 
          class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-all cursor-pointer active:scale-90 border border-slate-200 shadow-2xs"
        >
          <AppIcon name="x" size="14" />
        </button>
      </div>

      <!-- Conteúdo com Rolagem -->
      <div class="p-4 sm:p-6 overflow-y-auto space-y-4 text-xs sm:text-sm">
        <!-- Dados do Canteiro e Técnico -->
        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 space-y-2 text-xs">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <div>
              <span class="text-slate-400 block text-[10px] uppercase font-bold">Colaborador / Matrícula:</span>
              <strong class="text-slate-800">{{ entrega.nome_colaborador }}</strong>
              <span class="text-slate-500 block">Mat: {{ entrega.matricula }} • CPF: {{ entrega.cpf }}</span>
            </div>
            <div>
              <span class="text-slate-400 block text-[10px] uppercase font-bold">Canteiro de Obras:</span>
              <strong class="text-slate-800">{{ entrega.canteiro_obra }}</strong>
              <span class="text-slate-500 block">Resp: {{ entrega.tecnico_responsavel }}</span>
            </div>
          </div>
        </div>

        <!-- Tabela de Itens de EPI -->
        <div>
          <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Equipamentos Entregues</h4>
          <div class="border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
            <div class="bg-slate-100 px-3 py-2 grid grid-cols-12 text-[10px] font-bold text-slate-600 uppercase border-b border-slate-200">
              <span class="col-span-6">Equipamento / Fabricante</span>
              <span class="col-span-3 text-center">Nº CA</span>
              <span class="col-span-3 text-right">Qtd / Motivo</span>
            </div>
            <div class="divide-y divide-slate-100 bg-white">
              <div 
                v-for="item in entrega.itens" 
                :key="item.item"
                class="px-3 py-2.5 grid grid-cols-12 items-center text-xs hover:bg-slate-50"
              >
                <div class="col-span-6 pr-2">
                  <div class="font-bold text-slate-800 leading-tight">{{ item.descricao }}</div>
                  <div class="text-[10px] text-slate-400">Marca: {{ item.fabricante }}</div>
                </div>
                <div class="col-span-3 text-center">
                  <span class="inline-block bg-slate-100 border border-slate-200 text-slate-700 font-mono font-bold text-[10px] px-2 py-0.5 rounded">
                    CA {{ item.ca }}
                  </span>
                </div>
                <div class="col-span-3 text-right">
                  <span class="font-bold text-slate-800">{{ item.quantidade }} {{ item.unidade }}</span>
                  <div class="text-[9px] text-slate-400">{{ item.motivo }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Termo de Responsabilidade Legal (NR-06) -->
        <div class="p-3.5 bg-rose-50/60 border border-rose-200 rounded-2xl space-y-1.5">
          <div class="flex items-center gap-1.5 text-xs font-bold text-[#9c2337] uppercase tracking-wider">
            <AppIcon name="document" size="14" />
            <span>Termo de Ciência e Compromisso Legal (NR-06 / CLT)</span>
          </div>
          <p class="text-[11px] text-slate-700 leading-relaxed text-justify">
            {{ entrega.termo_responsabilidade }}
          </p>
        </div>

        <!-- Canvas de Assinatura Interativa (Touch / Mouse) -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1">
              <AppIcon name="pen-tool" size="14" />
              <span>Assinatura Eletrônica do Colaborador</span>
            </span>
            <button 
              type="button" 
              @click="limparAssinatura" 
              class="text-xs text-[#9c2337] hover:text-[#831b2d] font-bold hover:underline cursor-pointer"
            >
              Limpar / Assinar Novamente
            </button>
          </div>

          <div class="relative bg-slate-50 border-2 border-dashed border-slate-300 rounded-2xl overflow-hidden touch-none">
            <canvas 
              ref="signatureCanvas" 
              class="w-full h-36 bg-white cursor-crosshair block"
              @mousedown="iniciarDesenho"
              @mousemove="desenhar"
              @mouseup="finalizarDesenho"
              @mouseleave="finalizarDesenho"
              @touchstart.passive="iniciarTouch"
              @touchmove.prevent="desenharTouch"
              @touchend="finalizarDesenho"
            ></canvas>
            
            <div v-if="!assinou" class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center text-slate-300 text-xs">
              <AppIcon name="pen-tool" size="24" class="mb-1 text-slate-300" />
              <span>Assine com o dedo ou mouse nesta área</span>
              <div class="w-48 border-b border-slate-200 mt-3"></div>
            </div>
          </div>
        </div>

        <!-- Metadados de Auditoria e Validade Jurídica -->
        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-[10px] text-slate-500 space-y-1">
          <div class="flex justify-between">
            <span>Assinante: <strong>{{ entrega.nome_colaborador }} (CPF: {{ entrega.cpf }})</strong></span>
            <span>Data: <strong>{{ dataHoje }}</strong></span>
          </div>
          <div class="text-slate-400">
            Amparo Jurídico: Portaria MTP nº 672/2021 (NR-06) e Lei Federal nº 14.063/2020. Certificação e Hash SHA-256 gerados no envio.
          </div>
        </div>

        <div v-if="erroMsg" class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium rounded-xl">
          {{ erroMsg }}
        </div>
      </div>

      <!-- Rodapé com Botão de Confirmação -->
      <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-2.5">
        <button 
          @click="confirmarAssinatura" 
          :disabled="carregando || !assinou"
          class="flex-1 py-3.5 px-4 bg-[#9c2337] hover:bg-[#831b2d] disabled:opacity-50 active:scale-[0.98] text-white font-bold rounded-2xl text-xs sm:text-sm flex items-center justify-center gap-2 shadow-md transition cursor-pointer"
        >
          <span v-if="carregando" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
          <AppIcon v-else name="check" size="16" />
          <span>{{ carregando ? 'Registrando assinatura com hash...' : 'Confirmar e Assinar Recebimento' }}</span>
        </button>
        <button 
          @click="$emit('close')" 
          class="py-3.5 px-4 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-2xl text-xs transition cursor-pointer"
        >
          Cancelar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { usarFecharComEsc } from '../utils/fecharComEsc.js';
import { ref, onMounted, nextTick } from 'vue';
import AppIcon from './AppIcon.vue';

const props = defineProps({
  entrega: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close', 'assinado']);
usarFecharComEsc(() => emit('close'));

const signatureCanvas = ref(null);
const assinou = ref(false);
const carregando = ref(false);
const erroMsg = ref('');
const dataHoje = new Date().toLocaleDateString('pt-BR');

let ctx = null;
let desenhando = false;

function initCanvas() {
  const canvas = signatureCanvas.value;
  if (!canvas) return;

  // Ajusta resolução nítida
  const rect = canvas.getBoundingClientRect();
  canvas.width = rect.width * 2;
  canvas.height = rect.height * 2;
  ctx = canvas.getContext('2d');
  ctx.scale(2, 2);
  ctx.strokeStyle = '#141414';
  ctx.lineWidth = 2.5;
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
}

function iniciarDesenho(e) {
  desenhando = true;
  assinou.value = true;
  const rect = signatureCanvas.value.getBoundingClientRect();
  ctx.beginPath();
  ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
}

function desenhar(e) {
  if (!desenhando) return;
  const rect = signatureCanvas.value.getBoundingClientRect();
  ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
  ctx.stroke();
}

function finalizarDesenho() {
  desenhando = false;
}

function iniciarTouch(e) {
  desenhando = true;
  assinou.value = true;
  const rect = signatureCanvas.value.getBoundingClientRect();
  const touch = e.touches[0];
  ctx.beginPath();
  ctx.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
}

function desenharTouch(e) {
  if (!desenhando) return;
  const rect = signatureCanvas.value.getBoundingClientRect();
  const touch = e.touches[0];
  ctx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
  ctx.stroke();
}

function limparAssinatura() {
  if (!ctx) return;
  const canvas = signatureCanvas.value;
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  assinou.value = false;
}

async function confirmarAssinatura() {
  if (!assinou.value) {
    erroMsg.value = 'Por favor, faça sua assinatura antes de confirmar.';
    return;
  }

  carregando.value = true;
  erroMsg.value = '';

  try {
    const imagemBase64 = signatureCanvas.value.toDataURL('image/png');
    const token = localStorage.getItem('vb_auth_token');

    const res = await fetch('/api/epis/assinar', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        entrega_id: props.entrega.id,
        assinatura_base64: imagemBase64
      })
    });

    const data = await res.json();
    if (!res.ok) {
      erroMsg.value = data.erro || 'Falha ao registrar assinatura.';
      return;
    }

    emit('assinado', data.entrega);
  } catch (err) {
    erroMsg.value = 'Erro de comunicação ao assinar EPI.';
  } finally {
    carregando.value = false;
  }
}

onMounted(() => {
  nextTick(() => {
    initCanvas();
  });
});
</script>

<style scoped>
@keyframes slideUp {
  from {
    transform: translateY(100%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}
.animate-slide-up {
  animation: slideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
