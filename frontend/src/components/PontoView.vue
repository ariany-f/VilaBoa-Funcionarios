<template>
  <div class="space-y-6 animate-slide-up">
    <div v-if="carregando" class="py-16 text-center text-sm text-slate-400">Carregando espelho de ponto...</div>

    <div v-else-if="!ultimo" class="bg-white rounded-2xl border border-slate-200 py-16 text-center space-y-1">
      <AppIcon name="calendar" size="28" class="mx-auto text-slate-300" />
      <p class="text-sm text-slate-500">{{ erro || 'Nenhum espelho de ponto disponível no momento.' }}</p>
    </div>

    <template v-else>
      <!-- Último espelho (mesmo formato do último holerite) -->
      <div class="w-full bg-gradient-to-br from-[#141414] to-[#000000] text-white rounded-2xl p-6 sm:p-7 shadow-sm relative overflow-hidden space-y-5">
        <div class="absolute -right-20 -top-20 w-80 h-80 bg-[#9c2337]/30 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Identificação do colaborador -->
        <div v-if="colaborador" class="hidden sm:flex flex-wrap items-center justify-between gap-x-4 gap-y-1 relative z-10 text-xs text-white/60">
          <span class="text-sm font-semibold text-white">{{ colaborador.nome }}</span>
          <span class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <span>{{ colaborador.cargo }}</span>
            <span>Matrícula <strong class="text-white/90 font-medium">{{ colaborador.matricula }}</strong></span>
          </span>
        </div>

        <div class="relative z-10 space-y-1.5" :class="colaborador ? 'sm:border-t sm:border-white/10 sm:pt-4' : ''">
          <span class="text-xs text-white/60 font-medium">Seu último espelho de ponto</span>
          <div class="flex items-center gap-2.5 flex-wrap">
            <h3 class="text-3xl font-semibold tracking-tight leading-tight">{{ ultimo.mes_nome }}</h3>
            <span
              class="text-xs font-semibold px-3 py-1 rounded-full"
              :class="ultimo.assinatura ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-400/15 text-amber-300'"
            >{{ ultimo.assinatura ? '✓ Assinado' : 'Falta assinar' }}</span>
          </div>
          <p class="text-sm text-white/70">{{ resumoLinha(ultimo) }}</p>
        </div>

        <div class="relative z-10 grid grid-cols-1 gap-3" :class="podeAssinar(ultimo) ? 'sm:grid-cols-2' : 'sm:grid-cols-1 sm:max-w-xs'">
          <button
            v-if="podeAssinar(ultimo)"
            type="button"
            @click="assinarNoAutentique(ultimo)"
            class="h-14 sm:h-12 rounded-2xl sm:rounded-full bg-[#9c2337] hover:bg-[#831b2d] text-white active:scale-[0.98] font-semibold text-base sm:text-sm flex items-center justify-center gap-2 transition cursor-pointer"
          >
            <AppIcon name="pen-tool" size="18" />
            <span>Conferi e quero assinar</span>
          </button>
          <button
            type="button"
            @click="abrirDetalhe(ultimo.competencia)"
            class="h-14 sm:h-12 rounded-2xl sm:rounded-full bg-white/10 hover:bg-white/20 border border-white/20 text-white active:scale-[0.98] font-semibold text-base sm:text-sm flex items-center justify-center gap-2 transition cursor-pointer"
          >
            <AppIcon name="eye" size="18" />
            <span>Ver espelho</span>
          </button>
        </div>
        <p v-if="!ultimo.assinatura && !somenteLeitura && !ultimo.link_assinatura" class="relative z-10 text-xs text-white/60">
          A assinatura deste mês ainda não foi liberada pelo RH.
        </p>
      </div>

      <!-- Histórico de espelhos (mesmo formato do histórico de holerites) -->
      <div class="w-full bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 space-y-5">
        <div class="border-b border-slate-100 pb-4">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800 leading-tight">Histórico de espelhos</h3>
          <p class="text-xs text-slate-400 mt-0.5 font-medium">{{ competencias.length }} {{ competencias.length === 1 ? 'espelho disponível' : 'espelhos disponíveis' }}</p>
        </div>

        <!-- Celular: cartões com botões grandes -->
        <div class="md:hidden space-y-3">
          <div v-for="c in competencias" :key="c.competencia" class="bg-white rounded-2xl p-4 border border-slate-200 space-y-3">
            <button type="button" @click="abrirDetalhe(c.competencia)" class="w-full flex items-center gap-3 text-left cursor-pointer">
              <div class="w-12 h-12 rounded-xl bg-slate-100 flex flex-col items-center justify-center shrink-0">
                <span class="text-sm font-semibold uppercase text-[#9c2337] leading-none">{{ c.mes_nome.slice(0, 3) }}</span>
                <span class="text-[11px] font-semibold text-slate-900 leading-tight mt-0.5">{{ c.competencia.slice(3) }}</span>
              </div>
              <div class="min-w-0 flex-1">
                <h4 class="font-semibold text-slate-900 text-base leading-tight">{{ c.mes_nome }}</h4>
                <span class="text-xs text-slate-500 block mt-0.5">{{ c.assinado_em ? 'Assinado em ' + c.assinado_em.slice(0, 10) : 'Aguardando sua assinatura' }}</span>
              </div>
              <span class="shrink-0 text-[11px] font-semibold px-2.5 py-1 rounded-full" :class="c.assinado_em ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                {{ c.assinado_em ? '✓ Assinado' : 'Assinar' }}
              </span>
            </button>
            <button type="button" @click="abrirDetalhe(c.competencia)" class="w-full h-12 rounded-xl bg-[#141414] text-white font-semibold text-sm inline-flex items-center justify-center gap-2 cursor-pointer active:scale-[0.98] transition">
              <AppIcon name="eye" size="17" />
              Ver espelho
            </button>
          </div>
        </div>

        <!-- Computador: tabela com botões com texto -->
        <div class="hidden md:block rounded-2xl border border-slate-200 overflow-hidden">
          <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/80 border-b border-slate-200">
              <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                <th class="py-3.5 px-5">Mês</th>
                <th class="py-3.5 px-5">Situação</th>
                <th class="py-3.5 px-5"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
              <tr v-for="c in competencias" :key="c.competencia" @click="abrirDetalhe(c.competencia)" class="hover:bg-slate-50/70 cursor-pointer group">
                <td class="py-4 px-5">
                  <span class="font-semibold text-slate-900 group-hover:text-[#9c2337] text-sm">{{ c.mes_nome }}</span>
                </td>
                <td class="py-4 px-5">
                  <span class="h-6 px-3 rounded-full text-[11px] font-bold text-slate-600 bg-slate-100/90 border border-slate-200 inline-flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full" :class="c.assinado_em ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                    {{ c.assinado_em ? 'Assinado em ' + c.assinado_em.slice(0, 10) : 'Falta assinar' }}
                  </span>
                </td>
                <td class="py-3 px-5 text-right" @click.stop>
                  <div class="flex items-center justify-end gap-2">
                    <button type="button" @click="abrirDetalhe(c.competencia)" class="h-9 px-3.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 text-xs font-semibold inline-flex items-center gap-1.5 cursor-pointer">
                      <AppIcon name="eye" size="14" />
                      Ver
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- Detalhe do mês: espelho completo, com todos os dados do relatório de ponto -->
    <Teleport to="body">
      <div v-if="detalheAberto" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="detalheAberto = false">
        <div class="w-full max-w-6xl bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl flex flex-col max-h-[94vh] sm:max-h-[92vh] overflow-hidden">
          <div class="px-5 pt-3 pb-3 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
            <div class="flex items-center space-x-3 min-w-0">
              <img :src="logoEmpresa" alt="Logo da empresa" class="h-7 w-auto object-contain" />
              <div class="border-l border-slate-200 pl-3 min-w-0">
                <h3 class="text-xs font-bold text-slate-800">Espelho de Ponto</h3>
                <p class="text-[10px] text-slate-500 truncate">{{ espelho?.mes_nome }} · {{ espelho?.assinatura ? 'Assinado em ' + espelho.assinatura.data_hora : 'Falta assinar' }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <button v-if="espelho" type="button" @click="baixarEspelho(espelho)" class="h-9 px-3 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold inline-flex items-center gap-1.5 cursor-pointer border border-slate-200">
                <AppIcon name="printer" size="14" />
                <span class="hidden sm:inline">Imprimir / PDF</span>
              </button>
              <button type="button" @click="detalheAberto = false" class="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center cursor-pointer border border-slate-200" aria-label="Fechar">
                <AppIcon name="x" size="14" />
              </button>
            </div>
          </div>

          <div v-if="carregandoDetalhe || !espelho" class="py-16 text-center text-sm text-slate-400">Carregando...</div>
          <div v-else class="p-4 sm:p-5 overflow-y-auto space-y-4">
            <!-- Identificação (cabeçalho do relatório) -->
            <div class="rounded-2xl border border-slate-200 p-4 grid grid-cols-2 lg:grid-cols-5 gap-x-4 gap-y-3 text-sm">
              <div class="col-span-2 lg:col-span-2"><span class="block text-xs text-slate-500">Colaborador</span><strong class="text-slate-900">{{ nomeColab || '-' }}</strong></div>
              <div><span class="block text-xs text-slate-500">Matrícula</span><strong class="text-slate-900">{{ matriculaColab || '-' }}</strong></div>
              <div><span class="block text-xs text-slate-500">Função</span><strong class="text-slate-900">{{ espelho.funcao || colaborador?.cargo || '-' }}</strong></div>
              <div><span class="block text-xs text-slate-500">Admissão</span><strong class="text-slate-900">{{ espelho.admissao || '-' }}</strong></div>
            </div>

            <!-- Resumo do mês -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
              <div v-for="r in resumo" :key="r.rotulo" class="bg-white rounded-2xl border border-slate-200 p-4">
                <span class="block text-xs text-slate-500">{{ r.rotulo }}</span>
                <span class="block text-xl font-semibold mt-1 tabular-nums" :class="r.cor">{{ r.valor }}</span>
              </div>
            </div>

            <!-- Como ler (explicação didática) -->
            <details class="rounded-2xl border border-sky-100 bg-sky-50/60 p-4 text-xs text-slate-600">
              <summary class="cursor-pointer font-semibold text-sky-800 text-sm select-none">Como ler este espelho</summary>
              <ul class="mt-3 grid sm:grid-cols-2 gap-x-6 gap-y-1.5 list-disc pl-4">
                <li v-for="l in legenda" :key="l[0]"><strong class="text-slate-800">{{ l[0] }}:</strong> {{ l[1] }}</li>
              </ul>
              <p class="mt-3">Encontrou algo diferente do que você trabalhou? Confira cada dia abaixo e fale com o RH <strong>antes de assinar</strong> para contestar.</p>
            </details>

            <!-- Banco de horas -->
            <div v-if="banco.atual" class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 space-y-3">
              <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800 pb-3 border-b border-slate-100">Banco de horas</h3>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="rounded-xl bg-slate-50 p-3">
                  <span class="block text-xs text-slate-500">Saldo anterior</span>
                  <span class="block text-lg font-semibold tabular-nums mt-0.5" :class="corValor(banco.anterior)">{{ banco.anterior }}</span>
                  <span class="block text-[11px] text-slate-400 mt-0.5">O que você tinha ao começar o período</span>
                </div>
                <div class="rounded-xl bg-slate-50 p-3">
                  <span class="block text-xs text-slate-500">Saldo do período <span v-if="banco.periodo?.inicio">({{ dataBR(banco.periodo.inicio) }} a {{ dataBR(banco.periodo.fim) }})</span></span>
                  <span class="flex items-center gap-2 flex-wrap mt-0.5">
                    <span class="text-lg font-semibold tabular-nums" :class="corValor(banco.periodo?.valor)">{{ banco.periodo?.valor }}</span>
                    <span v-if="banco.periodo?.situacao" class="text-[10px] font-bold px-2 py-0.5 rounded-full" :class="banco.periodo.situacao === 'A PAGAR' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">{{ banco.periodo.situacao }}</span>
                  </span>
                  <span class="block text-[11px] text-slate-400 mt-0.5">A pagar: a empresa te deve estas horas · A compensar: horas a repor</span>
                </div>
                <div class="rounded-xl bg-slate-900 text-white p-3">
                  <span class="block text-xs text-white/60">Saldo atual</span>
                  <span class="block text-lg font-semibold tabular-nums mt-0.5">{{ banco.atual }}</span>
                  <span class="block text-[11px] text-white/50 mt-0.5">Saldo anterior + saldo do período</span>
                </div>
              </div>
            </div>

            <!-- Dias do mês -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6">
              <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-100">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800">Dias do mês</h3>
                <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer select-none">
                  <input type="checkbox" v-model="somenteTrabalhados" class="w-4 h-4 accent-[#9c2337]" />
                  Só dias trabalhados
                </label>
              </div>
              <div class="flex flex-wrap gap-x-4 gap-y-1 pt-3 text-[11px] text-slate-500">
                <span class="inline-flex items-center gap-1.5"><i class="w-3 h-3 rounded bg-amber-100 border border-amber-300"></i>Feriado</span>
                <span class="inline-flex items-center gap-1.5"><i class="w-3 h-3 rounded bg-sky-100 border border-sky-300"></i>Abono / compensação</span>
                <span class="inline-flex items-center gap-1.5"><i class="w-3 h-3 rounded bg-rose-100 border border-rose-300"></i>Falta</span>
                <span class="inline-flex items-center gap-1.5"><i class="w-3 h-3 rounded bg-slate-100 border border-slate-300"></i>Folga / descanso</span>
              </div>

              <!-- Celular: um cartão por dia, com todos os campos -->
              <ul class="md:hidden space-y-2 mt-3">
                <li v-for="d in diasVisiveis" :key="d.data" class="rounded-xl border p-3" :class="[classeDia(d), ehHoje(d) ? 'ring-2 ring-amber-300' : '']">
                  <div class="flex items-center justify-between gap-2">
                    <span class="text-sm font-semibold text-slate-900">{{ dataBR(d.data) }} <span class="text-xs font-medium text-slate-500">{{ d.dia_semana }}</span></span>
                    <span v-if="obsDia(d)" class="text-[11px] font-semibold text-slate-600 text-right">{{ obsDia(d) }}</span>
                  </div>
                  <template v-if="temDados(d)">
                    <div class="grid grid-cols-3 gap-x-2 gap-y-1.5 mt-2">
                      <div v-for="m in marcas(d)" :key="m[0]"><span class="block text-[10px] uppercase text-slate-400">{{ m[0] }}</span><span class="block text-sm tabular-nums" :class="m[1] === '--:--' ? 'text-slate-300' : 'text-slate-800'">{{ m[1] }}</span></div>
                    </div>
                    <div class="grid grid-cols-3 gap-x-2 gap-y-1.5 mt-2 pt-2 border-t border-slate-200/70">
                      <div v-for="c in totaisDia(d)" :key="c[0]"><span class="block text-[10px] uppercase text-slate-400">{{ c[0] }}</span><span class="block text-sm tabular-nums" :class="c[2] || (c[1] ? 'text-slate-800' : 'text-slate-300')">{{ c[1] || '–' }}</span></div>
                    </div>
                  </template>
                </li>
              </ul>

              <!-- Computador: tabela completa, igual ao relatório -->
              <div class="hidden md:block overflow-x-auto mt-2">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 text-left">
                      <th class="py-3 px-2">Data</th>
                      <th class="py-3 px-1.5">Ent1</th><th class="py-3 px-1.5">Sai1</th><th class="py-3 px-1.5">Ent2</th><th class="py-3 px-1.5">Sai2</th><th class="py-3 px-1.5">Ent3</th><th class="py-3 px-1.5">Sai3</th>
                      <th class="py-3 px-1.5">Exec</th><th class="py-3 px-1.5">Ad.Not</th><th class="py-3 px-1.5">Atraso</th><th class="py-3 px-1.5">Falta</th><th class="py-3 px-1.5">Abono</th><th class="py-3 px-1.5">Saldo do dia</th>
                      <th class="py-3 px-2">Observação</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="d in diasVisiveis" :key="d.data" class="border-t border-slate-100" :class="[classeDia(d), ehHoje(d) ? 'outline outline-2 outline-amber-300' : '']">
                      <td class="py-2 px-2 whitespace-nowrap"><span class="font-semibold text-slate-900">{{ dataBR(d.data) }}</span> <span class="text-xs text-slate-500">{{ d.dia_semana }}</span></td>
                      <td v-for="m in marcas(d)" :key="m[0]" class="py-2 px-1.5 tabular-nums" :class="m[1] === '--:--' ? 'text-slate-300' : 'text-slate-800'">{{ m[1] }}</td>
                      <td v-for="c in totaisDia(d).slice(0, 5)" :key="c[0]" class="py-2 px-1.5 tabular-nums" :class="c[2] || 'text-slate-800'">{{ c[1] }}</td>
                      <td class="py-2 px-1.5"><span v-if="trabalhado(d) && d.saldo !== '00:00'" class="text-xs font-semibold px-2 py-0.5 rounded-md tabular-nums" :class="corSaldo(d)">{{ d.saldo }}</span></td>
                      <td class="py-2 px-2 text-xs font-medium text-slate-600">{{ obsDia(d) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Eventos do movimento -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6">
              <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800 pb-3 border-b border-slate-100">Eventos do movimento no período</h3>
              <p v-if="!(espelho.eventos || []).length" class="text-xs text-slate-400 pt-3">Nenhum evento no período.</p>
              <table v-else class="w-full text-sm mt-1">
                <thead><tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 text-left"><th class="py-3 px-2">Código</th><th class="py-3 px-2">Descrição</th><th class="py-3 px-2 text-right">Horas</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="ev in espelho.eventos" :key="ev.codigo"><td class="py-2 px-2 tabular-nums">{{ ev.codigo }}</td><td class="py-2 px-2">{{ ev.descricao }}</td><td class="py-2 px-2 text-right tabular-nums font-semibold">{{ ev.horas }}</td></tr>
                </tbody>
              </table>
            </div>

            <!-- Dias ponte -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6">
              <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800 pb-3 border-b border-slate-100">Dias ponte</h3>
              <p class="text-[11px] text-slate-400 pt-2">Dia emendado em feriado ou folga: as horas do período são incrementadas (+) ou decrementadas (−) do seu banco.</p>
              <p v-if="!(espelho.pontes || []).length" class="text-xs text-slate-400 pt-2">Nenhum dia ponte no período.</p>
              <div v-else class="overflow-x-auto">
                <table class="w-full text-sm mt-1">
                  <thead><tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 text-left"><th class="py-3 px-2">Data do dia ponte</th><th class="py-3 px-2">Início</th><th class="py-3 px-2">Fim</th><th class="py-3 px-2 text-right">Horas a incrementar/decrementar</th></tr></thead>
                  <tbody class="divide-y divide-slate-100">
                    <tr v-for="p in espelho.pontes" :key="p.data"><td class="py-2 px-2 tabular-nums">{{ dataBR(p.data) }}</td><td class="py-2 px-2 tabular-nums">{{ dataBR(p.inicio) }}</td><td class="py-2 px-2 tabular-nums">{{ dataBR(p.fim) }}</td><td class="py-2 px-2 text-right tabular-nums font-semibold" :class="corValor(p.horas)">{{ p.horas }}</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Horário no período -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6">
              <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800 pb-3 border-b border-slate-100">Horário no período</h3>
              <p v-if="!(espelho.horario || []).length" class="text-xs text-slate-400 pt-3">Horário não informado.</p>
              <ul v-else class="pt-3 space-y-1 text-sm text-slate-700">
                <li v-for="h in espelho.horario" :key="h.codigo" class="tabular-nums"><strong>{{ h.codigo }}</strong> {{ h.descricao }}</li>
              </ul>
            </div>

            <!-- Assinatura eletrônica -->
            <p v-if="espelho.assinatura" class="text-[11px] text-slate-500 break-all">Assinado eletronicamente em {{ espelho.assinatura.data_hora }} · Código de autenticidade {{ espelho.assinatura.hash_autenticidade }}</p>
          </div>

          <!-- Ações, como no rodapé do demonstrativo -->
          <div v-if="espelho && podeAssinar(espelho)" class="p-3.5 pb-[calc(0.875rem+env(safe-area-inset-bottom,0px))] bg-slate-50 border-t border-slate-200 shrink-0">
            <button
              type="button"
              @click="assinarNoAutentique(espelho)"
              class="w-full h-12 rounded-xl bg-[#9c2337] hover:bg-[#831b2d] text-white font-semibold text-sm flex items-center justify-center gap-2 cursor-pointer"
            >
              <AppIcon name="pen-tool" size="16" />
              Conferi e quero assinar
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { usarFecharComEsc } from '../utils/fecharComEsc.js';
import { logoEmpresa } from '../utils/logoEmpresa.js';
import AppIcon from './AppIcon.vue';
import { apiFetch } from '../utils/api.js';
import { tokenAdmin } from '../utils/sessaoAdmin.js';

const props = defineProps({
  colaborador: { type: Object, default: null },
  // Consulta do RH: espelho de outro colaborador, sem botão de assinar
  matricula: { type: String, default: '' },
  // Competência vinda de um link de aviso (mm/aaaa)
  competenciaInicial: { type: String, default: '' },
  somenteLeitura: { type: Boolean, default: false }
});

const espelho = ref(null);       // mês aberto no detalhe
const ultimo = ref(null);        // mês mais recente (destaque do topo)
const detalheAberto = ref(false);
const carregandoDetalhe = ref(false);
usarFecharComEsc(() => { detalheAberto.value = false; }, detalheAberto);
const competencias = ref([]);
const somenteTrabalhados = ref(false);


// Envia um resumo do mês para o WhatsApp do colaborador, com o link para abrir no portal
function enviarWhatsapp(e, janelaAberta = null) {
  const r = Object.fromEntries(resumoDe(e).map((x) => [x.rotulo, x.valor]));
  const texto = `*Espelho de ponto · ${e.mes_nome}*\n` +
    `Dias trabalhados: ${r['Dias trabalhados']}\n` +
    `Horas no mês: ${r['Horas no mês']}\n` +
    `Saldo do mês: ${r['Saldo do mês']}\n` +
    `Banco de horas: ${r['Banco de horas']}\n` +
    `Situação: ${e.assinatura ? 'assinado em ' + e.assinatura.data_hora : 'falta assinar'}\n\n` +
    `Ver no Portal do Colaborador: ${window.location.origin}${window.location.pathname}#/ponto`;
  const telefone = (props.colaborador?.telefone_atual || '').replace(/\D/g, '');
  const destino = telefone ? `phone=55${telefone}&` : '';
  const url = `https://api.whatsapp.com/send?${destino}text=${encodeURIComponent(texto)}`;
  if (janelaAberta) janelaAberta.location.href = url;
  else window.open(url, '_blank');
}

const nomeColab = computed(() => props.colaborador?.nome || '');
const matriculaColab = computed(() => props.colaborador?.matricula || props.matricula || '');

const SEM = '--:--';
const dataBR = (iso) => (iso ? iso.split('-').reverse().join('/') : '');
const corValor = (v) => (String(v || '').startsWith('-') ? 'text-rose-700' : 'text-emerald-700');
const execDe = (d) => d.exec || (trabalhado(d) ? d.total : '');

// Seis marcações do dia (Ent1 ... Sai3)
const marcas = (d) => [['Ent1', d.e1 || SEM], ['Sai1', d.s1 || SEM], ['Ent2', d.e2 || SEM], ['Sai2', d.s2 || SEM], ['Ent3', d.e3 || SEM], ['Sai3', d.s3 || SEM]];
// Exec, Ad.Not, Atraso, Falta, Abono (+ saldo do dia): [rótulo, valor, classe de cor]
const totaisDia = (d) => [
  ['Exec', execDe(d), ''],
  ['Ad.Not', d.ad_not || '', ''],
  ['Atraso', d.atraso || '', 'text-amber-700 font-semibold'],
  ['Falta', d.falta || '', 'text-rose-700 font-semibold'],
  ['Abono', d.abono || '', 'text-sky-700 font-semibold'],
  ['Saldo', trabalhado(d) && d.saldo !== '00:00' ? d.saldo : '', d.saldo?.startsWith('-') ? 'text-rose-700 font-semibold' : 'text-emerald-700 font-semibold']
];
const temDados = (d) => marcas(d).some((m) => m[1] !== SEM) || totaisDia(d).some((c) => c[1]);
// Texto de feriado (ex.: "N. Sª Aparecida") e ocorrência do dia
function obsDia(d) {
  const tipo = d.tipo || '';
  const partes = [];
  if (!/^Trabalhado( \(Hoje\))?$/.test(tipo)) partes.push(tipo);
  if (d.feriado && !tipo.includes(d.feriado)) partes.push(d.feriado);
  return partes.join(' · ');
}
function classeDia(d) {
  if (d.falta) return 'bg-rose-50 border-rose-200';
  if (d.feriado || (d.tipo || '').startsWith('Feriado')) return 'bg-amber-50 border-amber-200';
  if (d.abono || (d.tipo || '').startsWith('Folga Compens')) return 'bg-sky-50 border-sky-200';
  if (!trabalhado(d)) return 'bg-slate-50 border-slate-200 text-slate-500';
  return 'bg-white border-slate-200';
}

const legenda = [
  ['Ent1 / Sai1 · Ent2 / Sai2 · Ent3 / Sai3', 'horários de entrada e saída que o relógio registrou'],
  ['Exec', 'horas realmente trabalhadas no dia'],
  ['Ad.Not', 'horas de adicional noturno'],
  ['Atraso', 'tempo que faltou para completar a jornada'],
  ['Falta', 'dia (ou horas) sem trabalho e sem justificativa'],
  ['Abono', 'horas justificadas (atestado, compensação, folga concedida)'],
  ['Saldo do dia', 'diferença entre o que você trabalhou e a jornada de 8h'],
  ['A pagar / A compensar', 'a pagar = a empresa te deve horas; a compensar = horas a repor']
];

const banco = computed(() => {
  const b = espelho.value?.banco_horas || {};
  return { anterior: b.saldo_anterior || '', periodo: b.saldo_periodo || null, atual: b.saldo_atual || b.saldo_total || '' };
});

// Abre o espelho completo formatado para imprimir ou salvar em PDF
function baixarEspelho(e, janelaAberta = null) {
  const esc = (v) => String(v ?? '').replace(/[&<>]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]));
  const nome = nomeColab.value;
  const matricula = matriculaColab.value;
  const b = e.banco_horas || {};
  const per = b.saldo_periodo;
  const linhas = e.dias.map((d) => {
    const cls = d.falta ? 'falta' : (d.feriado || (d.tipo || '').startsWith('Feriado')) ? 'feriado' : (d.abono || (d.tipo || '').startsWith('Folga Compens')) ? 'abono' : !trabalhado(d) ? 'folga' : '';
    const cel = [...marcas(d).map((m) => m[1]), ...totaisDia(d).map((c) => c[1])];
    return `<tr class="${cls}"><td class="nw">${dataBR(d.data)} ${esc(d.dia_semana)}</td>${cel.map((v) => `<td>${esc(v)}</td>`).join('')}<td>${esc(obsDia(d))}</td></tr>`;
  }).join('');
  const resumoHtml = resumoDe(e).map((r) => `<div><span>${esc(r.rotulo)}</span><strong>${esc(r.valor)}</strong></div>`).join('');
  const eventos = (e.eventos || []).map((v) => `<tr><td>${esc(v.codigo)}</td><td>${esc(v.descricao)}</td><td class="r">${esc(v.horas)}</td></tr>`).join('') || '<tr><td colspan="3">Nenhum evento no período.</td></tr>';
  const pontes = (e.pontes || []).map((p) => `<tr><td>${dataBR(p.data)}</td><td>${dataBR(p.inicio)}</td><td>${dataBR(p.fim)}</td><td class="r">${esc(p.horas)}</td></tr>`).join('') || '<tr><td colspan="4">Nenhum dia ponte no período.</td></tr>';
  const horario = (e.horario || []).map((h) => `<div><strong>${esc(h.codigo)}</strong> ${esc(h.descricao)}</div>`).join('') || '<div>Horário não informado.</div>';
  const assinatura = e.assinatura
    ? `Assinado eletronicamente em ${esc(e.assinatura.data_hora)} · Código de autenticidade ${esc(e.assinatura.hash_autenticidade)}`
    : 'Espelho ainda não assinado pelo colaborador.';
  const html = `<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Espelho de ponto ${esc(e.mes_nome)}</title>
<style>
  @page{size:A4 landscape;margin:12mm}
  body{font-family:Arial,sans-serif;color:#141414;margin:0;font-size:11px}
  h1{font-size:17px;margin:0}h2{font-size:12px;margin:16px 0 6px;text-transform:uppercase;letter-spacing:.04em}
  .topo{border-bottom:3px solid #9c2337;padding-bottom:8px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:flex-end}
  .id{display:flex;gap:24px;flex-wrap:wrap;margin-bottom:8px}.id span{color:#666;font-size:10px;display:block}
  .resumo{display:flex;gap:8px;margin:8px 0}.resumo div{flex:1;border:1px solid #ddd;border-radius:6px;padding:6px}.resumo span{display:block;color:#666;font-size:10px}.resumo strong{font-size:14px}
  table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:3px 5px;border-bottom:1px solid #e5e5e5}th{font-size:9px;text-transform:uppercase;color:#666;border-bottom:1px solid #999}
  td.r{text-align:right}td.nw{white-space:nowrap}
  tr.folga td{color:#888;background:#f6f6f6}tr.feriado td{background:#fff4d6}tr.abono td{background:#e3f2fb}tr.falta td{background:#fde4e4}
  .banco{display:flex;gap:8px}.banco div{flex:1;border:1px solid #ddd;border-radius:6px;padding:6px}.banco span{display:block;color:#666;font-size:10px}.banco strong{font-size:14px}
  .leg{color:#555;font-size:9.5px;margin-top:6px}.ass{margin-top:14px;font-size:10px;color:#444;word-break:break-all}
  .bloco{break-inside:avoid}tr{break-inside:avoid}thead{display:table-header-group}
  *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
</style></head><body>
<div class="topo"><div><h1>Espelho de Ponto · ${esc(e.mes_nome)}</h1></div><div>${esc(e.empresa || 'Vila Boa Construções e Serviços')}</div></div>
<div class="id"><div><span>Colaborador</span><strong>${esc(nome)}</strong></div><div><span>Matrícula</span><strong>${esc(matricula)}</strong></div><div><span>Função</span><strong>${esc(e.funcao || props.colaborador?.cargo || '')}</strong></div><div><span>Admissão</span><strong>${esc(e.admissao || '')}</strong></div></div>
<div class="resumo">${resumoHtml}</div>
<table><thead><tr><th>Data</th><th>Ent1</th><th>Sai1</th><th>Ent2</th><th>Sai2</th><th>Ent3</th><th>Sai3</th><th>Exec</th><th>Ad.Not</th><th>Atraso</th><th>Falta</th><th>Abono</th><th>Saldo do dia</th><th>Observação</th></tr></thead><tbody>${linhas}</tbody></table>
<div class="leg">Exec: horas trabalhadas · Ad.Not: adicional noturno · Atraso: falta para completar a jornada · Falta: ausência sem justificativa · Abono: horas justificadas · Saldo do dia: diferença para a jornada de 8h.</div>
<div class="bloco"><h2>Banco de horas</h2><div class="banco">
<div><span>Saldo anterior</span><strong>${esc(b.saldo_anterior || '-')}</strong></div>
<div><span>Saldo do período${per ? ` (${dataBR(per.inicio)} a ${dataBR(per.fim)})` : ''}</span><strong>${esc(per?.valor || b.horas_mes_atual || '-')} ${esc(per?.situacao || '')}</strong></div>
<div><span>Saldo atual</span><strong>${esc(b.saldo_atual || b.saldo_total || '-')}</strong></div></div></div>
<div class="bloco"><h2>Eventos do movimento no período</h2><table><thead><tr><th>Código</th><th>Descrição</th><th class="r">Horas</th></tr></thead><tbody>${eventos}</tbody></table></div>
<div class="bloco"><h2>Dias ponte</h2><table><thead><tr><th>Data do dia ponte</th><th>Início</th><th>Fim</th><th class="r">Horas a incrementar/decrementar</th></tr></thead><tbody>${pontes}</tbody></table></div>
<div class="bloco"><h2>Horário no período</h2>${horario}</div>
<p class="ass">${assinatura}</p>
<script>window.onload=()=>{window.print();}<\/script></body></html>`;
  const janela = janelaAberta || window.open('', '_blank');
  if (!janela) return;
  janela.document.write(html);
  janela.document.close();
}

const trabalhado = (d) => d.tipo.startsWith('Trabalhado');
const ehHoje = (d) => d.data === new Date().toISOString().slice(0, 10);
const corSaldo = (d) => (d.saldo.startsWith('-') ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700');
const paraMinutos = (hhmm) => {
  const [h, m] = hhmm.replace(/[+-]/, '').split(':').map(Number);
  return (h * 60 + m) * (hhmm.startsWith('-') ? -1 : 1);
};
// "08:48" -> "8h48"
const horasLegiveis = (hhmm) => {
  const [h, m] = hhmm.split(':');
  return Number(h) + 'h' + (m === '00' ? '' : m);
};

const diasVisiveis = computed(() => (espelho.value?.dias || []).filter((d) => !somenteTrabalhados.value || trabalhado(d)));

function resumoDe(e) {
  const dias = e?.dias || [];
  const trabalhados = dias.filter(trabalhado);
  const totalMin = trabalhados.reduce((t, d) => t + paraMinutos(d.total), 0);
  const horas = Math.floor(totalMin / 60) + 'h' + String(totalMin % 60).padStart(2, '0');
  const mes = e?.banco_horas?.horas_mes_atual || '';
  const banco = e?.banco_horas?.saldo_total || '';
  const cor = (v) => (v.startsWith('-') ? 'text-rose-700' : 'text-emerald-700');
  const comFalta = dias.filter((d) => d.falta);
  const faltaMin = comFalta.reduce((t, d) => t + (/^[+-]?\d+:\d+$/.test(String(d.falta)) ? Math.abs(paraMinutos(String(d.falta))) : 0), 0);
  const faltas = comFalta.length
    ? `${comFalta.length} dia${comFalta.length > 1 ? 's' : ''}${faltaMin ? ' · ' + Math.floor(faltaMin / 60) + 'h' + String(faltaMin % 60).padStart(2, '0') : ''}`
    : 'Nenhuma';
  return [
    { rotulo: 'Dias trabalhados', valor: trabalhados.length, cor: 'text-slate-900' },
    { rotulo: 'Horas no mês', valor: horas, cor: 'text-slate-900' },
    { rotulo: 'Faltas', valor: faltas, cor: comFalta.length ? 'text-rose-700' : 'text-slate-900' },
    { rotulo: 'Saldo do mês', valor: mes.replace(' ', '') || '-', cor: cor(mes) },
    { rotulo: 'Banco de horas', valor: banco.replace(' ', '') || '-', cor: cor(banco) }
  ];
}
const resumo = computed(() => resumoDe(espelho.value));

// "16 dias trabalhados · 145h48 · saldo +17h48m"
function resumoLinha(e) {
  const r = Object.fromEntries(resumoDe(e).map((x) => [x.rotulo, x.valor]));
  return `${r['Dias trabalhados']} dias trabalhados · ${r['Horas no mês']} no mês · saldo ${r['Saldo do mês']}`;
}
const carregando = ref(true);
const erro = ref('');


function cabecalhoAuth() {
  // Consulta do RH (matricula informada) usa a sessão do painel /admin
  const token = props.matricula ? tokenAdmin() : localStorage.getItem('vb_auth_token');
  return { 'Authorization': `Bearer ${token || ''}` };
}

function formatarData(iso) {
  const [, m, d] = iso.split('-');
  return `${d}/${m}`;
}

// A assinatura é feita no Autentique: cada espelho traz o link do documento (link_assinatura)
const podeAssinar = (e) => !!e && !e.assinatura && !props.somenteLeitura && !!e.link_assinatura;

function assinarNoAutentique(e) {
  window.open(e.link_assinatura, '_blank', 'noopener');
}

async function buscar(competencia = '') {
  const filtro = competencia ? `competencia=${encodeURIComponent(competencia)}` : '';
  const url = props.matricula
    ? `api/rh/ponto?matricula=${encodeURIComponent(props.matricula)}&${filtro}`
    : `api/ponto?${filtro}`;
  const res = await apiFetch(url, { headers: cabecalhoAuth() });
  const data = await res.json();
  if (!res.ok) throw new Error(data.erro);
  return data;
}

// Carrega a lista de meses e o mais recente (topo da tela)
async function carregar() {
  carregando.value = true;
  erro.value = '';
  try {
    const data = await buscar();
    competencias.value = data.competencias || [];
    ultimo.value = data.espelho;
  } catch (e) {
    ultimo.value = null;
    erro.value = 'Não foi possível carregar o espelho de ponto.';
  } finally {
    carregando.value = false;
  }
}

async function carregarMes(competencia) {
  if (espelho.value?.competencia === competencia) return espelho.value;
  if (ultimo.value?.competencia === competencia) return (espelho.value = ultimo.value);
  const data = await buscar(competencia);
  espelho.value = data.espelho;
  return espelho.value;
}

async function abrirDetalhe(competencia) {
  detalheAberto.value = true;
  carregandoDetalhe.value = true;
  try {
    await carregarMes(competencia);
  } finally {
    carregandoDetalhe.value = false;
  }
}

// Baixar/WhatsApp direto do histórico: abre a janela já no clique (o navegador bloqueia depois)
async function acaoNoPeriodo(competencia, acao) {
  const janela = window.open('', '_blank');
  const e = await carregarMes(competencia);
  if (acao === 'baixar') baixarEspelho(e, janela);
  else enviarWhatsapp(e, janela);
}

watch(() => props.matricula, () => { espelho.value = null; carregar(); });

onMounted(async () => {
  await carregar();
  if (props.competenciaInicial) abrirDetalhe(props.competenciaInicial);
});

</script>
