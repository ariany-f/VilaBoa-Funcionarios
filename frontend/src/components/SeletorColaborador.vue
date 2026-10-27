<template>
  <div class="relative" :class="larguraClasse">
    <div class="flex items-center h-10 rounded-lg border border-slate-200 bg-slate-50 focus-within:bg-white focus-within:border-slate-400">
      <AppIcon name="search" size="14" class="text-slate-400 ml-3 shrink-0" />
      <input
        ref="campo"
        v-model="termo"
        type="text"
        :placeholder="selecionado ? `${selecionado.nome} (${selecionado.matricula})` : 'Buscar colaborador...'"
        @focus="aberto = true"
        @blur="fechar"
        @keydown.down.prevent="mover(1)"
        @keydown.up.prevent="mover(-1)"
        @keydown.enter.prevent="filtrados[indice] && escolher(filtrados[indice])"
        @keydown.esc.stop="aberto = false"
        class="flex-1 min-w-0 h-full px-2.5 text-sm text-slate-800 bg-transparent outline-none"
        :class="selecionado && !termo ? 'placeholder-slate-800' : 'placeholder-slate-400'"
      />
      <AppIcon name="chevron-down" size="14" class="text-slate-400 mr-3 shrink-0" />
    </div>

    <ul v-if="aberto" class="absolute left-0 right-0 top-full mt-1.5 bg-white rounded-xl border border-slate-200 shadow-xl py-1.5 z-40 max-h-72 overflow-y-auto">
      <li v-for="(c, i) in filtrados" :key="c.matricula">
        <button
          type="button"
          @mousedown.prevent="escolher(c)"
          class="w-full px-3 py-2 text-left cursor-pointer"
          :class="i === indice ? 'bg-slate-100' : 'hover:bg-slate-50'"
        >
          <span class="block text-sm text-slate-800 truncate" :class="c.matricula === modelValue ? 'font-semibold' : ''">{{ c.nome }}</span>
          <span class="block text-xs text-slate-400 truncate">{{ c.matricula }} · {{ c.cargo }}</span>
        </button>
      </li>
      <li v-if="!filtrados.length" class="px-3 py-3 text-sm text-slate-400">Nenhum colaborador encontrado.</li>
    </ul>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import AppIcon from './AppIcon.vue';

/** Lista de colaboradores com busca por nome, matrícula ou cargo (v-model = matrícula). */
const props = defineProps({
  modelValue: { type: String, default: '' },
  colaboradores: { type: Array, default: () => [] },
  larguraClasse: { type: String, default: 'sm:w-80' }
});
const emit = defineEmits(['update:modelValue', 'change']);

const termo = ref('');
const aberto = ref(false);
const indice = ref(0);

const selecionado = computed(() => props.colaboradores.find((c) => c.matricula === props.modelValue) || null);

const filtrados = computed(() => {
  const t = termo.value.trim().toLowerCase();
  if (!t) return props.colaboradores;
  return props.colaboradores.filter((c) => [c.nome, c.matricula, c.cargo, c.departamento].some((v) => (v || '').toLowerCase().includes(t)));
});

watch(termo, () => { indice.value = 0; aberto.value = true; });

function mover(passo) {
  const total = filtrados.value.length;
  if (total) indice.value = (indice.value + passo + total) % total;
}

function escolher(c) {
  emit('update:modelValue', c.matricula);
  emit('change', c.matricula);
  termo.value = '';
  aberto.value = false;
}

function fechar() {
  setTimeout(() => { aberto.value = false; termo.value = ''; }, 120);
}
</script>
