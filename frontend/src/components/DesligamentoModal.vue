<template>
  <Teleport to="body">
    <div v-if="aberto && dados" class="fixed inset-0 z-[75] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/60 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="desligamento-titulo">
      <div class="bg-white w-full sm:max-w-lg max-h-[92vh] rounded-t-3xl sm:rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-slate-200 animate-slide-up" @click.stop>
        <div class="px-6 pt-6 pb-4 space-y-1.5">
          <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-[#9c2337] bg-[#fdf2f4] border border-rose-200 px-2.5 py-1 rounded-full">
            <AppIcon name="calendar" size="12" />
            Desligamento
          </span>
          <h3 id="desligamento-titulo" class="text-xl font-semibold text-slate-900 tracking-tight">Agende sua homologação</h3>
          <p class="text-xs text-slate-600 leading-relaxed">
            Seu desligamento da {{ dados.empresa }}<template v-if="dados.data_demissao"> foi registrado em {{ dataBr(dados.data_demissao) }}</template>.
            Para concluir, agende pelos links abaixo e depois toque em <strong>Já agendei</strong>.
          </p>
        </div>

        <div class="overflow-y-auto px-6 pb-2 space-y-3">
          <section v-for="item in itens" :key="item.tipo" class="rounded-2xl border p-4 space-y-3" :class="item.agendado ? 'border-emerald-200 bg-emerald-50/60' : 'border-slate-200'">
            <div class="flex items-start gap-3">
              <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" :class="item.agendado ? 'bg-emerald-100 text-emerald-700' : 'bg-[#fdf2f4] text-[#9c2337]'">
                <AppIcon :name="item.agendado ? 'check' : item.icone" size="16" />
              </span>
              <div class="min-w-0 flex-1">
                <h4 class="text-sm font-semibold text-slate-900">{{ item.titulo }}</h4>
                <p class="text-xs text-slate-500 leading-relaxed">{{ item.agendado ? item.feito : item.descricao }}</p>
              </div>
            </div>
            <div v-if="!item.agendado" class="flex flex-col sm:flex-row gap-2">
              <a :href="item.link" target="_blank" rel="noopener" class="sm:flex-1 h-11 rounded-xl bg-[#9c2337] hover:bg-[#831b2d] text-white text-sm font-semibold flex items-center justify-center gap-1.5">
                Abrir agenda
                <AppIcon name="chevron-right" size="14" />
              </a>
              <button type="button" @click="marcar(item.tipo, true)" :disabled="salvando === item.tipo" class="sm:flex-1 h-11 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold cursor-pointer disabled:opacity-60">
                {{ salvando === item.tipo ? 'Salvando...' : 'Já agendei' }}
              </button>
            </div>
            <button v-else-if="item.podeDesfazer" type="button" @click="marcar(item.tipo, false)" class="text-[11px] text-slate-500 underline cursor-pointer">Ainda não agendei</button>
          </section>

          <button v-if="dados.tem_guia" type="button" @click="abrirGuia" class="w-full flex items-center gap-3 text-left rounded-2xl border border-slate-200 px-4 py-3 hover:bg-slate-50 cursor-pointer">
            <span class="w-9 h-9 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center shrink-0"><AppIcon name="document" size="16" /></span>
            <span class="min-w-0 flex-1">
              <span class="block text-sm font-semibold text-slate-900">Guia de Desligamento</span>
              <span class="block text-xs text-slate-500">Documentos, verbas rescisórias, FGTS, seguro-desemprego e prevenção a golpes.</span>
            </span>
            <AppIcon name="download" size="16" class="text-slate-400 shrink-0" />
          </button>
          <p v-if="erro" class="text-xs font-medium text-rose-700">{{ erro }}</p>
        </div>

        <div class="px-6 py-4 border-t border-slate-100">
          <button type="button" @click="$emit('fechar')" class="w-full h-11 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold cursor-pointer">Lembrar depois</button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppIcon from './AppIcon.vue';

const props = defineProps({
  aberto: { type: Boolean, default: false },
  dados: { type: Object, default: null }
});
const emit = defineEmits(['fechar', 'atualizado']);

const salvando = ref('');
const erro = ref('');
const token = () => localStorage.getItem('vb_auth_token') || '';
const dataBr = (iso) => (iso ? iso.slice(0, 10).split('-').reverse().join('/') : '');
const dataHoraBr = (iso) => (iso ? `${dataBr(iso)} às ${iso.slice(11, 16)}` : '');

const itens = computed(() => {
  const d = props.dados;
  const rh = d.homologacao.pelo_rh;
  return [
    {
      tipo: 'homologacao',
      icone: 'calendar',
      titulo: 'Homologação com o DP',
      descricao: 'Escolha o dia e o horário para a homologação da sua rescisão com o Departamento Pessoal.',
      feito: rh ? `Agendada pelo RH: ${dataHoraBr(rh.data_hora)} · ${rh.sindicato}${rh.local ? ' (' + rh.local + ')' : ''}.` : `Você informou que agendou${d.homologacao.agendado_em ? ' em ' + dataBr(d.homologacao.agendado_em) : ''}.`,
      link: d.links.homologacao,
      agendado: d.homologacao.agendado,
      podeDesfazer: !rh
    },
    {
      tipo: 'documentos',
      icone: 'document',
      titulo: 'Retirada da documentação',
      descricao: 'Agende quando vai buscar os documentos da rescisão.',
      feito: `Você informou que agendou${d.documentos.agendado_em ? ' em ' + dataBr(d.documentos.agendado_em) : ''}.`,
      link: d.links.documentos,
      agendado: d.documentos.agendado,
      podeDesfazer: true
    }
  ];
});

async function marcar(tipo, agendado) {
  salvando.value = tipo;
  erro.value = '';
  try {
    const res = await fetch('/api/desligamento/agendado', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token()}` },
      body: JSON.stringify({ tipo, agendado })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.erro);
    emit('atualizado', data.desligamento);
  } catch (e) {
    erro.value = e.message || 'Não foi possível salvar. Tente novamente.';
  } finally {
    salvando.value = '';
  }
}

// Abre o PDF numa aba nova (aberta já no clique para o navegador não bloquear)
async function abrirGuia() {
  const aba = window.open('', '_blank');
  try {
    const res = await fetch('/api/desligamento/guia', { headers: { Authorization: `Bearer ${token()}` } });
    if (!res.ok) throw new Error();
    const url = URL.createObjectURL(await res.blob());
    if (aba) aba.location.href = url;
    else window.location.href = url;
  } catch (e) {
    aba?.close();
    erro.value = 'Não foi possível abrir o guia.';
  }
}
</script>
