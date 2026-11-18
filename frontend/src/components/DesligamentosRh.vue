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
    <p class="text-[11px] text-slate-400">Links: <a :href="links.homologacao" target="_blank" rel="noopener" class="underline">homologação com o DP</a> · <a :href="links.documentos" target="_blank" rel="noopener" class="underline">retirada de documentos</a></p>

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
import { ref, computed, onMounted } from 'vue';
import ModalCadastro from './ModalCadastro.vue';
import SeletorColaborador from './SeletorColaborador.vue';
import { pode } from '../utils/permissoes.js';
import { tokenAdmin } from '../utils/sessaoAdmin.js';
import { doContexto } from '../utils/empresaContexto.js';

defineProps({ colaborador: { type: Object, required: true } });

const lista = ref([]);
const colaboradores = ref([]);
const links = ref({});
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
    links.value = data.links;
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
