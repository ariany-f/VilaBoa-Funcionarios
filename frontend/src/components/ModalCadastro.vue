<template>
  <Teleport to="body">
    <div v-if="aberto" class="fixed inset-0 z-[80] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/60 backdrop-blur-sm" role="dialog" aria-modal="true" :aria-labelledby="idTitulo" @mousedown.self="pedirFechar">
      <form
        class="bg-white w-full max-h-[94vh] sm:max-h-[90vh] rounded-t-3xl sm:rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-slate-200 animate-slide-up"
        :class="larguras[tamanho] || larguras.md"
        @submit.prevent="$emit('salvar')"
      >
        <header class="px-6 pt-5 pb-4 border-b border-slate-100 flex items-start justify-between gap-4 shrink-0">
          <div class="min-w-0">
            <h3 :id="idTitulo" class="text-lg font-semibold text-slate-900 tracking-tight">{{ titulo }}</h3>
            <p v-if="descricao" class="text-xs text-slate-500 mt-0.5">{{ descricao }}</p>
          </div>
          <button type="button" @click="pedirFechar" class="w-9 h-9 -mr-2 rounded-full hover:bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer shrink-0" aria-label="Fechar">
            <AppIcon name="x" size="16" />
          </button>
        </header>

        <div class="flex-1 min-h-0 overflow-y-auto px-6 py-5 space-y-5">
          <p v-if="erro" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">{{ erro }}</p>
          <slot />
        </div>

        <footer class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between gap-3 shrink-0">
          <div class="min-w-0 text-xs text-slate-500"><slot name="rodape-esquerda" /></div>
          <div class="flex items-center gap-2 shrink-0">
            <button type="button" @click="pedirFechar" class="h-10 px-5 rounded-full text-sm font-semibold text-slate-600 hover:bg-slate-200 cursor-pointer">Cancelar</button>
            <button type="submit" :disabled="enviando || !podeSalvar" class="h-10 px-6 rounded-full bg-[#9c2337] hover:bg-[#831b2d] disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold cursor-pointer">
              {{ enviando ? 'Salvando...' : rotuloSalvar }}
            </button>
          </div>
        </footer>
      </form>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, toRef } from 'vue';
import AppIcon from './AppIcon.vue';
import { usarFecharComEsc } from '../utils/fecharComEsc.js';

/**
 * Modal padrão dos cadastros curtos do painel do RH (poucos campos).
 * Uso: <ModalCadastro :aberto="x" titulo="Nova empresa" @fechar="x = false" @salvar="salvar"> ...campos... </ModalCadastro>
 * `sujo` = há alterações não salvas: ao fechar, pede confirmação.
 */
const props = defineProps({
  aberto: { type: Boolean, default: false },
  titulo: { type: String, required: true },
  descricao: { type: String, default: '' },
  tamanho: { type: String, default: 'md' }, // sm | md | lg | xl
  rotuloSalvar: { type: String, default: 'Salvar' },
  enviando: { type: Boolean, default: false },
  podeSalvar: { type: Boolean, default: true },
  erro: { type: String, default: '' },
  sujo: { type: Boolean, default: false }
});
const emit = defineEmits(['fechar', 'salvar']);

const larguras = { sm: 'sm:max-w-md', md: 'sm:max-w-xl', lg: 'sm:max-w-3xl', xl: 'sm:max-w-5xl' };
const idTitulo = computed(() => 'modal-' + props.titulo.toLowerCase().replace(/[^a-z0-9]+/g, '-'));

function pedirFechar() {
  if (props.sujo && !window.confirm('Há alterações não salvas. Sair mesmo assim?')) return;
  emit('fechar');
}
usarFecharComEsc(pedirFechar, toRef(props, 'aberto'));
</script>
