<template>
  <svg :viewBox="`0 0 ${tamanho} ${tamanho}`" :width="tamanho" :height="tamanho" role="img" :aria-label="rotulo || `${porcento}% concluído`">
    <circle :cx="c" :cy="c" :r="r" fill="none" :stroke="corFundo" :stroke-width="espessura" />
    <circle
      v-if="total"
      :cx="c" :cy="c" :r="r" fill="none" :stroke="cor" :stroke-width="espessura" stroke-linecap="round"
      :stroke-dasharray="`${(porcento / 100) * circ} ${circ}`"
      :transform="`rotate(-90 ${c} ${c})`"
      class="motion-safe:transition-all motion-safe:duration-700"
    />
    <text :x="c" :y="c" text-anchor="middle" dominant-baseline="central" :class="corTexto" :style="{ fontSize: tamanho * 0.24 + 'px', fontWeight: 700 }">{{ total ? porcento + '%' : '–' }}</text>
  </svg>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  ok: { type: Number, default: 0 },
  total: { type: Number, default: 0 },
  tamanho: { type: Number, default: 132 },
  espessura: { type: Number, default: 14 },
  cor: { type: String, default: '#10b981' },
  corFundo: { type: String, default: '#fde68a' },
  rotulo: { type: String, default: '' },
  corTexto: { type: String, default: 'fill-slate-900' }
});
const c = computed(() => props.tamanho / 2);
const r = computed(() => (props.tamanho - props.espessura) / 2);
const circ = computed(() => 2 * Math.PI * r.value);
const porcento = computed(() => (props.total ? Math.round((props.ok / props.total) * 100) : 0));
</script>
