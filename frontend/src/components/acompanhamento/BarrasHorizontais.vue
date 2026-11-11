<template>
  <ul class="space-y-3.5" role="list">
    <li v-for="i in itens" :key="i.chave ?? i.rotulo">
      <div class="flex items-baseline justify-between gap-3">
        <span class="min-w-0 text-[13px] font-medium text-slate-800 truncate" :title="i.rotulo">
          {{ i.rotulo }}
          <span v-if="i.tag" class="ml-1.5 inline-block max-w-[9rem] truncate align-middle text-[9px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full" :class="i.tagClasse || 'bg-slate-100 text-slate-500'">{{ i.tag }}</span>
        </span>
        <span class="shrink-0 text-xs tabular-nums text-slate-500">
          <strong class="text-slate-900">{{ i.valor }}</strong><template v-if="i.total !== undefined"> de {{ i.total }}</template>
          <template v-if="i.total"> · {{ pct(i) }}%</template>
        </span>
      </div>
      <div
        class="mt-1.5 h-2.5 rounded-full bg-slate-100 overflow-hidden"
        role="progressbar"
        :aria-valuenow="pct(i)"
        aria-valuemin="0"
        aria-valuemax="100"
        :aria-label="`${i.rotulo}: ${i.valor}${i.total !== undefined ? ' de ' + i.total : ''}`"
      >
        <div class="h-full rounded-full motion-safe:transition-all motion-safe:duration-700" :style="{ width: Math.max(pct(i), i.valor ? 3 : 0) + '%', background: i.cor || corPadrao }"></div>
      </div>
      <p v-if="i.sub" class="mt-1 text-[11px] text-slate-400">{{ i.sub }}</p>
    </li>
  </ul>
</template>

<script setup>
// itens: [{ rotulo, valor, total?, tag?, tagClasse?, sub?, cor? }]. Sem "total", a barra usa o maior valor da lista.
const props = defineProps({
  itens: { type: Array, default: () => [] },
  corPadrao: { type: String, default: '#10b981' }
});
const maior = () => Math.max(1, ...props.itens.map((i) => i.valor || 0));
const pct = (i) => Math.round(((i.valor || 0) / (i.total !== undefined ? i.total || 1 : maior())) * 100);
</script>
