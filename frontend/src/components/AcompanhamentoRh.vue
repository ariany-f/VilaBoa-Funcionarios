<template>
  <section class="space-y-4" aria-label="Acompanhamento da adesão dos colaboradores">
    <!-- Carregando -->
    <div v-if="carregando" class="space-y-4" role="status" aria-live="polite">
      <span class="sr-only">Carregando indicadores...</span>
      <div class="h-44 rounded-3xl bg-slate-200/70 animate-pulse"></div>
      <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
        <div v-for="n in 6" :key="n" class="h-28 rounded-2xl bg-slate-200/60 animate-pulse"></div>
      </div>
      <div class="h-64 rounded-2xl bg-slate-200/50 animate-pulse"></div>
    </div>

    <!-- Erro -->
    <div v-else-if="erro && !dados" class="py-14 text-center space-y-3" role="alert">
      <p class="text-sm text-rose-700">{{ erro }}</p>
      <button type="button" @click="carregar" class="h-9 px-4 rounded-full bg-[#141414] text-white text-xs font-semibold cursor-pointer">Tentar de novo</button>
    </div>

    <!-- Nenhum colaborador cadastrado -->
    <div v-else-if="dados && !todas.length" class="py-14 text-center text-sm text-slate-500">
      Ainda não há colaboradores cadastrados para acompanhar.
    </div>

    <template v-else-if="dados">
      <!-- ===================== FILTROS ===================== -->
      <div class="bg-white rounded-2xl border border-slate-200 p-2.5 flex flex-wrap items-end gap-2" role="search" aria-label="Filtros do painel">
        <label class="campo">
          <span>Empresa</span>
          <select v-model="empresaFiltro">
            <option value="">Todas</option>
            <option v-for="e in dados.empresas" :key="e" :value="e">{{ e }}</option>
          </select>
        </label>
        <label class="campo">
          <span>Departamento</span>
          <select v-model="deptoFiltro">
            <option value="">Todos</option>
            <option v-for="d in departamentos" :key="d" :value="d">{{ d }}</option>
          </select>
        </label>
        <label class="campo">
          <span>Ano</span>
          <select v-model="ano" @change="carregar">
            <option v-for="a in anos" :key="a" :value="a">{{ a }}</option>
          </select>
        </label>
        <label class="campo">
          <span>Período</span>
          <select v-model="periodoId">
            <option v-for="p in periodos" :key="p.id" :value="p.id">{{ p.rotulo }}</option>
          </select>
        </label>
        <label class="campo grow sm:max-w-[14rem]">
          <span>Buscar</span>
          <input v-model="busca" type="search" placeholder="Nome ou matrícula..." autocomplete="off" />
        </label>
        <div class="flex items-center gap-2 ml-auto">
          <button v-if="filtrosAtivos" type="button" @click="limparFiltros" class="h-9 px-3 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer">Limpar filtros</button>
          <button type="button" @click="exportarCsv" :disabled="!lista.length" class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 disabled:opacity-40 text-xs font-semibold cursor-pointer" title="Baixar em CSV o que está filtrado">
            <AppIcon name="download" size="14" /> Exportar CSV
          </button>
        </div>
        <p v-if="atualizando" class="w-full text-[11px] text-slate-400" role="status">Atualizando dados...</p>
        <p v-if="erro" class="w-full text-[11px] text-rose-700" role="alert">{{ erro }}</p>
      </div>

      <div v-if="!lista.length" class="py-14 text-center text-sm text-slate-500 bg-white rounded-2xl border border-slate-200">
        Nenhum colaborador encontrado com esses filtros.
        <button type="button" @click="limparFiltros" class="block mx-auto mt-3 text-xs font-semibold text-[#9c2337] underline cursor-pointer">Limpar filtros</button>
      </div>

      <template v-else>
      <div class="grid xl:grid-cols-[minmax(0,1fr)_440px] gap-4 items-start">
        <div class="min-w-0 space-y-4">
        <!-- ===================== MANCHETE ===================== -->
        <header class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#141414] via-[#2b1117] to-[#9c2337] text-white p-4 sm:p-5">
          <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full bg-white/5" aria-hidden="true"></div>
          <div class="relative flex flex-row items-center gap-4">
            <div class="flex-1 min-w-0">
              
              <h3 class="text-base sm:text-xl font-semibold leading-snug tracking-tight">{{ manchete }}</h3>
              <p class="mt-1.5 text-xs sm:text-[13px] text-white/75 leading-snug">{{ submanchete }}</p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
              <Rosca :ok="emDia" :total="lista.length" :tamanho="76" :espessura="9" cor="#34d399" cor-fundo="rgba(255,255,255,.18)" cor-texto="fill-white" :rotulo="`${pct(emDia, lista.length)}% dos colaboradores em dia`" class="drop-shadow" />
              <p class="hidden md:block text-[11px] text-white/70 max-w-[8rem] leading-snug">{{ emDia }} de {{ lista.length }} em dia, sem pendência obrigatória</p>
            </div>
          </div>
        </header>

        <!-- KPIs -->
        <ul class="grid grid-cols-2 sm:grid-cols-3 2xl:grid-cols-6 gap-2" role="list" aria-label="Indicadores principais">
          <li v-for="k in kpis" :key="k.id">
            <button type="button" :title="k.contexto" @click="irPara(k.ancora)" class="w-full h-full text-left bg-white rounded-2xl border border-slate-200 hover:border-slate-300 hover:shadow-sm p-3 cursor-pointer motion-safe:transition focus-visible:outline-2 focus-visible:outline-[#9c2337]">
              <span class="flex items-center justify-between gap-2">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 leading-tight">{{ k.rotulo }}</span>
                <span v-if="k.delta" class="text-[11px] font-semibold tabular-nums" :class="k.delta >= 0 ? 'text-emerald-700' : 'text-slate-700'" :title="k.deltaDica">
                  {{ k.delta >= 0 ? '▲' : '▼' }} {{ Math.abs(k.delta) }} pp
                </span>
              </span>
              <span class="mt-1.5 flex items-baseline gap-2">
                <span class="text-2xl font-bold tracking-tight tabular-nums" :class="k.total ? tom(k.pct).texto : 'text-slate-300'">{{ k.total ? k.pct + '%' : '–' }}</span>
                <span class="text-xs text-slate-500 tabular-nums">{{ k.total ? `${k.feito} de ${k.total}` : 'sem dados' }}</span>
              </span>
              <span class="block mt-2 h-1 rounded-full bg-slate-100 overflow-hidden" aria-hidden="true">
                <span class="block h-full rounded-full motion-safe:transition-all motion-safe:duration-700" :style="{ width: k.pct + '%', background: tom(k.pct).cor }"></span>
              </span>
              <span class="hidden 2xl:block mt-1.5 text-[10px] text-slate-500 leading-snug line-clamp-2">{{ k.contexto }}</span>
            </button>
          </li>
        </ul>

        <!-- ===================== CAPÍTULOS ===================== -->
        <div class="grid 2xl:grid-cols-2 gap-4 items-start">
        <!-- 1. Holerites -->
        <article id="cap-holerites" class="cap" aria-labelledby="t-holerites">
          <p class="cap-num">Capítulo 1 · Holerites</p>
          <h3 id="t-holerites" class="cap-titulo">Quem já abriu o holerite de cada mês?</h3>
          <p class="cap-insight"><AppIcon name="info" size="15" class="shrink-0 mt-0.5" /> <span>{{ insightHolerites }}</span></p>
          <ColunasEmpilhadas v-if="serieHolerites.length" :itens="serieHolerites" rotulo-ok="Recebidos" rotulo-pend="Pendentes" descricao="Percentual de holerites recebidos por competência" />
          <p v-else class="vazio">Nenhum holerite no período escolhido.</p>
        </article>

        <!-- 2. Espelho de ponto -->
        <article id="cap-ponto" class="cap" aria-labelledby="t-ponto">
          <p class="cap-num">Capítulo 2 · Espelho de ponto</p>
          <h3 id="t-ponto" class="cap-titulo">Os espelhos de ponto estão sendo assinados?</h3>
          <p class="cap-insight"><AppIcon name="info" size="15" class="shrink-0 mt-0.5" /> <span>{{ insightPonto }}</span></p>
          <ColunasEmpilhadas v-if="seriePonto.length" :itens="seriePonto" rotulo-ok="Assinados" rotulo-pend="Aguardando assinatura" descricao="Percentual de espelhos de ponto assinados por competência" />
          <p v-else class="vazio">Nenhum espelho importado no período escolhido.</p>
        </article>

        </div>

        <div class="grid 2xl:grid-cols-2 gap-4 items-start">
        <!-- 3. Diretrizes -->
        <article id="cap-diretrizes" class="cap" aria-labelledby="t-diretrizes">
          <p class="cap-num">Capítulo 3 · Diretrizes e treinamentos</p>
          <h3 id="t-diretrizes" class="cap-titulo">Quem leu o que é obrigatório?</h3>
          <p class="cap-insight"><AppIcon name="info" size="15" class="shrink-0 mt-0.5" /> <span>{{ insightDiretrizes }}</span></p>
          <BarrasHorizontais v-if="barrasDiretrizes.length" :itens="barrasDiretrizes" />
          <p v-else class="vazio">Nenhuma diretriz publicada para este público.</p>
          <p class="text-[11px] text-slate-400 mt-4">Posição atual das leituras (não depende do período).</p>
        </article>

        <!-- 4. EPI -->
        <article id="cap-epi" class="cap" aria-labelledby="t-epi">
          <p class="cap-num">Capítulo 4 · Ficha de EPI</p>
          <h3 id="t-epi" class="cap-titulo">As entregas de EPI foram assinadas?</h3>
          <p class="cap-insight"><AppIcon name="info" size="15" class="shrink-0 mt-0.5" /> <span>{{ insightEpi }}</span></p>
          <div v-if="resumoEpi.total" class="grid gap-4">
            <div class="flex items-center gap-5 justify-center sm:justify-start">
              <Rosca :ok="resumoEpi.ok" :total="resumoEpi.total" :rotulo="`${resumoEpi.ok} de ${resumoEpi.total} fichas de EPI assinadas`" />
              <ul class="text-xs text-slate-600 space-y-1.5">
                <li class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></span><strong class="tabular-nums">{{ resumoEpi.ok }}</strong> assinadas</li>
                <li class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-sm bg-amber-200"></span><strong class="tabular-nums">{{ resumoEpi.pend }}</strong> pendentes</li>
              </ul>
            </div>
            <div v-if="fichasPendentes.length">
              <h4 class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-2">Fichas aguardando assinatura (mais antigas primeiro)</h4>
              <ul class="divide-y divide-slate-100 border border-slate-100 rounded-xl" role="list">
                <li v-for="f in fichasPendentes.slice(0, 5)" :key="f.id">
                  <button type="button" @click="abrirDetalhe(f.c, 'epi')" class="w-full flex items-center justify-between gap-3 px-3 py-2.5 text-left hover:bg-slate-50 cursor-pointer">
                    <span class="min-w-0">
                      <span class="block text-sm font-medium text-slate-800 truncate">{{ f.c.nome }}</span>
                      <span class="block text-[11px] text-slate-400">Ficha {{ f.id }} · entregue em {{ dataBr(f.data_entrega) }}</span>
                    </span>
                    <span class="shrink-0 badge" :class="f.dias > 180 ? 'critico' : 'pendente'">há {{ f.dias }} dias</span>
                  </button>
                </li>
              </ul>
            </div>
            <p v-else class="text-sm text-emerald-700 font-medium">Nenhuma ficha aguardando assinatura.</p>
          </div>
          <p v-else class="vazio">Nenhuma ficha de EPI registrada para este público.</p>
        </article>

        </div>

        <!-- 5. Academia -->
        <article id="cap-academia" class="cap" aria-labelledby="t-academia">
          <p class="cap-num">Capítulo 5 · Academia Vila Boa</p>
          <h3 id="t-academia" class="cap-titulo">Como andam as trilhas e quem já se certificou?</h3>
          <p class="cap-insight"><AppIcon name="info" size="15" class="shrink-0 mt-0.5" /> <span>{{ insightAcademia }}</span></p>
          <div v-if="barrasTrilhas.length" class="grid lg:grid-cols-2 gap-8">
            <div class="min-w-0">
              <h4 class="sub">Trilhas concluídas por quem deve fazê-las</h4>
              <BarrasHorizontais :itens="barrasTrilhas" cor-padrao="#9c2337" />
            </div>
            <div class="min-w-0">
              <h4 class="sub">Certificados emitidos em {{ ano }}</h4>
              <ColunasEmpilhadas :itens="certificadosPorMes" modo="abs" rotulo-ok="certificado(s)" :rotulo-pend="''" cor-ok="#9c2337" descricao="Certificados emitidos por mês" :altura="110" />
              <p class="text-[11px] text-slate-400 mt-3">{{ certificadosPorTrilha }}</p>
            </div>
          </div>
          <p v-else class="vazio">Nenhuma trilha publicada para este público.</p>
        </article>

        <!-- 6. Comunicados -->
        <article id="cap-comunicados" class="cap" aria-labelledby="t-comunicados">
          <p class="cap-num">Capítulo 6 · Comunicados</p>
          <h3 id="t-comunicados" class="cap-titulo">O que o RH comunicou no período?</h3>
          <p class="cap-insight"><AppIcon name="info" size="15" class="shrink-0 mt-0.5" /> <span>{{ insightComunicados }}</span></p>
          <div v-if="comunicadosPeriodo.length" class="grid lg:grid-cols-2 gap-8">
            <div class="min-w-0">
              <h4 class="sub">Publicados por mês</h4>
              <ColunasEmpilhadas :itens="comunicadosPorMes" modo="abs" rotulo-ok="ativo(s)" rotulo-pend="encerrado(s)" cor-ok="#9c2337" cor-pend="#cbd5e1" descricao="Comunicados publicados por mês" :altura="110" />
            </div>
            <div class="min-w-0">
              <h4 class="sub">Por categoria</h4>
              <BarrasHorizontais :itens="comunicadosPorCategoria" cor-padrao="#9c2337" />
            </div>
          </div>
          <p v-else class="vazio">Nenhum comunicado publicado no período.</p>
          <p class="text-[11px] text-slate-400 mt-4">O portal ainda não registra quem leu cada comunicado; por isso ele não entra na matriz abaixo.</p>
        </article>

        <!-- ===================== QUEM FEZ O QUÊ ===================== -->
        <article id="cap-matriz" class="cap" aria-labelledby="t-matriz">
          <p class="cap-num">Quem fez o quê</p>
          <h3 id="t-matriz" class="cap-titulo">Cada pessoa, cada obrigação</h3>
          <p class="text-xs text-slate-500 mt-1">Cada linha é um colaborador e cada coluna é uma obrigação. O número mostra quantos itens ele já fez. Clique para ver o detalhe.</p>

          <p class="mt-3 text-[12px] text-slate-600 leading-relaxed">
            <strong class="text-emerald-700">Tudo em dia:</strong> {{ destaques.emDia.length ? destaques.emDia.map((a) => primeiroNome(a.c.nome)).join(', ') : 'ninguém ainda' }}.
            <span class="mx-1 text-slate-300">|</span>
            <strong class="text-slate-800">Mais itens a fazer:</strong>
            <template v-if="destaques.pendencias.length"><template v-for="(a, n) in destaques.pendencias" :key="a.c.matricula"><template v-if="n">, </template>{{ primeiroNome(a.c.nome) }} ({{ a.score }})</template>.</template>
            <template v-else>ninguém.</template>
            <span class="mx-1 text-slate-300">|</span>
            <strong class="text-slate-800">Concluíram trilhas:</strong> {{ destaques.trilhas.length ? destaques.trilhas.slice(0, 3).map((a) => primeiroNome(a.c.nome)).join(', ') : 'ninguém ainda' }}.
          </p>

          <div class="flex flex-wrap items-center justify-between gap-3 mt-4 mb-2">
            <ul class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-600" aria-label="Legenda das cores">
              <li v-for="s in legenda" :key="s.id" class="inline-flex items-center gap-1.5"><span class="cel mini" :class="s.id"></span>{{ s.rotulo }}</li>
            </ul>
            <label class="flex items-center gap-2 text-[11px] text-slate-500">Ordenar por
              <select v-model="ordem" class="h-8 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs text-slate-700 outline-none">
                <option value="atencao">Mais itens a fazer</option>
                <option value="nome">Nome (A–Z)</option>
                <option value="empresa">Empresa</option>
              </select>
            </label>
          </div>

          <div class="matriz" role="table" aria-label="Situação de cada colaborador por obrigação">
            <div class="matriz-linha cab" role="row">
              <span role="columnheader">Colaborador</span>
              <span v-for="m in MODULOS" :key="m.id" role="columnheader" class="text-center leading-tight" :title="m.rotulo + ': ' + m.desc">
                <span class="block">{{ m.rotulo }}</span>
                <span class="block font-normal normal-case tracking-normal text-slate-400">{{ m.desc }}</span>
              </span>
              <span role="columnheader" class="text-center leading-tight">
                <span class="block">Falta</span>
                <span class="block font-normal normal-case tracking-normal text-slate-400">fazer</span>
              </span>
            </div>
            <div v-for="a in matrizVisivel" :key="a.c.matricula" class="matriz-linha" role="row">
              <button type="button" role="rowheader" class="text-left min-w-0 py-1.5 cursor-pointer group" @click="abrirDetalhe(a.c)" :aria-label="`Abrir detalhe de ${a.c.nome}`">
                <span class="block text-[13px] font-medium text-slate-900 truncate group-hover:underline">{{ a.c.nome }}</span>
                <span class="block text-[10px] text-slate-400 truncate">{{ a.c.empresa }} · {{ a.c.cargo }}</span>
              </button>
              <span v-for="m in MODULOS" :key="m.id" role="cell" class="flex justify-center">
                <button type="button" class="cel" :class="a.m[m.id].st" @click="abrirDetalhe(a.c, m.id)" :aria-label="`${a.c.nome}, ${m.rotulo}: ${rotuloStatus(a.m[m.id])}`" :title="`${m.rotulo}: ${rotuloStatus(a.m[m.id])}`">
                  {{ a.m[m.id].total ? `${a.m[m.id].ok}/${a.m[m.id].total}` : '–' }}
                </button>
              </span>
              <span role="cell" class="text-center text-xs font-bold tabular-nums" :class="a.score ? 'text-slate-700' : 'text-emerald-700'">{{ a.score || 'Ok' }}</span>
            </div>
          </div>
          <button v-if="matrizOrdenada.length > limiteMatriz" type="button" @click="matrizExpandida = !matrizExpandida" class="mt-3 text-xs font-semibold text-[#9c2337] hover:underline cursor-pointer">
            {{ matrizExpandida ? 'Mostrar menos' : `Mostrar todos os ${matrizOrdenada.length} colaboradores` }}
          </button>
        </article>

        <!-- ===================== COMPARATIVOS ===================== -->
        <article id="cap-comparativos" class="cap" aria-labelledby="t-comp">
          <p class="cap-num">Comparativos</p>
          <h3 id="t-comp" class="cap-titulo">Onde a adesão é maior e onde precisa de reforço?</h3>
          <p class="cap-insight"><AppIcon name="info" size="15" class="shrink-0 mt-0.5" /> <span>{{ insightComparativo }}</span></p>
          <div class="inline-flex bg-slate-100 rounded-full p-1 mb-4" role="tablist" aria-label="Agrupar por">
            <button v-for="g in AGRUPAMENTOS" :key="g.id" type="button" role="tab" :aria-selected="agrupar === g.id" @click="agrupar = g.id" class="h-8 px-4 rounded-full text-xs font-semibold cursor-pointer" :class="agrupar === g.id ? 'bg-[#141414] text-white' : 'text-slate-600'">{{ g.rotulo }}</button>
          </div>
          <ul class="space-y-3" role="list">
            <li v-for="g in grupos" :key="g.nome" class="border border-slate-100 rounded-xl p-3 sm:p-4">
              <div class="flex items-baseline justify-between gap-3">
                <span class="text-sm font-semibold text-slate-900 truncate" :title="g.nome">{{ g.nome }}</span>
                <span class="shrink-0 text-xs text-slate-500 tabular-nums">{{ g.n }} colaborador{{ g.n === 1 ? '' : 'es' }} · <strong :class="tom(g.pctEmDia).texto">{{ g.pctEmDia }}% em dia</strong></span>
              </div>
              <div class="mt-2 h-2.5 rounded-full bg-slate-100 overflow-hidden" role="progressbar" :aria-valuenow="g.pctEmDia" aria-valuemin="0" aria-valuemax="100" :aria-label="`${g.nome}: ${g.pctEmDia}% em dia`">
                <div class="h-full rounded-full motion-safe:transition-all motion-safe:duration-700" :style="{ width: g.pctEmDia + '%', background: tom(g.pctEmDia).cor }"></div>
              </div>
              <ul class="mt-3 flex flex-wrap gap-1.5" role="list" aria-label="Adesão por módulo">
                <li v-for="m in MODULOS" :key="m.id" class="text-[11px] px-2 py-1 rounded-lg border tabular-nums" :class="g.mods[m.id].total ? tom(g.mods[m.id].pct).chip : 'border-slate-100 text-slate-300'">
                  {{ m.rotulo }} <strong>{{ g.mods[m.id].total ? g.mods[m.id].pct + '%' : '–' }}</strong>
                </li>
              </ul>
            </li>
          </ul>
        </article>
        </div>
        <aside class="xl:sticky xl:top-3 xl:max-h-[calc(100vh-1.5rem)] xl:overflow-y-auto rounded-2xl" aria-label="Tarefas do RH">
        <!-- ===================== TAREFAS DO RH ===================== -->
        <article id="cap-tarefas" class="cap" aria-labelledby="t-tarefas">
          <p class="cap-num">Tarefas do RH</p>
          <div class="flex flex-wrap items-end justify-between gap-2">
            <h3 id="t-tarefas" class="cap-titulo">O que o RH precisa fazer agora?</h3>
            <div class="inline-flex bg-slate-100 rounded-full p-1" role="tablist" aria-label="Filtrar tarefas">
              <button v-for="f in filtrosTarefa" :key="f.id" type="button" role="tab" :aria-selected="filtroTarefa === f.id" @click="filtroTarefa = f.id" class="h-7 px-3 rounded-full text-[11px] font-semibold cursor-pointer" :class="filtroTarefa === f.id ? 'bg-[#141414] text-white' : 'text-slate-600'">{{ f.rotulo }} <span class="tabular-nums opacity-70">{{ f.n }}</span></button>
            </div>
          </div>
          <p v-if="!tarefas.length" class="mt-3 text-sm text-emerald-700 font-medium">Nenhuma tarefa pendente nos filtros atuais. 🎉</p>
          <p v-else-if="!tarefasFiltradas.length" class="vazio">Nenhuma tarefa nesta situação.</p>
          <ul v-else class="mt-3 space-y-2" role="list">
            <li v-for="t in tarefasVisiveis" :key="t.id" class="border border-l-4 rounded-xl" :style="{ borderLeftColor: PRIORIDADES[t.prioridade].cor }" :class="t.andamento ? 'border-sky-200 bg-sky-50/40' : 'border-slate-200'">
              <div class="p-3 flex flex-col gap-2.5">
                                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-1.5">
                    <span class="badge" :class="PRIORIDADES[t.prioridade].classe">{{ PRIORIDADES[t.prioridade].rotulo }}</span>
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ t.modulo }}</span>
                    <span v-if="t.andamento" class="text-[10px] font-semibold uppercase tracking-wider text-sky-700">Em andamento</span>
                  </div>
                  <p class="mt-1 text-sm font-semibold text-slate-900">{{ t.titulo }}</p>
                  <p class="text-[11px] text-slate-500">{{ t.detalhe }}</p>
                  <p class="text-[11px] text-slate-400 mt-0.5">Por que importa: {{ t.motivo }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                  <button type="button" @click="alternarAndamento(t.id)" class="h-8 px-2.5 rounded-lg text-[11px] font-semibold cursor-pointer" :class="t.andamento ? 'bg-sky-100 text-sky-800' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" :aria-pressed="t.andamento">{{ t.andamento ? 'Em andamento ✓' : 'Marcar em andamento' }}</button>
                  <button type="button" @click="tarefaAberta = tarefaAberta === t.id ? null : t.id" class="h-8 px-3 rounded-lg bg-[#141414] text-white text-[11px] font-semibold cursor-pointer" :aria-expanded="tarefaAberta === t.id">{{ tarefaAberta === t.id ? 'Fechar' : `Ver ${t.pessoas.length} pessoa${t.pessoas.length === 1 ? '' : 's'}` }}</button>
                </div>
              </div>
              <ul v-if="tarefaAberta === t.id" class="border-t border-slate-100 divide-y divide-slate-100" role="list">
                <li v-for="p in t.pessoas" :key="p.c.matricula" class="px-3 py-2 flex items-center justify-between gap-3">
                  <button type="button" class="min-w-0 text-left cursor-pointer" @click="abrirDetalhe(p.c)">
                    <span class="block text-[13px] font-medium text-slate-900 truncate hover:underline">{{ p.c.nome }}</span>
                    <span class="block text-[11px] text-slate-400 truncate">{{ p.info }}</span>
                  </button>
                  <a v-if="podeAvisar && p.c.telefone" :href="p.aviso" target="_blank" rel="noopener" class="shrink-0 inline-flex items-center gap-1.5 h-8 px-3 rounded-lg bg-[#1DA851] text-white text-xs font-semibold hover:brightness-95" :aria-label="`Cobrar ${p.c.nome} pelo WhatsApp`"><AppIcon name="whatsapp" size="13" /> Cobrar</a>
                  <span v-else-if="podeAvisar" class="shrink-0 text-[11px] text-slate-400">Sem telefone</span>
                </li>
              </ul>
            </li>
          </ul>
          <button v-if="tarefasFiltradas.length > 6" type="button" @click="tarefasExpandidas = !tarefasExpandidas" class="mt-3 text-xs font-semibold text-[#9c2337] hover:underline cursor-pointer">{{ tarefasExpandidas ? 'Mostrar menos' : `Ver todas as ${tarefasFiltradas.length} tarefas` }}</button>
          <p class="text-[11px] text-slate-400 mt-3">As tarefas saem sozinhas da lista quando o colaborador conclui o item. A marcação “Em andamento” fica só neste navegador.</p>
        </article>
        </aside>
      </div>



      </template>
    </template>

    <!-- ===================== DETALHE DO COLABORADOR ===================== -->
    <div v-if="detalhe" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-0 sm:p-4" @click.self="fecharDetalhe">
      <div class="bg-white w-full sm:max-w-2xl max-h-[92vh] sm:max-h-[85vh] rounded-t-3xl sm:rounded-3xl flex flex-col overflow-hidden" role="dialog" aria-modal="true" :aria-label="`Detalhe de ${detalhe.c.nome}`">
        <div class="p-5 border-b border-slate-100 flex items-start justify-between gap-3">
          <div class="min-w-0">
            <h3 class="text-base font-semibold text-slate-900 truncate">{{ detalhe.c.nome }}</h3>
            <p class="text-xs text-slate-500 mt-0.5">{{ detalhe.c.matricula }} · {{ detalhe.c.empresa }} · {{ detalhe.c.cargo }}<template v-if="detalhe.c.departamento"> · {{ detalhe.c.departamento }}</template></p>
            <p class="text-xs mt-2 font-medium" :class="detalhe.score ? 'text-amber-700' : 'text-emerald-700'">{{ detalhe.score ? `${detalhe.score} pendência${detalhe.score === 1 ? '' : 's'} obrigatória${detalhe.score === 1 ? '' : 's'}` : 'Tudo em dia' }}</p>
          </div>
          <button ref="botaoFechar" type="button" @click="fecharDetalhe" class="w-9 h-9 shrink-0 rounded-full hover:bg-slate-100 flex items-center justify-center cursor-pointer" aria-label="Fechar detalhe"><AppIcon name="x" size="18" /></button>
        </div>
        <div class="px-5 pt-3 flex gap-1.5 overflow-x-auto" role="tablist" aria-label="Módulos">
          <button v-for="m in MODULOS" :key="m.id" type="button" role="tab" :aria-selected="detalhe.modulo === m.id" @click="detalheSel.modulo = m.id" class="shrink-0 h-8 px-3 rounded-full text-xs font-semibold cursor-pointer inline-flex items-center gap-1.5" :class="detalhe.modulo === m.id ? 'bg-[#141414] text-white' : 'bg-slate-100 text-slate-600'">
            <span class="cel mini" :class="detalhe.m[m.id].st">{{ glifo(detalhe.m[m.id].st) }}</span>{{ m.rotulo }}
          </button>
        </div>
        <div class="p-5 overflow-y-auto flex-1">
          <ul v-if="itensDetalhe.length" class="divide-y divide-slate-100" role="list">
            <li v-for="(it, n) in itensDetalhe" :key="n" class="py-2.5 flex items-center justify-between gap-3">
              <span class="min-w-0">
                <span class="block text-sm text-slate-800 truncate">{{ it.titulo }}</span>
                <span v-if="it.sub" class="block text-[11px] text-slate-400 truncate">{{ it.sub }}</span>
              </span>
              <span class="shrink-0 flex items-center gap-2">
                <span class="text-xs font-medium" :class="it.feito ? 'text-emerald-700' : (it.obrigatorio === false ? 'text-slate-400' : 'text-amber-700')">{{ it.estado }}</span>
                <a v-if="!it.feito && it.aviso" :href="it.aviso" target="_blank" rel="noopener" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-[#1DA851] text-white" :aria-label="`Cobrar: ${it.titulo}`" title="Cobrar pelo WhatsApp"><AppIcon name="whatsapp" size="14" /></a>
              </span>
            </li>
          </ul>
          <p v-else class="text-sm text-slate-400 text-center py-8">{{ MODULOS.find((m) => m.id === detalhe.modulo).rotulo }} não se aplica a este colaborador no período.</p>
        </div>
        <div v-if="podeAvisar && detalhe.c.telefone && detalhe.score" class="p-4 border-t border-slate-100">
          <a :href="linkCobranca(detalhe.c)" target="_blank" rel="noopener" class="flex items-center justify-center gap-2 h-11 rounded-xl bg-[#1DA851] text-white text-sm font-semibold hover:brightness-95">
            <AppIcon name="whatsapp" size="16" /> Cobrar todas as pendências pelo WhatsApp
          </a>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import AppIcon from './AppIcon.vue';
import Rosca from './acompanhamento/Rosca.vue';
import ColunasEmpilhadas from './acompanhamento/ColunasEmpilhadas.vue';
import BarrasHorizontais from './acompanhamento/BarrasHorizontais.vue';
import { pode } from '../utils/permissoes.js';
import { tokenAdmin } from '../utils/sessaoAdmin.js';
import { usarFecharComEsc } from '../utils/fecharComEsc.js';
import { avisoCaderno, avisoPonto, avisoHolerite, cobrancaGeral } from '../utils/avisosWhatsapp.js';

const props = defineProps({
  // Usuário do painel (permissões do perfil)
  colaborador: { type: Object, required: true }
});

const MODULOS = [
  { id: 'hol', rotulo: 'Holerites', curto: 'Hol.' , desc: 'recebidos' },
  { id: 'ponto', rotulo: 'Ponto', curto: 'Pto.' , desc: 'assinados' },
  { id: 'dir', rotulo: 'Diretrizes', curto: 'Dir.' , desc: 'lidas' },
  { id: 'epi', rotulo: 'EPI', curto: 'EPI' , desc: 'assinados' },
  { id: 'aca', rotulo: 'Academia', curto: 'Aca.' , desc: 'trilhas feitas' }
];
const AGRUPAMENTOS = [
  { id: 'empresa', rotulo: 'Empresa' },
  { id: 'departamento', rotulo: 'Departamento' },
  { id: 'funcao', rotulo: 'Função' }
];
const periodos = [
  { id: 'ano', rotulo: 'Ano todo', mes: [1, 12] },
  { id: 't1', rotulo: '1º trimestre', mes: [1, 3] },
  { id: 't2', rotulo: '2º trimestre', mes: [4, 6] },
  { id: 't3', rotulo: '3º trimestre', mes: [7, 9] },
  { id: 't4', rotulo: '4º trimestre', mes: [10, 12] },
  { id: 's1', rotulo: '1º semestre', mes: [1, 6] },
  { id: 's2', rotulo: '2º semestre', mes: [7, 12] }
];
const legenda = [
  { id: 'ok', rotulo: 'Tudo feito' },
  { id: 'parcial', rotulo: 'Feito em parte' },
  { id: 'pend', rotulo: 'Nada feito' },
  { id: 'na', rotulo: 'Não se aplica' }
];
const NOMES_MES = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
const MESES_EXTENSO = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

// ---------------------------------------------------------------- dados
const anoAtual = new Date().getFullYear();
const anos = [anoAtual, anoAtual - 1, anoAtual - 2];
const ano = ref(anoAtual);
const dados = ref(null);
const carregando = ref(true);
const atualizando = ref(false);
const erro = ref('');
let sequencia = 0;

const auth = () => ({ Authorization: `Bearer ${tokenAdmin()}` });

async function carregar() {
  const minha = ++sequencia;
  erro.value = '';
  carregando.value = !dados.value;
  atualizando.value = !!dados.value;
  try {
    const res = await fetch(`/api/rh/indicadores?ano=${ano.value}`, { headers: auth() });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.erro || 'Não foi possível carregar os indicadores.');
    if (minha === sequencia) dados.value = data;
  } catch (e) {
    if (minha === sequencia) erro.value = e.message || 'Não foi possível carregar os indicadores.';
  } finally {
    if (minha === sequencia) { carregando.value = false; atualizando.value = false; }
  }
}
onMounted(carregar);

// ---------------------------------------------------------------- filtros
const empresaFiltro = ref('');
const deptoFiltro = ref('');
const periodoId = ref('ano');
const busca = ref('');
const periodoAtual = computed(() => periodos.find((p) => p.id === periodoId.value) || periodos[0]);
const filtrosAtivos = computed(() => !!(empresaFiltro.value || deptoFiltro.value || busca.value || periodoId.value !== 'ano'));
function limparFiltros() {
  empresaFiltro.value = '';
  deptoFiltro.value = '';
  busca.value = '';
  periodoId.value = 'ano';
}
const departamentos = computed(() => [...new Set((dados.value?.colaboradores || []).map((c) => c.departamento).filter(Boolean))].sort((a, b) => a.localeCompare(b)));

// ---------------------------------------------------------------- utilidades
const pct = (a, b) => (b ? Math.round((a / b) * 100) : 0);
const primeiroNome = (n) => (n || '').split(' ')[0];
const plural = (n, um, varios) => `${n} ${n === 1 ? um : varios}`;

// Aceita "2026-10-20 10:00", "26/10/2026 às 16:05" e "09/2026" (competência)
function parseData(s) {
  if (!s) return null;
  let m;
  if ((m = /^(\d{4})-(\d{2})(?:-(\d{2}))?/.exec(s))) return { a: +m[1], m: +m[2], d: m[3] ? +m[3] : 1 };
  if ((m = /^(\d{2})\/(\d{2})\/(\d{4})/.exec(s))) return { a: +m[3], m: +m[2], d: +m[1] };
  if ((m = /^(\d{2})\/(\d{4})$/.exec(s))) return { a: +m[2], m: +m[1], d: 1 };
  return null;
}
function noPeriodo(s) {
  const p = parseData(s);
  const [ini, fim] = periodoAtual.value.mes;
  return !!p && p.a === ano.value && p.m >= ini && p.m <= fim;
}
const dataBr = (s) => {
  const p = parseData(s);
  return p ? `${String(p.d).padStart(2, '0')}/${String(p.m).padStart(2, '0')}/${p.a}` : s || '';
};
const rotuloComp = (comp) => {
  const p = parseData(comp);
  return p ? `${NOMES_MES[p.m - 1]}/${String(p.a).slice(2)}` : comp;
};
const rotuloCompExtenso = (comp) => {
  const p = parseData(comp);
  return p ? `${MESES_EXTENSO[p.m - 1]} de ${p.a}` : comp;
};
const chaveComp = (comp) => { const p = parseData(comp); return p ? p.a * 100 + p.m : 0; };
const diasDesde = (iso) => {
  const p = parseData(iso);
  const h = parseData(dados.value?.hoje);
  if (!p || !h) return 0;
  return Math.max(0, Math.round((Date.UTC(h.a, h.m - 1, h.d) - Date.UTC(p.a, p.m - 1, p.d)) / 86400000));
};
const status = (total, ok) => (!total ? 'na' : ok >= total ? 'ok' : ok === 0 ? 'pend' : 'parcial');
const glifo = (st) => ({ ok: '✓', parcial: '½', pend: '✕', na: '–' }[st]);
const rotuloStatus = (m) => ({
  ok: `tudo feito (${m.ok} de ${m.total})`,
  parcial: `feito em parte (${m.ok} de ${m.total})`,
  pend: `nada feito (${m.ok} de ${m.total})`,
  na: 'não se aplica'
}[m.st]);
const tom = (p) => (p >= 80
  ? { cor: '#10b981', texto: 'text-emerald-700', chip: 'border-emerald-200 bg-emerald-50 text-emerald-800' }
  : p >= 50
    ? { cor: '#f59e0b', texto: 'text-amber-700', chip: 'border-amber-200 bg-amber-50 text-amber-800' }
    : { cor: '#64748b', texto: 'text-slate-600', chip: 'border-slate-200 bg-slate-100 text-slate-700' });

// ---------------------------------------------------------------- análise de cada colaborador
function analisar(c) {
  // A competência dos holerites vem com sufixos ("09/2026 - Vale", "13/2026"): o mês de referência sai do nome do mês
  const hol = (c.holerites?.detalhe || [])
    .map((h) => {
      const mes = MESES_EXTENSO.indexOf((h.mes_nome || '').toLowerCase()) + 1;
      return mes ? { ...h, competencia: `${String(mes).padStart(2, '0')}/${h.ano}` } : h;
    })
    .filter((h) => noPeriodo(h.competencia));
  const ponto = (c.ponto?.competencias || []).filter((p) => noPeriodo(p.competencia));
  const cads = c.cadernos?.detalhe || [];
  const obrig = cads.filter((d) => d.obrigatorio);
  // Fichas de EPI: as pendentes sempre aparecem (a idade da pendência é o que importa); as assinadas respeitam o período
  const epi = (c.epis?.detalhe || []).filter((e) => !e.assinado_em || noPeriodo(e.data_entrega));
  const trilhas = c.academia?.trilhas || [];
  const trObrig = trilhas.filter((t) => t.obrigatoria);

  const mk = (total, ok, kT = total, kO = ok) => ({ total, ok, kT, kO, st: status(total, ok) });
  const m = {
    hol: mk(hol.length, hol.filter((h) => h.recebido_em).length),
    ponto: mk(ponto.length, ponto.filter((p) => p.assinado_em).length),
    dir: mk(obrig.length, obrig.filter((d) => d.lido_em).length),
    epi: mk(epi.length, epi.filter((e) => e.assinado_em).length),
    aca: mk(trilhas.length, trilhas.filter((t) => t.completa).length, trObrig.length, trObrig.filter((t) => t.completa).length)
  };
  const score = MODULOS.reduce((t, mod) => t + (m[mod.id].kT - m[mod.id].kO), 0);
  return {
    c, m, score, hol, ponto, epi, trilhas,
    cads,
    completas: trilhas.filter((t) => t.completa).length,
    certs: (dados.value?.certificados || []).filter((x) => x.matricula === c.matricula && noPeriodo(x.emitido_em)).length
  };
}

const todas = computed(() => (dados.value?.colaboradores || []).map(analisar));

const lista = computed(() => {
  const termo = busca.value.trim().toLowerCase();
  return todas.value.filter(({ c }) =>
    (!empresaFiltro.value || c.empresa === empresaFiltro.value) &&
    (!deptoFiltro.value || c.departamento === deptoFiltro.value) &&
    (!termo || c.nome.toLowerCase().includes(termo) || c.matricula.toLowerCase().includes(termo)));
});
const matriculas = computed(() => new Set(lista.value.map((a) => a.c.matricula)));
const emDia = computed(() => lista.value.filter((a) => a.score === 0).length);
const comPendencia = computed(() => lista.value.length - emDia.value);

// Soma dos "feitos / total" de cada módulo na lista filtrada
const totaisMod = computed(() => Object.fromEntries(MODULOS.map((mod) => {
  const feito = lista.value.reduce((t, a) => t + a.m[mod.id].kO, 0);
  const total = lista.value.reduce((t, a) => t + a.m[mod.id].kT, 0);
  return [mod.id, { feito, total, pct: pct(feito, total) }];
})));

// ---------------------------------------------------------------- séries por competência
function serieCompetencia(itensDe, feito) {
  const mapa = new Map();
  lista.value.forEach((a) => itensDe(a).forEach((it) => {
    const k = it.competencia;
    const reg = mapa.get(k) || { chave: k, rotulo: rotuloComp(k), ok: 0, pend: 0 };
    if (feito(it)) reg.ok++; else reg.pend++;
    mapa.set(k, reg);
  }));
  return [...mapa.values()].sort((a, b) => chaveComp(a.chave) - chaveComp(b.chave));
}
const serieHolerites = computed(() => serieCompetencia((a) => a.hol, (h) => !!h.recebido_em));
const seriePonto = computed(() => serieCompetencia((a) => a.ponto, (p) => !!p.assinado_em));

// variação (em pontos percentuais) da última competência contra a anterior
function variacao(serie) {
  if (serie.length < 2) return null;
  const tx = (s) => pct(s.ok, s.ok + s.pend);
  const ult = serie[serie.length - 1];
  const ant = serie[serie.length - 2];
  return { delta: tx(ult) - tx(ant), dica: `${rotuloComp(ult.chave)} contra ${rotuloComp(ant.chave)}` };
}

// ---------------------------------------------------------------- manchete e KPIs
const moduloExtremo = computed(() => {
  const com = MODULOS.filter((m) => totaisMod.value[m.id].total).map((m) => ({ ...m, ...totaisMod.value[m.id] }));
  if (!com.length) return null;
  const ord = [...com].sort((a, b) => a.pct - b.pct);
  return { pior: ord[0], melhor: ord[ord.length - 1] };
});
const manchete = computed(() => {
  const n = lista.value.length;
  if (!comPendencia.value) return `Todos os ${n} colaboradores estão em dia com as obrigações. Missão cumprida!`;
  const p = comPendencia.value;
  return `Hoje ${pct(emDia.value, n)}% dos colaboradores estão em dia; ${p} ${p === 1 ? 'pessoa ainda tem' : 'pessoas ainda têm'} pendências obrigatórias.`;
});
const submanchete = computed(() => {
  const e = moduloExtremo.value;
  if (!e) return 'Ainda não há registros nos módulos para contar essa história.';
  const rotulo = (m) => ({ hol: 'Holerites', ponto: 'Espelho de ponto', dir: 'Diretrizes obrigatórias', epi: 'Ficha de EPI', aca: 'Trilhas obrigatórias' }[m.id]);
  if (e.pior.id === e.melhor.id) return `${rotulo(e.pior)} está em ${e.pior.pct}%.`;
  return `O ponto mais crítico é ${rotulo(e.pior)} (${e.pior.pct}% concluído); o melhor desempenho é ${rotulo(e.melhor)} (${e.melhor.pct}%).`;
});

const kpis = computed(() => {
  const t = totaisMod.value;
  const vh = variacao(serieHolerites.value);
  const vp = variacao(seriePonto.value);
  const pendP = (id) => lista.value.filter((a) => a.m[id].kT - a.m[id].kO > 0).length;
  const ctx = (id, texto) => (t[id].total ? `${plural(pendP(id), 'pessoa', 'pessoas')} com pendência. ${texto}` : texto);
  return [
    { id: 'emdia', rotulo: 'Colaboradores em dia', feito: emDia.value, total: lista.value.length, pct: pct(emDia.value, lista.value.length), delta: null, contexto: `${plural(comPendencia.value, 'pessoa', 'pessoas')} com pendência obrigatória.`, ancora: 'cap-matriz' },
    { id: 'hol', rotulo: 'Holerites recebidos', ...t.hol, delta: vh?.delta ?? null, deltaDica: vh ? `Última competência vs. anterior (${vh.dica})` : '', contexto: ctx('hol', 'Abertos no portal ou pelo WhatsApp.'), ancora: 'cap-holerites' },
    { id: 'ponto', rotulo: 'Espelhos assinados', ...t.ponto, delta: vp?.delta ?? null, deltaDica: vp ? `Última competência vs. anterior (${vp.dica})` : '', contexto: ctx('ponto', 'Competências importadas pelo RH.'), ancora: 'cap-ponto' },
    { id: 'dir', rotulo: 'Diretrizes obrigatórias', ...t.dir, delta: null, contexto: ctx('dir', 'Leituras confirmadas pelos colaboradores.'), ancora: 'cap-diretrizes' },
    { id: 'epi', rotulo: 'Fichas de EPI assinadas', ...t.epi, delta: null, contexto: ctx('epi', 'Entregas de EPI com assinatura.'), ancora: 'cap-epi' },
    { id: 'aca', rotulo: 'Trilhas obrigatórias', ...t.aca, delta: null, contexto: ctx('aca', 'Concluídas por quem deve fazê-las.'), ancora: 'cap-academia' }
  ];
});

function irPara(id) {
  const el = document.getElementById(id);
  if (el) el.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
}

// ---------------------------------------------------------------- insights dos capítulos
function insightSerie(serie, nomeItem, verboOk, vazio) {
  if (!serie.length) return vazio;
  const ok = serie.reduce((t, s) => t + s.ok, 0);
  const total = ok + serie.reduce((t, s) => t + s.pend, 0);
  const pior = [...serie].sort((a, b) => b.pend - a.pend || chaveComp(b.chave) - chaveComp(a.chave))[0];
  if (ok === total) return `Todos os ${total} ${nomeItem} do período foram ${verboOk}. Nada a cobrar aqui.`;
  return `${pct(ok, total)}% dos ${total} ${nomeItem} do período foram ${verboOk} (${ok} de ${total}). A competência de ${rotuloCompExtenso(pior.chave)} concentra mais pendências: ${pior.pend} de ${pior.ok + pior.pend}.`;
}
const insightHolerites = computed(() => insightSerie(serieHolerites.value, 'holerites', 'recebidos', 'Sem holerites no período escolhido.'));
const insightPonto = computed(() => insightSerie(seriePonto.value, 'espelhos de ponto', 'assinados', 'Sem espelhos de ponto no período escolhido.'));

const diretrizesAgr = computed(() => {
  const mapa = new Map();
  lista.value.forEach((a) => a.cads.forEach((d) => {
    const r = mapa.get(d.arquivo) || { chave: d.arquivo, titulo: d.titulo, obrigatorio: d.obrigatorio, total: 0, lidos: 0 };
    r.total++;
    if (d.lido_em) r.lidos++;
    mapa.set(d.arquivo, r);
  }));
  return [...mapa.values()].sort((a, b) => (b.obrigatorio - a.obrigatorio) || (pct(a.lidos, a.total) - pct(b.lidos, b.total)) || a.titulo.localeCompare(b.titulo));
});
const barrasDiretrizes = computed(() => diretrizesAgr.value.map((d) => ({
  chave: d.chave,
  rotulo: d.titulo,
  valor: d.lidos,
  total: d.total,
  tag: d.obrigatorio ? 'Obrigatória' : 'Opcional',
  tagClasse: d.obrigatorio ? 'bg-[#9c2337]/10 text-[#9c2337]' : 'bg-slate-100 text-slate-500',
  cor: d.obrigatorio ? tom(pct(d.lidos, d.total)).cor : '#94a3b8'
})));
const insightDiretrizes = computed(() => {
  const obr = diretrizesAgr.value.filter((d) => d.obrigatorio);
  const opc = diretrizesAgr.value.filter((d) => !d.obrigatorio);
  if (!obr.length && !opc.length) return 'Nenhuma diretriz publicada para este público.';
  const partes = [];
  if (obr.length) {
    const lidos = obr.reduce((t, d) => t + d.lidos, 0);
    const total = obr.reduce((t, d) => t + d.total, 0);
    const pior = obr[0];
    partes.push(lidos === total
      ? `Todas as leituras obrigatórias estão concluídas (${total}).`
      : `${pct(lidos, total)}% das leituras obrigatórias foram feitas (${lidos} de ${total}). A de menor adesão é “${pior.titulo}”: ${pior.lidos} de ${pior.total}.`);
  }
  if (opc.length) {
    const l = opc.reduce((t, d) => t + d.lidos, 0);
    const tt = opc.reduce((t, d) => t + d.total, 0);
    partes.push(`As leituras opcionais somam ${pct(l, tt)}% de adesão.`);
  }
  return partes.join(' ');
});

const resumoEpi = computed(() => {
  const total = lista.value.reduce((t, a) => t + a.m.epi.total, 0);
  const ok = lista.value.reduce((t, a) => t + a.m.epi.ok, 0);
  return { total, ok, pend: total - ok };
});
const fichasPendentes = computed(() => lista.value
  .flatMap((a) => a.epi.filter((e) => !e.assinado_em).map((e) => ({ ...e, c: a.c, dias: diasDesde(e.data_entrega) })))
  .sort((x, y) => y.dias - x.dias));
const insightEpi = computed(() => {
  if (!resumoEpi.value.total) return 'Sem fichas de EPI registradas para este público.';
  const f = fichasPendentes.value[0];
  if (!f) return `Todas as ${resumoEpi.value.total} fichas de EPI estão assinadas.`;
  return `${resumoEpi.value.ok} de ${resumoEpi.value.total} fichas de EPI estão assinadas (${pct(resumoEpi.value.ok, resumoEpi.value.total)}%). A pendência mais antiga é de ${f.c.nome}: entregue há ${f.dias} dias (${dataBr(f.data_entrega)}) e ainda sem assinatura.`;
});

const trilhasAgr = computed(() => (dados.value?.trilhas || []).filter((t) => t.publicada).map((t) => {
  let atribuidas = 0;
  let completas = 0;
  lista.value.forEach((a) => {
    const x = a.trilhas.find((tr) => tr.id === t.id);
    if (x) { atribuidas++; if (x.completa) completas++; }
  });
  const certs = (dados.value.certificados || []).filter((c) => c.trilha_id === t.id && matriculas.value.has(c.matricula) && noPeriodo(c.emitido_em)).length;
  return { ...t, atribuidas, completas, certs };
}).filter((t) => t.atribuidas > 0).sort((a, b) => (b.obrigatoria - a.obrigatoria) || (pct(a.completas, a.atribuidas) - pct(b.completas, b.atribuidas))));
const barrasTrilhas = computed(() => trilhasAgr.value.map((t) => ({
  chave: t.id,
  rotulo: t.titulo,
  valor: t.completas,
  total: t.atribuidas,
  tag: t.obrigatoria ? 'Obrigatória' : t.funcoes.join(', '),
  tagClasse: t.obrigatoria ? 'bg-[#9c2337]/10 text-[#9c2337]' : 'bg-slate-100 text-slate-500',
  sub: `${plural(t.certs, 'certificado emitido', 'certificados emitidos')}`
})));
const certificadosPorMes = computed(() => NOMES_MES.map((nome, i) => ({
  chave: nome,
  rotulo: nome,
  ok: (dados.value?.certificados || []).filter((c) => matriculas.value.has(c.matricula) && parseData(c.emitido_em)?.a === ano.value && parseData(c.emitido_em)?.m === i + 1 && noPeriodo(c.emitido_em)).length,
  pend: 0
})));
const totalCertificados = computed(() => certificadosPorMes.value.reduce((t, m) => t + m.ok, 0));
const certificadosPorTrilha = computed(() => {
  const com = trilhasAgr.value.filter((t) => t.certs);
  return com.length ? com.map((t) => `${t.titulo}: ${t.certs}`).join(' · ') : 'Nenhum certificado emitido no período.';
});
const insightAcademia = computed(() => {
  const t = totaisMod.value.aca;
  if (!trilhasAgr.value.length) return 'Nenhuma trilha publicada para este público.';
  const partes = [];
  if (t.total) partes.push(t.feito === t.total ? 'Todas as trilhas obrigatórias foram concluídas.' : `${t.pct}% das trilhas obrigatórias foram concluídas (${t.feito} de ${t.total}).`);
  const pior = trilhasAgr.value.find((x) => x.completas < x.atribuidas);
  if (pior) partes.push(`“${pior.titulo}” tem a menor conclusão: ${pior.completas} de ${pior.atribuidas}.`);
  const iniciaram = lista.value.filter((a) => a.trilhas.some((x) => x.concluidos > 0 && !x.completa)).length;
  if (iniciaram) partes.push(`${plural(iniciaram, 'pessoa tem', 'pessoas têm')} trilha em andamento.`);
  partes.push(totalCertificados.value ? `${plural(totalCertificados.value, 'certificado emitido', 'certificados emitidos')} no período.` : 'Nenhum certificado emitido no período.');
  return partes.join(' ');
});

const comunicadosPeriodo = computed(() => (dados.value?.comunicados || []).filter((c) =>
  ['publicado', 'vencido'].includes(c.situacao) && (!empresaFiltro.value || c.empresa === 'todas' || c.empresa === empresaFiltro.value) && noPeriodo(c.publicar_em || c.publicado_em)));
const comunicadosPorMes = computed(() => NOMES_MES.map((nome, i) => {
  const doMes = comunicadosPeriodo.value.filter((c) => parseData(c.publicar_em || c.publicado_em)?.m === i + 1);
  return { chave: nome, rotulo: nome, ok: doMes.filter((c) => !c.vencido).length, pend: doMes.filter((c) => c.vencido).length };
}));
const comunicadosPorCategoria = computed(() => {
  const mapa = new Map();
  comunicadosPeriodo.value.forEach((c) => mapa.set(c.categoria, (mapa.get(c.categoria) || 0) + 1));
  return [...mapa.entries()].sort((a, b) => b[1] - a[1]).map(([rotulo, valor]) => ({ rotulo, valor, total: comunicadosPeriodo.value.length }));
});
const insightComunicados = computed(() => {
  const l = comunicadosPeriodo.value;
  if (!l.length) return 'Nenhum comunicado publicado no período escolhido.';
  const ativos = l.filter((c) => !c.vencido).length;
  const importantes = l.filter((c) => c.prioridade === 'importante').length;
  const top = comunicadosPorCategoria.value[0];
  return `O RH publicou ${plural(l.length, 'comunicado', 'comunicados')} no período; ${ativos === l.length ? (l.length === 1 ? 'ele segue ativo' : 'todos seguem ativos') : `${ativos} ainda ${ativos === 1 ? 'está ativo' : 'estão ativos'}`}${importantes ? ` e ${importantes} ${importantes === 1 ? 'marcado' : 'marcados'} como importante${importantes === 1 ? '' : 's'}` : ''}. Categoria mais usada: ${top.rotulo}.`;
});

// ---------------------------------------------------------------- matriz "quem fez o quê"
const ordem = ref('atencao');
const matrizOrdenada = computed(() => [...lista.value].sort((a, b) => {
  if (ordem.value === 'nome') return a.c.nome.localeCompare(b.c.nome);
  if (ordem.value === 'empresa') return a.c.empresa.localeCompare(b.c.empresa) || a.c.nome.localeCompare(b.c.nome);
  return b.score - a.score || a.c.nome.localeCompare(b.c.nome);
}));
const limiteMatriz = 15;
const matrizExpandida = ref(false);
const matrizVisivel = computed(() => (matrizExpandida.value ? matrizOrdenada.value : matrizOrdenada.value.slice(0, limiteMatriz)));
const destaques = computed(() => ({
  emDia: lista.value.filter((a) => a.score === 0 && MODULOS.some((m) => a.m[m.id].total)),
  pendencias: [...lista.value].filter((a) => a.score > 0).sort((a, b) => b.score - a.score).slice(0, 3),
  trilhas: [...lista.value].filter((a) => a.completas > 0).sort((a, b) => b.completas - a.completas)
}));

// ---------------------------------------------------------------- comparativos
const agrupar = ref('empresa');
const grupos = computed(() => {
  const chave = {
    empresa: (c) => c.empresa || 'Sem empresa',
    departamento: (c) => c.departamento || 'Sem departamento',
    funcao: (c) => c.cargo || 'Sem função'
  }[agrupar.value];
  const mapa = new Map();
  lista.value.forEach((a) => {
    const k = chave(a.c);
    mapa.set(k, [...(mapa.get(k) || []), a]);
  });
  return [...mapa.entries()].map(([nome, itens]) => {
    const mods = Object.fromEntries(MODULOS.map((m) => {
      const feito = itens.reduce((t, a) => t + a.m[m.id].kO, 0);
      const total = itens.reduce((t, a) => t + a.m[m.id].kT, 0);
      return [m.id, { total, pct: pct(feito, total) }];
    }));
    const dia = itens.filter((a) => a.score === 0).length;
    return { nome, n: itens.length, pctEmDia: pct(dia, itens.length), mods };
  }).sort((a, b) => a.pctEmDia - b.pctEmDia || b.n - a.n || a.nome.localeCompare(b.nome));
});
const insightComparativo = computed(() => {
  const g = grupos.value;
  if (!g.length) return '';
  const rotulo = { empresa: 'A empresa', departamento: 'O departamento', funcao: 'A função' }[agrupar.value];
  if (g.length === 1) return `${rotulo} ${g[0].nome} tem ${g[0].pctEmDia}% dos colaboradores em dia.`;
  const pior = g[0];
  const melhor = g[g.length - 1];
  if (pior.pctEmDia === melhor.pctEmDia) return `Todos os grupos estão com ${pior.pctEmDia}% de colaboradores em dia.`;
  return `${rotulo} com mais pendências é ${pior.nome} (${pior.pctEmDia}% em dia); a melhor situação é de ${melhor.nome} (${melhor.pctEmDia}%).`;
});

// ---------------------------------------------------------------- tarefas do RH
const PRIORIDADES = {
  3: { rotulo: 'Urgente', cor: '#e11d48', classe: 'critico' },
  2: { rotulo: 'Importante', cor: '#f59e0b', classe: 'pendente' },
  1: { rotulo: 'Acompanhar', cor: '#94a3b8', classe: 'neutro' }
};
const podeAvisar = computed(() => pode(props.colaborador, 'rh_acompanhamento', 'cobrar_pendencias'));
const tarefaAberta = ref(null);
const tarefasExpandidas = ref(false);
const filtroTarefa = ref('todas');

// "Em andamento" fica só no navegador de quem usa o painel
const CHAVE_ANDAMENTO = 'rh-tarefas-andamento';
const andamento = ref(new Set());
try { andamento.value = new Set(JSON.parse(localStorage.getItem(CHAVE_ANDAMENTO) || '[]')); } catch (e) { /* sem armazenamento: segue sem lembrar */ }
function alternarAndamento(id) {
  const novo = new Set(andamento.value);
  if (novo.has(id)) novo.delete(id); else novo.add(id);
  andamento.value = novo;
  try { localStorage.setItem(CHAVE_ANDAMENTO, JSON.stringify([...novo])); } catch (e) { /* ignora */ }
}

// Idade (dias) desde o fim da competência "mm/aaaa"
const idadeCompetencia = (comp) => {
  const p = parseData(comp);
  const h = parseData(dados.value?.hoje);
  if (!p || !h) return 0;
  return Math.max(0, Math.round((Date.UTC(h.a, h.m - 1, h.d) - Date.UTC(p.a, p.m, 0)) / 86400000));
};
// Prioridade pelo que está em jogo para a EMPRESA (risco jurídico e de fiscalização), não para o colaborador.
// Peso do módulo + bônus por atraso; o nível "Urgente" (único em vermelho) exige peso alto e muito atraso.
const PESO = {
  ponto: { peso: 5, motivo: 'Espelho assinado é a prova da jornada em ações trabalhistas (horas extras, intervalos).' },
  epi: { peso: 5, motivo: 'Ficha assinada comprova a entrega de EPI (NR-06) em fiscalização e acidente de trabalho.' },
  aca: { peso: 4, motivo: 'Trilha obrigatória comprova treinamento de segurança e conduta exigido por norma.' },
  dir: { peso: 4, motivo: 'Leitura obrigatória comprova ciência de política, conduta e LGPD.' },
  hol: { peso: 2, motivo: 'Ciência do holerite ajuda contra alegação de não recebimento, com risco menor.' }
};
const bonusIdade = (dias) => (dias > 120 ? 2 : dias > 45 ? 1 : 0);
const nivel = (pontos) => (pontos >= 7 ? 3 : pontos >= 5 ? 2 : 1);

const tarefas = computed(() => {
  const out = [];
  const L = lista.value;
  const add = (t) => { if (t.pessoas.length) out.push({ ...t, andamento: andamento.value.has(t.id) }); };

  // Holerites e espelhos: uma tarefa por competência
  const porComp = (modulo, nome, txt, itensDe, pendente, avisoDe) => {
    const mapa = new Map();
    L.forEach((a) => itensDe(a).filter(pendente).forEach((it) => {
      const g = mapa.get(it.competencia) || new Map();
      g.set(a.c.matricula, { c: a.c, n: (g.get(a.c.matricula)?.n || 0) + 1, it });
      mapa.set(it.competencia, g);
    }));
    mapa.forEach((g, comp) => {
      const pessoas = [...g.values()].map((x) => ({ c: x.c, info: x.n > 1 ? `${x.n} ${txt.plural}` : txt.um, aviso: avisoDe(x.c, x.it) }));
      const dias = idadeCompetencia(comp);
      add({ id: `${modulo}:${comp}`, modulo: nome, motivo: PESO[modulo].motivo, prioridade: nivel(PESO[modulo].peso + bonusIdade(dias)), titulo: `${txt.titulo} de ${rotuloCompExtenso(comp)}`, detalhe: `${plural(pessoas.length, 'pessoa', 'pessoas')} sem ${txt.acao} · competência encerrada há ${dias} dias`, pessoas, ordem: dias });
    });
  };
  porComp('hol', 'Holerites', { titulo: 'Cobrar holerites', um: 'holerite não recebido', plural: 'holerites não recebidos', acao: 'receber' }, (a) => a.hol, (h) => !h.recebido_em, (c, h) => avisoHolerite(c, h));
  porComp('ponto', 'Espelho de ponto', { titulo: 'Cobrar assinatura do espelho', um: 'espelho sem assinar', plural: 'espelhos sem assinar', acao: 'assinar' }, (a) => a.ponto, (p) => !p.assinado_em, (c, p) => avisoPonto(c, p));

  // Diretrizes obrigatórias: uma tarefa por caderno
  diretrizesAgr.value.filter((d) => d.obrigatorio && d.lidos < d.total).forEach((d) => {
    const pessoas = L.filter((a) => a.cads.some((x) => x.arquivo === d.chave && !x.lido_em))
      .map((a) => ({ c: a.c, info: 'Ainda não leu', aviso: avisoCaderno(a.c, a.cads.find((x) => x.arquivo === d.chave)) }));
    add({ id: `dir:${d.chave}`, modulo: 'Diretrizes', prioridade: nivel(PESO.dir.peso + (pct(d.lidos, d.total) < 50 ? 1 : 0)), motivo: PESO.dir.motivo, titulo: `Cobrar a leitura de “${d.titulo}”`, detalhe: `${d.lidos} de ${d.total} já leram · leitura obrigatória`, pessoas, ordem: d.total - d.lidos });
  });

  // EPI: uma tarefa por ficha pendente
  fichasPendentes.value.forEach((f) => {
    add({ id: `epi:${f.id}`, modulo: 'Ficha de EPI', motivo: PESO.epi.motivo, prioridade: nivel(PESO.epi.peso + bonusIdade(f.dias)), titulo: `Colher a assinatura da ficha de EPI de ${f.c.nome}`, detalhe: `Ficha ${f.id} entregue em ${dataBr(f.data_entrega)} · pendente há ${f.dias} dias`, pessoas: [{ c: f.c, info: `Ficha ${f.id}`, aviso: linkCobranca(f.c) }], ordem: f.dias });
  });

  // Academia: uma tarefa por trilha obrigatória
  trilhasAgr.value.filter((t) => t.obrigatoria && t.completas < t.atribuidas).forEach((t) => {
    const pessoas = L.filter((a) => a.trilhas.some((x) => x.id === t.id && !x.completa)).map((a) => {
      const x = a.trilhas.find((y) => y.id === t.id);
      return { c: a.c, info: x.concluidos ? `${x.concluidos} de ${x.total} etapas` : 'Não começou', aviso: linkCobranca(a.c) };
    });
    add({ id: `aca:${t.id}`, modulo: 'Academia', motivo: PESO.aca.motivo, prioridade: nivel(PESO.aca.peso + (t.completas === 0 ? 1 : 0)), titulo: `Cobrar a conclusão da trilha “${t.titulo}”`, detalhe: `${t.completas} de ${t.atribuidas} concluíram · trilha obrigatória`, pessoas, ordem: t.atribuidas - t.completas });
  });

  const pesoDe = (t) => PESO[t.id.split(':')[0]]?.peso || 0;
  return out.sort((a, b) => b.prioridade - a.prioridade || pesoDe(b) - pesoDe(a) || b.ordem - a.ordem || a.titulo.localeCompare(b.titulo));
});
const filtrosTarefa = computed(() => [
  { id: 'todas', rotulo: 'Todas', n: tarefas.value.length },
  { id: 'urgentes', rotulo: 'Urgentes', n: tarefas.value.filter((t) => t.prioridade === 3).length },
  { id: 'andamento', rotulo: 'Em andamento', n: tarefas.value.filter((t) => t.andamento).length }
]);
const tarefasFiltradas = computed(() => tarefas.value.filter((t) => filtroTarefa.value === 'todas' || (filtroTarefa.value === 'urgentes' ? t.prioridade === 3 : t.andamento)));
const tarefasVisiveis = computed(() => (tarefasExpandidas.value ? tarefasFiltradas.value : tarefasFiltradas.value.slice(0, 6)));

// Cobrança: reaproveita o fluxo do RH (todas as pendências com link); só EPI/trilhas, manda um lembrete geral
function linkCobranca(c) {
  const geral = cobrancaGeral(c);
  if (geral) return geral;
  const a = todas.value.find((x) => x.c.matricula === c.matricula);
  const itens = [
    ...(a?.epi || []).filter((e) => !e.assinado_em).map((e) => `• Assinar a ficha de EPI entregue em ${dataBr(e.data_entrega)}`),
    ...(a?.trilhas || []).filter((t) => t.obrigatoria && !t.completa).map((t) => `• Concluir a trilha ${t.titulo} na Academia`)
  ];
  const texto = `Olá, ${primeiroNome(c.nome)}! Você tem pendências no Portal do Colaborador da Vila Boa:\n\n${itens.join('\n')}\n\nAcesse ${window.location.origin}${window.location.pathname} e entre com seu CPF.`;
  const digitos = (c.telefone || '').replace(/\D/g, '');
  return `https://api.whatsapp.com/send?${digitos ? `phone=55${digitos}&` : ''}text=${encodeURIComponent(texto)}`;
}

// ---------------------------------------------------------------- detalhe do colaborador
const detalheSel = ref(null); // { matricula, modulo }
const botaoFechar = ref(null);
usarFecharComEsc(() => fecharDetalhe(), () => !!detalheSel.value);
function abrirDetalhe(c, modulo = null) {
  const a = todas.value.find((x) => x.c.matricula === c.matricula);
  if (!a) return;
  const primeiroPendente = MODULOS.find((m) => a.m[m.id].kT - a.m[m.id].kO > 0)?.id;
  detalheSel.value = { matricula: c.matricula, modulo: modulo || primeiroPendente || 'hol' };
  nextTick(() => botaoFechar.value?.focus());
}
let abridorDoDetalhe = null;
watch(detalheSel, (novo, antigo) => {
  if (novo && !antigo) abridorDoDetalhe = document.activeElement;
  if (!novo && antigo) nextTick(() => abridorDoDetalhe?.focus?.());
});
function fecharDetalhe() { detalheSel.value = null; }
// O detalhe sempre reflete a análise atual (período/ano), mesmo se os filtros mudarem com ele aberto
const detalheAnalise = computed(() => (detalheSel.value ? todas.value.find((x) => x.c.matricula === detalheSel.value.matricula) : null));
const detalhe = computed(() => (detalheAnalise.value ? { ...detalheAnalise.value, modulo: detalheSel.value.modulo } : null));
const itensDetalhe = computed(() => {
  const a = detalheAnalise.value;
  if (!a) return [];
  const c = a.c;
  const aviso = (fn) => (podeAvisar.value && c.telefone ? fn() : null);
  switch (detalheSel.value.modulo) {
    case 'hol':
      return [...a.hol].sort((x, y) => chaveComp(y.competencia) - chaveComp(x.competencia)).map((h) => ({ titulo: h.descricao, feito: !!h.recebido_em, estado: h.recebido_em ? `Recebido ${dataBr(h.recebido_em)}` : 'Pendente', aviso: aviso(() => avisoHolerite(c, h)) }));
    case 'ponto':
      return a.ponto.map((p) => ({ titulo: p.mes_nome, feito: !!p.assinado_em, estado: p.assinado_em ? `Assinado ${dataBr(p.assinado_em)}` : 'Falta assinar', aviso: aviso(() => avisoPonto(c, p)) }));
    case 'dir':
      return [...a.cads].sort((x, y) => y.obrigatorio - x.obrigatorio).map((d) => ({ titulo: d.titulo, sub: `${d.categoria} · ${d.obrigatorio ? 'Obrigatória' : 'Opcional'}`, obrigatorio: d.obrigatorio, feito: !!d.lido_em, estado: d.lido_em ? `Lido ${dataBr(d.lido_em)}` : 'Não lido', aviso: aviso(() => avisoCaderno(c, d)) }));
    case 'epi':
      return a.epi.map((e) => ({ titulo: `Ficha ${e.id}`, sub: `Entrega em ${dataBr(e.data_entrega)} · ${plural(e.itens, 'item', 'itens')}${e.motivo ? ' · ' + e.motivo : ''}`, feito: !!e.assinado_em, estado: e.assinado_em ? 'Assinada' : `Pendente há ${diasDesde(e.data_entrega)} dias`, aviso: null }));
    default:
      return [...a.trilhas].sort((x, y) => y.obrigatoria - x.obrigatoria).map((t) => ({ titulo: t.titulo, sub: `${t.obrigatoria ? 'Obrigatória' : 'Por função'} · ${t.concluidos} de ${t.total} etapas${t.certificado_em ? ' · certificado em ' + dataBr(t.certificado_em) : ''}`, obrigatorio: t.obrigatoria, feito: t.completa, estado: t.completa ? 'Concluída' : `${pct(t.concluidos, t.total)}%`, aviso: null }));
  }
});

// ---------------------------------------------------------------- exportação CSV
function celulaCsv(v) {
  let s = String(v ?? '');
  if (/^[=+\-@\t\r]/.test(s)) s = "'" + s; // evita fórmula quando abrir no Excel
  return `"${s.replace(/"/g, '""')}"`;
}
function exportarCsv() {
  const cab = ['Matrícula', 'Nome', 'Empresa', 'Departamento', 'Função', 'Holerites recebidos', 'Holerites total', 'Espelhos assinados', 'Espelhos total', 'Diretrizes obrigatórias lidas', 'Diretrizes obrigatórias total', 'Fichas de EPI assinadas', 'Fichas de EPI total', 'Trilhas obrigatórias concluídas', 'Trilhas obrigatórias total', 'Trilhas concluídas (todas)', 'Certificados no período', 'Pendências obrigatórias', 'Situação'];
  const linhas = matrizOrdenada.value.map((a) => [
    a.c.matricula, a.c.nome, a.c.empresa, a.c.departamento, a.c.cargo,
    a.m.hol.ok, a.m.hol.total, a.m.ponto.ok, a.m.ponto.total, a.m.dir.ok, a.m.dir.total, a.m.epi.ok, a.m.epi.total,
    a.m.aca.kO, a.m.aca.kT, a.completas, a.certs, a.score, a.score ? 'Com pendências' : 'Em dia'
  ]);
  const csv = '﻿' + [cab, ...linhas].map((l) => l.map(celulaCsv).join(';')).join('\r\n');
  const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
  const link = document.createElement('a');
  link.href = url;
  link.download = `acompanhamento-${ano.value}-${periodoId.value}.csv`;
  document.body.appendChild(link);
  link.click();
  link.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}
</script>

<style scoped>
.campo { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.campo > span { font-size: 10px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: #64748b; }
.campo select, .campo input { height: 36px; padding: 0 10px; border-radius: 8px; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 13px; color: #334155; outline: none; max-width: 100%; }
.campo select:focus-visible, .campo input:focus-visible { border-color: #9c2337; box-shadow: 0 0 0 3px rgba(156, 35, 55, .15); background: #fff; }

.cap { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1rem; scroll-margin-top: 1rem; }
@media (min-width: 640px) { .cap { padding: 1.25rem; } }
.cap-num { font-size: 11px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #9c2337; }
.cap-titulo { margin-top: .125rem; font-size: 1rem; font-weight: 600; letter-spacing: -.01em; color: #0f172a; }
@media (min-width: 640px) { .cap-titulo { font-size: 1.125rem; } }
.cap-insight { display: flex; gap: .5rem; margin: .5rem 0 .875rem; padding: .5rem .75rem; border-radius: .625rem; background: #f1f5f9; color: #334155; font-size: 12px; line-height: 1.45; }
.sub { font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: #64748b; margin-bottom: .625rem; }
.vazio { padding: 2rem 0; text-align: center; font-size: 13px; color: #94a3b8; }

.destaque { border: 1px solid #e2e8f0; border-radius: .875rem; padding: .875rem 1rem; background: #f8fafc; }
.destaque h4 { font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: .375rem; }

.matriz { border: 1px solid #e2e8f0; border-radius: .875rem; overflow: hidden; }
.matriz-linha { display: grid; grid-template-columns: minmax(0, 1fr) repeat(5, 44px) 34px; gap: 4px; align-items: center; padding: 3px 8px; border-top: 1px solid #f1f5f9; }
.matriz-linha:first-child { border-top: 0; }
.matriz-linha.cab { background: #f8fafc; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: #475569; padding-top: 8px; padding-bottom: 8px; align-items: end; }
@media (min-width: 640px) {
  .matriz-linha { grid-template-columns: minmax(0, 1fr) repeat(5, 76px) 52px; gap: 8px; padding: 3px 14px; }
  .matriz-linha.cab { font-size: 10px; }
}

.cel { display: inline-flex; align-items: center; justify-content: center; width: 100%; height: 30px; padding: 0 4px; border-radius: 8px; font-size: 11px; font-weight: 700; font-variant-numeric: tabular-nums; border: 1px solid transparent; cursor: pointer; }
@media (min-width: 640px) { .cel { font-size: 12px; height: 32px; } }
.cel:focus-visible { outline: 2px solid #9c2337; outline-offset: 1px; }
.cel.ok { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
.cel.parcial { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.cel.pend { background: #e2e8f0; color: #334155; border-color: #cbd5e1; }
.cel.na { background: #f8fafc; color: #cbd5e1; border-color: #f1f5f9; }
.cel.mini { width: 14px; min-width: 14px; height: 14px; padding: 0; border-radius: 4px; cursor: default; }

.badge { display: inline-flex; align-items: center; padding: 2px 10px; border-radius: 9999px; font-size: 11px; font-weight: 600; border: 1px solid transparent; }
.badge.pendente { background: #fffbeb; color: #92400e; border-color: #fde68a; }
.badge.neutro { background: #f8fafc; color: #475569; border-color: #e2e8f0; }
.badge.critico { background: #fff1f2; color: #9f1239; border-color: #fecdd3; }
</style>
