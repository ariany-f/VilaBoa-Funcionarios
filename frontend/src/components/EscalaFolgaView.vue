<template>
  <div class="space-y-6 animate-slide-up">
    <div v-if="carregando" class="py-16 text-center text-sm text-slate-400">Carregando escala de folgas...</div>

    <div v-else-if="!escala" class="bg-white rounded-2xl border border-slate-200 py-16 px-6 text-center space-y-1">
      <AppIcon name="calendar-check" size="28" class="mx-auto text-slate-300" />
      <p class="text-sm text-slate-500">{{ erro || 'Nenhuma escala de folgas publicada para você no momento.' }}</p>
    </div>

    <template v-else>
      <!-- Seletor de período (mesmo formato da Ficha de EPI e do Espelho de Ponto) -->
      <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 space-y-3">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h3 class="text-base font-semibold text-slate-900">Escala de folgas</h3>
            <p class="text-xs text-slate-400">{{ escalas.length }} {{ escalas.length === 1 ? 'período' : 'períodos' }} · de 21 a 20 do mês seguinte</p>
          </div>
          <span v-if="totalPendentes > 0" class="text-xs font-semibold px-3 py-1 rounded-full bg-amber-100 text-amber-700 whitespace-nowrap">
            {{ totalPendentes }} {{ totalPendentes === 1 ? 'pendente' : 'pendentes' }}
          </span>
        </div>
        <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
          <button
            v-for="e in escalas"
            :key="e.id"
            type="button"
            @click="selecionar(e.id)"
            class="shrink-0 px-4 h-11 rounded-xl border text-sm font-semibold flex items-center gap-2 cursor-pointer transition"
            :class="e.id === escala.id ? 'bg-[#141414] text-white border-[#141414]' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
          >
            <span>{{ curta(e.periodo_inicio) }} a {{ curta(e.periodo_fim) }}</span>
            <span class="w-2 h-2 rounded-full" :class="e.ciencia ? 'bg-emerald-500' : 'bg-amber-400'"></span>
          </button>
        </div>
      </div>

      <!-- Escala selecionada (resumo + ações) -->
      <div class="w-full bg-gradient-to-br from-[#141414] to-[#000000] text-white rounded-2xl p-6 sm:p-7 shadow-sm relative overflow-hidden space-y-5">
        <div class="absolute -right-20 -top-20 w-80 h-80 bg-[#9c2337]/30 rounded-full blur-3xl pointer-events-none"></div>

        <div v-if="colaborador" class="hidden sm:flex flex-wrap items-center justify-between gap-x-4 gap-y-1 relative z-10 text-xs text-white/60">
          <span class="text-sm font-semibold text-white">{{ colaborador.nome }}</span>
          <span class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <span>{{ colaborador.cargo }}</span>
            <span>Matrícula <strong class="text-white/90 font-medium">{{ colaborador.matricula }}</strong></span>
          </span>
        </div>

        <div class="relative z-10 space-y-1.5" :class="colaborador ? 'sm:border-t sm:border-white/10 sm:pt-4' : ''">
          <span class="text-xs text-white/60 font-medium">{{ escala.titulo }}</span>
          <div class="flex items-center gap-2.5 flex-wrap">
            <h3 class="text-2xl sm:text-3xl font-semibold tracking-tight leading-tight">{{ curta(escala.periodo_inicio) }} a {{ curta(escala.periodo_fim, true) }}</h3>
            <span
              class="text-xs font-semibold px-3 py-1 rounded-full"
              :class="escala.ciencia ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-400/15 text-amber-300'"
            >{{ escala.ciencia ? '✓ Ciente' : 'Falta dar ciência' }}</span>
          </div>
          <p class="text-sm text-white/70">
            {{ folgas.length }} {{ folgas.length === 1 ? 'folga' : 'folgas' }} no período<template v-if="proximaFolga"> · Próxima folga: <strong class="text-white font-semibold">{{ porExtenso(proximaFolga.data) }}</strong></template>
          </p>
        </div>

        <div class="relative z-10 grid grid-cols-1 gap-3" :class="!escala.ciencia ? 'sm:grid-cols-2' : 'sm:grid-cols-1 sm:max-w-xs'">
          <button
            v-if="!escala.ciencia"
            type="button"
            @click="rolarParaCiencia"
            class="h-14 sm:h-12 rounded-2xl sm:rounded-full bg-[#9c2337] hover:bg-[#831b2d] text-white active:scale-[0.98] font-semibold text-base sm:text-sm flex items-center justify-center gap-2 transition cursor-pointer"
          >
            <AppIcon name="pen-tool" size="18" />
            <span>Conferi e quero dar ciência</span>
          </button>
          <button
            type="button"
            @click="imprimir"
            class="h-14 sm:h-12 rounded-2xl sm:rounded-full bg-white/10 hover:bg-white/20 border border-white/20 text-white active:scale-[0.98] font-semibold text-base sm:text-sm flex items-center justify-center gap-2 transition cursor-pointer"
          >
            <AppIcon name="download" size="18" />
            <span>Imprimir / salvar PDF</span>
          </button>
        </div>
      </div>

      <!-- Prévia do documento (como no Espelho de Ponto e na Ficha de EPI) -->
      <div ref="documento" class="w-full bg-white rounded-2xl border border-slate-200 scroll-mt-4">
        <div class="px-5 pt-3 pb-3 border-b border-slate-100 flex items-center justify-between bg-slate-50 rounded-t-2xl">
          <div class="flex items-center space-x-3 min-w-0">
            <img :src="logoEmpresa" alt="Logo da empresa" class="h-7 w-auto object-contain" />
            <div class="border-l border-slate-200 pl-3 min-w-0">
              <h3 class="text-xs font-bold text-slate-800">Escala de Folgas</h3>
              <p class="text-[10px] text-slate-500 truncate">{{ dataBr(escala.periodo_inicio) }} a {{ dataBr(escala.periodo_fim) }} · {{ escala.ciencia ? 'Ciente em ' + escala.ciencia.data_hora : 'Falta dar ciência' }}</p>
            </div>
          </div>
          <button type="button" @click="imprimir" class="h-9 px-3 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold inline-flex items-center gap-1.5 cursor-pointer border border-slate-200 shrink-0">
            <AppIcon name="printer" size="14" />
            <span class="hidden sm:inline">Imprimir / PDF</span>
          </button>
        </div>

        <!-- Documento no leiaute da escala impressa (PRC-153) -->
        <div class="p-3 sm:p-7 text-slate-900 font-mono text-[11px] sm:text-xs">
          <div class="flex flex-wrap items-end justify-between gap-x-4 gap-y-1 pb-2">
            <p class="font-bold uppercase">{{ escala.arquivo || escala.titulo }}</p>
            <p>{{ hojeBr }}</p>
          </div>

          <!-- Chapa / Nome / Função / Escala -->
          <div class="border border-slate-800">
            <div class="hidden sm:grid grid-cols-[6rem_minmax(0,2fr)_minmax(0,1.5fr)_minmax(0,1.2fr)] gap-3 px-3 py-1.5 border-b border-slate-800 font-bold uppercase">
              <span>Chapa</span><span>Nome</span><span>Função</span><span>Escala</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-[6rem_minmax(0,2fr)_minmax(0,1.5fr)_minmax(0,1.2fr)] gap-x-3 gap-y-2 px-3 py-2 uppercase">
              <div><span class="sm:hidden block font-bold text-[10px]">Chapa</span>{{ colaborador?.matricula }}</div>
              <div class="col-span-2 sm:col-span-1 order-first sm:order-none"><span class="sm:hidden block font-bold text-[10px]">Nome</span>{{ colaborador?.nome }}</div>
              <div><span class="sm:hidden block font-bold text-[10px]">Função</span>{{ colaborador?.cargo }}</div>
              <div><span class="sm:hidden block font-bold text-[10px]">Escala</span>{{ escala.letra ? '5x1 · Letra ' + escala.letra : escala.titulo }}</div>
            </div>
            <div v-if="escala.horario" class="px-3 py-1.5 border-t border-slate-800 uppercase"><b>Horário:</b> {{ escala.horario }}</div>
          </div>

          <!-- Dias: no celular, a situação do dia; no computador, as colunas da planilha -->
          <table class="w-full mt-4 border-collapse text-center">
            <thead class="font-bold uppercase">
              <tr class="bg-slate-100">
                <th class="border border-slate-400 py-1 px-1">Data</th>
                <th class="border border-slate-400 py-1 px-1">Dia</th>
                <th class="sm:hidden border border-slate-400 py-1 px-1">Situação</th>
                <th v-for="c in COLUNAS" :key="c" class="hidden sm:table-cell border border-slate-400 py-1 px-1">{{ c }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in escala.dias" :key="d.data" :class="[d.folga ? 'bg-slate-300 font-bold' : '', d.data === hoje ? 'outline outline-2 outline-amber-400' : '']">
                <td class="border border-slate-400 py-0.5 px-1 whitespace-nowrap">{{ dataBr(d.data) }}</td>
                <td class="border border-slate-400 py-0.5 px-1">{{ d.dia }}</td>
                <td class="sm:hidden border border-slate-400 py-0.5 px-1">{{ d.folga ? 'FOLGA' : 'TRABALHO' }}</td>
                <td v-for="(h, i) in horariosDia(d)" :key="i" class="hidden sm:table-cell border border-slate-400 py-0.5 px-1">{{ h }}</td>
              </tr>
            </tbody>
          </table>

          <!-- Rodapé da folha: chapa e assinatura (aqui, a ciência digital) -->
          <div class="mt-6 space-y-3">
            <p><b>CHAPA:</b> {{ colaborador?.matricula }}</p>
            <template v-if="escala.ciencia">
              <p><b>ASSINATURA:</b> ciência digital registrada em {{ escala.ciencia.data_hora }}</p>
              <p class="text-[10px] text-slate-500 break-all">{{ escala.ciencia.termo }} · Código de autenticidade {{ escala.ciencia.hash_autenticidade }}</p>
            </template>
            <template v-else>
              <p class="leading-relaxed text-left sm:text-justify">
                Declaro que tomei ciência da minha escala de folgas ({{ escala.titulo }}) do período de {{ dataBr(escala.periodo_inicio) }} a {{ dataBr(escala.periodo_fim) }}, com os dias de trabalho e de folga nela indicados.
              </p>
              <p><b>ASSINATURA:</b> ____________________________________</p>
            </template>
          </div>
        </div>

        <!-- Ação flutuante: fica presa na base da tela enquanto falta a ciência -->
        <div v-if="!escala.ciencia" class="sticky bottom-0 z-20 p-3.5 bg-slate-50/95 backdrop-blur border-t border-slate-200 rounded-b-2xl shadow-[0_-8px_24px_-12px_rgba(15,23,42,0.25)] space-y-2">
          <p v-if="erroCiencia" class="text-xs font-medium text-rose-700">{{ erroCiencia }}</p>
          <button
            type="button"
            :disabled="enviando"
            @click="darCiencia"
            class="w-full h-12 rounded-xl bg-[#9c2337] hover:bg-[#831b2d] disabled:opacity-60 text-white font-semibold text-sm flex items-center justify-center gap-2 cursor-pointer"
          >
            <AppIcon name="pen-tool" size="16" />
            {{ enviando ? 'Registrando...' : 'Li e estou ciente da minha escala' }}
          </button>
          <p class="text-[11px] text-slate-500 text-center">Registramos a data, a hora e o aparelho usado, no lugar da assinatura em papel.</p>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { imprimirHtml } from '../utils/imprimir.js';
import { ref, computed, onMounted, nextTick } from 'vue';
import AppIcon from './AppIcon.vue';
import { logoEmpresa } from '../utils/logoEmpresa.js';

const props = defineProps({
  colaborador: { type: Object, default: null },
  // Link do RH ou notificação: abre esta escala
  escalaInicial: { type: String, default: '' }
});
const emit = defineEmits(['ciencia']);

const COLUNAS = ['Entrada 1', 'Saída 1', 'Entrada 2', 'Saída 2'];
const hojeBr = new Date().toLocaleDateString('pt-BR', { timeZone: 'America/Sao_Paulo' });
const DIAS_EXTENSO = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];

const escalas = ref([]);
const selecionadaId = ref('');
const carregando = ref(true);
const erro = ref('');
const enviando = ref(false);
const erroCiencia = ref('');
const documento = ref(null);

const hoje = new Date().toLocaleDateString('sv-SE', { timeZone: 'America/Sao_Paulo' });
const token = () => localStorage.getItem('vb_auth_token') || '';

const escala = computed(() => escalas.value.find((e) => e.id === selecionadaId.value) || null);
const folgas = computed(() => (escala.value?.dias || []).filter((d) => d.folga));
const totalPendentes = computed(() => escalas.value.filter((e) => !e.ciencia).length);
const horariosDia = (d) => (d.folga ? Array(4).fill('FOLGA') : (d.horarios?.length === 4 ? d.horarios : Array(4).fill('TRABALHO')));
const proximaFolga = computed(() => folgas.value.find((d) => d.data >= hoje) || null);

const paraDate = (iso) => new Date(`${iso}T12:00:00`);
const dataBr = (iso) => (iso ? iso.split('-').reverse().join('/') : '');
const curta = (iso, comAno = false) => {
  const [a, m, d] = iso.split('-');
  return comAno ? `${d}/${m}/${a}` : `${d}/${m}`;
};
const porExtenso = (iso) => `${DIAS_EXTENSO[paraDate(iso).getDay()]}, ${curta(iso)}`;

// Abre a escala vigente (a que contém hoje); senão a próxima; senão a mais recente
function escalaInicial(lista) {
  return (lista.find((e) => e.periodo_inicio <= hoje && e.periodo_fim >= hoje)
    || [...lista].reverse().find((e) => e.periodo_inicio > hoje)
    || lista[0])?.id || '';
}

async function carregar() {
  try {
    const res = await fetch('/api/escalas-folga', { headers: { Authorization: `Bearer ${token()}` } });
    const data = await res.json();
    if (!res.ok) throw new Error(data.erro || 'Não foi possível carregar a escala.');
    escalas.value = data.escalas || [];
    if (!escala.value) {
      selecionadaId.value = escalas.value.some((e) => e.id === props.escalaInicial) ? props.escalaInicial : escalaInicial(escalas.value);
    }
  } catch (e) {
    erro.value = e.message;
  } finally {
    carregando.value = false;
  }
}

function selecionar(id) {
  selecionadaId.value = id;
  erroCiencia.value = '';
}

async function rolarParaCiencia() {
  await nextTick();
  // Leva ao início do documento: o botão de ciência fica preso na base da tela durante a leitura
  documento.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

async function darCiencia() {
  enviando.value = true;
  erroCiencia.value = '';
  try {
    const res = await fetch('/api/escalas-folga/ciencia', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token()}` },
      body: JSON.stringify({ escala_id: escala.value.id })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.erro || 'Não foi possível registrar a ciência.');
    escalas.value = escalas.value.map((e) => (e.id === data.escala.id ? data.escala : e));
    emit('ciencia');
  } catch (e) {
    erroCiencia.value = e.message;
  } finally {
    enviando.value = false;
  }
}

// Folha no formato da escala impressa do PRC-153: cabeçalho com nome e data, rodapé com chapa e ciência
function imprimir() {
  const e = escala.value;
  const c = props.colaborador || {};
  const esc = (t) => String(t ?? '').replace(/[&<>"]/g, (x) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[x]));
  const linhas = e.dias.map((d) => {
    const h = d.folga ? ['FOLGA', 'FOLGA', 'FOLGA', 'FOLGA'] : (d.horarios?.length ? d.horarios : ['TRABALHO', 'TRABALHO', 'TRABALHO', 'TRABALHO']);
    return `<tr class="${d.folga ? 'folga' : ''}"><td>${dataBr(d.data)}</td><td>${esc(d.dia)}</td>${h.map((x) => `<td>${esc(x)}</td>`).join('')}</tr>`;
  }).join('');
  const rodape = e.ciencia
    ? `Ciência digital registrada em ${esc(e.ciencia.data_hora)} · Código ${esc(e.ciencia.hash_autenticidade)}`
    : 'Ciência digital ainda não registrada no portal.';
  const html = `<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>${esc(e.titulo)}</title><style>
    body{font-family:Arial,sans-serif;margin:24px;color:#111}
    header{display:flex;justify-content:space-between;font-size:12px;margin-bottom:12px}
    table{border-collapse:collapse;width:100%;font-size:12px}
    th,td{border:1px solid #999;padding:4px 6px;text-align:center}
    th{background:#eee}
    tr.folga td{background:#d9d9d9;font-weight:bold}
    footer{margin-top:16px;font-size:12px;line-height:1.7}
    @page{size:landscape;margin:12mm}
  </style></head><body>
    <header><strong>${esc(e.arquivo || e.titulo)}</strong><span>${new Date().toLocaleDateString('pt-BR')}</span></header>
    <p style="font-size:12px;margin:0 0 8px">${esc(c.nome || '')} · Chapa ${esc(c.matricula || '')} · ${dataBr(e.periodo_inicio)} a ${dataBr(e.periodo_fim)}</p>
    <table><thead><tr><th>Data</th><th>Dia</th><th>Entrada 1</th><th>Saída 1</th><th>Entrada 2</th><th>Saída 2</th></tr></thead><tbody>${linhas}</tbody></table>
    <footer>CHAPA: ${esc(c.matricula || '')}<br>${rodape}</footer>
  </body></html>`;
  imprimirHtml(html);
}

onMounted(carregar);
</script>
