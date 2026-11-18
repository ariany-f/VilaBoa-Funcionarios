<template>
  <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800">Colaboradores</h3>
        <p class="text-xs text-slate-500 mt-0.5 max-w-2xl">
          Quem acessa o portal. O colaborador entra com CPF e data de nascimento e confirma uma pergunta de segurança
          (ano de admissão, RG, nome da mãe, cargo ou nascimento), por isso esses dados precisam estar certos.
          Inativar bloqueia o acesso ao portal sem apagar o histórico.
        </p>
      </div>
      <button v-if="podeCadastrar" type="button" @click="abrirNovo" class="h-11 px-6 rounded-full border border-slate-300 hover:bg-slate-50 text-slate-800 text-sm font-semibold cursor-pointer">+ Novo colaborador</button>
    </div>
    <p v-if="mensagem" class="text-xs font-medium" :class="mensagemErro ? 'text-rose-700' : 'text-emerald-700'">{{ mensagem }}</p>

    <div class="flex flex-wrap gap-2">
      <div class="flex items-center h-10 flex-1 min-w-[200px] max-w-sm rounded-xl border border-slate-200 bg-slate-50 focus-within:bg-white focus-within:border-slate-400">
        <AppIcon name="search" size="14" class="text-slate-400 ml-3 shrink-0" />
        <input v-model="busca" type="text" placeholder="Buscar nome, matrícula, CPF ou cargo" class="flex-1 min-w-0 h-full px-2.5 bg-transparent text-sm outline-none" />
      </div>
      <select v-model="filtroSituacao" class="h-10 px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm outline-none focus:border-slate-400 cursor-pointer" aria-label="Situação">
        <option value="ativos">Ativos</option>
        <option value="inativos">Inativos</option>
        <option value="">Todos</option>
      </select>
    </div>

    <div v-if="carregando" class="py-6 text-center text-sm text-slate-400">Carregando...</div>
    <p v-else-if="!filtrados.length" class="text-sm text-slate-500 py-2">{{ listaCtx.length ? 'Nenhum colaborador com esses filtros.' : 'Nenhum colaborador cadastrado nesta empresa.' }}</p>
    <div v-else class="overflow-x-auto -mx-5 sm:-mx-6">
      <table class="w-full text-sm min-w-[720px]">
        <thead>
          <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-100">
            <th class="px-5 sm:px-6 py-2 font-semibold">Colaborador</th>
            <th class="px-3 py-2 font-semibold">CPF</th>
            <th class="px-3 py-2 font-semibold">Cargo</th>
            <th class="px-3 py-2 font-semibold">Telefone</th>
            <th class="px-3 py-2 font-semibold">Situação</th>
            <th class="px-5 sm:px-6 py-2"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in filtrados" :key="c.matricula" class="border-b border-slate-50 last:border-0 align-top" :class="c.ativo ? '' : 'opacity-70'">
            <td class="px-5 sm:px-6 py-3">
              <p class="font-semibold text-slate-900">{{ c.nome }}</p>
              <p class="text-xs text-slate-500">{{ c.matricula }}<template v-if="c.departamento"> · {{ c.departamento }}</template></p>
            </td>
            <td class="px-3 py-3 whitespace-nowrap tabular-nums text-slate-700">{{ c.cpf }}</td>
            <td class="px-3 py-3 text-slate-700">{{ c.cargo || '—' }}</td>
            <td class="px-3 py-3 whitespace-nowrap">
              <p class="text-slate-700 tabular-nums">{{ c.telefone_atual || '—' }}</p>
              <p v-if="c.telefone_atual" class="text-[11px]" :class="c.telefone_confirmado_em ? 'text-emerald-700' : 'text-amber-700'">{{ c.telefone_confirmado_em ? 'Confirmado' : 'Aguardando confirmação' }}</p>
            </td>
            <td class="px-3 py-3">
              <span class="selo" :class="c.ativo ? 'ok' : 'inativo'">{{ c.ativo ? 'Ativo' : 'Inativo' }}</span>
              <p v-if="c.data_demissao" class="text-[11px] text-slate-400 mt-1">Demissão {{ dataBr(c.data_demissao) }}</p>
            </td>
            <td class="px-5 sm:px-6 py-3 text-right whitespace-nowrap">
              <button type="button" @click="abrirEdicao(c)" class="acao">{{ podeEditar ? 'Editar' : 'Ver' }}</button>
              <button v-if="podeInativar" type="button" @click="alternarAtivo(c)" class="acao" :class="c.ativo ? 'hover:!text-rose-700' : ''">{{ c.ativo ? 'Inativar' : 'Reativar' }}</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-if="listaCtx.length" class="text-[11px] text-slate-400">{{ listaCtx.filter((c) => c.ativo).length }} ativo(s) · {{ listaCtx.filter((c) => !c.ativo).length }} inativo(s)</p>

    <ModalCadastro
      :aberto="formAberto"
      :titulo="novo ? 'Novo colaborador' : (somenteLeitura ? 'Dados do colaborador' : 'Editar colaborador')"
      :descricao="novo ? 'Depois de salvo, já dá para entrar no portal com CPF e data de nascimento.' : `Matrícula ${form.matricula}`"
      tamanho="lg"
      :rotulo-salvar="novo ? 'Cadastrar' : 'Salvar alterações'"
      :enviando="salvando"
      :erro="erroForm"
      :pode-salvar="!somenteLeitura && podeSalvar"
      :sujo="!somenteLeitura && sujo"
      @fechar="formAberto = false"
      @salvar="salvar"
    >
      <fieldset :disabled="somenteLeitura" class="space-y-5 min-w-0">
        <div>
          <p class="secao">Identificação</p>
          <div class="grid grid-cols-1 sm:grid-cols-6 gap-3">
            <label class="block sm:col-span-4"><span class="rotulo">Nome completo *</span><input v-model="form.nome" required class="campo" /></label>
            <label class="block sm:col-span-2">
              <span class="rotulo">Matrícula *</span>
              <input v-model="form.matricula" :disabled="!novo" required placeholder="VB-0000" class="campo uppercase" />
            </label>
            <label class="block sm:col-span-2"><span class="rotulo">CPF *</span><input :value="form.cpf" @input="form.cpf = maskCPF($event.target.value)" inputmode="numeric" placeholder="000.000.000-00" required class="campo" /></label>
            <label class="block sm:col-span-2"><span class="rotulo">Data de nascimento *</span><input v-model="form.data_nascimento" type="date" required class="campo" /></label>
            <label class="block sm:col-span-2">
              <span class="rotulo">Empresa *</span>
              <select v-model="form.empresa" required class="campo cursor-pointer">
                <option v-for="e in empresas" :key="e" :value="e">{{ e }}</option>
              </select>
            </label>
          </div>
        </div>

        <div>
          <p class="secao">Trabalho</p>
          <div class="grid grid-cols-1 sm:grid-cols-6 gap-3">
            <label class="block sm:col-span-3"><span class="rotulo">Cargo</span><input v-model="form.cargo" class="campo" /></label>
            <label class="block sm:col-span-3"><span class="rotulo">Departamento</span><input v-model="form.departamento" list="colab-departamentos" class="campo" /></label>
            <label class="block sm:col-span-3"><span class="rotulo">Data de admissão *</span><input v-model="form.data_admissao" type="date" required class="campo" /></label>
            <label class="block sm:col-span-3">
              <span class="rotulo">Data de demissão</span>
              <input v-model="form.data_demissao" type="date" class="campo" />
              <span class="block text-[11px] text-slate-400 mt-1">Na data, o portal mostra o aviso de desligamento.</span>
            </label>
          </div>
          <datalist id="colab-departamentos"><option v-for="d in departamentos" :key="d" :value="d" /></datalist>
        </div>

        <div>
          <p class="secao">Segurança do login e contato</p>
          <div class="grid grid-cols-1 sm:grid-cols-6 gap-3">
            <label class="block sm:col-span-2"><span class="rotulo">3 primeiros dígitos do RG *</span><input v-model="form.rg_primeiros_3_digitos" inputmode="numeric" maxlength="3" required class="campo" /></label>
            <label class="block sm:col-span-4"><span class="rotulo">Nome da mãe</span><input v-model="form.nome_mae" class="campo" /></label>
            <label class="block sm:col-span-3">
              <span class="rotulo">Telefone / WhatsApp</span>
              <input :value="form.telefone_atual" @input="form.telefone_atual = maskPhone($event.target.value)" inputmode="tel" placeholder="(00) 00000-0000" class="campo" />
              <span v-if="!novo && telefoneMudou" class="block text-[11px] text-amber-700 mt-1">O colaborador vai confirmar o novo número no próximo acesso.</span>
            </label>
            <label class="block sm:col-span-3"><span class="rotulo">E-mail</span><input v-model="form.email" type="email" class="campo" /></label>
          </div>
        </div>
      </fieldset>
      <template #rodape-esquerda>
        <span v-if="!novo && original && JSON.parse(original).alterado_em">Alterado em {{ dataHoraBr(JSON.parse(original).alterado_em) }}</span>
      </template>
    </ModalCadastro>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppIcon from './AppIcon.vue';
import ModalCadastro from './ModalCadastro.vue';
import { pode } from '../utils/permissoes.js';
import { tokenAdmin } from '../utils/sessaoAdmin.js';
import { doContexto, empresaContexto } from '../utils/empresaContexto.js';
import { maskCPF, maskPhone } from '../utils/masks.js';

const props = defineProps({ colaborador: { type: Object, required: true } });

const podeCadastrar = computed(() => pode(props.colaborador, 'rh_colaboradores', 'cadastrar'));
const podeEditar = computed(() => pode(props.colaborador, 'rh_colaboradores', 'editar'));
const podeInativar = computed(() => pode(props.colaborador, 'rh_colaboradores', 'inativar'));

const lista = ref([]);
const empresas = ref([]);
const carregando = ref(true);
const mensagem = ref('');
const mensagemErro = ref(false);
const busca = ref('');
const filtroSituacao = ref('ativos');

const auth = () => ({ Authorization: `Bearer ${tokenAdmin()}` });
const semAcento = (s) => String(s ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
const dataBr = (iso) => (iso ? iso.slice(0, 10).split('-').reverse().join('/') : '');
const dataHoraBr = (s) => (s ? `${dataBr(s)} ${s.slice(11, 16)}` : '');
function aviso(texto, erro = false) { mensagem.value = texto; mensagemErro.value = erro; }

const listaCtx = computed(() => lista.value.filter((c) => doContexto(c.empresa)));
const departamentos = computed(() => [...new Set(lista.value.map((c) => c.departamento).filter(Boolean))].sort());
const filtrados = computed(() => {
  const termo = semAcento(busca.value.trim());
  const digitos = busca.value.replace(/\D/g, '');
  return listaCtx.value.filter((c) => {
    if (filtroSituacao.value === 'ativos' && !c.ativo) return false;
    if (filtroSituacao.value === 'inativos' && c.ativo) return false;
    if (!termo) return true;
    return [c.nome, c.matricula, c.cargo, c.departamento].some((t) => semAcento(t).includes(termo))
      || (digitos.length >= 3 && c.cpf.replace(/\D/g, '').includes(digitos));
  });
});

async function carregar() {
  try {
    const res = await fetch('/api/rh/colaboradores', { headers: auth() });
    const data = await res.json();
    if (!res.ok) return aviso(data.erro || 'Não foi possível carregar os colaboradores.', true);
    lista.value = data.colaboradores;
    empresas.value = data.empresas;
  } catch (e) {
    aviso('Não foi possível carregar os colaboradores.', true);
  } finally {
    carregando.value = false;
  }
}

async function chamar(rota, corpo) {
  const res = await fetch(rota, { method: 'POST', headers: { 'Content-Type': 'application/json', ...auth() }, body: JSON.stringify(corpo) });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.erro || 'Não foi possível concluir.');
  return data;
}

// ---------------- Formulário ----------------
const CAMPOS = ['matricula', 'nome', 'cpf', 'data_nascimento', 'empresa', 'cargo', 'departamento', 'data_admissao', 'data_demissao', 'rg_primeiros_3_digitos', 'nome_mae', 'telefone_atual', 'email'];
const formAberto = ref(false);
const novo = ref(false);
const salvando = ref(false);
const erroForm = ref('');
const form = ref({});
const original = ref('');
const somenteLeitura = computed(() => !novo.value && !podeEditar.value);
const sujo = computed(() => formAberto.value && JSON.stringify(Object.fromEntries(CAMPOS.map((k) => [k, form.value[k]]))) !== JSON.stringify(Object.fromEntries(CAMPOS.map((k) => [k, JSON.parse(original.value || '{}')[k]]))));
const telefoneMudou = computed(() => form.value.telefone_atual !== JSON.parse(original.value || '{}').telefone_atual);
const podeSalvar = computed(() => ['matricula', 'nome', 'cpf', 'data_nascimento', 'empresa', 'data_admissao', 'rg_primeiros_3_digitos'].every((k) => String(form.value[k] || '').trim()));

function abrir(dados, ehNovo) {
  form.value = { ...dados };
  original.value = JSON.stringify(dados);
  novo.value = ehNovo;
  erroForm.value = '';
  formAberto.value = true;
}

function abrirNovo() {
  const vazio = Object.fromEntries(CAMPOS.map((k) => [k, '']));
  vazio.empresa = empresas.value.includes(empresaContexto.value) ? empresaContexto.value : (empresas.value[0] || '');
  abrir(vazio, true);
}

function abrirEdicao(c) {
  abrir({ ...Object.fromEntries(CAMPOS.map((k) => [k, c[k] ?? ''])), alterado_em: c.alterado_em }, false);
}

async function salvar() {
  salvando.value = true;
  erroForm.value = '';
  try {
    const corpo = Object.fromEntries(CAMPOS.map((k) => [k, form.value[k] ?? '']));
    const data = await chamar('/api/rh/colaboradores', { ...corpo, novo: novo.value });
    formAberto.value = false;
    aviso(novo.value ? `${form.value.nome} cadastrado (${data.matricula}). Já pode entrar no portal.` : `Dados de ${form.value.nome} atualizados.`);
    await carregar();
  } catch (e) {
    erroForm.value = e.message;
  } finally {
    salvando.value = false;
  }
}

async function alternarAtivo(c) {
  const pergunta = c.ativo
    ? `Inativar ${c.nome}? O acesso ao portal fica bloqueado (e a sessão aberta é encerrada). Holerites, fichas e histórico continuam guardados.`
    : `Reativar ${c.nome}? O acesso ao portal volta a funcionar.`;
  if (!window.confirm(pergunta)) return;
  try {
    await chamar('/api/rh/colaboradores/status', { matricula: c.matricula, ativo: !c.ativo });
    aviso(c.ativo ? `${c.nome} inativado.` : `${c.nome} reativado.`);
    await carregar();
  } catch (e) {
    aviso(e.message, true);
  }
}

onMounted(carregar);
</script>

<style scoped>
.secao { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 8px; }
.rotulo { display: block; font-size: 12px; font-weight: 500; color: #475569; margin-bottom: 4px; }
.campo { width: 100%; height: 44px; padding: 0 12px; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 14px; outline: none; }
.campo:focus { background: #fff; border-color: #94a3b8; }
.campo:disabled { color: #475569; }
.selo { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; border: 1px solid; white-space: nowrap; }
.selo.ok { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
.selo.inativo { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
.acao { height: 32px; padding: 0 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; color: #475569; cursor: pointer; }
.acao:hover { background: #f1f5f9; }
</style>
