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

      <!-- Documento (ficha completa, no mesmo leiaute da ficha em papel) -->
      <div class="bg-white rounded-2xl border border-slate-200 p-3 sm:p-7 text-slate-900 font-mono text-[11px] sm:text-xs">
        <!-- Empresa / CNPJ / Competência -->
        <div class="border border-slate-800 px-3 py-2 flex flex-wrap items-end justify-between gap-x-4 gap-y-1">
          <div class="min-w-0">
            <p class="text-sm sm:text-base font-bold uppercase leading-tight">{{ ficha.empresa }}</p>
            <p>{{ ficha.cnpj }}</p>
          </div>
          <p class="text-sm sm:text-base font-bold whitespace-nowrap">Competência: {{ competenciaPapel(ficha.competencia) }}</p>
        </div>

        <!-- Chapa / Nome / Função / Equipe -->
        <div class="border border-slate-800 mt-2">
          <div class="hidden sm:grid grid-cols-[5rem_minmax(0,2fr)_minmax(0,1.5fr)_4rem] gap-3 px-3 py-1.5 border-b border-slate-800 font-bold uppercase">
            <span>Chapa</span><span>Nome</span><span>Função</span><span class="text-right">Equipe</span>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-[5rem_minmax(0,2fr)_minmax(0,1.5fr)_4rem] gap-x-3 gap-y-2 px-3 py-2 uppercase">
            <div><span class="sm:hidden block font-bold text-[10px]">Chapa</span>{{ ficha.chapa }}</div>
            <div class="col-span-2 sm:col-span-1 order-first sm:order-none"><span class="sm:hidden block font-bold text-[10px]">Nome</span>{{ ficha.nome_colaborador }}</div>
            <div><span class="sm:hidden block font-bold text-[10px]">Função</span>{{ ficha.cargo }}</div>
            <div class="sm:text-right"><span class="sm:hidden block font-bold text-[10px]">Equipe</span>{{ ficha.equipe }}</div>
          </div>
        </div>

        <!-- Declaração (Art. 191 CLT, NR-6 e Art. 462 §1º) -->
        <div class="mt-5 space-y-4 leading-relaxed text-left sm:text-justify">
          <p v-for="(par, i) in DECLARACAO" :key="i" class="indent-10 sm:indent-24">{{ par }}</p>
        </div>

        <!-- Itens: cartões no celular -->
        <div class="sm:hidden mt-5 space-y-2">
          <div v-for="(it, i) in ficha.itens" :key="i" class="border border-slate-300 rounded-lg p-2.5 space-y-1.5">
            <div class="font-bold leading-tight uppercase">{{ it.descricao }}</div>
            <div class="grid grid-cols-2 gap-x-3 gap-y-1 text-[11px]">
              <span><b>Data:</b> {{ dataCurta(it.data) }}</span>
              <span><b>Quant:</b> {{ it.quantidade }}</span>
              <span><b>Unid:</b> {{ it.unidade }}</span>
              <span><b>Código:</b> {{ it.codigo }}</span>
              <span><b>C.A.:</b> {{ it.ca }}</span>
              <span><b>R$ Valor:</b> {{ valorPapel(it.valor) }}</span>
              <span class="col-span-2"><b>Situação:</b> {{ it.situacao }}</span>
            </div>
          </div>
        </div>
        <!-- Itens: tabela no desktop, sem bordas como no papel -->
        <div class="hidden sm:block mt-6">
          <table class="w-full text-left">
            <thead class="font-bold uppercase">
              <tr>
                <th class="py-1 pr-2">Data</th><th class="py-1 pr-2">Quant</th><th class="py-1 pr-2">Unid</th><th class="py-1 pr-2">Código</th>
                <th class="py-1 pr-2">Descrição do equipamento de proteção individual</th><th class="py-1 pr-2">C.A.</th>
                <th class="py-1 pr-2">R$ Valor</th><th class="py-1">Situação</th>
              </tr>
            </thead>
            <tbody class="align-top">
              <tr v-for="(it, i) in ficha.itens" :key="i">
                <td class="py-0.5 pr-2 whitespace-nowrap">{{ dataCurta(it.data) }}</td>
                <td class="py-0.5 pr-2 text-center">{{ it.quantidade }}</td>
                <td class="py-0.5 pr-2">{{ it.unidade }}</td>
                <td class="py-0.5 pr-2">{{ it.codigo }}</td>
                <td class="py-0.5 pr-2 uppercase">{{ it.descricao }}</td>
                <td class="py-0.5 pr-2">{{ it.ca }}</td>
                <td class="py-0.5 pr-2 text-right whitespace-nowrap">{{ valorPapel(it.valor) }}</td>
                <td class="py-0.5 whitespace-nowrap">{{ it.situacao }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Local e data + assinatura, alinhados à direita como no papel -->
        <div class="mt-8 flex flex-col items-center sm:items-end">
          <p class="sm:pr-10">{{ localData(ficha) }}</p>
          <div class="mt-6 w-full max-w-xs text-center space-y-1">
            <div class="h-16 flex items-end justify-center border-b border-slate-700">
              <img v-if="ficha.assinatura?.imagem_base64" :src="ficha.assinatura.imagem_base64" alt="Assinatura" class="max-h-16 max-w-full object-contain" />
              <span v-else-if="assinada(ficha)" class="text-[11px] text-slate-400 italic pb-1">Assinada eletronicamente</span>
            </div>
            <p class="uppercase">{{ ficha.nome_colaborador }}</p>
            <p v-if="assinada(ficha)" class="text-[10px] text-slate-400 break-all normal-case font-sans">
              Assinado em {{ ficha.assinatura.data_hora }} · Código {{ ficha.assinatura.hash_autenticidade }}
            </p>
          </div>
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

// Os dois parágrafos da ficha em papel
const DECLARACAO = [
  'Declaro para fins de direito que recebi no mês em epígrafe os Equipamentos de Proteção Individual abaixo relacionados, fornecidos gratuitamente pela empresa e destinados exclusivamente a minha proteção contra Acidentes e/ou doenças, nos termos Art.191 da C.L.T. e com a NR-6 da Portaria nº 3.214/78.',
  'Nos termos do Art. 462 §1º da CLT, a não devolução do equipamento anterior ou constatado dano por mau uso ou negligência, autorizo o desconto em folha de pagamento do valor equivalente ao EPI, conforme valores e situação abaixo.',
];

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

// "12/2024" -> "12/ 2024", como no papel
function competenciaPapel(c) {
  return String(c || '').replace('/', '/ ');
}
// "20/12/2024" -> "20/12/24"
function dataCurta(d) {
  return String(d || '').replace(/^(\d{2}\/\d{2}\/)\d{2}(\d{2})$/, '$1$2');
}
// "35,00" -> "35.00" (a ficha em papel usa ponto decimal)
function valorPapel(v) {
  return String(v ?? '').replace(',', '.');
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

// Abre a ficha formatada em janela própria para imprimir ou salvar em PDF (mesmo leiaute do papel)
function imprimir(f) {
  const janela = window.open('', '_blank');
  if (!janela) return;
  const linhas = f.itens.map((it) => `<tr><td>${esc(dataCurta(it.data))}</td><td class="c">${esc(it.quantidade)}</td><td>${esc(it.unidade)}</td><td>${esc(it.codigo)}</td><td class="u">${esc(it.descricao)}</td><td>${esc(it.ca)}</td><td class="r">${esc(valorPapel(it.valor))}</td><td>${esc(it.situacao)}</td></tr>`).join('');
  const sig = f.assinatura?.imagem_base64 ? `<img src="${esc(f.assinatura.imagem_base64)}" style="max-height:60px">` : '';
  const aut = assinada(f) ? `<p class="aut">Assinado eletronicamente em ${esc(f.assinatura.data_hora)} · Código de autenticidade ${esc(f.assinatura.hash_autenticidade)}</p>` : '';
  const decl = DECLARACAO.map((p) => `<p class="decl">${esc(p)}</p>`).join('');
  janela.document.write(`<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Ficha de EPI ${esc(f.competencia)}</title>
<style>
  @page{size:A4;margin:12mm}
  body{font-family:"Courier New",Courier,monospace;color:#000;font-size:11px;margin:0}
  .box{border:1px solid #000;padding:6px 8px;margin-bottom:6px}
  .topo{display:flex;justify-content:space-between;align-items:flex-end}
  .emp{font-size:14px;font-weight:bold;text-transform:uppercase}
  .comp{font-size:14px;font-weight:bold}
  .id{display:grid;grid-template-columns:70px 2fr 1.5fr 60px;gap:10px;text-transform:uppercase}
  .id.h{font-weight:bold;border-bottom:1px solid #000;padding-bottom:4px;margin-bottom:4px}
  .id .e{text-align:right}
  p.decl{text-align:justify;line-height:1.45;margin:14px 0 0;text-indent:90px}
  table{width:100%;border-collapse:collapse;margin-top:28px}
  th,td{padding:1px 6px 1px 0;text-align:left;vertical-align:top}
  th{text-transform:uppercase}
  td.r{text-align:right}td.c{text-align:center}td.u{text-transform:uppercase}
  .rod{margin-top:36px;display:flex;flex-direction:column;align-items:flex-end}
  .rod .loc{padding-right:40px}
  .assin{margin-top:30px;width:300px;text-align:center}
  .assin .l{border-top:1px solid #000;padding-top:4px;text-transform:uppercase}
  .aut{font-family:Arial,sans-serif;font-size:9px;color:#444;word-break:break-all;margin-top:6px}
</style></head><body>
<div class="box topo"><div><div class="emp">${esc(f.empresa)}</div>${esc(f.cnpj)}</div><div class="comp">Competência: ${esc(competenciaPapel(f.competencia))}</div></div>
<div class="box"><div class="id h"><div>Chapa</div><div>Nome</div><div>Função</div><div class="e">Equipe</div></div><div class="id"><div>${esc(f.chapa)}</div><div>${esc(f.nome_colaborador)}</div><div>${esc(f.cargo)}</div><div class="e">${esc(f.equipe)}</div></div></div>
${decl}
<table><thead><tr><th>Data</th><th>Quant</th><th>Unid</th><th>Código</th><th>Descrição do equipamento de proteção individual</th><th>C.A.</th><th>R$ Valor</th><th>Situação</th></tr></thead><tbody>${linhas}</tbody></table>
<div class="rod"><p class="loc">${esc(localData(f))}</p><div class="assin">${sig}<div class="l">${esc(f.nome_colaborador)}</div>${aut}</div></div>
<script>window.onload=()=>{window.print();}<\/script></body></html>`);
  janela.document.close();
}
</script>
