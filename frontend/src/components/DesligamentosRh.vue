<template>
  <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800">Aviso de desligamento no portal</h3>
        <p class="text-xs text-slate-500 mt-0.5 max-w-2xl">Na data de demissão, o colaborador vê um aviso no portal com os links para agendar a homologação com o DP e a retirada da documentação, e o Guia de Desligamento da empresa. O aviso some quando ele confirma os dois agendamentos.</p>
      </div>
      <button v-if="pode(colaborador, 'rh_cadernos', 'publicar')" type="button" @click="abrirNovo" class="h-11 px-6 rounded-full border border-slate-300 hover:bg-slate-50 text-slate-800 text-sm font-semibold cursor-pointer">+ Registrar desligamento</button>
    </div>
    <p v-if="mensagem" class="text-xs font-medium" :class="mensagemErro ? 'text-rose-700' : 'text-emerald-700'">{{ mensagem }}</p>

    <div v-if="carregando" class="py-6 text-center text-sm text-slate-400">Carregando...</div>
    <p v-else-if="!listaCtx.length" class="text-sm text-slate-500 py-2">Nenhum desligamento registrado.</p>
    <div v-else class="overflow-x-auto -mx-5 sm:-mx-6">
      <table class="w-full text-sm min-w-[640px]">
        <thead>
          <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-100">
            <th class="px-5 sm:px-6 py-2 font-semibold">Colaborador</th>
            <th class="px-3 py-2 font-semibold">Demissão</th>
            <th class="px-3 py-2 font-semibold">Homologação</th>
            <th class="px-3 py-2 font-semibold">Documentos</th>
            <th class="px-5 sm:px-6 py-2"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in listaCtx" :key="d.matricula" class="border-b border-slate-50 last:border-0 align-top">
            <td class="px-5 sm:px-6 py-3">
              <p class="font-semibold text-slate-900">{{ d.nome }}</p>
              <p class="text-xs text-slate-500">{{ d.matricula }} · {{ d.empresa }}</p>
            </td>
            <td class="px-3 py-3 whitespace-nowrap">
              <p class="text-slate-800">{{ dataBr(d.data_demissao) }}</p>
              <p class="text-[11px]" :class="d.aviso_ativo ? 'text-[#9c2337] font-semibold' : 'text-slate-400'">{{ situacaoAviso(d) }}</p>
            </td>
            <td class="px-3 py-3"><span class="selo" :class="d.homologacao.agendado ? 'ok' : 'pendente'">{{ d.homologacao.agendado ? (d.homologacao.pelo_rh ? 'Agendada pelo RH' : 'Agendada') : 'Pendente' }}</span></td>
            <td class="px-3 py-3"><span class="selo" :class="d.documentos.agendado ? 'ok' : 'pendente'">{{ d.documentos.agendado ? 'Agendada' : 'Pendente' }}</span></td>
            <td class="px-5 sm:px-6 py-3 text-right whitespace-nowrap">
              <button v-if="pode(colaborador, 'rh_cadernos', 'editar') && (d.homologacao.agendado_em || d.documentos.agendado)" type="button" @click="acao(d, 'reabrir')" class="acao">Reabrir aviso</button>
              <button v-if="d.origem === 'rh' && pode(colaborador, 'rh_cadernos', 'remover')" type="button" @click="acao(d, 'remover')" class="acao hover:!text-rose-700">Remover</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <!-- Links do Bookings e Guia de Desligamento da empresa escolhida no painel -->
    <div v-if="config" class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 sm:p-5 space-y-4">
      <div>
        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-700">Links e guia · {{ empresaAtual }}</h4>
        <p class="text-xs text-slate-500 mt-0.5">O que o colaborador desta empresa vê no aviso. Link em branco usa o padrão.</p>
      </div>
      <p v-if="msgConfig" class="text-xs font-medium" :class="msgConfigErro ? 'text-rose-700' : 'text-emerald-700'">{{ msgConfig }}</p>

      <form class="grid grid-cols-1 lg:grid-cols-2 gap-3" @submit.prevent="salvarLinks">
        <label v-for="t in tiposLink" :key="t.id" class="block min-w-0">
          <span class="flex items-center justify-between gap-2 text-xs font-medium text-slate-600 mb-1">
            <span>{{ t.rotulo }}</span>
            <a :href="config.links[t.id]" target="_blank" rel="noopener" class="text-[11px] font-semibold text-[#9c2337] hover:underline">Testar link</a>
          </span>
          <input v-model="formLinks[t.id]" type="url" inputmode="url" :disabled="!podeEditar" :placeholder="config.links_padrao[t.id] + ' (padrão)'" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-white text-sm outline-none focus:border-slate-400 disabled:bg-slate-50" />
        </label>
        <div v-if="podeEditar" class="lg:col-span-2 flex justify-end">
          <button type="submit" :disabled="salvandoLinks || !linksMudaram" class="h-10 px-5 rounded-full bg-[#9c2337] hover:bg-[#831b2d] disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold cursor-pointer">{{ salvandoLinks ? 'Salvando...' : 'Salvar links' }}</button>
        </div>
      </form>

      <div class="pt-4 border-t border-slate-200 flex flex-wrap items-center gap-3">
        <span class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-[#9c2337] shrink-0"><AppIcon name="document" size="18" /></span>
        <div class="flex-1 min-w-[180px]">
          <p class="text-sm font-semibold text-slate-800">Guia de Desligamento</p>
          <p class="text-xs text-slate-500">{{ descricaoGuia }}</p>
        </div>
        <div class="flex flex-wrap gap-1.5">
          <button v-if="config.guia.tem" type="button" @click="verGuia" class="acao border border-slate-200 bg-white">Ver guia</button>
          <template v-if="podeEditar">
            <label class="acao border border-slate-200 bg-white inline-flex items-center" :class="enviandoGuia ? 'opacity-50 pointer-events-none' : ''">
              {{ enviandoGuia ? 'Enviando...' : (config.guia.tem ? 'Trocar PDF' : 'Enviar PDF') }}
              <input type="file" accept="application/pdf,.pdf" class="sr-only" @change="enviarGuia" />
            </label>
            <button v-if="config.guia.enviado" type="button" @click="removerGuia" class="acao border border-slate-200 bg-white hover:!text-rose-700">Remover enviado</button>
          </template>
        </div>
      </div>
    </div>

    <ModalCadastro :aberto="novoAberto" titulo="Registrar desligamento" descricao="Na data informada, o aviso aparece para o colaborador no portal." rotulo-salvar="Registrar" :enviando="salvando" :erro="erroNovo" :pode-salvar="!!form.matricula && !!form.data_demissao" :sujo="false" @fechar="novoAberto = false" @salvar="registrar">
      <div class="space-y-4">
        <label class="block">
          <span class="block text-xs font-medium text-slate-600 mb-1">Colaborador</span>
          <SeletorColaborador v-model="form.matricula" :colaboradores="colabsContexto" larguraClasse="w-full" />
        </label>
        <label class="block">
          <span class="block text-xs font-medium text-slate-600 mb-1">Data da demissão</span>
          <input v-model="form.data_demissao" type="date" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
        </label>
      </div>
    </ModalCadastro>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import AppIcon from './AppIcon.vue';
import ModalCadastro from './ModalCadastro.vue';
import SeletorColaborador from './SeletorColaborador.vue';
import { pode } from '../utils/permissoes.js';
import { tokenAdmin } from '../utils/sessaoAdmin.js';
import { doContexto, empresaContexto } from '../utils/empresaContexto.js';

const props = defineProps({ colaborador: { type: Object, required: true } });

const lista = ref([]);
const colaboradores = ref([]);
const configuracoes = ref({});
const limiteGuia = ref(0);
const carregando = ref(true);
const mensagem = ref('');
const mensagemErro = ref(false);
const novoAberto = ref(false);
const salvando = ref(false);
const erroNovo = ref('');
const form = ref({ matricula: '', data_demissao: '' });

const listaCtx = computed(() => lista.value.filter((d) => doContexto(d.empresa)));
const colabsContexto = computed(() => colaboradores.value.filter((c) => doContexto(c.empresa)));
const auth = () => ({ Authorization: `Bearer ${tokenAdmin()}` });
const dataBr = (iso) => (iso ? iso.slice(0, 10).split('-').reverse().join('/') : '');
const hoje = () => new Date().toLocaleDateString('sv-SE', { timeZone: 'America/Sao_Paulo' });
function situacaoAviso(d) {
  if (d.aviso_ativo) return 'Aviso aparecendo no portal';
  if (d.data_demissao > hoje()) return 'Aviso a partir desta data';
  return 'Agendamentos concluídos';
}
function aviso(texto, erro = false) { mensagem.value = texto; mensagemErro.value = erro; }

async function carregar() {
  try {
    const res = await fetch('/api/rh/desligamentos', { headers: auth() });
    const data = await res.json();
    if (!res.ok) return aviso(data.erro || 'Não foi possível carregar os desligamentos.', true);
    lista.value = data.desligamentos;
    colaboradores.value = data.colaboradores;
    configuracoes.value = data.configuracoes || {};
    limiteGuia.value = data.limite_guia || 0;
    preencherLinks();
  } finally {
    carregando.value = false;
  }
}

async function chamar(rota, corpo) {
  const res = await fetch(rota, { method: 'POST', headers: { 'Content-Type': 'application/json', ...auth() }, body: JSON.stringify(corpo) });
  const data = await res.json();
  if (!res.ok) throw new Error(data.erro || 'Não foi possível concluir.');
  return data;
}

function abrirNovo() {
  form.value = { matricula: '', data_demissao: hoje() };
  erroNovo.value = '';
  novoAberto.value = true;
}

async function registrar() {
  salvando.value = true;
  erroNovo.value = '';
  try {
    await chamar('/api/rh/desligamentos', form.value);
    novoAberto.value = false;
    aviso('Desligamento registrado.');
    await carregar();
  } catch (e) {
    erroNovo.value = e.message;
  } finally {
    salvando.value = false;
  }
}

async function acao(d, tipo) {
  try {
    await chamar('/api/rh/desligamentos/acao', { matricula: d.matricula, acao: tipo });
    aviso(tipo === 'remover' ? 'Desligamento removido.' : 'Aviso reaberto para o colaborador.');
    await carregar();
  } catch (e) {
    aviso(e.message, true);
  }
}

// ---------------- Links e guia por empresa ----------------
const tiposLink = [
  { id: 'homologacao', rotulo: 'Agendar homologação com o DP' },
  { id: 'documentos', rotulo: 'Agendar retirada de documentos' }
];
const podeEditar = computed(() => pode(props.colaborador, 'rh_cadernos', 'editar'));
const empresaAtual = computed(() => empresaContexto.value || Object.keys(configuracoes.value)[0] || '');
const config = computed(() => configuracoes.value[empresaAtual.value] || null);
const formLinks = ref({ homologacao: '', documentos: '' });
const salvandoLinks = ref(false);
const enviandoGuia = ref(false);
const msgConfig = ref('');
const msgConfigErro = ref(false);
function avisoConfig(texto, erro = false) { msgConfig.value = texto; msgConfigErro.value = erro; }

// Mostra no campo só o link próprio da empresa; o padrão fica no placeholder
function valoresSalvos() {
  const c = config.value;
  if (!c) return { homologacao: '', documentos: '' };
  return Object.fromEntries(tiposLink.map((t) => [t.id, c.links_personalizados[t.id] ? c.links[t.id] : '']));
}
function preencherLinks() { formLinks.value = valoresSalvos(); }
watch(empresaAtual, () => { preencherLinks(); msgConfig.value = ''; });
const linksMudaram = computed(() => JSON.stringify(formLinks.value) !== JSON.stringify(valoresSalvos()));

const tamanho = (b) => (b >= 1048576 ? `${Math.round(b / 104857.6) / 10} MB` : `${Math.round(b / 1024)} KB`);
const descricaoGuia = computed(() => {
  const g = config.value?.guia;
  if (!g?.tem) return 'Nenhum guia para esta empresa: o colaborador vê o guia da Vila Boa.';
  if (g.enviado) return `Enviado pelo RH em ${g.atualizado_em.slice(8, 10)}/${g.atualizado_em.slice(5, 7)}/${g.atualizado_em.slice(0, 4)}.`;
  return 'Guia que veio com o sistema. Envie um PDF para trocar.';
});

async function salvarLinks() {
  salvandoLinks.value = true;
  try {
    const data = await chamar('/api/rh/desligamentos/links', { empresa: empresaAtual.value, ...formLinks.value });
    configuracoes.value = data.configuracoes;
    preencherLinks();
    avisoConfig(`Links da ${empresaAtual.value} salvos.`);
  } catch (e) {
    avisoConfig(e.message, true);
  } finally {
    salvandoLinks.value = false;
  }
}

async function enviarGuia(ev) {
  const arquivo = ev.target.files?.[0];
  ev.target.value = '';
  if (!arquivo) return;
  if (limiteGuia.value && arquivo.size > limiteGuia.value) {
    return avisoConfig(`O PDF tem ${tamanho(arquivo.size)} e o servidor aceita até ${tamanho(limiteGuia.value)}. Peça para a TI aumentar o limite de envio do PHP ou reduza o PDF.`, true);
  }
  enviandoGuia.value = true;
  try {
    const corpo = new FormData();
    corpo.append('empresa', empresaAtual.value);
    corpo.append('guia', arquivo);
    const res = await fetch('/api/rh/desligamentos/guia', { method: 'POST', headers: auth(), body: corpo });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.erro || 'Não foi possível enviar o guia.');
    configuracoes.value = data.configuracoes;
    avisoConfig(`Guia da ${empresaAtual.value} atualizado. O colaborador já recebe o novo PDF.`);
  } catch (e) {
    avisoConfig(e.message, true);
  } finally {
    enviandoGuia.value = false;
  }
}

async function removerGuia() {
  if (!window.confirm(`Remover o guia enviado da ${empresaAtual.value}? Volta a valer o guia que veio com o sistema, se houver.`)) return;
  try {
    const data = await chamar('/api/rh/desligamentos/guia/remover', { empresa: empresaAtual.value });
    configuracoes.value = data.configuracoes;
    avisoConfig('Guia enviado removido.');
  } catch (e) {
    avisoConfig(e.message, true);
  }
}

// Abre o PDF numa aba nova (aberta já no clique para o navegador não bloquear)
async function verGuia() {
  const aba = window.open('', '_blank');
  try {
    const res = await fetch(`/api/rh/desligamentos/guia?empresa=${encodeURIComponent(empresaAtual.value)}`, { headers: auth() });
    if (!res.ok) throw new Error();
    const url = URL.createObjectURL(await res.blob());
    if (aba) aba.location.href = url;
    else window.location.href = url;
  } catch (e) {
    aba?.close();
    avisoConfig('Não foi possível abrir o guia.', true);
  }
}

onMounted(carregar);
defineExpose({ carregar });
</script>

<style scoped>
.selo { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; border: 1px solid; }
.selo.ok { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
.selo.pendente { background: #fffbeb; color: #b45309; border-color: #fde68a; }
.acao { height: 32px; padding: 0 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; color: #475569; cursor: pointer; }
.acao:hover { background: #f1f5f9; }
</style>
