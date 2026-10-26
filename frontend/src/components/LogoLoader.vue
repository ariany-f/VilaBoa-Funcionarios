<template>
  <div class="flex flex-col items-center justify-center p-6 text-center select-none">
    <!-- Logo oficial + anel de carregamento (roxo → bordô) -->
    <img src="/img/logo-vilaboa-color.png" alt="Vila Boa Construções e Serviços" class="h-12 w-auto max-w-[240px] object-contain" />

    <div class="relative w-10 h-10 mt-8">
      <svg viewBox="0 0 96 96" class="absolute inset-0 w-full h-full loader-ring" aria-hidden="true">
        <defs>
          <linearGradient id="loader-grad" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#141414" />
            <stop offset="100%" stop-color="#9c2337" />
          </linearGradient>
        </defs>
        <circle cx="48" cy="48" r="40" fill="none" stroke="#e2e8f0" stroke-width="8" />
        <circle
          cx="48" cy="48" r="40"
          fill="none"
          stroke="url(#loader-grad)"
          stroke-width="8"
          stroke-linecap="round"
          stroke-dasharray="252"
          class="loader-arc"
        />
      </svg>
    </div>

    <!-- Rótulo Institucional -->
    <div v-if="mensagem" class="mt-6 space-y-0.5">
      <span class="text-sm font-medium text-slate-800 block">
        {{ mensagem }}
      </span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  mensagem: {
    type: String,
    default: ''
  }
});
</script>

<style scoped>
.loader-ring {
  animation: loader-spin 1.4s linear infinite;
}
.loader-arc {
  transform-origin: 48px 48px;
  transform: rotate(-90deg);
  animation: loader-dash 1.4s ease-in-out infinite;
}

@keyframes loader-spin {
  to { transform: rotate(360deg); }
}
@keyframes loader-dash {
  0%   { stroke-dashoffset: 250; }
  50%  { stroke-dashoffset: 90; }
  100% { stroke-dashoffset: 250; }
}

@media (prefers-reduced-motion: reduce) {
  .loader-ring, .loader-arc { animation: none; }
}
</style>
