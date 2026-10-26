<template>
  <div v-if="aberto" class="fixed inset-0 z-50 flex md:hidden">
    <!-- Backdrop com blur escuro suave e toque para fechar -->
    <div 
      class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity animate-fade-in"
      @click="$emit('close')"
      aria-hidden="true"
    ></div>

    <!-- Gaveta Deslizante Lateral (Estilo Superapp Executivo Light) -->
    <div 
      class="relative w-[85%] max-w-xs bg-white text-slate-800 flex flex-col justify-between shadow-2xl z-10 animate-slide-right border-r border-slate-200/90 select-none h-full overflow-hidden"
      role="dialog"
      aria-modal="true"
      aria-label="Menu de Navegação Lateral"
    >
      <!-- Topo: Identidade da Empresa e Botão Fechar -->
      <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/60 shrink-0">
        <div class="flex items-center space-x-2.5 min-w-0">
          <img src="/img/logo-vilaboa-color.png" alt="Vila Boa Construções e Serviços" class="h-8 w-auto max-w-[170px] object-contain" />
        </div>

        <!-- Botão Fechar Estilo Glass Bubble Nativo -->
        <button 
          type="button" 
          @click="$emit('close')" 
          class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-500 hover:text-slate-800 flex items-center justify-center transition cursor-pointer shadow-2xs shrink-0"
          title="Fechar menu"
          aria-label="Fechar menu"
        >
          <AppIcon name="close" size="14" />
        </button>
      </div>

      <!-- Área de Rolagem do Menu -->
      <div class="flex-1 overflow-y-auto p-4 space-y-4">
        
        <!-- Cartão do Colaborador Logado (Visual Fintech) -->
        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-center space-x-3 shadow-2xs">
          <div class="relative shrink-0">
            <div class="w-11 h-11 rounded-2xl bg-white border border-slate-200 flex items-center justify-center font-semibold text-sm text-[#9c2337] shadow-2xs">
              {{ colaborador?.nome ? colaborador.nome.slice(0, 2).toUpperCase() : 'VB' }}
            </div>
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-white absolute -bottom-0.5 -right-0.5 shadow-[0_0_6px_rgba(16,185,129,0.8)]"></span>
          </div>
          <div class="overflow-hidden min-w-0 flex-1">
            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Colaborador</span>
            <h4 class="text-xs font-semibold truncate text-slate-900 leading-tight">{{ colaborador?.nome || 'Usuário' }}</h4>
            <span class="text-[10px] text-slate-500 font-medium block truncate mt-0.5">
              {{ colaborador?.cargo || 'Colaborador' }}
            </span>
          </div>
        </div>

        <!-- Grupo 1: Navegação Principal -->
        <div class="space-y-1.5">
          <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-2 block">
            Navegação
          </span>

          <nav class="space-y-1 text-xs">
            <!-- Módulos liberados -->
            <template v-for="(modulo, i) in modulos" :key="modulo.id">
            <span
              v-if="temRh && (i === 0 || (modulo.somenteRh && !modulos[i - 1].somenteRh))"
              class="block px-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400"
              :class="i === 0 ? '' : 'pt-3'"
            >{{ modulo.somenteRh ? 'Portal do RH' : 'Meu portal' }}</span>
            <button
              type="button"
              @click="navegar(modulo.id)"
              class="w-full flex items-center justify-between p-3 rounded-2xl transition cursor-pointer group active:scale-[0.98]"
              :class="abaAtiva === modulo.id ? 'bg-[#9c2337] text-white shadow-xs font-bold' : 'text-slate-700 hover:bg-slate-50 border border-transparent hover:border-slate-200 font-semibold'"
            >
              <div class="flex items-center space-x-3 min-w-0">
                <div 
                  class="w-8 h-8 rounded-xl flex items-center justify-center transition shrink-0"
                  :class="abaAtiva === modulo.id ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 group-hover:text-slate-600 group-hover:bg-slate-100'"
                >
                  <AppIcon :name="modulo.icone" size="16" />
                </div>
                <div class="text-left min-w-0">
                  <span class="block truncate leading-tight">{{ modulo.rotulo }}</span>
                  <span 
                    class="text-[10px] block mt-0.5 font-normal truncate"
                    :class="abaAtiva === modulo.id ? 'text-white/80' : 'text-slate-400'"
                  >
                    <template v-if="modulo.id === 'cadernos' && progressoCadernos?.total">
                      <span :class="progressoCadernos.lidos === progressoCadernos.total ? 'text-emerald-600 font-semibold' : ''">
                        {{ progressoCadernos.lidos === progressoCadernos.total ? '✓ Todos lidos' : progressoCadernos.lidos + ' de ' + progressoCadernos.total + ' lidos' }}
                      </span>
                    </template>
                    <template v-else>{{ modulo.descricao }}</template>
                  </span>
                </div>
              </div>
              <AppIcon 
                name="chevron-right" 
                size="14" 
                :class="abaAtiva === modulo.id ? 'text-white/70' : 'text-slate-300 group-hover:text-slate-500'" 
              />
            </button>
            </template>
          </nav>
        </div>

        <!-- Grupo 2: Ações & Utilidades -->
        <div class="space-y-1.5 pt-2 border-t border-slate-100">
          <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-2 block">
            Serviços Rápidos
          </span>

          <div class="space-y-1 text-xs">


            <!-- Ação 2: Privacidade LGPD -->
            <button
              type="button"
              @click="acionarLgpd"
              class="w-full flex items-center justify-between p-3 rounded-2xl bg-white hover:bg-slate-50 border border-slate-200/60 transition cursor-pointer group active:scale-[0.98]"
            >
              <div class="flex items-center space-x-3 min-w-0">
                <div class="w-8 h-8 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 shrink-0">
                  <AppIcon name="shield-check" size="15" />
                </div>
                <div class="text-left min-w-0">
                  <span class="font-bold text-slate-800 block truncate leading-tight">Segurança & LGPD</span>
                  <span class="text-[10px] text-slate-400 block font-normal truncate mt-0.5">Proteção e direitos do titular</span>
                </div>
              </div>
              <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 shrink-0">
                Ativa
              </span>
            </button>
          </div>
        </div>

      </div>

      <!-- Rodapé da Gaveta Lateral: Sair da Conta e Informações Legais -->
      <div class="p-4 border-t border-slate-100 bg-slate-50/80 space-y-3 shrink-0">
        <!-- Botão Sair com Estilo Limpo -->
        <button
          type="button"
          @click="solicitarLogout"
          class="w-full py-2.5 px-4 rounded-xl bg-white hover:bg-rose-50 border border-slate-200/90 hover:border-rose-200 text-slate-700 hover:text-[#9c2337] text-xs font-bold transition flex items-center justify-center gap-2 cursor-pointer active:scale-[0.98] shadow-2xs"
          title="Encerrar sessão no dispositivo"
        >
          <AppIcon name="log-out" size="15" class="text-slate-500" />
          <span>Sair da Minha Conta</span>
        </button>

        <div class="text-[9px] text-slate-400 text-center space-y-0.5 font-mono">
          <p class="font-semibold text-slate-500 font-sans">Vila Boa Construções e Serviços</p>
          <p>Portal do Colaborador · v2.6</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import AppIcon from './AppIcon.vue';
import { temMenu } from '../utils/permissoes.js';

const props = defineProps({
  aberto: {
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

const emit = defineEmits(['close', 'selecionar', 'logout', 'gerar-pacote', 'abrir-lgpd']);

const todosModulos = [
  { id: 'holerites', rotulo: 'Holerites & Folha', descricao: 'Demonstrativos de pagamento', icone: 'document' },
  { id: 'ponto', rotulo: 'Espelho de Ponto', descricao: 'Marcações e assinatura do mês', icone: 'calendar' },
  { id: 'cadernos', rotulo: 'Cadernos', descricao: 'Manuais e programas da empresa', icone: 'landmark' },
  { id: 'rh', rotulo: 'Painel do RH', descricao: 'Recebimentos, assinaturas e leituras', icone: 'shield-check', somenteRh: true }
];

// O Painel do RH só aparece para perfis com o menu do RH
const modulos = computed(() => todosModulos.filter(m => !m.somenteRh || temMenu(props.colaborador, '/rh')));
const temRh = computed(() => modulos.value.some(m => m.somenteRh));

function navegar(aba) {
  emit('selecionar', aba);
  emit('close');
}

function acionarPacoteRenda() {
  emit('gerar-pacote');
  emit('close');
}

function acionarLgpd() {
  emit('abrir-lgpd');
  emit('close');
}

function solicitarLogout() {
  emit('logout');
  emit('close');
}
</script>

<style scoped>
@keyframes slideRight {
  from {
    transform: translateX(-100%);
  }
  to {
    transform: translateX(0);
  }
}
.animate-slide-right {
  animation: slideRight 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
.animate-fade-in {
  animation: fadeIn 0.2s ease-out;
}
</style>
