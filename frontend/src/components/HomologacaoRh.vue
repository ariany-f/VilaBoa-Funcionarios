<template>
  <div class="space-y-5">
    <!-- Cabeçalho -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 space-y-4">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800">Homologação sindical</h3>
          <p class="text-xs text-slate-500 mt-0.5">Agende a homologação de quem foi demitido: o funcionário recebe o convite, o lembrete e os avisos de mudança por WhatsApp e e-mail.</p>
        </div>
        <button v-if="pode(colaborador, 'rh_cadernos', 'publicar')" type="button" @click="abrirNovo" class="h-11 px-6 rounded-full bg-[#9c2337] hover:bg-[#831b2d] text-white text-sm font-semibold cursor-pointer shadow-sm">+ Agendar homologação</button>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <button v-for="a in abas" :key="a.id" type="button" @click="aba = a.id" class="h-9 px-4 rounded-full border text-xs font-semibold transition cursor-pointer"
          :class="aba === a.id ? 'bg-[#141414] border-[#141414] text-white' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300'">{{ a.rotulo }} <span class="opacity-60 ml-1">{{ contagem[a.id] }}</span></button>
        <div class="ml-auto relative w-full sm:w-64">
          <AppIcon name="search" size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input v-model="busca" type="search" placeholder="Buscar colaborador ou sindicato" class="w-full h-9 pl-9 pr-3 rounded-full border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
        </div>
      </div>
      <p v-if="mensagem" class="text-xs font-medium" :class="mensagemErro ? 'text-rose-700' : 'text-emerald-700'">{{ mensagem }}</p>
    </div>

    <!-- Lista -->
    <div v-if="carregando" class="py-12 text-center text-sm text-slate-400">Carregando agendamentos...</div>
    <div v-else-if="!visiveis.length" class="bg-white rounded-2xl border border-dashed border-slate-300 py-14 text-center space-y-2">
      <AppIcon name="calendar" size="28" class="mx-auto text-slate-300" />
      <p class="text-sm text-slate-500">{{ busca ? 'Nenhum agendamento encontrado para essa busca.' : 'Nenhum agendamento nesta aba.' }}</p>
      <button v-if="!busca && aba === 'ativos' && pode(colaborador, 'rh_cadernos', 'publicar')" type="button" @click="abrirNovo" class="text-sm font-semibold text-[#9c2337] hover:underline cursor-pointer">Agendar a primeira homologação</button>
    </div>
    <ul v-else class="grid grid-cols-1 gap-3 md:grid-cols-2">
      <li v-for="h in visiveis" :key="h.id" class="bg-white rounded-2xl border border-slate-200 p-5 flex flex-col gap-3">
        <div class="flex items-start gap-3">
          <span class="w-11 h-11 rounded-xl bg-slate-100 text-slate-700 flex flex-col items-center justify-center shrink-0 leading-none">
            <span class="text-[10px] font-bold uppercase text-[#9c2337]">{{ mesCurto(h.data_hora) }}</span>
            <span class="text-base font-bold">{{ h.data_hora.slice(8, 10) }}</span>
          </span>
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-1.5 mb-1">
              <span class="selo" :class="situacoes[h.status].classe">{{ situacoes[h.status].rotulo }}</span>
              <span class="selo bg-slate-100 text-slate-600 border-slate-200">{{ h.empresa }}</span>
            </div>
            <h4 class="text-sm font-semibold text-slate-900 leading-snug">{{ h.colaborador_nome }} <span class="text-slate-400 font-normal">· {{ h.matricula }}</span></h4>
            <p class="text-xs text-slate-600 mt-1">{{ dataBr(h.data_hora) }} às {{ horaBr(h.data_hora) }} · {{ h.sindicato }}</p>
            <p v-if="h.local" class="text-xs text-slate-500">{{ h.local }}</p>
          </div>
        </div>
        <div class="text-[11px] text-slate-500 space-y-0.5">
          <p>{{ h.telefone || 'Sem WhatsApp' }} · {{ h.email || 'Sem e-mail' }}</p>
          <p>
            <template v-if="ultimoEnvio(h)">Último aviso: <strong class="text-slate-700">{{ rotuloEnvio[ultimoEnvio(h).tipo] }}</strong> em {{ dataBr(ultimoEnvio(h).em) }} às {{ horaBr(ultimoEnvio(h).em) }} (WhatsApp {{ statusCanal(ultimoEnvio(h).whatsapp.status) }} · e-mail {{ statusCanal(ultimoEnvio(h).email.status) }})</template>
            <template v-else>Nenhum aviso enviado ainda.</template>
          </p>
          <p v-if="ativo(h)">{{ h.lembrete_enviado_em ? 'Lembrete já enviado em ' + dataBr(h.lembrete_enviado_em) : 'Lembrete automático ' + h.lembrete_dias + (h.lembrete_dias === 1 ? ' dia' : ' dias') + ' antes' }}</p>
          <p v-if="h.status === 'cancelado' && h.motivo_alteracao">Motivo do cancelamento: {{ h.motivo_alteracao }}</p>
        </div>
        <div class="flex flex-wrap gap-1 pt-2 border-t border-slate-100 -mx-1">
          <template v-if="ativo(h) && pode(colaborador, 'rh_cadernos', 'editar')">
            <button type="button" @click="reenviar(h)" :disabled="ocupado === h.id" class="acao">Reenviar mensagem</button>
            <button type="button" @click="abrirRemarcar(h)" class="acao">Remarcar</button>
            <button type="button" @click="acaoSimples(h, 'realizar')" class="acao">Marcar como realizada</button>
            <button type="button" @click="abrirCancelar(h)" class="acao hover:!bg-rose-50 hover:!text-rose-700">Cancelar</button>
          </template>
          <button type="button" @click="detalhes = h" class="acao">Detalhes</button>
          <button v-if="pode(colaborador, 'rh_cadernos', 'remover')" type="button" @click="paraRemover = h" class="acao hover:!bg-rose-50 hover:!text-rose-700 ml-auto">Remover</button>
        </div>
      </li>
    </ul>

    <!-- Detalhes e histórico -->
    <ModalCadastro :aberto="!!detalhes" :titulo="'Homologação de ' + (detalhes?.colaborador_nome || '')" tamanho="md" rotulo-salvar="Fechar" @fechar="detalhes = null" @salvar="detalhes = null">
      <div v-if="detalhes" class="space-y-4 text-sm text-slate-700">
        <div class="grid sm:grid-cols-2 gap-3 text-xs">
          <p><span class="block text-slate-400">Quando</span><strong>{{ dataBr(detalhes.data_hora) }} às {{ horaBr(detalhes.data_hora) }}</strong></p>
          <p><span class="block text-slate-400">Sindicato</span><strong>{{ detalhes.sindicato }}</strong></p>
          <p><span class="block text-slate-400">Local</span><strong>{{ detalhes.local || '—' }}</strong></p>
          <p><span class="block text-slate-400">Data da demissão</span><strong>{{ detalhes.data_demissao ? dataBr(detalhes.data_demissao) : '—' }}</strong></p>
        </div>
        <div v-if="detalhes.documentos.length" class="text-xs">
          <p class="text-slate-400 mb-1">Documentos a levar</p>
          <ul class="list-disc pl-5 space-y-0.5"><li v-for="d in detalhes.documentos" :key="d">{{ d }}</li></ul>
        </div>
        <p v-if="detalhes.observacoes" class="text-xs"><span class="block text-slate-400">Observações</span>{{ detalhes.observacoes }}</p>
        <div class="text-xs">
          <p class="text-slate-400 mb-1">Avisos enviados</p>
          <p v-if="!detalhes.envios.length" class="text-slate-500">Nenhum ainda.</p>
          <ul class="divide-y divide-slate-100 rounded-xl border border-slate-200">
            <li v-for="(e, i) in [...detalhes.envios].reverse()" :key="i" class="px-3 py-2">
              <strong>{{ rotuloEnvio[e.tipo] }}</strong> · {{ dataBr(e.em) }} às {{ horaBr(e.em) }}
              <span class="block text-slate-500">WhatsApp: {{ statusCanal(e.whatsapp.status) }} · E-mail: {{ statusCanal(e.email.status) }}</span>
              <a v-if="e.whatsapp.link && e.whatsapp.status === 'ENFILEIRADO'" :href="e.whatsapp.link" target="_blank" rel="noopener" class="inline-block mt-1 font-semibold text-emerald-700 hover:underline">Abrir no WhatsApp para enviar</a>
            </li>
          </ul>
        </div>
        <div class="text-xs">
          <p class="text-slate-400 mb-1">Histórico</p>
          <ul class="space-y-1">
            <li v-for="(x, i) in [...detalhes.historico].reverse()" :key="i" class="text-slate-600">{{ dataBr(x.em) }} {{ horaBr(x.em) }} · {{ x.evento }}<template v-if="x.motivo"> ({{ x.motivo }})</template></li>
          </ul>
        </div>
      </div>
    </ModalCadastro>

    <!-- Remarcar -->
    <ModalCadastro :aberto="!!paraRemarcar" titulo="Remarcar homologação" :descricao="paraRemarcar ? paraRemarcar.colaborador_nome + ' será avisado por WhatsApp e e-mail.' : ''" rotulo-salvar="Remarcar e avisar" :enviando="salvandoAcao" :erro="erroAcao" :pode-salvar="!!novaData.data_hora" :sujo="false" @fechar="paraRemarcar = null" @salvar="remarcar">
      <label class="block text-xs font-medium text-slate-700 space-y-1.5">
        <span>Nova data e hora</span>
        <input v-model="novaData.data_hora" type="datetime-local" :min="agoraLocal()" class="campo h-11" />
      </label>
      <label class="block text-xs font-medium text-slate-700 space-y-1.5">
        <span>Sindicato</span>
        <input v-model="novaData.sindicato" type="text" maxlength="120" class="campo h-11" />
      </label>
      <label class="block text-xs font-medium text-slate-700 space-y-1.5">
        <span>Local / endereço</span>
        <input v-model="novaData.local" type="text" maxlength="200" class="campo h-11" />
      </label>
      <label class="block text-xs font-medium text-slate-700 space-y-1.5">
        <span>Motivo <span class="text-slate-400 font-normal">(vai na mensagem, opcional)</span></span>
        <input v-model="novaData.motivo" type="text" maxlength="200" placeholder="Ex.: o sindicato pediu outro horário" class="campo h-11" />
      </label>
    </ModalCadastro>

    <!-- Cancelar -->
    <ModalCadastro :aberto="!!paraCancelar" titulo="Cancelar homologação" tamanho="sm" rotulo-salvar="Cancelar e avisar" :enviando="salvandoAcao" :erro="erroAcao" @fechar="paraCancelar = null" @salvar="cancelar">
      <p class="text-sm text-slate-600">O agendamento de <strong class="text-slate-900">{{ paraCancelar?.colaborador_nome }}</strong> será cancelado e ele receberá o aviso por WhatsApp e e-mail.</p>
      <label class="block text-xs font-medium text-slate-700 space-y-1.5">
        <span>Motivo <span class="text-slate-400 font-normal">(vai na mensagem, opcional)</span></span>
        <input v-model="motivoCancelar" type="text" maxlength="200" class="campo h-11" />
      </label>
    </ModalCadastro>

    <!-- Remover -->
    <ModalCadastro :aberto="!!paraRemover" titulo="Remover agendamento" tamanho="sm" rotulo-salvar="Remover" :enviando="salvandoAcao" @fechar="paraRemover = null" @salvar="remover">
      <p class="text-sm text-slate-600">Remover o agendamento de <strong class="text-slate-900">{{ paraRemover?.colaborador_nome }}</strong>? Some da lista e não dá para desfazer; o funcionário <strong>não</strong> é avisado. Para avisar, use <strong>Cancelar</strong>.</p>
    </ModalCadastro>

    <!-- Wizard de novo agendamento -->
    <WizardCadastro
      :aberto="wizardAberto"
      titulo="Agendar homologação"
      descricao="Colaborador, sindicato e data, e o que levar. O aviso sai por WhatsApp e e-mail."
      :passos="passos"
      v-model:passo="passo"
      :validar="validar"
      :sujo="sujo"
      :enviando="enviando"
      :erro="erroWizard"
      :rotulo-concluir="form.enviar_convite ? 'Agendar e avisar' : 'Agendar'"
      @fechar="fecharWizard"
      @concluir="salvar"
    >
      <template #passo-colaborador>
        <div class="space-y-1.5">
          <span class="block text-xs font-medium text-slate-700">Colaborador demitido</span>
          <SeletorColaborador v-model="form.matricula" :colaboradores="colabsContexto" larguraClasse="w-full" @change="escolheuColaborador" />
        </div>
        <label class="block text-xs font-medium text-slate-700 space-y-1.5">
          <span>Data da demissão <span class="text-slate-400 font-normal">(opcional)</span></span>
          <input v-model="form.data_demissao" type="date" class="campo h-11" />
        </label>
        <div class="grid sm:grid-cols-2 gap-3">
          <label class="block text-xs font-medium text-slate-700 space-y-1.5">
            <span>WhatsApp do funcionário</span>
            <input v-model="form.telefone" type="tel" placeholder="(00) 00000-0000" class="campo h-11" />
          </label>
          <label class="block text-xs font-medium text-slate-700 space-y-1.5">
            <span>E-mail do funcionário</span>
            <input v-model="form.email" type="email" placeholder="nome@email.com" class="campo h-11" />
          </label>
        </div>
        <p class="text-[11px] text-slate-400">Os contatos vêm do cadastro. Quem já saiu da empresa costuma ter outro e-mail: confira antes de avisar.</p>
      </template>

      <template #passo-quando>
        <label class="block text-xs font-medium text-slate-700 space-y-1.5">
          <span>Sindicato</span>
          <input v-model="form.sindicato" list="lista-sindicatos" type="text" maxlength="120" placeholder="Ex.: Sindicato dos Trabalhadores da Construção Civil" class="campo h-11" @change="completarLocal" />
          <datalist id="lista-sindicatos"><option v-for="s in sindicatos" :key="s.sindicato" :value="s.sindicato" /></datalist>
        </label>
        <label class="block text-xs font-medium text-slate-700 space-y-1.5">
          <span>Local / endereço <span class="text-slate-400 font-normal">(opcional)</span></span>
          <input v-model="form.local" type="text" maxlength="200" placeholder="Rua, número, bairro, cidade" class="campo h-11" />
        </label>
        <div class="grid sm:grid-cols-2 gap-3">
          <label class="block text-xs font-medium text-slate-700 space-y-1.5">
            <span>Data e hora</span>
            <input v-model="form.data_hora" type="datetime-local" :min="agoraLocal()" class="campo h-11" />
          </label>
          <label class="block text-xs font-medium text-slate-700 space-y-1.5">
            <span>Lembrete automático</span>
            <select v-model.number="form.lembrete_dias" class="campo h-11">
              <option v-for="d in diasLembrete" :key="d" :value="d">{{ d }} {{ d === 1 ? 'dia' : 'dias' }} antes</option>
            </select>
          </label>
        </div>
      </template>

      <template #passo-aviso>
        <div class="space-y-1.5">
          <span class="block text-xs font-medium text-slate-700">Documentos que o funcionário deve levar</span>
          <div class="grid sm:grid-cols-2 gap-2">
            <label v-for="d in opcoesDocs" :key="d" class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 text-sm text-slate-700 cursor-pointer hover:bg-slate-50">
              <input type="checkbox" :value="d" v-model="form.documentos" class="accent-[#9c2337] w-4 h-4" /> {{ d }}
            </label>
          </div>
          <div class="flex gap-2 pt-1">
            <input v-model="outroDoc" type="text" maxlength="120" placeholder="Outro documento" class="campo h-10" @keydown.enter.prevent="adicionarDoc" />
            <button type="button" @click="adicionarDoc" class="h-10 px-4 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer shrink-0">Adicionar</button>
          </div>
        </div>
        <label class="block text-xs font-medium text-slate-700 space-y-1.5">
          <span>Observações <span class="text-slate-400 font-normal">(vão na mensagem)</span></span>
          <textarea v-model="form.observacoes" rows="3" maxlength="1000" placeholder="Ex.: chegar 15 minutos antes; levar acompanhante se quiser" class="campo py-2.5"></textarea>
        </label>
        <label class="flex items-center gap-3 p-4 rounded-xl border-2 text-sm font-medium text-slate-800 cursor-pointer" :class="form.enviar_convite ? 'border-[#9c2337] bg-[#fdf2f4]' : 'border-slate-200'">
          <input type="checkbox" v-model="form.enviar_convite" class="accent-[#9c2337] w-4 h-4" />
          <span>Avisar o funcionário agora por WhatsApp e e-mail
            <span class="block text-xs font-normal text-slate-500">Sem marcar, o agendamento fica como "Agendado" e você pode usar "Reenviar mensagem" depois.</span>
          </span>
        </label>
      </template>

      <template #previa>
        <div class="rounded-2xl bg-[#e7f5dc] border border-emerald-200 p-3 text-xs text-slate-800 whitespace-pre-line leading-relaxed">{{ previaMensagem }}</div>
        <p class="text-[11px] text-slate-400">Assim a mensagem chega no WhatsApp e no e-mail. Depois do convite, o sistema ainda manda um lembrete antes da data e avisa se você remarcar ou cancelar.</p>
      </template>
    </WizardCadastro>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppIcon from './AppIcon.vue';
import ModalCadastro from './ModalCadastro.vue';
import WizardCadastro from './WizardCadastro.vue';
import SeletorColaborador from './SeletorColaborador.vue';
import { pode } from '../utils/permissoes.js';
import { tokenAdmin } from '../utils/sessaoAdmin.js';
import { dataBr, horaBr } from '../utils/textoFormatado.js';
import { doContexto } from '../utils/empresaContexto.js';

defineProps({ colaborador: { type: Object, default: null } });

const abas = [
  { id: 'ativos', rotulo: 'Em andamento' },
  { id: 'realizados', rotulo: 'Realizadas' },
  { id: 'cancelados', rotulo: 'Canceladas' }
];
const abaDoStatus = { agendado: 'ativos', enviado: 'ativos', realizado: 'realizados', cancelado: 'cancelados' };
const situacoes = {
  agendado: { rotulo: 'Agendado', classe: 'bg-sky-50 text-sky-700 border-sky-200' },
  enviado: { rotulo: 'Aviso enviado', classe: 'bg-amber-50 text-amber-800 border-amber-200' },
  realizado: { rotulo: 'Realizado', classe: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
  cancelado: { rotulo: 'Cancelado', classe: 'bg-slate-100 text-slate-500 border-slate-200' }
};
const rotuloEnvio = { convite: 'Convite', lembrete: 'Lembrete', remarcacao: 'Aviso de remarcação', cancelamento: 'Aviso de cancelamento', reenvio: 'Reenvio' };
const statusCanal = (s) => ({ ENFILEIRADO: 'na fila', ENTREGUE: 'entregue', FALHOU: 'falhou', SEM_DESTINO: 'sem contato' }[s] || s);
const MESES = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'];
const mesCurto = (d) => MESES[Number(d.slice(5, 7)) - 1];
const ativo = (h) => h.status === 'agendado' || h.status === 'enviado';
const ultimoEnvio = (h) => h.envios[h.envios.length - 1] || null;

const lista = ref([]);
const colaboradores = ref([]);
const sindicatos = ref([]);
const docsPadrao = ref([]);
const diasLembrete = ref([1, 2, 3, 5, 7]);
const carregando = ref(true);
const mensagem = ref('');
const mensagemErro = ref(false);
const aba = ref('ativos');
const busca = ref('');
const detalhes = ref(null);
const ocupado = ref('');

const colabsContexto = computed(() => colaboradores.value.filter((c) => doContexto(c.empresa)));
const listaCtx = computed(() => lista.value.filter((h) => doContexto(h.empresa)));
const contagem = computed(() => {
  const n = { ativos: 0, realizados: 0, cancelados: 0 };
  listaCtx.value.forEach((h) => { n[abaDoStatus[h.status]]++; });
  return n;
});
const visiveis = computed(() => {
  const q = busca.value.trim().toLowerCase();
  return listaCtx.value.filter((h) => (q ? true : abaDoStatus[h.status] === aba.value) && (!q || `${h.colaborador_nome} ${h.matricula} ${h.sindicato}`.toLowerCase().includes(q)));
});

const auth = () => ({ Authorization: `Bearer ${tokenAdmin()}` });
const json = { 'Content-Type': 'application/json' };
function aviso(texto, erro = false) { mensagem.value = texto; mensagemErro.value = erro; }
const pad = (n) => String(n).padStart(2, '0');
function agoraLocal() {
  const d = new Date();
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

async function carregar() {
  try {
    const res = await fetch('/api/rh/homologacoes', { headers: auth() });
    const data = await res.json();
    if (!res.ok) return aviso(data.erro || 'Não foi possível carregar os agendamentos.', true);
    lista.value = data.homologacoes;
    colaboradores.value = data.colaboradores;
    sindicatos.value = data.sindicatos;
    docsPadrao.value = data.documentos_padrao;
    diasLembrete.value = data.lembrete_dias;
  } finally {
    carregando.value = false;
  }
}

async function chamar(rota, corpo) {
  const res = await fetch(rota, { method: 'POST', headers: { ...json, ...auth() }, body: JSON.stringify(corpo) });
  const data = await res.json();
  if (!res.ok) throw new Error(data.erro || 'Não foi possível concluir.');
  return data;
}

// ---------- Novo agendamento (wizard) ----------
const passos = [
  { id: 'colaborador', titulo: 'Colaborador', descricao: 'Quem foi demitido e para onde vão os avisos.' },
  { id: 'quando', titulo: 'Sindicato e data', descricao: 'Onde e quando será a homologação.' },
  { id: 'aviso', titulo: 'Documentos e aviso', descricao: 'O que levar e como avisar o funcionário.' }
];
const vazio = () => ({ matricula: '', data_demissao: '', telefone: '', email: '', sindicato: '', local: '', data_hora: '', lembrete_dias: 2, documentos: [], observacoes: '', enviar_convite: true });
const form = ref(vazio());
const original = ref('');
const wizardAberto = ref(false);
const passo = ref(0);
const enviando = ref(false);
const erroWizard = ref('');
const outroDoc = ref('');
const extrasDocs = ref([]);

const opcoesDocs = computed(() => [...docsPadrao.value, ...extrasDocs.value]);
const sujo = computed(() => JSON.stringify(form.value) !== original.value);

function abrirNovo() {
  form.value = vazio();
  form.value.documentos = docsPadrao.value.slice(0, 4);
  original.value = JSON.stringify(form.value);
  extrasDocs.value = [];
  outroDoc.value = '';
  erroWizard.value = '';
  passo.value = 0;
  wizardAberto.value = true;
}
function fecharWizard() { wizardAberto.value = false; }
function escolheuColaborador() {
  const c = colaboradores.value.find((x) => x.matricula === form.value.matricula);
  if (c) { form.value.telefone = c.telefone || ''; form.value.email = c.email || ''; }
}
function completarLocal() {
  const s = sindicatos.value.find((x) => x.sindicato.toLowerCase() === form.value.sindicato.trim().toLowerCase());
  if (s && !form.value.local) form.value.local = s.local;
}
function adicionarDoc() {
  const d = outroDoc.value.trim();
  if (!d) return;
  if (!opcoesDocs.value.includes(d)) extrasDocs.value.push(d);
  if (!form.value.documentos.includes(d)) form.value.documentos.push(d);
  outroDoc.value = '';
}

function validar(id) {
  const f = form.value;
  if (id === 'colaborador') {
    if (!f.matricula) return 'Escolha o colaborador demitido.';
    if (!f.telefone.replace(/\D/g, '') && !f.email.trim()) return 'Informe o WhatsApp ou o e-mail para avisar o funcionário.';
    if (f.email.trim() && !/^\S+@\S+\.\S+$/.test(f.email.trim())) return 'O e-mail informado não parece válido.';
  }
  if (id === 'quando') {
    if (!f.sindicato.trim()) return 'Informe o sindicato.';
    if (!f.data_hora) return 'Informe a data e a hora.';
    if (new Date(f.data_hora).getTime() <= Date.now()) return 'A data e hora precisam ser futuras.';
  }
  return '';
}

async function salvar() {
  enviando.value = true;
  erroWizard.value = '';
  try {
    const data = await chamar('/api/rh/homologacoes', form.value);
    wizardAberto.value = false;
    const h = data.homologacao;
    aviso(form.value.enviar_convite
      ? `Homologação de ${h.colaborador_nome} agendada. Convite registrado para WhatsApp e e-mail.`
      : `Homologação de ${h.colaborador_nome} agendada (aviso ainda não enviado).`);
    aba.value = 'ativos';
    await carregar();
  } catch (e) {
    erroWizard.value = e.message;
  } finally {
    enviando.value = false;
  }
}

const previaMensagem = computed(() => {
  const f = form.value;
  const c = colaboradores.value.find((x) => x.matricula === f.matricula);
  const nome = c ? c.nome.split(' ')[0] : 'Nome';
  const quando = f.data_hora ? `${dataBr(f.data_hora)} às ${horaBr(f.data_hora.replace('T', ' '))}` : '--/--/---- às --:--';
  let t = `Olá, ${nome}! Sua homologação sindical foi agendada. Ela é necessária para concluir o seu desligamento da Vila Boa Construções.\n\n📅 Quando: ${quando}\n📍 Onde: ${f.sindicato || 'Sindicato'}${f.local ? ' (' + f.local + ')' : ''}`;
  if (f.documentos.length) t += `\n\n📄 Leve os documentos:\n${f.documentos.map((d) => '• ' + d).join('\n')}`;
  if (f.observacoes.trim()) t += `\n\nℹ️ Observações: ${f.observacoes.trim()}`;
  return t;
});

// ---------- Ações da lista ----------
const salvandoAcao = ref(false);
const erroAcao = ref('');
const paraRemarcar = ref(null);
const paraCancelar = ref(null);
const paraRemover = ref(null);
const novaData = ref({ data_hora: '', sindicato: '', local: '', motivo: '' });
const motivoCancelar = ref('');

async function reenviar(h) {
  ocupado.value = h.id;
  try {
    await chamar('/api/rh/homologacoes/acao', { id: h.id, acao: 'reenviar' });
    aviso(`Mensagem reenviada para ${h.colaborador_nome} (WhatsApp e e-mail).`);
    await carregar();
  } catch (e) { aviso(e.message, true); } finally { ocupado.value = ''; }
}
async function acaoSimples(h, acao) {
  try {
    await chamar('/api/rh/homologacoes/acao', { id: h.id, acao });
    aviso('Homologação marcada como realizada.');
    await carregar();
  } catch (e) { aviso(e.message, true); }
}
function abrirRemarcar(h) {
  erroAcao.value = '';
  novaData.value = { data_hora: '', sindicato: h.sindicato, local: h.local, motivo: '' };
  paraRemarcar.value = h;
}
async function remarcar() {
  salvandoAcao.value = true;
  erroAcao.value = '';
  try {
    await chamar('/api/rh/homologacoes/acao', { id: paraRemarcar.value.id, acao: 'remarcar', ...novaData.value });
    aviso(`Homologação de ${paraRemarcar.value.colaborador_nome} remarcada. Aviso registrado para WhatsApp e e-mail.`);
    paraRemarcar.value = null;
    await carregar();
  } catch (e) { erroAcao.value = e.message; } finally { salvandoAcao.value = false; }
}
function abrirCancelar(h) { erroAcao.value = ''; motivoCancelar.value = ''; paraCancelar.value = h; }
async function cancelar() {
  salvandoAcao.value = true;
  erroAcao.value = '';
  try {
    await chamar('/api/rh/homologacoes/acao', { id: paraCancelar.value.id, acao: 'cancelar', motivo: motivoCancelar.value });
    aviso(`Homologação de ${paraCancelar.value.colaborador_nome} cancelada. Aviso registrado para WhatsApp e e-mail.`);
    paraCancelar.value = null;
    await carregar();
  } catch (e) { erroAcao.value = e.message; } finally { salvandoAcao.value = false; }
}
async function remover() {
  salvandoAcao.value = true;
  try {
    await chamar('/api/rh/homologacoes/remover', { id: paraRemover.value.id });
    aviso('Agendamento removido.');
    paraRemover.value = null;
    await carregar();
  } catch (e) { aviso(e.message, true); paraRemover.value = null; } finally { salvandoAcao.value = false; }
}

onMounted(carregar);
</script>

<style scoped>
.selo { font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 9999px; border: 1px solid; }
.acao { height: 2rem; padding: 0 0.75rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; color: #475569; cursor: pointer; }
.acao:hover { background: #f1f5f9; }
.acao:disabled { opacity: 0.5; cursor: wait; }
.campo { width: 100%; padding: 0 0.75rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 0.875rem; outline: none; }
.campo:focus { background: #fff; border-color: #94a3b8; }
</style>
