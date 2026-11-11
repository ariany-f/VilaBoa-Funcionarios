<template>
  <WizardCadastro
    :aberto="aberto"
    :titulo="editando ? 'Editar caderno' : 'Novo caderno'"
    :descricao="editando ? 'Ajuste os dados, o público e a capa. O PDF continua o mesmo.' : 'Em 4 passos: arquivo, detalhes, público e revisão.'"
    :passos="passos"
    v-model:passo="passo"
    :validar="validar"
    :sujo="alterou"
    :enviando="enviando"
    :erro="erro"
    :rotulo-concluir="editando ? 'Salvar alterações' : 'Publicar caderno'"
    @fechar="$emit('fechar')"
    @concluir="salvar"
  >
    <!-- 1. ARQUIVO -->
    <template #passo-arquivo>
      <div v-if="editando" class="rounded-xl border border-slate-200 bg-slate-50 p-4 flex items-center gap-3">
        <AppIcon name="document" size="22" class="text-[#9c2337] shrink-0" />
        <div class="min-w-0">
          <p class="text-sm font-semibold text-slate-800 truncate">{{ form.arquivo }}</p>
          <p class="text-xs text-slate-500">Arquivo atual. Para trocar o PDF, publique um novo caderno com o mesmo nome de arquivo.</p>
        </div>
      </div>
      <label
        v-else
        class="flex flex-col items-center justify-center gap-1.5 text-center rounded-2xl border-2 border-dashed px-6 py-12 cursor-pointer transition"
        :class="arrastando ? 'border-[#9c2337] bg-[#fdf2f4]' : (pdf ? 'border-emerald-300 bg-emerald-50' : 'border-slate-300 bg-slate-50 hover:border-slate-400')"
        @dragover.prevent="arrastando = true"
        @dragleave.prevent="arrastando = false"
        @drop.prevent="soltarPdf"
      >
        <AppIcon name="document" size="32" :class="pdf ? 'text-emerald-600' : 'text-slate-400'" />
        <template v-if="pdf">
          <span class="text-base font-semibold text-slate-800 break-all">{{ pdf.name }}</span>
          <span class="text-xs text-slate-500">{{ tamanho(pdf.size) }} · clique ou arraste outro para trocar</span>
        </template>
        <template v-else>
          <span class="text-base font-semibold text-slate-700">Arraste o PDF aqui</span>
          <span class="text-xs text-slate-500">ou clique para escolher o arquivo no computador</span>
        </template>
        <input type="file" accept="application/pdf,.pdf" class="sr-only" @change="escolherPdf" />
      </label>

      <div class="space-y-2">
        <p class="text-sm font-semibold text-slate-800">Capa <span class="text-xs font-normal text-slate-400">(opcional · JPG, PNG ou WEBP até 3 MB)</span></p>
        <p v-if="erroCapa" class="text-xs text-rose-700">{{ erroCapa }}</p>
        <div class="flex items-center gap-4">
          <div class="w-20 h-28 rounded-lg border border-slate-200 bg-slate-50 overflow-hidden flex items-center justify-center shrink-0">
            <img v-if="previaCapa" :src="previaCapa" alt="Prévia da capa" class="w-full h-full object-cover" />
            <AppIcon v-else name="document" size="22" class="text-slate-300" />
          </div>
          <div class="space-y-2">
            <label class="inline-flex items-center h-10 px-5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold cursor-pointer">
              {{ previaCapa ? 'Trocar capa' : 'Escolher capa' }}
              <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="escolherCapa" />
            </label>
            <button v-if="previaCapa" type="button" @click="tirarCapa" class="block text-xs text-slate-500 hover:text-rose-700 cursor-pointer">Remover capa</button>
          </div>
        </div>
      </div>
    </template>

    <!-- 2. DETALHES -->
    <template #passo-detalhes>
      <div class="space-y-2">
        <p class="text-sm font-semibold text-slate-800">Onde este caderno aparece?</p>
        <div class="grid sm:grid-cols-2 gap-3">
          <button type="button" @click="form.tipo = 'politica'" class="text-left rounded-2xl border-2 p-4 cursor-pointer transition" :class="form.tipo === 'politica' ? 'border-[#9c2337] bg-[#fdf2f4]' : 'border-slate-200 hover:border-slate-300'">
            <span class="block text-sm font-semibold text-slate-900">Diretrizes — consulta e pesquisa</span>
            <span class="block text-xs text-slate-500 leading-snug mt-1">Manuais e políticas que o colaborador consulta, com confirmação de leitura.</span>
          </button>
          <button type="button" @click="form.tipo = 'treinamento'" class="text-left rounded-2xl border-2 p-4 cursor-pointer transition" :class="form.tipo === 'treinamento' ? 'border-[#9c2337] bg-[#fdf2f4]' : 'border-slate-200 hover:border-slate-300'">
            <span class="block text-sm font-semibold text-slate-900">Academia — gera certificado e entra em trilhas</span>
            <span class="block text-xs text-slate-500 leading-snug mt-1">Treinamentos concluídos com certificado e que podem compor trilhas.</span>
          </button>
        </div>
      </div>

      <label class="block space-y-1.5">
        <span class="text-sm font-semibold text-slate-800">Nome do caderno</span>
        <input v-model="form.titulo" type="text" maxlength="120" placeholder="Ex.: Manual do Colaborador" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
      </label>

      <div class="grid sm:grid-cols-2 gap-4">
        <div class="space-y-1.5">
          <span class="text-sm font-semibold text-slate-800">Categoria</span>
          <div class="flex gap-2">
            <select v-model="form.categoria" class="flex-1 min-w-0 h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm outline-none">
              <option v-for="cat in categorias" :key="cat" :value="cat">{{ cat }}</option>
            </select>
            <button v-if="podeCriarCategoria" type="button" @click="abrirCategoria" class="h-11 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-700 cursor-pointer shrink-0">+ Nova</button>
          </div>
        </div>
        <label class="block space-y-1.5">
          <span class="text-sm font-semibold text-slate-800">Prazo para ler <span class="text-xs font-normal text-slate-400">(opcional)</span></span>
          <div class="flex items-center gap-2">
            <input v-model="form.prazo" type="date" class="flex-1 min-w-0 h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
            <button v-if="form.prazo" type="button" @click="form.prazo = ''" class="h-11 px-3 rounded-xl text-xs font-semibold text-slate-500 hover:bg-slate-100 cursor-pointer shrink-0">Sem prazo</button>
          </div>
        </label>
        <label v-if="form.tipo === 'treinamento'" class="block space-y-1.5">
          <span class="text-sm font-semibold text-slate-800">Carga horária <span class="text-xs font-normal text-slate-400">(vai no certificado)</span></span>
          <input v-model="form.carga_horaria" type="text" maxlength="20" placeholder="Ex.: 4 horas" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
        </label>
      </div>

      <div class="space-y-2">
        <p class="text-sm font-semibold text-slate-800">Tags para a busca do portal <span class="text-xs font-normal text-slate-400">(até 12 · Enter ou vírgula adiciona)</span></p>
        <div class="flex flex-wrap items-center gap-1.5 min-h-11 px-2.5 py-2 rounded-xl border border-slate-200 bg-slate-50 focus-within:bg-white focus-within:border-slate-400">
          <span v-for="t in form.tags" :key="t" class="inline-flex items-center gap-1 h-6 pl-2.5 pr-1 rounded-full bg-white border border-slate-200 text-[11px] font-semibold text-slate-700">
            {{ t }}
            <button type="button" @click="tirarTag(t)" class="w-4 h-4 rounded-full hover:bg-slate-200 text-slate-400 hover:text-slate-700 leading-none cursor-pointer" :aria-label="'Remover tag ' + t">×</button>
          </span>
          <input
            v-model="tagDigitada"
            type="text"
            maxlength="30"
            :disabled="form.tags.length >= 12"
            :placeholder="form.tags.length ? '' : 'Ex.: férias, vale transporte, benefícios'"
            @keydown.enter.prevent="adicionarTag"
            @keydown="teclaTag"
            @blur="adicionarTag"
            class="flex-1 min-w-[8rem] h-6 bg-transparent text-sm outline-none"
          />
        </div>
        <div v-if="sugestoesTags.length" class="flex flex-wrap items-center gap-1.5">
          <span class="text-[11px] text-slate-400">Já usadas:</span>
          <button v-for="t in sugestoesTags" :key="t" type="button" @mousedown.prevent @click="incluirTag(t)" class="h-6 px-2.5 rounded-full bg-slate-100 hover:bg-slate-200 text-[11px] font-semibold text-slate-600 cursor-pointer">+ {{ t }}</button>
        </div>
      </div>
    </template>

    <!-- 3. PÚBLICO -->
    <template #passo-publico>
      <div class="space-y-2">
        <p class="text-sm font-semibold text-slate-800">A leitura é...</p>
        <div class="grid sm:grid-cols-2 gap-3">
          <button type="button" @click="form.obrigatorio = true" class="text-left rounded-2xl border-2 p-4 cursor-pointer transition" :class="form.obrigatorio ? 'border-[#9c2337] bg-[#fdf2f4]' : 'border-slate-200 hover:border-slate-300'">
            <span class="block text-sm font-semibold text-slate-900">Obrigatória</span>
            <span class="block text-xs text-slate-500 mt-1">Entra no acompanhamento e pode ser cobrada do colaborador.</span>
          </button>
          <button type="button" @click="form.obrigatorio = false" class="text-left rounded-2xl border-2 p-4 cursor-pointer transition" :class="!form.obrigatorio ? 'border-slate-800 bg-slate-50' : 'border-slate-200 hover:border-slate-300'">
            <span class="block text-sm font-semibold text-slate-900">Opcional</span>
            <span class="block text-xs text-slate-500 mt-1">Fica disponível para quem quiser ler, sem cobrança.</span>
          </button>
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-4">
        <label class="block space-y-1.5">
          <span class="text-sm font-semibold text-slate-800">Empresa</span>
          <select v-model="form.empresa" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm outline-none">
            <option value="todas">Todas as empresas</option>
            <option v-for="e in empresas" :key="e" :value="e">{{ e }}</option>
          </select>
        </label>
        <label class="block space-y-1.5">
          <span class="text-sm font-semibold text-slate-800">Quem tem acesso</span>
          <select v-model="form.publico" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm outline-none">
            <option value="todos">Todos os colaboradores</option>
            <option value="departamentos">Departamentos específicos</option>
            <option value="colaboradores">Colaboradores específicos</option>
          </select>
        </label>
      </div>

      <div v-if="form.publico !== 'todos'" class="space-y-2">
        <div class="flex items-center gap-2">
          <input v-model="buscaPublico" type="search" :placeholder="form.publico === 'departamentos' ? 'Buscar departamento' : 'Buscar por nome ou matrícula'" class="flex-1 min-w-0 h-10 px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
          <button type="button" @click="marcarVisiveis" class="h-10 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-700 cursor-pointer shrink-0">Marcar todos</button>
          <button type="button" @click="form.valores = []" class="h-10 px-3 rounded-xl text-xs font-semibold text-slate-500 hover:bg-slate-100 cursor-pointer shrink-0">Limpar</button>
        </div>
        <div class="max-h-64 overflow-y-auto border border-slate-200 rounded-xl p-2 space-y-0.5">
          <label v-for="op in opcoesFiltradas" :key="op.valor" class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-slate-50 text-sm text-slate-700 cursor-pointer">
            <input type="checkbox" :value="op.valor" v-model="form.valores" class="accent-[#9c2337]" />
            <span class="truncate">{{ op.rotulo }}</span>
          </label>
          <p v-if="!opcoesFiltradas.length" class="py-4 text-center text-xs text-slate-400">Nada encontrado.</p>
        </div>
        <p class="text-xs text-slate-500">{{ form.valores.length }} selecionado(s)</p>
      </div>
    </template>

    <!-- 4. REVISAR -->
    <template #passo-revisar>
      <dl class="rounded-2xl border border-slate-200 divide-y divide-slate-100 text-sm">
        <div v-for="linha in resumo" :key="linha[0]" class="flex gap-3 px-4 py-3">
          <dt class="w-32 shrink-0 text-xs font-semibold uppercase tracking-wider text-slate-400 pt-0.5">{{ linha[0] }}</dt>
          <dd class="min-w-0 text-slate-800 break-words">{{ linha[1] }}</dd>
        </div>
      </dl>
      <div class="space-y-2">
        <p class="text-xs font-semibold text-slate-500">Como o colaborador verá</p>
        <div class="max-w-[220px]"><CartaoPrevia /></div>
      </div>
    </template>

    <template #previa>
      <CartaoPrevia />
      <p class="text-[11px] text-slate-400 leading-snug">Assim o caderno aparece no portal do colaborador.</p>
    </template>
  </WizardCadastro>

  <ModalCadastro
    :aberto="modalCategoria"
    titulo="Categorias"
    descricao="Crie uma categoria nova ou remova as que não usa mais."
    tamanho="sm"
    rotulo-salvar="Criar categoria"
    :enviando="salvandoCategoria"
    :pode-salvar="!!novaCategoria.trim()"
    :erro="erroCategoria"
    @fechar="modalCategoria = false"
    @salvar="criarCategoria"
  >
    <label class="block space-y-1.5">
      <span class="text-sm font-semibold text-slate-800">Nome da nova categoria</span>
      <input v-model="novaCategoria" type="text" maxlength="40" placeholder="Ex.: Segurança do trabalho" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
    </label>
    <div v-if="podeRemoverCategoria" class="space-y-1.5">
      <p class="text-xs font-semibold text-slate-500">Categorias existentes</p>
      <div class="flex flex-wrap gap-1.5">
        <span v-for="cat in categorias" :key="cat" class="inline-flex items-center gap-1 h-7 pl-2.5 pr-1 rounded-full bg-slate-100 text-[11px] text-slate-700">
          {{ cat }}
          <button v-if="cat !== 'Outros'" type="button" @click="removerCategoria(cat)" class="w-5 h-5 rounded-full hover:bg-slate-200 text-slate-400 hover:text-slate-700 flex items-center justify-center cursor-pointer" :aria-label="`Remover categoria ${cat}`"><AppIcon name="x" size="10" /></button>
        </span>
      </div>
    </div>
  </ModalCadastro>
</template>

<script setup>
import { ref, computed, watch, nextTick, defineComponent, h } from 'vue';
import WizardCadastro from './WizardCadastro.vue';
import ModalCadastro from './ModalCadastro.vue';
import AppIcon from './AppIcon.vue';
import { tokenAdmin } from '../utils/sessaoAdmin.js';
import { urlCapa } from '../utils/capas.js';

/**
 * Cadastro e edição de caderno (PDF) em wizard.
 *   <CadernoWizardRh :aberto="x" :caderno="cadernoEditando|null" :colaborador="colaborador"
 *     :empresas :categorias :departamentos :colaboradores :cadernos
 *     @fechar @salvo="(mensagem, ehErro)" @categorias="(lista)" />
 * Sem `caderno` cria um novo; com ele, edita (capa em chamada própria).
 */
const props = defineProps({
  aberto: { type: Boolean, default: false },
  caderno: { type: Object, default: null },
  colaborador: { type: Object, default: null },
  empresas: { type: Array, default: () => [] },
  categorias: { type: Array, default: () => [] },
  departamentos: { type: Array, default: () => [] },
  colaboradores: { type: Array, default: () => [] },
  cadernos: { type: Array, default: () => [] },
  podeEditar: { type: Boolean, default: false }
});
const emit = defineEmits(['fechar', 'salvo', 'categorias']);

const passos = [
  { id: 'arquivo', titulo: 'Arquivo', descricao: 'Escolha o PDF e, se quiser, uma capa.' },
  { id: 'detalhes', titulo: 'Detalhes', descricao: 'Onde aparece, nome, categoria e tags da busca.' },
  { id: 'publico', titulo: 'Público', descricao: 'Quem precisa ler e quem tem acesso.' },
  { id: 'revisar', titulo: 'Revisar', descricao: 'Confira tudo antes de salvar.' }
];
const passo = ref(0);
const editando = computed(() => !!props.caderno);
const auth = () => ({ Authorization: `Bearer ${tokenAdmin()}` });

const formVazio = () => ({ arquivo: '', titulo: '', tipo: 'politica', tags: [], carga_horaria: '', prazo: '', categoria: 'Outros', obrigatorio: true, publico: 'todos', valores: [], empresa: 'todas' });
const form = ref(formVazio());
const pdf = ref(null);
const arrastando = ref(false);
const capaEscolhida = ref(null);
const previaCapa = ref('');
const removerCapa = ref(false);
const erroCapa = ref('');
const tagDigitada = ref('');
const buscaPublico = ref('');
const enviando = ref(false);
const erro = ref('');
const alterou = ref(false);
let iniciando = false;

watch(() => props.aberto, (a) => {
  if (!a) return;
  iniciando = true;
  const c = props.caderno;
  form.value = c
    ? { arquivo: c.arquivo, titulo: c.titulo, tipo: c.tipo || 'politica', tags: [...(c.tags || [])], carga_horaria: c.carga_horaria || '', prazo: c.prazo || '', categoria: c.categoria, obrigatorio: !!c.obrigatorio, publico: c.publico, valores: [...(c.valores || [])], empresa: c.empresa || 'todas' }
    : formVazio();
  pdf.value = null;
  capaEscolhida.value = null;
  removerCapa.value = false;
  previaCapa.value = c ? (urlCapa(c) || '') : '';
  erroCapa.value = '';
  erro.value = '';
  tagDigitada.value = '';
  buscaPublico.value = '';
  alterou.value = false;
  nextTick(() => { iniciando = false; });
});
watch([form, pdf, capaEscolhida, removerCapa], () => { if (!iniciando) alterou.value = true; }, { deep: true });

watch(() => form.value.publico, (novo, antigo) => {
  if (antigo && novo !== antigo && !iniciando) form.value.valores = [];
  buscaPublico.value = '';
});
watch(() => form.value.empresa, () => {
  if (iniciando || form.value.publico !== 'colaboradores') return;
  const validos = new Set(opcoes.value.map((o) => o.valor));
  form.value.valores = form.value.valores.filter((v) => validos.has(v));
});

// ---- Arquivo e capa
const tamanho = (b) => (b >= 1024 * 1024 ? (b / 1024 / 1024).toFixed(1).replace('.', ',') + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB');

function definirPdf(f) {
  if (!f) return;
  if (!/\.pdf$/i.test(f.name)) { erro.value = 'Envie apenas arquivos PDF.'; return; }
  erro.value = '';
  pdf.value = f;
  if (!form.value.titulo.trim()) form.value.titulo = f.name.replace(/\.pdf$/i, '').replace(/[_-]+/g, ' ').trim();
}
function escolherPdf(e) { definirPdf(e.target.files?.[0]); e.target.value = ''; }
function soltarPdf(e) { arrastando.value = false; definirPdf(e.dataTransfer?.files?.[0]); }

function escolherCapa(e) {
  const f = e.target.files?.[0];
  e.target.value = '';
  if (!f) return;
  if (!/^image\/(jpeg|png|webp)$/.test(f.type)) { erroCapa.value = 'Use uma imagem JPG, PNG ou WEBP.'; return; }
  if (f.size > 3 * 1024 * 1024) { erroCapa.value = 'A imagem da capa pode ter no máximo 3 MB.'; return; }
  erroCapa.value = '';
  capaEscolhida.value = f;
  removerCapa.value = false;
  previaCapa.value = URL.createObjectURL(f);
}
function tirarCapa() {
  capaEscolhida.value = null;
  previaCapa.value = '';
  removerCapa.value = editando.value;
}

// ---- Tags
const sugestoesTags = computed(() => {
  const usadas = new Set(form.value.tags);
  const cont = {};
  props.cadernos.forEach((c) => (c.tags || []).forEach((t) => { cont[t] = (cont[t] || 0) + 1; }));
  return Object.keys(cont).filter((t) => !usadas.has(t)).sort((a, b) => cont[b] - cont[a] || a.localeCompare(b)).slice(0, 12);
});
function incluirTag(t) {
  const v = String(t).replace(/,/g, '').replace(/^#/, '').trim().toLowerCase().slice(0, 30);
  if (v && !form.value.tags.includes(v) && form.value.tags.length < 12) form.value.tags.push(v);
}
function adicionarTag() { incluirTag(tagDigitada.value); tagDigitada.value = ''; }
function tirarTag(t) { form.value.tags = form.value.tags.filter((x) => x !== t); }
function teclaTag(e) {
  if (e.key === ',') { e.preventDefault(); adicionarTag(); }
  else if (e.key === 'Backspace' && !tagDigitada.value && form.value.tags.length) tirarTag(form.value.tags[form.value.tags.length - 1]);
}

// ---- Público
const opcoes = computed(() =>
  form.value.publico === 'departamentos'
    ? props.departamentos.map((d) => ({ valor: d, rotulo: d }))
    : props.colaboradores
        .filter((c) => form.value.empresa === 'todas' || c.empresa === form.value.empresa)
        .map((c) => ({ valor: c.matricula, rotulo: `${c.nome} (${c.matricula})` }))
);
const opcoesFiltradas = computed(() => {
  const t = buscaPublico.value.trim().toLowerCase();
  return t ? opcoes.value.filter((o) => o.rotulo.toLowerCase().includes(t)) : opcoes.value;
});
function marcarVisiveis() {
  form.value.valores = [...new Set([...form.value.valores, ...opcoesFiltradas.value.map((o) => o.valor)])];
}
function descreverPublico() {
  const f = form.value;
  if (f.publico === 'departamentos') return `Departamentos: ${f.valores.join(', ')}`;
  if (f.publico === 'colaboradores') return `${f.valores.length} colaborador(es) escolhido(s)`;
  return 'Todos os colaboradores';
}

// ---- Validação e resumo
function validar(id) {
  const f = form.value;
  if (id === 'arquivo' && !editando.value && !pdf.value) return 'Escolha o arquivo PDF do caderno para continuar.';
  if (id === 'detalhes') {
    if (!f.titulo.trim()) return 'Dê um nome ao caderno para continuar.';
    if (!f.categoria) return 'Escolha a categoria do caderno.';
  }
  if (id === 'publico' && f.publico !== 'todos' && !f.valores.length) {
    return f.publico === 'departamentos' ? 'Marque ao menos um departamento.' : 'Marque ao menos um colaborador.';
  }
  return '';
}
const resumo = computed(() => {
  const f = form.value;
  const linhas = [
    ['Arquivo', editando.value ? f.arquivo : (pdf.value ? `${pdf.value.name} · ${tamanho(pdf.value.size)}` : '—')],
    ['Capa', previaCapa.value ? 'Personalizada' : 'Sem capa (o portal mostra o padrão)'],
    ['Nome', f.titulo || '—'],
    ['Aparece em', f.tipo === 'treinamento' ? 'Academia (gera certificado)' : 'Diretrizes (consulta e pesquisa)']
  ];
  if (f.tipo === 'treinamento') linhas.push(['Carga horária', f.carga_horaria || 'Não informada']);
  linhas.push(['Categoria', f.categoria], ['Tags', f.tags.length ? f.tags.join(', ') : 'Nenhuma'], ['Leitura', f.obrigatorio ? 'Obrigatória' : 'Opcional'], ['Empresa', f.empresa === 'todas' ? 'Todas as empresas' : f.empresa], ['Público', descreverPublico()]);
  return linhas;
});

// Cartão como o colaborador verá
const CartaoPrevia = defineComponent({
  setup() {
    return () => {
      const f = form.value;
      return h('div', { class: 'rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm' }, [
        h('div', { class: 'aspect-[3/4] bg-[#9c2337] flex items-center justify-center overflow-hidden' },
          previaCapa.value ? [h('img', { src: previaCapa.value, class: 'w-full h-full object-cover', alt: '' })] : [h('span', { class: 'text-white/80 text-xs font-semibold' }, 'PDF')]),
        h('div', { class: 'p-3 space-y-1' }, [
          h('p', { class: 'text-sm font-semibold text-slate-900 leading-snug break-words' }, f.titulo || 'Nome do caderno'),
          h('p', { class: 'text-[11px] text-slate-500' }, `${f.categoria}${f.tipo === 'treinamento' && f.carga_horaria ? ' · ' + f.carga_horaria : ''}`),
          h('span', { class: 'inline-block text-[10px] font-semibold px-2 py-0.5 rounded-full ' + (f.obrigatorio ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-600') }, f.obrigatorio ? 'Leitura obrigatória' : 'Opcional')
        ])
      ]);
    };
  }
});

// ---- Categorias
const modalCategoria = ref(false);
const novaCategoria = ref('');
const erroCategoria = ref('');
const salvandoCategoria = ref(false);
const podeCriarCategoria = computed(() => props.podeEditar);
const podeRemoverCategoria = computed(() => props.podeEditar);
function abrirCategoria() { novaCategoria.value = ''; erroCategoria.value = ''; modalCategoria.value = true; }
async function postCategoria(rota, nome) {
  const res = await fetch(`/api/rh/cadernos/categorias${rota}`, { method: 'POST', headers: { 'Content-Type': 'application/json', ...auth() }, body: JSON.stringify({ nome }) });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) { erroCategoria.value = data.erro || 'Não foi possível salvar a categoria.'; return null; }
  emit('categorias', data.categorias);
  return data;
}
async function criarCategoria() {
  const nome = novaCategoria.value.trim();
  if (!nome) return;
  salvandoCategoria.value = true;
  erroCategoria.value = '';
  const data = await postCategoria('', nome);
  salvandoCategoria.value = false;
  if (data) { form.value.categoria = nome; modalCategoria.value = false; }
}
async function removerCategoria(nome) {
  if (!window.confirm(`Remover a categoria "${nome}"?`)) return;
  erroCategoria.value = '';
  const data = await postCategoria('/remover', nome);
  if (data && form.value.categoria === nome) form.value.categoria = 'Outros';
}

// ---- Salvar
async function salvar() {
  const f = form.value;
  enviando.value = true;
  erro.value = '';
  const config = { titulo: f.titulo.trim(), tipo: f.tipo, tags: f.tags, carga_horaria: f.tipo === 'treinamento' ? f.carga_horaria : '', prazo: f.prazo || '', categoria: f.categoria, obrigatorio: f.obrigatorio, publico: f.publico, valores: f.publico === 'todos' ? [] : f.valores, empresa: f.empresa };
  try {
    let res;
    if (f.arquivo) {
      res = await fetch('/api/rh/cadernos/config', { method: 'POST', headers: { 'Content-Type': 'application/json', ...auth() }, body: JSON.stringify({ arquivo: f.arquivo, ...config }) });
    } else {
      const fd = new FormData();
      fd.append('arquivo', pdf.value);
      fd.append('config', JSON.stringify(config));
      if (capaEscolhida.value) fd.append('capa', capaEscolhida.value);
      res = await fetch('/api/rh/cadernos', { method: 'POST', headers: auth(), body: fd });
    }
    const data = await res.json();
    if (!res.ok) throw new Error(data.erro);

    let aviso = data.aviso || '';
    if (f.arquivo && capaEscolhida.value) {
      const fdCapa = new FormData();
      fdCapa.append('arquivo', f.arquivo);
      fdCapa.append('capa', capaEscolhida.value);
      const r = await fetch('/api/rh/cadernos/capa', { method: 'POST', headers: auth(), body: fdCapa });
      if (!r.ok) aviso = 'Caderno salvo, mas a capa não: ' + ((await r.json().catch(() => ({}))).erro || 'erro ao enviar.');
    } else if (f.arquivo && removerCapa.value) {
      await fetch('/api/rh/cadernos/capa/remover', { method: 'POST', headers: { 'Content-Type': 'application/json', ...auth() }, body: JSON.stringify({ arquivo: f.arquivo }) });
    }
    alterou.value = false;
    emit('salvo', aviso || (f.arquivo ? 'Caderno atualizado.' : 'Caderno publicado.'), !!aviso);
    emit('fechar');
  } catch (e) {
    erro.value = e.message || 'Não foi possível salvar o caderno.';
  } finally {
    enviando.value = false;
  }
}
</script>
