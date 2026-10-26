<template>
  <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
    <!-- Card do Modal -->
    <div 
      class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200/90 overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-200"
      role="dialog"
      aria-modal="true"
    >
      <!-- Cabeçalho com Glass Bubble Close Button Nativo -->
      <div class="sticky top-0 bg-white/95 backdrop-blur-md px-5 py-4 border-b border-slate-100 flex items-center justify-between z-10 shrink-0">
        <div class="flex items-center space-x-3 min-w-0">
          <div class="w-10 h-10 rounded-2xl bg-slate-100 text-slate-600 border border-slate-200 flex items-center justify-center shrink-0 shadow-2xs">
            <AppIcon name="shield-check" size="20" />
          </div>
          <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-[10px] font-bold text-emerald-800 bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 rounded-full uppercase tracking-wider">
                Lei nº 13.709/2018
              </span>
              <span class="text-[10px] font-bold text-slate-500 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-full">
                Art. 464 CLT
              </span>
            </div>
            <h3 class="font-semibold text-slate-900 text-base sm:text-lg leading-tight mt-0.5 truncate">
              Segurança & Proteção de Dados (LGPD)
            </h3>
          </div>
        </div>

        <!-- Botão Fechar Estilo Bubble Apple Nativo -->
        <button 
          type="button"
          @click="$emit('close')"
          class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-all cursor-pointer shrink-0 shadow-2xs"
          title="Fechar painel de privacidade"
        >
          <AppIcon name="close" size="14" />
        </button>
      </div>

      <!-- Conteúdo com Rolagem Suave -->
      <div class="p-5 overflow-y-auto space-y-4 text-xs sm:text-sm text-slate-600">
        <!-- Banner de Boas-Vindas da Política -->
        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-1.5">
          <h4 class="font-semibold text-slate-900 text-sm flex items-center gap-2">
            <span>Compromisso com o Sigilo Funcional</span>
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          </h4>
          <p class="text-xs text-slate-600 leading-relaxed">
            A <strong>Vila Boa Construções e Serviços LTDA</strong> adota rigorosas salvaguardas técnicas e administrativas para assegurar a inviolabilidade, confidencialidade e autenticidade dos seus dados funcionais e remunerações.
          </p>
        </div>

        <!-- Grade com 4 Pilares de Proteção -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <!-- Pilar 1: Criptografia e Não-Repúdio -->
          <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs space-y-1.5">
            <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold">
              <AppIcon name="lock" size="14" />
            </div>
            <h5 class="font-semibold text-slate-900 text-xs">Criptografia & Sessão HMAC</h5>
            <p class="text-[11px] text-slate-500 leading-relaxed">
              Tráfego protegido e tokens criptografados com <code>HMAC-SHA256</code>. Nenhuma senha trafega em texto aberto na rede.
            </p>
          </div>

          <!-- Pilar 2: Mascaramento LGPD -->
          <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs space-y-1.5">
            <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold">
              <AppIcon name="eye" size="14" />
            </div>
            <h5 class="font-semibold text-slate-900 text-xs">Mascaramento de Identificadores</h5>
            <p class="text-[11px] text-slate-500 leading-relaxed">
              No validador de QR Code para bancos e terceiros, seu CPF é anonimizado (ex: <code>123.***.***-45</code>). Ninguém tem acesso integral aos seus dados sem sua autorização.
            </p>
          </div>

          <!-- Pilar 3: Bases Legais Legítimas -->
          <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs space-y-1.5">
            <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold">
              <AppIcon name="document" size="14" />
            </div>
            <h5 class="font-semibold text-slate-900 text-xs">Bases Legais (Art. 7º LGPD)</h5>
            <p class="text-[11px] text-slate-500 leading-relaxed">
              Tratamento respaldado no <strong>Inciso II</strong> (cumprimento de obrigação legal trabalhista / eSocial) e <strong>Inciso V</strong> (execução do contrato de trabalho CLT).
            </p>
          </div>

          <!-- Pilar 4: Trilha de Auditoria Inviolável -->
          <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs space-y-1.5">
            <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold">
              <AppIcon name="shield-check" size="14" />
            </div>
            <h5 class="font-semibold text-slate-900 text-xs">Trilha de Auditoria & Não-Repúdio</h5>
            <p class="text-[11px] text-slate-500 leading-relaxed">
              Assinaturas de recibo registram data/hora, IP de origem e hash criptográfico, conferindo validade jurídica irrefutável conforme o Art. 464 da CLT.
            </p>
          </div>
        </div>

        <!-- Seção: Seus Direitos como Titular (Art. 18 LGPD) -->
        <div class="bg-slate-50/70 border border-slate-200 rounded-2xl p-4 space-y-2">
          <h4 class="font-semibold text-slate-900 text-xs uppercase tracking-wider">
            Seus Direitos como Titular de Dados
          </h4>
          <ul class="text-[11px] text-slate-600 space-y-1.5 list-disc list-inside">
            <li><strong>Acesso Facilitado:</strong> Consulte seus holerites, informes e recibos de EPI a qualquer instante no portal.</li>
            <li><strong>Correção e Atualização:</strong> Caso identifique divergências em endereço ou telefone, solicite a retificação imediata junto ao DP.</li>
            <li><strong>Transparência e Finalidade:</strong> Seus dados jamais são comercializados, alugados ou transferidos a terceiros alheios às exigências legais da folha.</li>
          </ul>
        </div>

        <!-- Contato do Encarregado (DPO) -->
        <div class="p-3.5 rounded-2xl bg-emerald-50/60 border border-emerald-200/80 flex items-center justify-between gap-3 flex-wrap">
          <div>
            <span class="text-[10px] font-bold text-emerald-900 uppercase tracking-wider block">Canal do Encarregado de Dados (DPO)</span>
            <span class="font-mono text-xs font-bold text-emerald-950">dpo@vilaboaconstrucoes.com.br</span>
          </div>
          <span class="text-[10px] font-semibold text-emerald-800 bg-white border border-emerald-300 px-2.5 py-1 rounded-full">
            Prazo de Resposta: até 15 dias
          </span>
        </div>
      </div>

      <!-- Rodapé com Ações -->
      <div class="sticky bottom-0 bg-slate-50 px-5 py-3.5 border-t border-slate-200 flex items-center justify-end gap-2 shrink-0">
        <button 
          type="button" 
          @click="$emit('close')"
          class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 active:scale-95 text-white font-bold text-xs transition cursor-pointer shadow-xs"
        >
          Entendido
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { usarFecharComEsc } from '../utils/fecharComEsc.js';
import AppIcon from './AppIcon.vue';

const emit = defineEmits(['close']);

usarFecharComEsc(() => emit('close'));
</script>
