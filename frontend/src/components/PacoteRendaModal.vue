<template>
  <div v-if="pacote" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300">
    <div 
      class="bg-white w-full max-w-2xl lg:max-w-3xl max-h-[96vh] sm:max-h-[92vh] rounded-t-3xl sm:rounded-2xl shadow-2xl flex flex-col overflow-hidden animate-slide-up border border-slate-200"
      @click.stop
    >
      <!-- Drag Handle para Mobile (Estilo Bottom Sheet Nativo) -->
      <div class="sm:hidden pt-2.5 pb-1 flex justify-center bg-slate-50 cursor-grab shrink-0">
        <div class="w-10 h-1 bg-slate-300 rounded-full"></div>
      </div>

      <!-- Cabeçalho do Modal -->
      <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between bg-slate-50 shrink-0">
        <div class="flex items-center space-x-3">
          <img src="/img/logo-vilaboa-color.png" alt="Vila Boa" class="h-7 w-auto object-contain" />
          <div class="border-l border-slate-200 pl-3">
            <h3 class="text-xs sm:text-sm font-semibold text-slate-800">
              Dossiê Consolidado dos Últimos 3 Meses
            </h3>
            <p class="text-[10px] text-slate-500">
              Período: {{ pacote.resumo_financeiro.periodo_competencias }} • Financiamento & Bancos
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

      <!-- Corpo rolável do dossiê (Área Unificada para Impressão e PDF) -->
      <div class="p-4 sm:p-6 overflow-y-auto space-y-6 text-xs sm:text-sm printable-pacote">
        
        <!-- ======================================================== -->
        <!-- SEÇÃO 1: FOLHA DE ROSTO E DECLARAÇÃO CONSOLIDADA DE RENDA -->
        <!-- ======================================================== -->
        <div class="space-y-4 pb-4 border-b-2 border-dashed border-slate-200 page-break">
          
          <!-- Banner Superior Corporativo (Sólido) -->
          <div class="bg-[#141414] text-white p-4 sm:p-5 rounded-2xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
              <span class="text-[10px] uppercase font-bold tracking-wider text-rose-200 block">
                Documento Oficial de Comprovação de Rendimentos
              </span>
              <h2 class="text-base sm:text-lg font-semibold text-white">
                Dossiê Consolidado dos Últimos 3 Meses
              </h2>
              <p class="text-xs text-slate-300 mt-0.5">
                Válido para Caixa (Minha Casa Minha Vida / SBPE), Bancos e Locações
              </p>
            </div>

            <div class="text-left sm:text-right bg-white/10 px-3 py-2 rounded-xl border border-white/10 shrink-0">
              <span class="text-[9px] uppercase tracking-wider block text-slate-300">Emissão Oficial</span>
              <span class="text-xs font-bold text-white">{{ pacote.emissao.data }} às {{ pacote.emissao.hora }}</span>
            </div>
          </div>

          <!-- Dados da Empresa e do Colaborador -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-50 p-4 rounded-2xl border border-slate-200">
            <div class="space-y-1">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Empregadora:</span>
              <p class="font-bold text-slate-800 text-xs">{{ pacote.empresa.razao_social }}</p>
              <p class="text-[11px] text-slate-600">CNPJ: {{ pacote.empresa.cnpj }}</p>
              <p class="text-[11px] text-slate-500">{{ pacote.empresa.endereco }} • DP: {{ pacote.empresa.contato_rh }}</p>
            </div>

            <div class="space-y-1 sm:border-l sm:border-slate-200 sm:pl-3">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Colaborador Titular:</span>
              <p class="font-bold text-slate-800 text-xs">{{ pacote.colaborador.nome }}</p>
              <p class="text-[11px] text-slate-600 font-mono">CPF: {{ pacote.colaborador.cpf }} • Matrícula: {{ pacote.colaborador.matricula }}</p>
              <p class="text-[11px] text-slate-500">Cargo: {{ pacote.colaborador.cargo }} (Admissão: {{ formatarData(pacote.colaborador.data_admissao) }})</p>
            </div>
          </div>

          <!-- Resumo Financeiro / Médias para o Analista de Crédito -->
          <div class="space-y-2">
            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
              <AppIcon name="info" size="14" />
              <span>Súmula de Rendimentos para Análise de Crédito</span>
            </h4>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center">
              <div class="bg-emerald-50 border border-emerald-200 p-3 rounded-xl">
                <span class="text-[10px] font-bold text-emerald-800 uppercase block">Média Bruta Mensal</span>
                <span class="text-sm sm:text-base font-semibold text-emerald-700 font-mono">
                  {{ formatCurrency(pacote.resumo_financeiro.media_proventos_bruta) }}
                </span>
              </div>

              <div class="bg-rose-50 border border-rose-200 p-3 rounded-xl">
                <span class="text-[10px] font-bold text-[#9c2337] uppercase block">Média Líquida Mensal</span>
                <span class="text-sm sm:text-base font-semibold text-[#9c2337] font-mono">
                  {{ formatCurrency(pacote.resumo_financeiro.media_salario_liquida) }}
                </span>
              </div>

              <div class="bg-slate-50 border border-slate-200 p-3 rounded-xl">
                <span class="text-[10px] font-bold text-slate-600 uppercase block">Total Bruto (3 Meses)</span>
                <span class="text-xs sm:text-sm font-bold text-slate-800 font-mono">
                  {{ formatCurrency(pacote.resumo_financeiro.total_proventos_bruto) }}
                </span>
              </div>

              <div class="bg-slate-50 border border-slate-200 p-3 rounded-xl">
                <span class="text-[10px] font-bold text-slate-600 uppercase block">Total Líquido (3 Meses)</span>
                <span class="text-xs sm:text-sm font-bold text-slate-800 font-mono">
                  {{ formatCurrency(pacote.resumo_financeiro.total_salario_liquido) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Tabela Sintética dos 3 Meses -->
          <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
            <div class="bg-slate-100 px-3 py-2 grid grid-cols-12 text-[10px] font-bold text-slate-600 uppercase">
              <span class="col-span-3">Competência</span>
              <span class="col-span-3 text-right">Proventos (Bruto)</span>
              <span class="col-span-3 text-right">Descontos</span>
              <span class="col-span-3 text-right">Líquido Depositado</span>
            </div>
            <div class="divide-y divide-slate-100 bg-white text-xs">
              <div 
                v-for="m in pacote.resumo_financeiro.meses" 
                :key="m.competencia"
                class="px-3 py-2 grid grid-cols-12 items-center"
              >
                <div class="col-span-3">
                  <span class="font-bold text-slate-800">{{ m.competencia }}</span>
                  <span class="text-[10px] text-slate-400 block">{{ m.mes_nome }}/{{ m.ano }}</span>
                </div>
                <div class="col-span-3 text-right font-medium text-emerald-700 font-mono">
                  {{ formatCurrency(m.proventos) }}
                </div>
                <div class="col-span-3 text-right font-medium text-rose-600 font-mono">
                  - {{ formatCurrency(m.descontos) }}
                </div>
                <div class="col-span-3 text-right font-bold text-slate-900 font-mono">
                  {{ formatCurrency(m.liquido) }}
                </div>
              </div>
            </div>
          </div>

          <!-- Bloco Institucional de Validação por QR Code para Bancos e Financiamentos -->
          <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col sm:flex-row items-center gap-4">
            <div v-if="qrCodeDataUrl" class="shrink-0 bg-white p-2 rounded-2xl border border-slate-200 shadow-2xs">
              <img :src="qrCodeDataUrl" alt="QR Code de Autenticidade do Dossiê" class="w-20 h-20 sm:w-24 sm:h-24 object-contain" />
            </div>
            <div class="space-y-1 text-center sm:text-left min-w-0 flex-1">
              <div class="flex items-center justify-center sm:justify-start gap-1.5 font-bold text-slate-900 uppercase tracking-tight text-xs">
                <AppIcon name="shield-check" size="15" class="text-slate-500 shrink-0" />
                <span>Validação Digital Oficial via QR Code</span>
              </div>
              <p class="text-slate-600 text-[11px] leading-relaxed">
                Aponte a câmera do celular para este QR Code para verificar a autenticidade e a integridade deste dossiê diretamente no portal oficial da Vila Boa Construções.
              </p>
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-[10px] text-slate-500 pt-1 border-t border-slate-200">
                <span>Finalidade: {{ pacote.finalidade }}</span>
                <span>Emissão Oficial: {{ pacote.emissao.data }} às {{ pacote.emissao.hora }}</span>
              </div>
              <div class="font-mono text-[9px] text-slate-400 break-all pt-0.5">
                Protocolo SHA-256: {{ pacote.emissao.protocolo_autenticidade }}
              </div>
            </div>
          </div>
        </div>

        <!-- ======================================================== -->
        <!-- SEÇÃO 2: OS 3 HOLERITES COMPLETOS DISCRIMINADOS           -->
        <!-- ======================================================== -->
        <div class="space-y-6">
          <div class="flex items-center justify-between border-b border-slate-200 pb-2">
            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
              Demonstrativos Individuais Detalhados (Anexos 1 a 3)
            </h4>
            <span class="text-[10px] text-slate-400">Total: 3 Holerites Oficiais</span>
          </div>

          <!-- Loop de cada holerite com layout de contracheque oficial -->
          <div 
            v-for="(h, idx) in pacote.holerites" 
            :key="h.id"
            class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 space-y-3 shadow-sm page-break"
          >
            <!-- Topo do Holerite Individual -->
            <div class="flex justify-between items-start border-b border-slate-200 pb-2.5">
              <div class="flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-[#9c2337] text-white font-bold text-xs flex items-center justify-center shrink-0">
                  {{ idx + 1 }}
                </span>
                <div>
                  <h5 class="text-xs font-bold text-slate-800 leading-none">
                    Demonstrativo de Pagamento • {{ h.competencia }} ({{ h.mes_nome }}/{{ h.ano }})
                  </h5>
                  <p class="text-[10px] text-slate-500 mt-0.5">
                    Pagamento efetuado em: <strong class="text-slate-700">{{ h.data_pagamento }}</strong> • {{ h.tipo_folha }}
                  </p>
                </div>
              </div>

              <div class="text-right">
                <span class="text-[10px] text-slate-400 block uppercase">Salário Líquido</span>
                <span class="text-sm font-semibold text-slate-900 font-mono">
                  {{ formatCurrency(h.salario_liquido) }}
                </span>
              </div>
            </div>

            <!-- Tabela dos Itens (Proventos e Descontos) -->
            <div class="border border-slate-200 rounded-xl overflow-hidden text-[11px]">
              <div class="bg-slate-100/90 px-3 py-1.5 grid grid-cols-12 text-[9px] font-bold text-slate-600 uppercase border-b border-slate-200">
                <span class="col-span-2">Cód.</span>
                <span class="col-span-5">Descrição</span>
                <span class="col-span-2 text-right">Ref.</span>
                <span class="col-span-3 text-right">Valor</span>
              </div>
              <div class="divide-y divide-slate-100 bg-white">
                <div 
                  v-for="item in h.itens" 
                  :key="item.codigo"
                  class="px-3 py-1.5 grid grid-cols-12 items-center"
                >
                  <span class="col-span-2 font-mono text-slate-400 text-[10px]">{{ item.codigo }}</span>
                  <span class="col-span-5 font-medium text-slate-800 truncate">{{ item.descricao }}</span>
                  <span class="col-span-2 text-right text-slate-500 text-[10px]">{{ item.referencia }}</span>
                  <span 
                    class="col-span-3 text-right font-semibold font-mono"
                    :class="item.tipo === 'PROVENTO' ? 'text-emerald-700' : 'text-rose-600'"
                  >
                    {{ item.tipo === 'DESCONTO' ? '-' : '+' }} {{ formatCurrency(item.valor) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Rodapé Sintético do Holerite Individual -->
            <div class="grid grid-cols-3 gap-2 bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-center text-[10px]">
              <div>
                <span class="text-slate-500 block">Total Proventos:</span>
                <span class="font-bold text-emerald-700 font-mono">{{ formatCurrency(h.total_proventos) }}</span>
              </div>
              <div>
                <span class="text-slate-500 block">Total Descontos:</span>
                <span class="font-bold text-rose-600 font-mono">{{ formatCurrency(h.total_descontos) }}</span>
              </div>
              <div>
                <span class="text-slate-600 block font-bold">Líquido Recebido:</span>
                <span class="font-semibold text-slate-900 font-mono">{{ formatCurrency(h.salario_liquido) }}</span>
              </div>
            </div>

            <!-- Carimbo de Ciência / Validação -->
            <div class="text-[9px] text-slate-400 flex flex-col sm:flex-row sm:items-center justify-between gap-1 pt-1 font-mono">
              <span class="truncate">Autenticação: {{ h.autenticacao_digital.substring(0, 32) }}...</span>
              <span v-if="h.aceite?.confirmado" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200/80 font-sans shrink-0">
                <AppIcon name="check" size="10" class="text-slate-500" />
                <span>Sim (Confirmado em {{ h.aceite.data_hora }})</span>
              </span>
              <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-900 border border-amber-200/80 font-sans shrink-0">
                <AppIcon name="alert-triangle" size="10" class="text-slate-500" />
                <span>Não (Pendente de Ciência)</span>
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Barra de Ações Fixa Inferior com Safe Area Padding -->
      <div class="p-3.5 pb-[calc(0.875rem+env(safe-area-inset-bottom,0px))] bg-slate-50 border-t border-slate-200 flex flex-wrap sm:flex-nowrap gap-2.5 shrink-0">
        <button 
          @click="imprimirPacote" 
          class="flex-1 h-12 px-5 bg-[#9c2337] hover:bg-[#831b2d] active:scale-[0.98] text-white font-bold rounded-xl text-xs sm:text-sm flex items-center justify-center gap-2 shadow-sm transition cursor-pointer"
        >
          <AppIcon name="printer" size="16" />
          <span>Salvar / Imprimir PDF (3 Meses)</span>
        </button>

        <button 
          type="button"
          @click="compartilharPacoteWhatsapp"
          class="h-12 px-4 bg-emerald-700 hover:bg-emerald-800 active:scale-[0.98] text-white font-bold rounded-xl text-xs flex items-center justify-center gap-2 transition cursor-pointer shrink-0 shadow-2xs"
          title="Compartilhar Dossiê de 3 Meses via WhatsApp"
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
  </div>
</template>

<script setup>
import { usarFecharComEsc } from '../utils/fecharComEsc.js';
import { ref, computed, watch } from 'vue';
import QRCode from 'qrcode';
import { formatCurrency } from '../utils/masks.js';
import AppIcon from './AppIcon.vue';

const qrCodeDataUrl = ref('');

watch(() => props.pacote, async (novo) => {
  const hash = novo?.emissao?.protocolo_autenticidade || novo?.holerites?.[0]?.autenticacao_digital;
  if (hash) {
    try {
      const baseUrl = typeof window !== 'undefined' ? window.location.origin : '';
      const urlValidacao = `${baseUrl}/#/validar?hash=${hash}`;
      qrCodeDataUrl.value = await QRCode.toDataURL(urlValidacao, {
        width: 180,
        margin: 1,
        color: {
          dark: '#141414',
          light: '#ffffff'
        }
      });
    } catch (err) {
      console.warn('Erro ao gerar QR Code do Dossiê:', err);
    }
  }
}, { immediate: true });

const props = defineProps({
  pacote: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close']);
usarFecharComEsc(() => emit('close'));

const suportaCompartilhar = computed(() => {
  return typeof navigator !== 'undefined' && !!navigator.share;
});

function formatarData(dataStr) {
  if (!dataStr) return '-';
  const partes = dataStr.split('-');
  if (partes.length === 3) {
    return `${partes[2]}/${partes[1]}/${partes[0]}`;
  }
  return dataStr;
}

function compartilharPacoteWhatsapp() {
  if (!props.pacote) return;
  const p = props.pacote;
  const periodo = p.resumo_financeiro?.periodo_competencias || 'Últimos 3 Meses';
  const texto = encodeURIComponent(
    `*Dossiê de Comprovação de Renda (3 Holerites) - Vila Boa Construções*\n` +
    `Colaborador Titular: ${p.colaborador?.nome} (CPF: ${p.colaborador?.cpf})\n` +
    `Período: ${periodo}\n` +
    `Média Salarial Líquida: ${formatCurrency(p.resumo_financeiro?.media_salario_liquida)}\n` +
    `Média Proventos Bruta: ${formatCurrency(p.resumo_financeiro?.media_proventos_bruta)}\n` +
    `Documento Oficial com Autenticação Digital para Bancos e Financiamentos.`
  );
  window.open(`https://api.whatsapp.com/send?text=${texto}`, '_blank');
}

function imprimirPacote() {
  if (!props.pacote) return;
  const p = props.pacote;
  const qrImg = qrCodeDataUrl.value || '';
  const totalPaginas = 1 + (p.holerites ? p.holerites.length : 0);

  // Constrói documento HTML autocontido e otimizado especificamente para PDF / Impressão A4
  const html = `<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Dossie_Comprovacao_Renda_${p.colaborador?.matricula || 'VB'}_VilaBoa</title>
  <style>
    @page {
      size: A4 portrait;
      margin: 12mm 14mm 14mm 14mm;
    }
    * {
      box-sizing: border-box;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      color: #0f172a;
      background: #ffffff;
      margin: 0;
      padding: 0;
      font-size: 10.5px;
      line-height: 1.35;
    }
    .page {
      page-break-after: always;
      break-after: page;
      position: relative;
      min-height: 260mm;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .page:last-child {
      page-break-after: avoid;
      break-after: avoid;
    }
    .header-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2px solid #9c2337;
      padding-bottom: 8px;
      margin-bottom: 12px;
    }
    .header-logo {
      height: 38px;
      object-fit: contain;
    }
    .company-info {
      text-align: right;
      font-size: 9px;
      color: #475569;
      line-height: 1.25;
    }
    .company-title {
      font-weight: 900;
      font-size: 11px;
      color: #0f172a;
    }
    .doc-banner {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-left: 4px solid #9c2337;
      padding: 10px 12px;
      border-radius: 8px;
      margin-bottom: 12px;
    }
    .doc-banner h1 {
      margin: 0;
      font-size: 14px;
      font-weight: 900;
      color: #0f172a;
      letter-spacing: -0.3px;
    }
    .doc-banner p {
      margin: 2px 0 0 0;
      font-size: 9.5px;
      color: #64748b;
    }
    .meta-tags {
      display: flex;
      gap: 8px;
      margin-top: 6px;
      font-size: 9px;
      color: #475569;
    }
    .meta-tag {
      background: #ffffff;
      border: 1px solid #cbd5e1;
      padding: 2px 6px;
      border-radius: 4px;
      font-weight: 600;
    }
    .section-title {
      font-size: 9.5px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #475569;
      margin: 10px 0 4px 0;
      display: flex;
      align-items: center;
      gap: 4px;
    }
    .info-card {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 8px 10px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 6px;
      margin-bottom: 10px;
    }
    .info-item {
      font-size: 10px;
    }
    .info-label {
      color: #64748b;
      font-size: 8.5px;
      text-transform: uppercase;
      display: block;
      font-weight: 700;
    }
    .info-val {
      font-weight: 700;
      color: #0f172a;
    }
    .metrics-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
      margin-bottom: 12px;
    }
    .metric-box {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      padding: 8px 10px;
      border-radius: 8px;
      text-align: center;
    }
    .metric-box.highlight {
      background: #fdf2f4;
      border-color: #fbcfe8;
    }
    .metric-lbl {
      font-size: 8px;
      font-weight: 800;
      text-transform: uppercase;
      color: #64748b;
      display: block;
      margin-bottom: 2px;
    }
    .metric-num {
      font-size: 13px;
      font-weight: 900;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      color: #0f172a;
    }
    .metric-num.wine {
      color: #9c2337;
    }
    .table-custom {
      width: 100%;
      border-collapse: collapse;
      font-size: 9.5px;
      margin-bottom: 12px;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      overflow: hidden;
    }
    .table-custom th {
      background: #f1f5f9;
      color: #334155;
      font-weight: 800;
      text-transform: uppercase;
      font-size: 8.5px;
      padding: 6px 8px;
      border-bottom: 1px solid #cbd5e1;
      text-align: left;
    }
    .table-custom td {
      padding: 6px 8px;
      border-bottom: 1px solid #f1f5f9;
      color: #1e293b;
    }
    .table-custom tr:nth-child(even) {
      background: #f8fafc;
    }
    .qr-auth-section {
      background: #f8fafc;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      padding: 10px 12px;
      display: flex;
      align-items: center;
      gap: 14px;
      margin-top: 8px;
    }
    .qr-img-box {
      width: 84px;
      height: 84px;
      background: #ffffff;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      padding: 2px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .qr-img-box img {
      width: 80px;
      height: 80px;
      display: block;
    }
    .qr-details {
      flex: 1;
      font-size: 9px;
      color: #475569;
    }
    .qr-title {
      font-size: 10px;
      font-weight: 800;
      color: #0f172a;
      text-transform: uppercase;
      margin-bottom: 2px;
    }
    .hash-code {
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      font-size: 7.5px;
      color: #64748b;
      word-break: break-all;
      background: #ffffff;
      padding: 3px 6px;
      border: 1px solid #e2e8f0;
      border-radius: 4px;
      margin-top: 4px;
    }
    .holerite-card {
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      padding: 10px;
      margin-bottom: 10px;
      background: #ffffff;
    }
    .holerite-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #e2e8f0;
      padding-bottom: 6px;
      margin-bottom: 8px;
    }
    .holerite-h-title {
      font-size: 11px;
      font-weight: 800;
      color: #0f172a;
    }
    .holerite-h-badge {
      font-size: 8.5px;
      font-weight: 700;
      background: #f1f5f9;
      border: 1px solid #cbd5e1;
      padding: 2px 6px;
      border-radius: 4px;
    }
    .holerite-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 9px;
      margin-bottom: 8px;
      border: 1px solid #e2e8f0;
    }
    .holerite-table th {
      background: #f8fafc;
      padding: 4px 6px;
      border-bottom: 1px solid #cbd5e1;
      font-weight: 800;
      text-transform: uppercase;
      font-size: 8px;
    }
    .holerite-table td {
      padding: 4px 6px;
      border-bottom: 1px solid #f1f5f9;
    }
    .totals-row {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 6px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      padding: 6px 8px;
      border-radius: 6px;
      margin-bottom: 6px;
      text-align: center;
    }
    .totals-lbl {
      font-size: 8px;
      color: #64748b;
      display: block;
      font-weight: 700;
    }
    .totals-val {
      font-size: 11px;
      font-weight: 800;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }
    .bases-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 4px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      padding: 4px 6px;
      border-radius: 4px;
      font-size: 8px;
      color: #475569;
      margin-bottom: 6px;
    }
    .page-footer {
      border-top: 1px solid #e2e8f0;
      padding-top: 6px;
      margin-top: auto;
      display: flex;
      justify-content: space-between;
      font-size: 8px;
      color: #64748b;
    }
  </style>
</head>
<body>

  <!-- ========================================== -->
  <!-- PÁGINA 1: FOLHA DE ROSTO & DECLARAÇÃO OFICIAL -->
  <!-- ========================================== -->
  <div class="page">
    <div>
      <div class="header-bar">
        <div>
          <img src="/img/logo-vilaboa-color.png" alt="Vila Boa" class="header-logo" />
        </div>
        <div class="company-info">
          <div class="company-title">${p.empresa?.razao_social || 'VILA BOA CONSTRUÇÕES E SERVIÇOS LIMITADA'}</div>
          <div>CNPJ: ${p.empresa?.cnpj || '02.785.261/0001-90'} · ${p.empresa?.endereco || 'São Bernardo do Campo - SP'}</div>
          <div>Departamento de Pessoal: ${p.empresa?.contato_rh || 'dp@vilaboaconstrucoes.com.br'}</div>
        </div>
      </div>

      <div class="doc-banner">
        <h1>DOSSIÊ CONSOLIDADO DE COMPROVAÇÃO DE RENDA</h1>
        <p>Documento Oficial emitido pelo Portal do Colaborador para Análise de Crédito, Financiamento Habitacional (Caixa / MCMV / SBPE) e Locações</p>
        <div class="meta-tags">
          <span class="meta-tag">Período: ${p.resumo_financeiro?.periodo_competencias || 'Últimos 3 Meses'}</span>
          <span class="meta-tag">Emissão: ${p.emissao?.data || ''} às ${p.emissao?.hora || ''}</span>
          <span class="meta-tag">Protocolo: ${(p.emissao?.protocolo_autenticidade || '').substring(0, 20)}...</span>
        </div>
      </div>

      <div class="section-title">1. Identificação do Colaborador Titular</div>
      <div class="info-card">
        <div>
          <div class="info-item"><span class="info-label">Nome Completo:</span><span class="info-val">${p.colaborador?.nome || ''}</span></div>
          <div class="info-item" style="margin-top: 4px;"><span class="info-label">CPF do Titular:</span><span class="info-val" style="font-family: monospace;">${p.colaborador?.cpf || ''}</span></div>
          <div class="info-item" style="margin-top: 4px;"><span class="info-label">Data de Admissão:</span><span class="info-val">${formatarData(p.colaborador?.data_admissao)}</span></div>
        </div>
        <div>
          <div class="info-item"><span class="info-label">Matrícula Funcional:</span><span class="info-val" style="font-family: monospace;">${p.colaborador?.matricula || ''}</span></div>
          <div class="info-item" style="margin-top: 4px;"><span class="info-label">Cargo Oficial:</span><span class="info-val">${p.colaborador?.cargo || ''}</span></div>
          <div class="info-item" style="margin-top: 4px;"><span class="info-label">Departamento / Setor:</span><span class="info-val">${p.colaborador?.departamento || ''}</span></div>
        </div>
      </div>

      <div class="section-title">2. Súmula de Rendimentos Consolidados (${p.resumo_financeiro?.quantidade_meses || 3} Meses)</div>
      <div class="metrics-grid">
        <div class="metric-box highlight">
          <span class="metric-lbl">MÉDIA LÍQUIDA MENSAL</span>
          <span class="metric-num wine">${formatCurrency(p.resumo_financeiro?.media_salario_liquida || 0)}</span>
        </div>
        <div class="metric-box">
          <span class="metric-lbl">MÉDIA BRUTA MENSAL</span>
          <span class="metric-num">${formatCurrency(p.resumo_financeiro?.media_proventos_bruta || 0)}</span>
        </div>
        <div class="metric-box">
          <span class="metric-lbl">TOTAL LÍQUIDO (${p.resumo_financeiro?.quantidade_meses || 3}M)</span>
          <span class="metric-num">${formatCurrency(p.resumo_financeiro?.total_salario_liquido || 0)}</span>
        </div>
        <div class="metric-box">
          <span class="metric-lbl">TOTAL BRUTO (${p.resumo_financeiro?.quantidade_meses || 3}M)</span>
          <span class="metric-num">${formatCurrency(p.resumo_financeiro?.total_proventos_bruto || 0)}</span>
        </div>
      </div>

      <div class="section-title">3. Discriminação Sintética por Competência</div>
      <table class="table-custom">
        <thead>
          <tr>
            <th>Competência</th>
            <th>Tipo de Folha</th>
            <th>Data de Pagamento</th>
            <th style="text-align: right;">Total Bruto</th>
            <th style="text-align: right;">Total Descontos</th>
            <th style="text-align: right;">Líquido Recebido</th>
          </tr>
        </thead>
        <tbody>
          ${(p.resumo_financeiro?.meses || []).map(m => `
            <tr>
              <td><strong>${m.competencia}</strong> (${m.mes_nome}/${m.ano})</td>
              <td>Folha Mensal</td>
              <td>${m.data_pagamento}</td>
              <td style="text-align: right; font-family: monospace;">${formatCurrency(m.proventos)}</td>
              <td style="text-align: right; font-family: monospace; color: #dc2626;">-${formatCurrency(m.descontos)}</td>
              <td style="text-align: right; font-family: monospace; font-weight: 800;">${formatCurrency(m.liquido)}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>

      <!-- Bloco com QR Code Nítido e Integrado de Validação Oficial -->
      <div class="qr-auth-section">
        <div class="qr-img-box">
          ${qrImg ? `<img src="${qrImg}" alt="QR Code Oficial" />` : ''}
        </div>
        <div class="qr-details">
          <div class="qr-title">Chancela Oficial de Autenticidade via QR Code</div>
          <p style="margin: 0;">Aponte a câmera do celular para este QR Code para auditar a autenticidade deste dossiê diretamente nos servidores oficiais da Vila Boa Construções. Documento eletrônico válido e auditável em conformidade com o Art. 464 da CLT e LGPD (Lei 13.709/2018).</p>
          <div class="hash-code">Protocolo Criptográfico SHA-256: ${p.emissao?.protocolo_autenticidade || ''}</div>
        </div>
      </div>
    </div>

    <div class="page-footer">
      <span>Vila Boa Construções e Serviços LTDA · Documento Oficial Emitido Eletronicamente</span>
      <span>Página 1 de ${totalPaginas}</span>
    </div>
  </div>

  <!-- ========================================== -->
  <!-- PÁGINAS 2 EM DIANTE: OS HOLERITES INDIVIDUAIS -->
  <!-- ========================================== -->
  ${(p.holerites || []).map((h, idx) => `
    <div class="page">
      <div>
        <div class="header-bar">
          <div>
            <img src="/img/logo-vilaboa-color.png" alt="Vila Boa" class="header-logo" />
          </div>
          <div class="company-info">
            <div class="company-title">${p.empresa?.razao_social || 'VILA BOA CONSTRUÇÕES E SERVIÇOS LIMITADA'}</div>
            <div>CNPJ: ${p.empresa?.cnpj || '02.785.261/0001-90'} · Dossiê Anexo ${idx + 1} de ${p.holerites.length}</div>
          </div>
        </div>

        <div class="holerite-card">
          <div class="holerite-header">
            <div>
              <div class="holerite-h-title">DEMONSTRATIVO DE PAGAMENTO DE SALÁRIO · ${h.competencia} (${h.mes_nome}/${h.ano})</div>
              <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Pagamento realizado em: <strong>${h.data_pagamento}</strong></div>
            </div>
            <div class="holerite-h-badge">${h.tipo_folha || 'Folha Mensal'}</div>
          </div>

          <div class="info-card" style="margin-bottom: 8px;">
            <div>
              <div class="info-item"><span class="info-label">Colaborador:</span><span class="info-val">${p.colaborador?.nome || ''}</span></div>
              <div class="info-item" style="margin-top: 3px;"><span class="info-label">CPF:</span><span class="info-val" style="font-family: monospace;">${p.colaborador?.cpf || ''}</span></div>
            </div>
            <div>
              <div class="info-item"><span class="info-label">Matrícula:</span><span class="info-val" style="font-family: monospace;">${p.colaborador?.matricula || ''}</span></div>
              <div class="info-item" style="margin-top: 3px;"><span class="info-label">Cargo:</span><span class="info-val">${p.colaborador?.cargo || ''}</span></div>
            </div>
          </div>

          <table class="holerite-table">
            <thead>
              <tr>
                <th style="width: 12%;">Cód.</th>
                <th style="width: 50%;">Descrição dos Vencimentos e Descontos</th>
                <th style="width: 15%; text-align: right;">Referência</th>
                <th style="width: 23%; text-align: right;">Valor (R$)</th>
              </tr>
            </thead>
            <tbody>
              ${(h.itens || []).map(item => `
                <tr>
                  <td style="font-family: monospace; color: #64748b;">${item.codigo}</td>
                  <td><strong>${item.descricao}</strong></td>
                  <td style="text-align: right; font-family: monospace;">${item.referencia || '-'}</td>
                  <td style="text-align: right; font-family: monospace; font-weight: ${item.tipo === 'PROVENTO' ? '700' : 'normal'}; color: ${item.tipo === 'DESCONTO' ? '#dc2626' : '#0f172a'};">
                    ${item.tipo === 'DESCONTO' ? '-' : ''}${formatCurrency(item.valor)}
                  </td>
                </tr>
              `).join('')}
            </tbody>
          </table>

          <div class="totals-row">
            <div>
              <span class="totals-lbl">TOTAL PROVENTOS</span>
              <span class="totals-val">${formatCurrency(h.total_proventos)}</span>
            </div>
            <div>
              <span class="totals-lbl">TOTAL DESCONTOS</span>
              <span class="totals-val" style="color: #dc2626;">-${formatCurrency(h.total_descontos)}</span>
            </div>
            <div style="background: #ffffff; border-radius: 4px; border: 1px solid #cbd5e1; padding: 2px 4px;">
              <span class="totals-lbl" style="color: #9c2337;">VALOR LÍQUIDO A RECEBER</span>
              <span class="totals-val" style="color: #0f172a; font-size: 13px;">${formatCurrency(h.salario_liquido)}</span>
            </div>
          </div>

          <div class="bases-row">
            <div>Salário Base: <strong>${formatCurrency(h.salario_base || 0)}</strong></div>
            <div>Base INSS: <strong>${formatCurrency(h.base_inss || 0)}</strong></div>
            <div>Base FGTS: <strong>${formatCurrency(h.base_fgts || 0)}</strong></div>
            <div>FGTS do Mês: <strong>${formatCurrency(h.fgts_mes || 0)}</strong></div>
          </div>

          <div style="padding-top: 4px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; font-size: 8px; color: #64748b;">
            <div>Autenticação eSocial / Art. 464 CLT: <span style="font-family: monospace;">${h.autenticacao_digital || ''}</span></div>
            <div>Ciência CLT: <strong>${h.aceite?.confirmado ? 'Confirmada em ' + h.aceite.data_hora : 'Pendente de Ciência'}</strong></div>
          </div>
        </div>
      </div>

      <div class="page-footer">
        <span>Vila Boa Construções e Serviços LTDA · Demonstrativo Anexo Oficial</span>
        <span>Página ${idx + 2} de ${totalPaginas}</span>
      </div>
    </div>
  `).join('')}

</body>
</html>`;

  // Cria um iframe invisível para impressão isolada livre de travas de viewport da SPA
  const iframe = document.createElement('iframe');
  iframe.style.position = 'fixed';
  iframe.style.right = '0';
  iframe.style.bottom = '0';
  iframe.style.width = '0';
  iframe.style.height = '0';
  iframe.style.border = 'none';
  document.body.appendChild(iframe);

  const doc = iframe.contentWindow.document;
  doc.open();
  doc.write(html);
  doc.close();

  // Aguarda carregamento das imagens antes de disparar a impressão nativa
  setTimeout(() => {
    iframe.contentWindow.focus();
    iframe.contentWindow.print();
    setTimeout(() => {
      if (document.body.contains(iframe)) {
        document.body.removeChild(iframe);
      }
    }, 2000);
  }, 400);
}

async function compartilharPacote() {
  if (!suportaCompartilhar.value || !props.pacote) return;

  try {
    const periodo = props.pacote.resumo_financeiro?.periodo_competencias || 'Últimos 3 Meses';
    await navigator.share({
      title: 'Comprovante de Renda - Vila Boa Construções',
      text: `Pacote de Comprovação de Renda (3 Últimos Holerites - ${periodo}) do colaborador ${props.pacote.colaborador?.nome} (Vila Boa Construções e Serviços).`,
      url: window.location.href
    });
  } catch (err) {
    // Compartilhamento cancelado pelo usuário
  }
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
  .printable-pacote, .printable-pacote * {
    visibility: visible;
  }
  .printable-pacote {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    margin: 0;
    padding: 10mm;
  }
  .page-break {
    page-break-after: always;
    break-after: page;
  }
}
</style>
