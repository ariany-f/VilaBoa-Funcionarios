<template>
  <div class="space-y-6 animate-slide-up">
    <div v-if="carregando" class="py-16 text-center text-sm text-slate-400">Carregando escala de folgas...</div>

    <div v-else-if="!escala" class="bg-white rounded-2xl border border-slate-200 py-16 px-6 text-center space-y-1">
      <AppIcon name="calendar" size="28" class="mx-auto text-slate-300" />
      <p class="text-sm text-slate-500">{{ erro || 'Nenhuma escala de folgas publicada para você no momento.' }}</p>
    </div>

    <template v-else>
      <!-- Escala selecionada (mesmo formato do espelho de ponto) -->
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
            <AppIcon name="check" size="18" />
            <span>Conferir e dar ciência</span>
          </button>
          <button
            type="button"
            @click="imprimir"
            class="h-14 sm:h-12 rounded-2xl sm:rounded-full bg-white/10 hover:bg-white/20 border border-white/20 text-white active:scale-[0.98] font-semibold text-base sm:text-sm flex items-center justify-center gap-2 transition cursor-pointer"
          >
            <AppIcon name="printer" size="18" />
            <span>Imprimir escala</span>
          </button>
        </div>
      </div>

      <!-- Calendário do período -->
      <div class="w-full bg-white rounded-2xl p-5 sm:p-8 border border-slate-200 space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-100 pb-4">
          <div>
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800 leading-tight">Calendário da escala</h3>
            <p class="text-xs text-slate-400 mt-0.5 font-medium">{{ escala.titulo }}<template v-if="escala.horario"> · Horário {{ escala.horario }}</template></p>
          </div>
          <div class="flex items-center gap-4 text-xs text-slate-500">
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-[#9c2337]"></span>Folga</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-slate-100 border border-slate-200"></span>Trabalho</span>
          </div>
        </div>

        <div class="grid gap-6" :class="meses.length > 1 ? 'lg:grid-cols-2' : ''">
          <div v-for="m in meses" :key="m.chave" class="space-y-2">
            <p class="text-sm font-semibold text-slate-800">{{ m.nome }}</p>
            <div class="grid grid-cols-7 gap-1 sm:gap-1.5 text-center">
              <span v-for="d in SEMANA" :key="d" class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 py-1">{{ d }}</span>
              <div
                v-for="(c, i) in m.celulas"
                :key="i"
                class="aspect-square sm:aspect-auto sm:h-14 rounded-lg flex flex-col items-center justify-center text-sm"
                :class="classeCelula(c)"
                :title="c?.dia ? (c.dia.folga ? 'Folga' : 'Trabalho') : ''"
              >
                <template v-if="c">
                  <span class="font-semibold tabular-nums leading-none">{{ c.numero }}</span>
                  <span v-if="c.dia?.folga" class="hidden sm:block text-[10px] font-semibold uppercase tracking-wide mt-1">Folga</span>
                </template>
              </div>
            </div>
          </div>
        </div>

        <!-- Lista das folgas: leitura rápida no celular -->
        <div class="space-y-2">
          <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Suas folgas</p>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="f in folgas"
              :key="f.data"
              class="h-8 px-3 rounded-full text-xs font-semibold inline-flex items-center border"
              :class="f.data < hoje ? 'bg-slate-50 text-slate-400 border-slate-200' : 'bg-rose-50 text-[#9c2337] border-rose-100'"
            >{{ porExtenso(f.data) }}</span>
          </div>
        </div>
      </div>

      <!-- Ciência digital (substitui a assinatura no rodapé da escala impressa) -->
      <div ref="blocoCiencia" class="w-full bg-white rounded-2xl p-5 sm:p-8 border space-y-4" :class="escala.ciencia ? 'border-emerald-200' : 'border-amber-200'">
        <template v-if="!escala.ciencia">
          <div>
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800">Ciência da escala</h3>
            <p class="text-xs text-slate-500 mt-0.5">Confira os dias acima. Ao confirmar, registramos a data, a hora e o aparelho usado, no lugar da assinatura em papel.</p>
          </div>
          <p class="text-sm text-slate-700 bg-slate-50 border border-slate-200 rounded-xl p-4 leading-relaxed">
            Declaro que tomei ciência da minha escala de folgas ({{ escala.titulo }}) do período de {{ dataBr(escala.periodo_inicio) }} a {{ dataBr(escala.periodo_fim) }}, com os dias de trabalho e de folga nela indicados.
          </p>
          <p v-if="erroCiencia" class="text-xs font-medium text-rose-700">{{ erroCiencia }}</p>
          <button
            type="button"
            :disabled="enviando"
            @click="darCiencia"
            class="w-full sm:w-auto h-14 sm:h-12 px-8 rounded-2xl sm:rounded-full bg-[#9c2337] hover:bg-[#831b2d] disabled:opacity-60 text-white font-semibold text-base sm:text-sm inline-flex items-center justify-center gap-2 transition cursor-pointer active:scale-[0.98]"
          >
            <AppIcon name="check" size="18" />
            {{ enviando ? 'Registrando...' : 'Li e estou ciente da minha escala' }}
          </button>
        </template>
        <div v-else class="flex items-start gap-3">
          <span class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
            <AppIcon name="check" size="20" />
          </span>
          <div class="min-w-0 space-y-1">
            <p class="text-sm font-semibold text-slate-900">Você deu ciência desta escala em {{ escala.ciencia.data_hora }}</p>
            <p class="text-xs text-slate-500 break-all">Código de autenticidade: <span class="font-mono">{{ escala.ciencia.hash_autenticidade }}</span></p>
          </div>
        </div>
      </div>

      <!-- Outras escalas -->
      <div v-if="escalas.length > 1" class="w-full bg-white rounded-2xl p-5 sm:p-8 border border-slate-200 space-y-4">
        <div class="border-b border-slate-100 pb-4">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800 leading-tight">Histórico de escalas</h3>
          <p class="text-xs text-slate-400 mt-0.5 font-medium">{{ escalas.length }} escalas publicadas</p>
        </div>
        <div class="divide-y divide-slate-100">
          <button
            v-for="e in escalas"
            :key="e.id"
            type="button"
            @click="selecionar(e.id)"
            class="w-full flex items-center gap-3 py-3 text-left cursor-pointer group"
          >
            <span class="min-w-0 flex-1">
              <span class="block text-sm font-semibold truncate" :class="e.id === escala.id ? 'text-[#9c2337]' : 'text-slate-900 group-hover:text-[#9c2337]'">{{ dataBr(e.periodo_inicio) }} a {{ dataBr(e.periodo_fim) }}</span>
              <span class="block text-xs text-slate-500 truncate">{{ e.titulo }}</span>
            </span>
            <span class="shrink-0 text-[11px] font-semibold px-2.5 py-1 rounded-full" :class="e.ciencia ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
              {{ e.ciencia ? '✓ Ciente' : 'Dar ciência' }}
            </span>
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import AppIcon from './AppIcon.vue';

const props = defineProps({
  colaborador: { type: Object, default: null },
  // Link do RH ou notificação: abre esta escala
  escalaInicial: { type: String, default: '' }
});
const emit = defineEmits(['ciencia']);

const SEMANA = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
const DIAS_EXTENSO = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];

const escalas = ref([]);
const selecionadaId = ref('');
const carregando = ref(true);
const erro = ref('');
const enviando = ref(false);
const erroCiencia = ref('');
const blocoCiencia = ref(null);

const hoje = new Date().toLocaleDateString('sv-SE', { timeZone: 'America/Sao_Paulo' });
const token = () => localStorage.getItem('vb_auth_token') || '';

const escala = computed(() => escalas.value.find((e) => e.id === selecionadaId.value) || null);
const folgas = computed(() => (escala.value?.dias || []).filter((d) => d.folga));
const proximaFolga = computed(() => folgas.value.find((d) => d.data >= hoje) || null);

const paraDate = (iso) => new Date(`${iso}T12:00:00`);
const dataBr = (iso) => (iso ? iso.split('-').reverse().join('/') : '');
const curta = (iso, comAno = false) => {
  const [a, m, d] = iso.split('-');
  return comAno ? `${d}/${m}/${a}` : `${d}/${m}`;
};
const porExtenso = (iso) => `${DIAS_EXTENSO[paraDate(iso).getDay()]}, ${curta(iso)}`;

// Um bloco de calendário para cada mês do período (21 a 20 do mês seguinte)
const meses = computed(() => {
  if (!escala.value) return [];
  const porData = Object.fromEntries(escala.value.dias.map((d) => [d.data, d]));
  const lista = [];
  const inicio = paraDate(escala.value.periodo_inicio);
  const fim = paraDate(escala.value.periodo_fim);
  let ano = inicio.getFullYear();
  let mes = inicio.getMonth();
  while (ano < fim.getFullYear() || (ano === fim.getFullYear() && mes <= fim.getMonth())) {
    const primeiro = new Date(ano, mes, 1, 12);
    const totalDias = new Date(ano, mes + 1, 0).getDate();
    const todas = Array(primeiro.getDay()).fill(null);
    for (let d = 1; d <= totalDias; d++) {
      const iso = `${ano}-${String(mes + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
      todas.push({ numero: d, iso, dia: porData[iso] || null });
    }
    // Só as semanas que têm dias da escala (o período vai de 21 a 20)
    const celulas = [];
    for (let i = 0; i < todas.length; i += 7) {
      const semana = todas.slice(i, i + 7);
      if (semana.some((c) => c?.dia)) celulas.push(...semana);
    }
    const nome = primeiro.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
    lista.push({ chave: `${ano}-${mes}`, nome: nome.charAt(0).toUpperCase() + nome.slice(1), celulas });
    mes++;
    if (mes > 11) { mes = 0; ano++; }
  }
  return lista;
});

function classeCelula(c) {
  if (!c) return '';
  const marcaHoje = c.iso === hoje ? ' ring-2 ring-offset-1 ring-slate-900' : '';
  if (!c.dia) return 'text-slate-300' + marcaHoje;
  if (c.dia.folga) return 'bg-[#9c2337] text-white' + marcaHoje;
  return 'bg-slate-100 text-slate-700' + marcaHoje;
}

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
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function rolarParaCiencia() {
  await nextTick();
  blocoCiencia.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
    <scr${"ipt"}>window.onload=function(){window.print()}</scr${"ipt"}>
  </body></html>`;
  const janela = window.open('', '_blank');
  if (!janela) return;
  janela.document.write(html);
  janela.document.close();
}

onMounted(carregar);
</script>
