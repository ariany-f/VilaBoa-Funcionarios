<template>
  <Teleport to="body">
    <div v-if="aberto && itens.length" class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/60 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="pendencias-titulo">
      <div class="bg-white w-full sm:max-w-lg max-h-[92vh] rounded-t-3xl sm:rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-slate-200 animate-slide-up" @click.stop>
        <div class="px-6 pt-6 pb-4 space-y-1.5">
          <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-amber-800 bg-amber-100 border border-amber-200 px-2.5 py-1 rounded-full">
            <AppIcon name="alert-triangle" size="12" />
            {{ itens.length === 1 ? '1 pendência' : itens.length + ' pendências' }}
          </span>
          <h3 id="pendencias-titulo" class="text-xl font-semibold text-slate-900 tracking-tight">Você tem pendências</h3>
          <p class="text-xs text-slate-500">Há documentos esperando a sua leitura ou assinatura. Resolva agora ou volte pelo sino de notificações.</p>
        </div>

        <div class="overflow-y-auto px-6 pb-2 space-y-4">
          <!-- Trilhas obrigatórias: aviso diferenciado -->
          <section v-if="obrigatorios.length" class="rounded-2xl border-2 border-[#9c2337] bg-[#fdf2f4] p-4 space-y-3">
            <div class="flex items-start gap-2.5">
              <AppIcon name="shield-check" size="18" class="text-[#9c2337] shrink-0 mt-0.5" />
              <div>
                <h4 class="text-sm font-bold text-[#9c2337]">Conclusão obrigatória</h4>
                <p class="text-xs text-slate-700 leading-relaxed">
                  {{ obrigatorios.length === 1 ? 'Este treinamento' : 'Estes treinamentos' }} fazem parte da trilha obrigatória e precisam ser concluídos.
                </p>
              </div>
            </div>
            <ul class="space-y-2">
              <li v-for="(n, i) in obrigatorios" :key="'o' + i">
                <button type="button" @click="$emit('abrir', n)" class="w-full flex items-center gap-3 text-left bg-white rounded-xl border border-rose-200 px-3.5 py-3 hover:border-[#9c2337] cursor-pointer">
                  <AppIcon :name="n.icone" size="18" class="text-[#9c2337] shrink-0" />
                  <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-slate-900">{{ n.titulo }}</span>
                    <span class="block text-xs text-slate-500 truncate">{{ n.descricao }}</span>
                  </span>
                  <span class="text-[11px] font-bold text-[#9c2337] shrink-0">Concluir →</span>
                </button>
              </li>
            </ul>
          </section>

          <ul v-if="demais.length" class="space-y-2">
            <li v-for="(n, i) in demais" :key="'d' + i">
              <button type="button" @click="$emit('abrir', n)" class="w-full flex items-center gap-3 text-left rounded-xl border border-slate-200 px-3.5 py-3 hover:bg-slate-50 cursor-pointer">
                <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" :class="n.cor || 'bg-slate-100 text-slate-700'">
                  <AppIcon :name="n.icone" size="16" />
                </span>
                <span class="min-w-0 flex-1">
                  <span class="block text-sm font-medium text-slate-900">{{ n.titulo }}</span>
                  <span class="block text-xs text-slate-500 truncate">{{ n.descricao }}</span>
                </span>
                <AppIcon name="chevron-right" size="16" class="text-slate-300 shrink-0" />
              </button>
            </li>
          </ul>
        </div>

        <div class="px-6 py-4 border-t border-slate-100">
          <button type="button" @click="$emit('fechar')" class="w-full h-11 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold cursor-pointer">Ver depois</button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';
import AppIcon from './AppIcon.vue';

// itens: a mesma lista das notificações; os marcados com `obrigatorio` (trilha obrigatória) ganham destaque
const props = defineProps({
  aberto: { type: Boolean, default: false },
  itens: { type: Array, default: () => [] }
});
defineEmits(['abrir', 'fechar']);

const obrigatorios = computed(() => props.itens.filter((n) => n.obrigatorio));
const demais = computed(() => props.itens.filter((n) => !n.obrigatorio));
</script>
