<template>
  <div class="space-y-3">
    <div class="flex flex-col sm:flex-row gap-2">
      <div class="flex-1 flex items-center h-10 rounded-lg border border-slate-200 bg-slate-50 focus-within:bg-white focus-within:border-slate-400">
        <AppIcon name="search" size="14" class="text-slate-400 ml-3 shrink-0" />
        <input v-model="termo" type="text" placeholder="Buscar por nome, chapa ou cargo" class="flex-1 min-w-0 h-full px-2.5 text-sm text-slate-800 bg-transparent outline-none" />
      </div>
      <button type="button" @click="colarAberto = !colarAberto" class="h-10 px-4 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-700 cursor-pointer whitespace-nowrap">
        {{ colarAberto ? 'Fechar' : 'Colar lista de chapas' }}
      </button>
    </div>

    <div v-if="colarAberto" class="space-y-2 p-3 rounded-xl bg-slate-50 border border-slate-200">
      <p class="text-xs text-slate-500">Cole as chapas do relatório de escala de folgas (uma por linha, ou separadas por vírgula). Marcamos quem for encontrado.</p>
      <textarea v-model="textoColado" rows="4" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm font-mono outline-none focus:border-slate-400"></textarea>
      <div class="flex flex-wrap items-center gap-3">
        <button type="button" @click="aplicarColagem" class="h-9 px-4 rounded-full bg-[#141414] text-white text-xs font-semibold cursor-pointer">Marcar chapas</button>
        <span v-if="resultadoColagem" class="text-xs" :class="resultadoColagem.faltando.length ? 'text-amber-700' : 'text-emerald-700'">
          {{ resultadoColagem.marcados }} marcados<template v-if="resultadoColagem.faltando.length"> · não encontrados: {{ resultadoColagem.faltando.join(', ') }}</template>
        </span>
      </div>
    </div>

    <div class="flex items-center justify-between text-xs">
      <label class="inline-flex items-center gap-2 text-slate-600 cursor-pointer select-none">
        <input type="checkbox" :checked="todosFiltradosMarcados" @change="alternarFiltrados" class="w-4 h-4 accent-[#9c2337]" />
        {{ termo ? 'Marcar os encontrados' : 'Marcar todos' }}
      </label>
      <span class="font-semibold text-slate-700">{{ modelValue.length }} selecionado{{ modelValue.length === 1 ? '' : 's' }}</span>
    </div>

    <ul class="max-h-64 overflow-y-auto rounded-xl border border-slate-200 divide-y divide-slate-100">
      <li v-for="c in filtrados" :key="c.matricula">
        <label class="flex items-center gap-3 px-3 py-2.5 cursor-pointer hover:bg-slate-50">
          <input type="checkbox" :checked="marcados.has(c.matricula)" @change="alternar(c.matricula)" class="w-4 h-4 accent-[#9c2337] shrink-0" />
          <span class="min-w-0">
            <span class="block text-sm text-slate-800 truncate">{{ c.nome }}</span>
            <span class="block text-xs text-slate-400 truncate">{{ c.matricula }} · {{ c.cargo }}</span>
          </span>
        </label>
      </li>
      <li v-if="!filtrados.length" class="px-3 py-3 text-sm text-slate-400">Nenhum colaborador encontrado.</li>
    </ul>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppIcon from './AppIcon.vue';

/** Seleção de vários colaboradores (v-model = lista de matrículas), com busca e colagem de chapas. */
const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  colaboradores: { type: Array, default: () => [] }
});
const emit = defineEmits(['update:modelValue']);

const termo = ref('');
const colarAberto = ref(false);
const textoColado = ref('');
const resultadoColagem = ref(null);

const marcados = computed(() => new Set(props.modelValue));
const filtrados = computed(() => {
  const t = termo.value.trim().toLowerCase();
  if (!t) return props.colaboradores;
  return props.colaboradores.filter((c) => [c.nome, c.matricula, c.cargo, c.departamento].some((v) => (v || '').toLowerCase().includes(t)));
});
const todosFiltradosMarcados = computed(() => filtrados.value.length > 0 && filtrados.value.every((c) => marcados.value.has(c.matricula)));

function atualizar(conjunto) {
  emit('update:modelValue', props.colaboradores.map((c) => c.matricula).filter((m) => conjunto.has(m)));
}

function alternar(matricula) {
  const s = new Set(marcados.value);
  s.has(matricula) ? s.delete(matricula) : s.add(matricula);
  atualizar(s);
}

function alternarFiltrados() {
  const s = new Set(marcados.value);
  const marcar = !todosFiltradosMarcados.value;
  filtrados.value.forEach((c) => (marcar ? s.add(c.matricula) : s.delete(c.matricula)));
  atualizar(s);
}

// Chapa no RM costuma vir só com números (ex.: 001040); a matrícula do portal pode ter prefixo (VB-1040)
const soDigitos = (v) => String(v || '').replace(/\D/g, '').replace(/^0+/, '');

function aplicarColagem() {
  const itens = textoColado.value.split(/[\s,;]+/).map((x) => x.trim()).filter(Boolean);
  const s = new Set(marcados.value);
  const faltando = [];
  let marcadosAgora = 0;
  itens.forEach((item) => {
    const achado = props.colaboradores.find((c) => c.matricula.toLowerCase() === item.toLowerCase())
      || (soDigitos(item) && props.colaboradores.find((c) => soDigitos(c.matricula) === soDigitos(item)));
    if (achado) {
      s.add(achado.matricula);
      marcadosAgora++;
    } else {
      faltando.push(item);
    }
  });
  atualizar(s);
  resultadoColagem.value = { marcados: marcadosAgora, faltando: faltando.slice(0, 15) };
}
</script>
