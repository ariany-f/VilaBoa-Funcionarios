<template>
  <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800">Escala de folgas</h3>
        <p class="text-xs text-slate-500 mt-0.5 max-w-2xl">
          Envie a planilha de cada letra exportada do RM (período de 21 a 20 do mês seguinte) e marque os colaboradores daquela letra.
          Quem está em outro horário 5x1 segue a letra do horário 052; o horário 073 tem escala própria.
          O colaborador vê a escala no portal e dá ciência digital, no lugar da assinatura na folha impressa.
        </p>
      </div>
      <button v-if="podePublicar" type="button" @click="abrirNova" class="h-11 px-6 rounded-full border border-slate-300 hover:bg-slate-50 text-slate-800 text-sm font-semibold cursor-pointer">+ Publicar escala</button>
    </div>
    <p v-if="mensagem" class="text-xs font-medium" :class="mensagemErro ? 'text-rose-700' : 'text-emerald-700'">{{ mensagem }}</p>

    <div v-if="carregando" class="py-6 text-center text-sm text-slate-400">Carregando...</div>
    <p v-else-if="!escalasCtx.length" class="text-sm text-slate-500 py-2">Nenhuma escala publicada.</p>
    <div v-else class="divide-y divide-slate-100 border-t border-slate-100 -mx-5 sm:-mx-6">
      <div v-for="e in escalasCtx" :key="e.id" class="px-5 sm:px-6 py-3">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
          <button type="button" @click="alternarAberta(e.id)" class="flex-1 min-w-[220px] flex items-center gap-3 text-left cursor-pointer group">
            <AppIcon :name="aberta === e.id ? 'chevron-down' : 'chevron-right'" size="14" class="text-slate-400 shrink-0" />
            <span class="min-w-0">
              <span class="block font-semibold text-slate-900 group-hover:text-[#9c2337] truncate">{{ e.titulo }}</span>
              <span class="block text-xs text-slate-500 truncate">{{ dataBr(e.periodo_inicio) }} a {{ dataBr(e.periodo_fim) }} · {{ e.dias.filter((d) => d.folga).length }} folgas<template v-if="e.horario"> · Horário {{ e.horario }}</template></span>
            </span>
          </button>
          <div class="w-40 shrink-0">
            <div class="flex items-center justify-between text-[11px] mb-1">
              <span class="text-slate-500">Ciência</span>
              <span class="font-semibold tabular-nums" :class="e.cientes === e.total ? 'text-emerald-700' : 'text-slate-700'">{{ e.cientes }}/{{ e.total }}</span>
            </div>
            <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
              <div class="h-full rounded-full" :class="e.cientes === e.total ? 'bg-emerald-500' : 'bg-[#9c2337]'" :style="{ width: (e.total ? Math.round(e.cientes / e.total * 100) : 0) + '%' }"></div>
            </div>
          </div>
          <div v-if="podePublicar" class="flex items-center gap-1 shrink-0">
            <button type="button" @click="abrirColaboradores(e)" class="acao">Colaboradores</button>
            <button type="button" @click="remover(e)" class="acao hover:!text-rose-700">Remover</button>
          </div>
        </div>

        <div v-if="aberta === e.id" class="mt-3 ml-7 space-y-3">
          <div class="flex flex-wrap gap-1.5">
            <span v-for="d in e.dias.filter((x) => x.folga)" :key="d.data" class="h-7 px-2.5 rounded-full text-[11px] font-semibold inline-flex items-center bg-rose-50 text-[#9c2337] border border-rose-100">{{ d.dia.toLowerCase() }}, {{ dataBr(d.data).slice(0, 5) }}</span>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[520px]">
              <thead>
                <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-100">
                  <th class="py-2 pr-3 font-semibold">Colaborador</th>
                  <th class="py-2 px-3 font-semibold">Ciência</th>
                  <th class="py-2 pl-3"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="c in e.colaboradores.filter((x) => doContexto(x.empresa))" :key="c.matricula" class="border-b border-slate-50 last:border-0">
                  <td class="py-2 pr-3">
                    <p class="text-slate-900">{{ c.nome }}</p>
                    <p class="text-xs text-slate-500">{{ c.matricula }}<template v-if="c.cargo"> · {{ c.cargo }}</template></p>
                  </td>
                  <td class="py-2 px-3"><span class="selo" :class="c.ciente_em ? 'ok' : 'pendente'">{{ c.ciente_em ? 'Ciente em ' + c.ciente_em.slice(0, 16) : 'Pendente' }}</span></td>
                  <td class="py-2 pl-3 text-right">
                    <a v-if="!c.ciente_em && podeCobrar" :href="avisoEscala(c, e)" target="_blank" rel="noopener" class="acao inline-flex items-center gap-1.5"><AppIcon name="whatsapp" size="13" />Avisar</a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p class="text-[11px] text-slate-400">Publicada em {{ dataHoraBr(e.publicado_em) }} por {{ e.publicado_por }}<template v-if="e.arquivo"> · {{ e.arquivo }}</template></p>
        </div>
      </div>
    </div>

    <!-- Publicar escala -->
    <ModalCadastro
      :aberto="novaAberta"
      titulo="Publicar escala de folgas"
      descricao="Planilha .xlsx exportada do RM (ou .csv), uma por letra."
      tamanho="lg"
      rotulo-salvar="Publicar"
      :enviando="salvando"
      :erro="erroModal"
      :pode-salvar="!!lida && !!form.titulo.trim() && form.matriculas.length > 0"
      :sujo="!!lida"
      @fechar="novaAberta = false"
      @salvar="publicar"
    >
      <div class="space-y-5">
        <label class="block">
          <span class="block text-xs font-medium text-slate-600 mb-1">Planilha da escala</span>
          <input type="file" accept=".xlsx,.csv" @change="lerArquivo" class="block w-full text-sm text-slate-600 file:mr-3 file:h-10 file:px-4 file:rounded-full file:border-0 file:bg-[#141414] file:text-white file:text-xs file:font-semibold file:cursor-pointer" />
        </label>

        <template v-if="lida">
          <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
            <p class="text-sm text-slate-800"><strong>{{ dataBr(lida.dias[0].data) }} a {{ dataBr(lida.dias[lida.dias.length - 1].data) }}</strong> · {{ lida.dias.length }} dias · {{ folgasLidas.length }} folgas</p>
            <div class="flex flex-wrap gap-1.5">
              <span v-for="d in folgasLidas" :key="d.data" class="h-7 px-2.5 rounded-full text-[11px] font-semibold inline-flex items-center bg-rose-50 text-[#9c2337] border border-rose-100">{{ diaSemana(d.data) }}, {{ dataBr(d.data).slice(0, 5) }}</span>
            </div>
          </div>

          <div class="grid sm:grid-cols-3 gap-3">
            <label class="block sm:col-span-2">
              <span class="block text-xs font-medium text-slate-600 mb-1">Nome da escala</span>
              <input v-model="form.titulo" type="text" class="campo" placeholder="Escala 5x1 - Letra A" />
            </label>
            <label class="block">
              <span class="block text-xs font-medium text-slate-600 mb-1">Letra</span>
              <input v-model="form.letra" type="text" maxlength="3" class="campo uppercase" placeholder="A" @input="sugerirTitulo" />
            </label>
            <label class="block sm:col-span-3">
              <span class="block text-xs font-medium text-slate-600 mb-1">Horário (opcional)</span>
              <input v-model="form.horario" type="text" class="campo" placeholder="052 08:00 - 11:20 12:40 - 16:40 (ESCALA 5 X 1)" />
            </label>
          </div>

          <div>
            <span class="block text-xs font-medium text-slate-600 mb-2">Colaboradores desta escala</span>
            <SeletorVariosColaboradores v-model="form.matriculas" :colaboradores="colabsContexto" />
          </div>
        </template>
      </div>
    </ModalCadastro>

    <!-- Alterar colaboradores -->
    <ModalCadastro
      :aberto="!!editando"
      :titulo="editando ? 'Colaboradores · ' + editando.titulo : 'Colaboradores'"
      descricao="Cada colaborador fica em uma escala por período: quem você marcar aqui sai das outras do mesmo período."
      tamanho="lg"
      :enviando="salvando"
      :erro="erroModal"
      :pode-salvar="matriculasEdicao.length > 0"
      @fechar="editando = null"
      @salvar="salvarColaboradores"
    >
      <SeletorVariosColaboradores v-model="matriculasEdicao" :colaboradores="colabsContexto" />
    </ModalCadastro>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppIcon from './AppIcon.vue';
import ModalCadastro from './ModalCadastro.vue';
import SeletorVariosColaboradores from './SeletorVariosColaboradores.vue';
import { pode } from '../utils/permissoes.js';
import { tokenAdmin } from '../utils/sessaoAdmin.js';
import { doContexto } from '../utils/empresaContexto.js';
import { avisoEscala } from '../utils/avisosWhatsapp.js';
import { lerPlanilhaEscala } from '../utils/planilhaEscala.js';

const props = defineProps({ colaborador: { type: Object, required: true } });

const escalas = ref([]);
const colaboradores = ref([]);
const carregando = ref(true);
const mensagem = ref('');
const mensagemErro = ref(false);
const aberta = ref('');

const novaAberta = ref(false);
const salvando = ref(false);
const erroModal = ref('');
const lida = ref(null);
const nomeArquivo = ref('');
const form = ref({ titulo: '', letra: '', horario: '', matriculas: [] });

const editando = ref(null);
const matriculasEdicao = ref([]);

const podePublicar = computed(() => pode(props.colaborador, 'rh_ponto', 'importar'));
const podeCobrar = computed(() => pode(props.colaborador, 'rh_acompanhamento', 'cobrar_pendencias'));
const colabsContexto = computed(() => colaboradores.value.filter((c) => doContexto(c.empresa)));
// Na empresa escolhida, mostra as escalas que têm alguém dela
const escalasCtx = computed(() => escalas.value.filter((e) => !e.colaboradores.length || e.colaboradores.some((c) => doContexto(c.empresa))));
const folgasLidas = computed(() => (lida.value?.dias || []).filter((d) => d.folga));

const auth = () => ({ Authorization: `Bearer ${tokenAdmin()}` });
const dataBr = (iso) => (iso ? iso.slice(0, 10).split('-').reverse().join('/') : '');
const dataHoraBr = (s) => (s ? `${dataBr(s)} ${s.slice(11, 16)}` : '');
const diaSemana = (iso) => ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'][new Date(`${iso}T12:00:00`).getDay()];
const textoMovidos = (n) => (n ? ` ${n} ${n === 1 ? 'saiu' : 'saíram'} de outra escala do mesmo período.` : '');
function aviso(texto, erro = false) { mensagem.value = texto; mensagemErro.value = erro; }

async function carregar() {
  try {
    const res = await fetch('/api/rh/escalas-folga', { headers: auth() });
    const data = await res.json();
    if (!res.ok) return aviso(data.erro || 'Não foi possível carregar as escalas.', true);
    escalas.value = data.escalas;
    colaboradores.value = data.colaboradores;
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

function alternarAberta(id) {
  aberta.value = aberta.value === id ? '' : id;
}

function abrirNova() {
  lida.value = null;
  nomeArquivo.value = '';
  form.value = { titulo: '', letra: '', horario: '', matriculas: [] };
  erroModal.value = '';
  novaAberta.value = true;
}

function sugerirTitulo() {
  form.value.letra = form.value.letra.toUpperCase();
  if (!form.value.titulo || /^Escala 5x1( - Letra \w*)?$/.test(form.value.titulo)) {
    form.value.titulo = form.value.letra ? `Escala 5x1 - Letra ${form.value.letra}` : 'Escala 5x1';
  }
}

async function lerArquivo(ev) {
  const arquivo = ev.target.files?.[0];
  erroModal.value = '';
  lida.value = null;
  if (!arquivo) return;
  try {
    const r = await lerPlanilhaEscala(arquivo);
    lida.value = r;
    nomeArquivo.value = arquivo.name;
    form.value.letra = r.letra;
    form.value.horario = r.horario === '073' ? '073 08:00 - 12:00 13:00 - 16:20 (TER a DOM) - Desc. SEG' : (r.horario || '');
    form.value.titulo = '';
    sugerirTitulo();
  } catch (e) {
    erroModal.value = e.message || 'Não foi possível ler a planilha.';
  }
}

async function publicar() {
  salvando.value = true;
  erroModal.value = '';
  try {
    const data = await chamar('/api/rh/escalas-folga', { ...form.value, dias: lida.value.dias, arquivo: nomeArquivo.value });
    novaAberta.value = false;
    aviso(`Escala publicada para ${form.value.matriculas.length} colaborador${form.value.matriculas.length === 1 ? '' : 'es'}.${textoMovidos(data.movidos)}`);
    await carregar();
    aberta.value = data.escala.id;
  } catch (e) {
    erroModal.value = e.message;
  } finally {
    salvando.value = false;
  }
}

function abrirColaboradores(e) {
  // Mantém quem está fora da empresa escolhida: o seletor só mostra os da empresa atual
  matriculasEdicao.value = e.colaboradores.map((c) => c.matricula);
  erroModal.value = '';
  editando.value = e;
}

async function salvarColaboradores() {
  salvando.value = true;
  erroModal.value = '';
  try {
    const foraDoContexto = editando.value.colaboradores.filter((c) => !doContexto(c.empresa)).map((c) => c.matricula);
    const data = await chamar('/api/rh/escalas-folga/colaboradores', { id: editando.value.id, matriculas: [...new Set([...foraDoContexto, ...matriculasEdicao.value])] });
    editando.value = null;
    aviso(`Colaboradores da escala atualizados.${textoMovidos(data.movidos)}`);
    await carregar();
  } catch (e) {
    erroModal.value = e.message;
  } finally {
    salvando.value = false;
  }
}

async function remover(e) {
  if (!window.confirm(`Remover a escala "${e.titulo}" (${dataBr(e.periodo_inicio)} a ${dataBr(e.periodo_fim)})? Ela some do portal dos colaboradores.`)) return;
  try {
    await chamar('/api/rh/escalas-folga/remover', { id: e.id });
    aviso('Escala removida.');
    await carregar();
  } catch (err) {
    aviso(err.message, true);
  }
}

onMounted(carregar);
</script>

<style scoped>
.selo { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; border: 1px solid; white-space: nowrap; }
.selo.ok { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
.selo.pendente { background: #fffbeb; color: #b45309; border-color: #fde68a; }
.acao { height: 32px; padding: 0 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; color: #475569; cursor: pointer; display: inline-flex; align-items: center; }
.acao:hover { background: #f1f5f9; }
.campo { width: 100%; height: 44px; padding: 0 12px; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 14px; outline: none; }
.campo:focus { background: #fff; border-color: #94a3b8; }
</style>
