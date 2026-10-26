<template>
  <div class="space-y-6 animate-slide-up">
    <!-- Banner Superior de Férias (Apenas Consulta - Sólido Corporativo) -->
    <div class="bg-[#141414] border-l-4 border-[#9c2337] text-white rounded-2xl p-6 sm:p-7 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div class="space-y-1.5">
        <div class="flex items-center gap-2">
          <span class="text-xs bg-white/10 text-white font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider border border-white/10">
            CLT • Art. 129
          </span>
          <span class="text-xs text-rose-200">Painel de Consulta</span>
        </div>
        <h2 class="text-xl sm:text-2xl font-semibold tracking-tight">Consulta de Férias & Saldos</h2>
        <p class="text-xs text-slate-300 max-w-xl leading-relaxed">
          Acompanhe seu saldo de dias de descanso, o período aquisitivo vigente, prazos legais de concessão e o histórico de férias usufruídas.
        </p>
      </div>

      <!-- Saldo Destaque -->
      <div class="bg-black/30 px-5 py-3.5 rounded-2xl border border-white/10 text-center self-stretch sm:self-auto shrink-0">
        <span class="text-[10px] text-slate-300 uppercase font-bold block">Saldo Disponível</span>
        <div class="text-3xl font-semibold text-amber-300 font-mono">
          {{ ferias?.dias_saldo ?? 30 }} Dias
        </div>
        <span class="text-[9px] text-slate-300 block mt-0.5">Limite: {{ ferias?.periodo_aquisitivo_atual?.limite_concessivo }}</span>
      </div>
    </div>

    <!-- Status do Período e Detalhes da Programação -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- Coluna 1: Período Aquisitivo Vigente + Comunicado de Consulta -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Período Aquisitivo</h3>
          <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full font-bold">Vigente</span>
        </div>
        
        <div class="space-y-2 text-xs">
          <div class="flex justify-between py-1 border-b border-slate-100">
            <span class="text-slate-500">Início do Período:</span>
            <strong class="text-slate-800">{{ ferias?.periodo_aquisitivo_atual?.inicio }}</strong>
          </div>
          <div class="flex justify-between py-1 border-b border-slate-100">
            <span class="text-slate-500">Fim do Período:</span>
            <strong class="text-slate-800">{{ ferias?.periodo_aquisitivo_atual?.fim }}</strong>
          </div>
          <div class="flex justify-between py-1 border-b border-slate-100">
            <span class="text-slate-500">Limite de Concessão:</span>
            <strong class="text-[#9c2337]">{{ ferias?.periodo_aquisitivo_atual?.limite_concessivo }}</strong>
          </div>
          <div class="flex justify-between py-1">
            <span class="text-slate-500">Situação:</span>
            <span class="text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded">
              {{ ferias?.periodo_aquisitivo_atual?.status || 'Adquirido (Pronto para Gozo)' }}
            </span>
          </div>
        </div>

        <!-- Aviso Institucional: Consulta Exclusiva -->
        <div class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200 text-amber-900 space-y-1.5">
          <div class="flex items-center gap-1.5 font-bold text-xs text-amber-950">
            <AppIcon name="info" size="14" class="text-slate-500" />
            <span>Programação no Canteiro</span>
          </div>
          <p class="text-[11px] leading-relaxed text-amber-900/90">
            A definição das datas de férias é planejada diretamente junto à <strong>Engenharia de Campo</strong> e ao <strong>RH/DP da obra</strong>, respeitando o cronograma operacional. Este portal destina-se exclusivamente à <strong>consulta transparente de saldos e histórico</strong>.
          </p>
        </div>
      </div>

      <!-- Coluna 2 e 3: Programação Confirmada e Histórico -->
      <div class="lg:col-span-2 space-y-4">
        
        <!-- Card de Programação de Férias Agendada (se houver no cadastro) -->
        <div v-if="ferias?.solicitacao_atual" class="bg-emerald-50/70 border border-emerald-200 rounded-2xl p-5 sm:p-6 space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
              <span class="text-xs font-bold text-emerald-900 uppercase tracking-wider">Programação de Férias Confirmada pelo RH</span>
            </div>
            <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2.5 py-1 rounded-full">
              {{ ferias.solicitacao_atual.status }}
            </span>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-white p-4 rounded-2xl border border-emerald-100 text-xs">
            <div>
              <span class="text-slate-400 block text-[10px] uppercase font-bold">Início do Gozo</span>
              <strong class="text-slate-800 text-sm">{{ ferias.solicitacao_atual.data_inicio }}</strong>
            </div>
            <div>
              <span class="text-slate-400 block text-[10px] uppercase font-bold">Dias de Gozo</span>
              <strong class="text-slate-800 text-sm">{{ ferias.solicitacao_atual.dias }} Dias</strong>
            </div>
            <div>
              <span class="text-slate-400 block text-[10px] uppercase font-bold">Abono Pecuniário</span>
              <strong class="text-[#9c2337] text-sm">{{ ferias.solicitacao_atual.dias_abono }} Dias Vendidos</strong>
            </div>
            <div>
              <span class="text-slate-400 block text-[10px] uppercase font-bold">Retorno à Obra</span>
              <strong class="text-slate-800 text-sm">{{ ferias.solicitacao_atual.data_retorno }}</strong>
            </div>
          </div>

          <p class="text-[11px] text-slate-500">
            Registro Oficial: <strong class="font-mono text-slate-700">{{ ferias.solicitacao_atual.protocolo }}</strong> • A remuneração de férias com terço constitucional é paga em conta bancária até 2 dias antes do início do gozo (Art. 145 CLT).
          </p>
        </div>

        <!-- Se não houver programação agendada no momento -->
        <div v-else class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-start gap-3">
          <AppIcon name="calendar" size="22" class="text-slate-400 mt-0.5" />
          <div>
            <h4 class="text-xs font-bold text-slate-800">Nenhuma escala futura agendada no momento</h4>
            <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
              Quando a sua escala de férias for confirmada pelo setor de RH da Vila Boa Construções, os períodos e datas de retorno serão automaticamente exibidos aqui.
            </p>
          </div>
        </div>

        <!-- Histórico de Períodos Anteriores Gozados -->
        <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200 shadow-sm space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">Histórico de Férias Gozadas</h3>
            <span class="text-xs text-slate-400 font-medium">Registros Anteriores</span>
          </div>
          
          <div v-if="ferias?.historico && ferias.historico.length > 0" class="divide-y divide-slate-100 text-xs">
            <div 
              v-for="(hist, idx) in ferias.historico" 
              :key="idx" 
              class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2"
            >
              <div>
                <strong class="text-slate-800">{{ hist.data_inicio }} até {{ hist.data_fim }}</strong>
                <span class="text-slate-500 block text-[11px]">
                  Ref: {{ hist.periodo_aquisitivo }} • {{ hist.dias_gozados }} dias usufruídos ({{ hist.abono_pecuniario }})
                </span>
              </div>
              <div class="text-right">
                <span class="text-[10px] text-slate-400 block">Pago em {{ hist.recibo_pago_em }}:</span>
                <span class="font-bold text-emerald-700 font-mono text-sm">
                  R$ {{ hist.valor_liquido?.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}
                </span>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-6 text-slate-400 text-xs">
            Nenhum histórico de férias anterior registrado no sistema.
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import AppIcon from './AppIcon.vue';

defineProps({
  ferias: {
    type: Object,
    default: null
  }
});
</script>
