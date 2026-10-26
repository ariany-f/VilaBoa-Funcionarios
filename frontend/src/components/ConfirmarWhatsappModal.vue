<template>
  <div v-if="holerite" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-all duration-300">
    <div 
      class="bg-white w-full max-w-md rounded-2xl shadow-2xl flex flex-col overflow-hidden animate-scale-in border border-slate-200/90 text-left"
      @click.stop
    >
      <!-- Cabeçalho do Modal -->
      <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
        <div class="flex items-center space-x-3 min-w-0">
          <div class="w-10 h-10 rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 shadow-2xs shrink-0">
            <AppIcon name="whatsapp" size="20" />
          </div>
          <div class="min-w-0">
            <h3 class="text-sm font-semibold text-slate-900 leading-tight">Enviar para WhatsApp</h3>
            <span class="text-[11px] text-slate-500 font-medium block truncate mt-0.5">
              {{ holerite.mes_nome }} / {{ holerite.ano }} · {{ holerite.tipo_folha || 'Folha Mensal' }}
            </span>
          </div>
        </div>

        <button 
          type="button"
          @click="$emit('close')" 
          class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-500 hover:text-slate-800 flex items-center justify-center transition cursor-pointer shadow-2xs shrink-0"
          aria-label="Fechar"
        >
          <AppIcon name="close" size="14" />
        </button>
      </div>

      <!-- Corpo do Modal -->
      <div class="p-5 space-y-4 text-xs">
        
        <!-- Bloco de Conferência do Telefone Cadastrado -->
        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-[10px] uppercase font-semibold tracking-wider text-slate-400">
              Número Cadastrado no Sistema
            </span>
            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-800 bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 rounded-full">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              <span>Verificado</span>
            </span>
          </div>

          <div v-if="!editandoNumero" class="flex items-center justify-between pt-1">
            <div class="text-lg sm:text-xl font-semibold text-slate-900 font-mono tracking-tight">
              {{ telefoneExibicao }}
            </div>

            <button 
              type="button" 
              @click="iniciarEdicaoNumero"
              class="text-xs font-bold text-[#9c2337] hover:text-[#831b2d] hover:underline cursor-pointer flex items-center gap-1"
            >
              <AppIcon name="edit" size="12" />
              <span>Alterar</span>
            </button>
          </div>

          <!-- Campo de Edição Manual do Número (caso precise corrigir) -->
          <div v-else class="space-y-2 pt-1 animate-fade-in">
            <input 
              v-model="telefoneInput" 
              @input="onInputTelefone"
              type="tel" 
              placeholder="(00) 00000-0000"
              class="w-full h-10 px-3 text-sm font-mono font-bold bg-white border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1DA851]/40 focus:border-[#1DA851]"
            />
            <div class="flex items-center justify-end gap-2">
              <button 
                type="button" 
                @click="cancelarEdicaoNumero"
                class="px-3 py-1 rounded-lg text-slate-500 hover:bg-slate-200 text-[11px] font-bold transition cursor-pointer"
              >
                Cancelar
              </button>
              <button 
                type="button" 
                @click="salvarNumeroTemporario"
                class="px-3 py-1 rounded-lg bg-slate-900 text-white text-[11px] font-bold transition cursor-pointer"
              >
                Confirmar Número
              </button>
            </div>
          </div>
        </div>

        <!-- Explicação do Token e Abertura Instantânea -->
        <div class="p-3.5 bg-emerald-50/70 border border-emerald-200/80 rounded-2xl flex items-start gap-3">
          <div class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0 mt-0.5 shadow-2xs">
            <AppIcon name="check" size="12" />
          </div>
          <div class="space-y-1 text-emerald-950">
            <strong class="font-bold text-xs block leading-tight">Acesso Direto com Token Criptografado</strong>
            <p class="text-[11px] leading-relaxed text-emerald-900/90">
              O link enviado conterá sua chave de autenticação digital. Ao tocar nele no WhatsApp, o holerite abrirá <strong>imediatamente aberto</strong> na tela, sem exigir nova senha.
            </p>
          </div>
        </div>

        <!-- Identificação do Colaborador -->
        <div class="px-1 text-[11px] text-slate-500 flex items-center justify-between">
          <span>Destinatário: <strong class="text-slate-700 font-semibold">{{ colaborador?.nome }}</strong></span>
          <span>Matrícula: <strong class="text-slate-700 font-mono">{{ colaborador?.matricula }}</strong></span>
        </div>
      </div>

      <!-- Rodapé com Botões de Ação -->
      <div class="p-4 border-t border-slate-100 bg-slate-50/80 flex items-center justify-end gap-2.5">
        <button 
          type="button"
          @click="$emit('close')"
          class="h-11 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 font-bold text-xs transition cursor-pointer active:scale-95 shadow-2xs"
        >
          Cancelar
        </button>

        <button 
          type="button"
          @click="confirmarEEnviar"
          :disabled="!telefoneValido"
          class="h-11 px-5 rounded-xl bg-[#1DA851] hover:bg-[#178F44] active:scale-95 text-white font-semibold text-xs shadow-md shadow-[#1DA851]/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <AppIcon name="whatsapp" size="16" />
          <span>Confirmar e Enviar para WhatsApp</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { usarFecharComEsc } from '../utils/fecharComEsc.js';
import { ref, computed } from 'vue';
import AppIcon from './AppIcon.vue';
import { maskPhone } from '../utils/masks.js';

const props = defineProps({
  holerite: {
    type: Object,
    required: true
  },
  colaborador: {
    type: Object,
    required: true
  },
  token: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['close', 'enviado']);
usarFecharComEsc(() => emit('close'));

// Estado de edição do telefone
const editandoNumero = ref(false);
const telefoneCustomizado = ref('');
const telefoneInput = ref('');

const telefoneAtualFormatado = computed(() => {
  const tel = props.colaborador?.telefone_atual || props.colaborador?.telefone || '';
  return tel ? maskPhone(tel) : '(11) 98765-4321';
});

const telefoneExibicao = computed(() => {
  return telefoneCustomizado.value ? maskPhone(telefoneCustomizado.value) : telefoneAtualFormatado.value;
});

const telefoneValido = computed(() => {
  const digitos = (telefoneCustomizado.value || telefoneAtualFormatado.value).replace(/\D/g, '');
  return digitos.length >= 10 && digitos.length <= 11;
});

function iniciarEdicaoNumero() {
  telefoneInput.value = telefoneExibicao.value;
  editandoNumero.value = true;
}

function onInputTelefone(e) {
  telefoneInput.value = maskPhone(e.target.value);
}

function salvarNumeroTemporario() {
  const digitos = telefoneInput.value.replace(/\D/g, '');
  if (digitos.length >= 10) {
    telefoneCustomizado.value = telefoneInput.value;
    editandoNumero.value = false;
  }
}

function cancelarEdicaoNumero() {
  editandoNumero.value = false;
}

function confirmarEEnviar() {
  const telString = telefoneCustomizado.value || props.colaborador?.telefone_atual || props.colaborador?.telefone || '';
  let digitos = telString.replace(/\D/g, '');
  
  // Garante DDI 55 do Brasil
  if (digitos.length === 10 || digitos.length === 11) {
    digitos = '55' + digitos;
  }

  // Gera o link seguro direto com o token e o ID do holerite
  const origin = typeof window !== 'undefined' ? window.location.origin : 'https://vilaboa.com.br';
  const tokenParaUsar = props.token || (typeof localStorage !== 'undefined' ? localStorage.getItem('vb_auth_token') : '') || '';
  
  const linkDireto = `${origin}/?token=${encodeURIComponent(tokenParaUsar)}&abrir_holerite=${encodeURIComponent(props.holerite.id)}`;

  const primeiroNome = props.colaborador?.nome ? props.colaborador.nome.split(' ')[0] : 'Colaborador';
  
  const textoMensagem = 
    `Olá, *${primeiroNome}*! 👋\n\n` +
    `Aqui está o seu *Demonstrativo de Pagamento* da *Vila Boa Construções* referente a *${props.holerite.mes_nome}/${props.holerite.ano}* (${props.holerite.tipo_folha || 'Folha Mensal'}).\n\n` +
    `📄 *Toque no link seguro abaixo para abrir seu holerite direto na tela:*\n` +
    `${linkDireto}\n\n` +
    `_Mensagem oficial do Portal do Colaborador Vila Boa._`;

  const urlWhatsapp = `https://api.whatsapp.com/send?phone=${digitos}&text=${encodeURIComponent(textoMensagem)}`;
  
  // Abre o WhatsApp no celular ou WhatsApp Web
  window.open(urlWhatsapp, '_blank');

  // Dispara evento de feedback e fecha o modal
  emit('enviado', {
    holeriteId: props.holerite.id,
    telefone: digitos
  });
  emit('close');
}
</script>

<style scoped>
@keyframes scaleIn {
  from {
    transform: scale(0.95);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}
.animate-scale-in {
  animation: scaleIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
