<template>
  <div class="space-y-6">
    <!-- Cabeçalho -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800">Empresas do grupo</h3>
        <p class="text-xs text-slate-500 mt-0.5">Cada colaborador pertence a uma empresa e vê o logo dela no portal. Cadernos, trilhas e comunicados podem ser de todas ou de uma só.</p>
      </div>
      <button v-if="podeEditar" type="button" @click="abrirNova" class="h-10 px-5 rounded-full bg-[#9c2337] hover:bg-[#831b2d] text-white text-sm font-semibold cursor-pointer shrink-0">+ Nova empresa</button>
    </div>

    <p v-if="mensagem" class="p-3 rounded-xl border text-xs font-medium" :class="mensagemErro ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700'" role="status">{{ mensagem }}</p>

    <p v-if="carregando" class="py-10 text-center text-sm text-slate-400">Carregando empresas...</p>

    <!-- Estado vazio -->
    <div v-else-if="!empresas.length" class="bg-white rounded-2xl border border-dashed border-slate-300 py-14 px-6 text-center">
      <p class="text-base font-semibold text-slate-800">Nenhuma empresa cadastrada ainda</p>
      <p class="text-sm text-slate-500 mt-1">Cadastre a primeira empresa do grupo para separar colaboradores, cadernos e comunicados.</p>
      <button v-if="podeEditar" type="button" @click="abrirNova" class="mt-5 h-10 px-6 rounded-full bg-[#9c2337] hover:bg-[#831b2d] text-white text-sm font-semibold cursor-pointer">+ Cadastrar primeira empresa</button>
    </div>

    <!-- Cards -->
    <ul v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <li v-for="e in empresas" :key="e.nome" class="bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col">
        <div class="h-32 bg-slate-50 border-b border-slate-100 flex items-center justify-center p-5">
          <img v-if="logoDe(e)" :src="logoDe(e)" :alt="`Logo ${e.nome}`" class="max-w-full max-h-full object-contain" />
          <span v-else class="text-xs text-slate-400 text-center">Sem logo<br />O portal mostra o da Vila Boa</span>
        </div>
        <div class="p-4 flex-1 flex flex-col gap-3">
          <p class="text-base font-semibold text-slate-900 truncate" :title="e.nome">{{ e.nome }}</p>
          <dl class="grid grid-cols-4 gap-2 text-center">
            <div v-for="m in metricas" :key="m.chave" class="rounded-lg bg-slate-50 py-2">
              <dt class="text-[10px] uppercase tracking-wider text-slate-400">{{ m.rotulo }}</dt>
              <dd class="text-sm font-semibold text-slate-800">{{ e.vinculos?.[m.chave] ?? 0 }}</dd>
            </div>
          </dl>
          <div v-if="podeEditar" class="flex items-center gap-2 mt-auto pt-1">
            <button type="button" @click="abrirEditar(e)" class="flex-1 h-9 rounded-full border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-100 cursor-pointer">Editar</button>
            <button type="button" @click="abrirRemover(e)" class="h-9 px-4 rounded-full text-sm font-semibold text-slate-500 hover:bg-rose-50 hover:text-rose-700 cursor-pointer">Remover</button>
          </div>
        </div>
      </li>
    </ul>

    <!-- Empresa de cada colaborador -->
    <div v-if="empresas.length" class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800">Empresa de cada colaborador</h3>
        <div class="flex flex-wrap items-center gap-2">
          <select v-model="empresaFiltro" class="h-9 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-700 outline-none">
            <option value="">Todas as empresas</option>
            <option v-for="e in empresas" :key="e.nome" :value="e.nome">{{ e.nome }}</option>
          </select>
          <input v-model="busca" type="text" placeholder="Buscar nome ou matrícula..." class="h-9 w-full sm:w-56 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
        </div>
      </div>
      <ul class="divide-y divide-slate-100">
        <li v-for="c in colaboradoresFiltrados" :key="c.matricula" class="py-3 flex items-center gap-3">
          <span class="min-w-0 flex-1">
            <span class="block text-sm font-medium text-slate-800 truncate">{{ c.nome }}</span>
            <span class="block text-xs text-slate-400 truncate">{{ c.matricula }} · {{ c.cargo }}</span>
          </span>
          <select :value="c.empresa" :disabled="!podeEditar" @change="trocarEmpresa(c, $event.target.value)" class="h-9 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-700 outline-none shrink-0 max-w-[45%]">
            <option v-for="e in empresas" :key="e.nome" :value="e.nome">{{ e.nome }}</option>
          </select>
        </li>
        <li v-if="!colaboradoresFiltrados.length" class="py-10 text-center text-sm text-slate-400">Nenhum colaborador encontrado.</li>
      </ul>
    </div>

    <!-- Modal criar / editar -->
    <ModalCadastro
      :aberto="modalAberto"
      :titulo="editando ? 'Editar empresa' : 'Nova empresa'"
      :descricao="editando ? 'Mude o nome ou o logo. Os vínculos acompanham o novo nome.' : 'Informe o nome e, se quiser, envie o logo agora.'"
      :rotulo-salvar="editando ? 'Salvar alterações' : 'Criar empresa'"
      :enviando="enviando"
      :pode-salvar="podeSalvar"
      :erro="erroModal"
      :sujo="sujo"
      @fechar="fecharModal"
      @salvar="salvar"
    >
      <label class="block space-y-1.5">
        <span class="text-xs font-semibold text-slate-700">Nome da empresa</span>
        <input ref="campoNome" v-model="form.nome" type="text" maxlength="60" placeholder="Ex.: Vila Boa" class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
        <span class="block text-[11px] text-slate-400 text-right">{{ form.nome.length }}/60</span>
      </label>

      <div v-if="renomeando" class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800 space-y-1">
        <p class="font-semibold">"{{ editando.nome }}" passará a se chamar "{{ nomeLimpo }}".</p>
        <p>
          Isso leva junto
          <strong>{{ editando.vinculos?.colaboradores ?? 0 }} colaborador(es)</strong> e
          <strong>{{ editando.vinculos?.cadernos ?? 0 }} caderno(s)</strong><template v-if="(editando.vinculos?.trilhas ?? 0) || (editando.vinculos?.comunicados ?? 0)">,
          <strong>{{ editando.vinculos?.trilhas ?? 0 }} trilha(s)</strong> e
          <strong>{{ editando.vinculos?.comunicados ?? 0 }} comunicado(s)</strong></template>,
          que passam a usar o novo nome.
        </p>
      </div>

      <div class="space-y-1.5">
        <span class="text-xs font-semibold text-slate-700">Logo</span>
        <div
          class="rounded-xl border-2 border-dashed px-4 py-6 text-center cursor-pointer transition-colors"
          :class="arrastando ? 'border-[#9c2337] bg-rose-50' : 'border-slate-300 bg-slate-50 hover:bg-slate-100'"
          role="button" tabindex="0"
          @click="campoArquivo.click()" @keydown.enter.prevent="campoArquivo.click()" @keydown.space.prevent="campoArquivo.click()"
          @dragover.prevent="arrastando = true" @dragleave.prevent="arrastando = false" @drop.prevent="soltou"
        >
          <p class="text-sm font-medium text-slate-700">{{ previaLogo ? 'Arraste outra imagem ou clique para trocar' : 'Arraste o logo aqui ou clique para escolher' }}</p>
          <p class="text-[11px] text-slate-400 mt-1">PNG, JPG ou WEBP de até 2 MB, de preferência com fundo transparente.</p>
          <input ref="campoArquivo" type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="escolheu" />
        </div>
        <p v-if="erroLogo" class="text-xs font-medium text-rose-700">{{ erroLogo }}</p>
      </div>

      <div v-if="previaLogo" class="space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-700">Como vai aparecer</span>
          <button type="button" @click="tirarLogo" class="text-xs font-semibold text-slate-500 hover:text-rose-700 cursor-pointer">Remover logo</button>
        </div>
        <div class="grid grid-cols-3 gap-2">
          <figure class="rounded-xl border border-slate-200 bg-white h-20 flex items-center justify-center p-2">
            <img :src="previaLogo" alt="Prévia em fundo claro" class="max-w-full max-h-full object-contain" />
          </figure>
          <figure class="rounded-xl border border-slate-700 bg-[#141414] h-20 flex items-center justify-center p-2">
            <img :src="previaLogo" alt="Prévia em fundo escuro" class="max-w-full max-h-full object-contain" />
          </figure>
          <figure class="rounded-xl border border-slate-700 bg-[#141414] h-20 flex flex-col items-start justify-center p-3 gap-1.5">
            <img :src="previaLogo" alt="Prévia na barra lateral" class="max-h-7 max-w-full object-contain" />
            <span class="w-10 h-1 rounded bg-slate-600"></span><span class="w-14 h-1 rounded bg-slate-700"></span>
          </figure>
        </div>
        <p class="text-[11px] text-slate-400 grid grid-cols-3 gap-2 text-center"><span>Fundo claro</span><span>Fundo escuro</span><span>Menu do portal</span></p>
      </div>
      <p v-else class="text-[11px] text-slate-400">Sem logo, o portal mostra o da Vila Boa para os colaboradores desta empresa.</p>
    </ModalCadastro>

    <!-- Confirmação de remoção -->
    <ModalCadastro
      :aberto="!!removendo"
      titulo="Remover empresa"
      tamanho="sm"
      rotulo-salvar="Remover empresa"
      :enviando="enviando"
      :pode-salvar="!!removendo && !bloqueioRemocao"
      :erro="erroModal"
      @fechar="removendo = null"
      @salvar="remover"
    >
      <template v-if="removendo">
        <div v-if="bloqueioRemocao" class="space-y-3 text-sm text-slate-700">
          <p class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-medium">{{ bloqueioRemocao }}</p>
          <p class="text-xs text-slate-500">
            A empresa <strong>{{ removendo.nome }}</strong> tem {{ resumoVinculos(removendo) }}.
            Troque a empresa deles (ou exclua) e depois volte aqui para remover.
          </p>
        </div>
        <p v-else class="text-sm text-slate-700">
          Tem certeza que quer remover <strong>{{ removendo.nome }}</strong>? Ela não tem nenhum vínculo. O logo enviado também será apagado. Essa ação não pode ser desfeita.
        </p>
      </template>
    </ModalCadastro>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import ModalCadastro from './ModalCadastro.vue';
import { pode } from '../utils/permissoes.js';
import { tokenAdmin } from '../utils/sessaoAdmin.js';
import { urlLogoEmpresa } from '../utils/logoEmpresa.js';

/**
 * Cadastro de empresas do painel do RH (aba "Empresas"): cards, criação/edição em modal,
 * logo por arrastar e soltar e remoção com confirmação. Uso: <EmpresasRh :colaborador="colaborador" />
 */
const props = defineProps({ colaborador: { type: Object, default: null } });

const podeEditar = computed(() => pode(props.colaborador, 'rh_cadernos', 'editar'));
const metricas = [
  { chave: 'colaboradores', rotulo: 'Pessoas' },
  { chave: 'cadernos', rotulo: 'Cadernos' },
  { chave: 'trilhas', rotulo: 'Trilhas' },
  { chave: 'comunicados', rotulo: 'Avisos' }
];

const auth = () => ({ Authorization: `Bearer ${tokenAdmin()}` });
const empresas = ref([]);
const colaboradores = ref([]);
const carregando = ref(true);
const mensagem = ref('');
const mensagemErro = ref(false);
let temporizador = null;

function avisar(texto, erro = false) {
  mensagem.value = texto;
  mensagemErro.value = erro;
  clearTimeout(temporizador);
  temporizador = setTimeout(() => { mensagem.value = ''; }, 7000);
}

async function carregar() {
  try {
    const res = await fetch('/api/rh/empresas', { headers: auth() });
    const data = await res.json();
    if (!res.ok) throw new Error(data.erro);
    empresas.value = data.empresas;
  } catch (e) {
    avisar(e.message || 'Não foi possível carregar as empresas.', true);
  } finally {
    carregando.value = false;
  }
}

async function carregarColaboradores() {
  try {
    const res = await fetch(`/api/rh/resumo?ano=${new Date().getFullYear()}`, { headers: auth() });
    const data = await res.json();
    if (res.ok) colaboradores.value = data.colaboradores.map((c) => ({ matricula: c.matricula, nome: c.nome, cargo: c.cargo, empresa: c.empresa }));
  } catch (e) { /* a lista de colaboradores é secundária */ }
}

onMounted(() => { carregar(); carregarColaboradores(); });

const logoDe = (e) => urlLogoEmpresa(e.nome, e.logo);
const resumoVinculos = (e) => {
  const v = e.vinculos || {};
  return [[v.colaboradores, 'colaborador(es)'], [v.cadernos, 'caderno(s)'], [v.trilhas, 'trilha(s)'], [v.comunicados, 'comunicado(s)']]
    .filter(([n]) => n).map(([n, t]) => `${n} ${t}`).join(', ');
};

// ---------------- Criar / editar ----------------
const modalAberto = ref(false);
const editando = ref(null); // empresa em edição; null = nova
const form = ref({ nome: '' });
const arquivo = ref(null);
const previaLogo = ref('');
const removerLogoAoSalvar = ref(false);
const arrastando = ref(false);
const erroLogo = ref('');
const erroModal = ref('');
const enviando = ref(false);
const campoArquivo = ref(null);
const campoNome = ref(null);

const nomeLimpo = computed(() => form.value.nome.trim().replace(/\s+/g, ' '));
const renomeando = computed(() => !!editando.value && nomeLimpo.value !== '' && nomeLimpo.value !== editando.value.nome);
const sujo = computed(() => modalAberto.value && (renomeando.value || !!arquivo.value || removerLogoAoSalvar.value || (!editando.value && nomeLimpo.value !== '')));
const podeSalvar = computed(() => nomeLimpo.value !== '' && (!editando.value || renomeando.value || !!arquivo.value || removerLogoAoSalvar.value));

function limparPrevia() {
  if (previaLogo.value.startsWith('blob:')) URL.revokeObjectURL(previaLogo.value);
  previaLogo.value = '';
}

function abrir(emp) {
  editando.value = emp;
  form.value = { nome: emp ? emp.nome : '' };
  arquivo.value = null;
  removerLogoAoSalvar.value = false;
  erroLogo.value = '';
  erroModal.value = '';
  limparPrevia();
  previaLogo.value = emp ? (logoDe(emp) || '') : '';
  modalAberto.value = true;
  nextTick(() => campoNome.value?.focus());
}
const abrirNova = () => abrir(null);
const abrirEditar = (e) => abrir(e);

function fecharModal() {
  modalAberto.value = false;
  limparPrevia();
}

function aceitarArquivo(f) {
  erroLogo.value = '';
  if (!f) return;
  if (!['image/png', 'image/jpeg', 'image/webp'].includes(f.type)) {
    erroLogo.value = 'Esse arquivo não serve. Envie o logo em PNG, JPG ou WEBP.';
    return;
  }
  if (f.size > 2 * 1024 * 1024) {
    erroLogo.value = `A imagem tem ${(f.size / 1024 / 1024).toFixed(1).replace('.', ',')} MB e o limite é 2 MB. Reduza o tamanho e tente de novo.`;
    return;
  }
  limparPrevia();
  arquivo.value = f;
  removerLogoAoSalvar.value = false;
  previaLogo.value = URL.createObjectURL(f);
}
const escolheu = (ev) => { aceitarArquivo(ev.target.files?.[0]); ev.target.value = ''; };
function soltou(ev) {
  arrastando.value = false;
  aceitarArquivo(ev.dataTransfer?.files?.[0]);
}
function tirarLogo() {
  limparPrevia();
  arquivo.value = null;
  erroLogo.value = '';
  removerLogoAoSalvar.value = !!(editando.value && logoDe(editando.value));
}

async function post(rota, corpo) {
  const res = await fetch(`/api/rh/empresas${rota}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...auth() },
    body: JSON.stringify(corpo)
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.erro || 'Não foi possível concluir. Tente novamente.');
  return data;
}

async function enviarArquivo(nome) {
  const corpo = new FormData();
  corpo.append('nome', nome);
  corpo.append('logo', arquivo.value);
  const res = await fetch('/api/rh/empresas/logo', { method: 'POST', headers: auth(), body: corpo });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.erro || 'Falha ao enviar o logo.');
}

async function salvar() {
  if (!podeSalvar.value || enviando.value) return;
  erroModal.value = '';
  enviando.value = true;
  const nome = nomeLimpo.value;
  let passo = 'empresa';
  try {
    if (!editando.value) {
      await post('', { nome });
      passo = 'logo';
      if (arquivo.value) await enviarArquivo(nome);
      avisar(`Empresa "${nome}" criada${arquivo.value ? ' com o logo' : ''}.`);
    } else {
      const atual = editando.value.nome;
      if (renomeando.value) await post('/renomear', { nome: atual, novo_nome: nome });
      passo = 'logo';
      if (arquivo.value) await enviarArquivo(nome);
      else if (removerLogoAoSalvar.value) await post('/logo/remover', { nome });
      avisar(renomeando.value ? `"${atual}" agora se chama "${nome}". Colaboradores e cadernos foram junto.` : `Empresa "${nome}" atualizada.`);
    }
    fecharModal();
    await Promise.all([carregar(), carregarColaboradores()]);
  } catch (e) {
    if (passo === 'logo') {
      // O nome já foi gravado: segue em modo de edição para tentar o logo de novo
      await Promise.all([carregar(), carregarColaboradores()]);
      editando.value = empresas.value.find((x) => x.nome === nome) || editando.value;
      form.value.nome = nome;
      erroModal.value = `A empresa foi salva, mas o logo não: ${e.message}`;
    } else {
      erroModal.value = e.message;
    }
  } finally {
    enviando.value = false;
  }
}

// ---------------- Remover ----------------
const removendo = ref(null);
function abrirRemover(e) {
  erroModal.value = '';
  removendo.value = e;
}
const bloqueioRemocao = computed(() => {
  const e = removendo.value;
  if (!e) return '';
  if (empresas.value.length <= 1) return 'É preciso manter ao menos uma empresa cadastrada.';
  return resumoVinculos(e) ? 'Esta empresa ainda tem vínculos e não pode ser removida.' : '';
});

async function remover() {
  if (!removendo.value || bloqueioRemocao.value) return;
  enviando.value = true;
  erroModal.value = '';
  const nome = removendo.value.nome;
  try {
    await post('/remover', { nome });
    removendo.value = null;
    avisar(`Empresa "${nome}" removida.`);
    await carregar();
  } catch (e) {
    erroModal.value = e.message;
  } finally {
    enviando.value = false;
  }
}

// ---------------- Empresa de cada colaborador ----------------
const empresaFiltro = ref('');
const busca = ref('');
const colaboradoresFiltrados = computed(() => {
  const termo = busca.value.trim().toLowerCase();
  return colaboradores.value.filter((c) =>
    (!empresaFiltro.value || c.empresa === empresaFiltro.value) &&
    (!termo || c.nome.toLowerCase().includes(termo) || c.matricula.toLowerCase().includes(termo))
  );
});

async function trocarEmpresa(c, empresa) {
  const anterior = c.empresa;
  c.empresa = empresa;
  try {
    await post('/colaborador', { matricula: c.matricula, empresa });
    avisar(`${c.nome} agora é da ${empresa}.`);
    await carregar();
  } catch (e) {
    c.empresa = anterior;
    avisar(e.message, true);
  }
}
</script>
