<template>
  <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800">Fichas de EPI</h3>
        <p class="text-xs text-slate-500 mt-0.5 max-w-2xl">
          Cadastre a ficha de cada colaborador por competência com os EPIs entregues. Ela aparece no portal para o colaborador assinar.
          Depois de assinada, a ficha não muda mais: para corrigir, cadastre uma nova.
        </p>
      </div>
      <div v-if="podeCriar" class="flex flex-wrap gap-2">
        <button type="button" @click="abrirImportar" class="h-11 px-5 rounded-full border border-slate-300 hover:bg-slate-50 text-slate-800 text-sm font-semibold cursor-pointer">Importar planilha</button>
        <button type="button" @click="abrirNova()" class="h-11 px-6 rounded-full border border-slate-300 hover:bg-slate-50 text-slate-800 text-sm font-semibold cursor-pointer">+ Nova ficha</button>
      </div>
    </div>
    <p v-if="mensagem" class="text-xs font-medium" :class="mensagemErro ? 'text-rose-700' : 'text-emerald-700'">{{ mensagem }}</p>

    <!-- Filtros -->
    <div class="flex flex-wrap gap-2">
      <div class="flex items-center h-10 flex-1 min-w-[200px] max-w-sm rounded-xl border border-slate-200 bg-slate-50 focus-within:bg-white focus-within:border-slate-400">
        <AppIcon name="search" size="14" class="text-slate-400 ml-3 shrink-0" />
        <input v-model="busca" type="text" placeholder="Buscar colaborador, matrícula ou EPI" class="flex-1 min-w-0 h-full px-2.5 bg-transparent text-sm outline-none" />
      </div>
      <select v-model="filtroCompetencia" class="h-10 px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm outline-none focus:border-slate-400 cursor-pointer" aria-label="Competência">
        <option value="">Todas as competências</option>
        <option v-for="c in competencias" :key="c" :value="c">{{ c }}</option>
      </select>
      <select v-model="filtroSituacao" class="h-10 px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm outline-none focus:border-slate-400 cursor-pointer" aria-label="Situação">
        <option value="">Todas as situações</option>
        <option value="pendente">Aguardando assinatura</option>
        <option value="assinado">Assinadas</option>
      </select>
    </div>

    <div v-if="carregando" class="py-6 text-center text-sm text-slate-400">Carregando...</div>
    <p v-else-if="!filtradas.length" class="text-sm text-slate-500 py-2">{{ fichasCtx.length ? 'Nenhuma ficha com esses filtros.' : 'Nenhuma ficha de EPI cadastrada.' }}</p>
    <div v-else class="overflow-x-auto -mx-5 sm:-mx-6">
      <table class="w-full text-sm min-w-[680px]">
        <thead>
          <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-100">
            <th class="px-5 sm:px-6 py-2 font-semibold">Colaborador</th>
            <th class="px-3 py-2 font-semibold">Competência</th>
            <th class="px-3 py-2 font-semibold">Itens</th>
            <th class="px-3 py-2 font-semibold">Situação</th>
            <th class="px-5 sm:px-6 py-2"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="f in filtradas" :key="f.id" class="border-b border-slate-50 last:border-0 align-top">
            <td class="px-5 sm:px-6 py-3">
              <p class="font-semibold text-slate-900">{{ f.nome_colaborador }}</p>
              <p class="text-xs text-slate-500">{{ f.matricula }}<template v-if="f.cargo"> · {{ f.cargo }}</template></p>
            </td>
            <td class="px-3 py-3 whitespace-nowrap">
              <p class="text-slate-800 tabular-nums">{{ f.competencia }}</p>
              <p class="text-[11px] text-slate-400">{{ f.id }}</p>
            </td>
            <td class="px-3 py-3">
              <p class="text-slate-800">{{ f.itens.length }} {{ f.itens.length === 1 ? 'item' : 'itens' }}</p>
              <p class="text-[11px] text-slate-400 max-w-[260px] truncate">{{ f.itens.map((i) => i.descricao).join(', ') }}</p>
            </td>
            <td class="px-3 py-3">
              <span class="selo" :class="f.status === 'pendente_assinatura' ? 'pendente' : 'ok'">{{ f.status === 'pendente_assinatura' ? 'Aguardando assinatura' : 'Assinada' }}</span>
              <p v-if="f.assinado_em" class="text-[11px] text-slate-400 mt-1">{{ dataHoraBr(f.assinado_em) }}</p>
            </td>
            <td class="px-5 sm:px-6 py-3 text-right whitespace-nowrap">
              <button v-if="f.status === 'pendente_assinatura' && podeEditar" type="button" @click="abrirEdicao(f)" class="acao">Editar</button>
              <button v-else type="button" @click="abrirEdicao(f)" class="acao">Ver</button>
              <button v-if="podeCriar" type="button" @click="abrirNova(f)" class="acao" title="Nova ficha com os mesmos itens">Copiar</button>
              <button v-if="f.status === 'pendente_assinatura' && podeRemover" type="button" @click="remover(f)" class="acao hover:!text-rose-700">Remover</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Ficha (nova, edição ou visualização) -->
    <ModalCadastro
      :aberto="formAberto"
      :titulo="somenteLeitura ? 'Ficha de EPI assinada' : (form.id ? 'Editar ficha de EPI' : 'Nova ficha de EPI')"
      :descricao="somenteLeitura ? 'Assinada pelo colaborador: os dados não podem mais ser alterados.' : 'A ficha aparece no portal para o colaborador assinar.'"
      tamanho="xl"
      :rotulo-salvar="somenteLeitura ? 'Assinada' : (form.id ? 'Salvar alterações' : 'Cadastrar ficha')"
      :enviando="salvando"
      :erro="erroForm"
      :pode-salvar="!somenteLeitura && !!form.matricula && !!form.competencia"
      :sujo="!somenteLeitura && sujo"
      @fechar="formAberto = false"
      @salvar="salvar"
    >
      <fieldset :disabled="somenteLeitura" class="space-y-5 min-w-0">
        <div class="grid grid-cols-1 sm:grid-cols-6 gap-3">
          <label class="block sm:col-span-4">
            <span class="rotulo">Colaborador</span>
            <SeletorColaborador v-if="!somenteLeitura" v-model="form.matricula" :colaboradores="colabsContexto" larguraClasse="w-full" @change="aoEscolherColaborador" />
            <input v-else :value="`${form.nome || ''} (${form.matricula})`" class="campo" />
          </label>
          <label class="block sm:col-span-2">
            <span class="rotulo">Competência</span>
            <input v-model="form.competencia" type="month" required class="campo" />
          </label>
          <label class="block sm:col-span-2">
            <span class="rotulo">Chapa</span>
            <input v-model="form.chapa" inputmode="numeric" class="campo" />
          </label>
          <label class="block sm:col-span-2">
            <span class="rotulo">Função</span>
            <input v-model="form.cargo" class="campo" />
          </label>
          <label class="block sm:col-span-2">
            <span class="rotulo">Equipe</span>
            <input v-model="form.equipe" class="campo" />
          </label>
        </div>

        <details class="rounded-xl border border-slate-200 px-4 py-3 group" :open="maisDadosAberto">
          <summary class="text-xs font-semibold text-slate-600 cursor-pointer select-none">Mais dados (opcionais): obra, técnico, motivo, empresa</summary>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
            <label class="block"><span class="rotulo">Canteiro / obra</span><input v-model="form.canteiro_obra" class="campo" /></label>
            <label class="block"><span class="rotulo">Técnico responsável</span><input v-model="form.tecnico_responsavel" class="campo" /></label>
            <label class="block"><span class="rotulo">Motivo da entrega</span><input v-model="form.motivo" class="campo" /></label>
            <label class="block"><span class="rotulo">Local e data (rodapé da ficha)</span><input v-model="form.local_data" placeholder="Ex.: São Bernardo do Campo, 10 de novembro de 2026" class="campo" /></label>
            <label class="block"><span class="rotulo">Razão social</span><input v-model="form.empresa" placeholder="VILA BOA CONSTRUCOES E SERVICOS LTDA." class="campo" /></label>
            <label class="block"><span class="rotulo">CNPJ</span><input v-model="form.cnpj" placeholder="02.785.261/0001-90" class="campo" /></label>
          </div>
        </details>

        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-700">Itens entregues</span>
            <span class="text-[11px] text-slate-400">{{ form.itens.length }} {{ form.itens.length === 1 ? 'item' : 'itens' }}</span>
          </div>

          <!-- Cabeçalho (só no computador; no celular cada item vira um cartão) -->
          <div class="hidden lg:grid itens-grade text-[10px] font-semibold uppercase tracking-wider text-slate-400 px-1">
            <span>Data</span><span>Qtd.</span><span>Un.</span><span>Código</span><span>Descrição</span><span>C.A.</span><span>Valor (R$)</span><span>Situação</span><span></span>
          </div>
          <div v-for="(it, i) in form.itens" :key="it._k" class="itens-grade grid grid-cols-2 sm:grid-cols-4 gap-2 p-3 lg:p-1 rounded-xl border border-slate-200 lg:border-0 bg-slate-50/60 lg:bg-transparent">
            <label class="block"><span class="rotulo lg:sr-only">Data</span><input v-model="it.data" type="date" class="campo campo-sm" /></label>
            <label class="block"><span class="rotulo lg:sr-only">Quantidade</span><input v-model="it.quantidade" inputmode="decimal" class="campo campo-sm" /></label>
            <label class="block"><span class="rotulo lg:sr-only">Unidade</span><input v-model="it.unidade" list="epi-unidades" placeholder="UN" class="campo campo-sm uppercase" /></label>
            <label class="block"><span class="rotulo lg:sr-only">Código</span><input v-model="it.codigo" class="campo campo-sm" /></label>
            <label class="block col-span-2 sm:col-span-4 lg:col-span-1"><span class="rotulo lg:sr-only">Descrição</span><input v-model="it.descricao" class="campo campo-sm" placeholder="Ex.: Capacete de segurança" /></label>
            <label class="block"><span class="rotulo lg:sr-only">C.A.</span><input v-model="it.ca" class="campo campo-sm" /></label>
            <label class="block"><span class="rotulo lg:sr-only">Valor (R$)</span><input v-model="it.valor" inputmode="decimal" placeholder="0,00" class="campo campo-sm" /></label>
            <label class="block"><span class="rotulo lg:sr-only">Situação</span>
              <select v-model="it.situacao" class="campo campo-sm cursor-pointer">
                <option>Primeira Entrega</option>
                <option>Troca</option>
              </select>
            </label>
            <div class="flex items-end justify-end col-span-2 sm:col-span-4 lg:col-span-1">
              <button v-if="!somenteLeitura" type="button" @click="form.itens.splice(i, 1)" :disabled="form.itens.length === 1" class="h-9 px-3 lg:w-9 lg:px-0 rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-700 disabled:opacity-30 flex items-center justify-center gap-1.5 text-xs font-semibold cursor-pointer" aria-label="Remover item">
                <AppIcon name="x" size="14" /><span class="lg:hidden">Remover item</span>
              </button>
            </div>
          </div>
          <datalist id="epi-unidades"><option value="UN" /><option value="PAR" /><option value="CX" /><option value="KIT" /><option value="M" /></datalist>
          <button v-if="!somenteLeitura" type="button" @click="adicionarItem" class="h-10 px-4 rounded-full border border-dashed border-slate-300 text-sm font-semibold text-slate-600 hover:border-slate-400 hover:text-slate-900 cursor-pointer">+ Adicionar item</button>
        </div>
      </fieldset>
      <template #rodape-esquerda>
        <span v-if="totalFicha">Total: R$ {{ totalFicha }}</span>
      </template>
    </ModalCadastro>

    <!-- Importar planilha -->
    <ModalCadastro
      :aberto="importarAberto"
      titulo="Importar fichas de EPI"
      descricao="Uma linha por item entregue. As linhas do mesmo colaborador na mesma competência viram uma ficha."
      tamanho="lg"
      :rotulo-salvar="importacao ? `Importar ${importacao.fichas.length} ficha(s)` : 'Importar'"
      :enviando="importando"
      :erro="erroImportar"
      :pode-salvar="!!importacao?.fichas.length"
      @fechar="importarAberto = false"
      @salvar="importar"
    >
      <div class="space-y-4">
        <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 text-xs text-slate-600 space-y-2">
          <p>Colunas: <strong>Matrícula</strong> (ou Chapa), <strong>Competência</strong>, Função, Equipe, <strong>Data</strong>, <strong>Quantidade</strong>, Unidade, Código, <strong>Descrição</strong>, C.A., Valor e Situação (Primeira Entrega ou Troca).</p>
          <p>Se o colaborador já tem uma ficha aguardando assinatura na mesma competência, os itens entram nela.</p>
          <button type="button" @click="baixarModeloEpi" class="inline-flex items-center gap-1.5 font-semibold text-[#9c2337] hover:underline cursor-pointer"><AppIcon name="download" size="13" />Baixar modelo da planilha</button>
        </div>
        <label class="block">
          <span class="rotulo">Planilha (.xlsx ou .csv)</span>
          <input type="file" accept=".xlsx,.csv" @change="lerArquivo" class="block w-full text-sm text-slate-600 file:mr-3 file:h-10 file:px-4 file:rounded-full file:border-0 file:bg-slate-100 file:text-slate-700 file:font-semibold hover:file:bg-slate-200 file:cursor-pointer" />
        </label>
        <p v-if="lendo" class="text-sm text-slate-400">Lendo a planilha...</p>
        <template v-if="importacao">
          <div v-if="importacao.avisos.length" class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs space-y-1">
            <p class="font-semibold">Linhas que ficam de fora ({{ importacao.avisos.length }}):</p>
            <p v-for="a in importacao.avisos.slice(0, 8)" :key="a">{{ a }}</p>
            <p v-if="importacao.avisos.length > 8">e mais {{ importacao.avisos.length - 8 }}.</p>
          </div>
          <div v-if="importacao.fichas.length" class="border border-slate-200 rounded-xl divide-y divide-slate-100 max-h-72 overflow-y-auto">
            <div v-for="f in importacao.fichas" :key="f.matricula + f.competencia" class="px-4 py-2.5 flex items-center justify-between gap-3 text-sm">
              <span class="min-w-0">
                <span class="block font-medium text-slate-800 truncate">{{ nomeDe(f.matricula) }}</span>
                <span class="block text-xs text-slate-500">{{ f.matricula }} · {{ f.competencia }}</span>
              </span>
              <span class="text-xs text-slate-500 shrink-0">{{ f.itens.length }} {{ f.itens.length === 1 ? 'item' : 'itens' }}</span>
            </div>
          </div>
        </template>
      </div>
    </ModalCadastro>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppIcon from './AppIcon.vue';
import ModalCadastro from './ModalCadastro.vue';
import SeletorColaborador from './SeletorColaborador.vue';
import { pode } from '../utils/permissoes.js';
import { tokenAdmin } from '../utils/sessaoAdmin.js';
import { doContexto } from '../utils/empresaContexto.js';
import { lerPlanilhaEpi, baixarModeloEpi } from '../utils/planilhaEpi.js';

const props = defineProps({ colaborador: { type: Object, required: true } });

const podeCriar = computed(() => pode(props.colaborador, 'rh_cadernos', 'publicar'));
const podeEditar = computed(() => pode(props.colaborador, 'rh_cadernos', 'editar'));
const podeRemover = computed(() => pode(props.colaborador, 'rh_cadernos', 'remover'));

const fichas = ref([]);
const colaboradores = ref([]);
const carregando = ref(true);
const mensagem = ref('');
const mensagemErro = ref(false);
const busca = ref('');
const filtroCompetencia = ref('');
const filtroSituacao = ref('');

const auth = () => ({ Authorization: `Bearer ${tokenAdmin()}` });
const semAcento = (s) => String(s ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
const dataHoraBr = (s) => (s ? `${s.slice(8, 10)}/${s.slice(5, 7)}/${s.slice(0, 4)} ${s.slice(11, 16)}` : '');
const brParaIso = (d) => (/^\d{2}\/\d{2}\/\d{4}$/.test(d || '') ? d.split('/').reverse().join('-') : (d || ''));
const isoParaBr = (d) => (/^\d{4}-\d{2}-\d{2}$/.test(d || '') ? d.split('-').reverse().join('/') : (d || ''));
const compParaMes = (c) => (/^\d{2}\/\d{4}$/.test(c || '') ? `${c.slice(3)}-${c.slice(0, 2)}` : '');
const mesParaComp = (m) => (/^\d{4}-\d{2}$/.test(m || '') ? `${m.slice(5)}/${m.slice(0, 4)}` : '');
const hojeIso = () => new Date().toLocaleDateString('sv-SE', { timeZone: 'America/Sao_Paulo' });
function aviso(texto, erro = false) { mensagem.value = texto; mensagemErro.value = erro; }

const fichasCtx = computed(() => fichas.value.filter((f) => doContexto(f.empresa_grupo)));
const colabsContexto = computed(() => colaboradores.value.filter((c) => doContexto(c.empresa)));
const competencias = computed(() => [...new Set(fichasCtx.value.map((f) => f.competencia))]);
const filtradas = computed(() => {
  const termo = semAcento(busca.value.trim());
  return fichasCtx.value.filter((f) => {
    if (filtroCompetencia.value && f.competencia !== filtroCompetencia.value) return false;
    if (filtroSituacao.value === 'pendente' && f.status !== 'pendente_assinatura') return false;
    if (filtroSituacao.value === 'assinado' && f.status === 'pendente_assinatura') return false;
    if (!termo) return true;
    return [f.nome_colaborador, f.matricula, f.cargo, f.id, ...f.itens.map((i) => i.descricao)].some((t) => semAcento(t).includes(termo));
  });
});
const nomeDe = (matricula) => colaboradores.value.find((c) => c.matricula === matricula)?.nome || matricula;

async function carregar() {
  try {
    const res = await fetch('/api/rh/epis', { headers: auth() });
    const data = await res.json();
    if (!res.ok) return aviso(data.erro || 'Não foi possível carregar as fichas.', true);
    fichas.value = data.fichas;
    colaboradores.value = data.colaboradores;
  } catch (e) {
    aviso('Não foi possível carregar as fichas.', true);
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

// ---------------- Formulário da ficha ----------------
let chaveItem = 0;
const formAberto = ref(false);
const salvando = ref(false);
const erroForm = ref('');
const form = ref(fichaVazia());
const original = ref('');
const somenteLeitura = ref(false);
const maisDadosAberto = ref(false);

function itemVazio(base = {}) {
  return { _k: ++chaveItem, data: hojeIso(), quantidade: '1', unidade: 'UN', codigo: '', descricao: '', ca: '', valor: '', situacao: 'Primeira Entrega', ...base };
}
function fichaVazia() {
  return { id: '', matricula: '', nome: '', competencia: hojeIso().slice(0, 7), chapa: '', cargo: '', equipe: '', canteiro_obra: '', tecnico_responsavel: '', motivo: '', local_data: '', empresa: '', cnpj: '', itens: [itemVazio()] };
}
const serializar = (f) => JSON.stringify({ ...f, itens: f.itens.map(({ _k, ...r }) => r) });
const sujo = computed(() => formAberto.value && serializar(form.value) !== original.value);

const totalFicha = computed(() => {
  const total = form.value.itens.reduce((s, it) => {
    const v = Number(String(it.valor || '').replace(/\./g, '').replace(',', '.'));
    const q = Number(String(it.quantidade || '').replace(',', '.'));
    return s + (Number.isFinite(v) && Number.isFinite(q) ? v * q : 0);
  }, 0);
  return total ? total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
});

function dadosDaFicha(f, { copia = false } = {}) {
  return {
    id: copia ? '' : f.id,
    matricula: f.matricula,
    nome: f.nome_colaborador,
    competencia: copia ? hojeIso().slice(0, 7) : compParaMes(f.competencia),
    chapa: f.chapa || '',
    cargo: f.cargo || '',
    equipe: f.equipe || '',
    canteiro_obra: f.canteiro_obra || '',
    tecnico_responsavel: f.tecnico_responsavel || '',
    motivo: copia ? '' : (f.motivo || ''),
    local_data: copia ? '' : (f.local_data || ''),
    empresa: f.empresa || '',
    cnpj: f.cnpj || '',
    itens: f.itens.map((it) => itemVazio({
      data: copia ? hojeIso() : brParaIso(it.data),
      quantidade: String(it.quantidade ?? ''),
      unidade: it.unidade || '',
      codigo: it.codigo || '',
      descricao: it.descricao || '',
      ca: it.ca || '',
      valor: it.valor || '',
      situacao: copia ? 'Troca' : (it.situacao || 'Primeira Entrega')
    }))
  };
}

function abrir(dados, leitura) {
  form.value = dados;
  original.value = serializar(dados);
  somenteLeitura.value = leitura;
  maisDadosAberto.value = leitura || !!(dados.canteiro_obra || dados.tecnico_responsavel || dados.motivo);
  erroForm.value = '';
  formAberto.value = true;
}

// Sem base: ficha em branco. Com base: "Copiar" = mesma pessoa e itens numa ficha nova
function abrirNova(base) {
  abrir(base ? dadosDaFicha(base, { copia: true }) : fichaVazia(), false);
}

function abrirEdicao(f) {
  abrir(dadosDaFicha(f), f.status !== 'pendente_assinatura' || !podeEditar.value);
}

// Ao escolher o colaborador, sugere função, chapa e equipe (da última ficha dele)
function aoEscolherColaborador() {
  const c = colaboradores.value.find((x) => x.matricula === form.value.matricula);
  if (!c) return;
  form.value.nome = c.nome;
  const ultima = fichas.value.filter((f) => f.matricula === c.matricula).sort((a, b) => (b.data_entrega || '').localeCompare(a.data_entrega || ''))[0];
  form.value.cargo = ultima?.cargo || c.cargo || '';
  if (!form.value.chapa) form.value.chapa = ultima?.chapa || '';
  if (!form.value.equipe) form.value.equipe = ultima?.equipe || '';
}

function adicionarItem() {
  const ultimo = form.value.itens[form.value.itens.length - 1];
  form.value.itens.push(itemVazio({ data: ultimo?.data || hojeIso() }));
}

async function salvar() {
  if (somenteLeitura.value) return;
  salvando.value = true;
  erroForm.value = '';
  try {
    const { nome, ...resto } = form.value;
    const corpo = {
      ...resto,
      competencia: mesParaComp(form.value.competencia),
      itens: form.value.itens.map(({ _k, ...it }) => ({ ...it, data: isoParaBr(it.data) }))
    };
    if (!corpo.id) delete corpo.id;
    const data = await chamar('/api/rh/epis', corpo);
    formAberto.value = false;
    aviso(form.value.id ? `Ficha ${data.id} atualizada.` : `Ficha ${data.id} cadastrada. Ela já aparece no portal do colaborador para assinatura.`);
    await carregar();
  } catch (e) {
    erroForm.value = e.message;
  } finally {
    salvando.value = false;
  }
}

async function remover(f) {
  if (!window.confirm(`Remover a ficha ${f.id} de ${f.nome_colaborador} (${f.competencia})? Ela some do portal do colaborador.`)) return;
  try {
    await chamar('/api/rh/epis/remover', { id: f.id });
    aviso(`Ficha ${f.id} removida.`);
    await carregar();
  } catch (e) {
    aviso(e.message, true);
  }
}

// ---------------- Importação ----------------
const importarAberto = ref(false);
const importacao = ref(null);
const lendo = ref(false);
const importando = ref(false);
const erroImportar = ref('');

function abrirImportar() {
  importacao.value = null;
  erroImportar.value = '';
  importarAberto.value = true;
}

async function lerArquivo(ev) {
  const arquivo = ev.target.files?.[0];
  importacao.value = null;
  erroImportar.value = '';
  if (!arquivo) return;
  lendo.value = true;
  try {
    importacao.value = await lerPlanilhaEpi(arquivo, colaboradores.value);
  } catch (e) {
    erroImportar.value = e.message || 'Não foi possível ler a planilha.';
  } finally {
    lendo.value = false;
  }
}

async function importar() {
  importando.value = true;
  erroImportar.value = '';
  try {
    const data = await chamar('/api/rh/epis/importar', { fichas: importacao.value.fichas });
    const partes = [];
    if (data.criadas) partes.push(`${data.criadas} ficha(s) nova(s)`);
    if (data.acrescidas) partes.push(`itens acrescentados em ${data.acrescidas} ficha(s) já existente(s)`);
    if (data.erros.length) {
      erroImportar.value = `${partes.length ? 'Importado: ' + partes.join(' e ') + '. ' : ''}Não entraram: ` + data.erros.map((e) => `${nomeDe(e.matricula)}: ${e.erro}`).join(' · ');
      importacao.value = null;
    } else {
      importarAberto.value = false;
      aviso(`Importação concluída: ${partes.join(' e ')}.`);
    }
    await carregar();
  } catch (e) {
    erroImportar.value = e.message;
  } finally {
    importando.value = false;
  }
}

onMounted(carregar);
</script>

<style scoped>
.rotulo { display: block; font-size: 12px; font-weight: 500; color: #475569; margin-bottom: 4px; }
.campo { width: 100%; height: 44px; padding: 0 12px; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 14px; outline: none; }
.campo:focus { background: #fff; border-color: #94a3b8; }
.campo:disabled, fieldset:disabled .campo { color: #334155; background: #f8fafc; opacity: 1; }
.campo-sm { height: 40px; padding: 0 10px; font-size: 13px; border-radius: 10px; }
@media (min-width: 1024px) {
  .itens-grade { grid-template-columns: 8.5rem 4rem 4.5rem 6rem minmax(10rem, 1fr) 5.5rem 6rem 9rem 2.5rem; }
}
.selo { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; border: 1px solid; white-space: nowrap; }
.selo.ok { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
.selo.pendente { background: #fffbeb; color: #b45309; border-color: #fde68a; }
.acao { height: 32px; padding: 0 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; color: #475569; cursor: pointer; }
.acao:hover { background: #f1f5f9; }
</style>
