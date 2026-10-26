<template>
  <div v-if="informe" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300">
    <div 
      class="bg-white w-full max-w-3xl lg:max-w-4xl max-h-[96vh] sm:max-h-[92vh] rounded-t-3xl sm:rounded-2xl shadow-2xl flex flex-col overflow-hidden animate-slide-up border border-slate-200"
      @click.stop
    >
      <!-- Cabeçalho do Modal -->
      <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
        <div class="flex items-center space-x-3">
          <img src="/img/logo-vilaboa-color.png" alt="Vila Boa" class="h-7 w-auto object-contain" />
          <div class="border-l border-slate-200 pl-3">
            <h3 class="text-xs sm:text-sm font-semibold text-slate-800">
              Informe de Rendimentos IRPF {{ informe.exercicio }}
            </h3>
            <p class="text-[10px] text-slate-500">
              Ano-Calendário {{ informe.ano_calendario }} • Modelo Oficial Receita Federal / eSocial
            </p>
          </div>
        </div>

        <button 
          @click="$emit('close')" 
          class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-all cursor-pointer active:scale-90 border border-slate-200 shadow-2xs"
          aria-label="Fechar"
        >
          <AppIcon name="x" size="14" />
        </button>
      </div>

      <!-- Corpo do Informe (Área Imprimível A4 Oficial) -->
      <div class="p-4 sm:p-6 overflow-y-auto space-y-4 text-xs printable-informe">
        
        <!-- Cabeçalho Oficial do Comprovante da Receita Federal -->
        <div class="border-2 border-slate-800 p-3 bg-white text-center space-y-1">
          <p class="text-[10px] font-bold tracking-wider text-slate-600 uppercase">
            Ministério da Fazenda • Secretaria Especial da Receita Federal do Brasil
          </p>
          <h2 class="text-xs sm:text-sm font-semibold uppercase text-slate-900 tracking-tight">
            Comprovante de Rendimentos Pagos e de Retenção de Imposto de Renda na Fonte
          </h2>
          <div class="inline-flex items-center gap-4 text-[11px] font-bold text-slate-800 bg-slate-100 px-3 py-1 rounded border border-slate-300">
            <span>Exercício de {{ informe.exercicio }}</span>
            <span>•</span>
            <span>Ano-Calendário de {{ informe.ano_calendario }}</span>
          </div>
          <p class="text-[9px] text-slate-500 pt-0.5">
            {{ informe.normativa }}
          </p>
        </div>

        <!-- QUADRO 1: FONTE PAGADORA PESSOA JURÍDICA -->
        <div class="border border-slate-400 rounded-lg overflow-hidden">
          <div class="bg-slate-200/80 px-3 py-1 font-bold text-[10px] uppercase text-slate-800 border-b border-slate-400">
            1. Fonte Pagadora Pessoa Jurídica
          </div>
          <div class="p-2.5 grid grid-cols-1 sm:grid-cols-12 gap-2 bg-white">
            <div class="sm:col-span-4">
              <span class="text-[9px] text-slate-400 uppercase block font-semibold">C.N.P.J.</span>
              <span class="font-bold text-slate-900 font-mono">{{ informe.fonte_pagadora.cnpj }}</span>
            </div>
            <div class="sm:col-span-8">
              <span class="text-[9px] text-slate-400 uppercase block font-semibold">Nome Empresarial</span>
              <span class="font-bold text-slate-900">{{ informe.fonte_pagadora.nome_empresarial }}</span>
            </div>
          </div>
        </div>

        <!-- QUADRO 2: PESSOA FÍSICA BENEFICIÁRIA DOS RENDIMENTOS -->
        <div class="border border-slate-400 rounded-lg overflow-hidden">
          <div class="bg-slate-200/80 px-3 py-1 font-bold text-[10px] uppercase text-slate-800 border-b border-slate-400">
            2. Pessoa Física Beneficiária dos Rendimentos
          </div>
          <div class="p-2.5 grid grid-cols-1 sm:grid-cols-12 gap-2 bg-white">
            <div class="sm:col-span-3">
              <span class="text-[9px] text-slate-400 uppercase block font-semibold">C.P.F.</span>
              <span class="font-bold text-slate-900 font-mono">{{ informe.beneficiario.cpf }}</span>
            </div>
            <div class="sm:col-span-6">
              <span class="text-[9px] text-slate-400 uppercase block font-semibold">Nome Completo</span>
              <span class="font-bold text-slate-900">{{ informe.beneficiario.nome_completo }}</span>
            </div>
            <div class="sm:col-span-3">
              <span class="text-[9px] text-slate-400 uppercase block font-semibold">Natureza do Rendimento</span>
              <span class="font-semibold text-slate-800">{{ informe.beneficiario.natureza_rendimento }}</span>
            </div>
          </div>
        </div>

        <!-- QUADRO 3: RENDIMENTOS TRIBUTÁVEIS, DEDUÇÕES E IRRF -->
        <div class="border border-slate-400 rounded-lg overflow-hidden">
          <div class="bg-slate-200/80 px-3 py-1 font-bold text-[10px] uppercase text-slate-800 border-b border-slate-400">
            3. Rendimentos Tributáveis, Deduções e Imposto Retido na Fonte (Valores em Reais)
          </div>
          <div class="divide-y divide-slate-200 bg-white">
            <div class="p-2 flex justify-between items-center hover:bg-slate-50">
              <span class="text-[11px] text-slate-700">01. Total dos rendimentos (inclusive férias)</span>
              <span class="font-mono font-bold text-slate-900">
                {{ formatCurrency(informe.rendimentos_tributaveis.total_rendimentos) }}
              </span>
            </div>
            <div class="p-2 flex justify-between items-center hover:bg-slate-50">
              <span class="text-[11px] text-slate-700">02. Contribuição previdenciária oficial</span>
              <span class="font-mono font-bold text-slate-900">
                {{ formatCurrency(informe.rendimentos_tributaveis.previdencia_oficial) }}
              </span>
            </div>
            <div class="p-2 flex justify-between items-center hover:bg-slate-50">
              <span class="text-[11px] text-slate-700">03. Contribuições a entidades de previdência complementar pública ou privada</span>
              <span class="font-mono text-slate-500">
                {{ formatCurrency(informe.rendimentos_tributaveis.previdencia_complementar) }}
              </span>
            </div>
            <div class="p-2 flex justify-between items-center hover:bg-slate-50">
              <span class="text-[11px] text-slate-700">04. Pensão alimentícia</span>
              <span class="font-mono text-slate-500">
                {{ formatCurrency(informe.rendimentos_tributaveis.pensao_alimenticia) }}
              </span>
            </div>
            <div class="p-2 flex justify-between items-center bg-rose-50/50">
              <span class="text-[11px] font-bold text-[#9c2337]">05. Imposto sobre a renda retido na fonte (IRRF)</span>
              <span class="font-mono font-semibold text-[#9c2337]">
                {{ formatCurrency(informe.rendimentos_tributaveis.imposto_retido_fonte) }}
              </span>
            </div>
          </div>
        </div>

        <!-- QUADRO 4: RENDIMENTOS ISENTOS E NÃO TRIBUTÁVEIS -->
        <div class="border border-slate-400 rounded-lg overflow-hidden">
          <div class="bg-slate-200/80 px-3 py-1 font-bold text-[10px] uppercase text-slate-800 border-b border-slate-400">
            4. Rendimentos Isentos e Não Tributáveis (Valores em Reais)
          </div>
          <div class="divide-y divide-slate-200 bg-white">
            <div class="p-2 flex justify-between items-center hover:bg-slate-50">
              <span class="text-[11px] text-slate-700">01. Parcela isenta de aposentadoria/pensão (65 anos ou mais)</span>
              <span class="font-mono text-slate-500">{{ formatCurrency(informe.rendimentos_isentos.parcela_isenta_aposentadoria) }}</span>
            </div>
            <div class="p-2 flex justify-between items-center hover:bg-slate-50">
              <span class="text-[11px] text-slate-700">02. Diárias e ajudas de custo</span>
              <span class="font-mono font-bold text-slate-900">{{ formatCurrency(informe.rendimentos_isentos.diarias_ajuda_custo) }}</span>
            </div>
            <div class="p-2 flex justify-between items-center hover:bg-slate-50">
              <span class="text-[11px] text-slate-700">03. Indenizações por rescisão de contrato de trabalho / FGTS</span>
              <span class="font-mono text-slate-500">{{ formatCurrency(informe.rendimentos_isentos.indenizacoes_rescisao_fgts) }}</span>
            </div>
            <div class="p-2 flex justify-between items-center hover:bg-slate-50">
              <div>
                <span class="text-[11px] text-slate-700 block">07. Outros</span>
                <span class="text-[9px] text-slate-400">({{ informe.rendimentos_isentos.descricao_outros }})</span>
              </div>
              <span class="font-mono font-bold text-slate-900">{{ formatCurrency(informe.rendimentos_isentos.outros_isentos) }}</span>
            </div>
          </div>
        </div>

        <!-- QUADRO 5: RENDIMENTOS SUJEITOS A TRIBUTAÇÃO EXCLUSIVA -->
        <div class="border border-slate-400 rounded-lg overflow-hidden">
          <div class="bg-slate-200/80 px-3 py-1 font-bold text-[10px] uppercase text-slate-800 border-b border-slate-400">
            5. Rendimentos Sujeitos a Tributação Exclusiva (Rendimento Líquido)
          </div>
          <div class="divide-y divide-slate-200 bg-white">
            <div class="p-2 flex justify-between items-center hover:bg-slate-50">
              <span class="text-[11px] text-slate-700">01. Décimo terceiro salário (Líquido)</span>
              <span class="font-mono font-bold text-slate-900">{{ formatCurrency(informe.tributacao_exclusiva.decimo_terceiro_salario) }}</span>
            </div>
            <div class="p-2 flex justify-between items-center hover:bg-slate-50">
              <span class="text-[11px] text-slate-700">02. Imposto sobre a renda retido na fonte sobre o 13º salário</span>
              <span class="font-mono font-bold text-slate-900">{{ formatCurrency(informe.tributacao_exclusiva.imposto_retido_decimo_terceiro) }}</span>
            </div>
          </div>
        </div>

        <!-- QUADRO 7: INFORMAÇÕES COMPLEMENTARES -->
        <div class="border border-slate-400 rounded-lg overflow-hidden">
          <div class="bg-slate-200/80 px-3 py-1 font-bold text-[10px] uppercase text-slate-800 border-b border-slate-400">
            7. Informações Complementares
          </div>
          <div class="p-3 bg-white space-y-2 text-[11px] text-slate-700">
            <div class="bg-slate-50 p-2.5 rounded border border-slate-200">
              <span class="font-bold text-slate-800 block text-[10px] uppercase">Plano Privado de Assistência à Saúde (Coletivo Empresarial)</span>
              <div class="flex justify-between items-center mt-1">
                <span>Operadora: {{ informe.informacoes_complementares.plano_saude.operadora }} (CNPJ: {{ informe.informacoes_complementares.plano_saude.cnpj }})</span>
                <span class="font-mono font-bold text-slate-900">Total Descontado: {{ formatCurrency(informe.informacoes_complementares.plano_saude.total_descontado) }}</span>
              </div>
            </div>
            <p class="text-[10px] text-slate-500 italic">
              {{ informe.informacoes_complementares.observacao }}
            </p>
          </div>
        </div>

        <!-- QUADRO 8: RESPONSÁVEL PELAS INFORMAÇÕES & AUTENTICAÇÃO -->
        <div class="border border-slate-400 rounded-lg p-3 bg-slate-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 text-[10px]">
          <div>
            <span class="text-slate-400 uppercase font-semibold block">Responsável pelas Informações:</span>
            <span class="font-bold text-slate-800">{{ informe.fonte_pagadora.responsavel_dp }}</span>
          </div>
          <div class="text-left sm:text-right">
            <span class="text-slate-400 uppercase font-semibold block">Emissão e Assinatura Eletrônica:</span>
            <span class="font-bold text-slate-800">{{ informe.data_emissao }} às {{ informe.hora_emissao }}</span>
          </div>
        </div>

        <!-- Chave Criptográfica SHA-256 da Receita Federal -->
        <div class="text-[9px] text-slate-400 font-mono text-center break-all pt-1">
          Código de Autenticidade Digital DIRF / eSocial: {{ informe.chave_autenticacao }}
        </div>
      </div>

      <!-- Barra de Ações Inferior -->
      <div class="p-3 bg-slate-50 border-t border-slate-200 flex flex-wrap gap-2">
        <button 
          @click="imprimirInforme" 
          class="flex-1 py-3 px-4 bg-[#9c2337] hover:bg-[#831b2d] active:scale-[0.98] text-white font-bold rounded-xl text-xs sm:text-sm flex items-center justify-center gap-2 shadow-sm transition cursor-pointer"
        >
          <AppIcon name="printer" size="16" />
          <span>Salvar / Imprimir Informe IRPF (PDF)</span>
        </button>

        <button 
          @click="$emit('close')" 
          class="h-12 px-5 bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-700 font-bold rounded-xl text-xs sm:text-sm border border-slate-200 shadow-2xs transition-all cursor-pointer shrink-0 active:scale-[0.98] flex items-center justify-center"
        >
          Fechar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { usarFecharComEsc } from '../utils/fecharComEsc.js';
import { formatCurrency } from '../utils/masks.js';
import AppIcon from './AppIcon.vue';

defineProps({
  informe: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close']);
usarFecharComEsc(() => emit('close'));

function imprimirInforme() {
  window.print();
}
</script>

<style scoped>
@keyframes slideUp {
  from {
    transform: translateY(100%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.animate-slide-up {
  animation: slideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

@media print {
  body * {
    visibility: hidden;
  }
  .printable-informe, .printable-informe * {
    visibility: visible;
  }
  .printable-informe {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    margin: 0;
    padding: 8mm;
  }
}
</style>
