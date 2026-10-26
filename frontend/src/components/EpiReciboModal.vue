<template>
  <div v-if="entrega" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/70 backdrop-blur-sm transition-all">
    <div 
      class="bg-white w-full max-w-xl lg:max-w-3xl max-h-[95vh] sm:max-h-[92vh] rounded-t-3xl sm:rounded-2xl shadow-2xl flex flex-col overflow-hidden animate-slide-up border border-slate-200"
      @click.stop
    >
      <!-- Cabeçalho na Tela (Não sai na impressão) -->
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50 no-print">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-2xl bg-slate-100 text-slate-600 border border-slate-200 flex items-center justify-center font-bold text-lg">
            <AppIcon name="check" size="18" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-slate-900">Ficha de EPI Assinada (NR-06)</h3>
            <p class="text-xs text-slate-500">Documento Eletrônico Válido • Ref: {{ entrega.id }}</p>
          </div>
        </div>

        <button 
          @click="$emit('close')" 
          class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-all cursor-pointer active:scale-90 border border-slate-200 shadow-2xs"
        >
          <AppIcon name="x" size="14" />
        </button>
      </div>

      <!-- Área Oficial da Ficha (Área de Impressão A4) -->
      <div class="p-4 sm:p-6 overflow-y-auto space-y-4 text-xs printable-area">
        
        <!-- Cabeçalho Oficial da Ficha -->
        <div class="border-b-2 border-slate-800 pb-3 flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <img src="/img/logo-horizontal.png" alt="Vila Boa" class="h-8 w-auto object-contain" />
            <div class="border-l border-slate-300 pl-3">
              <h2 class="text-xs sm:text-sm font-semibold text-slate-900 uppercase">
                Ficha de Controle e Fornecimento de EPI
              </h2>
              <p class="text-[10px] text-slate-500">Atendimento à NR-06 do Ministério do Trabalho e Emprego</p>
            </div>
          </div>
          <div class="text-right">
            <span class="inline-block bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded">
              DOCUMENTO ASSINADO
            </span>
            <p class="text-[9px] text-slate-400 font-mono mt-0.5">ID: {{ entrega.id }}</p>
          </div>
        </div>

        <!-- Dados da Empresa e do Colaborador -->
        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 space-y-2 text-[11px]">
          <div class="grid grid-cols-2 gap-2 border-b border-slate-200 pb-2">
            <div>
              <span class="text-slate-400 block text-[9px] uppercase font-bold">Empregador:</span>
              <strong class="text-slate-800">Vila Boa Construcoes e Servicos Limitada</strong>
              <span class="text-slate-500 block text-[10px]">CNPJ: 02.785.261/0001-90 • São Bernardo do Campo - SP</span>
            </div>
            <div class="text-right">
              <span class="text-slate-400 block text-[9px] uppercase font-bold">Canteiro de Obras:</span>
              <strong class="text-slate-800">{{ entrega.canteiro_obra }}</strong>
              <span class="text-slate-500 block text-[10px]">Data Entrega: {{ entrega.data_entrega_formatada }}</span>
            </div>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1 text-[11px]">
            <div>
              <span class="text-slate-400 block text-[9px]">COLABORADOR:</span>
              <strong class="text-slate-800">{{ entrega.nome_colaborador }}</strong>
            </div>
            <div>
              <span class="text-slate-400 block text-[9px]">MATRÍCULA:</span>
              <strong class="text-slate-800">{{ entrega.matricula }}</strong>
            </div>
            <div>
              <span class="text-slate-400 block text-[9px]">CPF:</span>
              <strong class="font-mono text-slate-800">{{ entrega.cpf }}</strong>
            </div>
            <div>
              <span class="text-slate-400 block text-[9px]">FUNÇÃO / CARGO:</span>
              <strong class="text-slate-800">{{ entrega.cargo }}</strong>
            </div>
          </div>
        </div>

        <!-- Tabela de Itens de EPI -->
        <div>
          <h4 class="text-[11px] font-bold text-slate-800 uppercase tracking-wider mb-1.5">Equipamentos Entregues com CA</h4>
          <div class="border border-slate-300 rounded-xl overflow-hidden">
            <table class="w-full text-left text-[10px]">
              <thead class="bg-slate-100 border-b border-slate-300 font-bold uppercase text-slate-600">
                <tr>
                  <th class="p-2">Item</th>
                  <th class="p-2">Descrição do EPI</th>
                  <th class="p-2 text-center">Nº CA</th>
                  <th class="p-2">Fabricante</th>
                  <th class="p-2 text-center">Qtd</th>
                  <th class="p-2">Motivo</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200">
                <tr v-for="item in entrega.itens" :key="item.item" class="hover:bg-slate-50">
                  <td class="p-2 font-mono">{{ item.item }}</td>
                  <td class="p-2 font-semibold text-slate-800">{{ item.descricao }}</td>
                  <td class="p-2 text-center font-mono font-bold text-slate-700">CA {{ item.ca }}</td>
                  <td class="p-2 text-slate-500">{{ item.fabricante }}</td>
                  <td class="p-2 text-center font-bold">{{ item.quantidade }} {{ item.unidade }}</td>
                  <td class="p-2 text-slate-500">{{ item.motivo }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Termo de Ciência e Compromisso -->
        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
          <strong class="text-[10px] uppercase text-slate-700 block">Termo de Ciência e Responsabilidade (NR-06):</strong>
          <p class="text-[10px] text-slate-600 leading-relaxed text-justify">
            {{ entrega.termo_responsabilidade }}
          </p>
        </div>

        <!-- Bloco de Assinatura Digital e Chave Criptográfica -->
        <div class="border border-slate-300 rounded-xl p-3 bg-white grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
          <!-- Assinatura Gráfica -->
          <div class="border-b sm:border-b-0 sm:border-r border-slate-200 pb-3 sm:pb-0 sm:pr-3 text-center sm:text-left">
            <span class="text-[9px] uppercase font-bold text-slate-400 block mb-1">Assinatura Eletrônica do Colaborador:</span>
            <div class="h-20 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-center p-1">
              <img 
                v-if="entrega.assinatura?.imagem_base64" 
                :src="entrega.assinatura.imagem_base64" 
                alt="Assinatura" 
                class="max-h-full max-w-full object-contain"
              />
              <span v-else class="text-xs text-slate-400 font-mono italic">Assinatura Registrada Digitalmente</span>
            </div>
            <p class="text-[10px] font-bold text-slate-800 mt-1">{{ entrega.nome_colaborador }}</p>
            <p class="text-[9px] text-slate-400 font-mono">CPF: {{ entrega.cpf }}</p>
          </div>

          <!-- Metadados de Auditoria e Validade -->
          <div class="space-y-1.5 text-[10px]">
            <div class="flex items-center gap-1.5 text-emerald-700 font-bold">
              <AppIcon name="lock" size="12" />
              <span>Assinatura Digital Válida</span>
            </div>
            <div>
              <span class="text-slate-400 block text-[9px]">DATA E HORA DO REGISTRO:</span>
              <strong class="text-slate-800 font-mono">{{ entrega.assinatura?.data_hora }}</strong>
            </div>
            <div>
              <span class="text-slate-400 block text-[9px]">ENDEREÇO IP DO DISPOSITIVO:</span>
              <span class="font-mono text-slate-600">{{ entrega.assinatura?.ip }}</span>
            </div>
            <div>
              <span class="text-slate-400 block text-[9px]">CHAVE CRIPTOGRÁFICA DE AUTENTICIDADE (SHA-256):</span>
              <span class="font-mono text-[8px] text-slate-700 break-all bg-slate-100 p-1 rounded block">
                {{ entrega.assinatura?.hash_autenticidade }}
              </span>
            </div>
            <div class="text-[8px] text-slate-400 pt-1">
              {{ entrega.assinatura?.amparo_legal || 'Portaria MTP nº 672/2021 c/c Lei Federal 14.063/2020' }}
            </div>
          </div>
        </div>
      </div>

      <!-- Rodapé com Ações (Oculto na impressão) -->
      <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-2.5 no-print">
        <button 
          @click="imprimir" 
          class="flex-1 py-3 px-4 bg-[#9c2337] hover:bg-[#831b2d] active:scale-[0.98] text-white font-bold rounded-2xl text-xs sm:text-sm flex items-center justify-center gap-2 shadow-md transition cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          Salvar / Imprimir Ficha de EPI (PDF)
        </button>
        <button 
          @click="$emit('close')" 
          class="py-3 px-5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-2xl text-xs transition cursor-pointer"
        >
          Fechar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { usarFecharComEsc } from '../utils/fecharComEsc.js';
import AppIcon from './AppIcon.vue';

const props = defineProps({
  entrega: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close']);
usarFecharComEsc(() => emit('close'));

function imprimir() {
  window.print();
}
</script>

<style scoped>
@media print {
  body * {
    visibility: hidden;
  }
  .printable-area, .printable-area * {
    visibility: visible;
  }
  .printable-area {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    margin: 0;
    padding: 10mm;
  }
  .no-print {
    display: none !important;
  }
}
</style>
