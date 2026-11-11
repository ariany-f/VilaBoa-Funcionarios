<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center bg-black/60 p-0 sm:p-6" @click.self="$emit('fechar')">
      <div class="bg-white w-full sm:max-w-4xl max-h-[95vh] rounded-t-3xl sm:rounded-3xl flex flex-col overflow-hidden" role="dialog" aria-modal="true" :aria-label="`Certificado: ${nome}`">
        <div class="px-5 py-3.5 flex items-center justify-between gap-3 border-b border-slate-100">
          <div class="min-w-0">
            <h3 class="text-sm font-semibold text-slate-900 truncate">Certificado</h3>
            <p class="text-xs text-slate-500 truncate">{{ nome }}</p>
          </div>
          <button ref="fechar" type="button" @click="$emit('fechar')" class="w-9 h-9 shrink-0 rounded-full hover:bg-slate-100 flex items-center justify-center cursor-pointer" aria-label="Fechar certificado"><AppIcon name="x" size="18" /></button>
        </div>

        <div ref="area" class="flex-1 overflow-auto bg-slate-100 p-3 sm:p-5">
          <div class="mx-auto shadow-md bg-white" :style="{ width: LARGURA * escala + 'px', height: ALTURA * escala + 'px' }">
            <iframe
              ref="quadro"
              :srcdoc="html"
              title="Pré-visualização do certificado"
              class="border-0 origin-top-left bg-white block"
              :style="{ width: LARGURA + 'px', height: ALTURA + 'px', transform: `scale(${escala})` }"
              sandbox="allow-modals allow-same-origin allow-popups"
            ></iframe>
          </div>
        </div>

        <div class="px-4 sm:px-5 py-3 border-t border-slate-100 flex flex-wrap items-center gap-2">
          <button type="button" @click="imprimir" class="inline-flex items-center gap-2 h-10 px-5 rounded-full bg-[#9c2337] hover:bg-[#831b2d] text-white text-sm font-semibold cursor-pointer">
            <AppIcon name="download" size="15" /> Baixar PDF
          </button>
          <button type="button" @click="imprimir" class="inline-flex items-center gap-2 h-10 px-4 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold cursor-pointer">
            <AppIcon name="printer" size="15" /> Imprimir
          </button>
          <button type="button" @click="baixarArquivo" class="h-10 px-4 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold cursor-pointer">Baixar arquivo</button>
          <button v-if="validacao" type="button" @click="copiarLink" class="inline-flex items-center gap-2 h-10 px-4 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold cursor-pointer">
            <AppIcon name="copy" size="15" /> {{ copiado ? 'Link copiado!' : 'Copiar link de validação' }}
          </button>
          <p class="basis-full sm:basis-auto sm:ml-auto text-[11px] text-slate-400">Em “Baixar PDF”, escolha “Salvar como PDF” na janela de impressão.</p>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
import AppIcon from './AppIcon.vue';
import { usarFecharComEsc } from '../utils/fecharComEsc.js';

// A folha do certificado é A4 paisagem (297 x 210 mm ≈ 1123 x 794 px); a prévia é reduzida para caber na tela
const LARGURA = 1123;
const ALTURA = 794;

const props = defineProps({
  html: { type: String, required: true },
  nome: { type: String, default: '' },
  validacao: { type: String, default: '' }
});
const emit = defineEmits(['fechar']);
usarFecharComEsc(() => emit('fechar'));

const area = ref(null);
const quadro = ref(null);
const fechar = ref(null);
const escala = ref(0.6);
const copiado = ref(false);
let observador = null;

function ajustar() {
  const largura = (area.value?.clientWidth || LARGURA) - (window.innerWidth < 640 ? 24 : 40);
  escala.value = Math.min(1, Math.max(0.25, largura / LARGURA));
}

onMounted(() => {
  ajustar();
  if (window.ResizeObserver && area.value) {
    observador = new ResizeObserver(ajustar);
    observador.observe(area.value);
  }
  nextTick(() => fechar.value?.focus());
});
onBeforeUnmount(() => observador?.disconnect());

function imprimir() {
  const janela = quadro.value?.contentWindow;
  if (janela) { janela.focus(); janela.print(); }
}

function baixarArquivo() {
  const url = URL.createObjectURL(new Blob([props.html], { type: 'text/html;charset=utf-8' }));
  const a = document.createElement('a');
  a.href = url;
  a.download = `certificado-${props.nome.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^A-Za-z0-9]+/g, '-').replace(/^-|-$/g, '').toLowerCase() || 'vila-boa'}.html`;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

async function copiarLink() {
  try {
    await navigator.clipboard.writeText(props.validacao);
    copiado.value = true;
    setTimeout(() => { copiado.value = false; }, 2000);
  } catch (e) {
    window.prompt('Copie o link de validação:', props.validacao);
  }
}
</script>
