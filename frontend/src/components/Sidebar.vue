<template>
  <aside 
    class="hidden md:flex flex-col h-full shrink-0 bg-white text-slate-800 transition-all duration-300 relative z-30 shadow-sm border-r border-slate-200 select-none overflow-hidden"
    :class="recolhida ? 'w-20' : 'w-64'"
  >
    <!-- Topo da Sidebar: Identidade da Marca & Toggle -->
    <div class="h-16 px-4 flex items-center justify-between border-b border-slate-200 shrink-0 bg-white">
      <!-- Brand Expandida -->
      <div v-if="!recolhida" class="flex items-center overflow-hidden min-w-0">
        <img :src="logoEmpresa" alt="Logo da empresa" class="h-8 w-auto max-w-[170px] object-contain" />
      </div>

      <!-- Brand Recolhida -->
      <div v-else class="w-full flex justify-center">
        <div class="w-9 h-9 rounded-xl bg-[#f4f4f5] border border-slate-200 flex items-center justify-center font-semibold text-xs text-[#9c2337] shadow-xs">
          VB
        </div>
      </div>

      <!-- Botão de Toggle Retrátil -->
      <button 
        type="button"
        @click="$emit('toggle')"
        class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-400 hover:text-slate-900 flex items-center justify-center text-xs transition cursor-pointer border border-slate-200 shrink-0"
        :title="recolhida ? 'Expandir Menu' : 'Recolher Menu'"
        aria-label="Alternar Menu"
      >
        <AppIcon :name="recolhida ? 'chevron-right' : 'chevron-left'" size="13" />
      </button>
    </div>

    <!-- Empresa / Filial do colaborador -->
    <div v-if="!recolhida" class="px-3 pt-4 shrink-0">
      <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200">
        <span class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-500 flex items-center justify-center shrink-0">
          <AppIcon name="hard-hat" size="15" />
        </span>
        <span class="leading-tight min-w-0">
          <span class="text-xs font-semibold text-slate-800 block truncate">Vila Boa Construções e Serviços</span>
          <span class="text-[10px] text-slate-400 block truncate">Matriz · Obras & Infraestrutura</span>
        </span>
      </div>
    </div>

    <!-- Navegação / Módulos Corporativos Fluidos (Sem cabeçalhos redundantes) -->
    <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1.5 min-h-0 custom-scrollbar">
      
      <!-- Módulos liberados -->
      <template v-for="modulo in modulos" :key="modulo.id">
      <button
        type="button"
        @click="$emit('selecionar', modulo.id)"
        class="relative w-full flex items-center px-3 py-3 rounded-xl text-xs font-bold transition-all duration-150 cursor-pointer group"
        :class="abaAtiva === modulo.id ? 'bg-slate-100 text-[#9c2337]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
        :title="recolhida ? modulo.rotulo : ''"
      >
        <AppIcon :name="modulo.icone" size="18" :class="recolhida ? 'mx-auto' : 'mr-3 text-slate-400 group-hover:text-slate-700'" />
        <span v-if="!recolhida" class="flex-1 min-w-0 text-left">
          <span class="block truncate">{{ modulo.rotulo }}</span>
          <span v-if="modulo.id === 'cadernos' && progressoCadernos?.total" class="flex items-center gap-2 mt-1.5">
            <span class="flex-1 h-1 rounded-full bg-slate-200 overflow-hidden">
              <span
                class="block h-full rounded-full transition-all duration-500"
                :class="progressoCadernos.lidos === progressoCadernos.total ? 'bg-emerald-500' : 'bg-[#9c2337]'"
                :style="{ width: Math.round((progressoCadernos.lidos / progressoCadernos.total) * 100) + '%' }"
              ></span>
            </span>
            <span
              class="text-[10px] font-semibold tabular-nums"
              :class="progressoCadernos.lidos === progressoCadernos.total ? 'text-emerald-600' : 'text-slate-500'"
            >{{ progressoCadernos.lidos === progressoCadernos.total ? '✓' : '' }} {{ progressoCadernos.lidos }}/{{ progressoCadernos.total }}</span>
          </span>
        </span>
        <span
          v-if="recolhida && modulo.id === 'cadernos' && progressoCadernos && progressoCadernos.lidos < progressoCadernos.total"
          class="absolute top-2 right-2 w-2 h-2 rounded-full bg-[#9c2337]"
        ></span>
      </button>
      </template>

      <!-- Bloco Informativo de Conformidade (Apenas quando expandida) -->
      <div v-if="!recolhida" class="pt-6 px-1">
        <div class="p-3 rounded-2xl bg-[#f4f4f5] border border-slate-200 space-y-1.5">
          <div class="flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Conformidade eSocial</span>
          </div>
          <p class="text-[10px] text-slate-400 leading-relaxed">
            Demonstrativos com ciência registrada sob o Art. 464 da CLT.
          </p>
        </div>
      </div>
    </div>

    <!-- Rodapé da Sidebar: Perfil do Colaborador + Botão Sair Integrado -->
    <div class="p-3 border-t border-slate-200 bg-white shrink-0 mt-auto">
      <!-- Versão Expandida -->
      <div 
        v-if="!recolhida" 
        class="flex items-center justify-between p-2 rounded-xl bg-[#f4f4f5] border border-slate-200 hover:border-slate-200 transition-all"
      >
        <div class="flex items-center space-x-2.5 overflow-hidden min-w-0 flex-1">
          <div class="relative shrink-0">
            <div class="w-8 h-8 rounded-lg bg-[#f4f4f5] border border-slate-200 flex items-center justify-center font-semibold text-xs text-[#9c2337] shadow-xs">
              {{ colaborador?.nome ? colaborador.nome.slice(0, 2).toUpperCase() : 'VB' }}
            </div>
            <span class="w-2 h-2 rounded-full bg-emerald-400 border-2 border-white absolute -bottom-0.5 -right-0.5 shadow-[0_0_6px_rgba(52,211,153,0.8)]"></span>
          </div>
          <div class="overflow-hidden min-w-0">
            <h4 class="text-xs font-bold truncate text-slate-900 leading-tight">{{ colaborador?.nome }}</h4>
            <span class="text-[10px] text-slate-400 block truncate">{{ colaborador?.cargo || 'Colaborador' }}</span>
          </div>
        </div>

        <button 
          type="button"
          @click="$emit('logout')"
          class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 flex items-center justify-center transition cursor-pointer shrink-0 ml-1"
          title="Encerrar Sessão"
          aria-label="Sair da Conta"
        >
          <AppIcon name="log-out" size="13" />
        </button>
      </div>

      <!-- Versão Recolhida -->
      <div v-else class="flex flex-col items-center space-y-2 py-1">
        <div class="relative">
          <div class="w-9 h-9 rounded-xl bg-[#f4f4f5] border border-slate-200 flex items-center justify-center font-semibold text-xs text-[#9c2337]" :title="colaborador?.nome">
            {{ colaborador?.nome ? colaborador.nome.slice(0, 2).toUpperCase() : 'VB' }}
          </div>
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 border-2 border-white absolute -bottom-0.5 -right-0.5 shadow-[0_0_8px_rgba(52,211,153,0.7)]"></span>
        </div>

        <button 
          type="button"
          @click="$emit('logout')"
          class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 flex items-center justify-center transition cursor-pointer"
          title="Sair da Conta"
          aria-label="Sair da Conta"
        >
          <AppIcon name="log-out" size="14" />
        </button>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import { logoEmpresa } from '../utils/logoEmpresa.js';
import AppIcon from './AppIcon.vue';
import { temMenu } from '../utils/permissoes.js';

const props = defineProps({
  recolhida: {
    type: Boolean,
    default: false
  },
  abaAtiva: {
    type: String,
    required: true
  },
  colaborador: {
    type: Object,
    default: null
  },
  episPendentes: {
    type: Number,
    default: 0
  },
  progressoCadernos: {
    type: Object,
    default: null
  }
});

defineEmits(['toggle', 'selecionar', 'logout', 'gerar-pacote']);

const todosModulos = [
  { id: 'holerites', rotulo: 'Holerites & Salários', icone: 'document' },
  { id: 'ponto', rotulo: 'Espelho de Ponto', icone: 'calendar' },
  { id: 'cadernos', rotulo: 'Cadernos', icone: 'landmark' }
];

// O Painel do RH agora fica separado, em /admin
const modulos = computed(() => todosModulos);
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(100,116,139,0.25);
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(100,116,139,0.45);
}
</style>
