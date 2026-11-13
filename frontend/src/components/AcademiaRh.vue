<template>
  <div class="space-y-5">
    <!-- Cabeçalho -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 space-y-4">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800">Trilhas de Treinamentos</h3>
          <p class="text-xs text-slate-500 mt-0.5">Monte trilhas de aprendizagem com vídeos e cadernos. Ao concluir, o colaborador emite o certificado.</p>
        </div>
        <button v-if="pode(colaborador, 'rh_cadernos', 'publicar')" type="button" @click="abrirWizard(null)" class="h-10 px-5 rounded-full bg-[#9c2337] hover:bg-[#831b2d] text-white text-sm font-semibold cursor-pointer shrink-0">+ Nova trilha</button>
      </div>

      <div class="flex flex-wrap gap-2">
        <button v-for="f in filtros" :key="f.id" type="button" @click="filtro = f.id" class="h-8 px-3.5 rounded-full border text-xs font-semibold transition cursor-pointer"
          :class="filtro === f.id ? 'bg-[#141414] border-[#141414] text-white' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300'">
          {{ f.rotulo }} <span class="opacity-60">{{ contagem(f.id) }}</span>
        </button>
      </div>
      <p v-if="mensagem" class="text-xs font-medium" :class="mensagemErro ? 'text-rose-700' : 'text-emerald-700'">{{ mensagem }}</p>
    </div>

    <!-- Cards de trilhas -->
    <div v-if="carregando" class="py-12 text-center text-sm text-slate-400">Carregando trilhas...</div>
    <div v-else-if="!lista.length" class="bg-white rounded-2xl border border-dashed border-slate-300 py-14 px-6 text-center space-y-3">
      <AppIcon name="graduation-cap" size="36" class="mx-auto text-slate-300" />
      <p class="text-base font-semibold text-slate-800">Vamos criar a primeira trilha?</p>
      <p class="text-sm text-slate-500 max-w-md mx-auto">Reúna vídeos e cadernos numa sequência de aprendizagem, escolha quem deve fazer e publique. É rápido: são só 4 passos.</p>
      <button v-if="pode(colaborador, 'rh_cadernos', 'publicar')" type="button" @click="abrirWizard(null)" class="h-11 px-6 rounded-full bg-[#9c2337] hover:bg-[#831b2d] text-white text-sm font-semibold cursor-pointer">+ Criar primeira trilha</button>
    </div>
    <div v-else-if="!filtradas.length" class="bg-white rounded-2xl border border-slate-200 py-10 text-center text-sm text-slate-400">Nenhuma trilha neste filtro.</div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-3">
      <article v-for="t in filtradas" :key="t.id" class="bg-white rounded-2xl border overflow-hidden flex flex-col" :class="t.publicada === false ? 'border-dashed border-slate-300' : 'border-slate-200'">
        <div class="h-20 bg-gradient-to-br from-[#141414] to-[#9c2337] relative">
          <img v-if="urlCapaTrilha(t)" :src="urlCapaTrilha(t)" alt="" class="absolute inset-0 w-full h-full object-cover" />
        </div>
        <div class="p-4 flex flex-col gap-2.5 flex-1">
        <div class="flex flex-wrap items-center gap-1.5">
          <span v-if="t.obrigatoria" class="text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-[#fdf2f4] text-[#9c2337] border border-rose-200">Obrigatória</span>
          <span v-else class="text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200" :title="t.funcoes.join(', ')">Por função · {{ t.funcoes.length }}</span>
          <span v-if="t.publicada === false" class="text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200">Rascunho</span>
          <span class="text-[11px] text-slate-400 ml-auto">{{ t.empresa === 'todas' || !t.empresa ? 'Todas as empresas' : t.empresa }}</span>
        </div>
        <div class="min-w-0">
          <h4 class="text-base font-semibold text-slate-900 leading-snug">{{ t.titulo }}</h4>
          <p v-if="t.descricao" class="text-sm text-slate-500 mt-0.5 line-clamp-2">{{ t.descricao }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-600">
          <span class="inline-flex items-center gap-1.5"><IconePlay class="w-3.5 h-3.5 text-slate-400" /> {{ qtd(t, 'video') }} {{ qtd(t, 'video') === 1 ? 'vídeo' : 'vídeos' }}</span>
          <span class="inline-flex items-center gap-1.5"><AppIcon name="document" size="14" class="text-slate-400" /> {{ qtd(t, 'caderno') }} {{ qtd(t, 'caderno') === 1 ? 'caderno' : 'cadernos' }}</span>
          <span v-if="t.carga_horaria" class="inline-flex items-center gap-1.5"><AppIcon name="calendar" size="14" class="text-slate-400" /> {{ t.carga_horaria }}</span>
          <span v-if="t.prazo" class="inline-flex items-center gap-1.5 font-semibold text-[#9c2337]"><AppIcon name="calendar" size="14" /> Prazo {{ dataBr(t.prazo) }}</span>
          <span class="inline-flex items-center gap-1.5"><AppIcon name="shield-check" size="14" class="text-slate-400" /> {{ t.certificados_emitidos }} {{ t.certificados_emitidos === 1 ? 'certificado' : 'certificados' }}</span>
          <span class="text-slate-400">≈ {{ atingidos(t) }} colab.</span>
        </div>
        <div class="flex flex-wrap items-center gap-1.5 pt-2 mt-auto border-t border-slate-100">
          <template v-if="pode(colaborador, 'rh_cadernos', 'editar')">
            <button type="button" @click="abrirWizard(t)" class="h-8 px-3.5 rounded-full bg-[#141414] hover:bg-black text-white text-xs font-semibold cursor-pointer">Editar</button>
            <button type="button" @click="abrirWizard(t, 2)" class="h-8 px-3.5 rounded-full border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs font-semibold cursor-pointer">Gerenciar conteúdo</button>
          </template>
          <button v-if="pode(colaborador, 'rh_cadernos', 'publicar')" type="button" @click="duplicar(t)" class="h-8 px-3 rounded-full text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer">Duplicar</button>
          <button v-if="pode(colaborador, 'rh_cadernos', 'editar')" type="button" @click="alternarPublicacao(t)" class="h-8 px-3 rounded-full text-xs font-semibold hover:bg-slate-100 cursor-pointer" :class="t.publicada === false ? 'text-emerald-700' : 'text-slate-600'">{{ t.publicada === false ? 'Publicar' : 'Despublicar' }}</button>
          <button v-if="pode(colaborador, 'rh_cadernos', 'remover')" type="button" @click="remover(t)" class="h-8 px-3 rounded-full text-xs font-semibold text-slate-500 hover:bg-rose-50 hover:text-rose-700 cursor-pointer ml-auto">Remover</button>
        </div>
        </div>
      </article>
    </div>

    <!-- WIZARD: nova trilha / editar trilha -->
    <WizardCadastro
      :aberto="wizardAberto"
      :titulo="form.id ? 'Editar trilha' : 'Nova trilha'"
      :descricao="form.id ? 'Todos os passos já estão preenchidos: ajuste o que precisar.' : 'Em 4 passos a trilha fica pronta para os colaboradores.'"
      :passos="passos"
      v-model:passo="passo"
      :validar="validar"
      :sujo="sujo"
      :enviando="enviando"
      :erro="erroWizard"
      :pode-rascunho="true"
      :rotulo-concluir="escolha === 'publicar' ? (form.id && form.publicada ? 'Salvar alterações' : 'Publicar trilha') : 'Salvar rascunho'"
      @fechar="wizardAberto = false"
      @concluir="salvar(escolha === 'publicar')"
      @rascunho="salvar(false)"
    >
      <!-- 1. Básico -->
      <template #passo-basico>
        <div class="space-y-4 max-w-xl">
          <label class="block text-xs font-medium text-slate-700 space-y-1.5">
            <span>Nome da trilha</span>
            <input v-model="form.titulo" type="text" maxlength="120" placeholder="Ex.: Integração de segurança" class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
          </label>
          <div class="space-y-1.5">
            <span class="block text-xs font-medium text-slate-700">Capa <span class="text-slate-400 font-normal">(opcional · JPG, PNG ou WEBP até 3 MB)</span></span>
            <div class="flex items-center gap-3">
              <div class="w-32 h-20 rounded-xl overflow-hidden bg-gradient-to-br from-[#141414] to-[#9c2337] shrink-0">
                <img v-if="previaCapa" :src="previaCapa" alt="Prévia da capa" class="w-full h-full object-cover" />
              </div>
              <div class="space-y-1.5">
                <input ref="campoCapa" type="file" accept="image/jpeg,image/png,image/webp" @change="escolherCapa" class="block text-xs text-slate-600 file:mr-3 file:h-9 file:px-4 file:rounded-full file:border-0 file:bg-slate-100 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200 file:cursor-pointer" />
                <button v-if="previaCapa" type="button" @click="tirarCapa" class="text-[11px] font-semibold text-slate-500 hover:text-slate-800 cursor-pointer">Remover capa</button>
              </div>
            </div>
          </div>
          <label class="block text-xs font-medium text-slate-700 space-y-1.5">
            <span>Descrição <span class="text-slate-400 font-normal">(o que o colaborador vai aprender)</span></span>
            <textarea v-model="form.descricao" rows="3" maxlength="600" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400"></textarea>
            <span class="block text-right text-[11px] text-slate-400 font-normal">{{ form.descricao.length }}/600</span>
          </label>
          <label class="block text-xs font-medium text-slate-700 space-y-1.5 sm:max-w-[50%]">
            <span>Prazo para concluir <span class="text-slate-400 font-normal">(opcional)</span></span>
            <input v-model="form.prazo" type="date" class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
            <span v-if="form.prazo" class="flex items-center gap-2 text-[11px] text-slate-400 font-normal">O colaborador vê o aviso de prazo até {{ dataBr(form.prazo) }}. <button type="button" @click="form.prazo = ''" class="text-[#9c2337] font-semibold hover:underline cursor-pointer">Remover prazo</button></span>
            <span v-else class="block text-[11px] text-slate-400 font-normal">Sem prazo, a trilha fica disponível sem data limite.</span>
          </label>
          <label class="block text-xs font-medium text-slate-700 space-y-1.5 sm:max-w-[50%]">
            <span>Pontos ao concluir a trilha <span class="text-slate-400 font-normal">(opcional)</span></span>
            <input v-model="form.pontos" type="number" min="0" max="1000" :placeholder="'Padrão: ' + PONTOS_TRILHA" class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
            <span class="block text-[11px] text-slate-400 font-normal">Conta no nível do colaborador. Em branco, todas as trilhas valem o mesmo ({{ PONTOS_TRILHA }}).</span>
          </label>
          <div class="grid sm:grid-cols-2 gap-3">
            <label class="block text-xs font-medium text-slate-700 space-y-1.5">
              <span>Carga horária <span class="text-slate-400 font-normal">(sai no certificado)</span></span>
              <input v-model="form.carga_horaria" type="text" maxlength="20" placeholder="Ex.: 4 horas" class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
              <button v-if="duracaoVideos && form.carga_horaria !== duracaoVideos" type="button" @click="form.carga_horaria = duracaoVideos" class="text-[11px] text-[#9c2337] font-semibold hover:underline cursor-pointer">Usar a duração dos vídeos: {{ duracaoVideos }}</button>
            </label>
            <label class="block text-xs font-medium text-slate-700 space-y-1.5">
              <span>Empresa</span>
              <select v-model="form.empresa" class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none">
                <option value="todas">Todas as empresas</option>
                <option v-for="e in empresas" :key="e" :value="e">{{ e }}</option>
              </select>
            </label>
          </div>
        </div>
      </template>

      <!-- 2. Quem faz -->
      <template #passo-quem>
        <div class="space-y-5 max-w-2xl">
          <div class="grid sm:grid-cols-2 gap-3">
            <button type="button" @click="form.obrigatoria = true" class="text-left rounded-2xl border-2 p-4 space-y-1 cursor-pointer transition" :class="form.obrigatoria ? 'border-[#9c2337] bg-[#fdf2f4]' : 'border-slate-200 hover:border-slate-300'">
              <span class="block text-sm font-semibold" :class="form.obrigatoria ? 'text-[#9c2337]' : 'text-slate-800'">Todos (obrigatória)</span>
              <span class="block text-xs text-slate-500">Vale para todos os colaboradores{{ form.empresa === 'todas' ? '' : ' da ' + form.empresa }}. Aparece em destaque no portal.</span>
            </button>
            <button type="button" @click="form.obrigatoria = false" class="text-left rounded-2xl border-2 p-4 space-y-1 cursor-pointer transition" :class="!form.obrigatoria ? 'border-slate-800 bg-slate-50' : 'border-slate-200 hover:border-slate-300'">
              <span class="block text-sm font-semibold text-slate-800">Por função</span>
              <span class="block text-xs text-slate-500">Só para os cargos que você escolher (ex.: Operador de Rocadeira).</span>
            </button>
          </div>

          <div v-if="!form.obrigatoria" class="space-y-3">
            <div class="relative">
              <label class="block text-xs font-medium text-slate-700 mb-1.5">Funções</label>
              <div class="flex flex-wrap items-center gap-1.5 min-h-[44px] px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 focus-within:bg-white focus-within:border-slate-400">
                <span v-for="f in form.funcoes" :key="f" class="inline-flex items-center gap-1.5 h-7 pl-3 pr-1.5 rounded-full bg-[#141414] text-white text-xs font-semibold">
                  {{ f }} <span class="opacity-60 font-normal">{{ colabsDoCargo(f) }}</span>
                  <button type="button" @click="removerFuncao(f)" class="w-4 h-4 rounded-full hover:bg-white/20 flex items-center justify-center cursor-pointer" :aria-label="'Remover ' + f"><AppIcon name="x" size="10" /></button>
                </span>
                <input v-model="buscaCargo" @keydown.enter.prevent="adicionarFuncao(buscaCargo)" @keydown.backspace="!buscaCargo && form.funcoes.pop()" @focus="cargoFoco = true" @blur="fecharSugestoes" type="text" placeholder="Digite um cargo e tecle Enter…" class="flex-1 min-w-[160px] h-8 bg-transparent text-sm outline-none" />
              </div>
              <ul v-if="cargoFoco && sugestoes.length" class="absolute z-10 left-0 right-0 mt-1 max-h-56 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg py-1">
                <li v-for="c in sugestoes" :key="c.cargo">
                  <button type="button" @mousedown.prevent="adicionarFuncao(c.cargo)" class="w-full flex items-center justify-between gap-3 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer text-left">
                    <span class="truncate">{{ c.cargo }}</span><span class="text-xs text-slate-400 shrink-0">{{ c.qtd }} colab.</span>
                  </button>
                </li>
              </ul>
            </div>
            <div v-if="cargosDisponiveis.length" class="space-y-1.5">
              <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Cargos cadastrados</p>
              <div class="flex flex-wrap gap-1.5">
                <button v-for="c in cargosDisponiveis.slice(0, 14)" :key="c.cargo" type="button" @click="adicionarFuncao(c.cargo)" class="h-7 px-3 rounded-full border border-slate-200 text-xs text-slate-600 hover:border-slate-400 hover:bg-slate-50 cursor-pointer">+ {{ c.cargo }} <span class="text-slate-400">{{ c.qtd }}</span></button>
              </div>
            </div>
            <p v-if="funcoesSemColaborador.length" class="text-xs text-amber-700">Atenção: nenhum colaborador tem o cargo {{ funcoesSemColaborador.join(', ') }}. Confira se o nome é igual ao do cadastro.</p>
          </div>

          <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-700">
            <strong class="text-slate-900">{{ atingidos(form) }}</strong> {{ atingidos(form) === 1 ? 'colaborador será atingido' : 'colaboradores serão atingidos' }} por esta trilha.
          </div>
        </div>
      </template>

      <!-- 3. Conteúdo: construtor -->
      <template #passo-conteudo>
        <div class="space-y-4">
          <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="adicionarVideo" class="h-10 px-4 rounded-full bg-[#9c2337] hover:bg-[#831b2d] text-white text-sm font-semibold cursor-pointer inline-flex items-center gap-2"><IconePlay class="w-3.5 h-3.5" /> Vídeo</button>
            <button type="button" @click="abrirExistente" class="h-10 px-4 rounded-full bg-[#141414] hover:bg-black text-white text-sm font-semibold cursor-pointer inline-flex items-center gap-2"><AppIcon name="document" size="14" /> Caderno existente</button>
            <button v-if="pode(colaborador, 'rh_cadernos', 'publicar')" type="button" @click="abrirNovoCaderno" class="h-10 px-4 rounded-full border border-slate-300 text-slate-800 hover:bg-slate-50 text-sm font-semibold cursor-pointer inline-flex items-center gap-2"><AppIcon name="download" size="14" class="rotate-180" /> Novo caderno (PDF)</button>
            <span class="ml-auto text-xs text-slate-500">{{ resumoConteudo }}</span>
          </div>

          <div v-if="!form.itens.length" class="rounded-2xl border-2 border-dashed border-slate-300 py-12 px-6 text-center space-y-1.5">
            <p class="text-sm font-semibold text-slate-700">A trilha ainda não tem etapas</p>
            <p class="text-xs text-slate-500">Adicione vídeos e cadernos acima. Você pode reordenar arrastando.</p>
          </div>

          <ol class="space-y-2.5">
            <li v-for="(i, n) in form.itens" :key="i.id" :id="'etapa-' + i.id"
              class="rounded-2xl border bg-white transition" :class="[overIdx === n && dragIdx !== n ? 'border-[#9c2337] ring-2 ring-[#9c2337]/20' : 'border-slate-200', dragIdx === n ? 'opacity-40' : '']"
              @dragover.prevent="overIdx = n" @dragleave="overIdx === n && (overIdx = -1)" @drop.prevent="soltar(n)">
              <div class="flex items-center gap-2.5 p-2.5">
                <span draggable="true" @dragstart="comecarArrasto($event, n)" @dragend="dragIdx = overIdx = -1" class="w-6 h-9 flex items-center justify-center text-slate-300 hover:text-slate-500 cursor-grab active:cursor-grabbing shrink-0 select-none" title="Arraste para reordenar" aria-label="Arrastar">
                  <svg width="12" height="18" viewBox="0 0 12 18" fill="currentColor"><circle cx="3" cy="3" r="1.6"/><circle cx="9" cy="3" r="1.6"/><circle cx="3" cy="9" r="1.6"/><circle cx="9" cy="9" r="1.6"/><circle cx="3" cy="15" r="1.6"/><circle cx="9" cy="15" r="1.6"/></svg>
                </span>
                <span class="w-6 text-xs font-bold text-slate-400 text-center shrink-0">{{ n + 1 }}</span>
                <button type="button" @click="alternar(i.id)" class="flex items-center gap-3 min-w-0 flex-1 text-left cursor-pointer">
                  <span class="w-14 h-10 rounded-lg bg-slate-100 overflow-hidden flex items-center justify-center shrink-0 text-slate-400">
                    <img v-if="miniatura(i)" :src="miniatura(i)" alt="" class="w-full h-full object-cover" />
                    <IconePlay v-else-if="i.tipo === 'video'" class="w-4 h-4" />
                    <AppIcon v-else name="document" size="18" />
                  </span>
                  <span class="min-w-0">
                    <span class="block text-sm font-semibold text-slate-900 truncate">{{ tituloDe(i) || 'Etapa sem título' }}</span>
                    <span class="block text-[11px] text-slate-400 truncate">
                      {{ i.tipo === 'video' ? 'Vídeo' + (i.duracao ? ' · ' + i.duracao : '') : 'Caderno' + (cad(i)?.paginas ? ' · ' + cad(i).paginas + ' páginas' : '') + (cad(i) ? ' · ' + rotuloTipo(cad(i).tipo) : '') }}
                    </span>
                  </span>
                </button>
                <span v-if="i.pontos !== '' && i.pontos != null" class="hidden sm:inline text-[11px] font-semibold text-slate-500 bg-slate-100 rounded-full px-2 py-0.5 shrink-0">{{ i.pontos }} pts</span>
                <span v-if="problemaItem(i)" class="hidden sm:inline text-[11px] font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-full px-2 py-0.5 shrink-0">Incompleta</span>
                <span class="flex items-center gap-0.5 shrink-0">
                  <button type="button" @click="mover(n, -1)" :disabled="n === 0" class="w-8 h-8 rounded-lg text-slate-500 hover:bg-slate-100 disabled:opacity-25 cursor-pointer flex items-center justify-center" aria-label="Subir"><AppIcon name="chevron-up" size="16" /></button>
                  <button type="button" @click="mover(n, 1)" :disabled="n === form.itens.length - 1" class="w-8 h-8 rounded-lg text-slate-500 hover:bg-slate-100 disabled:opacity-25 cursor-pointer flex items-center justify-center" aria-label="Descer"><AppIcon name="chevron-down" size="16" /></button>
                  <button type="button" @click="duplicarItem(n)" class="w-8 h-8 rounded-lg text-slate-500 hover:bg-slate-100 cursor-pointer flex items-center justify-center" aria-label="Duplicar etapa" title="Duplicar"><AppIcon name="copy" size="15" /></button>
                  <button type="button" @click="removerItem(n)" class="w-8 h-8 rounded-lg text-slate-500 hover:bg-rose-50 hover:text-rose-700 cursor-pointer flex items-center justify-center" aria-label="Remover etapa" title="Remover"><AppIcon name="x" size="15" /></button>
                </span>
              </div>

              <!-- Edição inline -->
              <div v-if="abertos[i.id]" class="px-4 pb-4 pt-1 border-t border-slate-100 space-y-3">
                <div class="grid sm:grid-cols-2 gap-3 pt-3">
                  <label class="block text-xs font-medium text-slate-700 space-y-1">
                    <span>Título da etapa</span>
                    <input v-model="i.titulo" type="text" maxlength="120" :placeholder="i.tipo === 'caderno' && cad(i) ? cad(i).titulo : 'Ex.: Uso correto dos EPIs'" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
                  </label>
                  <label v-if="i.tipo === 'video'" class="block text-xs font-medium text-slate-700 space-y-1">
                    <span>Duração</span>
                    <input v-model="i.duracao" type="text" maxlength="20" placeholder="Ex.: 12 min" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
                  </label>
                </div>
                <label class="block text-xs font-medium text-slate-700 space-y-1 sm:max-w-[50%]">
                  <span>Pontos desta etapa <span class="text-slate-400 font-normal">(opcional)</span></span>
                  <input v-model="i.pontos" type="number" min="0" max="1000" :placeholder="'Padrão: ' + PONTOS_ETAPA" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
                </label>
                <label class="block text-xs font-medium text-slate-700 space-y-1">
                  <span>Descrição curta <span class="text-slate-400 font-normal">(aparece para o colaborador)</span></span>
                  <textarea v-model="i.descricao" rows="2" maxlength="300" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400"></textarea>
                </label>

                <template v-if="i.tipo === 'video'">
                  <label class="block text-xs font-medium text-slate-700 space-y-1">
                    <span>Link do vídeo</span>
                    <input v-model="i.url" type="url" placeholder="https://… (YouTube, Vimeo ou arquivo .mp4)" class="w-full h-10 px-3 rounded-lg border bg-slate-50 text-sm outline-none focus:bg-white" :class="i.url && !urlValida(i.url) ? 'border-rose-300' : 'border-slate-200 focus:border-slate-400'" />
                    <span v-if="i.url && !urlValida(i.url)" class="block text-[11px] text-rose-600">O link precisa começar com https://</span>
                  </label>
                  <div v-if="urlValida(i.url) && embedDe(i.url)" class="rounded-xl overflow-hidden bg-black aspect-video max-w-md">
                    <iframe v-if="embedDe(i.url).tipo === 'iframe'" :src="embedDe(i.url).src" class="w-full h-full" allowfullscreen title="Prévia do vídeo"></iframe>
                    <video v-else :src="embedDe(i.url).src" class="w-full h-full" controls preload="metadata" @loadedmetadata="duracaoDoArquivo($event, i)"></video>
                  </div>
                  <p v-else-if="urlValida(i.url)" class="text-[11px] text-slate-400">Prévia indisponível para este link; o colaborador o verá como página embutida.</p>
                </template>

                <div v-else-if="cad(i)" class="flex gap-4 rounded-xl bg-slate-50 border border-slate-200 p-3">
                  <div class="w-20 h-28 rounded-lg bg-white border border-slate-200 overflow-hidden flex items-center justify-center text-slate-300 shrink-0">
                    <img v-if="urlCapa(cad(i))" :src="urlCapa(cad(i))" alt="" class="w-full h-full object-cover" />
                    <AppIcon v-else name="document" size="26" />
                  </div>
                  <div class="min-w-0 space-y-2 text-xs text-slate-600">
                    <p class="font-semibold text-slate-800 text-sm">{{ cad(i).titulo }}</p>
                    <p>{{ cad(i).paginas ? cad(i).paginas + ' páginas' : 'Páginas não informadas' }} · {{ cad(i).categoria }}</p>
                    <label class="inline-flex items-center gap-2">Tipo do caderno
                      <select :value="cad(i).tipo" @change="mudarTipoCaderno(cad(i), $event.target.value)" :disabled="!pode(colaborador, 'rh_cadernos', 'editar')" class="h-8 px-2 rounded-lg border border-slate-200 bg-white text-xs outline-none">
                        <option value="politica">Diretriz</option>
                        <option value="treinamento">Academia</option>
                      </select>
                    </label>
                    <p class="text-slate-400">Capa e demais dados do caderno ficam na aba Cadernos.</p>
                  </div>
                </div>
                <div v-else class="text-xs text-rose-700">Caderno não encontrado (pode ter sido removido). Remova esta etapa.</div>
              </div>
            </li>
          </ol>
        </div>
      </template>

      <!-- 4. Revisar -->
      <template #passo-revisar>
        <div class="space-y-5 max-w-2xl">
          <dl class="rounded-2xl border border-slate-200 divide-y divide-slate-100 text-sm">
            <div class="flex gap-4 px-4 py-3"><dt class="w-32 text-slate-400 shrink-0">Trilha</dt><dd class="font-semibold text-slate-900">{{ form.titulo }}<span v-if="form.descricao" class="block font-normal text-slate-500 text-xs mt-0.5">{{ form.descricao }}</span></dd></div>
            <div class="flex gap-4 px-4 py-3"><dt class="w-32 text-slate-400 shrink-0">Quem faz</dt><dd class="text-slate-800">{{ form.obrigatoria ? 'Todos (obrigatória)' : form.funcoes.join(', ') }} <span class="text-slate-400">· {{ atingidos(form) }} colab.</span></dd></div>
            <div class="flex gap-4 px-4 py-3"><dt class="w-32 text-slate-400 shrink-0">Empresa</dt><dd class="text-slate-800">{{ form.empresa === 'todas' ? 'Todas as empresas' : form.empresa }}</dd></div>
            <div class="flex gap-4 px-4 py-3"><dt class="w-32 text-slate-400 shrink-0">Prazo</dt><dd class="text-slate-800">{{ form.prazo ? dataBr(form.prazo) : 'Sem prazo' }}</dd></div>
            <div class="flex gap-4 px-4 py-3"><dt class="w-32 text-slate-400 shrink-0">Carga horária</dt><dd class="text-slate-800">{{ form.carga_horaria || 'Não informada' }}</dd></div>
            <div class="flex gap-4 px-4 py-3"><dt class="w-32 text-slate-400 shrink-0">Conteúdo</dt>
              <dd class="text-slate-800 min-w-0">{{ resumoConteudo }}
                <ol class="mt-2 space-y-1 text-xs text-slate-600 list-decimal list-inside">
                  <li v-for="i in form.itens" :key="i.id" class="truncate">{{ tituloDe(i) }} <span class="text-slate-400">({{ i.tipo === 'video' ? 'vídeo' : 'caderno' }})</span></li>
                </ol>
              </dd>
            </div>
          </dl>

          <div class="space-y-2">
            <p class="text-xs font-medium text-slate-700">O que fazer agora?</p>
            <div class="grid sm:grid-cols-2 gap-3">
              <button type="button" @click="escolha = 'publicar'" class="text-left rounded-2xl border-2 p-4 space-y-1 cursor-pointer transition" :class="escolha === 'publicar' ? 'border-[#9c2337] bg-[#fdf2f4]' : 'border-slate-200 hover:border-slate-300'">
                <span class="block text-sm font-semibold" :class="escolha === 'publicar' ? 'text-[#9c2337]' : 'text-slate-800'">Publicar agora</span>
                <span class="block text-xs text-slate-500">A trilha já aparece para os colaboradores atingidos.</span>
              </button>
              <button type="button" @click="escolha = 'rascunho'" class="text-left rounded-2xl border-2 p-4 space-y-1 cursor-pointer transition" :class="escolha === 'rascunho' ? 'border-slate-800 bg-slate-50' : 'border-slate-200 hover:border-slate-300'">
                <span class="block text-sm font-semibold text-slate-800">Salvar rascunho</span>
                <span class="block text-xs text-slate-500">Só o RH vê. Publique depois, na lista de trilhas.</span>
              </button>
            </div>
          </div>
        </div>
      </template>

      <!-- Prévia do card do colaborador -->
      <template #previa>
        <article class="rounded-2xl border border-slate-200 bg-white p-4 space-y-3">
          <div v-if="previaCapa" class="-mx-4 -mt-4 h-24 overflow-hidden rounded-t-2xl"><img :src="previaCapa" alt="" class="w-full h-full object-cover" /></div>
          <div class="flex flex-wrap items-center gap-2">
            <span v-if="form.obrigatoria" class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-[#fdf2f4] text-[#9c2337] border border-rose-200">Obrigatória</span>
            <span v-if="form.carga_horaria" class="text-[11px] text-slate-400">{{ form.carga_horaria }}</span>
          </div>
          <h4 class="text-base font-semibold text-slate-900 leading-snug">{{ form.titulo || 'Nome da trilha' }}</h4>
          <p class="text-xs text-slate-500">{{ form.descricao || 'A descrição aparece aqui.' }}</p>
          <div class="space-y-1">
            <div class="flex justify-between text-[11px] text-slate-500"><span>0 de {{ form.itens.length }} {{ form.itens.length === 1 ? 'etapa' : 'etapas' }}</span><span class="font-semibold">0%</span></div>
            <div class="h-1.5 rounded-full bg-slate-200"></div>
          </div>
          <ol class="divide-y divide-slate-100 border-t border-slate-100">
            <li v-for="(i, n) in form.itens.slice(0, 6)" :key="i.id" class="py-2 flex items-start gap-2.5">
              <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-500 text-[11px] font-bold flex items-center justify-center shrink-0">{{ n + 1 }}</span>
              <span class="min-w-0">
                <span class="block text-xs font-medium text-slate-800 truncate">{{ tituloDe(i) || 'Etapa sem título' }}</span>
                <span class="block text-[11px] text-slate-400">{{ i.tipo === 'video' ? 'Vídeo' + (i.duracao ? ' · ' + i.duracao : '') : 'Caderno' }}</span>
                <span v-if="i.descricao" class="block text-[11px] text-slate-500 leading-snug">{{ i.descricao }}</span>
              </span>
            </li>
            <li v-if="form.itens.length > 6" class="py-2 text-[11px] text-slate-400">+ {{ form.itens.length - 6 }} etapas</li>
          </ol>
          <div class="h-9 rounded-xl bg-slate-100 text-slate-400 text-[11px] font-semibold flex items-center justify-center">Certificado ao concluir</div>
        </article>
        <div class="rounded-xl bg-white border border-slate-200 p-3 text-xs text-slate-600 space-y-1">
          <p><strong class="text-slate-800">{{ form.itens.length }}</strong> {{ form.itens.length === 1 ? 'etapa' : 'etapas' }} · <strong class="text-slate-800">{{ contar('video') }}</strong> vídeos · <strong class="text-slate-800">{{ contar('caderno') }}</strong> cadernos</p>
          <p v-if="duracaoVideos">Duração dos vídeos: {{ duracaoVideos }}</p>
        </div>
      </template>
    </WizardCadastro>

    <!-- Modal: caderno existente -->
    <ModalCadastro :aberto="existenteAberto" titulo="Adicionar caderno existente" descricao="Escolha um ou mais cadernos já enviados." tamanho="lg" :rotulo-salvar="selecionados.length ? `Adicionar ${selecionados.length} à trilha` : 'Adicionar à trilha'" :pode-salvar="selecionados.length > 0" @fechar="existenteAberto = false" @salvar="adicionarSelecionados">
      <div class="flex flex-wrap gap-2">
        <input v-model="buscaCaderno" type="search" placeholder="Buscar por título ou tag…" class="flex-1 min-w-[200px] h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
        <select v-model="tipoCaderno" class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none">
          <option value="">Todos os tipos</option>
          <option value="politica">Diretriz</option>
          <option value="treinamento">Academia</option>
        </select>
      </div>
      <ul class="grid sm:grid-cols-2 gap-2.5">
        <li v-for="c in cadernosFiltrados" :key="c.arquivo">
          <label class="flex items-center gap-3 rounded-xl border p-2.5 cursor-pointer transition" :class="[jaNaTrilha(c) ? 'opacity-50 cursor-not-allowed border-slate-200' : selecionados.includes(c.arquivo) ? 'border-[#9c2337] bg-[#fdf2f4]' : 'border-slate-200 hover:border-slate-300']">
            <input type="checkbox" :value="c.arquivo" v-model="selecionados" :disabled="jaNaTrilha(c)" class="accent-[#9c2337] w-4 h-4 shrink-0" />
            <span class="w-11 h-14 rounded-md bg-slate-100 overflow-hidden flex items-center justify-center text-slate-300 shrink-0">
              <img v-if="urlCapa(c)" :src="urlCapa(c)" alt="" class="w-full h-full object-cover" />
              <AppIcon v-else name="document" size="18" />
            </span>
            <span class="min-w-0">
              <span class="block text-sm font-semibold text-slate-800 truncate">{{ c.titulo }}</span>
              <span class="block text-[11px] text-slate-400">{{ rotuloTipo(c.tipo) }}{{ c.paginas ? ' · ' + c.paginas + ' pág.' : '' }}{{ jaNaTrilha(c) ? ' · já na trilha' : '' }}</span>
            </span>
          </label>
        </li>
      </ul>
      <p v-if="!cadernosFiltrados.length" class="text-sm text-slate-400 text-center py-6">Nenhum caderno encontrado.</p>
    </ModalCadastro>

    <!-- Modal: novo caderno (PDF) -->
    <ModalCadastro :aberto="novoAberto" titulo="Novo caderno (PDF)" descricao="O PDF é enviado agora e já entra na trilha." tamanho="md" rotulo-salvar="Enviar e adicionar" :enviando="enviandoPdf" :pode-salvar="!!novo.arquivo && !!novo.titulo.trim()" :erro="erroPdf" :sujo="!!novo.arquivo" @fechar="novoAberto = false" @salvar="enviarPdf">
      <div class="rounded-2xl border-2 border-dashed px-4 py-8 text-center cursor-pointer transition" :class="arrastandoPdf ? 'border-[#9c2337] bg-[#fdf2f4]' : 'border-slate-300 hover:border-slate-400'"
        @click="$refs.inputPdf.click()" @dragover.prevent="arrastandoPdf = true" @dragleave="arrastandoPdf = false" @drop.prevent="soltarPdf">
        <input ref="inputPdf" type="file" accept="application/pdf,.pdf" class="hidden" @change="escolherPdf($event.target.files[0])" />
        <AppIcon name="document" size="26" class="mx-auto text-slate-400" />
        <p v-if="novo.arquivo" class="text-sm font-semibold text-slate-800 mt-2">{{ novo.arquivo.name }}</p>
        <p v-else class="text-sm font-semibold text-slate-700 mt-2">Arraste o PDF aqui ou clique para escolher</p>
        <p class="text-xs text-slate-400 mt-0.5">{{ novo.arquivo ? (novo.arquivo.size / 1048576).toFixed(1) + ' MB · clique para trocar' : 'Somente arquivos .pdf' }}</p>
      </div>
      <label class="block text-xs font-medium text-slate-700 space-y-1.5">
        <span>Título</span>
        <input v-model="novo.titulo" type="text" maxlength="120" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
      </label>
      <div class="grid sm:grid-cols-2 gap-3">
        <label class="block text-xs font-medium text-slate-700 space-y-1.5">
          <span>Tipo</span>
          <select v-model="novo.tipo" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none">
            <option value="treinamento">Academia (emite certificado)</option>
            <option value="politica">Diretriz</option>
          </select>
        </label>
        <label class="block text-xs font-medium text-slate-700 space-y-1.5">
          <span>Tags <span class="text-slate-400 font-normal">(separadas por vírgula)</span></span>
          <input v-model="novo.tags" type="text" placeholder="segurança, epi" class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
        </label>
      </div>
      <label class="block text-xs font-medium text-slate-700 space-y-1.5">
        <span>Capa <span class="text-slate-400 font-normal">(opcional, imagem)</span></span>
        <input type="file" accept="image/*" @change="novo.capa = $event.target.files[0] || null" class="block w-full text-xs text-slate-600 file:mr-3 file:h-9 file:px-4 file:rounded-full file:border-0 file:bg-slate-100 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200 file:cursor-pointer" />
      </label>
    </ModalCadastro>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick, h } from 'vue';
import { doContexto } from '../utils/empresaContexto.js';
import AppIcon from './AppIcon.vue';
import WizardCadastro from './WizardCadastro.vue';
import ModalCadastro from './ModalCadastro.vue';
import { pode } from '../utils/permissoes.js';
import { tokenAdmin } from '../utils/sessaoAdmin.js';
import { urlCapa, urlCapaTrilha } from '../utils/capas.js';

/**
 * Trilhas da Academia no painel do RH: lista em cards + wizard de 4 passos (o mesmo para criar e editar).
 * Dados: GET/POST /api/rh/academia, POST /api/rh/academia/remover; cadernos, cargos e empresas vêm de /api/rh/resumo.
 */
const props = defineProps({ colaborador: { type: Object, default: null } });

// Ícone de "play" (o AppIcon não tem)
const IconePlay = (_, { attrs }) => h('svg', { viewBox: '0 0 24 24', fill: 'currentColor', 'aria-hidden': 'true', ...attrs }, [h('path', { d: 'M8 5.5v13a1 1 0 0 0 1.5.86l11-6.5a1 1 0 0 0 0-1.72l-11-6.5A1 1 0 0 0 8 5.5z' })]);

const lista = ref([]);
const carregando = ref(true);
const cadernos = ref([]);
const empresas = ref([]);
const colaboradores = ref([]);
const mensagem = ref('');
const mensagemErro = ref(false);
const filtro = ref('todas');

const filtros = [
  { id: 'todas', rotulo: 'Todas' },
  { id: 'obrigatorias', rotulo: 'Obrigatórias' },
  { id: 'funcao', rotulo: 'Por função' },
  { id: 'rascunhos', rotulo: 'Rascunhos' }
];
const passa = {
  todas: () => true,
  obrigatorias: (t) => t.obrigatoria,
  funcao: (t) => !t.obrigatoria,
  rascunhos: (t) => t.publicada === false
};
// Só as trilhas da empresa escolhida no menu lateral (as de "todas" aparecem sempre)
const listaCtx = computed(() => lista.value.filter((t) => doContexto(t.empresa)));
const contagem = (id) => listaCtx.value.filter(passa[id]).length;
const filtradas = computed(() => listaCtx.value.filter(passa[filtro.value]));
const qtd = (t, tipo) => t.itens.filter((i) => i.tipo === tipo).length;

const auth = () => ({ Authorization: `Bearer ${tokenAdmin()}` });
const jsonReq = (corpo) => ({ method: 'POST', headers: { 'Content-Type': 'application/json', ...auth() }, body: JSON.stringify(corpo) });
function aviso(texto, erro = false) {
  mensagem.value = texto;
  mensagemErro.value = erro;
}

async function carregar() {
  try {
    const res = await fetch('/api/rh/academia', { headers: auth() });
    const data = await res.json();
    if (!res.ok) return aviso(data.erro || 'Não foi possível carregar as trilhas.', true);
    lista.value = data.trilhas;
  } finally {
    carregando.value = false;
  }
}

async function carregarResumo() {
  try {
    const res = await fetch('/api/rh/resumo', { headers: auth() });
    if (!res.ok) return;
    const d = await res.json();
    empresas.value = d.empresas || [];
    cadernos.value = d.cadernos || [];
    categorias.value = d.categorias || [];
    colaboradores.value = (d.colaboradores || []).map((c) => ({ cargo: (c.cargo || '').trim(), empresa: c.empresa || '' }));
  } catch (e) { /* sem cadernos/cargos: o wizard continua funcionando */ }
}
const categorias = ref([]);

// ---------------------------------------------------------------- público atingido
const colabsDaEmpresa = (empresa) => colaboradores.value.filter((c) => empresa === 'todas' || !empresa || !c.empresa || c.empresa === empresa);
const mesmoCargo = (a, b) => a.trim().toLowerCase() === b.trim().toLowerCase();
function atingidos(t) {
  const base = colabsDaEmpresa(t.empresa);
  if (t.obrigatoria) return base.length;
  return base.filter((c) => c.cargo && (t.funcoes || []).some((f) => mesmoCargo(f, c.cargo))).length;
}

// ---------------------------------------------------------------- wizard
const passos = [
  { id: 'basico', titulo: 'Básico', descricao: 'Nome, descrição e empresa da trilha.' },
  { id: 'quem', titulo: 'Quem faz', descricao: 'Todos os colaboradores ou só algumas funções.' },
  { id: 'conteudo', titulo: 'Conteúdo', descricao: 'Monte a sequência de vídeos e cadernos. Arraste para reordenar.' },
  { id: 'revisar', titulo: 'Revisar e publicar', descricao: 'Confira tudo antes de publicar.' }
];
const PONTOS_ETAPA = 10;
const PONTOS_TRILHA = 50;
const novoId = () => 'i' + Math.random().toString(36).slice(2, 10);
const vazio = () => ({ id: '', titulo: '', descricao: '', carga_horaria: '', prazo: '', pontos: '', empresa: 'todas', obrigatoria: true, funcoes: [], itens: [], publicada: false });

// Capa: arquivo escolhido (enviado ao salvar), prévia e pedido de remoção
const campoCapa = ref(null);
const capaEscolhida = ref(null);
const previaCapa = ref('');
const removerCapaAoSalvar = ref(false);
const capaDaTrilha = ref(false); // a trilha em edição já tem capa salva
function escolherCapa(e) {
  const arquivo = e.target.files?.[0];
  if (!arquivo) return;
  if (arquivo.size > 3 * 1024 * 1024) { erroWizard.value = 'A imagem da capa pode ter no máximo 3 MB.'; e.target.value = ''; return; }
  erroWizard.value = '';
  capaEscolhida.value = arquivo;
  removerCapaAoSalvar.value = false;
  previaCapa.value = URL.createObjectURL(arquivo);
}
function tirarCapa() {
  capaEscolhida.value = null;
  previaCapa.value = '';
  removerCapaAoSalvar.value = capaDaTrilha.value;
  if (campoCapa.value) campoCapa.value.value = '';
}
async function enviarCapa(id) {
  if (capaEscolhida.value) {
    const fd = new FormData();
    fd.append('id', id);
    fd.append('capa', capaEscolhida.value);
    const res = await fetch('/api/rh/academia/capa', { method: 'POST', headers: auth(), body: fd });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error('Trilha salva, mas a capa não: ' + (data.erro || 'falha no envio.'));
  } else if (removerCapaAoSalvar.value) {
    await fetch('/api/rh/academia/capa/remover', jsonReq({ id }));
  }
}

const wizardAberto = ref(false);
const passo = ref(0);
const form = reactive(vazio());
const abertos = ref({});
const escolha = ref('publicar');
const snapshot = ref('');
const enviando = ref(false);
const erroWizard = ref('');
const sujo = computed(() => wizardAberto.value && JSON.stringify(form) !== snapshot.value);

function copiaItem(i) {
  return { id: i.id, tipo: i.tipo, titulo: i.titulo || '', descricao: i.descricao || '', url: i.url || '', duracao: i.duracao || '', arquivo: i.arquivo || '', pontos: i.pontos ?? '' };
}
async function abrirWizard(t, irPara = 0) {
  Object.assign(form, t
    ? { id: t.id, titulo: t.titulo, descricao: t.descricao || '', carga_horaria: t.carga_horaria || '', prazo: t.prazo || '', pontos: t.pontos ?? '', empresa: t.empresa || 'todas', obrigatoria: !!t.obrigatoria, funcoes: [...(t.funcoes || [])], itens: t.itens.map(copiaItem), publicada: t.publicada !== false }
    : vazio());
  capaEscolhida.value = null;
  removerCapaAoSalvar.value = false;
  capaDaTrilha.value = !!(t && t.capa);
  previaCapa.value = t ? urlCapaTrilha(t) || '' : '';
  if (campoCapa.value) campoCapa.value.value = '';
  abertos.value = {};
  escolha.value = t && t.publicada === false ? 'rascunho' : 'publicar';
  erroWizard.value = '';
  buscaCargo.value = '';
  snapshot.value = JSON.stringify(form);
  wizardAberto.value = true;
  await nextTick();
  passo.value = t ? irPara : 0; // o wizard volta ao passo 1 ao abrir; só depois vamos ao passo pedido
}

const dataBr = (iso) => (iso || '').split('-').reverse().join('/');
const urlValida = (u) => /^https:\/\/\S+$/i.test((u || '').trim());
const cad = (i) => cadernos.value.find((c) => c.arquivo === i.arquivo) || null;
const tituloDe = (i) => i.titulo.trim() || (i.tipo === 'caderno' ? cad(i)?.titulo || '' : '');
function problemaItem(i) {
  if (i.tipo === 'video') {
    if (!i.titulo.trim()) return 'Dê um título ao vídeo.';
    if (!urlValida(i.url)) return 'O vídeo precisa de um link que comece com https://';
  } else if (!cad(i)) return 'Um dos cadernos não existe mais. Remova-o da trilha.';
  return '';
}
function problemasConteudo() {
  for (const [n, i] of form.itens.entries()) {
    const p = problemaItem(i);
    if (p) return `Etapa ${n + 1}: ${p}`;
  }
  return '';
}
function validar(id) {
  if (id === 'basico') return form.titulo.trim() ? '' : 'Dê um nome para a trilha para continuar.';
  if (id === 'quem') return form.obrigatoria || form.funcoes.length ? '' : 'Escolha ao menos uma função ou marque a trilha como obrigatória para todos.';
  if (id === 'conteudo') return form.itens.length ? problemasConteudo() : 'Adicione ao menos um vídeo ou caderno à trilha.';
  return '';
}

// ---- Passo 2: funções
const buscaCargo = ref('');
const cargoFoco = ref(false);
const colabsDoCargo = (cargo) => colabsDaEmpresa(form.empresa).filter((c) => c.cargo && mesmoCargo(c.cargo, cargo)).length;
const cargosTodos = computed(() => {
  const mapa = new Map();
  for (const c of colabsDaEmpresa(form.empresa)) if (c.cargo) mapa.set(c.cargo, (mapa.get(c.cargo) || 0) + 1);
  return [...mapa].map(([cargo, qtd]) => ({ cargo, qtd })).sort((a, b) => a.cargo.localeCompare(b.cargo, 'pt-BR'));
});
const cargosDisponiveis = computed(() => cargosTodos.value.filter((c) => !form.funcoes.some((f) => mesmoCargo(f, c.cargo))));
const sugestoes = computed(() => {
  const q = buscaCargo.value.trim().toLowerCase();
  return cargosDisponiveis.value.filter((c) => !q || c.cargo.toLowerCase().includes(q)).slice(0, 30);
});
const funcoesSemColaborador = computed(() => colaboradores.value.length ? form.funcoes.filter((f) => !colabsDoCargo(f)) : []);
function adicionarFuncao(texto) {
  const t = (texto || '').trim();
  if (!t) return;
  if (!form.funcoes.some((f) => mesmoCargo(f, t))) {
    // Usa a grafia do cadastro quando houver
    form.funcoes.push(cargosTodos.value.find((c) => mesmoCargo(c.cargo, t))?.cargo || t);
  }
  buscaCargo.value = '';
}
const removerFuncao = (f) => { form.funcoes = form.funcoes.filter((x) => x !== f); };
const fecharSugestoes = () => setTimeout(() => { cargoFoco.value = false; }, 120);

// ---- Passo 3: construtor
const contar = (tipo) => form.itens.filter((i) => i.tipo === tipo).length;
function parseMin(s) {
  s = (s || '').toLowerCase().trim();
  if (!s) return 0;
  let m;
  if ((m = s.match(/^(\d+):(\d+)(?::(\d+))?$/))) return m[3] !== undefined ? +m[1] * 60 + +m[2] + Math.round(+m[3] / 60) : +m[1] + Math.round(+m[2] / 60);
  let total = 0;
  if ((m = s.match(/(\d+)\s*h/))) total += +m[1] * 60;
  if ((m = s.match(/(\d+)\s*m/))) total += +m[1];
  if (!total && /^\d+$/.test(s)) total = +s;
  return total;
}
const fmtMin = (min) => (min >= 60 ? `${Math.floor(min / 60)}h${min % 60 ? ' ' + String(min % 60).padStart(2, '0') + 'min' : ''}` : `${min} min`);
const duracaoVideos = computed(() => {
  const min = form.itens.filter((i) => i.tipo === 'video').reduce((s, i) => s + parseMin(i.duracao), 0);
  return min ? fmtMin(min) : '';
});
const resumoConteudo = computed(() => {
  const n = form.itens.length;
  const partes = [`${n} ${n === 1 ? 'etapa' : 'etapas'}`, `${contar('video')} ${contar('video') === 1 ? 'vídeo' : 'vídeos'}`, `${contar('caderno')} ${contar('caderno') === 1 ? 'caderno' : 'cadernos'}`];
  if (duracaoVideos.value) partes.push(duracaoVideos.value);
  return partes.join(' · ');
});
const rotuloTipo = (t) => (t === 'treinamento' ? 'Academia' : 'Diretriz');

function embedDe(url) {
  const u = (url || '').trim();
  const yt = u.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{6,})/i);
  if (yt) return { tipo: 'iframe', src: `https://www.youtube-nocookie.com/embed/${yt[1]}?rel=0` };
  const vm = u.match(/vimeo\.com\/(?:video\/)?(\d+)/i);
  if (vm) return { tipo: 'iframe', src: `https://player.vimeo.com/video/${vm[1]}` };
  if (/\.(mp4|webm|ogg)(\?|$)/i.test(u)) return { tipo: 'video', src: u };
  return null;
}
function miniatura(i) {
  if (i.tipo === 'caderno') return urlCapa(cad(i));
  const yt = (i.url || '').match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{6,})/i);
  return yt ? `https://img.youtube.com/vi/${yt[1]}/mqdefault.jpg` : null;
}
function duracaoDoArquivo(ev, item) {
  const s = ev.target.duration;
  if (!item.duracao && isFinite(s) && s > 0) item.duracao = fmtMin(Math.max(1, Math.round(s / 60)));
}

const alternar = (id) => { abertos.value[id] = !abertos.value[id]; };
function adicionarVideo() {
  const i = { id: novoId(), tipo: 'video', titulo: '', descricao: '', url: '', duracao: '', arquivo: '' };
  form.itens.unshift(i);
  abertos.value[i.id] = true;
  mostrarNoTopo(i.id);
}
// Etapas novas entram no topo da lista e a tela rola até elas
async function mostrarNoTopo(id) {
  await nextTick();
  document.getElementById('etapa-' + id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
function mover(n, d) {
  const alvo = n + d;
  if (alvo < 0 || alvo >= form.itens.length) return;
  const [x] = form.itens.splice(n, 1);
  form.itens.splice(alvo, 0, x);
}
function duplicarItem(n) {
  const c = { ...form.itens[n], id: novoId() };
  c.titulo = c.titulo ? c.titulo + ' (cópia)' : '';
  form.itens.splice(n + 1, 0, c);
  abertos.value[c.id] = true;
}
function removerItem(n) {
  const i = form.itens[n];
  if (!window.confirm(`Remover a etapa "${tituloDe(i) || n + 1}" da trilha?` + (form.id ? ' O progresso dos colaboradores nesta etapa deixa de contar.' : ''))) return;
  form.itens.splice(n, 1);
}
// arrastar e soltar
const dragIdx = ref(-1);
const overIdx = ref(-1);
function comecarArrasto(ev, n) {
  dragIdx.value = n;
  ev.dataTransfer.effectAllowed = 'move';
  ev.dataTransfer.setData('text/plain', String(n));
  const li = ev.currentTarget.closest('li');
  if (li) ev.dataTransfer.setDragImage(li, 20, 20);
}
function soltar(n) {
  const de = dragIdx.value;
  dragIdx.value = overIdx.value = -1;
  if (de < 0 || de === n) return;
  const [x] = form.itens.splice(de, 1);
  form.itens.splice(n, 0, x);
}

// caderno existente
const existenteAberto = ref(false);
const buscaCaderno = ref('');
const tipoCaderno = ref('');
const selecionados = ref([]);
const jaNaTrilha = (c) => form.itens.some((i) => i.tipo === 'caderno' && i.arquivo === c.arquivo);
const cadernosFiltrados = computed(() => {
  const q = buscaCaderno.value.trim().toLowerCase();
  return cadernos.value.filter((c) => (!tipoCaderno.value || c.tipo === tipoCaderno.value)
    && (!q || c.titulo.toLowerCase().includes(q) || (c.tags || []).some((t) => t.toLowerCase().includes(q))));
});
function abrirExistente() {
  selecionados.value = [];
  buscaCaderno.value = '';
  tipoCaderno.value = '';
  existenteAberto.value = true;
}
function adicionarSelecionados() {
  const novos = selecionados.value.map((arquivo) => ({ id: novoId(), tipo: 'caderno', titulo: '', descricao: '', url: '', duracao: '', arquivo }));
  form.itens.unshift(...novos);
  existenteAberto.value = false;
  if (novos.length) mostrarNoTopo(novos[0].id);
}
async function mudarTipoCaderno(c, tipo) {
  // salvarConfig regrava a configuração inteira: reenvia o que o caderno já tem
  const corpo = { arquivo: c.arquivo, titulo: c.titulo, categoria: c.categoria, obrigatorio: c.obrigatorio, publico: c.publico, valores: c.valores, empresa: c.empresa, tags: c.tags, carga_horaria: c.carga_horaria, prazo: c.prazo || '', tipo };
  try {
    const res = await fetch('/api/rh/cadernos/config', jsonReq(corpo));
    if (!res.ok) throw new Error();
    c.tipo = tipo;
  } catch (e) {
    erroWizard.value = 'Não foi possível mudar o tipo do caderno.';
  }
}

// novo caderno (PDF)
const novoAberto = ref(false);
const novo = reactive({ arquivo: null, titulo: '', tipo: 'treinamento', tags: '', capa: null });
const enviandoPdf = ref(false);
const erroPdf = ref('');
const arrastandoPdf = ref(false);
function abrirNovoCaderno() {
  Object.assign(novo, { arquivo: null, titulo: '', tipo: 'treinamento', tags: '', capa: null });
  erroPdf.value = '';
  novoAberto.value = true;
}
function escolherPdf(f) {
  if (!f) return;
  if (!/\.pdf$/i.test(f.name) && f.type !== 'application/pdf') { erroPdf.value = 'Escolha um arquivo PDF.'; return; }
  erroPdf.value = '';
  novo.arquivo = f;
  if (!novo.titulo.trim()) novo.titulo = f.name.replace(/\.pdf$/i, '').replace(/[_-]+/g, ' ').trim();
}
function soltarPdf(ev) {
  arrastandoPdf.value = false;
  escolherPdf(ev.dataTransfer.files[0]);
}
async function enviarPdf() {
  enviandoPdf.value = true;
  erroPdf.value = '';
  const treino = novo.tipo === 'treinamento';
  const categoria = (treino ? 'Treinamentos' : 'Políticas');
  const config = {
    titulo: novo.titulo.trim(), tipo: novo.tipo, categoria: categorias.value.includes(categoria) ? categoria : 'Outros',
    tags: novo.tags.split(',').map((t) => t.trim()).filter(Boolean), obrigatorio: false, publico: 'todos', valores: [], empresa: form.empresa
  };
  const fd = new FormData();
  fd.append('arquivo', novo.arquivo);
  fd.append('config', JSON.stringify(config));
  if (novo.capa) fd.append('capa', novo.capa);
  try {
    const res = await fetch('/api/rh/cadernos', { method: 'POST', headers: auth(), body: fd });
    const data = await res.json();
    if (!res.ok) throw new Error(data.erro || 'Não foi possível enviar o PDF.');
    await carregarResumo();
    const i = { id: novoId(), tipo: 'caderno', titulo: '', descricao: '', url: '', duracao: '', arquivo: data.arquivo };
    form.itens.unshift(i);
    novoAberto.value = false;
    mostrarNoTopo(i.id);
  } catch (e) {
    erroPdf.value = e.message || 'Não foi possível enviar o PDF.';
  } finally {
    enviandoPdf.value = false;
  }
}

// ---------------------------------------------------------------- salvar
const numOuNulo = (v) => (v === '' || v == null ? null : Number(v));
const corpoItens = (itens) => itens.map((i) => (i.tipo === 'video'
  ? { id: i.id, tipo: 'video', titulo: i.titulo, descricao: i.descricao, url: i.url.trim(), duracao: i.duracao, pontos: numOuNulo(i.pontos) }
  : { id: i.id, tipo: 'caderno', titulo: i.titulo, descricao: i.descricao, arquivo: i.arquivo, pontos: numOuNulo(i.pontos) }));

async function enviarTrilha(t, publicada) {
  const corpo = { id: t.id, titulo: t.titulo, descricao: t.descricao, obrigatoria: t.obrigatoria, funcoes: t.funcoes, empresa: t.empresa, carga_horaria: t.carga_horaria, prazo: t.prazo || '', pontos: numOuNulo(t.pontos), publicada, itens: corpoItens(t.itens) };
  const res = await fetch('/api/rh/academia', jsonReq(corpo));
  const data = await res.json();
  if (!res.ok) throw new Error(data.erro || 'Não foi possível salvar a trilha.');
  return data.trilha;
}

async function salvar(publicada) {
  erroWizard.value = '';
  if (!form.titulo.trim()) { passo.value = 0; erroWizard.value = 'Dê um nome para a trilha.'; return; }
  const p = problemasConteudo();
  if (p) { passo.value = 2; erroWizard.value = p; return; }
  enviando.value = true;
  try {
    const salva = await enviarTrilha(form, publicada);
    await enviarCapa(salva.id);
    aviso(publicada ? (form.id ? 'Trilha salva e publicada.' : 'Trilha publicada.') : 'Rascunho salvo. Publique quando estiver pronta.');
    wizardAberto.value = false;
    await carregar();
  } catch (e) {
    erroWizard.value = e.message;
  } finally {
    enviando.value = false;
  }
}

async function acao(texto, fn) {
  aviso('');
  try {
    await fn();
    aviso(texto);
    await carregar();
  } catch (e) {
    aviso(e.message || 'Não foi possível concluir a ação.', true);
  }
}

const duplicar = (t) => acao('Trilha duplicada como rascunho.', () => enviarTrilha({ ...t, id: '', titulo: `${t.titulo} (cópia)`, itens: t.itens.map((i) => ({ ...copiaItem(i), id: novoId() })) }, false));
const alternarPublicacao = (t) => acao(t.publicada === false ? 'Trilha publicada.' : 'Trilha despublicada: voltou a ser rascunho.', () => enviarTrilha({ ...t, itens: t.itens.map(copiaItem) }, t.publicada === false));

async function remover(t) {
  if (!window.confirm(`Remover a trilha "${t.titulo}"? Os certificados já emitidos continuam válidos.`)) return;
  await acao('Trilha removida.', async () => {
    const res = await fetch('/api/rh/academia/remover', jsonReq({ id: t.id }));
    const data = await res.json();
    if (!res.ok) throw new Error(data.erro || 'Não foi possível remover.');
  });
}

onMounted(async () => {
  await Promise.all([carregar(), carregarResumo()]);
});
</script>
