<template>
  <!-- Tela de abertura: logo com respiração suave e barra de progresso fina, como no splash de um app -->
  <div class="flex flex-col items-center justify-center p-6 text-center select-none" role="status" aria-live="polite">
    <img src="/img/logo-vilaboa-color.png" alt="Vila Boa Construções e Serviços" class="logo-respira h-12 w-auto max-w-[220px] object-contain" />

    <div class="mt-7 w-28 h-1 rounded-full bg-slate-200 overflow-hidden" aria-hidden="true">
      <div class="barra-corre h-full w-1/3 rounded-full bg-[#9c2337]"></div>
    </div>

    <span class="mt-4 text-xs font-medium text-slate-500" :class="mensagem ? '' : 'sr-only'">
      {{ mensagem || 'Carregando...' }}
    </span>
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
.logo-respira {
  animation: respira 1.8s ease-in-out infinite;
}
.barra-corre {
  animation: corre 1.1s cubic-bezier(0.65, 0, 0.35, 1) infinite;
}

@keyframes respira {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.75; transform: scale(0.97); }
}
@keyframes corre {
  0% { transform: translateX(-110%); }
  100% { transform: translateX(310%); }
}

@media (prefers-reduced-motion: reduce) {
  .logo-respira, .barra-corre { animation: none; }
}
</style>
