<template>
  <Teleport to="body">
    <div v-if="aberto" class="fixed inset-0 z-[80] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/60 backdrop-blur-sm" role="dialog" aria-modal="true" :aria-labelledby="idTitulo" @mousedown.self="pedirFechar">
      <div class="bg-white w-full sm:max-w-5xl h-[96vh] sm:h-[90vh] rounded-t-3xl sm:rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-slate-200 animate-slide-up">
        <!-- Cabeçalho + progresso -->
        <header class="px-6 pt-5 pb-4 border-b border-slate-100 shrink-0 space-y-4">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <h3 :id="idTitulo" class="text-lg font-semibold text-slate-900 tracking-tight">{{ titulo }}</h3>
              <p v-if="descricao" class="text-xs text-slate-500 mt-0.5">{{ descricao }}</p>
            </div>
            <button type="button" @click="pedirFechar" class="w-9 h-9 -mr-2 rounded-full hover:bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer shrink-0" aria-label="Fechar">
              <AppIcon name="x" size="16" />
            </button>
          </div>

          <!-- Passos: clicáveis para voltar; os da frente só abrem depois de validar o atual -->
          <ol class="flex items-center gap-1.5 sm:gap-2">
            <li v-for="(p, i) in passos" :key="p.id" class="flex items-center gap-1.5 sm:gap-2 min-w-0" :class="i < passos.length - 1 ? 'flex-1' : ''">
              <button
                type="button"
                @click="irPara(i)"
                :disabled="i > atual && !passoLiberado(i)"
                class="flex items-center gap-2 min-w-0 cursor-pointer disabled:cursor-not-allowed"
                :aria-current="i === atual ? 'step' : undefined"
              >
                <span
                  class="w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center shrink-0 transition"
                  :class="i < atual ? 'bg-emerald-500 text-white' : (i === atual ? 'bg-[#9c2337] text-white' : 'bg-slate-100 text-slate-400')"
                >{{ i < atual ? '✓' : i + 1 }}</span>
                <span class="hidden sm:block text-xs font-semibold truncate" :class="i === atual ? 'text-slate-900' : 'text-slate-400'">{{ p.titulo }}</span>
              </button>
              <span v-if="i < passos.length - 1" class="flex-1 h-px" :class="i < atual ? 'bg-emerald-300' : 'bg-slate-200'"></span>
            </li>
          </ol>
          <p class="sm:hidden text-xs font-semibold text-slate-700">Passo {{ atual + 1 }} de {{ passos.length }} · {{ passoAtual.titulo }}</p>
        </header>

        <!-- Conteúdo do passo (e prévia opcional ao lado) -->
        <div class="flex-1 min-h-0 flex">
          <div class="flex-1 min-w-0 overflow-y-auto px-6 py-6 space-y-5">
            <div>
              <h4 class="text-base font-semibold text-slate-900">{{ passoAtual.titulo }}</h4>
              <p v-if="passoAtual.descricao" class="text-xs text-slate-500 mt-0.5">{{ passoAtual.descricao }}</p>
            </div>
            <p v-if="erro" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">{{ erro }}</p>
            <p v-else-if="tentouAvancar && motivo" class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-medium">{{ motivo }}</p>
            <slot :name="'passo-' + passoAtual.id" :passo="atual" />
          </div>
          <aside v-if="$slots.previa" class="hidden lg:block w-80 shrink-0 border-l border-slate-100 bg-slate-50 overflow-y-auto p-5 space-y-3">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Prévia</p>
            <slot name="previa" />
          </aside>
        </div>

        <!-- Rodapé -->
        <footer class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between gap-3 shrink-0">
          <button type="button" @click="voltar" :disabled="atual === 0" class="h-10 px-5 rounded-full text-sm font-semibold text-slate-600 hover:bg-slate-200 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer">← Voltar</button>
          <div class="flex items-center gap-2">
            <button v-if="atual === passos.length - 1 && podeRascunho" type="button" @click="$emit('rascunho')" :disabled="enviando" class="h-10 px-5 rounded-full border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-white disabled:opacity-50 cursor-pointer">Salvar rascunho</button>
            <button v-if="atual < passos.length - 1" type="button" @click="avancar" class="h-10 px-6 rounded-full bg-[#141414] hover:bg-black text-white text-sm font-semibold cursor-pointer">Continuar →</button>
            <button v-else type="button" @click="concluir" :disabled="enviando" class="h-10 px-6 rounded-full bg-[#9c2337] hover:bg-[#831b2d] disabled:opacity-50 text-white text-sm font-semibold cursor-pointer">
              {{ enviando ? 'Salvando...' : rotuloConcluir }}
            </button>
          </div>
        </footer>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, toRef } from 'vue';
import AppIcon from './AppIcon.vue';
import { usarFecharComEsc } from '../utils/fecharComEsc.js';

/**
 * Wizard padrão dos cadastros longos do painel do RH (vários grupos de dados).
 *
 *   <WizardCadastro
 *     :aberto="aberto" titulo="Nova trilha" v-model:passo="passo"
 *     :passos="[{ id: 'basico', titulo: 'Básico', descricao: '...' }, ...]"
 *     :validar="(id) => mensagem de erro do passo ou '' se estiver ok"
 *     :sujo="alterou" :enviando="salvando" rotulo-concluir="Publicar trilha"
 *     @fechar="..." @concluir="..." @rascunho="...">
 *     <template #passo-basico> ...campos... </template>
 *     <template #previa> ...como vai aparecer (opcional, só em telas largas)... </template>
 *   </WizardCadastro>
 *
 * `validar(idDoPasso)` devolve o texto do que falta; vazio libera o passo. Voltar sempre é livre.
 */
const props = defineProps({
  aberto: { type: Boolean, default: false },
  titulo: { type: String, required: true },
  descricao: { type: String, default: '' },
  passos: { type: Array, required: true },
  passo: { type: Number, default: 0 },
  validar: { type: Function, default: () => '' },
  rotuloConcluir: { type: String, default: 'Concluir' },
  podeRascunho: { type: Boolean, default: false },
  enviando: { type: Boolean, default: false },
  erro: { type: String, default: '' },
  sujo: { type: Boolean, default: false }
});
const emit = defineEmits(['fechar', 'concluir', 'rascunho', 'update:passo']);

const atual = ref(props.passo);
const tentouAvancar = ref(false);
const passoAtual = computed(() => props.passos[atual.value]);
const motivo = computed(() => props.validar(passoAtual.value.id));
const idTitulo = computed(() => 'wizard-' + props.titulo.toLowerCase().replace(/[^a-z0-9]+/g, '-'));

watch(() => props.passo, (p) => { atual.value = p; });
watch(() => props.aberto, (a) => { if (a) { atual.value = 0; tentouAvancar.value = false; emit('update:passo', 0); } });
watch(atual, (p) => { tentouAvancar.value = false; emit('update:passo', p); });

// Um passo da frente só abre se todos os anteriores estiverem válidos
const passoLiberado = (i) => props.passos.slice(0, i).every((p) => !props.validar(p.id));

function irPara(i) {
  if (i <= atual.value || passoLiberado(i)) atual.value = i;
}
function voltar() {
  if (atual.value > 0) atual.value -= 1;
}
function avancar() {
  if (motivo.value) { tentouAvancar.value = true; return; }
  atual.value += 1;
}
function concluir() {
  const pendente = props.passos.findIndex((p) => props.validar(p.id));
  if (pendente !== -1) { atual.value = pendente; tentouAvancar.value = true; return; }
  emit('concluir');
}
function pedirFechar() {
  if (props.sujo && !window.confirm('Há alterações não salvas. Sair mesmo assim?')) return;
  emit('fechar');
}
usarFecharComEsc(pedirFechar, toRef(props, 'aberto'));
</script>
