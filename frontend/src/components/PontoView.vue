<template>
  <div class="space-y-6 animate-slide-up">
    <div v-if="carregando" class="py-16 text-center text-sm text-slate-400">Carregando espelho de ponto...</div>

    <div v-else-if="!ultimo" class="bg-white rounded-2xl border border-slate-200 py-16 text-center space-y-1">
      <AppIcon name="calendar" size="28" class="mx-auto text-slate-300" />
      <p class="text-sm text-slate-500">{{ erro || 'Nenhum espelho de ponto disponível no momento.' }}</p>
    </div>

    <template v-else>
      <!-- Último espelho (mesmo formato do último holerite) -->
      <div class="w-full bg-gradient-to-br from-[#141414] to-[#000000] text-white rounded-2xl p-6 sm:p-7 shadow-sm space-y-5">
        <div class="space-y-1.5">
          <span class="text-xs text-white/60 font-medium">Seu último espelho de ponto</span>
          <div class="flex items-center gap-2.5 flex-wrap">
            <h3 class="text-3xl font-semibold tracking-tight leading-tight">{{ ultimo.mes_nome }}</h3>
            <span
              class="text-xs font-semibold px-3 py-1 rounded-full"
              :class="ultimo.assinatura ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-400/15 text-amber-300'"
            >{{ ultimo.assinatura ? '✓ Assinado' : 'Falta assinar' }}</span>
          </div>
          <p class="text-sm text-white/70">{{ resumoLinha(ultimo) }}</p>
        </div>

        <div class="grid grid-cols-1 gap-3" :class="podeAssinar(ultimo) ? 'sm:grid-cols-2' : 'sm:grid-cols-1 sm:max-w-xs'">
          <button
            v-if="podeAssinar(ultimo)"
            type="button"
            @click="assinarNoAutentique(ultimo)"
            class="h-14 sm:h-12 rounded-2xl sm:rounded-full bg-[#9c2337] hover:bg-[#831b2d] text-white active:scale-[0.98] font-semibold text-base sm:text-sm flex items-center justify-center gap-2 transition cursor-pointer"
          >
            <AppIcon name="pen-tool" size="18" />
            <span>Conferi e quero assinar</span>
          </button>
          <button
            type="button"
            @click="abrirDetalhe(ultimo.competencia)"
            class="h-14 sm:h-12 rounded-2xl sm:rounded-full bg-white/10 hover:bg-white/20 border border-white/20 text-white active:scale-[0.98] font-semibold text-base sm:text-sm flex items-center justify-center gap-2 transition cursor-pointer"
          >
            <AppIcon name="eye" size="18" />
            <span>Ver espelho</span>
          </button>
        </div>
        <p v-if="!ultimo.assinatura && !somenteLeitura && !ultimo.link_assinatura" class="text-xs text-white/60">
          A assinatura deste mês ainda não foi liberada pelo RH.
        </p>
      </div>

      <!-- Histórico de espelhos (mesmo formato do histórico de holerites) -->
      <div class="w-full bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 space-y-5">
        <div class="border-b border-slate-100 pb-4">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800 leading-tight">Histórico de espelhos</h3>
          <p class="text-xs text-slate-400 mt-0.5 font-medium">{{ competencias.length }} {{ competencias.length === 1 ? 'espelho disponível' : 'espelhos disponíveis' }}</p>
        </div>

        <!-- Celular: cartões com botões grandes -->
        <div class="md:hidden space-y-3">
          <div v-for="c in competencias" :key="c.competencia" class="bg-white rounded-2xl p-4 border border-slate-200 space-y-3">
            <button type="button" @click="abrirDetalhe(c.competencia)" class="w-full flex items-center gap-3 text-left cursor-pointer">
              <div class="w-12 h-12 rounded-xl bg-slate-100 flex flex-col items-center justify-center shrink-0">
                <span class="text-sm font-semibold uppercase text-[#9c2337] leading-none">{{ c.mes_nome.slice(0, 3) }}</span>
                <span class="text-[11px] font-semibold text-slate-900 leading-tight mt-0.5">{{ c.competencia.slice(3) }}</span>
              </div>
              <div class="min-w-0 flex-1">
                <h4 class="font-semibold text-slate-900 text-base leading-tight">{{ c.mes_nome }}</h4>
                <span class="text-xs text-slate-500 block mt-0.5">{{ c.assinado_em ? 'Assinado em ' + c.assinado_em.slice(0, 10) : 'Aguardando sua assinatura' }}</span>
              </div>
              <span class="shrink-0 text-[11px] font-semibold px-2.5 py-1 rounded-full" :class="c.assinado_em ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                {{ c.assinado_em ? '✓ Assinado' : 'Assinar' }}
              </span>
            </button>
            <div class="grid grid-cols-2 gap-2">
              <button type="button" @click="abrirDetalhe(c.competencia)" class="h-12 rounded-xl bg-[#141414] text-white font-semibold text-sm inline-flex items-center justify-center gap-2 cursor-pointer active:scale-[0.98] transition">
                <AppIcon name="eye" size="17" />
                Ver espelho
              </button>
              <button type="button" @click="acaoNoPeriodo(c.competencia, 'whatsapp')" class="h-12 rounded-xl bg-[#1DA851] text-white font-semibold text-sm inline-flex items-center justify-center gap-2 cursor-pointer active:scale-[0.98] transition">
                <AppIcon name="whatsapp" size="17" />
                WhatsApp
              </button>
            </div>
          </div>
        </div>

        <!-- Computador: tabela com botões com texto -->
        <div class="hidden md:block rounded-2xl border border-slate-200 overflow-hidden">
          <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/80 border-b border-slate-200">
              <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                <th class="py-3.5 px-5">Mês</th>
                <th class="py-3.5 px-5">Situação</th>
                <th class="py-3.5 px-5"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
              <tr v-for="c in competencias" :key="c.competencia" @click="abrirDetalhe(c.competencia)" class="hover:bg-slate-50/70 cursor-pointer group">
                <td class="py-4 px-5">
                  <span class="font-semibold text-slate-900 group-hover:text-[#9c2337] text-sm">{{ c.mes_nome }}</span>
                </td>
                <td class="py-4 px-5">
                  <span class="h-6 px-3 rounded-full text-[11px] font-bold text-slate-600 bg-slate-100/90 border border-slate-200 inline-flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full" :class="c.assinado_em ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                    {{ c.assinado_em ? 'Assinado em ' + c.assinado_em.slice(0, 10) : 'Falta assinar' }}
                  </span>
                </td>
                <td class="py-3 px-5 text-right" @click.stop>
                  <div class="flex items-center justify-end gap-2">
                    <button type="button" @click="abrirDetalhe(c.competencia)" class="h-9 px-3.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 text-xs font-semibold inline-flex items-center gap-1.5 cursor-pointer">
                      <AppIcon name="eye" size="14" />
                      Ver
                    </button>
                    <button type="button" @click="acaoNoPeriodo(c.competencia, 'baixar')" class="h-9 px-3.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 text-xs font-semibold inline-flex items-center gap-1.5 cursor-pointer">
                      <AppIcon name="download" size="14" />
                      Baixar
                    </button>
                    <button type="button" @click="acaoNoPeriodo(c.competencia, 'whatsapp')" class="h-9 px-3.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-[#1DA851] hover:text-white text-xs font-semibold inline-flex items-center gap-1.5 cursor-pointer">
                      <AppIcon name="whatsapp" size="14" />
                      WhatsApp
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- Detalhe do mês (mesmo formato do demonstrativo de pagamento) -->
    <Teleport to="body">
      <div v-if="detalheAberto" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="detalheAberto = false">
        <div class="w-full max-w-4xl bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl flex flex-col max-h-[94vh] sm:max-h-[90vh] overflow-hidden">
          <div class="px-5 pt-3 pb-3 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
            <div class="flex items-center space-x-3 min-w-0">
              <img src="/img/logo-vilaboa-color.png" alt="Vila Boa" class="h-7 w-auto object-contain" />
              <div class="border-l border-slate-200 pl-3 min-w-0">
                <h3 class="text-xs font-bold text-slate-800">Espelho de Ponto</h3>
                <p class="text-[10px] text-slate-500 truncate">{{ espelho?.mes_nome }} · {{ espelho?.assinatura ? 'Assinado em ' + espelho.assinatura.data_hora : 'Falta assinar' }}</p>
              </div>
            </div>
            <button type="button" @click="detalheAberto = false" class="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center cursor-pointer border border-slate-200" aria-label="Fechar">
              <AppIcon name="x" size="14" />
            </button>
          </div>

          <div v-if="carregandoDetalhe || !espelho" class="py-16 text-center text-sm text-slate-400">Carregando...</div>
          <div v-else class="p-4 sm:p-5 overflow-y-auto space-y-4">
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
          </div>

          <!-- Ações, como no rodapé do demonstrativo -->
          <div v-if="espelho" class="p-3.5 pb-[calc(0.875rem+env(safe-area-inset-bottom,0px))] bg-slate-50 border-t border-slate-200 grid gap-2.5 shrink-0" :class="podeAssinar(espelho) ? 'grid-cols-1 sm:grid-cols-3' : 'grid-cols-2'">
            <button
              v-if="podeAssinar(espelho)"
              type="button"
              @click="assinarNoAutentique(espelho)"
              class="h-12 rounded-xl bg-[#9c2337] hover:bg-[#831b2d] text-white font-semibold text-sm flex items-center justify-center gap-2 cursor-pointer"
            >
              <AppIcon name="pen-tool" size="16" />
              Conferi e quero assinar
            </button>
            <button type="button" @click="baixarEspelho(espelho)" class="h-12 rounded-xl bg-white border border-slate-200 text-slate-800 font-semibold text-sm flex items-center justify-center gap-2 cursor-pointer">
              <AppIcon name="download" size="16" />
              Baixar
            </button>
            <button type="button" @click="enviarWhatsapp(espelho)" class="h-12 rounded-xl bg-[#1DA851] hover:bg-[#178F44] text-white font-semibold text-sm flex items-center justify-center gap-2 cursor-pointer">
              <AppIcon name="whatsapp" size="16" />
              WhatsApp
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { usarFecharComEsc } from '../utils/fecharComEsc.js';
import AppIcon from './AppIcon.vue';
import { apiFetch } from '../utils/api.js';

const props = defineProps({
  colaborador: { type: Object, default: null },
  // Consulta do RH: espelho de outro colaborador, sem botão de assinar
  matricula: { type: String, default: '' },
  // Competência vinda de um link de aviso (mm/aaaa)
  competenciaInicial: { type: String, default: '' },
  somenteLeitura: { type: Boolean, default: false }
});

const espelho = ref(null);       // mês aberto no detalhe
const ultimo = ref(null);        // mês mais recente (destaque do topo)
const detalheAberto = ref(false);
const carregandoDetalhe = ref(false);
usarFecharComEsc(() => { detalheAberto.value = false; }, detalheAberto);
const competencias = ref([]);
const somenteTrabalhados = ref(false);

// Abre o espelho formatado para imprimir ou salvar em PDF (opção "Salvar como PDF" do celular/navegador)
function baixarEspelho(e, janelaAberta = null) {
  const nome = props.colaborador?.nome || '';
  const matricula = props.colaborador?.matricula || props.matricula || '';
  const linhas = e.dias.map((d) => trabalhado(d)
    ? `<tr><td>${formatarData(d.data)} ${d.dia_semana}</td><td>${d.e1} – ${d.s1}</td><td>${d.e2} – ${d.s2}</td><td>${horasLegiveis(d.total)}</td><td>${d.saldo}</td><td></td></tr>`
    : `<tr class="folga"><td>${formatarData(d.data)} ${d.dia_semana}</td><td></td><td></td><td></td><td></td><td>${d.tipo}</td></tr>`
  ).join('');
  const resumoHtml = resumoDe(e).map((r) => `<div><span>${r.rotulo}</span><strong>${r.valor}</strong></div>`).join('');
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
  const janela = janelaAberta || window.open('', '_blank');
  if (!janela) return;
  janela.document.write(html);
  janela.document.close();
}

// Envia um resumo do mês para o WhatsApp do colaborador, com o link para abrir no portal
function enviarWhatsapp(e, janelaAberta = null) {
  const r = Object.fromEntries(resumoDe(e).map((x) => [x.rotulo, x.valor]));
  const texto = `*Espelho de ponto · ${e.mes_nome}*\n` +
    `Dias trabalhados: ${r['Dias trabalhados']}\n` +
    `Horas no mês: ${r['Horas no mês']}\n` +
    `Saldo do mês: ${r['Saldo do mês']}\n` +
    `Banco de horas: ${r['Banco de horas']}\n` +
    `Situação: ${e.assinatura ? 'assinado em ' + e.assinatura.data_hora : 'falta assinar'}\n\n` +
    `Ver no Portal do Colaborador: ${window.location.origin}${window.location.pathname}#/ponto`;
  const telefone = (props.colaborador?.telefone_atual || '').replace(/\D/g, '');
  const destino = telefone ? `phone=55${telefone}&` : '';
  const url = `https://api.whatsapp.com/send?${destino}text=${encodeURIComponent(texto)}`;
  if (janelaAberta) janelaAberta.location.href = url;
  else window.open(url, '_blank');
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

function resumoDe(e) {
  const dias = e?.dias || [];
  const trabalhados = dias.filter(trabalhado);
  const totalMin = trabalhados.reduce((t, d) => t + paraMinutos(d.total), 0);
  const horas = Math.floor(totalMin / 60) + 'h' + String(totalMin % 60).padStart(2, '0');
  const mes = e?.banco_horas?.horas_mes_atual || '';
  const banco = e?.banco_horas?.saldo_total || '';
  const cor = (v) => (v.startsWith('-') ? 'text-rose-700' : 'text-emerald-700');
  return [
    { rotulo: 'Dias trabalhados', valor: trabalhados.length, cor: 'text-slate-900' },
    { rotulo: 'Horas no mês', valor: horas, cor: 'text-slate-900' },
    { rotulo: 'Saldo do mês', valor: mes.replace(' ', '') || '-', cor: cor(mes) },
    { rotulo: 'Banco de horas', valor: banco.replace(' ', '') || '-', cor: cor(banco) }
  ];
}
const resumo = computed(() => resumoDe(espelho.value));

// "16 dias trabalhados · 145h48 · saldo +17h48m"
function resumoLinha(e) {
  const r = Object.fromEntries(resumoDe(e).map((x) => [x.rotulo, x.valor]));
  return `${r['Dias trabalhados']} dias trabalhados · ${r['Horas no mês']} no mês · saldo ${r['Saldo do mês']}`;
}
const carregando = ref(true);
const erro = ref('');


function cabecalhoAuth() {
  return { 'Authorization': `Bearer ${localStorage.getItem('vb_auth_token') || ''}` };
}

function formatarData(iso) {
  const [, m, d] = iso.split('-');
  return `${d}/${m}`;
}

// A assinatura é feita no Autentique: cada espelho traz o link do documento (link_assinatura)
const podeAssinar = (e) => !!e && !e.assinatura && !props.somenteLeitura && !!e.link_assinatura;

function assinarNoAutentique(e) {
  window.open(e.link_assinatura, '_blank', 'noopener');
}

async function buscar(competencia = '') {
  const filtro = competencia ? `competencia=${encodeURIComponent(competencia)}` : '';
  const url = props.matricula
    ? `api/rh/ponto?matricula=${encodeURIComponent(props.matricula)}&${filtro}`
    : `api/ponto?${filtro}`;
  const res = await apiFetch(url, { headers: cabecalhoAuth() });
  const data = await res.json();
  if (!res.ok) throw new Error(data.erro);
  return data;
}

// Carrega a lista de meses e o mais recente (topo da tela)
async function carregar() {
  carregando.value = true;
  erro.value = '';
  try {
    const data = await buscar();
    competencias.value = data.competencias || [];
    ultimo.value = data.espelho;
  } catch (e) {
    ultimo.value = null;
    erro.value = 'Não foi possível carregar o espelho de ponto.';
  } finally {
    carregando.value = false;
  }
}

async function carregarMes(competencia) {
  if (espelho.value?.competencia === competencia) return espelho.value;
  if (ultimo.value?.competencia === competencia) return (espelho.value = ultimo.value);
  const data = await buscar(competencia);
  espelho.value = data.espelho;
  return espelho.value;
}

async function abrirDetalhe(competencia) {
  detalheAberto.value = true;
  carregandoDetalhe.value = true;
  try {
    await carregarMes(competencia);
  } finally {
    carregandoDetalhe.value = false;
  }
}

// Baixar/WhatsApp direto do histórico: abre a janela já no clique (o navegador bloqueia depois)
async function acaoNoPeriodo(competencia, acao) {
  const janela = window.open('', '_blank');
  const e = await carregarMes(competencia);
  if (acao === 'baixar') baixarEspelho(e, janela);
  else enviarWhatsapp(e, janela);
}

watch(() => props.matricula, () => { espelho.value = null; carregar(); });

onMounted(async () => {
  await carregar();
  if (props.competenciaInicial) abrirDetalhe(props.competenciaInicial);
});

</script>
