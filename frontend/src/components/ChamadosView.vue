<template>
  <div class="space-y-6 animate-slide-up">
    <!-- Banner Superior (Sólido Corporativo) -->
    <div class="bg-[#141414] border-l-4 border-[#9c2337] text-white rounded-2xl p-6 sm:p-7 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div class="space-y-1.5">
        <div class="flex items-center gap-2">
          <span class="text-xs bg-white/10 text-white font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider border border-white/10">
            Autoatendimento
          </span>
          <span class="text-xs text-rose-200">RH & Departamento Pessoal Digital</span>
        </div>
        <h2 class="text-xl sm:text-2xl font-semibold tracking-tight">Chamados & Serviços Rápidos</h2>
        <p class="text-xs text-slate-300 max-w-xl leading-relaxed">
          Abra solicitações de 2ª via de crachá, emissão de declarações de trabalho e tire dúvidas com o DP sem precisar se deslocar do canteiro.
        </p>
      </div>

      <button 
        type="button" 
        @click="modalNovoChamado = true"
        class="h-11 px-5 rounded-xl bg-white text-[#9c2337] hover:bg-rose-50 font-bold text-xs sm:text-sm shadow-md transition flex items-center gap-2 cursor-pointer shrink-0"
      >
        <AppIcon name="message-square" size="16" />
        <span>Novo Chamado</span>
      </button>
    </div>

    <!-- Lista de Solicitações do Colaborador -->
    <div class="bg-white rounded-2xl p-5 sm:p-7 border border-slate-200 shadow-sm space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div>
          <h3 class="text-sm sm:text-base font-bold text-slate-900">Meus Chamados Protocolados</h3>
          <p class="text-xs text-slate-400">Acompanhe as respostas e o andamento das suas solicitações</p>
        </div>
        <span class="text-xs font-bold text-slate-700 bg-slate-100 px-3 py-1 rounded-full">
          {{ chamados.length }} Solicitações
        </span>
      </div>

      <div v-if="chamados.length > 0" class="divide-y divide-slate-100">
        <div 
          v-for="c in chamados" 
          :key="c.id"
          class="py-4 space-y-2 hover:bg-slate-50/60 p-3 rounded-2xl transition"
        >
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-2.5">
              <span class="text-xs font-mono font-bold text-[#9c2337]">{{ c.id }}</span>
              <span class="text-xs font-bold text-slate-800">{{ c.tipo }}</span>
            </div>
            
            <div class="flex items-center gap-2">
              <span 
                class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                :class="c.status === 'Concluído' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900'"
              >
                {{ c.status }}
              </span>
              <span class="text-[11px] text-slate-400 font-mono">{{ formatarDataHora(c.data_abertura) }}</span>
            </div>
          </div>

          <p class="text-xs font-semibold text-slate-700">{{ c.assunto }}</p>

          <!-- Resposta do RH -->
          <div v-if="c.resposta_rh" class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-xs text-slate-600 flex items-start gap-2">
            <span class="font-bold text-[#9c2337] shrink-0">RH:</span>
            <span>{{ c.resposta_rh }}</span>
          </div>

          <div v-if="c.documento_disponivel" class="pt-1">
            <button 
              type="button" 
              @click="baixarDeclaracao"
              class="h-8 px-3.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold flex items-center gap-1.5 shadow-sm transition cursor-pointer"
            >
              <AppIcon name="download" size="14" />
              <span>Baixar Declaração Assinada (PDF)</span>
            </button>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-8 text-xs text-slate-400">
        Nenhum chamado aberto até o momento.
      </div>
    </div>

    <!-- Modal Novo Chamado -->
    <div v-if="modalNovoChamado" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-md rounded-2xl p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-sm font-semibold text-slate-900">Nova Solicitação ao RH</h3>
          <button @click="modalNovoChamado = false" class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-all cursor-pointer active:scale-90 border border-slate-200 shadow-2xs">
            <AppIcon name="x" size="16" />
          </button>
        </div>

        <form @submit.prevent="salvarChamado" class="space-y-3 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Tipo de Solicitação:</label>
            <select v-model="formChamado.tipo" class="w-full p-3 rounded-xl border border-slate-300 font-semibold" required>
              <option value="Declaração de Trabalho">Declaração de Trabalho / Vínculo</option>
              <option value="Segunda Via de Crachá">Segunda Via de Crachá de Acesso</option>
              <option value="Inclusão de Dependente">Inclusão de Dependente (IR / Plano de Saúde)</option>
              <option value="Dúvida de Holerite">Dúvidas sobre Folha / Adicionais</option>
              <option value="Atualização Cadastral">Alteração de Endereço Residencial</option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Assunto / Descrição do Pedido:</label>
            <textarea 
              v-model="formChamado.assunto" 
              rows="3" 
              placeholder="Descreva claramente o que você precisa do DP..." 
              class="w-full p-3 rounded-xl border border-slate-300 font-medium"
              required
            ></textarea>
          </div>

          <div class="flex gap-2 pt-2">
            <button 
              type="submit" 
              :disabled="carregando"
              class="flex-1 py-3 bg-[#9c2337] hover:bg-[#831b2d] text-white font-bold text-xs rounded-xl shadow transition cursor-pointer flex items-center justify-center gap-1.5"
            >
              <AppIcon name="check" size="14" />
              <span>{{ carregando ? 'Enviando...' : 'Abrir Chamado' }}</span>
            </button>
            <button 
              type="button"
              @click="modalNovoChamado = false" 
              class="h-12 px-5 bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-700 font-bold rounded-xl text-xs sm:text-sm border border-slate-200 shadow-2xs transition-all cursor-pointer shrink-0 active:scale-[0.98] flex items-center justify-center"
            >
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import AppIcon from './AppIcon.vue';

const props = defineProps({
  chamados: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['chamado-criado']);

const modalNovoChamado = ref(false);
const carregando = ref(false);
const formChamado = ref({
  tipo: 'Declaração de Trabalho',
  assunto: ''
});

function formatarDataHora(dataStr) {
  if (!dataStr) return '';
  try {
    const d = new Date(dataStr);
    return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
  } catch {
    return dataStr;
  }
}

async function salvarChamado() {
  const token = localStorage.getItem('vb_auth_token');
  if (!token) return;

  carregando.value = true;
  try {
    const res = await fetch('/api/chamados', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify(formChamado.value)
    });

    const data = await res.json();
    if (res.ok && data.chamado) {
      emit('chamado-criado', data.chamado);
      modalNovoChamado.value = false;
      formChamado.value.assunto = '';
    }
  } catch (err) {
    console.error('Falha ao abrir chamado:', err);
  } finally {
    carregando.value = false;
  }
}

function baixarDeclaracao() {
  window.print();
}
</script>
