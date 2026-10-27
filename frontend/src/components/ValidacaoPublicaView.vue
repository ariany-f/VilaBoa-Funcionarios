<template>
  <div class="min-h-screen bg-slate-100 flex flex-col justify-between py-6 px-4 sm:px-6">
    <div class="max-w-2xl w-full mx-auto space-y-6">
      
      <!-- Cabeçalho Institucional -->
      <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <img src="/img/logo-vilaboa-color.png" alt="Vila Boa" class="h-8 w-auto object-contain" />
          <div class="border-l border-slate-200 pl-3">
            <h1 class="text-xs sm:text-sm font-semibold text-slate-900 leading-none">
              Validador Oficial de Autenticidade
            </h1>
            <span class="text-[10px] text-slate-500 font-medium">
              Vila Boa Construções e Serviços LTDA
            </span>
          </div>
        </div>

        <button 
          @click="irParaLogin" 
          class="text-xs font-bold text-[#9c2337] hover:text-[#831b2d] bg-rose-50 border border-rose-200 px-3 py-1.5 rounded-xl transition cursor-pointer"
        >
          Acessar Portal →
        </button>
      </div>

      <!-- Estado 1: Carregando Validação -->
      <div v-if="carregando" class="bg-white rounded-2xl p-12 text-center space-y-3 border border-slate-200 shadow-sm">
        <div class="w-8 h-8 border-3 border-[#9c2337] border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="text-xs font-bold text-slate-700">Auditando autenticidade digital nos registros da Vila Boa...</p>
      </div>

      <!-- Estado 2: Documento Autêntico e Válido -->
      <div v-else-if="resultado && resultado.valido" class="bg-white rounded-2xl p-6 sm:p-8 border-2 border-emerald-500 shadow-lg space-y-6 animate-slide-up">
        
        <!-- Selo de Verificação Positivo -->
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5 flex items-start sm:items-center gap-4">
          <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-md">
            <AppIcon name="shield-check" size="26" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-[10px] uppercase font-bold tracking-wider text-emerald-900 bg-emerald-200/80 px-2 py-0.5 rounded-full">
                Verificação Oficial
              </span>
              <span class="text-[11px] text-emerald-800 font-mono">Status: AUTÊNTICO</span>
            </div>
            <h2 class="text-base sm:text-lg font-semibold text-emerald-950 mt-1">
              Documento Autêntico e Válido
            </h2>
            <p class="text-xs text-emerald-900 mt-0.5 leading-relaxed">
              Os dados deste demonstrativo de pagamento conferem integralmente com os arquivos oficiais do Departamento Pessoal da Vila Boa Construções.
            </p>
          </div>
        </div>

        <!-- Dados do Colaborador e do Holerite Certificado -->
        <div class="space-y-3">
          <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-100 pb-2">
            <AppIcon name="document" size="14" />
            <span>Dados Oficiais Certificados</span>
          </h3>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
              <span class="text-[10px] text-slate-400 font-bold uppercase block">Colaborador Titular</span>
              <span class="font-bold text-slate-900 text-sm block">{{ resultado.colaborador.nome }}</span>
              <span class="text-slate-500">Matrícula: {{ resultado.colaborador.matricula }} • CPF: {{ resultado.colaborador.cpf_mascarado }}</span>
            </div>

            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
              <span class="text-[10px] text-slate-400 font-bold uppercase block">Cargo & Lotação</span>
              <span class="font-bold text-slate-900 text-sm block">{{ resultado.colaborador.cargo }}</span>
              <span class="text-slate-500">{{ resultado.colaborador.departamento }}</span>
            </div>

            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
              <span class="text-[10px] text-slate-400 font-bold uppercase block">Competência / Folha</span>
              <span class="font-bold text-slate-900 text-sm block">Ref: {{ resultado.documento.competencia }}</span>
              <span class="text-slate-500">{{ resultado.documento.tipo_folha }} • Pagto: {{ resultado.documento.data_pagamento }}</span>
            </div>

            <div class="p-3 bg-emerald-50/60 rounded-2xl border border-emerald-200">
              <span class="text-[10px] text-emerald-800 font-bold uppercase block">Salário Líquido Confirmado</span>
              <span class="font-semibold text-emerald-700 text-base font-mono block">
                {{ formatCurrency(resultado.documento.salario_liquido) }}
              </span>
              <span class="text-[10px] text-emerald-800">Bruto: {{ formatCurrency(resultado.documento.total_proventos) }}</span>
            </div>
          </div>
        </div>

        <!-- Informações da Empregadora -->
        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 text-xs space-y-1">
          <span class="text-[10px] text-slate-400 font-bold uppercase block">Fonte Pagadora Certificada</span>
          <p class="font-bold text-slate-800">{{ resultado.empresa.razao_social }}</p>
          <p class="text-slate-600">CNPJ: {{ resultado.empresa.cnpj }} • {{ resultado.empresa.cidade }}</p>
          <p class="text-[11px] text-slate-500">DP: {{ resultado.empresa.contato_rh }}</p>
        </div>

        <!-- Protocolo e Registro Jurídico -->
        <div class="bg-slate-100 p-3 rounded-xl border border-slate-200 text-[10px] text-slate-500 space-y-1 font-mono break-all">
          <div><strong class="font-sans">Chave Autenticidade Digital:</strong> {{ resultado.documento.autenticacao_digital }}</div>
          <div><strong class="font-sans">Consulta realizada em:</strong> {{ resultado.validado_em }}</div>
          <div v-if="resultado.documento.ciencia_registrada" class="text-emerald-800 font-sans font-bold pt-1">
            ✓ Ciência do Colaborador registrada para o DP em: {{ resultado.documento.data_ciencia }} (Art. 464 CLT)
          </div>
        </div>

        <!-- Botão para Imprimir Laudo / Comprovante -->
        <div class="pt-2 flex flex-col sm:flex-row gap-2">
          <button 
            type="button" 
            @click="imprimirLaudo"
            class="flex-1 py-3 px-4 bg-[#9c2337] hover:bg-[#831b2d] active:scale-[0.98] text-white font-bold rounded-xl text-xs flex items-center justify-center gap-2 shadow-sm transition cursor-pointer"
          >
            <AppIcon name="printer" size="16" />
            <span>Imprimir Certidão de Autenticidade</span>
          </button>
          <button 
            type="button" 
            @click="irParaLogin"
            class="py-3 px-4 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl text-xs transition cursor-pointer"
          >
            Ir para a Tela de Login
          </button>
        </div>
      </div>

      <!-- Estado 3: Documento Inválido / Não Localizado -->
      <div v-else class="bg-white rounded-2xl p-6 sm:p-8 border-2 border-rose-400 shadow-md space-y-5 animate-slide-up">
        <div class="bg-rose-50 border border-rose-200 rounded-2xl p-5 flex items-start gap-4">
          <div class="w-12 h-12 rounded-2xl bg-rose-600 text-white flex items-center justify-center shrink-0 shadow-md">
            <AppIcon name="alert-triangle" size="26" />
          </div>
          <div>
            <span class="text-[10px] uppercase font-bold tracking-wider text-rose-900 bg-rose-200/80 px-2 py-0.5 rounded-full">
              Falha de Validação
            </span>
            <h2 class="text-base sm:text-lg font-semibold text-rose-950 mt-1">
              Documento Não Localizado
            </h2>
            <p class="text-xs text-rose-900 mt-0.5 leading-relaxed">
              {{ erro || 'Não foi possível certificar a autenticidade deste documento. Verifique se o código ou link informado foi digitado corretamente.' }}
            </p>
          </div>
        </div>

        <div class="text-center pt-2">
          <button 
            type="button" 
            @click="irParaLogin"
            class="py-2.5 px-5 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl text-xs transition cursor-pointer"
          >
            Voltar para a Página Inicial
          </button>
        </div>
      </div>

      <!-- Rodapé Institucional -->
      <div class="text-center text-[11px] text-slate-400 space-y-1">
        <p>Vila Boa Construções e Serviços LTDA • CNPJ: 02.785.261/0001-90</p>
        <p>Serviço oficial de auditoria digital de contracheques em conformidade com o eSocial.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { formatCurrency } from '../utils/masks.js';
import AppIcon from './AppIcon.vue';

const carregando = ref(true);
const resultado = ref(null);
const erro = ref('');

onMounted(async () => {
  await consultarAutenticidade();
});

async function consultarAutenticidade() {
  carregando.value = true;
  erro.value = '';

  try {
    // Extrai parâmetros da URL (id e auth podem vir no hash '#/validar?id=...' ou no search '?id=...')
    const url = new URL(window.location.href);
    let params = url.searchParams;

    // Se estiver no formato hash '#/validar?id=...'
    if (window.location.hash.includes('?')) {
      const hashQuery = window.location.hash.split('?')[1];
      params = new URLSearchParams(hashQuery);
    }

    const id = params.get('id') || '';
    // QR do dossiê de renda manda a chave como "hash"
    const auth = params.get('auth') || params.get('hash') || '';

    if (!id && !auth) {
      erro.value = 'Chave de autenticação ou ID do demonstrativo ausente no QR Code.';
      carregando.value = false;
      return;
    }

    const res = await fetch(`/api/validar-holerite?id=${encodeURIComponent(id)}&auth=${encodeURIComponent(auth)}`);
    const data = await res.json();

    if (!res.ok || !data.valido) {
      erro.value = data.erro || 'Documento não localizado nos arquivos da Vila Boa Construções.';
      resultado.value = null;
    } else {
      resultado.value = data;
    }
  } catch (e) {
    erro.value = 'Erro de conexão com o servidor de autenticação da Vila Boa.';
  } finally {
    carregando.value = false;
  }
}

function imprimirLaudo() {
  window.print();
}

function irParaLogin() {
  window.location.hash = '';
  window.location.search = '';
  window.location.pathname = '/';
}
</script>

<style scoped>
@keyframes slideUp {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.animate-slide-up {
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
