<template>
  <div class="space-y-6 animate-slide-up">
    <!-- Período: lista rolável de meses -->
    <div v-if="competencias.length" class="-mx-4 px-4 sm:mx-0 sm:px-0 overflow-x-auto">
      <div class="flex items-center gap-2 w-max">
        <button
          v-for="c in competencias"
          :key="c.competencia"
          type="button"
          @click="carregar(c.competencia)"
          class="h-11 px-4 rounded-full border text-sm font-semibold flex items-center gap-2 transition cursor-pointer whitespace-nowrap"
          :class="espelho?.competencia === c.competencia ? 'bg-[#141414] border-[#141414] text-white' : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300'"
        >
          {{ c.mes_nome }}
          <span v-if="c.assinado_em" class="text-emerald-500" title="Assinado">✓</span>
          <span v-else class="w-2 h-2 rounded-full bg-amber-400" title="Falta assinar"></span>
        </button>
      </div>
    </div>

    <div v-if="carregando" class="py-16 text-center text-sm text-slate-400">Carregando espelho de ponto...</div>

    <div v-else-if="!espelho" class="bg-white rounded-2xl border border-slate-200 py-16 text-center space-y-1">
      <AppIcon name="calendar" size="28" class="mx-auto text-slate-300" />
      <p class="text-sm text-slate-500">{{ erro || 'Nenhum espelho de ponto disponível no momento.' }}</p>
    </div>

    <template v-else>
      <!-- Cabeçalho do mês com a ação principal -->
      <div class="bg-gradient-to-br from-[#141414] to-[#000000] text-white rounded-2xl p-5 sm:p-7 flex flex-col sm:flex-row sm:items-center justify-between gap-5">
        <div class="space-y-2">
          <span class="text-xs text-white/60 font-medium">Espelho de ponto</span>
          <h2 class="text-2xl sm:text-3xl font-semibold tracking-tight leading-tight">{{ espelho.mes_nome }}</h2>
          <span
            class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full"
            :class="espelho.assinatura ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-400/15 text-amber-300'"
          >
            {{ espelho.assinatura ? '✓ Assinado em ' + espelho.assinatura.data_hora.slice(0, 10) : 'Falta assinar' }}
          </span>
        </div>

        <button
          v-if="!espelho.assinatura && !somenteLeitura"
          type="button"
          @click="modalAssinatura = true"
          class="h-14 sm:h-12 px-6 rounded-2xl sm:rounded-full bg-[#9c2337] hover:bg-[#831b2d] active:scale-[0.98] text-white font-semibold text-base sm:text-sm flex items-center justify-center gap-2 transition cursor-pointer shrink-0 w-full sm:w-auto"
        >
          <AppIcon name="pen-tool" size="18" />
          <span>Conferi e quero assinar</span>
        </button>
        <p v-else-if="espelho.assinatura" class="text-[11px] text-white/50 font-mono truncate sm:max-w-[240px]" :title="espelho.assinatura.hash_autenticidade">
          Código {{ espelho.assinatura.hash_autenticidade.substring(0, 16) }}…
        </p>
      </div>

      <!-- Baixar e enviar -->
      <div class="grid grid-cols-2 gap-3">
        <button
          type="button"
          @click="baixarEspelho"
          class="h-12 rounded-2xl bg-white border border-slate-200 hover:border-slate-300 text-slate-800 text-sm font-semibold flex items-center justify-center gap-2 cursor-pointer active:scale-[0.98] transition"
        >
          <AppIcon name="download" size="17" />
          Baixar
        </button>
        <button
          type="button"
          @click="enviarWhatsapp"
          class="h-12 rounded-2xl bg-[#1DA851] hover:bg-[#178F44] text-white text-sm font-semibold flex items-center justify-center gap-2 cursor-pointer active:scale-[0.98] transition"
        >
          <AppIcon name="whatsapp" size="17" />
          WhatsApp
        </button>
      </div>

      <!-- Resumo do mês -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div v-for="r in resumo" :key="r.rotulo" class="bg-white rounded-2xl border border-slate-200 p-4">
          <span class="block text-xs text-slate-500">{{ r.rotulo }}</span>
          <span class="block text-xl font-semibold mt-1 tabular-nums" :class="r.cor">{{ r.valor }}</span>
        </div>
      </div>

      <!-- Dias do mês -->
      <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6">
        <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-100">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800">Dias do mês</h3>
          <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer select-none">
            <input type="checkbox" v-model="somenteTrabalhados" class="w-4 h-4 accent-[#9c2337]" />
            Só dias trabalhados
          </label>
        </div>

        <!-- Celular: uma linha por dia -->
        <ul class="md:hidden divide-y divide-slate-100">
          <li v-for="d in diasVisiveis" :key="d.data" class="py-3 flex items-center gap-3" :class="ehHoje(d) ? 'bg-amber-50/60 -mx-4 px-4' : ''">
            <div class="w-12 shrink-0 text-center" :class="trabalhado(d) ? 'text-slate-900' : 'text-slate-400'">
              <span class="block text-lg font-semibold leading-none">{{ d.data.slice(8, 10) }}</span>
              <span class="block text-[11px] mt-1">{{ d.dia_semana }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <template v-if="trabalhado(d)">
                <p class="text-sm text-slate-800 tabular-nums"><span class="text-xs text-slate-400 w-12 inline-block">Manhã</span>{{ d.e1 }} – {{ d.s1 }}</p>
                <p class="text-sm text-slate-800 tabular-nums"><span class="text-xs text-slate-400 w-12 inline-block">Tarde</span>{{ d.e2 }} – {{ d.s2 }}</p>
                <p class="text-xs text-slate-500 mt-0.5">Trabalhou {{ horasLegiveis(d.total) }}</p>
              </template>
              <p v-else class="text-sm text-slate-400">{{ d.tipo }}</p>
            </div>
            <span v-if="trabalhado(d) && d.saldo !== '00:00'" class="shrink-0 text-xs font-semibold px-2 py-1 rounded-lg tabular-nums" :class="corSaldo(d)">
              {{ d.saldo }}
            </span>
          </li>
        </ul>

        <!-- Computador: tabela -->
        <table class="hidden md:table w-full text-sm mt-1">
          <thead>
            <tr class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 text-left">
              <th class="py-3 px-2">Dia</th>
              <th class="py-3 px-2">Manhã</th>
              <th class="py-3 px-2">Tarde</th>
              <th class="py-3 px-2">Trabalhou</th>
              <th class="py-3 px-2">Saldo</th>
              <th class="py-3 px-2">Ocorrência</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="d in diasVisiveis" :key="d.data" :class="[trabalhado(d) ? 'text-slate-700' : 'text-slate-400 bg-slate-50/60', ehHoje(d) ? 'bg-amber-50' : '']">
              <td class="py-2.5 px-2 whitespace-nowrap">
                <span class="font-semibold" :class="trabalhado(d) ? 'text-slate-900' : ''">{{ formatarData(d.data) }}</span>
                <span class="text-xs ml-1.5">{{ d.dia_semana }}</span>
              </td>
              <td class="py-2.5 px-2 tabular-nums">{{ trabalhado(d) ? d.e1 + ' – ' + d.s1 : '' }}</td>
              <td class="py-2.5 px-2 tabular-nums">{{ trabalhado(d) ? d.e2 + ' – ' + d.s2 : '' }}</td>
              <td class="py-2.5 px-2 tabular-nums">{{ trabalhado(d) ? horasLegiveis(d.total) : '' }}</td>
              <td class="py-2.5 px-2">
                <span v-if="trabalhado(d) && d.saldo !== '00:00'" class="text-xs font-semibold px-2 py-0.5 rounded-md tabular-nums" :class="corSaldo(d)">{{ d.saldo }}</span>
              </td>
              <td class="py-2.5 px-2 text-xs">{{ trabalhado(d) ? '' : d.tipo }}</td>
            </tr>
          </tbody>
        </table>
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
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import { usarFecharComEsc } from '../utils/fecharComEsc.js';
import AppIcon from './AppIcon.vue';
import { apiFetch } from '../utils/api.js';

const props = defineProps({
  colaborador: { type: Object, default: null },
  // Consulta do RH: espelho de outro colaborador, sem botão de assinar
  matricula: { type: String, default: '' },
  somenteLeitura: { type: Boolean, default: false }
});

const espelho = ref(null);
const competencias = ref([]);
const somenteTrabalhados = ref(false);

// Abre o espelho formatado para imprimir ou salvar em PDF (opção "Salvar como PDF" do celular/navegador)
function baixarEspelho() {
  const e = espelho.value;
  const nome = props.colaborador?.nome || '';
  const matricula = props.colaborador?.matricula || props.matricula || '';
  const linhas = e.dias.map((d) => trabalhado(d)
    ? `<tr><td>${formatarData(d.data)} ${d.dia_semana}</td><td>${d.e1} – ${d.s1}</td><td>${d.e2} – ${d.s2}</td><td>${horasLegiveis(d.total)}</td><td>${d.saldo}</td><td></td></tr>`
    : `<tr class="folga"><td>${formatarData(d.data)} ${d.dia_semana}</td><td></td><td></td><td></td><td></td><td>${d.tipo}</td></tr>`
  ).join('');
  const resumoHtml = resumo.value.map((r) => `<div><span>${r.rotulo}</span><strong>${r.valor}</strong></div>`).join('');
  const assinatura = e.assinatura
    ? `Assinado eletronicamente em ${e.assinatura.data_hora} · Código de autenticidade ${e.assinatura.hash_autenticidade}`
    : 'Espelho ainda não assinado pelo colaborador.';
  const html = `<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Espelho de ponto ${e.mes_nome}</title>
<style>
  body{font-family:Arial,sans-serif;color:#141414;margin:32px;font-size:12px}
  h1{font-size:18px;margin:0}.topo{border-bottom:3px solid #9c2337;padding-bottom:10px;margin-bottom:14px;display:flex;justify-content:space-between;align-items:flex-end}
  .resumo{display:flex;gap:10px;margin:14px 0}.resumo div{flex:1;border:1px solid #ddd;border-radius:8px;padding:8px}.resumo span{display:block;color:#666;font-size:10px}.resumo strong{font-size:15px}
  table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:5px 6px;border-bottom:1px solid #eee}th{font-size:10px;text-transform:uppercase;color:#666}
  tr.folga td{color:#999;background:#fafafa}.ass{margin-top:18px;font-size:10px;color:#444}
</style></head><body>
<div class="topo"><div><h1>Espelho de Ponto · ${e.mes_nome}</h1><div>${nome} ${matricula ? '· Matrícula ' + matricula : ''}</div></div><div>Vila Boa Construções e Serviços</div></div>
<div class="resumo">${resumoHtml}</div>
<table><thead><tr><th>Dia</th><th>Manhã</th><th>Tarde</th><th>Trabalhou</th><th>Saldo</th><th>Ocorrência</th></tr></thead><tbody>${linhas}</tbody></table>
<p class="ass">${assinatura}</p>
<script>window.onload=()=>{window.print();}<\/script></body></html>`;
  const janela = window.open('', '_blank');
  if (!janela) return;
  janela.document.write(html);
  janela.document.close();
}

// Envia um resumo do mês para o WhatsApp do colaborador, com o link para abrir no portal
function enviarWhatsapp() {
  const e = espelho.value;
  const r = Object.fromEntries(resumo.value.map((x) => [x.rotulo, x.valor]));
  const texto = `*Espelho de ponto · ${e.mes_nome}*\n` +
    `Dias trabalhados: ${r['Dias trabalhados']}\n` +
    `Horas no mês: ${r['Horas no mês']}\n` +
    `Saldo do mês: ${r['Saldo do mês']}\n` +
    `Banco de horas: ${r['Banco de horas']}\n` +
    `Situação: ${e.assinatura ? 'assinado em ' + e.assinatura.data_hora : 'falta assinar'}\n\n` +
    `Ver no Portal do Colaborador: ${window.location.origin}${window.location.pathname}#/ponto`;
  const telefone = (props.colaborador?.telefone_atual || '').replace(/\D/g, '');
  const destino = telefone ? `phone=55${telefone}&` : '';
  window.open(`https://api.whatsapp.com/send?${destino}text=${encodeURIComponent(texto)}`, '_blank');
}

const trabalhado = (d) => d.tipo.startsWith('Trabalhado');
const ehHoje = (d) => d.data === new Date().toISOString().slice(0, 10);
const corSaldo = (d) => (d.saldo.startsWith('-') ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700');
const paraMinutos = (hhmm) => {
  const [h, m] = hhmm.replace(/[+-]/, '').split(':').map(Number);
  return (h * 60 + m) * (hhmm.startsWith('-') ? -1 : 1);
};
// "08:48" -> "8h48"
const horasLegiveis = (hhmm) => {
  const [h, m] = hhmm.split(':');
  return Number(h) + 'h' + (m === '00' ? '' : m);
};

const diasVisiveis = computed(() => (espelho.value?.dias || []).filter((d) => !somenteTrabalhados.value || trabalhado(d)));

const resumo = computed(() => {
  const dias = espelho.value?.dias || [];
  const trabalhados = dias.filter(trabalhado);
  const totalMin = trabalhados.reduce((t, d) => t + paraMinutos(d.total), 0);
  const horas = Math.floor(totalMin / 60) + 'h' + String(totalMin % 60).padStart(2, '0');
  const mes = espelho.value?.banco_horas?.horas_mes_atual || '';
  const banco = espelho.value?.banco_horas?.saldo_total || '';
  const cor = (v) => (v.startsWith('-') ? 'text-rose-700' : 'text-emerald-700');
  return [
    { rotulo: 'Dias trabalhados', valor: trabalhados.length, cor: 'text-slate-900' },
    { rotulo: 'Horas no mês', valor: horas, cor: 'text-slate-900' },
    { rotulo: 'Saldo do mês', valor: mes.replace(' ', '') || '-', cor: cor(mes) },
    { rotulo: 'Banco de horas', valor: banco.replace(' ', '') || '-', cor: cor(banco) }
  ];
});
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
    const comp = competencias.value.find(c => c.competencia === data.espelho.competencia);
    if (comp) comp.assinado_em = data.espelho.assinatura?.data_hora;
    modalAssinatura.value = false;
  } catch (e) {
    erro.value = e.message || 'Não foi possível registrar a assinatura.';
  } finally {
    enviando.value = false;
  }
}

usarFecharComEsc(() => { modalAssinatura.value = false; }, modalAssinatura);

async function carregar(competencia = '') {
  carregando.value = true;
  erro.value = '';
  try {
    const filtro = competencia ? `competencia=${encodeURIComponent(competencia)}` : '';
    const url = props.matricula
      ? `api/rh/ponto?matricula=${encodeURIComponent(props.matricula)}&${filtro}`
      : `api/ponto?${filtro}`;
    const res = await apiFetch(url, { headers: cabecalhoAuth() });
    const data = await res.json();
    if (!res.ok) throw new Error(data.erro);
    espelho.value = data.espelho;
    competencias.value = data.competencias || [];
  } catch (e) {
    espelho.value = null;
    erro.value = 'Não foi possível carregar o espelho de ponto.';
  } finally {
    carregando.value = false;
  }
}

watch(() => props.matricula, () => carregar());

onMounted(() => carregar());

</script>
