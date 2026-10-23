<template>
  <aside 
    class="hidden md:flex flex-col h-full shrink-0 bg-[#0f1117] text-white transition-all duration-300 relative z-30 shadow-2xl border-r border-white/[0.08] select-none overflow-hidden"
    :class="recolhida ? 'w-20' : 'w-64'"
  >
    <!-- Topo da Sidebar: Identidade da Marca & Toggle -->
    <div class="h-16 px-4 flex items-center justify-between border-b border-white/[0.08] shrink-0 bg-white/[0.02]">
      <!-- Brand Expandida -->
      <div v-if="!recolhida" class="flex items-center space-x-2.5 overflow-hidden">
        <div class="w-8 h-8 rounded-xl bg-white/[0.08] border border-white/15 flex items-center justify-center font-black text-xs text-[#ba2e45] shadow-xs shrink-0">
          VB
        </div>
        <div class="leading-tight overflow-hidden">
          <div class="flex items-center gap-1.5">
            <span class="text-xs font-black text-white tracking-tight truncate">Vila Boa</span>
            <span class="text-[9px] bg-[#ba2e45]/20 text-rose-300 border border-[#ba2e45]/40 font-bold px-1.5 py-0.2 rounded-md uppercase">PRO</span>
          </div>
          <span class="text-[10px] text-slate-400 block truncate">Portal Corporativo</span>
        </div>
      </div>

      <!-- Brand Recolhida -->
      <div v-else class="w-full flex justify-center">
        <div class="w-9 h-9 rounded-xl bg-white/[0.08] border border-white/15 flex items-center justify-center font-black text-xs text-[#ba2e45] shadow-xs">
          VB
        </div>
      </div>

      <!-- Botão de Toggle Retrátil -->
      <button 
        type="button"
        @click="$emit('toggle')"
        class="w-7 h-7 rounded-lg bg-white/[0.05] hover:bg-white/[0.12] active:bg-white/[0.18] text-slate-400 hover:text-white flex items-center justify-center text-xs transition cursor-pointer border border-white/5 shrink-0"
        :title="recolhida ? 'Expandir Menu' : 'Recolher Menu'"
        aria-label="Alternar Menu"
      >
        <AppIcon :name="recolhida ? 'chevron-right' : 'chevron-left'" size="13" />
      </button>
    </div>

    <!-- Navegação / Módulos Corporativos Fluidos (Sem cabeçalhos redundantes) -->
    <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1.5 min-h-0 custom-scrollbar">
      
      <!-- Item 1: Holerites & Salários -->
      <button
        type="button"
        @click="$emit('selecionar', 'holerites')"
        class="w-full flex items-center px-3 py-3 rounded-xl text-xs font-bold transition-all duration-150 cursor-pointer group"
        :class="abaAtiva === 'holerites' ? 'bg-[#ba2e45] text-white shadow-sm shadow-[#ba2e45]/25' : 'text-slate-400 hover:bg-white/[0.06] hover:text-white'"
        :title="recolhida ? 'Holerites & Salários' : ''"
      >
        <AppIcon name="document" size="18" :class="recolhida ? 'mx-auto' : 'mr-3 text-slate-300 group-hover:text-white'" />
        <span v-if="!recolhida" class="truncate flex-1 text-left">Holerites & Salários</span>
        <span 
          v-if="!recolhida" 
          class="text-[10px] px-2 py-0.5 rounded-full font-bold"
          :class="abaAtiva === 'holerites' ? 'bg-white/20 text-white' : 'bg-white/10 text-slate-400 group-hover:text-white'"
        >
          Folha
        </span>
      </button>

      <!-- Bloco Informativo de Conformidade (Apenas quando expandida) -->
      <div v-if="!recolhida" class="pt-6 px-1">
        <div class="p-3 rounded-2xl bg-white/[0.03] border border-white/[0.06] space-y-1.5">
          <div class="flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-[10px] font-bold text-slate-300 uppercase tracking-wider">Conformidade eSocial</span>
          </div>
          <p class="text-[10px] text-slate-400 leading-relaxed">
            Demonstrativos com ciência registrada sob o Art. 464 da CLT.
          </p>
        </div>
      </div>
    </div>

    <!-- Rodapé da Sidebar: Perfil do Colaborador + Botão Sair Integrado -->
    <div class="p-3 border-t border-white/[0.08] bg-white/[0.02] shrink-0 mt-auto">
      <!-- Versão Expandida -->
      <div 
        v-if="!recolhida" 
        class="flex items-center justify-between p-2 rounded-xl bg-white/[0.04] border border-white/[0.08] hover:border-white/15 transition-all"
      >
        <div class="flex items-center space-x-2.5 overflow-hidden min-w-0 flex-1">
          <div class="relative shrink-0">
            <div class="w-8 h-8 rounded-lg bg-white/[0.08] border border-white/15 flex items-center justify-center font-black text-xs text-[#ba2e45] shadow-xs">
              {{ colaborador?.nome ? colaborador.nome.slice(0, 2).toUpperCase() : 'VB' }}
            </div>
            <span class="w-2 h-2 rounded-full bg-emerald-400 border-2 border-[#0f1117] absolute -bottom-0.5 -right-0.5 shadow-[0_0_6px_rgba(52,211,153,0.8)]"></span>
          </div>
          <div class="overflow-hidden min-w-0">
            <h4 class="text-xs font-bold truncate text-white leading-tight">{{ colaborador?.nome }}</h4>
            <span class="text-[10px] text-slate-400 block truncate">{{ colaborador?.cargo || 'Colaborador' }}</span>
          </div>
        </div>

        <button 
          type="button"
          @click="$emit('logout')"
          class="w-7 h-7 rounded-lg bg-white/[0.05] hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 flex items-center justify-center transition cursor-pointer shrink-0 ml-1"
          title="Encerrar Sessão"
          aria-label="Sair da Conta"
        >
          <AppIcon name="log-out" size="13" />
        </button>
      </div>

      <!-- Versão Recolhida -->
      <div v-else class="flex flex-col items-center space-y-2 py-1">
        <div class="relative">
          <div class="w-9 h-9 rounded-xl bg-white/[0.08] border border-white/15 flex items-center justify-center font-black text-xs text-[#ba2e45]" :title="colaborador?.nome">
            {{ colaborador?.nome ? colaborador.nome.slice(0, 2).toUpperCase() : 'VB' }}
          </div>
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 border-2 border-[#0f1117] absolute -bottom-0.5 -right-0.5 shadow-[0_0_8px_rgba(52,211,153,0.7)]"></span>
        </div>

        <button 
          type="button"
          @click="$emit('logout')"
          class="w-8 h-8 rounded-lg bg-white/[0.05] hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 flex items-center justify-center transition cursor-pointer"
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
import AppIcon from './AppIcon.vue';

defineProps({
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
  }
});

defineEmits(['toggle', 'selecionar', 'logout', 'gerar-pacote']);
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.12);
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.25);
}
</style>
