<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[60] bg-black/40 flex items-end sm:items-center justify-center p-0 sm:p-4" @click.self="emit('close')">
      <div class="w-full sm:max-w-xl bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl max-h-[90vh] flex flex-col">
        <!-- Cabeçalho -->
        <div class="px-5 pt-5 pb-4 border-b border-slate-100 flex items-start gap-3">
          <div class="w-12 h-12 rounded-full bg-[#141414] text-white flex items-center justify-center font-semibold shrink-0">
            {{ c.nome.slice(0, 2).toUpperCase() }}
          </div>
          <div class="min-w-0 flex-1">
            <h3 class="text-base font-semibold text-slate-900 truncate">{{ c.nome }}</h3>
            <p class="text-xs text-slate-500 truncate">{{ c.cargo }} · {{ c.departamento }}</p>
            <p class="text-xs text-slate-400 mt-0.5">Matrícula {{ c.matricula }}<template v-if="c.telefone"> · {{ c.telefone }}</template></p>
          </div>
          <button type="button" @click="emit('close')" class="w-9 h-9 rounded-full hover:bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer shrink-0" aria-label="Fechar">
            <AppIcon name="x" size="16" />
          </button>
        </div>

        <div class="overflow-y-auto p-5 space-y-5">
          <!-- Situação geral -->
          <div class="grid grid-cols-3 gap-2">
            <div v-for="k in indicadores" :key="k.rotulo" class="rounded-xl border border-slate-200 p-3">
              <span class="block text-[11px] text-slate-500">{{ k.rotulo }}</span>
              <span class="block text-lg font-semibold mt-0.5" :class="k.ok ? 'text-emerald-700' : 'text-amber-700'">{{ k.valor }}</span>
            </div>
          </div>

          <!-- Holerites -->
          <section>
            <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Holerites {{ ano }}</h4>
            <ul class="divide-y divide-slate-100 border border-slate-100 rounded-xl">
              <li v-for="(h, i) in c.holerites.detalhe" :key="i" class="px-3 py-2 flex justify-between gap-2 text-sm">
                <span class="text-slate-800 truncate">{{ h.descricao }}</span>
                <span class="flex items-center shrink-0"><span class="text-xs" :class="h.recebido_em ? 'text-emerald-700' : 'text-amber-700'">{{ h.recebido_em || 'Não recebido' }}</span><a v-if="!h.recebido_em && linkAviso(() => avisoHolerite(c, h))" :href="!h.recebido_em && linkAviso(() => avisoHolerite(c, h))" target="_blank" rel="noopener" class="h-7 px-2.5 rounded-lg bg-[#1DA851] hover:bg-[#178F44] text-white text-[11px] font-semibold inline-flex items-center gap-1 ml-2" title="Enviar aviso pelo WhatsApp"><AppIcon name="whatsapp" size="12" />Avisar</a></span>
              </li>
              <li v-if="!c.holerites.detalhe.length" class="px-3 py-3 text-sm text-slate-400">Nenhum holerite no ano.</li>
            </ul>
          </section>

          <!-- Espelho de ponto -->
          <section>
            <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Espelho de ponto</h4>
            <ul class="divide-y divide-slate-100 border border-slate-100 rounded-xl">
              <li v-for="p in c.ponto?.competencias || []" :key="p.competencia" class="px-3 py-2 flex justify-between gap-2 text-sm">
                <span class="text-slate-800">{{ p.mes_nome }}</span>
                <span class="flex items-center shrink-0"><span class="text-xs" :class="p.assinado_em ? 'text-emerald-700' : 'text-amber-700'">{{ p.assinado_em ? 'Assinado em ' + p.assinado_em : 'Falta assinar' }}</span><a v-if="!p.assinado_em && linkAviso(() => avisoPonto(c, p))" :href="!p.assinado_em && linkAviso(() => avisoPonto(c, p))" target="_blank" rel="noopener" class="h-7 px-2.5 rounded-lg bg-[#1DA851] hover:bg-[#178F44] text-white text-[11px] font-semibold inline-flex items-center gap-1 ml-2" title="Enviar aviso pelo WhatsApp"><AppIcon name="whatsapp" size="12" />Avisar</a></span>
              </li>
              <li v-if="!c.ponto" class="px-3 py-3 text-sm text-slate-400">Nenhum espelho importado.</li>
            </ul>
          </section>

          <!-- Cadernos -->
          <section>
            <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Cadernos</h4>
            <ul class="divide-y divide-slate-100 border border-slate-100 rounded-xl">
              <li v-for="(d, i) in c.cadernos.detalhe" :key="i" class="px-3 py-2 flex justify-between gap-2 text-sm">
                <span class="text-slate-800 truncate">{{ d.titulo }}<span v-if="!d.obrigatorio" class="text-xs text-slate-400"> · opcional</span></span>
                <span class="flex items-center shrink-0"><span class="text-xs" :class="d.lido_em ? 'text-emerald-700' : (d.obrigatorio ? 'text-amber-700' : 'text-slate-400')">{{ d.lido_em || 'Não leu' }}</span><a v-if="!d.lido_em && linkAviso(() => avisoCaderno(c, d))" :href="!d.lido_em && linkAviso(() => avisoCaderno(c, d))" target="_blank" rel="noopener" class="h-7 px-2.5 rounded-lg bg-[#1DA851] hover:bg-[#178F44] text-white text-[11px] font-semibold inline-flex items-center gap-1 ml-2" title="Enviar aviso pelo WhatsApp"><AppIcon name="whatsapp" size="12" />Avisar</a></span>
              </li>
              <li v-if="!c.cadernos.detalhe.length" class="px-3 py-3 text-sm text-slate-400">Nenhum caderno para este colaborador.</li>
            </ul>
          </section>
        </div>

        <!-- Ações -->
        <div class="px-5 py-4 border-t border-slate-100 grid grid-cols-2 gap-2 safe-bottom">
          <button type="button" @click="emit('abrir-painel')" class="h-11 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 hover:bg-slate-50 cursor-pointer">
            Abrir no Painel do RH
          </button>
          <span v-if="!pendencias.length" class="h-11 rounded-xl bg-emerald-50 text-emerald-800 text-sm font-semibold flex items-center justify-center">Tudo em dia</span>
          <span v-else class="h-11 rounded-xl bg-amber-50 text-amber-800 text-xs font-semibold flex items-center justify-center text-center px-2">{{ pendencias.length }} pendência(s) · use "Avisar" em cada item</span>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';
import AppIcon from './AppIcon.vue';
import { usarFecharComEsc } from '../utils/fecharComEsc.js';
import { avisoCaderno, avisoPonto, avisoHolerite } from '../utils/avisosWhatsapp.js';

/** Resumo de um colaborador para o RH (linha do /api/rh/resumo). */
const props = defineProps({
  colaborador: { type: Object, required: true },
  ano: { type: [String, Number], required: true },
  podeCobrar: { type: Boolean, default: false }
});
const emit = defineEmits(['close', 'abrir-painel']);
usarFecharComEsc(() => emit('close'));

const c = computed(() => props.colaborador);

const indicadores = computed(() => [
  { rotulo: 'Holerites', valor: `${c.value.holerites.recebidos}/${c.value.holerites.total}`, ok: c.value.holerites.recebidos === c.value.holerites.total },
  { rotulo: 'Ponto', valor: !c.value.ponto ? '-' : c.value.ponto.assinado_em ? 'Assinado' : 'Pendente', ok: !c.value.ponto || !!c.value.ponto.assinado_em },
  { rotulo: 'Cadernos', valor: `${c.value.cadernos.obrigatorios_lidos}/${c.value.cadernos.obrigatorios_total}`, ok: c.value.cadernos.obrigatorios_lidos === c.value.cadernos.obrigatorios_total }
]);

const pendencias = computed(() => {
  const itens = [];
  if (c.value.holerites.recebidos < c.value.holerites.total) itens.push(`• ${c.value.holerites.total - c.value.holerites.recebidos} holerite(s) para abrir`);
  (c.value.ponto?.competencias || []).filter((p) => !p.assinado_em).forEach((p) => itens.push(`• espelho de ponto de ${p.mes_nome} para assinar`));
  const naoLidos = c.value.cadernos.detalhe.filter((d) => d.obrigatorio && !d.lido_em).map((d) => d.titulo);
  if (naoLidos.length) itens.push(`• ler: ${naoLidos.join(', ')}`);
  return itens;
});

// Link de aviso de um item, só quando o RH pode enviar e o colaborador tem telefone
const linkAviso = (gerar) => (props.podeCobrar && c.value.telefone ? gerar() : null);
</script>
