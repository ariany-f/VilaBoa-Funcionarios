<template>
  <div class="space-y-5">
    <!-- Cabeçalho -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 space-y-4">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800">Comunicados</h3>
          <p class="text-xs text-slate-500 mt-0.5">Mural de avisos, campanhas, prazos e mudanças de rotina do portal.</p>
        </div>
        <button v-if="pode(colaborador, 'rh_cadernos', 'publicar')" type="button" @click="abrirNovo" class="h-11 px-6 rounded-full bg-[#9c2337] hover:bg-[#831b2d] text-white text-sm font-semibold cursor-pointer shadow-sm">+ Novo comunicado</button>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <button
          v-for="a in abas"
          :key="a.id"
          type="button"
          @click="aba = a.id"
          class="h-9 px-4 rounded-full border text-xs font-semibold transition cursor-pointer"
          :class="aba === a.id ? 'bg-[#141414] border-[#141414] text-white' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300'"
        >{{ a.rotulo }} <span class="opacity-60 ml-1">{{ contagem[a.id] }}</span></button>
        <div class="ml-auto relative w-full sm:w-64">
          <AppIcon name="search" size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input v-model="busca" type="search" placeholder="Buscar comunicado" class="w-full h-9 pl-9 pr-3 rounded-full border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
        </div>
      </div>

      <p v-if="mensagem" class="text-xs font-medium" :class="mensagemErro ? 'text-rose-700' : 'text-emerald-700'">{{ mensagem }}</p>
    </div>

    <!-- Lista em cards -->
    <div v-if="carregando" class="py-12 text-center text-sm text-slate-400">Carregando comunicados...</div>
    <div v-else-if="!visiveis.length" class="bg-white rounded-2xl border border-dashed border-slate-300 py-14 text-center space-y-2">
      <AppIcon name="megaphone" size="28" class="mx-auto text-slate-300" />
      <p class="text-sm text-slate-500">{{ busca ? 'Nenhum comunicado encontrado para essa busca.' : 'Nenhum comunicado nesta aba.' }}</p>
      <button v-if="!busca && aba === 'publicados' && pode(colaborador, 'rh_cadernos', 'publicar')" type="button" @click="abrirNovo" class="text-sm font-semibold text-[#9c2337] hover:underline cursor-pointer">Criar o primeiro comunicado</button>
    </div>
    <ul v-else class="grid grid-cols-1 gap-3 md:grid-cols-2">
      <li v-for="c in visiveis" :key="c.id" class="bg-white rounded-2xl border p-5 flex flex-col gap-3" :class="c.prioridade === 'importante' ? 'border-amber-300' : 'border-slate-200'">
        <div class="flex items-start gap-3">
          <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" :class="tipoDe(c.categoria).fundo"><AppIcon :name="tipoDe(c.categoria).icone" size="18" /></span>
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-1.5 mb-1">
              <span class="selo" :class="tipoDe(c.categoria).selo">{{ c.categoria }}</span>
              <span v-if="c.prioridade === 'importante'" class="selo bg-amber-100 text-amber-800 border-amber-200">Importante</span>
              <span v-if="c.fixado" class="selo bg-[#fdf2f4] text-[#9c2337] border-rose-200">Fixado</span>
              <span class="selo bg-slate-100 text-slate-600 border-slate-200">{{ c.empresa === 'todas' ? 'Todas as empresas' : c.empresa }}</span>
              <span class="selo" :class="situacoes[c.situacao].classe">{{ situacoes[c.situacao].rotulo }}</span>
            </div>
            <h4 class="text-sm font-semibold text-slate-900 leading-snug">{{ c.titulo }}</h4>
            <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ resumoTexto(c.texto) }}</p>
          </div>
        </div>
        <p class="text-[11px] text-slate-400">
          {{ publicoTexto(c) }} ·
          <template v-if="c.situacao === 'agendado'">Publica em {{ dataBr(c.publicar_em) }} às {{ horaBr(c.publicar_em) }}</template>
          <template v-else-if="c.situacao === 'rascunho'">Rascunho{{ c.publicar_em ? ' · agendar para ' + dataBr(c.publicar_em) : '' }}</template>
          <template v-else>Publicado em {{ dataBr(c.publicar_em || c.publicado_em) }}</template>
          · {{ c.validade ? (c.vencido ? 'Venceu em ' : 'Até ') + dataBr(c.validade) : 'Sem validade' }}
        </p>
        <div class="flex flex-wrap gap-1 pt-2 border-t border-slate-100 -mx-1">
          <button v-if="pode(colaborador, 'rh_cadernos', 'editar')" type="button" @click="abrirEdicao(c)" class="acao">Editar</button>
          <button v-if="pode(colaborador, 'rh_cadernos', 'publicar')" type="button" @click="acaoRapida(c, 'duplicar')" class="acao">Duplicar</button>
          <button v-if="pode(colaborador, 'rh_cadernos', 'editar') && c.situacao !== 'arquivado' && c.situacao !== 'rascunho'" type="button" @click="acaoRapida(c, c.fixado ? 'desafixar' : 'fixar')" class="acao">{{ c.fixado ? 'Desafixar' : 'Fixar' }}</button>
          <button v-if="pode(colaborador, 'rh_cadernos', 'editar') && c.situacao !== 'arquivado' && c.situacao !== 'rascunho'" type="button" @click="acaoRapida(c, 'arquivar')" class="acao">Arquivar</button>
          <button v-if="pode(colaborador, 'rh_cadernos', 'editar') && c.situacao === 'arquivado'" type="button" @click="acaoRapida(c, 'reativar')" class="acao">Reativar</button>
          <button v-if="pode(colaborador, 'rh_cadernos', 'remover')" type="button" @click="paraRemover = c" class="acao hover:!bg-rose-50 hover:!text-rose-700 ml-auto">Remover</button>
        </div>
      </li>
    </ul>

    <!-- Confirmação de remoção -->
    <ModalCadastro :aberto="!!paraRemover" titulo="Remover comunicado" tamanho="sm" rotulo-salvar="Remover" :enviando="removendo" @fechar="paraRemover = null" @salvar="remover">
      <p class="text-sm text-slate-600">Remover o comunicado <strong class="text-slate-900">"{{ paraRemover?.titulo }}"</strong>? Ele some do mural e não dá para desfazer. Para só tirar do mural, use <strong>Arquivar</strong>.</p>
    </ModalCadastro>

    <!-- Wizard de criação e edição -->
    <WizardCadastro
      :aberto="wizardAberto"
      :titulo="form.id ? 'Editar comunicado' : 'Novo comunicado'"
      descricao="Siga os passos: tipo, mensagem, público e publicação."
      :passos="passos"
      v-model:passo="passo"
      :validar="validar"
      :sujo="sujo"
      :enviando="enviando"
      :erro="erroWizard"
      :pode-rascunho="true"
      :rotulo-concluir="rotuloConcluir"
      @fechar="fecharWizard"
      @concluir="salvar('publicado')"
      @rascunho="salvar('rascunho')"
    >
      <!-- 1. Tipo -->
      <template #passo-tipo>
        <div class="grid sm:grid-cols-2 gap-3">
          <button v-for="t in tipos" :key="t.nome" type="button" @click="escolherTipo(t)" class="text-left p-4 rounded-2xl border-2 transition cursor-pointer flex items-start gap-3" :class="form.categoria === t.nome ? 'border-[#9c2337] bg-[#fdf2f4]' : 'border-slate-200 hover:border-slate-300 bg-white'">
            <span class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" :class="t.fundo"><AppIcon :name="t.icone" size="20" /></span>
            <span>
              <span class="block text-sm font-semibold text-slate-900">{{ t.nome }}</span>
              <span class="block text-xs text-slate-500 mt-0.5">{{ t.descricao }}</span>
            </span>
          </button>
        </div>
        <p class="text-xs text-slate-400">Ao escolher o tipo sugerimos um texto-modelo (só se a mensagem ainda estiver vazia ou intocada).</p>
      </template>

      <!-- 2. Mensagem -->
      <template #passo-mensagem>
        <label class="block text-xs font-medium text-slate-700 space-y-1.5">
          <span>Título</span>
          <input v-model="form.titulo" type="text" maxlength="120" placeholder="Ex.: Campanha de vacinação contra a gripe" class="campo h-11" />
        </label>
        <div class="space-y-1.5">
          <div class="flex items-center justify-between gap-2">
            <span class="text-xs font-medium text-slate-700">Texto</span>
            <div class="flex items-center gap-1">
              <button type="button" @click="formatar('negrito')" class="h-8 px-3 rounded-lg border border-slate-200 text-xs font-bold hover:bg-slate-100 cursor-pointer" title="Negrito">N</button>
              <button type="button" @click="formatar('lista')" class="h-8 px-3 rounded-lg border border-slate-200 text-xs font-semibold hover:bg-slate-100 cursor-pointer" title="Lista">• Lista</button>
              <button type="button" @click="formatar('link')" class="h-8 px-3 rounded-lg border border-slate-200 text-xs font-semibold hover:bg-slate-100 cursor-pointer" title="Link">Link</button>
            </div>
          </div>
          <textarea ref="areaTexto" v-model="form.texto" rows="9" maxlength="5000" placeholder="Escreva a mensagem que os colaboradores vão ler" class="campo py-2.5"></textarea>
          <p class="text-[11px] text-slate-400">Selecione um trecho e use os botões. <strong>**negrito**</strong>, linhas começando com <code>-</code> viram lista e <code>[texto](https://site)</code> vira link. {{ form.texto.length }}/5000</p>
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
          <div class="space-y-1.5">
            <span class="block text-xs font-medium text-slate-700">Prioridade</span>
            <div class="flex gap-2">
              <button type="button" @click="form.prioridade = 'normal'" class="flex-1 h-11 rounded-xl border-2 text-sm font-semibold cursor-pointer" :class="form.prioridade === 'normal' ? 'border-[#9c2337] bg-[#fdf2f4] text-[#9c2337]' : 'border-slate-200 text-slate-600'">Normal</button>
              <button type="button" @click="form.prioridade = 'importante'" class="flex-1 h-11 rounded-xl border-2 text-sm font-semibold cursor-pointer" :class="form.prioridade === 'importante' ? 'border-amber-400 bg-amber-50 text-amber-800' : 'border-slate-200 text-slate-600'">Importante</button>
            </div>
          </div>
          <label class="flex items-center gap-3 h-11 sm:mt-6 px-4 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 cursor-pointer">
            <input type="checkbox" v-model="form.fixado" class="accent-[#9c2337] w-4 h-4" />
            Fixar no topo do mural
          </label>
        </div>
      </template>

      <!-- 3. Público -->
      <template #passo-publico>
        <label class="block text-xs font-medium text-slate-700 space-y-1.5">
          <span>Empresa</span>
          <select v-model="form.empresa" class="campo h-11">
            <option value="todas">Todas as empresas</option>
            <option v-for="e in empresas" :key="e" :value="e">{{ e }}</option>
          </select>
        </label>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
          <button v-for="p in publicos" :key="p.id" type="button" @click="escolherPublico(p.id)" class="text-left p-3.5 rounded-xl border-2 cursor-pointer transition" :class="form.publico === p.id ? 'border-[#9c2337] bg-[#fdf2f4]' : 'border-slate-200 hover:border-slate-300'">
            <span class="block text-sm font-semibold text-slate-900">{{ p.titulo }}</span>
            <span class="block text-xs text-slate-500">{{ p.descricao }}</span>
          </button>
        </div>

        <div v-if="form.publico === 'departamentos'" class="grid sm:grid-cols-2 gap-2">
          <label v-for="d in departamentos" :key="d" class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 text-sm text-slate-700 cursor-pointer hover:bg-slate-50">
            <input type="checkbox" :value="d" v-model="form.valores" class="accent-[#9c2337] w-4 h-4" /> {{ d }}
          </label>
          <p v-if="!departamentos.length" class="text-xs text-slate-400">Nenhum departamento cadastrado.</p>
        </div>

        <div v-if="form.publico === 'colaboradores'" class="space-y-2">
          <input v-model="buscaColab" type="search" placeholder="Buscar por nome ou matrícula" class="campo h-10" />
          <div class="max-h-64 overflow-y-auto rounded-xl border border-slate-200 divide-y divide-slate-100">
            <label v-for="c in colaboradoresFiltrados" :key="c.matricula" class="flex items-center gap-2.5 px-3 py-2.5 text-sm text-slate-700 cursor-pointer hover:bg-slate-50">
              <input type="checkbox" :value="c.matricula" v-model="form.valores" class="accent-[#9c2337] w-4 h-4" />
              <span class="min-w-0 truncate">{{ c.nome }} <span class="text-slate-400">({{ c.matricula }})</span></span>
            </label>
            <p v-if="!colaboradoresFiltrados.length" class="p-4 text-xs text-slate-400 text-center">Nenhum colaborador encontrado.</p>
          </div>
        </div>

        <p class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600">
          <strong class="text-slate-900">{{ alcance }}</strong> {{ alcance === 1 ? 'colaborador vai' : 'colaboradores vão' }} ver este comunicado.
        </p>
      </template>

      <!-- 4. Agendar e publicar -->
      <template #passo-publicar>
        <div class="grid sm:grid-cols-2 gap-3">
          <button type="button" @click="form.modo = 'agora'" class="text-left p-4 rounded-2xl border-2 cursor-pointer" :class="form.modo === 'agora' ? 'border-[#9c2337] bg-[#fdf2f4]' : 'border-slate-200 hover:border-slate-300'">
            <span class="block text-sm font-semibold text-slate-900">Publicar agora</span>
            <span class="block text-xs text-slate-500">Aparece no mural assim que concluir.</span>
          </button>
          <button type="button" @click="form.modo = 'agendar'" class="text-left p-4 rounded-2xl border-2 cursor-pointer" :class="form.modo === 'agendar' ? 'border-[#9c2337] bg-[#fdf2f4]' : 'border-slate-200 hover:border-slate-300'">
            <span class="block text-sm font-semibold text-slate-900">Agendar</span>
            <span class="block text-xs text-slate-500">Escolha a data e a hora de publicação.</span>
          </button>
        </div>
        <label v-if="form.modo === 'agendar'" class="block text-xs font-medium text-slate-700 space-y-1.5">
          <span>Publicar em</span>
          <input v-model="form.publicar_em" type="datetime-local" :min="agoraLocal()" class="campo h-11" />
        </label>
        <label class="block text-xs font-medium text-slate-700 space-y-1.5">
          <span>Válido até <span class="text-slate-400 font-normal">(opcional — depois disso sai do mural)</span></span>
          <input v-model="form.validade" type="date" class="campo h-11" />
        </label>

        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-1">
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Resumo</p>
          <p><strong class="text-slate-900">{{ form.titulo }}</strong> · {{ form.categoria }}{{ form.prioridade === 'importante' ? ' · Importante' : '' }}{{ form.fixado ? ' · Fixado' : '' }}</p>
          <p>{{ publicoTexto(form) }} · {{ alcance }} {{ alcance === 1 ? 'pessoa' : 'pessoas' }}</p>
          <p>{{ form.modo === 'agendar' && form.publicar_em ? 'Publica em ' + dataBr(form.publicar_em) + ' às ' + horaBr(form.publicar_em) : 'Publica ao concluir' }}{{ form.validade ? ' · válido até ' + dataBr(form.validade) : ' · sem validade' }}</p>
          <p class="text-slate-400">Prefere revisar depois? Use <strong>Salvar rascunho</strong> — ele não aparece para os colaboradores.</p>
        </div>
      </template>

      <template #previa>
        <article class="rounded-2xl border p-4 space-y-2 bg-white" :class="form.prioridade === 'importante' ? 'border-amber-300 bg-amber-50/60' : 'border-slate-200'">
          <div class="flex flex-wrap items-center gap-1.5">
            <span class="selo" :class="tipoDe(form.categoria).selo">{{ form.categoria }}</span>
            <span class="selo bg-emerald-100 text-emerald-800 border-emerald-200">Novo</span>
            <span v-if="form.prioridade === 'importante'" class="selo bg-amber-100 text-amber-800 border-amber-200">Importante</span>
            <span v-if="form.fixado" class="selo bg-[#fdf2f4] text-[#9c2337] border-rose-200">Fixado</span>
          </div>
          <h4 class="text-sm font-semibold text-slate-900 leading-snug">{{ form.titulo || 'Título do comunicado' }}</h4>
          <div class="text-xs text-slate-600 leading-relaxed space-y-1" v-html="htmlPrevia"></div>
          <p v-if="form.validade" class="text-[11px] text-slate-400">Válido até {{ dataBr(form.validade) }}</p>
        </article>
        <p class="text-[11px] text-slate-400">Assim o card aparece no mural do colaborador.</p>
      </template>
    </WizardCadastro>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppIcon from './AppIcon.vue';
import ModalCadastro from './ModalCadastro.vue';
import WizardCadastro from './WizardCadastro.vue';
import { pode } from '../utils/permissoes.js';
import { tokenAdmin } from '../utils/sessaoAdmin.js';
import { textoParaHtml, dataBr, horaBr } from '../utils/textoFormatado.js';

defineProps({ colaborador: { type: Object, default: null } });

const tipos = [
  { nome: 'Aviso', icone: 'bell', descricao: 'Informações gerais para a equipe.', fundo: 'bg-sky-100 text-sky-700', selo: 'bg-sky-50 text-sky-700 border-sky-200',
    modelo: 'Olá, equipe!\n\nQueremos avisar que ...\n\n**O que muda:** ...\n\nQualquer dúvida, fale com o RH.' },
  { nome: 'Campanha', icone: 'megaphone', descricao: 'Vacinação, doação, ações de saúde e engajamento.', fundo: 'bg-emerald-100 text-emerald-700', selo: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    modelo: 'Vem aí uma nova campanha!\n\n**Quando:** ...\n**Onde:** ...\n**Quem pode participar:** todos os colaboradores\n\nParticipe e convide os colegas.' },
  { nome: 'Prazo', icone: 'calendar', descricao: 'Datas-limite: documentos, benefícios, entregas.', fundo: 'bg-amber-100 text-amber-700', selo: 'bg-amber-50 text-amber-700 border-amber-200',
    modelo: 'Atenção ao prazo!\n\n**Data-limite:** ...\n\nO que fazer:\n- Passo 1\n- Passo 2\n\nApós a data não será possível ...' },
  { nome: 'Mudança de rotina', icone: 'refresh-cw', descricao: 'Horários, processos e regras que passam a valer.', fundo: 'bg-violet-100 text-violet-700', selo: 'bg-violet-50 text-violet-700 border-violet-200',
    modelo: 'A partir de **...** a rotina muda:\n\n- Antes: ...\n- Agora: ...\n\nPor que mudamos: ...\n\nDúvidas? Procure seu gestor ou o RH.' }
];
const tipoDe = (nome) => tipos.find((t) => t.nome === nome) || tipos[0];

const publicos = [
  { id: 'todos', titulo: 'Todos', descricao: 'Toda a empresa escolhida.' },
  { id: 'departamentos', titulo: 'Departamentos', descricao: 'Só quem está nos setores marcados.' },
  { id: 'colaboradores', titulo: 'Pessoas', descricao: 'Colaboradores específicos.' }
];

const passos = [
  { id: 'tipo', titulo: 'Tipo', descricao: 'Que tipo de comunicado você vai fazer?' },
  { id: 'mensagem', titulo: 'Mensagem', descricao: 'Escreva o título e o texto; a prévia mostra como fica no mural.' },
  { id: 'publico', titulo: 'Público', descricao: 'Quem deve receber este comunicado?' },
  { id: 'publicar', titulo: 'Publicar', descricao: 'Publique agora, agende ou salve como rascunho.' }
];

const abas = [
  { id: 'publicados', rotulo: 'Publicados' },
  { id: 'agendados', rotulo: 'Agendados' },
  { id: 'rascunhos', rotulo: 'Rascunhos' },
  { id: 'arquivados', rotulo: 'Vencidos / Arquivados' }
];
const situacoes = {
  publicado: { rotulo: 'Publicado', classe: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
  agendado: { rotulo: 'Agendado', classe: 'bg-sky-50 text-sky-700 border-sky-200' },
  rascunho: { rotulo: 'Rascunho', classe: 'bg-slate-100 text-slate-600 border-slate-200' },
  vencido: { rotulo: 'Vencido', classe: 'bg-slate-100 text-slate-500 border-slate-200' },
  arquivado: { rotulo: 'Arquivado', classe: 'bg-slate-100 text-slate-500 border-slate-200' }
};
const abaDaSituacao = { publicado: 'publicados', agendado: 'agendados', rascunho: 'rascunhos', vencido: 'arquivados', arquivado: 'arquivados' };

const lista = ref([]);
const empresas = ref([]);
const departamentos = ref([]);
const colaboradores = ref([]);
const carregando = ref(true);
const mensagem = ref('');
const mensagemErro = ref(false);
const aba = ref('publicados');
const busca = ref('');
const buscaColab = ref('');

const contagem = computed(() => {
  const n = { publicados: 0, agendados: 0, rascunhos: 0, arquivados: 0 };
  lista.value.forEach((c) => { n[abaDaSituacao[c.situacao]]++; });
  return n;
});
const visiveis = computed(() => {
  const q = busca.value.trim().toLowerCase();
  // Buscando, procura em todas as abas
  return lista.value.filter((c) => (q ? true : abaDaSituacao[c.situacao] === aba.value) && (!q || `${c.titulo} ${c.texto} ${c.categoria}`.toLowerCase().includes(q)));
});

const resumoTexto = (t) => (t || '').replace(/\*\*/g, '').replace(/\[([^\]]+)\]\([^)]+\)/g, '$1').replace(/\s+/g, ' ').trim();
const publicoTexto = (c) => {
  const emp = c.empresa === 'todas' ? 'Todas as empresas' : c.empresa;
  if (c.publico === 'departamentos') return `${emp} · Departamentos: ${(c.valores || []).join(', ')}`;
  if (c.publico === 'colaboradores') return `${emp} · ${(c.valores || []).length} colaborador(es) específico(s)`;
  return `${emp} · Todos`;
};

const auth = () => ({ Authorization: `Bearer ${tokenAdmin()}` });
const json = { 'Content-Type': 'application/json' };
function aviso(texto, erro = false) { mensagem.value = texto; mensagemErro.value = erro; }

async function carregar() {
  try {
    const res = await fetch('/api/rh/comunicados', { headers: auth() });
    const data = await res.json();
    if (!res.ok) return aviso(data.erro || 'Não foi possível carregar os comunicados.', true);
    lista.value = data.comunicados;
  } finally {
    carregando.value = false;
  }
}

// ---------- Wizard ----------
const vazio = () => ({ id: '', titulo: '', texto: '', categoria: 'Aviso', prioridade: 'normal', fixado: false, validade: '', empresa: 'todas', publico: 'todos', valores: [], modo: 'agora', publicar_em: '', situacao: '' });
const form = ref(vazio());
const original = ref('');
const wizardAberto = ref(false);
const passo = ref(0);
const enviando = ref(false);
const erroWizard = ref('');
const areaTexto = ref(null);
const modeloAplicado = ref('');

const sujo = computed(() => JSON.stringify(form.value) !== original.value);
const rotuloConcluir = computed(() => (form.value.modo === 'agendar' ? 'Agendar publicação' : (form.value.id && form.value.situacao === 'publicado' ? 'Salvar alterações' : 'Publicar agora')));

function abrirWizard(dados) {
  form.value = dados;
  original.value = JSON.stringify(dados);
  modeloAplicado.value = '';
  erroWizard.value = '';
  buscaColab.value = '';
  wizardAberto.value = true;
}
function abrirNovo() {
  const f = vazio();
  f.texto = tipos[0].modelo;
  modeloAplicado.value = f.texto;
  abrirWizard(f);
  modeloAplicado.value = f.texto;
}
function abrirEdicao(c) {
  const agendado = !!c.publicar_em && c.publicar_em > agoraBanco() && c.situacao !== 'publicado';
  abrirWizard({
    id: c.id, titulo: c.titulo, texto: c.texto, categoria: c.categoria, prioridade: c.prioridade, fixado: !!c.fixado, validade: c.validade || '',
    empresa: c.empresa || 'todas', publico: c.publico || 'todos', valores: [...(c.valores || [])],
    modo: agendado ? 'agendar' : 'agora', publicar_em: agendado ? c.publicar_em.slice(0, 16).replace(' ', 'T') : '', situacao: c.situacao
  });
}
function fecharWizard() { wizardAberto.value = false; }

const pad = (n) => String(n).padStart(2, '0');
function agoraLocal() {
  const d = new Date();
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}
const agoraBanco = () => agoraLocal().replace('T', ' ') + ':00';

function escolherTipo(t) {
  const texto = form.value.texto.trim();
  if (!texto || texto === modeloAplicado.value.trim()) {
    form.value.texto = t.modelo;
    modeloAplicado.value = t.modelo;
  }
  form.value.categoria = t.nome;
}
function escolherPublico(id) {
  if (form.value.publico !== id) form.value.valores = [];
  form.value.publico = id;
}

function formatar(tipo) {
  const el = areaTexto.value;
  if (!el) return;
  const { selectionStart: a, selectionEnd: b, value } = el;
  const sel = value.slice(a, b);
  let novo;
  if (tipo === 'negrito') novo = `**${sel || 'texto em destaque'}**`;
  else if (tipo === 'link') {
    const url = window.prompt('Endereço do link (https://...)', 'https://');
    if (!url || !/^(https?:\/\/|mailto:)\S+$/i.test(url)) return;
    novo = `[${sel || 'clique aqui'}](${url})`;
  } else novo = (sel || 'item').split('\n').map((l) => `- ${l.replace(/^\s*[-•]\s+/, '')}`).join('\n');
  form.value.texto = value.slice(0, a) + novo + value.slice(b);
  requestAnimationFrame(() => { el.focus(); el.setSelectionRange(a, a + novo.length); });
}

const htmlPrevia = computed(() => textoParaHtml(form.value.texto) || '<p class="text-slate-400">O texto da mensagem aparece aqui.</p>');

// Público
const colaboradoresDaEmpresa = computed(() => colaboradores.value.filter((c) => form.value.empresa === 'todas' || c.empresa === form.value.empresa));
const colaboradoresFiltrados = computed(() => {
  const q = buscaColab.value.trim().toLowerCase();
  return colaboradoresDaEmpresa.value.filter((c) => !q || `${c.nome} ${c.matricula}`.toLowerCase().includes(q));
});
const alcance = computed(() => {
  const base = colaboradoresDaEmpresa.value;
  if (form.value.publico === 'departamentos') return base.filter((c) => form.value.valores.includes(c.departamento)).length;
  if (form.value.publico === 'colaboradores') return base.filter((c) => form.value.valores.includes(c.matricula)).length;
  return base.length;
});

function validar(id) {
  const f = form.value;
  if (id === 'mensagem') {
    if (!f.titulo.trim()) return 'Dê um título ao comunicado.';
    if (!f.texto.trim()) return 'Escreva o texto do comunicado.';
  }
  if (id === 'publico' && f.publico !== 'todos' && !f.valores.length) {
    return f.publico === 'departamentos' ? 'Marque ao menos um departamento ou volte para "Todos".' : 'Marque ao menos um colaborador ou volte para "Todos".';
  }
  if (id === 'publicar') {
    if (f.modo === 'agendar' && !f.publicar_em) return 'Escolha a data e a hora da publicação.';
    if (f.modo === 'agendar' && f.publicar_em.replace('T', ' ') < agoraBanco().slice(0, 16)) return 'A data de publicação precisa ser no futuro.';
    const inicio = f.modo === 'agendar' ? f.publicar_em.slice(0, 10) : agoraLocal().slice(0, 10);
    if (f.validade && f.validade < inicio) return 'A validade não pode ser antes da publicação.';
  }
  return '';
}

async function salvar(status) {
  erroWizard.value = '';
  if (status === 'rascunho') {
    const falta = validar('mensagem');
    if (falta) { passo.value = 1; erroWizard.value = falta; return; }
  }
  enviando.value = true;
  try {
    const f = form.value;
    const corpo = { ...f, status, publicar_em: f.modo === 'agendar' ? f.publicar_em : '' };
    const res = await fetch('/api/rh/comunicados', { method: 'POST', headers: { ...json, ...auth() }, body: JSON.stringify(corpo) });
    const data = await res.json();
    if (!res.ok) throw new Error(data.erro);
    const agendou = status === 'publicado' && f.modo === 'agendar';
    aviso(status === 'rascunho' ? 'Rascunho salvo.' : agendou ? 'Comunicado agendado.' : (f.id ? 'Comunicado atualizado.' : 'Comunicado publicado.'));
    aba.value = status === 'rascunho' ? 'rascunhos' : agendou ? 'agendados' : 'publicados';
    wizardAberto.value = false;
    await carregar();
  } catch (e) {
    erroWizard.value = e.message || 'Não foi possível salvar.';
  } finally {
    enviando.value = false;
  }
}

// ---------- Ações da lista ----------
async function acaoRapida(c, acao) {
  const res = await fetch('/api/rh/comunicados/acao', { method: 'POST', headers: { ...json, ...auth() }, body: JSON.stringify({ id: c.id, acao }) });
  const data = await res.json();
  if (!res.ok) return aviso(data.erro || 'Não foi possível concluir a ação.', true);
  aviso({ fixar: 'Comunicado fixado no topo.', desafixar: 'Comunicado desafixado.', arquivar: 'Comunicado arquivado.', reativar: 'Comunicado reativado.', duplicar: 'Cópia criada como rascunho.' }[acao]);
  if (acao === 'duplicar') aba.value = 'rascunhos';
  await carregar();
}

const paraRemover = ref(null);
const removendo = ref(false);
async function remover() {
  removendo.value = true;
  try {
    const res = await fetch('/api/rh/comunicados/remover', { method: 'POST', headers: { ...json, ...auth() }, body: JSON.stringify({ id: paraRemover.value.id }) });
    const data = await res.json();
    if (!res.ok) return aviso(data.erro || 'Não foi possível remover.', true);
    aviso('Comunicado removido.');
    paraRemover.value = null;
    await carregar();
  } finally {
    removendo.value = false;
  }
}

onMounted(async () => {
  await carregar();
  try {
    const res = await fetch('/api/rh/resumo', { headers: auth() });
    if (res.ok) {
      const d = await res.json();
      empresas.value = d.empresas || [];
      departamentos.value = d.departamentos || [];
      colaboradores.value = d.colaboradores || [];
    }
  } catch (e) { /* sem listas de público */ }
});
</script>

<style scoped>
.selo { font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 9999px; border: 1px solid; }
.acao { height: 2rem; padding: 0 0.75rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; color: #475569; cursor: pointer; }
.acao:hover { background: #f1f5f9; }
.campo { width: 100%; padding: 0 0.75rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 0.875rem; outline: none; }
.campo:focus { background: #fff; border-color: #94a3b8; }
</style>
