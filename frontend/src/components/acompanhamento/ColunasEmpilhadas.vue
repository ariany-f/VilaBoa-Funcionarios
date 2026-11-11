<template>
  <div>
    <ul class="flex items-end gap-1 sm:gap-1.5" :style="{ height: props.altura + 'px' }" role="list" :aria-label="descricao">
      <li
        v-for="i in itens"
        :key="i.chave"
        class="flex-1 min-w-0 h-full flex flex-col justify-end items-center group"
        :title="dica(i)"
        :aria-label="dica(i)"
      >
        <span class="text-[10px] font-semibold text-slate-600 mb-1 leading-none tabular-nums">{{ valorTopo(i) }}</span>
        <span class="w-full max-w-[34px] flex flex-col justify-end rounded-t-md overflow-hidden bg-slate-100/70" :style="{ height: alturaBarra(i) + '%' }">
          <span class="block w-full motion-safe:transition-all motion-safe:duration-700" :style="{ height: pct(i.pend, i) + '%', background: corPend }"></span>
          <span class="block w-full motion-safe:transition-all motion-safe:duration-700" :style="{ height: pct(i.ok, i) + '%', background: corOk }"></span>
        </span>
      </li>
    </ul>
    <ul class="flex gap-1 sm:gap-1.5 mt-1.5" role="presentation" aria-hidden="true">
      <li v-for="i in itens" :key="i.chave" class="flex-1 min-w-0 text-center text-[10px] text-slate-400 truncate">{{ i.rotulo }}</li>
    </ul>
    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-3 text-[11px] text-slate-500">
      <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" :style="{ background: corOk }"></span>{{ rotuloOk }}</span>
      <span v-if="rotuloPend" class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" :style="{ background: corPend }"></span>{{ rotuloPend }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

// itens: [{ chave, rotulo, ok, pend }]. modo "pct": cada coluna vai a 100% e mostra a taxa de "ok";
// modo "abs": a altura segue o total, e o topo mostra a contagem.
const props = defineProps({
  itens: { type: Array, default: () => [] },
  modo: { type: String, default: 'pct' },
  rotuloOk: { type: String, default: 'Concluídos' },
  rotuloPend: { type: String, default: 'Pendentes' },
  corOk: { type: String, default: '#10b981' },
  corPend: { type: String, default: '#f59e0b' },
  descricao: { type: String, default: 'Gráfico de colunas' },
  altura: { type: Number, default: 110 }
});

const total = (i) => (i.ok || 0) + (i.pend || 0);
const maximo = computed(() => Math.max(1, ...props.itens.map(total)));
// altura (em %) da coluna dentro da área do gráfico; deixa folga para o número no topo
const alturaBarra = (i) => {
  if (!total(i)) return 2;
  return props.modo === 'pct' ? 100 : Math.max(6, Math.round((total(i) / maximo.value) * 100));
};
const pct = (parte, i) => (total(i) ? (parte / total(i)) * 100 : 0);
const valorTopo = (i) => {
  if (!total(i)) return '·';
  return props.modo === 'pct' ? Math.round((i.ok / total(i)) * 100) + '%' : String(total(i));
};
const dica = (i) => {
  const t = total(i);
  if (!t) return `${i.rotulo}: sem registros`;
  return props.modo === 'pct'
    ? `${i.rotulo}: ${i.ok} de ${t} ${props.rotuloOk.toLowerCase()} (${Math.round((i.ok / t) * 100)}%)`
    : `${i.rotulo}: ${i.ok} ${props.rotuloOk.toLowerCase()}${props.rotuloPend ? ', ' + i.pend + ' ' + props.rotuloPend.toLowerCase() : ''}`;
};
</script>
