<template>
  <div class="relative">
    <button
      type="button"
      @click="alternar"
      class="relative w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 active:bg-white/20 text-white flex items-center justify-center transition border border-white/20 cursor-pointer active:scale-95"
      :title="itens.length ? itens.length + ' pendência(s)' : 'Sem pendências'"
      aria-label="Notificações"
      :aria-expanded="aberto"
    >
      <AppIcon name="bell" size="16" />
      <span
        v-if="itens.length"
        class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-[#9c2337] text-white text-[10px] font-semibold flex items-center justify-center ring-2 ring-[#141414]"
      >
        {{ itens.length > 99 ? '99+' : itens.length }}
      </span>
    </button>

    <Teleport to="body">
      <div v-if="aberto" class="fixed inset-0 z-[60]" @click="aberto = false">
        <!-- Celular: painel que sobe de baixo; computador: caixa abaixo do sino -->
        <div class="absolute inset-0 bg-black/30 sm:bg-transparent"></div>
        <div
          class="absolute left-0 right-0 bottom-0 sm:left-auto sm:bottom-auto sm:right-6 sm:top-16 sm:w-96 bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl border border-slate-200 max-h-[80vh] flex flex-col safe-bottom"
          @click.stop
        >
          <div class="px-5 pt-4 pb-3 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-semibold text-slate-900">Notificações</h3>
            <button type="button" @click="aberto = false" class="w-9 h-9 rounded-full hover:bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer" aria-label="Fechar">
              <AppIcon name="x" size="16" />
            </button>
          </div>

          <div v-if="!itens.length" class="px-5 py-10 text-center space-y-1">
            <AppIcon name="check" size="28" class="mx-auto text-slate-400" />
            <p class="text-sm font-medium text-slate-800">Tudo em dia!</p>
            <p class="text-xs text-slate-500">Você não tem nada pendente agora.</p>
          </div>

          <ul v-else class="overflow-y-auto divide-y divide-slate-100">
            <li v-for="(n, i) in itens" :key="i">
              <button type="button" @click="selecionar(n)" class="w-full px-5 py-3.5 flex items-center gap-3 text-left hover:bg-slate-50 active:bg-slate-100 cursor-pointer">
                <span class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                  <AppIcon :name="n.icone" size="18" />
                </span>
                <span class="min-w-0 flex-1">
                  <span class="block text-sm font-medium text-slate-900">{{ n.titulo }}</span>
                  <span class="block text-xs text-slate-500 mt-0.5">{{ n.descricao }}</span>
                </span>
                <AppIcon name="chevron-right" size="16" class="text-slate-300 shrink-0" />
              </button>
            </li>
          </ul>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import AppIcon from './AppIcon.vue';
import { usarFecharComEsc } from '../utils/fecharComEsc.js';

defineProps({
  // [{ titulo, descricao, icone, cor, acao }]
  itens: { type: Array, default: () => [] }
});
const emit = defineEmits(['abrir']);

const aberto = ref(false);
usarFecharComEsc(() => { aberto.value = false; }, aberto);

function alternar() {
  aberto.value = !aberto.value;
  if (aberto.value) emit('abrir');
}

function selecionar(n) {
  aberto.value = false;
  n.acao();
}
</script>
