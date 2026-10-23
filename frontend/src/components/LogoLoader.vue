<template>
  <div class="flex flex-col items-center justify-center p-6 text-center select-none">
    <!-- Caixa do Logo com Efeito Caligráfico Segmentado (Sem corte vertical no subtítulo) -->
    <div class="relative w-72 sm:w-88 max-w-full aspect-[300/69] flex items-center justify-center">
      <svg 
        viewBox="0 0 300 69" 
        class="w-full h-full overflow-visible"
        xmlns="http://www.w3.org/2000/svg"
      >
        <defs>
          <!-- Zonas de Corte Físico: Isolam o título e o subtítulo para impedir vazamento de traçado -->
          <clipPath id="top-zone">
            <rect x="0" y="0" width="300" height="40" />
          </clipPath>
          
          <clipPath id="bot-zone">
            <rect x="0" y="41" width="300" height="28" />
          </clipPath>

          <!-- Máscara de Escrita com Revelação em Duas Etapas Perfeitas -->
          <mask id="write-mask" maskUnits="userSpaceOnUse" x="0" y="0" width="300" height="69">
            <!-- Etapa 1: Traçado das Letras Superiores "V I L A   B O A" (Restrito a Y <= 40) -->
            <g clip-path="url(#top-zone)">
              <path
                :d="pathTop"
                fill="none"
                stroke="white"
                stroke-width="34"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="writing-stroke-top"
              />
            </g>

            <!-- Etapa 2: Traçado do Subtítulo "CONSTRUÇÕES E SERVIÇOS" (Restrito a Y >= 41, altura total) -->
            <g clip-path="url(#bot-zone)">
              <path
                d="M 8 55 L 292 55"
                fill="none"
                stroke="white"
                stroke-width="28"
                stroke-linecap="round"
                class="writing-stroke-bot"
              />
            </g>
          </mask>
        </defs>

        <!-- Camada 1: Rascunho / Guia Translúcido em Marca D'água (O contorno prévio das letras) -->
        <image 
          href="/img/logo-vilaboa-color.png" 
          x="0" 
          y="0" 
          width="300" 
          height="69" 
          class="opacity-15 filter grayscale pointer-events-none"
        />

        <!-- Camada 2: Logo Oficial em Cores Sendo Escrito e Revelado com Precisão -->
        <image 
          href="/img/logo-vilaboa-color.png" 
          x="0" 
          y="0" 
          width="300" 
          height="69" 
          mask="url(#write-mask)"
          class="pointer-events-none"
        />
      </svg>
    </div>

    <!-- Rótulo Institucional -->
    <div class="mt-6 space-y-0.5">
      <span class="text-xs font-bold text-slate-700 tracking-wide block">
        {{ mensagem || 'Acessando o portal corporativo...' }}
      </span>
      <span class="text-[10px] text-slate-400 font-medium block">
        Vila Boa Construções e Serviços LTDA
      </span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  mensagem: {
    type: String,
    default: 'Acessando o portal corporativo...'
  }
});

// Traçado caligráfico exclusivo das letras superiores (V I L A   B O A)
const pathTop = 
  'M 16 6 L 30 35 L 44 6 ' +
  'L 55 6 L 55 35 ' +
  'L 66 6 L 66 35 L 97 35 ' +
  'L 106 35 L 121 6 L 137 35 L 114 22 L 129 22 ' +
  'L 170 35 L 170 6 L 195 7 L 197 20 L 170 20 L 199 20 L 202 35 L 170 35 ' +
  'L 226 6 L 208 20 L 226 35 L 244 20 L 226 6 ' +
  'L 252 35 L 267 6 L 283 35 L 260 22 L 276 22';
</script>

<style scoped>
/* 1. Traçado das letras superiores (0% a 56%) */
.writing-stroke-top {
  stroke-dasharray: 1150;
  stroke-dashoffset: 1150;
  animation: writeTop 3.4s cubic-bezier(0.35, 0, 0.25, 1) infinite;
}

@keyframes writeTop {
  0% {
    stroke-dashoffset: 1150;
  }
  56% {
    stroke-dashoffset: 0;
  }
  92% {
    stroke-dashoffset: 0;
    opacity: 1;
  }
  100% {
    stroke-dashoffset: 1150;
    opacity: 0.95;
  }
}

/* 2. Traçado do subtítulo: só inicia APÓS as letras superiores terminarem (56% a 76%), cobrindo a altura inteira */
.writing-stroke-bot {
  stroke-dasharray: 300;
  stroke-dashoffset: 300;
  animation: writeBot 3.4s cubic-bezier(0.25, 0, 0.25, 1) infinite;
}

@keyframes writeBot {
  0%, 54% {
    stroke-dashoffset: 300;
    opacity: 0;
  }
  56% {
    stroke-dashoffset: 300;
    opacity: 1;
  }
  76% {
    stroke-dashoffset: 0;
    opacity: 1;
  }
  92% {
    stroke-dashoffset: 0;
    opacity: 1;
  }
  100% {
    stroke-dashoffset: 300;
    opacity: 0;
  }
}
</style>
