<template>
  <div class="space-y-6 animate-slide-up">
    <div v-if="!fichas.length" class="bg-white rounded-2xl border border-slate-200 py-16 text-center space-y-1">
      <AppIcon name="hard-hat" size="28" class="mx-auto text-slate-300" />
      <p class="text-sm text-slate-500">Nenhuma ficha de EPI disponível no momento.</p>
    </div>

    <template v-else>
      <!-- Seletor de competência -->
      <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 space-y-3">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h3 class="text-base font-semibold text-slate-900">Ficha de EPI</h3>
            <p class="text-xs text-slate-400">{{ fichas.length }} {{ fichas.length === 1 ? 'competência' : 'competências' }} · Art. 191 da CLT e NR-6</p>
          </div>
          <span v-if="totalPendentes > 0" class="text-xs font-semibold px-3 py-1 rounded-full bg-amber-100 text-amber-700 whitespace-nowrap">
            {{ totalPendentes }} {{ totalPendentes === 1 ? 'pendente' : 'pendentes' }}
          </span>
        </div>
        <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
          <button
            v-for="f in fichas"
            :key="f.id"
            type="button"
            @click="selecionadaId = f.id"
            class="shrink-0 px-4 h-11 rounded-xl border text-sm font-semibold flex items-center gap-2 cursor-pointer transition"
            :class="f.id === ficha.id ? 'bg-[#141414] text-white border-[#141414]' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
          >
            <span>{{ f.competencia }}</span>
            <span class="w-2 h-2 rounded-full" :class="assinada(f) ? 'bg-emerald-500' : 'bg-amber-400'"></span>
          </button>
        </div>
      </div>

      <!-- Resumo + ações -->
      <div class="bg-gradient-to-br from-[#141414] to-[#000000] text-white rounded-2xl p-5 sm:p-6 shadow-sm space-y-4">
        <div class="flex items-center gap-2.5 flex-wrap">
          <h3 class="text-2xl sm:text-3xl font-semibold tracking-tight">{{ ficha.competencia }}</h3>
          <span
            class="text-xs font-semibold px-3 py-1 rounded-full"
            :class="assinada(ficha) ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-400/15 text-amber-300'"
          >{{ assinada(ficha) ? '✓ Assinada' : 'Falta assinar' }}</span>
        </div>
        <p class="text-sm text-white/70">
          {{ ficha.itens.length }} {{ ficha.itens.length === 1 ? 'item' : 'itens' }} · Total R$ {{ totalValor(ficha) }}
        </p>
        <div class="grid grid-cols-1 gap-3" :class="assinada(ficha) ? 'sm:grid-cols-1 sm:max-w-xs' : 'sm:grid-cols-2'">
          <button
            v-if="!assinada(ficha)"
            type="button"
            @click="entregaParaAssinar = ficha"
            class="h-14 sm:h-12 rounded-2xl sm:rounded-full bg-[#9c2337] hover:bg-[#831b2d] text-white active:scale-[0.98] font-semibold text-base sm:text-sm flex items-center justify-center gap-2 transition cursor-pointer"
          >
            <AppIcon name="pen-tool" size="18" />
            <span>Conferi e quero assinar</span>
          </button>
          <button
            type="button"
            @click="imprimir(ficha)"
            class="h-14 sm:h-12 rounded-2xl sm:rounded-full bg-white/10 hover:bg-white/20 border border-white/20 text-white active:scale-[0.98] font-semibold text-base sm:text-sm flex items-center justify-center gap-2 transition cursor-pointer"
          >
            <AppIcon name="download" size="18" />
            <span>Imprimir / salvar PDF</span>
          </button>
        </div>
      </div>

      <!-- Documento (ficha completa) -->
      <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-7 space-y-4 text-slate-800">
        <div class="border-b-2 border-slate-800 pb-3 flex flex-wrap items-center justify-between gap-2">
          <div>
            <h2 class="text-sm sm:text-base font-bold uppercase">Ficha de Entrega de EPI</h2>
            <p class="text-xs font-semibold">{{ ficha.empresa }}</p>
            <p class="text-[11px] text-slate-500">CNPJ: {{ ficha.cnpj }}</p>
          </div>
          <div class="text-right">
            <span class="text-[10px] text-slate-400 block uppercase font-bold">Competência</span>
            <strong class="text-lg">{{ ficha.competencia }}</strong>
          </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs bg-slate-50 border border-slate-200 rounded-xl p-3">
          <div><span class="text-slate-400 block text-[10px] font-bold">CHAPA</span><strong>{{ ficha.chapa }}</strong></div>
          <div class="col-span-2 sm:col-span-1"><span class="text-slate-400 block text-[10px] font-bold">NOME</span><strong>{{ ficha.nome_colaborador }}</strong></div>
          <div><span class="text-slate-400 block text-[10px] font-bold">FUNÇÃO</span><strong>{{ ficha.cargo }}</strong></div>
          <div><span class="text-slate-400 block text-[10px] font-bold">EQUIPE</span><strong>{{ ficha.equipe }}</strong></div>
        </div>

        <p class="text-[11px] sm:text-xs leading-relaxed text-justify text-slate-600">{{ DECLARACAO }}</p>

        <!-- Itens: cartões no celular -->
        <div class="sm:hidden space-y-2">
          <div v-for="(it, i) in ficha.itens" :key="i" class="border border-slate-200 rounded-xl p-3 text-xs space-y-1.5">
            <div class="font-semibold text-slate-900 leading-tight">{{ it.descricao }}</div>
            <div class="grid grid-cols-2 gap-x-3 gap-y-1 text-[11px]">
              <span><span class="text-slate-400">Data:</span> {{ it.data }}</span>
              <span><span class="text-slate-400">Quant.:</span> {{ it.quantidade }} {{ it.unidade }}</span>
              <span><span class="text-slate-400">Código:</span> {{ it.codigo }}</span>
              <span><span class="text-slate-400">C.A.:</span> {{ it.ca }}</span>
              <span><span class="text-slate-400">R$ Valor:</span> {{ it.valor }}</span>
              <span><span class="text-slate-400">Situação:</span> {{ it.situacao }}</span>
            </div>
          </div>
        </div>
        <!-- Itens: tabela no desktop -->
        <div class="hidden sm:block border border-slate-300 rounded-xl overflow-hidden">
          <table class="w-full text-left text-[11px]">
            <thead class="bg-slate-100 border-b border-slate-300 font-bold uppercase text-slate-600">
              <tr>
                <th class="p-2">Data</th><th class="p-2">Quant</th><th class="p-2">Unid</th><th class="p-2">Código</th>
                <th class="p-2">Descrição do equipamento de proteção individual</th><th class="p-2">C.A.</th>
                <th class="p-2 text-right">R$ Valor</th><th class="p-2">Situação</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              <tr v-for="(it, i) in ficha.itens" :key="i">
                <td class="p-2 whitespace-nowrap">{{ it.data }}</td>
                <td class="p-2">{{ it.quantidade }}</td>
                <td class="p-2">{{ it.unidade }}</td>
                <td class="p-2 font-mono">{{ it.codigo }}</td>
                <td class="p-2 font-semibold">{{ it.descricao }}</td>
                <td class="p-2 font-mono">{{ it.ca }}</td>
                <td class="p-2 text-right">{{ it.valor }}</td>
                <td class="p-2 whitespace-nowrap">{{ it.situacao }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <p class="text-xs">{{ localData(ficha) }}</p>

        <div class="pt-2 text-center sm:max-w-sm space-y-1">
          <div class="h-16 flex items-end justify-center border-b border-slate-700">
            <img v-if="ficha.assinatura?.imagem_base64" :src="ficha.assinatura.imagem_base64" alt="Assinatura" class="max-h-16 max-w-full object-contain" />
            <span v-else-if="assinada(ficha)" class="text-[11px] text-slate-400 italic pb-1">Assinada eletronicamente</span>
          </div>
          <p class="text-xs font-bold">{{ ficha.nome_colaborador }}</p>
          <p v-if="assinada(ficha)" class="text-[10px] text-slate-400 break-all">
            Assinado em {{ ficha.assinatura.data_hora }} · Código {{ ficha.assinatura.hash_autenticidade }}
          </p>
        </div>
      </div>
    </template>

    <EpiSignatureModal
      v-if="entregaParaAssinar"
      :entrega="entregaParaAssinar"
      @close="entregaParaAssinar = null"
      @assinado="aoAssinar"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppIcon from './AppIcon.vue';
import EpiSignatureModal from './EpiSignatureModal.vue';

const props = defineProps({
  epis: { type: Object, default: () => ({ pendentes: [], assinados: [] }) },
});
const emit = defineEmits(['assinado']);

const DECLARACAO = 'Declaro para fins de direito que recebi no mês em epígrafe os Equipamentos de Proteção Individual abaixo relacionados, fornecidos gratuitamente pela empresa e destinados exclusivamente à minha proteção contra Acidentes e/ou doenças, nos termos Art.191 da C.L.T. e com a NR-6 da Portaria nº 3.214/78. Nos termos do Art. 462 §1º da CLT, a não devolução do equipamento anterior ou constatado dano por mau uso ou negligência, autorizo o desconto em folha de pagamento do valor equivalente ao EPI, conforme valores e situação abaixo.';

const MESES = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

const entregaParaAssinar = ref(null);
const selecionadaId = ref(null);

const chaveComp = (c) => (c || '').split('/').reverse().join('-');
const fichas = computed(() =>
  [...(props.epis?.pendentes || []), ...(props.epis?.assinados || [])]
    .sort((a, b) => chaveComp(b.competencia).localeCompare(chaveComp(a.competencia)) || (b.data_entrega || '').localeCompare(a.data_entrega || ''))
);
const ficha = computed(() => fichas.value.find((f) => f.id === selecionadaId.value) || fichas.value[0]);
const totalPendentes = computed(() => fichas.value.filter((f) => !assinada(f)).length);

function assinada(f) {
  return f.status === 'assinado';
}

function numero(v) {
  const n = parseFloat(String(v ?? '').replace(/\./g, '').replace(',', '.'));
  return Number.isFinite(n) ? n : 0;
}
function totalValor(f) {
  return f.itens.reduce((s, it) => s + numero(it.valor), 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function localData(f) {
  if (f.local_data) return f.local_data;
  const [y, m, d] = (f.data_entrega || '').split('-');
  return y ? `São Bernardo do Campo, ${parseInt(d, 10)} de ${MESES[parseInt(m, 10) - 1]} de ${y}` : '';
}

function aoAssinar(entrega) {
  entregaParaAssinar.value = null;
  if (entrega?.id) selecionadaId.value = entrega.id;
  emit('assinado', entrega);
}

const esc = (v) => String(v ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

// Abre a ficha formatada em janela própria para imprimir ou salvar em PDF
function imprimir(f) {
  const janela = window.open('', '_blank');
  if (!janela) return;
  const linhas = f.itens.map((it) => `<tr><td>${esc(it.data)}</td><td>${esc(it.quantidade)}</td><td>${esc(it.unidade)}</td><td>${esc(it.codigo)}</td><td>${esc(it.descricao)}</td><td>${esc(it.ca)}</td><td class="r">${esc(it.valor)}</td><td>${esc(it.situacao)}</td></tr>`).join('');
  const sig = f.assinatura?.imagem_base64 ? `<img src="${esc(f.assinatura.imagem_base64)}" style="max-height:60px">` : '';
  const aut = assinada(f) ? `<p class="aut">Assinado eletronicamente em ${esc(f.assinatura.data_hora)} · Código de autenticidade ${esc(f.assinatura.hash_autenticidade)}</p>` : '';
  janela.document.write(`<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Ficha de EPI ${esc(f.competencia)}</title>
<style>
  @page{size:A4;margin:12mm}
  body{font-family:Arial,sans-serif;color:#141414;font-size:11px;margin:0}
  .topo{border-bottom:3px solid #9c2337;padding-bottom:8px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:flex-end}
  h1{font-size:16px;margin:0 0 3px;text-transform:uppercase}
  .id{display:grid;grid-template-columns:1fr 2fr 2fr 1fr;gap:8px;border:1px solid #ccc;padding:8px;margin-bottom:10px}
  .id span{display:block;color:#666;font-size:9px;font-weight:bold}
  p.decl{text-align:justify;line-height:1.45;margin:0 0 10px}
  table{width:100%;border-collapse:collapse}th,td{border:1px solid #999;padding:4px 5px;text-align:left}th{font-size:9px;text-transform:uppercase;background:#eee}td.r{text-align:right}
  .assin{margin:40px 0 0;width:300px;text-align:center}.assin .l{border-top:1px solid #000;padding-top:4px;font-weight:bold}
  .aut{font-size:9px;color:#444;word-break:break-all}
</style></head><body>
<div class="topo"><div><h1>Ficha de Entrega de EPI</h1><strong>${esc(f.empresa)}</strong><br>CNPJ: ${esc(f.cnpj)}</div><div style="text-align:right"><span style="font-size:9px;color:#666">COMPETÊNCIA</span><br><strong style="font-size:16px">${esc(f.competencia)}</strong></div></div>
<div class="id"><div><span>CHAPA</span>${esc(f.chapa)}</div><div><span>NOME</span>${esc(f.nome_colaborador)}</div><div><span>FUNÇÃO</span>${esc(f.cargo)}</div><div><span>EQUIPE</span>${esc(f.equipe)}</div></div>
<p class="decl">${esc(DECLARACAO)}</p>
<table><thead><tr><th>Data</th><th>Quant</th><th>Unid</th><th>Código</th><th>Descrição do equipamento de proteção individual</th><th>C.A.</th><th>R$ Valor</th><th>Situação</th></tr></thead><tbody>${linhas}</tbody></table>
<p style="margin-top:14px">${esc(localData(f))}</p>
<div class="assin">${sig}<div class="l">${esc(f.nome_colaborador)}</div></div>
${aut}
<script>window.onload=()=>{window.print();}<\/script></body></html>`);
  janela.document.close();
}
</script>
