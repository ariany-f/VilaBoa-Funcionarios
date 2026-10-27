<template>
  <div class="space-y-6 animate-slide-up">
    <!-- Cabeçalho + abas -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
      <div>
        <h2 class="text-xl font-semibold text-slate-900 tracking-tight">Painel do RH</h2>
        <p class="text-xs text-slate-500 mt-0.5">Acompanhe recebimentos, assinaturas e leituras dos colaboradores.</p>
      </div>
      <div v-if="!abaExterna" class="flex items-center gap-1 bg-white border border-slate-200 rounded-full p-1 self-start">
        <button
          v-for="a in abas"
          :key="a.id"
          type="button"
          @click="aba = a.id"
          class="h-8 px-4 rounded-full text-xs font-semibold transition cursor-pointer"
          :class="aba === a.id ? 'bg-[#141414] text-white' : 'text-slate-600 hover:text-slate-900'"
        >
          {{ a.rotulo }}
        </button>
      </div>
    </div>

    <div v-if="carregando" class="py-16 text-center text-sm text-slate-400">Carregando painel...</div>
    <div v-else-if="erro" class="py-16 text-center text-sm text-rose-700">{{ erro }}</div>

    <!-- ===================== ACOMPANHAMENTO ===================== -->
    <template v-else-if="aba === 'acompanhamento'">
      <div class="grid sm:grid-cols-3 gap-4">
        <button
          v-for="k in indicadores"
          :key="k.rotulo"
          type="button"
          @click="k.abrir()"
          class="text-left bg-white rounded-2xl border border-slate-200 hover:border-slate-300 p-5 space-y-3 cursor-pointer transition"
          title="Ver detalhes"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-600">{{ k.rotulo }}</span>
            <span class="text-xs font-semibold text-slate-900">{{ k.feito }} de {{ k.total }}</span>
          </div>
          <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
            <div class="h-full rounded-full bg-[#9c2337]" :style="{ width: (k.total ? Math.round((k.feito / k.total) * 100) : 0) + '%' }"></div>
          </div>
          <p class="text-xs text-slate-500">{{ k.descricao }}</p>
        </button>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800">Colaboradores</h3>
          <div class="flex flex-wrap items-center gap-2">
            <select v-model="tipoFiltro" class="h-9 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-700 outline-none">
              <option value="todos">Todos os tipos</option>
              <option value="holerites">Holerites</option>
              <option value="ponto">Espelho de ponto</option>
              <option value="cadernos">Cadernos</option>
            </select>
            <select v-model="filtro" class="h-9 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-700 outline-none">
              <option value="todos">Todos</option>
              <option value="pendentes">Com pendências</option>
              <option value="em_dia">Em dia</option>
            </select>
            <select v-model="ano" @change="carregar" class="h-9 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-700 outline-none">
              <option v-for="a in anos" :key="a" :value="a">{{ a }}</option>
            </select>
            <input v-model="busca" type="text" placeholder="Buscar nome ou matrícula..." class="h-9 w-56 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm" :class="tipoFiltro === 'todos' ? 'min-w-[760px]' : 'min-w-[480px]'">
            <thead>
              <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 text-left">
                <th class="py-3 pr-3">Colaborador</th>
                <th v-if="mostraColuna('holerites')" class="py-3 px-3">Holerites recebidos</th>
                <th v-if="mostraColuna('ponto')" class="py-3 px-3">Espelho de ponto</th>
                <th v-if="mostraColuna('cadernos')" class="py-3 px-3">Cadernos</th>
                <th class="py-3 pl-3 text-right">Ações</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <template v-for="c in colaboradoresFiltrados" :key="c.matricula">
                <tr class="hover:bg-slate-50/70 cursor-pointer" @click="expandido = expandido === c.matricula ? null : c.matricula">
                  <td class="py-3 pr-3">
                    <span class="block font-medium text-slate-900">{{ c.nome }}</span>
                    <span class="block text-xs text-slate-400">{{ c.matricula }} · {{ c.cargo }}</span>
                  </td>
                  <td v-if="mostraColuna('holerites')" class="py-3 px-3">
                    <button type="button" @click.stop="abrirHoleritesDoColaborador(c)" class="badge cursor-pointer" :class="c.holerites.recebidos === c.holerites.total ? 'ok' : 'pendente'" title="Ver holerites">
                      {{ c.holerites.recebidos }} de {{ c.holerites.total }}
                      <AppIcon name="chevron-right" size="12" class="ml-1" />
                    </button>
                  </td>
                  <td v-if="mostraColuna('ponto')" class="py-3 px-3">
                    <span v-if="!c.ponto" class="text-xs text-slate-400">Sem espelho</span>
                    <button v-else type="button" @click.stop="abrirPontoDoColaborador(c)" class="badge cursor-pointer" :class="c.ponto.assinado_em ? 'ok' : 'pendente'" title="Ver espelhos">
                      {{ c.ponto.assinado_em ? 'Assinado' : 'Pendente' }} · {{ c.ponto.competencia }}
                      <AppIcon name="chevron-right" size="12" class="ml-1" />
                    </button>
                  </td>
                  <td v-if="mostraColuna('cadernos')" class="py-3 px-3">
                    <button
                      v-if="c.cadernos.obrigatorios_total || c.cadernos.opcionais_total"
                      type="button"
                      @click.stop="abrirLeiturasDoColaborador(c)"
                      class="text-left cursor-pointer group"
                      title="Ver quais cadernos leu"
                    >
                      <span v-if="c.cadernos.obrigatorios_total" class="badge group-hover:brightness-95" :class="c.cadernos.obrigatorios_lidos === c.cadernos.obrigatorios_total ? 'ok' : 'pendente'">
                        {{ c.cadernos.obrigatorios_lidos }} de {{ c.cadernos.obrigatorios_total }} obrigatórios
                        <AppIcon name="chevron-right" size="12" class="ml-1" />
                      </span>
                      <span v-if="c.cadernos.opcionais_total" class="block text-[11px] text-slate-400 mt-1 group-hover:text-slate-600">
                        {{ c.cadernos.opcionais_lidos }} de {{ c.cadernos.opcionais_total }} opcionais lidos
                      </span>
                    </button>
                  </td>
                  <td class="py-3 pl-3 text-right" @click.stop>

                    <a
                      v-if="temPendencia(c) && c.telefone && podeAvisar"
                      :href="cobrancaGeral(c)"
                      target="_blank"
                      rel="noopener"
                      class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg bg-slate-100 text-slate-700 hover:bg-[#1DA851] hover:text-white text-xs font-semibold transition"
                      title="Enviar todas as pendências pelo WhatsApp, com o link de cada uma"
                    >
                      <AppIcon name="whatsapp" size="13" />
                      Cobrar
                    </a>
                    <span v-else-if="!temPendencia(c)" class="text-xs text-emerald-700 font-medium">Em dia</span>
                    <span v-else class="text-xs text-amber-700 font-medium">Pendente</span>
                  </td>
                </tr>

                <!-- Detalhe do colaborador -->
                <tr v-if="expandido === c.matricula" class="bg-slate-50/60">
                  <td :colspan="tipoFiltro === 'todos' ? 5 : 3" class="px-3 py-4">
                    <div class="grid md:grid-cols-3 gap-4 text-xs">
                      <div>
                        <h4 class="font-semibold uppercase tracking-wider text-slate-600 mb-2">Holerites {{ ano }}</h4>
                        <ul class="space-y-1">
                          <li v-for="(h, i) in c.holerites.detalhe" :key="i" class="flex justify-between gap-2">
                            <span class="text-slate-700 truncate">{{ h.descricao }}</span>
                            <span :class="h.recebido_em ? 'text-emerald-700' : 'text-amber-700'" class="shrink-0">{{ h.recebido_em || 'Pendente' }}</span>
                          </li>
                        </ul>
                      </div>
                      <div>
                        <h4 class="font-semibold uppercase tracking-wider text-slate-600 mb-2">Espelho de ponto</h4>
                        <p v-if="!c.ponto" class="text-slate-400">Nenhum espelho importado.</p>
                        <p v-else class="text-slate-700">
                          {{ c.ponto.mes_nome }}:
                          <strong :class="c.ponto.assinado_em ? 'text-emerald-700' : 'text-amber-700'">{{ c.ponto.assinado_em ? 'assinado em ' + c.ponto.assinado_em : 'aguardando assinatura' }}</strong>
                        </p>
                      </div>
                      <div>
                        <h4 class="font-semibold uppercase tracking-wider text-slate-600 mb-2">Cadernos</h4>
                        <ul class="space-y-1">
                          <li v-for="(cad, i) in c.cadernos.detalhe" :key="i" class="flex justify-between gap-2">
                            <span class="text-slate-700 truncate">
                              {{ cad.titulo }}
                              <span class="text-[10px] uppercase font-semibold" :class="cad.obrigatorio ? 'text-[#9c2337]' : 'text-slate-400'">· {{ cad.obrigatorio ? 'obrigatório' : 'opcional' }}</span>
                            </span>
                            <span :class="cad.lido_em ? 'text-emerald-700' : (cad.obrigatorio ? 'text-amber-700' : 'text-slate-400')" class="shrink-0">{{ cad.lido_em || 'Não lido' }}</span>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
              <tr v-if="!colaboradoresFiltrados.length">
                <td :colspan="tipoFiltro === 'todos' ? 5 : 3" class="py-10 text-center text-sm text-slate-400">Nenhum colaborador encontrado.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- ===================== CADERNOS ===================== -->
    <template v-else-if="aba === 'cadernos'">
      <div class="grid lg:grid-cols-[minmax(0,1fr)_360px] gap-6 items-start">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 space-y-4">
          <div>
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800">Cadernos publicados</h3>
            <p class="text-xs text-slate-500 mt-0.5">Ao substituir um PDF por outro com o mesmo nome, o público precisa ler de novo.</p>
          </div>

          <p v-if="mensagemCadernos" class="text-xs" :class="mensagemCadernosErro ? 'text-rose-700' : 'text-emerald-700'">{{ mensagemCadernos }}</p>

          <ul class="divide-y divide-slate-100 border-t border-slate-100">
            <li v-for="cad in dados.cadernos" :key="cad.arquivo" class="py-3">
              <div class="flex items-center gap-3">
              <span class="w-8 h-10 rounded bg-[#9c2337] text-white text-[9px] font-semibold flex items-end justify-center pb-1 shrink-0">PDF</span>
              <span class="min-w-0 flex-1">
                <span class="block text-sm font-medium text-slate-800 truncate">{{ cad.titulo }}</span>
                <span class="block text-xs text-slate-400 truncate">
                  {{ cad.categoria }} · {{ cad.obrigatorio ? 'Obrigatório' : 'Opcional' }} · {{ descreverPublico(cad) }}
                </span>
              </span>
              <button
                type="button"
                @click="abrirLeiturasDoCaderno(cad)"
                class="badge shrink-0 cursor-pointer"
                :class="cad.total_leram === cad.total_publico ? 'ok' : (cad.obrigatorio ? 'pendente' : 'neutro')"
                title="Ver quem leu e quem não leu"
              >
                {{ cad.total_leram }} de {{ cad.total_publico }} leram
                <AppIcon name="chevron-right" size="12" class="ml-1" />
              </button>
              <button type="button" @click="verCaderno(cad)" class="w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-800 flex items-center justify-center cursor-pointer shrink-0" title="Ver caderno" aria-label="Ver caderno">
                <AppIcon name="eye" size="14" />
              </button>
              <button v-if="pode(colaborador, 'rh_cadernos', 'editar')" type="button" @click="editarCaderno(cad)" class="w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-800 flex items-center justify-center cursor-pointer shrink-0" title="Editar categoria e público" aria-label="Editar caderno">
                <AppIcon name="edit" size="14" />
              </button>
              <button v-if="pode(colaborador, 'rh_cadernos', 'remover')" type="button" @click="removerCaderno(cad)" class="w-8 h-8 rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-700 flex items-center justify-center cursor-pointer shrink-0" title="Remover caderno" aria-label="Remover caderno">
                <AppIcon name="x" size="15" />
              </button>
              </div>
            </li>
            <li v-if="!dados.cadernos.length" class="py-10 text-center text-sm text-slate-400">Nenhum caderno publicado.</li>
          </ul>
        </div>

        <!-- Formulário: novo caderno ou edição -->
        <form v-if="formCaderno.arquivo ? pode(colaborador, 'rh_cadernos', 'editar') : pode(colaborador, 'rh_cadernos', 'publicar')" class="bg-white rounded-2xl border border-slate-200 p-5 space-y-4 lg:sticky lg:top-0" @submit.prevent="salvarCaderno">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800">{{ formCaderno.arquivo ? 'Editar caderno' : 'Novo caderno' }}</h3>
            <button v-if="formCaderno.arquivo" type="button" @click="limparFormCaderno" class="text-xs text-slate-500 hover:text-slate-900 cursor-pointer">Cancelar</button>
          </div>

          <p v-if="formCaderno.arquivo" class="text-xs text-slate-600 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 truncate">{{ formCaderno.arquivo }}</p>
          <label v-else class="block text-xs font-medium text-slate-700 space-y-1.5">
            <span>Arquivo PDF</span>
            <input ref="campoPdf" type="file" @change="sugerirTitulo" accept="application/pdf,.pdf" required class="block w-full text-sm text-slate-600 file:mr-3 file:h-9 file:px-4 file:rounded-full file:border-0 file:bg-slate-100 file:text-slate-700 file:font-semibold file:cursor-pointer" />
          </label>

          <label class="block text-xs font-medium text-slate-700 space-y-1.5">
            <span>Nome do caderno</span>
            <input v-model="formCaderno.titulo" type="text" maxlength="120" required placeholder="Ex.: Manual do Colaborador" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
          </label>

          <label class="block text-xs font-medium text-slate-700 space-y-1.5">
            <span>Categoria</span>
            <select v-model="formCaderno.categoria" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none">
              <option v-for="cat in dados.categorias" :key="cat" :value="cat">{{ cat }}</option>
            </select>
          </label>

          <!-- Categorias: o RH cadastra e remove -->
          <div v-if="pode(colaborador, 'rh_cadernos', 'editar')" class="space-y-2">
            <div class="flex gap-2">
              <input
                v-model="novaCategoria"
                type="text"
                maxlength="40"
                placeholder="Nova categoria"
                @keydown.enter.prevent="adicionarCategoria"
                class="flex-1 min-w-0 h-9 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none"
              />
              <button type="button" @click="adicionarCategoria" :disabled="!novaCategoria.trim()" class="h-9 px-3 rounded-lg bg-slate-100 hover:bg-slate-200 disabled:opacity-40 text-xs font-semibold text-slate-700 cursor-pointer">Adicionar</button>
            </div>
            <div class="flex flex-wrap gap-1.5">
              <span v-for="cat in dados.categorias" :key="cat" class="inline-flex items-center gap-1 h-7 pl-2.5 pr-1 rounded-full bg-slate-100 text-[11px] text-slate-700">
                {{ cat }}
                <button
                  v-if="cat !== 'Outros'"
                  type="button"
                  @click="removerCategoria(cat)"
                  class="w-5 h-5 rounded-full hover:bg-slate-200 text-slate-400 hover:text-slate-700 flex items-center justify-center cursor-pointer"
                  :title="`Remover ${cat}`"
                  :aria-label="`Remover categoria ${cat}`"
                ><AppIcon name="x" size="10" /></button>
              </span>
            </div>
          </div>

          <div class="text-xs font-medium text-slate-700 space-y-1.5">
            <span>Leitura</span>
            <div class="grid grid-cols-2 gap-2">
              <button type="button" @click="formCaderno.obrigatorio = true" class="h-10 rounded-lg border text-sm font-semibold cursor-pointer" :class="formCaderno.obrigatorio ? 'border-[#9c2337] bg-[#fdf2f4] text-[#9c2337]' : 'border-slate-200 text-slate-600'">Obrigatória</button>
              <button type="button" @click="formCaderno.obrigatorio = false" class="h-10 rounded-lg border text-sm font-semibold cursor-pointer" :class="!formCaderno.obrigatorio ? 'border-slate-800 bg-slate-100 text-slate-900' : 'border-slate-200 text-slate-600'">Opcional</button>
            </div>
          </div>

          <label class="block text-xs font-medium text-slate-700 space-y-1.5">
            <span>Quem tem acesso</span>
            <select v-model="formCaderno.publico" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none">
              <option value="todos">Todos os colaboradores</option>
              <option value="departamentos">Departamentos específicos</option>
              <option value="colaboradores">Colaboradores específicos</option>
            </select>
          </label>

          <div v-if="formCaderno.publico !== 'todos'" class="max-h-48 overflow-y-auto border border-slate-200 rounded-lg p-2 space-y-1">
            <label
              v-for="op in opcoesPublico"
              :key="op.valor"
              class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-slate-50 text-sm text-slate-700 cursor-pointer"
            >
              <input type="checkbox" :value="op.valor" v-model="formCaderno.valores" class="accent-[#9c2337]" />
              <span class="truncate">{{ op.rotulo }}</span>
            </label>
          </div>

          <button type="submit" :disabled="enviando" class="w-full h-11 rounded-full bg-[#9c2337] hover:bg-[#831b2d] disabled:opacity-50 text-white text-sm font-semibold cursor-pointer">
            {{ enviando ? 'Salvando...' : (formCaderno.arquivo ? 'Salvar alterações' : 'Publicar caderno') }}
          </button>
        </form>
      </div>
    </template>

    <!-- ===================== HOLERITES ===================== -->
    <template v-else-if="aba === 'holerites'">
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="p-5 sm:p-6 space-y-4 border-b border-slate-100">
          <div class="flex flex-col lg:flex-row lg:items-center gap-3">
            <div class="flex-1">
              <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800 whitespace-nowrap">Holerites por colaborador</h3>
              <p class="text-xs text-slate-400 mt-0.5">A consulta do RH não registra recebimento no lugar do colaborador.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
              <SeletorColaborador v-model="matriculaHolerite" :colaboradores="dados.colaboradores" @change="carregarHolerites" />
              <select v-model="anoHolerite" @change="carregarHolerites" class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none">
                <option v-for="a in anos" :key="a" :value="a">{{ a }}</option>
              </select>
            </div>
          </div>
          <div v-if="holeritesRh.length && !carregandoHolerites" class="flex flex-wrap gap-2">
            <span class="chip-resumo">{{ holeritesRh.length }} holerites</span>
            <span class="chip-resumo ok">{{ holeritesRh.filter(h => h.aceite?.confirmado).length }} recebidos</span>
            <span class="chip-resumo pendente">{{ holeritesRh.filter(h => !h.aceite?.confirmado).length }} não recebidos</span>
          </div>
        </div>

        <div v-if="carregandoHolerites" class="py-12 text-center text-sm text-slate-400">Carregando holerites...</div>
        <p v-else-if="!holeritesRh.length" class="py-12 text-center text-sm text-slate-400">Nenhum holerite em {{ anoHolerite }}.</p>
        <ul v-else class="divide-y divide-slate-100">
          <li v-for="h in holeritesRh" :key="h.id">
            <button type="button" @click="holeriteAberto = h" class="linha-lista">
              <span class="bloco-mes">
                <span class="mes">{{ h.mes_nome.slice(0, 3) }}</span>
                <span class="ano">{{ h.ano }}</span>
              </span>
              <span class="min-w-0 flex-1">
                <span class="block text-sm font-semibold text-slate-800 truncate">{{ h.mes_nome }} / {{ h.ano }}</span>
                <span class="block text-xs text-slate-400 truncate">{{ h.tipo_folha || 'Folha Mensal' }} · pago em {{ h.data_pagamento }}</span>
              </span>
              <span class="hidden sm:block text-sm font-semibold font-mono text-slate-800 shrink-0">{{ moedaRh(h.salario_liquido) }}</span>
              <span class="flex flex-col items-end shrink-0">
                <span class="badge" :class="h.aceite?.confirmado ? 'ok' : 'pendente'">{{ h.aceite?.confirmado ? 'Recebido' : 'Não recebido' }}</span>
                <span v-if="h.aceite?.confirmado" class="hidden sm:block text-[10px] text-slate-400 mt-1">{{ h.aceite.data_hora }}</span>
              </span>
              <AppIcon name="chevron-right" size="16" class="text-slate-300 shrink-0" />
            </button>
          </li>
        </ul>
      </div>

      <HoleriteModal
        v-if="holeriteAberto"
        :holerite="holeriteAberto"
        consulta-rh
        :colaborador="dados.colaboradores.find(c => c.matricula === matriculaHolerite) || {}"
        @close="holeriteAberto = null"
      />
    </template>

    <!-- ===================== ESPELHO DE PONTO ===================== -->
    <template v-else-if="aba === 'ponto'">
      <!-- Lista de todos os espelhos (colaborador × competência) -->
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="p-5 sm:p-6 space-y-4 border-b border-slate-100">
          <div class="flex flex-col lg:flex-row lg:items-center gap-3">
            <div class="flex-1">
              <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800 whitespace-nowrap">Espelhos de ponto</h3>
              <p class="text-xs text-slate-400 mt-0.5">Toque em um espelho para ver os dias e a assinatura.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
              <div class="flex items-center gap-1">
                <SeletorColaborador v-model="matriculaPonto" :colaboradores="dados.colaboradores" class="flex-1" />
                <button v-if="matriculaPonto" type="button" @click="matriculaPonto = ''" class="w-10 h-10 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center cursor-pointer shrink-0" title="Ver todos os colaboradores" aria-label="Ver todos os colaboradores">
                  <AppIcon name="x" size="14" />
                </button>
              </div>
              <select v-model="filtroCompetenciaPonto" class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-700 outline-none">
                <option value="">Todos os meses</option>
                <option v-for="c in competenciasPonto" :key="c.competencia" :value="c.competencia">{{ c.mes_nome }}</option>
              </select>
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <button v-for="f in filtrosSituacaoPonto" :key="f.id" type="button" @click="filtroSituacaoPonto = f.id" class="chip-resumo cursor-pointer" :class="[f.classe, filtroSituacaoPonto === f.id ? 'ativo' : '']">
              {{ f.rotulo }} · {{ f.total }}
            </button>
          </div>
        </div>

        <p v-if="!espelhosFiltrados.length" class="py-12 text-center text-sm text-slate-400">Nenhum espelho encontrado.</p>
        <ul v-else class="divide-y divide-slate-100">
          <li v-for="e in espelhosFiltrados" :key="e.matricula + e.competencia">
            <button type="button" @click="abrirEspelhoRh(e)" class="linha-lista">
              <span class="bloco-mes">
                <span class="mes">{{ e.mes_nome.slice(0, 3) }}</span>
                <span class="ano">{{ e.competencia.slice(3) }}</span>
              </span>
              <span class="min-w-0 flex-1">
                <span class="block text-sm font-semibold text-slate-800 truncate">{{ e.nome }}</span>
                <span class="block text-xs text-slate-400 truncate">{{ e.matricula }} · {{ e.mes_nome }}</span>
              </span>
              <span class="flex flex-col items-end shrink-0">
                <span class="badge" :class="e.assinado_em ? 'ok' : 'pendente'">{{ e.assinado_em ? 'Assinado' : 'Falta assinar' }}</span>
                <span v-if="e.assinado_em" class="hidden sm:block text-[10px] text-slate-400 mt-1">{{ e.assinado_em }}</span>
              </span>
              <AppIcon name="chevron-right" size="16" class="text-slate-300 shrink-0" />
            </button>
          </li>
        </ul>
      </div>

      <!-- Abre só o detalhe do espelho escolhido (o modal do PontoView) -->
      <div v-if="espelhoRh" class="hidden">
        <PontoView :key="espelhoRh.matricula + espelhoRh.competencia" :matricula="espelhoRh.matricula" :competencia-inicial="espelhoRh.competencia" somente-leitura />
      </div>

      <div v-if="pode(colaborador, 'rh_ponto', 'importar')" class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 space-y-5 max-w-3xl">
        <div>
          <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800">Importar espelho de ponto</h3>
          <p class="text-xs text-slate-500 mt-0.5">Uma planilha com os espelhos de todos os colaboradores do mês. Cada espelho importado substitui o anterior do mesmo mês e fica disponível para assinatura.</p>
        </div>

        <form class="space-y-4" @submit.prevent="importarPonto">
          <label class="block text-xs font-medium text-slate-700 space-y-1.5">
            <span>Planilha (CSV)</span>
            <input ref="campoCsv" type="file" accept=".csv,text/csv" required class="block w-full text-sm text-slate-600 file:mr-3 file:h-9 file:px-4 file:rounded-full file:border-0 file:bg-slate-100 file:text-slate-700 file:font-semibold file:cursor-pointer" />
          </label>
          <div class="flex flex-wrap items-center gap-3">
            <button type="submit" :disabled="importando" class="h-10 px-5 rounded-full bg-[#9c2337] hover:bg-[#831b2d] disabled:opacity-50 text-white text-sm font-semibold cursor-pointer">
              {{ importando ? 'Importando...' : 'Importar espelhos' }}
            </button>
            <button type="button" @click="baixarModelo" class="text-xs font-semibold text-slate-600 hover:text-slate-900 cursor-pointer">Baixar planilha modelo</button>
          </div>
        </form>

        <p v-if="mensagemPonto" class="text-xs" :class="mensagemPontoErro ? 'text-rose-700' : 'text-emerald-700'">{{ mensagemPonto }}</p>

        <div class="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg p-3 space-y-1">
          <p class="font-semibold text-slate-700">Formato do CSV</p>
          <p>Uma linha por dia de cada colaborador, separada por ponto e vírgula: <code class="font-mono">matricula;data;entrada1;saida1;entrada2;saida2;ocorrencia;link_assinatura;saldo_banco</code></p>
          <p><strong>link_assinatura</strong> é o link do documento daquele espelho no Autentique (um por colaborador e mês). Basta preencher na primeira linha de cada colaborador; o mesmo vale para <strong>saldo_banco</strong> (opcional).</p>
          <p>Datas em dd/mm/aaaa e horários em hh:mm. Deixe as horas vazias em folgas e informe a ocorrência (ex.: DSR, Feriado, Atestado). O saldo diário considera jornada de 8 horas.</p>
        </div>
      </div>
    </template>

    <!-- Modal: quem leu / o que foi lido -->
    <Teleport to="body">
      <div v-if="modalLeitura" class="fixed inset-0 z-[60] bg-black/40 flex items-end sm:items-center justify-center p-0 sm:p-4" @click.self="modalLeitura = null">
        <div class="w-full sm:max-w-lg bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl max-h-[85vh] flex flex-col">
          <div class="px-5 pt-5 pb-4 border-b border-slate-100 flex items-start justify-between gap-3">
            <div class="min-w-0">
              <h3 class="text-base font-semibold text-slate-900">{{ modalLeitura.titulo }}</h3>
              <p class="text-xs text-slate-500 mt-0.5">{{ modalLeitura.subtitulo }}</p>
            </div>
            <button type="button" @click="modalLeitura = null" class="w-9 h-9 rounded-full hover:bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer shrink-0" aria-label="Fechar">
              <AppIcon name="x" size="16" />
            </button>
          </div>

          <div class="flex gap-1 px-5 pt-3">
            <button
              v-for="(g, i) in modalLeitura.grupos"
              :key="g.rotulo"
              type="button"
              @click="grupoModal = i"
              class="h-9 px-4 rounded-full text-xs font-semibold transition cursor-pointer"
              :class="grupoModal === i ? 'bg-[#141414] text-white' : 'bg-slate-100 text-slate-600 hover:text-slate-900'"
            >{{ g.rotulo }} ({{ g.itens.length }})</button>
          </div>

          <ul class="overflow-y-auto px-5 py-3 divide-y divide-slate-100">
            <li v-for="(item, i) in modalLeitura.grupos[grupoModal].itens" :key="i" class="py-3 flex items-center justify-between gap-3">
              <span class="min-w-0">
                <span class="block text-sm font-medium text-slate-900 truncate">{{ item.nome }}</span>
                <span class="block text-xs text-slate-400 truncate">{{ item.detalhe }}</span>
              </span>
              <span class="flex items-center gap-2 shrink-0">
                <!-- A aba já diz a situação; na linha só aparece a data (quando houver) -->
                <span v-if="item.data" class="text-xs text-slate-500">{{ item.data }}</span>
                <a
                  v-if="item.enviar"
                  :href="item.enviar"
                  target="_blank"
                  rel="noopener"
                  class="h-8 px-3 rounded-lg bg-[#1DA851] hover:bg-[#178F44] text-white text-xs font-semibold inline-flex items-center gap-1.5"
                  title="Enviar aviso pelo WhatsApp com o link direto"
                >
                  <AppIcon name="whatsapp" size="13" />
                  Avisar
                </a>
              </span>
            </li>
            <li v-if="!modalLeitura.grupos[grupoModal].itens.length" class="py-8 text-center text-sm text-slate-400">{{ modalLeitura.grupos[grupoModal].vazio }}</li>
          </ul>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import AppIcon from './AppIcon.vue';
import { pode } from '../utils/permissoes.js';
import { tokenAdmin } from '../utils/sessaoAdmin.js';
import { usarFecharComEsc } from '../utils/fecharComEsc.js';
import { avisoCaderno, avisoPonto, avisoHolerite, cobrancaGeral } from '../utils/avisosWhatsapp.js';
import PontoView from './PontoView.vue';
import SeletorColaborador from './SeletorColaborador.vue';
import HoleriteModal from './HoleriteModal.vue';

const props = defineProps({
  // Usuário do painel (permissões do perfil)
  colaborador: { type: Object, required: true },
  // Seção escolhida no menu do /admin (esconde as abas internas)
  abaExterna: { type: String, default: '' }
});

// Cada aba aparece só para perfis com a permissão de visualizar a tela correspondente
const abas = computed(() => [
  { id: 'acompanhamento', rotulo: 'Acompanhamento', tela: 'rh_acompanhamento' },
  { id: 'cadernos', rotulo: 'Cadernos', tela: 'rh_cadernos' },
  { id: 'ponto', rotulo: 'Espelho de ponto', tela: 'rh_ponto' },
  { id: 'holerites', rotulo: 'Holerites', tela: 'rh_holerites' }
].filter((a) => pode(props.colaborador, a.tela, 'visualizar')));
const aba = ref(props.abaExterna || abas.value[0]?.id || 'acompanhamento');
watch(() => props.abaExterna, (v) => { if (v) aba.value = v; });
const anoAtual = new Date().getFullYear();
const anos = [anoAtual, anoAtual - 1, anoAtual - 2];
const ano = ref(anoAtual);

const dados = ref({ colaboradores: [], cadernos: [] });
const carregando = ref(true);
const erro = ref('');
const filtro = ref('todos');
const busca = ref('');
const expandido = ref(null);

const enviando = ref(false);
const mensagemCadernos = ref('');
const mensagemCadernosErro = ref(false);

const campoCsv = ref(null);
const importando = ref(false);
const mensagemPonto = ref('');
const mensagemPontoErro = ref(false);

function auth() {
  return { 'Authorization': `Bearer ${tokenAdmin()}` };
}

// Modal de leituras: { titulo, subtitulo, grupos: [{ rotulo, vazio, itens: [{ nome, detalhe, data }] }] }
const modalLeitura = ref(null);
const grupoModal = ref(0);
usarFecharComEsc(() => { modalLeitura.value = null; }, () => !!modalLeitura.value);

// Aviso individual pelo WhatsApp (só para quem tem a permissão e telefone cadastrado)
const podeAvisar = computed(() => pode(props.colaborador, 'rh_acompanhamento', 'cobrar_pendencias'));
const aviso = (pessoa, gerar) => (podeAvisar.value && pessoa.telefone ? gerar() : null);

function abrirLeiturasDoCaderno(cad) {
  const pessoa = (l) => ({ nome: l.nome, detalhe: `${l.matricula} · ${l.cargo}`, data: l.lido_em, enviar: l.lido_em ? null : aviso(l, () => avisoCaderno(l, cad)) });
  modalLeitura.value = {
    titulo: cad.titulo,
    subtitulo: `${cad.categoria} · ${cad.obrigatorio ? 'Leitura obrigatória' : 'Leitura opcional'} · ${cad.total_leram} de ${cad.total_publico} leram`,
    grupos: [
      { rotulo: 'Não leram', vazio: 'Todos leram.', itens: cad.leitores.filter((l) => !l.lido_em).map(pessoa) },
      { rotulo: 'Leram', vazio: 'Ninguém leu ainda.', itens: cad.leitores.filter((l) => l.lido_em).map(pessoa) }
    ]
  };
  grupoModal.value = cad.total_leram === cad.total_publico ? 1 : 0;
}

// Modal genérico: separa os colaboradores em "pendentes" e "em dia" para um indicador
function abrirPorColaborador(titulo, subtitulo, emDia, resumoDe, considerar = () => true) {
  const lista = dados.value.colaboradores.filter(considerar);
  const pessoa = (c) => ({ nome: c.nome, detalhe: resumoDe(c), data: null });
  modalLeitura.value = {
    titulo,
    subtitulo,
    grupos: [
      { rotulo: 'Pendentes', vazio: 'Ninguém pendente.', itens: lista.filter((c) => !emDia(c)).map(pessoa) },
      { rotulo: 'Em dia', vazio: 'Ninguém em dia ainda.', itens: lista.filter(emDia).map(pessoa) }
    ]
  };
  grupoModal.value = lista.every(emDia) ? 1 : 0;
}

function abrirHoleritesDoColaborador(c) {
  const item = (h) => ({ nome: h.descricao, detalhe: '', data: h.recebido_em, enviar: h.recebido_em ? null : aviso(c, () => avisoHolerite(c, h)) });
  modalLeitura.value = {
    titulo: c.nome,
    subtitulo: `${c.matricula} · holerites de ${ano.value} · ${c.holerites.recebidos} de ${c.holerites.total} recebidos`,
    grupos: [
      { rotulo: 'Não recebidos', vazio: 'Recebeu todos.', itens: c.holerites.detalhe.filter((h) => !h.recebido_em).map(item) },
      { rotulo: 'Recebidos', vazio: 'Nenhum recebido ainda.', itens: c.holerites.detalhe.filter((h) => h.recebido_em).map(item) }
    ]
  };
  grupoModal.value = c.holerites.recebidos === c.holerites.total ? 1 : 0;
}

function abrirPontoDoColaborador(c) {
  const comps = c.ponto?.competencias || [];
  const item = (p) => ({ nome: p.mes_nome, detalhe: '', data: p.assinado_em, enviar: p.assinado_em ? null : aviso(c, () => avisoPonto(c, p)) });
  modalLeitura.value = {
    titulo: c.nome,
    subtitulo: `${c.matricula} · espelhos de ponto`,
    grupos: [
      { rotulo: 'Falta assinar', vazio: 'Todos assinados.', itens: comps.filter((p) => !p.assinado_em).map(item) },
      { rotulo: 'Assinados', vazio: 'Nenhum assinado ainda.', itens: comps.filter((p) => p.assinado_em).map(item) }
    ]
  };
  grupoModal.value = comps.every((p) => p.assinado_em) ? 1 : 0;
}

function abrirLeiturasDoColaborador(c) {
  const doc = (d) => ({ nome: d.titulo, detalhe: `${d.categoria} · ${d.obrigatorio ? 'Obrigatório' : 'Opcional'}`, data: d.lido_em, enviar: d.lido_em ? null : aviso(c, () => avisoCaderno(c, d)) });
  const lista = c.cadernos.detalhe;
  modalLeitura.value = {
    titulo: c.nome,
    subtitulo: `${c.matricula} · ${c.cadernos.obrigatorios_lidos} de ${c.cadernos.obrigatorios_total} obrigatórios · ${c.cadernos.opcionais_lidos} de ${c.cadernos.opcionais_total} opcionais`,
    grupos: [
      { rotulo: 'Não leu', vazio: 'Leu todos os cadernos.', itens: lista.filter((d) => !d.lido_em).map(doc) },
      { rotulo: 'Leu', vazio: 'Ainda não leu nenhum caderno.', itens: lista.filter((d) => d.lido_em).map(doc) }
    ]
  };
  grupoModal.value = lista.every((d) => d.lido_em) ? 1 : 0;
}
const matriculaPonto = ref('');
const filtroCompetenciaPonto = ref('');
const filtroSituacaoPonto = ref('');
const espelhoRh = ref(null);

// Um item por espelho importado, do mês mais recente para o mais antigo
const espelhos = computed(() => (dados.value?.colaboradores || []).flatMap((c) =>
  (c.ponto?.competencias || []).map((p) => ({ nome: c.nome, matricula: c.matricula, competencia: p.competencia, mes_nome: p.mes_nome, assinado_em: p.assinado_em }))
).sort((a, b) => chaveCompetencia(b.competencia).localeCompare(chaveCompetencia(a.competencia)) || a.nome.localeCompare(b.nome)));

const chaveCompetencia = (c) => (c || '').split('/').reverse().join('');

const competenciasPonto = computed(() => {
  const vistos = new Map();
  espelhos.value.forEach((e) => vistos.set(e.competencia, e));
  return [...vistos.values()];
});

const espelhosFiltrados = computed(() => {
  return espelhos.value.filter((e) =>
    (!filtroCompetenciaPonto.value || e.competencia === filtroCompetenciaPonto.value) &&
    (!filtroSituacaoPonto.value || (filtroSituacaoPonto.value === 'assinado') === !!e.assinado_em) &&
    (!matriculaPonto.value || e.matricula === matriculaPonto.value)
  );
});

// Contagens respeitam o colaborador e o mês escolhidos
const filtrosSituacaoPonto = computed(() => {
  const base = espelhos.value.filter((e) =>
    (!filtroCompetenciaPonto.value || e.competencia === filtroCompetenciaPonto.value) &&
    (!matriculaPonto.value || e.matricula === matriculaPonto.value)
  );
  return [
    { id: '', rotulo: 'Todos', classe: '', total: base.length },
    { id: 'pendente', rotulo: 'Falta assinar', classe: 'pendente', total: base.filter((e) => !e.assinado_em).length },
    { id: 'assinado', rotulo: 'Assinados', classe: 'ok', total: base.filter((e) => e.assinado_em).length }
  ];
});

const moedaRh = (v) => Number(v || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

function abrirEspelhoRh(e) {
  // Recria o PontoView mesmo se for o mesmo espelho de antes
  espelhoRh.value = null;
  nextTick(() => { espelhoRh.value = e; });
}
const matriculaHolerite = ref('');
const anoHolerite = ref(anoAtual);
const holeritesRh = ref([]);
const carregandoHolerites = ref(false);
const holeriteAberto = ref(null);

async function carregarHolerites() {
  if (!matriculaHolerite.value) return;
  carregandoHolerites.value = true;
  try {
    const res = await fetch(`/api/rh/holerites?matricula=${encodeURIComponent(matriculaHolerite.value)}&ano=${anoHolerite.value}`, { headers: auth() });
    const data = await res.json();
    holeritesRh.value = res.ok ? data.holerites || [] : [];
  } finally {
    carregandoHolerites.value = false;
  }
}

// Ao abrir as abas de consulta, já seleciona o primeiro colaborador
watch([aba, () => dados.value.colaboradores.length], ([atual]) => {
  const primeiro = dados.value.colaboradores[0]?.matricula || '';
  if (atual === 'holerites' && !matriculaHolerite.value) {
    matriculaHolerite.value = primeiro;
    carregarHolerites();
  }
});

async function carregar() {
  carregando.value = !dados.value.colaboradores.length;
  erro.value = '';
  try {
    const res = await fetch(`/api/rh/resumo?ano=${ano.value}`, { headers: auth() });
    const data = await res.json();
    if (!res.ok) throw new Error(data.erro);
    dados.value = data;
  } catch (e) {
    erro.value = e.message || 'Não foi possível carregar o painel.';
  } finally {
    carregando.value = false;
  }
}

// Pendência por tipo; sem tipo, vale qualquer uma
const pendenciaPorTipo = {
  holerites: (c) => c.holerites.recebidos < c.holerites.total,
  ponto: (c) => !!(c.ponto && !c.ponto.assinado_em),
  cadernos: (c) => c.cadernos.obrigatorios_lidos < c.cadernos.obrigatorios_total
};
const temPendencia = (c) => Object.values(pendenciaPorTipo).some((f) => f(c));

// Filtro por tipo: mostra só a coluna do tipo escolhido
const tipoFiltro = ref('todos');
const mostraColuna = (tipo) => tipoFiltro.value === 'todos' || tipoFiltro.value === tipo;

const colaboradoresFiltrados = computed(() => {
  const termo = busca.value.trim().toLowerCase();
  return dados.value.colaboradores.filter((c) => {
    if (termo && !c.nome.toLowerCase().includes(termo) && !c.matricula.toLowerCase().includes(termo)) return false;
    const pendente = tipoFiltro.value === 'todos' ? temPendencia(c) : pendenciaPorTipo[tipoFiltro.value](c);
    if (tipoFiltro.value === 'ponto' && !c.ponto && filtro.value !== 'todos') return false;
    if (filtro.value === 'pendentes') return pendente;
    if (filtro.value === 'em_dia') return !pendente;
    return true;
  });
});

const indicadores = computed(() => {
  const cs = dados.value.colaboradores;
  const comPonto = cs.filter((c) => c.ponto);
  return [
    {
      rotulo: 'Holerites recebidos',
      feito: cs.reduce((t, c) => t + c.holerites.recebidos, 0),
      total: cs.reduce((t, c) => t + c.holerites.total, 0),
      descricao: `Demonstrativos de ${ano.value} abertos ou enviados ao WhatsApp.`,
      abrir: () => abrirPorColaborador('Holerites recebidos', `Demonstrativos de ${ano.value}`, (c) => c.holerites.recebidos === c.holerites.total, (c) => `${c.holerites.recebidos} de ${c.holerites.total} recebidos`)
    },
    {
      rotulo: 'Espelhos assinados',
      feito: comPonto.filter((c) => c.ponto.assinado_em).length,
      total: comPonto.length,
      descricao: 'Espelhos de ponto da competência atual.',
      abrir: () => abrirPorColaborador('Espelhos assinados', 'Competência mais recente de cada colaborador', (c) => !!c.ponto?.assinado_em, (c) => (c.ponto ? `${c.ponto.mes_nome} · ${c.ponto.assinado_em ? 'assinado em ' + c.ponto.assinado_em : 'falta assinar'}` : 'Sem espelho'), (c) => !!c.ponto)
    },
    {
      rotulo: 'Cadernos obrigatórios',
      feito: cs.reduce((t, c) => t + c.cadernos.obrigatorios_lidos, 0),
      total: cs.reduce((t, c) => t + c.cadernos.obrigatorios_total, 0),
      descricao: `Leituras obrigatórias confirmadas. Opcionais lidas: ${cs.reduce((t, c) => t + c.cadernos.opcionais_lidos, 0)} de ${cs.reduce((t, c) => t + c.cadernos.opcionais_total, 0)}.`,
      abrir: () => abrirPorColaborador('Cadernos obrigatórios', 'Leituras obrigatórias de cada colaborador', (c) => c.cadernos.obrigatorios_lidos === c.cadernos.obrigatorios_total, (c) => `${c.cadernos.obrigatorios_lidos} de ${c.cadernos.obrigatorios_total} lidos`)
    }
  ];
});


const campoPdf = ref(null);
const novaCategoria = ref('');

async function adicionarCategoria() {
  const nome = novaCategoria.value.trim();
  if (!nome) return;
  const res = await fetch('/api/rh/cadernos/categorias', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...auth() },
    body: JSON.stringify({ nome })
  });
  const data = await res.json();
  mensagemCadernosErro.value = !res.ok;
  mensagemCadernos.value = res.ok ? `Categoria "${nome}" criada.` : data.erro;
  if (res.ok) {
    dados.value.categorias = data.categorias;
    formCaderno.value.categoria = nome;
    novaCategoria.value = '';
  }
}

async function removerCategoria(nome) {
  if (!confirm(`Remover a categoria "${nome}"?`)) return;
  const res = await fetch('/api/rh/cadernos/categorias/remover', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...auth() },
    body: JSON.stringify({ nome })
  });
  const data = await res.json();
  mensagemCadernosErro.value = !res.ok;
  mensagemCadernos.value = res.ok ? `Categoria "${nome}" removida.` : data.erro;
  if (res.ok) dados.value.categorias = data.categorias;
}
const formVazio = () => ({ arquivo: '', titulo: '', categoria: 'Outros', obrigatorio: true, publico: 'todos', valores: [] });
const formCaderno = ref(formVazio());

const opcoesPublico = computed(() =>
  formCaderno.value.publico === 'departamentos'
    ? (dados.value.departamentos || []).map((d) => ({ valor: d, rotulo: d }))
    : dados.value.colaboradores.map((c) => ({ valor: c.matricula, rotulo: `${c.nome} (${c.matricula})` }))
);

watch(() => formCaderno.value.publico, (novo, antigo) => {
  if (antigo && novo !== antigo) formCaderno.value.valores = [];
});

function descreverPublico(cad) {
  if (cad.publico === 'departamentos') return `Departamentos: ${cad.valores.join(', ')}`;
  if (cad.publico === 'colaboradores') return `${cad.valores.length} colaborador(es)`;
  return 'Todos';
}

function editarCaderno(cad) {
  formCaderno.value = { arquivo: cad.arquivo, titulo: cad.titulo, categoria: cad.categoria, obrigatorio: cad.obrigatorio, publico: cad.publico, valores: [...cad.valores] };
  mensagemCadernos.value = '';
}

// Ao escolher o PDF, sugere o nome do arquivo como título (o RH pode trocar)
function sugerirTitulo(e) {
  const nome = e.target.files?.[0]?.name || '';
  if (!formCaderno.value.titulo.trim()) formCaderno.value.titulo = nome.replace(/\.pdf$/i, '').replace(/[_-]+/g, ' ').trim();
}

function limparFormCaderno() {
  formCaderno.value = formVazio();
  if (campoPdf.value) campoPdf.value.value = '';
}

async function salvarCaderno() {
  const f = formCaderno.value;
  if (f.publico !== 'todos' && !f.valores.length) {
    mensagemCadernosErro.value = true;
    mensagemCadernos.value = 'Selecione quem terá acesso ao caderno.';
    return;
  }
  enviando.value = true;
  mensagemCadernos.value = '';
  const config = { titulo: f.titulo.trim(), categoria: f.categoria, obrigatorio: f.obrigatorio, publico: f.publico, valores: f.valores };
  try {
    let res;
    if (f.arquivo) {
      res = await fetch('/api/rh/cadernos/config', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', ...auth() },
        body: JSON.stringify({ arquivo: f.arquivo, ...config })
      });
    } else {
      const arquivo = campoPdf.value?.files?.[0];
      if (!arquivo) throw new Error('Escolha o arquivo PDF.');
      const fd = new FormData();
      fd.append('arquivo', arquivo);
      fd.append('config', JSON.stringify(config));
      res = await fetch('/api/rh/cadernos', { method: 'POST', headers: auth(), body: fd });
    }
    const data = await res.json();
    if (!res.ok) throw new Error(data.erro);
    mensagemCadernosErro.value = false;
    mensagemCadernos.value = f.arquivo ? 'Caderno atualizado.' : 'Caderno publicado.';
    limparFormCaderno();
    carregar();
  } catch (e) {
    mensagemCadernosErro.value = true;
    mensagemCadernos.value = e.message || 'Não foi possível salvar o caderno.';
  } finally {
    enviando.value = false;
  }
}

// Abre o PDF numa aba nova (a aba é aberta já no clique para o navegador não bloquear)
async function verCaderno(cad) {
  const aba = window.open('', '_blank');
  try {
    const res = await fetch(`/api/rh/cadernos/arquivo?nome=${encodeURIComponent(cad.arquivo)}`, { headers: auth() });
    if (!res.ok) throw new Error();
    const url = URL.createObjectURL(await res.blob());
    if (aba) aba.location.href = url;
    else window.location.href = url;
  } catch (e) {
    aba?.close();
    mensagemCadernosErro.value = true;
    mensagemCadernos.value = 'Não foi possível abrir o caderno.';
  }
}

async function removerCaderno(cad) {
  if (!confirm(`Remover "${cad.titulo}"? Os colaboradores deixarão de ver este caderno.`)) return;
  const res = await fetch('/api/rh/cadernos/remover', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...auth() },
    body: JSON.stringify({ arquivo: cad.arquivo })
  });
  const data = await res.json().catch(() => ({}));
  mensagemCadernosErro.value = !res.ok;
  mensagemCadernos.value = res.ok ? `"${cad.titulo}" removido.` : (data.erro || 'Não foi possível remover o caderno.');
  carregar();
}

async function importarPonto() {
  const arquivo = campoCsv.value?.files?.[0];
  if (!arquivo) return;
  importando.value = true;
  mensagemPonto.value = '';
  const fd = new FormData();
  fd.append('arquivo', arquivo);
  try {
    const res = await fetch('/api/rh/ponto', { method: 'POST', headers: auth(), body: fd });
    const data = await res.json();
    const ok = data.importados || [];
    const semLink = ok.filter((i) => !i.com_link).length;
    mensagemPontoErro.value = !!(data.erro || (data.erros && data.erros.length));
    mensagemPonto.value = data.erro
      || [
        ok.length ? `${ok.length} espelho(s) importado(s).` : '',
        semLink ? `${semLink} sem link do Autentique (a assinatura fica bloqueada até informar).` : '',
        (data.erros || []).length ? `Erros: ${data.erros.join(' · ')}` : ''
      ].filter(Boolean).join(' ');
    if (ok.length) {
      campoCsv.value.value = '';
      carregar();
    }
  } catch (e) {
    mensagemPontoErro.value = true;
    mensagemPonto.value = 'Falha ao importar a planilha.';
  } finally {
    importando.value = false;
  }
}

function baixarModelo() {
  const linhas = [
    'matricula;data;entrada1;saida1;entrada2;saida2;ocorrencia;link_assinatura;saldo_banco',
    'VB-1040;01/11/2026;;;;;DSR;https://painel.autentique.com.br/documentos/COLE-O-LINK-AQUI;+14h 30m',
    'VB-1040;02/11/2026;;;;;Feriado (Finados);;',
    'VB-1040;03/11/2026;07:00;12:00;13:00;17:00;;;',
    'VB-2085;01/11/2026;;;;;DSR;https://painel.autentique.com.br/documentos/OUTRO-LINK;',
    'VB-2085;03/11/2026;08:00;12:00;13:00;17:00;;;'
  ];
  const blob = new Blob(['\ufeff' + linhas.join('\r\n')], { type: 'text/csv;charset=utf-8' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'modelo-espelhos-de-ponto.csv';
  a.click();
  setTimeout(() => URL.revokeObjectURL(a.href), 1000);
}

onMounted(carregar);
</script>

<style scoped>
.linha-lista {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 20px;
  text-align: left;
  cursor: pointer;
  transition: background 0.15s;
}
.linha-lista:hover {
  background: #f8fafc;
}
.bloco-mes {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: #f1f5f9;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  line-height: 1;
}
.bloco-mes .mes {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  color: #9c2337;
}
.bloco-mes .ano {
  font-size: 10px;
  font-weight: 600;
  color: #0f172a;
  margin-top: 3px;
}
.chip-resumo {
  display: inline-flex;
  align-items: center;
  height: 30px;
  padding: 0 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  background: #f8fafc;
  color: #475569;
  border: 1px solid #e2e8f0;
}
.chip-resumo.ok {
  background: #ecfdf5;
  color: #065f46;
  border-color: #a7f3d0;
}
.chip-resumo.pendente {
  background: #fffbeb;
  color: #92400e;
  border-color: #fde68a;
}
.chip-resumo.ativo {
  outline: 2px solid #141414;
  outline-offset: 1px;
}
.badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 10px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 600;
  border: 1px solid;
  white-space: nowrap;
}
.badge.ok {
  background: #ecfdf5;
  color: #065f46;
  border-color: #a7f3d0;
}
.badge.neutro {
  background: #f8fafc;
  color: #475569;
  border-color: #e2e8f0;
}
.badge.pendente {
  background: #fffbeb;
  color: #92400e;
  border-color: #fde68a;
}
</style>
