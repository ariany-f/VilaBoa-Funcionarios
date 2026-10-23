<template>
  <div v-if="holerite" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300">
    <div 
      class="bg-white w-full max-w-xl md:max-w-2xl lg:max-w-3xl max-h-[94vh] sm:max-h-[90vh] rounded-t-3xl sm:rounded-3xl shadow-2xl flex flex-col overflow-hidden animate-slide-up border border-slate-200/80"
      @click.stop
    >
      <!-- Drag Handle para Mobile (Estilo Bottom Sheet Nativo) -->
      <div class="sm:hidden pt-2.5 pb-1 flex justify-center bg-slate-50 cursor-grab shrink-0">
        <div class="w-10 h-1 bg-slate-300 rounded-full"></div>
      </div>

      <!-- Cabeçalho do Modal / Handle para Mobile -->
      <div class="px-5 pt-2 sm:pt-3 pb-3 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
        <div class="flex items-center space-x-3">
          <img src="/img/logo-vilaboa-color.png" alt="Vila Boa" class="h-7 w-auto object-contain" />
          <div class="border-l border-slate-200 pl-3">
            <h3 class="text-xs font-bold text-slate-800">Demonstrativo de Pagamento</h3>
            <p class="text-[10px] text-slate-500">Ref: {{ holerite.competencia }} • {{ holerite.tipo_folha }}</p>
          </div>
        </div>

        <button 
          @click="$emit('close')" 
          class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-all cursor-pointer active:scale-90 border border-slate-200/80 shadow-2xs"
          aria-label="Fechar"
        >
          <AppIcon name="x" size="14" />
        </button>
      </div>

      <!-- Corpo com rolagem do Holerite (Área imprimível) -->
      <div class="p-4 sm:p-5 overflow-y-auto space-y-4 text-xs sm:text-sm printable-area">
                <!-- GRADE DE 2 COLUNAS NO TOPO: VALOR LÍQUIDO + CONFIRMAÇÃO DE CIÊNCIA CLT -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 items-stretch">
          
          <!-- Coluna 1: Valor Líquido do Demonstrativo (Estilo Fintech Executiva) -->
          <div class="bg-slate-50 p-4 sm:p-5 rounded-2xl border border-slate-200/90 flex flex-col justify-between space-y-3 shadow-2xs">
            <div>
              <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-black text-slate-400 tracking-wider">Valor Líquido do Demonstrativo</span>
                <span class="text-[10px] font-bold text-slate-600 bg-white border border-slate-200 px-2 py-0.5 rounded-full">
                  {{ holerite.competencia }}
                </span>
              </div>
              <div class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 font-mono tracking-tight mt-1.5">
                {{ formatCurrency(holerite.salario_liquido) }}
              </div>
            </div>

            <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-[11px] text-slate-500 font-medium">
              <span>Creditado em: <strong class="text-slate-800">{{ holerite.data_pagamento }}</strong></span>
              <span class="font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200/80 text-[10px]">
                ✓ Art. 464 CLT
              </span>
            </div>
          </div>

          <!-- Coluna 2: Confirmação de Ciência e Recebimento CLT (Pendente ou Confirmada) -->
          <!-- CASO PENDENTE: Ação Imediata para Assinar ao Lado do Valor Líquido -->
          <div 
            v-if="!holerite.aceite?.confirmado" 
            id="secao-ciencia"
            class="bg-amber-50/80 border-2 border-amber-300/90 rounded-2xl p-4 flex flex-col justify-between space-y-2.5 shadow-2xs transition-all duration-300"
          >
            <div class="space-y-1.5">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                  <div class="w-5 h-5 rounded-full bg-amber-500 text-white flex items-center justify-center shrink-0">
                    <AppIcon name="shield-check" size="12" />
                  </div>
                  <span class="text-[10px] uppercase font-black text-amber-950 tracking-wider">Ação Requerida</span>
                </div>
                <span class="text-[9px] font-black uppercase text-amber-950 bg-amber-200/90 border border-amber-400 px-2 py-0.5 rounded-full">
                  Pendente CLT
                </span>
              </div>

              <h4 class="text-xs sm:text-sm font-black text-amber-950 leading-tight">
                Confirmação de Ciência e Recebimento
              </h4>
              <p class="text-[11px] text-amber-900/90 leading-snug">
                Confirme o recebimento deste holerite para registrar a ciência oficial perante o DP e eSocial.
              </p>

              <div v-if="erroCiencia" class="p-1.5 rounded-lg bg-rose-100 border border-rose-300 text-rose-800 text-[10px] font-medium">
                {{ erroCiencia }}
              </div>
            </div>

            <button 
              type="button"
              @click="confirmarCiencia"
              :disabled="enviandoCiencia"
              class="w-full h-11 px-4 bg-emerald-700 hover:bg-emerald-800 active:scale-[0.98] disabled:opacity-50 text-white font-bold rounded-xl text-xs sm:text-sm flex items-center justify-center gap-2 shadow-xs transition cursor-pointer shrink-0"
            >
              <span v-if="enviandoCiencia" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <AppIcon v-else name="check" size="15" />
              <span>{{ enviandoCiencia ? 'Registrando...' : 'Confirmar Ciência do Holerite' }}</span>
            </button>
          </div>

          <!-- CASO JÁ CONFIRMADO: Selo de Validade Digital CLT -->
          <div 
            v-else 
            class="bg-emerald-50/80 border border-emerald-300/80 rounded-2xl p-4 flex flex-col justify-between space-y-2.5 shadow-2xs text-emerald-950"
          >
            <div class="space-y-1">
              <div class="flex items-center justify-between border-b border-emerald-200/60 pb-1.5">
                <div class="flex items-center gap-1.5">
                  <div class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0">
                    <AppIcon name="check" size="12" />
                  </div>
                  <span class="text-[10px] uppercase font-black text-emerald-900 tracking-wider">Ciência Registrada</span>
                </div>
                <span class="text-[9px] font-bold text-emerald-900 bg-emerald-200/80 px-2 py-0.5 rounded-full uppercase">
                  Art. 464 CLT
                </span>
              </div>

              <p class="text-[11px] text-emerald-900 leading-snug pt-0.5 italic">
                "{{ holerite.aceite.termo || 'Ciência e recebimento integral confirmados pelo colaborador.' }}"
              </p>
            </div>

            <div class="pt-1.5 border-t border-emerald-200/60 text-[10px] text-emerald-800 flex items-center justify-between font-mono">
              <span>Data: <strong>{{ holerite.aceite.data_hora }}</strong></span>
              <span class="truncate ml-2" :title="holerite.aceite.hash_comprovante">Hash: {{ holerite.aceite.hash_comprovante?.substring(0, 12) }}...</span>
            </div>
          </div>
        </div>

        <!-- Dados da Empresa e Colaborador -->
        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80 space-y-2.5">
          <div class="flex justify-between items-start border-b border-slate-200 pb-2">
            <div>
              <p class="font-bold text-slate-800 uppercase tracking-tight">Vila Boa Construcoes e Servicos Limitada</p>
              <p class="text-[11px] text-slate-500">CNPJ: 02.785.261/0001-90 • São Bernardo do Campo - SP</p>
            </div>
            <div class="text-right">
              <span 
                class="inline-block text-[10px] font-bold px-2.5 py-0.5 rounded-full border"
                :class="badgeTipoFolhaClasse"
              >
                {{ holerite.tipo_folha || 'Folha Mensal' }}
              </span>
              <p class="text-[10px] text-slate-400 mt-0.5">Pagto: {{ holerite.data_pagamento }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2 text-[11px]">
            <div>
              <span class="text-slate-400 block text-[10px]">COLABORADOR:</span>
              <span class="font-semibold text-slate-800 truncate block">{{ colaborador.nome }}</span>
              <span class="text-slate-500">Matrícula: {{ colaborador.matricula }}</span>
            </div>
            <div class="text-right">
              <span class="text-slate-400 block text-[10px]">CARGO / DEPTO:</span>
              <span class="font-semibold text-slate-800 truncate block">{{ colaborador.cargo }}</span>
              <span class="text-slate-500">{{ colaborador.departamento }}</span>
            </div>
          </div>
        </div>

        <!-- Barra Didática "Entenda seu Holerite" (Apenas na tela) -->
        <div class="flex items-center justify-between text-[11px] text-slate-600 bg-slate-50 px-3 py-2 rounded-xl border border-slate-200/80 shadow-xs no-print">
          <span class="flex items-center gap-1.5 font-medium">
            <AppIcon name="info" size="14" class="text-[#ba2e45] shrink-0" />
            <span><strong>Entenda seu Holerite:</strong> Toque em qualquer linha, desconto ou no FGTS para ver a explicação em linguagem simples.</span>
          </span>
        </div>

        <!-- Tabela de Itens (Proventos e Descontos) -->
        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
          <div class="bg-slate-100/80 px-3 py-2 grid grid-cols-12 text-[10px] font-bold text-slate-600 uppercase border-b border-slate-200">
            <span class="col-span-2">Cód.</span>
            <span class="col-span-5">Descrição</span>
            <span class="col-span-2 text-right">Ref.</span>
            <span class="col-span-3 text-right">Valor</span>
          </div>

          <div class="divide-y divide-slate-100 bg-white">
            <div 
              v-for="item in holerite.itens" 
              :key="item.codigo" 
              @click="explicarItem(item)"
              class="px-3 py-2 grid grid-cols-12 text-[11px] items-center hover:bg-rose-50/40 active:bg-rose-50 transition cursor-pointer group"
              title="Toque para ver a explicação desta rubrica"
            >
              <span class="col-span-2 font-mono text-slate-400 text-[10px]">{{ item.codigo }}</span>
              <span class="col-span-5 font-medium text-slate-800 leading-tight flex items-center gap-1">
                <span>{{ item.descricao }}</span>
                <span class="text-slate-300 group-hover:text-[#ba2e45] transition no-print">
                  <AppIcon name="info" size="11" />
                </span>
              </span>
              <span class="col-span-2 text-right text-slate-500 text-[10px]">{{ item.referencia }}</span>
              <span 
                class="col-span-3 text-right font-semibold font-mono"
                :class="item.tipo === 'PROVENTO' ? 'text-emerald-600' : 'text-rose-500'"
              >
                {{ item.tipo === 'DESCONTO' ? '-' : '+' }} {{ formatCurrency(item.valor) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Resumo Financeiro Líquido (Interativo com Toque Didático) -->
        <div class="grid grid-cols-3 gap-2 bg-slate-50 p-3 rounded-xl border border-slate-200 text-center">
          <div 
            @click="explicarChave('TOTAL_PROVENTOS')" 
            class="border-r border-slate-200 pr-2 hover:bg-emerald-50/50 rounded-lg p-1 transition cursor-pointer group"
            title="Toque para entender o Total de Proventos"
          >
            <span class="text-[10px] text-slate-500 uppercase block font-medium group-hover:text-emerald-700">Total Proventos</span>
            <span class="text-xs sm:text-sm font-bold text-emerald-600 font-mono">
              {{ formatCurrency(holerite.total_proventos) }}
            </span>
          </div>
          <div 
            @click="explicarChave('TOTAL_DESCONTOS')" 
            class="border-r border-slate-200 pr-2 hover:bg-rose-50/50 rounded-lg p-1 transition cursor-pointer group"
            title="Toque para entender o Total de Descontos"
          >
            <span class="text-[10px] text-slate-500 uppercase block font-medium group-hover:text-rose-700">Total Descontos</span>
            <span class="text-xs sm:text-sm font-bold text-rose-500 font-mono">
              {{ formatCurrency(holerite.total_descontos) }}
            </span>
          </div>
          <div 
            @click="explicarChave('SALARIO_LIQUIDO')" 
            class="hover:bg-slate-100 rounded-lg p-1 transition cursor-pointer group"
            title="Toque para entender o Salário Líquido"
          >
            <span class="text-[10px] text-slate-700 uppercase block font-bold group-hover:text-slate-900">Valor Líquido</span>
            <span class="text-xs sm:text-sm font-black text-slate-900 font-mono">
              {{ formatCurrency(holerite.salario_liquido) }}
            </span>
          </div>
        </div>

        <!-- Bases de Cálculo e Informações Legais (Interativas com Toque Didático) -->
        <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-2 text-[10px] text-slate-600">
          <div 
            @click="explicarChave('001')" 
            class="hover:bg-slate-100 p-1 rounded-lg transition cursor-pointer"
            title="Toque para entender o Salário Base"
          >
            <span class="text-slate-400 block">Salário Base:</span>
            <span class="font-semibold text-slate-800 font-mono">{{ formatCurrency(holerite.salario_base) }}</span>
          </div>
          <div 
            @click="explicarChave('BASE_INSS')" 
            class="hover:bg-slate-100 p-1 rounded-lg transition cursor-pointer"
            title="Toque para entender a Base INSS"
          >
            <span class="text-slate-400 block">Base INSS:</span>
            <span class="font-semibold text-slate-800 font-mono">{{ formatCurrency(holerite.base_inss) }}</span>
          </div>
          <div 
            @click="explicarChave('BASE_FGTS')" 
            class="hover:bg-slate-100 p-1 rounded-lg transition cursor-pointer"
            title="Toque para entender a Base FGTS"
          >
            <span class="text-slate-400 block">Base FGTS:</span>
            <span class="font-semibold text-slate-800 font-mono">{{ formatCurrency(holerite.base_fgts) }}</span>
          </div>
          <div 
            @click="explicarChave('FGTS_MES')" 
            class="bg-white hover:bg-slate-100 p-1 rounded-lg transition cursor-pointer border border-slate-200 shadow-2xs"
            title="Toque para entender o FGTS depositado pela Vila Boa"
          >
            <span class="text-slate-500 font-bold block">FGTS do Mês:</span>
            <span class="font-black text-slate-900 font-mono">{{ formatCurrency(holerite.fgts_mes) }}</span>
          </div>
        </div>

        <!-- Bloco Institucional de Validação por QR Code (Autenticidade para Bancos / Terceiros) -->
        <div class="p-3 bg-slate-50 border border-slate-200 rounded-2xl flex items-center gap-3">
          <div v-if="qrCodeDataUrl" class="shrink-0 bg-white p-1 rounded-xl border border-slate-200 shadow-2xs">
            <img :src="qrCodeDataUrl" alt="QR Code de Autenticidade" class="w-16 h-16 object-contain" />
          </div>
          <div class="space-y-0.5 text-[10px] text-slate-500 min-w-0">
            <div class="flex items-center gap-1.5 font-bold text-slate-800 uppercase tracking-tight">
              <AppIcon name="shield-check" size="13" class="text-emerald-700 shrink-0" />
              <span>Validação Oficial via QR Code</span>
            </div>
            <p class="text-slate-600 text-[10px] leading-tight">
              Aponte a câmera do celular para este QR Code para verificar a autenticidade deste documento diretamente no portal oficial da Vila Boa Construções.
            </p>
            <div class="font-mono text-[9px] text-slate-400 break-all pt-0.5">
              Chave: {{ holerite.autenticacao_digital }}
            </div>
          </div>
        </div>
      </div>

      <!-- Barra de Ações Fixa Inferior com Safe Area Padding -->
      <div class="p-3.5 pb-[calc(0.875rem+env(safe-area-inset-bottom,0px))] bg-slate-50 border-t border-slate-200 flex flex-wrap sm:flex-nowrap gap-2.5 shrink-0 no-print">
        <button 
          @click="imprimir" 
          class="flex-1 h-12 px-5 bg-[#ba2e45] hover:bg-[#9c2337] active:scale-[0.98] text-white font-bold rounded-xl text-xs sm:text-sm flex items-center justify-center gap-2 shadow-sm transition cursor-pointer"
        >
          <AppIcon name="printer" size="16" />
          <span>Salvar / Imprimir PDF</span>
        </button>

        <button 
          type="button"
          @click="compartilharNoWhatsapp" 
          class="h-12 px-4 bg-emerald-700 hover:bg-emerald-800 active:scale-[0.98] text-white font-bold rounded-xl text-xs flex items-center justify-center gap-2 transition cursor-pointer shrink-0 shadow-2xs"
          title="Compartilhar demonstrativo via WhatsApp"
        >
          <AppIcon name="share" size="16" />
          <span class="hidden sm:inline">WhatsApp</span>
        </button>
        <button 
          @click="$emit('close')" 
          class="h-12 px-5 bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-700 font-bold rounded-xl text-xs sm:text-sm border border-slate-200 shadow-2xs transition-all cursor-pointer shrink-0 active:scale-[0.98] flex items-center justify-center"
        >
          Fechar
        </button>
      </div>
    </div>

    <!-- BALÃO EXPLICATIVO DIDÁTICO ("ENTENDA SEU HOLERITE") -->
    <div 
      v-if="rubricaSelecionada" 
      class="fixed inset-0 z-60 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs no-print transition-all"
      @click="rubricaSelecionada = null"
    >
      <div 
        class="bg-white rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl border border-slate-200 space-y-4 animate-scale-in"
        @click.stop
      >
        <div class="flex items-start justify-between border-b border-slate-100 pb-3">
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <span 
                class="text-[9px] font-bold px-2 py-0.5 rounded-full uppercase"
                :class="getRubricaBadgeClasse(rubricaSelecionada.tipo)"
              >
                {{ getRubricaBadgeTexto(rubricaSelecionada.tipo) }}
              </span>
              <span class="text-[10px] text-slate-400 font-medium">Tradutor da Folha</span>
            </div>
            <h4 class="text-sm sm:text-base font-black text-slate-900">
              {{ rubricaSelecionada.titulo }}
            </h4>
            <p class="text-[11px] text-[#ba2e45] font-semibold">
              {{ rubricaSelecionada.subtitulo }}
            </p>
          </div>

          <button 
            type="button"
            @click="rubricaSelecionada = null" 
            class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition cursor-pointer shrink-0"
            aria-label="Fechar explicação"
          >
            <AppIcon name="x" size="14" />
          </button>
        </div>

        <!-- Conteúdo Didático em Linguagem Simples -->
        <div class="space-y-3 text-xs text-slate-600">
          <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80 leading-relaxed">
            <strong class="text-slate-800 block text-[11px] uppercase mb-1">O que significa:</strong>
            {{ rubricaSelecionada.descricao }}
          </div>

          <div class="bg-rose-50/60 p-3.5 rounded-2xl border border-rose-200/60 leading-relaxed text-slate-700">
            <strong class="text-[#ba2e45] block text-[11px] uppercase mb-1">Como funciona na prática:</strong>
            {{ rubricaSelecionada.comoFunciona }}
          </div>
        </div>

        <!-- Botão Entendido -->
        <button 
          type="button"
          @click="rubricaSelecionada = null"
          class="w-full py-3 px-4 bg-[#ba2e45] hover:bg-[#9c2337] active:scale-[0.98] text-white font-bold rounded-xl text-xs flex items-center justify-center gap-2 shadow-sm transition cursor-pointer"
        >
          <span>Entendi, obrigado!</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { formatCurrency } from '../utils/masks.js';
import { obterExplicacaoRubrica, GLOSSARIO_RUBRICAS } from '../utils/rubricas.js';
import AppIcon from './AppIcon.vue';
import QRCode from 'qrcode';

const props = defineProps({
  holerite: {
    type: Object,
    default: null
  },
  colaborador: {
    type: Object,
    required: true
  },
  focarCiencia: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'ciencia-confirmada']);

// QR Code Oficial de Validação para Bancos e Terceiros
const qrCodeDataUrl = ref('');

watch(
  () => props.holerite,
  async (h) => {
    if (h?.id && h?.autenticacao_digital) {
      try {
        const origin = typeof window !== 'undefined' ? window.location.origin : 'https://vilaboa.com.br';
        const urlValidacao = `${origin}/#/validar?id=${encodeURIComponent(h.id)}&auth=${encodeURIComponent(h.autenticacao_digital)}`;
        qrCodeDataUrl.value = await QRCode.toDataURL(urlValidacao, {
          width: 140,
          margin: 1,
          color: {
            dark: '#0f1117',
            light: '#ffffff'
          }
        });
      } catch (e) {
        console.error('Falha ao gerar QR Code de autenticidade:', e);
      }
    }
  },
  { immediate: true }
);

onMounted(() => {
  if (props.focarCiencia && !props.holerite?.aceite?.confirmado) {
    setTimeout(() => {
      const el = document.getElementById('secao-ciencia');
      if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }, 180);
  }
});

// Tradutor Didático ("Entenda seu Holerite")
const rubricaSelecionada = ref(null);

function explicarItem(item) {
  rubricaSelecionada.value = obterExplicacaoRubrica(item.codigo, item.descricao);
}

function explicarChave(chave) {
  if (GLOSSARIO_RUBRICAS[chave]) {
    rubricaSelecionada.value = GLOSSARIO_RUBRICAS[chave];
  } else {
    rubricaSelecionada.value = obterExplicacaoRubrica(chave, '');
  }
}

function getRubricaBadgeClasse(tipo) {
  switch (tipo) {
    case 'PROVENTO':
      return 'bg-emerald-100 text-emerald-900 border border-emerald-300';
    case 'DESCONTO':
      return 'bg-rose-100 text-rose-900 border border-rose-300';
    case 'BENEFICIO_EMPRESA':
      return 'bg-amber-100 text-amber-950 border border-amber-300';
    case 'VALOR_FINAL':
      return 'bg-rose-50 text-[#ba2e45] border border-rose-200/80';
    default:
      return 'bg-slate-100 text-slate-800 border border-slate-300';
  }
}

function getRubricaBadgeTexto(tipo) {
  switch (tipo) {
    case 'PROVENTO':
      return 'Provento (Ganho)';
    case 'DESCONTO':
      return 'Desconto em Folha';
    case 'BENEFICIO_EMPRESA':
      return 'Pago pela Empresa (Não Desconta)';
    case 'VALOR_FINAL':
      return 'Valor Líquido na Conta';
    default:
      return 'Informativo Legal';
  }
}

const badgeTipoFolhaClasse = computed(() => {
  const codigo = props.holerite?.tipo_folha_codigo || '';
  const tipo = (props.holerite?.tipo_folha || '').toLowerCase();

  if (codigo === 'adiantamento' || tipo.includes('adiantamento') || tipo.includes('vale')) {
    return 'bg-amber-100 text-amber-900 border-amber-300';
  }
  if (codigo === '13_salario' || tipo.includes('13')) {
    return 'bg-rose-50 text-[#ba2e45] border border-rose-200/80';
  }
  if (codigo === 'ferias' || tipo.includes('férias') || tipo.includes('ferias')) {
    return 'bg-emerald-100 text-emerald-900 border-emerald-300';
  }
  if (codigo === 'plr' || tipo.includes('plr')) {
    return 'bg-emerald-100 text-emerald-900 border-emerald-300';
  }
  return 'bg-slate-100 text-slate-800 border-slate-300';
});

const enviandoCiencia = ref(false);
const erroCiencia = ref('');

async function confirmarCiencia() {
  if (!props.holerite?.id || enviandoCiencia.value) return;

  enviandoCiencia.value = true;
  erroCiencia.value = '';

  try {
    const token = localStorage.getItem('vb_auth_token') || '';
    const res = await fetch('/api/holerites/aceitar', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        holerite_id: props.holerite.id
      })
    });

    const data = await res.json();
    if (!res.ok) {
      erroCiencia.value = data.erro || 'Não foi possível registrar a confirmação de ciência.';
      return;
    }

    emit('ciencia-confirmada', data.holerite);
  } catch (err) {
    erroCiencia.value = 'Falha de comunicação ao registrar ciência com o servidor.';
  } finally {
    enviandoCiencia.value = false;
  }
}

function compartilharNoWhatsapp() {
  if (!props.holerite) return;
  const h = props.holerite;
  const origin = typeof window !== 'undefined' ? window.location.origin : 'https://vilaboa.com.br';
  const url = `${origin}/#/validar?id=${encodeURIComponent(h.id)}&auth=${encodeURIComponent(h.autenticacao_digital || '')}`;
  const texto = encodeURIComponent(
    `*Demonstrativo de Pagamento - Vila Boa Construções*\n` +
    `Colaborador: ${props.colaborador?.nome || ''} (Matrícula: ${props.colaborador?.matricula || ''})\n` +
    `Competência: ${h.competencia} (${h.tipo_folha || 'Folha Mensal'})\n` +
    `Salário Líquido: ${formatCurrency(h.salario_liquido)}\n` +
    `Data Pagto: ${h.data_pagamento}\n` +
    `Autenticação eSocial / Art. 464 CLT: ${h.autenticacao_digital || ''}\n` +
    `Validar documento: ${url}`
  );
  window.open(`https://api.whatsapp.com/send?text=${texto}`, '_blank');
}

function imprimir() {
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

.animate-slide-up {
  animation: slideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.animate-scale-in {
  animation: scaleIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

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
  }
  .no-print {
    display: none !important;
  }
}
</style>
