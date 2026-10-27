<template>
  <div 
    class="bg-slate-100 flex flex-col selection:bg-[#9c2337] selection:text-white text-slate-800"
    :class="etapaAtual === 'dashboard' ? 'h-screen overflow-hidden' : 'min-h-screen'"
  >
    
    <!-- TELA PÚBLICA DE AUDITORIA E VALIDAÇÃO DE AUTENTICIDADE VIA QR CODE -->
    <ValidacaoPublicaView v-if="ehRotaValidacao" />

    <!-- TELA DE TRANSIÇÃO / RESTAURAÇÃO DE SESSÃO COM LOGO PINTANDO DINÂMICO -->
    <div v-else-if="verificandoSessao" class="min-h-screen flex-1 flex flex-col items-center justify-center p-6 bg-slate-50">
      <LogoLoader />
    </div>

    <!-- ========================================================================= -->
    <!-- TELA 1: LOGIN (RESPONSIVO: SPLIT SCREEN NO DESKTOP, COMPACTO NO MOBILE)   -->
    <!-- ========================================================================= -->
    <div v-else-if="etapaAtual === 'login'" class="flex-1 flex items-center justify-center p-3 sm:p-6 lg:p-8">
      <div class="w-full max-w-md md:max-w-xl lg:max-w-5xl xl:max-w-6xl bg-white rounded-2xl lg:rounded-3xl shadow-2xl overflow-hidden flex flex-col lg:flex-row border border-slate-200">
        
        <!-- Lado Esquerdo Institucional (Desktop / Tablet) - Sólido Corporativo Sem Gradiente -->
        <div class="lg:w-1/2 bg-[#141414] text-white p-8 lg:p-12 flex flex-col justify-between relative border-b lg:border-b-0 lg:border-r border-slate-800">
          <div class="relative z-10 space-y-6">
            <div class="inline-flex items-center space-x-3 bg-white/10 px-4 py-2.5 rounded-2xl border border-white/15">
              <img src="/img/logo-vilaboa-white.png" alt="Vila Boa Construções e Serviços" class="h-9 w-auto object-contain" />
            </div>

            <div class="space-y-3 pt-2">
              <h1 class="text-2xl sm:text-3xl lg:text-4xl font-semibold leading-tight tracking-tight">
                Portal do Colaborador
              </h1>
              <p class="text-slate-300 text-xs sm:text-sm leading-relaxed">
                Plataforma digital integrada para acesso a demonstrativos de pagamento, controle de EPIs (NR-06), consulta de férias e solicitações ao RH.
              </p>
            </div>

            <!-- Benefícios em Destaque no Desktop -->
            <div class="hidden lg:grid grid-cols-1 gap-3 pt-4 border-t border-white/15">
              <div class="flex items-center space-x-3 text-xs text-slate-200">
                <span class="w-6 h-6 rounded-lg bg-white/10 border border-white/10 flex items-center justify-center text-white shrink-0">
                  <AppIcon name="check" size="14" />
                </span>
                <span>Holerites, 13º Salário e Informe de Rendimentos</span>
              </div>
              <div class="flex items-center space-x-3 text-xs text-slate-200">
                <span class="w-6 h-6 rounded-lg bg-white/10 border border-white/10 flex items-center justify-center text-white shrink-0">
                  <AppIcon name="check" size="14" />
                </span>
                <span>Ficha de EPI com Assinatura Eletrônica (NR-06)</span>
              </div>
              <div class="flex items-center space-x-3 text-xs text-slate-200">
                <span class="w-6 h-6 rounded-lg bg-white/10 border border-white/10 flex items-center justify-center text-white shrink-0">
                  <AppIcon name="check" size="14" />
                </span>
                <span>Consulta de Férias e Autoatendimento ao RH</span>
              </div>
            </div>
          </div>

          <div class="relative z-10 pt-6 text-[11px] text-slate-400 font-medium">
            Segurança Criptografada • Protegido pela LGPD
          </div>
        </div>

        <!-- Lado Direito: Formulário de Autenticação -->
        <div class="lg:w-1/2 p-6 sm:p-8 lg:p-10 flex flex-col justify-between bg-white">
          <div class="space-y-6">
            <div>
              <div class="lg:hidden flex items-center mb-4">
                <img src="/img/logo-horizontal.png" alt="Vila Boa Construções e Serviços" class="h-8 w-auto object-contain" />
              </div>
              <h2 class="text-xl sm:text-2xl font-semibold text-slate-900 tracking-tight">Acesso ao Portal</h2>
              <p class="text-xs sm:text-sm text-slate-500">Informe seu CPF e data de nascimento cadastrados</p>
            </div>

            <!-- Mensagem de Alerta/Erro -->
            <div v-if="mensagemErro" class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-start gap-2 animate-shake">
              <AppIcon name="alert-triangle" size="16" class="text-slate-500 shrink-0 mt-0.5" />
              <span class="font-medium">{{ mensagemErro }}</span>
            </div>

            <!-- Formulário -->
            <form @submit.prevent="fazerLogin" class="space-y-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                  CPF do Colaborador
                </label>
                <input 
                  type="tel"
                  inputmode="numeric"
                  v-model="formLogin.cpf"
                  @input="handleCpfInput"
                  placeholder="000.000.000-00"
                  maxlength="14"
                  class="w-full px-4 py-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold text-base focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#9c2337] focus:border-transparent transition"
                  required
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                  Data de Nascimento
                </label>
                <input 
                  type="tel"
                  inputmode="numeric"
                  v-model="formLogin.data_nascimento"
                  @input="handleDataInput"
                  placeholder="DD/MM/AAAA"
                  maxlength="10"
                  class="w-full px-4 py-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold text-base focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#9c2337] focus:border-transparent transition"
                  required
                />
              </div>

              <button 
                type="submit" 
                :disabled="carregando || formLogin.cpf.length < 14 || formLogin.data_nascimento.length < 10 || bloqueioSegundos > 0"
                class="w-full py-4 px-6 rounded-2xl bg-[#9c2337] hover:bg-[#831b2d] disabled:opacity-50 active:scale-[0.98] text-white font-bold text-sm tracking-wide shadow-lg shadow-[#9c2337]/25 transition flex items-center justify-center gap-2 cursor-pointer"
              >
                <span v-if="carregando" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span>{{ bloqueioSegundos > 0 ? 'Aguarde ' + tempoFormatadoBloqueio : (carregando ? 'Autenticando...' : 'Acessar Portal') }}</span>
                <span v-if="!carregando && bloqueioSegundos === 0">→</span>
              </button>
            </form>

            <!-- Preenchimento Rápido com Mocks (Homologação & LGPD) -->
            <div class="pt-4 border-t border-slate-100">
              <div class="flex items-center justify-between mb-2">
                <button 
                  type="button" 
                  @click="mostrarMocks = !mostrarMocks"
                  class="text-[11px] font-bold uppercase tracking-wider text-slate-500 hover:text-slate-800 flex items-center gap-1 cursor-pointer transition select-none"
                >
                  <span>{{ mostrarMocks ? '▼ Ocultar Perfis de Teste' : '▶ Perfis de Teste (Homologação)' }}</span>
                </button>
                <span class="text-[9px] bg-slate-100 text-slate-600 border border-slate-200 px-2 py-0.5 rounded-full font-bold">
                  Ambiente Homologação
                </span>
              </div>

              <div v-if="mostrarMocks" class="space-y-2">
                <p class="text-[10px] text-slate-400 leading-tight">
                  <strong class="text-slate-600">Conformidade LGPD:</strong> Em produção, esta lista é desativada para resguardar a privacidade dos dados funcionais.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-1 gap-2">
                <button 
                  v-for="demo in colaboradoresDemo" 
                  :key="demo.id"
                  type="button"
                  @click="preencherDemo(demo)"
                  class="w-full text-left p-3 rounded-2xl border border-slate-200/90 hover:border-[#9c2337] bg-slate-50/80 hover:bg-rose-50/40 active:scale-[0.99] transition flex items-center justify-between group cursor-pointer shadow-2xs"
                >
                  <div class="flex items-center space-x-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-white border border-slate-200 group-hover:border-[#9c2337]/40 flex items-center justify-center font-semibold text-xs text-[#9c2337] shrink-0 shadow-2xs">
                      {{ demo.nome.slice(0, 2).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                      <div class="text-xs font-bold text-slate-800 group-hover:text-[#9c2337] transition truncate">{{ demo.nome }}</div>
                      <div class="text-[10px] text-slate-400 font-medium truncate">{{ demo.cargo }} • Mat: {{ demo.matricula }}</div>
                    </div>
                  </div>
                  <div class="text-right shrink-0">
                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 block">Nasc.</span>
                    <span class="text-[11px] font-mono font-bold text-slate-700 group-hover:text-[#9c2337]">
                      {{ demo.data_nascimento }}
                    </span>
                  </div>
                </button>
              </div>
              </div>
            </div>
          </div>

          <div class="text-center pt-4 text-[11px] text-slate-400 space-y-1">
            <div>© 2026 Vila Boa Construções e Serviços LTDA</div>
            <div>
              <button 
                type="button" 
                @click="exibirModalLgpd = true"
                class="hover:text-slate-600 underline font-semibold transition cursor-pointer"
              >
                Segurança & Proteção de Dados (LGPD)
              </button>
            </div>
            <div>
              <a :href="urlPainelRh" class="inline-flex items-center gap-1.5 mt-1 h-9 px-4 rounded-full border border-slate-300 text-slate-700 hover:border-slate-400 hover:text-slate-900 font-semibold transition">
                <AppIcon name="shield-check" size="13" />
                Acesso do RH
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TELA 2A: CONFIRMAÇÃO POR TOKEN NO CELULAR (com alternativa pelas perguntas) -->
    <div v-else-if="etapaAtual === 'token'" class="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-10">
      <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200 p-6 sm:p-10 space-y-6 animate-slide-up">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
          <button @click="voltarParaLogin" class="text-slate-400 hover:text-slate-700 text-xs font-semibold flex items-center gap-1 transition cursor-pointer">
            ← Voltar para o início
          </button>
          <span class="text-xs font-bold text-[#9c2337]">Etapa 3 de 3</span>
        </div>

        <div class="text-center space-y-2">
          <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-slate-100 text-slate-600 border border-slate-200">
            <AppIcon name="phone" size="24" />
          </div>
          <h2 class="text-xl sm:text-2xl font-semibold text-slate-800">Confirme pelo celular</h2>
          <p class="text-xs sm:text-sm text-slate-500">
            Olá, <strong class="text-slate-700">{{ dadosEtapaSeguranca.colaborador?.primeiro_nome }}</strong>. Enviamos um código de 6 dígitos para
            <strong class="text-slate-700">{{ dadosEtapaSeguranca.token_sms?.telefone_mascarado }}</strong>.
          </p>
        </div>

        <input
          v-model="codigoToken"
          @input="codigoToken = codigoToken.replace(/\D/g, '').slice(0, 6)"
          @keydown.enter="validarToken"
          type="tel"
          inputmode="numeric"
          autocomplete="one-time-code"
          placeholder="000000"
          class="w-full h-14 text-center text-2xl tracking-[0.5em] font-mono rounded-xl border border-slate-300 focus:border-[#9c2337] focus:ring-2 focus:ring-rose-100 outline-none"
        />

        <p v-if="mensagemErro" class="text-xs text-rose-700 text-center">{{ mensagemErro }}</p>

        <button
          type="button"
          @click="validarToken"
          :disabled="codigoToken.length !== 6 || carregando"
          class="w-full h-12 rounded-full bg-[#9c2337] hover:bg-[#831b2d] disabled:opacity-50 text-white font-semibold text-sm transition cursor-pointer"
        >
          {{ carregando ? 'Validando...' : 'Confirmar código' }}
        </button>

        <button v-if="dadosEtapaSeguranca.perguntas_ativas" type="button" @click="responderPerguntas" class="w-full text-xs font-semibold text-slate-500 hover:text-slate-900 transition cursor-pointer">
          Não tenho acesso ao meu celular
        </button>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TELA 2: VERIFICAÇÃO DE SEGURANÇA (DADO PESSOAL ALEATÓRIO)                 -->
    <!-- ========================================================================= -->
    <div v-else-if="etapaAtual === 'seguranca'" class="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-10">
      <div class="w-full max-w-xl lg:max-w-3xl bg-white rounded-2xl shadow-2xl border border-slate-200 p-6 sm:p-10 space-y-6 animate-slide-up">
        
        <!-- Indicador de Progresso -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
          <button @click="voltarParaLogin" class="text-slate-400 hover:text-slate-700 text-xs font-semibold flex items-center gap-1 transition cursor-pointer">
            ← Voltar para o início
          </button>
          <div class="flex items-center gap-1.5">
            <span class="text-xs font-bold text-[#9c2337]">Etapa 2 de 3</span>
            <div class="flex gap-1">
              <span class="w-4 h-1.5 rounded-full bg-[#9c2337]"></span>
              <span class="w-4 h-1.5 rounded-full bg-[#9c2337]"></span>
              <span class="w-2 h-1.5 rounded-full bg-slate-200"></span>
            </div>
          </div>
        </div>

        <div class="text-center space-y-2">
          <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-slate-100 text-slate-600 border border-slate-200 shadow-sm">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
          <h2 class="text-xl sm:text-2xl font-semibold text-slate-800">Verificação de Segurança</h2>
          <p class="text-xs sm:text-sm text-slate-500">
            Olá, <strong class="text-slate-700">{{ dadosEtapaSeguranca.colaborador?.primeiro_nome }}</strong>. Para proteger suas informações salariais contra acessos não autorizados, confirme o dado abaixo:
          </p>
        </div>

        <!-- Pergunta Aleatória -->
        <div class="bg-rose-50/70 border border-rose-200 p-4 rounded-2xl space-y-1">
          <span class="text-[10px] uppercase font-bold text-[#9c2337] tracking-wider">Pergunta de Autenticação</span>
          <p class="text-sm sm:text-base font-bold text-slate-900 leading-snug">
            {{ dadosEtapaSeguranca.pergunta?.enunciado }}
          </p>
          <p class="text-xs text-slate-500">
            {{ dadosEtapaSeguranca.pergunta?.dica }}
          </p>
        </div>

        <!-- Card de Bloqueio com Cronômetro Regressivo -->
        <div v-if="bloqueioSegundos > 0" class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-950 space-y-2 text-center animate-pulse">
          <div class="flex items-center justify-center gap-1.5 font-bold text-xs text-amber-900">
            <AppIcon name="lock" size="14" class="text-slate-500" />
            <span>Tentativa Temporariamente Travada</span>
          </div>
          <div class="text-3xl font-semibold font-mono text-[#9c2337]">
            {{ tempoFormatadoBloqueio }}
          </div>
          <p class="text-[11px] text-amber-900/90 font-medium">
            Resposta incorreta. Uma nova pergunta de validação foi sorteada acima. Aguarde a contagem zerar para poder responder.
          </p>
        </div>

        <!-- Erro comum (se não estiver em bloqueio) -->
        <div v-else-if="mensagemErro" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium flex items-start gap-1.5">
          <AppIcon name="alert-triangle" size="14" class="text-slate-500 shrink-0 mt-0.5" />
          <span>{{ mensagemErro }}</span>
        </div>

        <!-- Opções -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
          <button
            v-for="(opcao, idx) in dadosEtapaSeguranca.pergunta?.opcoes"
            :key="idx"
            :disabled="bloqueioSegundos > 0"
            @click="bloqueioSegundos === 0 && (respostaSelecionada = opcao)"
            class="p-4 rounded-2xl border text-left transition flex items-center justify-between text-xs sm:text-sm"
            :class="[
              bloqueioSegundos > 0 ? 'opacity-40 cursor-not-allowed bg-slate-100 border-slate-200 text-slate-400' : 'cursor-pointer',
              respostaSelecionada === opcao && bloqueioSegundos === 0
                ? 'border-[#9c2337] bg-rose-50/90 text-[#9c2337] font-bold shadow-sm' 
                : 'border-slate-200 bg-slate-50/60 hover:bg-slate-100 text-slate-800 font-medium'
            ]"
          >
            <span class="truncate">{{ opcao }}</span>
            <span 
              class="w-5 h-5 ml-2 shrink-0 rounded-full border flex items-center justify-center text-xs"
              :class="respostaSelecionada === opcao && bloqueioSegundos === 0 ? 'border-[#9c2337] bg-[#9c2337] text-white' : 'border-slate-300'"
            >
              <AppIcon v-if="respostaSelecionada === opcao && bloqueioSegundos === 0" name="check" size="12" />
            </span>
          </button>
        </div>

        <button 
          @click="validarRespostaSeguranca"
          :disabled="carregando || !respostaSelecionada || bloqueioSegundos > 0"
          class="w-full py-4 px-6 rounded-2xl bg-[#9c2337] hover:bg-[#831b2d] disabled:opacity-50 active:scale-[0.98] text-white font-bold text-sm shadow-lg shadow-[#9c2337]/25 transition flex items-center justify-center gap-2"
          :class="bloqueioSegundos > 0 ? 'cursor-not-allowed' : 'cursor-pointer'"
        >
          <span v-if="carregando" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
          <span>{{ bloqueioSegundos > 0 ? 'Aguarde ' + tempoFormatadoBloqueio : 'Confirmar Resposta' }}</span>
          <span v-if="!carregando && bloqueioSegundos === 0">→</span>
        </button>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TELA 3: ATUALIZAÇÃO / CONFIRMAÇÃO DE TELEFONE (OBJETIVO PRIMÁRIO DA EMPRESA)-->
    <!-- ========================================================================= -->
    <div v-else-if="etapaAtual === 'telefone'" class="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-10">
      <div class="w-full max-w-xl lg:max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200 p-6 sm:p-10 space-y-6 animate-slide-up">
        
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
          <div class="flex items-center gap-2">
            <span class="inline-block w-2.5 h-2.5 rounded-full bg-[#9c2337] animate-pulse"></span>
            <span class="text-xs font-bold text-[#9c2337] uppercase tracking-wider">Ação Obrigatória</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="text-xs font-bold text-[#9c2337]">Etapa {{ fluxoToken ? '2' : '3' }} de 3</span>
            <div class="flex gap-1">
              <span class="w-4 h-1.5 rounded-full bg-[#9c2337]"></span>
              <span class="w-4 h-1.5 rounded-full bg-[#9c2337]"></span>
              <span class="w-4 h-1.5 rounded-full bg-[#9c2337]"></span>
            </div>
          </div>
        </div>

        <div class="text-center space-y-2">
          <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-slate-100 border border-slate-200 text-slate-600 shadow-md">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
          </div>
          <h2 class="text-xl sm:text-2xl font-semibold text-slate-800">Confirmação de Telefone</h2>
          <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
            O Departamento de RH da <strong>Vila Boa Construções e Serviços</strong> precisa do seu contato atualizado para envio de avisos de folha, benefícios e comunicados corporativos.
          </p>
        </div>

        <div v-if="mensagemErro" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">
          {{ mensagemErro }}
        </div>

        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 space-y-4">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Telefone Cadastrado</span>
            <span class="text-xs text-slate-400 font-medium">WhatsApp / Celular</span>
          </div>

          <div v-if="!editandoTelefone" class="space-y-4">
            <div class="p-4 bg-white border border-slate-200 rounded-xl flex items-center justify-between shadow-sm">
              <span class="text-xl font-bold font-mono text-slate-800 tracking-tight">
                {{ dadosEtapaTelefone.colaborador?.telefone_atual || '(Não informado)' }}
              </span>
              <span class="text-xs bg-rose-100 text-[#9c2337] font-bold px-3 py-1 rounded-lg">
                Atual
              </span>
            </div>

            <div class="space-y-2.5">
              <button 
                type="button"
                @click="confirmarTelefoneMesmo()"
                :disabled="carregando"
                class="w-full py-4 px-4 rounded-xl bg-[#9c2337] hover:bg-[#831b2d] active:scale-[0.98] text-white font-bold text-sm shadow-md transition flex items-center justify-center gap-2 cursor-pointer"
              >
                <span v-if="carregando" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <AppIcon v-else name="check" size="16" />
                <span>Sim, este ainda é meu número</span>
              </button>

              <button 
                type="button"
                @click="iniciarEdicaoTelefone"
                class="w-full py-3 px-4 rounded-xl bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs transition cursor-pointer flex items-center justify-center gap-1.5"
              >
                <AppIcon name="edit" size="14" />
                <span>Meu número mudou (Informar novo)</span>
              </button>
            </div>
          </div>

          <div v-else class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                Novo Telefone Celular com DDD
              </label>
              <input 
                type="tel"
                inputmode="numeric"
                v-model="novoTelefoneInput"
                @input="handleTelefoneInput"
                placeholder="(11) 90000-0000"
                maxlength="15"
                class="w-full px-4 py-3.5 rounded-xl bg-white border-2 border-[#9c2337] font-mono text-lg font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#9c2337]"
                autofocus
              />
              <span class="text-xs text-slate-400 block mt-1">Informe DDD + 9 dígitos para celular/WhatsApp</span>
            </div>

            <div class="space-y-2">
              <button 
                type="button"
                @click="salvarNovoTelefone"
                :disabled="carregando || novoTelefoneInput.length < 14"
                class="w-full py-4 px-4 rounded-xl bg-[#9c2337] hover:bg-[#831b2d] disabled:opacity-50 active:scale-[0.98] text-white font-bold text-sm shadow-md transition flex items-center justify-center gap-2 cursor-pointer"
              >
                <span v-if="carregando" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span>Salvar Novo Telefone e Continuar →</span>
              </button>

              <button 
                type="button"
                @click="editandoTelefone = false"
                class="w-full py-2 px-4 text-slate-500 hover:text-slate-800 text-xs font-medium cursor-pointer"
              >
                Cancelar e manter número anterior
              </button>
            </div>
          </div>
        </div>

        <div class="text-center text-xs text-slate-400 flex items-center justify-center gap-1.5">
          <AppIcon name="lock" size="12" />
          <span>Confirmação gravada com data e hora para fins de conformidade e RH.</span>
        </div>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TELA 4: DASHBOARD CORPORATIVO ENTERPRISE (SIDEBAR DESKTOP + DRAWER MOBILE)-->
    <!-- ========================================================================= -->
    <div v-else-if="etapaAtual === 'dashboard'" class="flex-1 flex min-h-0 h-full overflow-hidden">
      
      <!-- SIDEBAR RETRÁTIL DESKTOP -->
      <Sidebar 
        :progressoCadernos="progressoCadernos"
        :recolhida="sidebarRecolhida" 
        :abaAtiva="abaAtiva"
        :colaborador="usuarioLogado"
        :episPendentes="epis.total_pendentes"
        @toggle="alternarSidebar"
        @selecionar="navegarParaAba($event)"
        @gerar-pacote="gerarPacoteRenda"
        @logout="fazerLogout"
      />

      <!-- DRAWER MÓVEL (SUPER APP FEEL) -->
      <MobileDrawer 
        :progressoCadernos="progressoCadernos"
        :aberto="drawerMobileAberto"
        :abaAtiva="abaAtiva"
        :colaborador="usuarioLogado"
        :episPendentes="epis.total_pendentes"
        @close="drawerMobileAberto = false"
        @selecionar="navegarParaAba($event)"
        @gerar-pacote="gerarPacoteRenda"
        @abrir-lgpd="exibirModalLgpd = true"
        @logout="fazerLogout"
      />

      <!-- ÁREA PRINCIPAL DE CONTEÚDO (APP SHELL COM HEADER E FOOTER FIXOS) -->
      <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden bg-[#f7f7f8]">
        
        <!-- Header Superior Fixo: Mobile Native SuperApp Bar + Desktop Titlebar -->
        <header class="h-16 shrink-0 bg-[#141414] border-b border-[#262626] px-3.5 sm:px-6 flex items-center justify-between z-20 select-none shadow-xs safe-top">
          <!-- ========================================================= -->
          <!-- 1. VERSÃO MOBILE (md:hidden): ESTILO SUPERAPP (IFOOD / FINTECH) -->
          <!-- ========================================================= -->
          <div class="md:hidden flex items-center justify-between w-full">
            <!-- Lado Esquerdo: Avatar Circular com Status Online + Saudação -->
            <button 
              type="button"
              class="flex items-center space-x-2.5 text-left min-w-0 cursor-default"
            >
              <div class="relative shrink-0">
                <div class="w-10 h-10 rounded-full bg-[#9c2337] text-white flex items-center justify-center font-semibold text-sm shadow-xs ring-2 ring-white/30">
                  {{ usuarioLogado?.nome ? usuarioLogado.nome.charAt(0).toUpperCase() : 'VB' }}
                </div>
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-white absolute bottom-0 right-0 shadow-[0_0_6px_rgba(16,185,129,0.8)]"></span>
              </div>

              <div class="overflow-hidden min-w-0">
                <div class="flex items-center gap-1.5 leading-none">
                  <span class="text-[10px] font-bold text-neutral-200 uppercase tracking-wider truncate">
                    {{ usuarioLogado?.cargo || 'Colaborador' }}
                  </span>
                </div>
                <h2 class="text-sm font-semibold text-white truncate leading-tight mt-0.5">
                  Olá, {{ usuarioLogado?.nome ? usuarioLogado.nome.split(' ')[0] : 'Colaborador' }}
                </h2>
              </div>
            </button>

            <!-- Lado Direito: Ações Rápidas em Pílulas / Botões Circulares -->
            <div class="flex items-center space-x-1.5 shrink-0">
              <NotificacoesBotao :itens="notificacoes" @abrir="carregarPendencias" />
              <!-- Botão Ocultar / Mostrar Valores Salariais -->
              <button 
                type="button"
                @click="ocultarValores = !ocultarValores"
                class="w-9 h-9 rounded-full bg-white/10 active:bg-white/20 text-white flex items-center justify-center transition border border-white/20 cursor-pointer active:scale-95"
                :title="ocultarValores ? 'Exibir valores' : 'Ocultar valores por privacidade'"
                :aria-label="ocultarValores ? 'Exibir valores' : 'Ocultar valores'"
              >
                <AppIcon :name="ocultarValores ? 'eye' : 'eye-off'" size="16" />
              </button>

              <!-- Botão Menu Lateral Drawer (Gaveta Nativa) -->
              <button 
                type="button"
                @click="drawerMobileAberto = true"
                class="w-9 h-9 rounded-full bg-white/10 active:bg-white/20 text-white flex items-center justify-center transition border border-white/20 cursor-pointer active:scale-95"
                title="Abrir Menu de Navegação"
                aria-label="Abrir Menu"
              >
                <AppIcon name="menu" size="17" />
              </button>


            </div>
          </div>

          <!-- ========================================================= -->
          <!-- 2. VERSÃO DESKTOP / TABLET (hidden md:flex): TITLEBAR EXECUTIVA -->
          <!-- ========================================================= -->
          <div class="hidden md:flex items-center justify-between w-full min-w-0 gap-3">
            <!-- Busca geral do portal (estilo NewRocket) -->
            <div class="hidden lg:block relative flex-1 max-w-md">
              <div class="flex items-center h-9 rounded-lg bg-white overflow-hidden">
                <AppIcon name="search" size="15" class="text-slate-400 ml-3 shrink-0" />
                <input
                  v-model="buscaGlobal"
                  @focus="buscaGlobalAberta = true; carregarCadernosBusca()"
                  @blur="fecharBuscaGlobal"
                  @keydown.enter.prevent="resultadosBusca[0] && selecionarResultado(resultadosBusca[0])"
                  type="text"
                  placeholder="Buscar no portal: holerites, cadernos, ponto..."
                  class="flex-1 min-w-0 h-full px-3 text-sm text-slate-800 placeholder-slate-400 outline-none"
                />
              </div>

              <div
                v-if="buscaGlobalAberta && buscaGlobal.trim()"
                class="absolute left-0 right-0 top-full mt-2 bg-white rounded-xl border border-slate-200 shadow-xl py-2 z-50 max-h-96 overflow-y-auto"
              >
                <p v-if="!resultadosBusca.length" class="px-4 py-3 text-sm text-slate-500">Nada encontrado para "{{ buscaGlobal }}".</p>
                <button
                  v-for="(r, i) in resultadosBusca"
                  :key="i"
                  type="button"
                  @mousedown.prevent="selecionarResultado(r)"
                  class="w-full px-4 py-2.5 flex items-center gap-3 text-left hover:bg-slate-50 cursor-pointer"
                >
                  <span class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                    <AppIcon :name="r.icone" size="15" />
                  </span>
                  <span class="min-w-0 flex-1">
                    <span class="block text-sm font-medium text-slate-800 truncate">{{ r.titulo }}</span>
                    <span class="block text-xs text-slate-400 truncate">{{ r.categoria }}</span>
                  </span>
                </button>
              </div>
            </div>

            <!-- Ações da Direita no Desktop e Tablet -->
            <div class="flex items-center space-x-2 sm:space-x-3 shrink-0">
              <!-- Notificações: todas as pendências do colaborador -->
              <NotificacoesBotao :itens="notificacoes" @abrir="carregarPendencias" />

              <!-- Botão Ocultar / Mostrar Valores Salariais (Disponível no Header Desktop/Tablet) -->
              <button 
                type="button"
                @click="ocultarValores = !ocultarValores"
                class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition border border-white/20 cursor-pointer active:scale-95"
                :title="ocultarValores ? 'Exibir valores salariais' : 'Ocultar valores por privacidade'"
                :aria-label="ocultarValores ? 'Exibir valores' : 'Ocultar valores'"
              >
                <AppIcon :name="ocultarValores ? 'eye' : 'eye-off'" size="15" />
              </button>



              <!-- Perfil do Usuário Logado com Menu Flutuante -->
              <div class="relative pl-2 border-l border-white/20">
                <button 
                  type="button"
                  @click="menuUsuarioAberto = !menuUsuarioAberto"
                  class="flex items-center gap-2 py-1 px-2 rounded-xl hover:bg-white/10 active:bg-white/20 transition cursor-pointer text-left select-none group"
                  :title="menuUsuarioAberto ? 'Fechar menu de opções' : 'Abrir menu de opções'"
                  :aria-expanded="menuUsuarioAberto"
                >
                  <div class="w-8 h-8 rounded-full bg-[#9c2337] text-white flex items-center justify-center text-xs font-semibold shrink-0 ring-2 ring-white/30 group-hover:ring-slate-300 transition">
                    {{ usuarioLogado?.nome ? usuarioLogado.nome.charAt(0).toUpperCase() : 'C' }}
                  </div>
                  <div class="text-left leading-tight hidden sm:block max-w-[130px] lg:max-w-[180px]">
                    <span class="text-xs font-bold text-white block truncate group-hover:text-rose-200 transition">{{ usuarioLogado?.nome }}</span>
                    <span class="text-[10px] text-neutral-200 block truncate">{{ usuarioLogado?.cargo }}</span>
                  </div>
                  <AppIcon name="chevron-down" size="14" class="text-neutral-200 group-hover:text-white transition hidden sm:block shrink-0" :class="menuUsuarioAberto ? 'rotate-180' : ''" />
                </button>

                <!-- Overlay para fechar ao clicar fora -->
                <div 
                  v-if="menuUsuarioAberto" 
                  class="fixed inset-0 z-40 bg-transparent" 
                  @click="menuUsuarioAberto = false"
                ></div>

                <!-- Menu Flutuante (Floating Dropdown) -->
                <div 
                  v-if="menuUsuarioAberto" 
                  class="absolute right-0 top-full mt-2 w-64 bg-white rounded-2xl shadow-xl border border-slate-200/90 py-2 z-50 animate-scale-in text-xs select-none"
                  @click.stop
                >
                  <!-- Identificação do Colaborador -->
                  <div class="px-4 py-2.5 border-b border-slate-100">
                    <div class="font-bold text-slate-900 truncate text-xs">{{ usuarioLogado?.nome }}</div>
                    <div class="text-[10px] text-slate-400 truncate mt-0.5">{{ usuarioLogado?.cargo }}</div>
                    <div class="flex items-center gap-1.5 mt-1.5">

                      <span class="text-[9px] font-bold text-emerald-800 bg-emerald-50 border border-emerald-200/80 px-1.5 py-0.2 rounded">
                        CLT Ativo
                      </span>
                    </div>
                  </div>

                  <!-- Ação de Sair da Conta -->
                  <div class="pt-1 border-t border-slate-100">
                    <button 
                      type="button"
                      @click="menuUsuarioAberto = false; fazerLogout()"
                      class="w-full px-4 py-2.5 text-left text-[#9c2337] hover:bg-rose-50 font-bold flex items-center gap-2.5 transition cursor-pointer"
                    >
                      <AppIcon name="log-out" size="15" class="text-slate-500" />
                      <span>Sair da Conta</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </header>

        <!-- CONTAINER PRINCIPAL DINÂMICO COM SCROLL DEDICADO -->
        <main class="flex-1 min-h-0 overflow-y-auto max-w-[1600px] w-full mx-auto p-4 sm:p-6 lg:p-8">
          
          <!-- MÓDULO 1: HOLERITES & SALÁRIOS (POLISHED EXECUTIVE FINTECH STORYTELLING) -->
          <div v-if="abaAtiva === 'holerites'" class="grid xl:grid-cols-[minmax(0,1fr)_300px] gap-6 items-start">
            <div class="space-y-6 min-w-0">
              
              <!-- CARD 2: HERO CARD - DEMONSTRATIVO MAIS RECENTE & ENVIO WHATSAPP -->
              <div v-if="holerites.length > 0" class="w-full bg-gradient-to-br from-[#141414] to-[#000000] text-white rounded-2xl p-6 sm:p-7 shadow-sm relative overflow-hidden space-y-5 transition-all duration-200">
                <!-- Luz ambiente suave de fundo -->
                <div class="absolute -right-20 -top-20 w-80 h-80 bg-[#9c2337]/30 rounded-full blur-3xl pointer-events-none"></div>

                <!-- Identificação do colaborador -->
                <div class="hidden sm:flex flex-wrap items-center justify-between gap-x-4 gap-y-1 relative z-10 text-xs text-white/60">
                  <span class="text-sm font-semibold text-white">{{ usuarioLogado?.nome }}</span>
                  <span class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <span>{{ usuarioLogado?.cargo }}</span>
                    <span>Matrícula <strong class="text-white/90 font-medium">{{ usuarioLogado?.matricula }}</strong></span>
                    <span>CPF <strong class="text-white/90 font-medium">{{ ocultarValores ? '***.***.***-**' : (usuarioLogado?.cpf ? maskCPF(usuarioLogado.cpf) : '---') }}</strong></span>
                  </span>
                </div>

                <!-- Último holerite: título, situação e as duas ações principais -->
                <div class="relative z-10 space-y-1.5 sm:border-t sm:border-white/10 sm:pt-4">
                  <span class="text-xs text-white/60 font-medium">Seu último holerite</span>
                  <div class="flex items-center gap-2.5 flex-wrap">
                    <h3 class="text-3xl font-semibold text-white tracking-tight leading-tight">
                      {{ holerites[0].mes_nome }} / {{ holerites[0].ano }}
                    </h3>
                    <span
                      class="text-xs font-semibold px-3 py-1 rounded-full"
                      :class="holerites[0].aceite?.confirmado ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-400/15 text-amber-300'"
                    >{{ holerites[0].aceite?.confirmado ? '✓ Recebido' : 'Novo' }}</span>
                  </div>
                  <p class="text-sm text-white/70">{{ holerites[0].tipo_folha || 'Folha Mensal' }} · pago em {{ holerites[0].data_pagamento }}</p>
                </div>

                <div class="relative z-10 grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <button
                    type="button"
                    @click="abrirHolerite(holerites[0], !holerites[0].aceite?.confirmado)"
                    class="h-14 sm:h-12 rounded-2xl sm:rounded-full bg-[#9c2337] hover:bg-[#831b2d] text-white active:scale-[0.98] font-semibold text-base sm:text-sm flex items-center justify-center gap-2 transition cursor-pointer"
                  >
                    <AppIcon name="eye" size="18" />
                    <span>Ver holerite</span>
                  </button>
                  <button
                    type="button"
                    @click="abrirModalWhatsapp(holerites[0])"
                    class="h-14 sm:h-12 rounded-2xl sm:rounded-full bg-[#1DA851] hover:bg-[#178F44] active:scale-[0.98] text-white font-semibold text-base sm:text-sm flex items-center justify-center gap-2 transition cursor-pointer"
                  >
                    <AppIcon name="whatsapp" size="18" />
                    <span>Mandar no WhatsApp</span>
                  </button>
                </div>
              </div>

              <!-- CARD 3: HISTÓRICO DE DEMONSTRATIVOS (TABELA & FEED COM MENU FLUTUANTE) -->
              <div class="w-full bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-none space-y-6">
                <!-- CABEÇALHO DO CARD (TÍTULO, SUBTÍTULO E SELETOR DE ANO) -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3.5 border-b border-slate-100 pb-4">
                  <div>
                    <h3 id="historico-demonstrativos" class="text-sm font-semibold uppercase tracking-wider text-slate-800 leading-tight">
                      Histórico de Demonstrativos
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5 font-medium">
                      {{ contadoresFolha.todos }} demonstrativos emitidos em {{ anoReferencia }}
                    </p>
                  </div>
                  
                  <!-- Seletor de Exercício Fiscal (Pill Switcher Elegante) -->
                  <div class="flex items-center gap-1.5 bg-slate-100/90 p-1 rounded-full border border-slate-200 self-start sm:self-auto shadow-2xs">
                    <span class="text-[10px] uppercase font-semibold text-slate-400 pl-2.5 hidden sm:inline">Ano:</span>
                    <button 
                      v-for="ano in anosDisponiveis" 
                      :key="ano"
                      type="button"
                      @click="mudarAnoReferencia(ano)"
                      :disabled="carregandoHolerites"
                      class="px-4 py-1.5 rounded-full text-xs font-bold transition-all duration-150 cursor-pointer active:scale-95"
                      :class="anoReferencia === ano ? 'bg-[#9c2337] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60'"
                    >
                      {{ ano }}
                    </button>
                  </div>
                </div>

                <!-- LINHA DE ABAS SEGMENTADAS NATIVAS & BUSCA -->
                <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 pt-1">
                  <!-- Abas de Categoria em Formato de Pílulas -->
                  <div class="flex items-center gap-2 overflow-x-auto pb-1 lg:pb-0 scrollbar-none">
                    <button 
                      type="button"
                      @click="filtroFolha = 'todos'; filtroStatusCiencia = 'todos'; paginaAtual = 1"
                      class="h-9 px-4 rounded-full text-xs font-bold transition-all duration-150 inline-flex items-center gap-2 cursor-pointer whitespace-nowrap border shrink-0 active:scale-95"
                      :class="filtroFolha === 'todos' && filtroStatusCiencia === 'todos' ? 'bg-[#9c2337] text-white border-[#9c2337] shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
                    >
                      <span>Todos</span>
                      <span class="text-[10px] px-2 py-0.5 rounded-full font-bold" :class="filtroFolha === 'todos' && filtroStatusCiencia === 'todos' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'">
                        {{ contadoresFolha.todos }}
                      </span>
                    </button>

                    <button 
                      type="button"
                      @click="filtroFolha = 'mensal'; filtroStatusCiencia = 'todos'; paginaAtual = 1"
                      class="h-9 px-4 rounded-full text-xs font-bold transition-all duration-150 inline-flex items-center gap-2 cursor-pointer whitespace-nowrap border shrink-0 active:scale-95"
                      :class="filtroFolha === 'mensal' ? 'bg-[#9c2337] text-white border-[#9c2337] shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
                    >
                      <span>Folha Mensal</span>
                      <span class="text-[10px] px-2 py-0.5 rounded-full font-bold" :class="filtroFolha === 'mensal' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'">
                        {{ contadoresFolha.mensal }}
                      </span>
                    </button>

                    <button 
                      type="button"
                      @click="filtroFolha = 'adiantamento'; filtroStatusCiencia = 'todos'; paginaAtual = 1"
                      class="h-9 px-4 rounded-full text-xs font-bold transition-all duration-150 inline-flex items-center gap-2 cursor-pointer whitespace-nowrap border shrink-0 active:scale-95"
                      :class="filtroFolha === 'adiantamento' ? 'bg-[#9c2337] text-white border-[#9c2337] shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
                    >
                      <span>Adiantamento</span>
                      <span class="text-[10px] px-2 py-0.5 rounded-full font-bold" :class="filtroFolha === 'adiantamento' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'">
                        {{ contadoresFolha.adiantamento }}
                      </span>
                    </button>

                    <button 
                      type="button"
                      @click="filtroFolha = 'ferias'; filtroStatusCiencia = 'todos'; paginaAtual = 1"
                      class="h-9 px-4 rounded-full text-xs font-bold transition-all duration-150 inline-flex items-center gap-2 cursor-pointer whitespace-nowrap border shrink-0 active:scale-95"
                      :class="filtroFolha === 'ferias' ? 'bg-[#9c2337] text-white border-[#9c2337] shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
                    >
                      <AppIcon name="calendar" size="13" />
                      <span>Férias</span>
                      <span class="text-[10px] px-2 py-0.5 rounded-full font-bold" :class="filtroFolha === 'ferias' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'">
                        {{ contadoresFolha.ferias }}
                      </span>
                    </button>

                    <button 
                      type="button"
                      @click="filtroStatusCiencia = (filtroStatusCiencia === 'pendente' ? 'todos' : 'pendente'); paginaAtual = 1"
                      class="h-9 px-4 rounded-full text-xs font-bold transition-all duration-150 inline-flex items-center gap-1.5 cursor-pointer whitespace-nowrap border shrink-0 active:scale-95"
                      :class="filtroStatusCiencia === 'pendente' ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                    >
                      <AppIcon name="alert-triangle" size="12" />
                      <span>Pendentes</span>
                      <span class="text-[10px] px-2 py-0.5 rounded-full font-bold" :class="filtroStatusCiencia === 'pendente' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'">
                        {{ contadoresStatus.pendente }}
                      </span>
                    </button>
                  </div>

                  <!-- Controles da Direita (Busca e Limpar) -->
                  <div class="flex items-center gap-2">
                    <!-- Campo de Busca Textual -->
                    <div class="relative flex-1 sm:w-64 h-9">
                      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <AppIcon name="search" size="14" />
                      </div>
                      <input 
                        v-model="buscaHolerite"
                        @input="paginaAtual = 1"
                        type="text"
                        placeholder="Buscar demonstrativo..."
                        class="w-full h-9 pl-9 pr-8 text-xs bg-slate-100/80 hover:bg-slate-100 border border-slate-200/60 focus:border-[#9c2337]/40 rounded-full text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#9c2337]/20 focus:bg-white transition-all shadow-2xs"
                      />
                      <button 
                        v-if="buscaHolerite"
                        type="button"
                        @click="buscaHolerite = ''; paginaAtual = 1"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer"
                      >
                        <AppIcon name="x" size="13" />
                      </button>
                    </div>

                    <!-- Botão Limpar Filtros -->
                    <button 
                      v-if="temFiltrosAtivos"
                      type="button"
                      @click="limparFiltrosHolerite"
                      title="Limpar todos os filtros"
                      class="w-9 h-9 rounded-full bg-rose-50 text-[#9c2337] hover:bg-rose-100 border border-rose-200 transition-all inline-flex items-center justify-center cursor-pointer shrink-0 active:scale-95 shadow-2xs"
                    >
                      <AppIcon name="refresh-cw" size="13" />
                    </button>
                  </div>
                </div>

                <!-- CARREGANDO SPINNER -->
                <div v-if="carregandoHolerites" class="py-12 text-center text-slate-400 space-y-2">
                  <div class="w-6 h-6 border-2 border-[#9c2337] border-t-transparent rounded-full animate-spin mx-auto"></div>
                  <p class="text-xs font-medium">Carregando dados da folha {{ anoReferencia }}...</p>
                </div>

                <!-- CELULAR: um cartão por holerite, com botões grandes e diretos -->
                <div v-else-if="holeritesPaginados.length > 0" class="md:hidden space-y-3">
                  <div
                    v-for="h in holeritesPaginados"
                    :key="h.id"
                    class="bg-white rounded-2xl p-4 border border-slate-200 space-y-3"
                  >
                    <button type="button" @click="abrirHolerite(h)" class="w-full flex items-center gap-3 text-left cursor-pointer">
                      <div class="w-12 h-12 rounded-xl bg-slate-100 flex flex-col items-center justify-center shrink-0">
                        <span class="text-sm font-semibold uppercase text-[#9c2337] leading-none">{{ h.mes_nome.slice(0, 3) }}</span>
                        <span class="text-[11px] font-semibold text-slate-900 leading-tight mt-0.5">{{ h.ano }}</span>
                      </div>
                      <div class="min-w-0 flex-1">
                        <h4 class="font-semibold text-slate-900 text-base leading-tight">{{ h.mes_nome }} de {{ h.ano }}</h4>
                        <span class="text-xs text-slate-500 block mt-0.5">{{ h.tipo_folha || 'Folha Mensal' }} · pago em {{ h.data_pagamento }}</span>
                      </div>
                      <span
                        class="shrink-0 text-[11px] font-semibold px-2.5 py-1 rounded-full"
                        :class="h.aceite?.confirmado ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                      >{{ h.aceite?.confirmado ? '✓ Recebido' : 'Novo' }}</span>
                    </button>

                    <div class="grid grid-cols-2 gap-2">
                      <button
                        type="button"
                        @click="abrirHolerite(h)"
                        class="h-12 rounded-xl bg-[#141414] active:bg-black text-white font-semibold text-sm inline-flex items-center justify-center gap-2 cursor-pointer active:scale-[0.98] transition"
                      >
                        <AppIcon name="eye" size="17" />
                        Ver holerite
                      </button>
                      <button
                        type="button"
                        @click="abrirModalWhatsapp(h)"
                        class="h-12 rounded-xl bg-[#1DA851] active:bg-[#178F44] text-white font-semibold text-sm inline-flex items-center justify-center gap-2 cursor-pointer active:scale-[0.98] transition"
                      >
                        <AppIcon name="whatsapp" size="17" />
                        WhatsApp
                      </button>
                    </div>
                  </div>
                </div>

                <!-- TABELA DE DEMONSTRATIVOS (ESTRUTURA LIMPA & MENU FLUTUANTE CONTEXTUAL) -->
                <div v-if="holeritesPaginados.length > 0" class="hidden md:block overflow-visible rounded-2xl border border-slate-200 bg-white shadow-2xs">
                  <table class="w-full text-left border-collapse min-w-[620px]">
                    <thead class="bg-slate-50/80 border-b border-slate-200">
                      <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="py-3.5 px-5">DEMONSTRATIVO / COMPETÊNCIA</th>
                        <th class="py-3.5 px-5">DATA PAGTO</th>
                        <th class="py-3.5 px-5">SITUAÇÃO</th>
                        <th class="py-3.5 px-5 text-right"></th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                      <tr 
                        v-for="h in holeritesPaginados" 
                        :key="h.id"
                        @click="abrirHolerite(h)"
                        class="transition-colors duration-150 group cursor-pointer"
                        :class="menuItemAbertoId === h.id ? 'relative z-30 bg-slate-50' : 'hover:bg-slate-50/70'"
                      >
                        <!-- Coluna 1: Demonstrativo & Competência -->
                        <td class="py-4 px-5">
                          <button 
                            type="button"
                            @click="abrirHolerite(h)"
                            class="font-semibold text-slate-900 group-hover:text-[#9c2337] transition-colors cursor-pointer text-left block text-sm leading-tight"
                          >
                            {{ h.mes_nome }} / {{ h.ano }}
                          </button>
                          <span class="text-[10px] font-medium text-slate-400 block mt-0.5">
                            {{ h.tipo_folha || 'Folha Mensal' }}
                          </span>
                        </td>

                        <!-- Coluna 2: DATA PAGTO -->
                        <td class="py-4 px-5 text-slate-600 font-medium">
                          {{ h.data_pagamento }}
                        </td>



                        <!-- Coluna 4: CIÊNCIA CLT (DISCRETO) -->
                        <td class="py-4 px-5">
                          <span 
                            v-if="h.aceite?.confirmado" 
                            class="h-6 px-3 rounded-full text-[11px] font-bold text-slate-600 bg-slate-100/90 border border-slate-200 inline-flex items-center gap-1.5 shadow-2xs"
                          >
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>Recebido</span>
                          </span>
                          
                          <span 
                            v-else 
                            class="h-6 px-3 rounded-full text-[11px] font-bold text-slate-500 bg-slate-100/90 border border-slate-200 inline-flex items-center gap-1.5 shadow-2xs"
                          >
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            <span>Novo</span>
                          </span>
                        </td>

                        <!-- Coluna 5: AÇÕES -->
                        <td class="py-3 px-5 text-right" @click.stop>
                          <div class="flex items-center justify-end gap-2">
                            <button
                              type="button"
                              @click="abrirHolerite(h)"
                              class="h-9 px-3.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 text-xs font-semibold inline-flex items-center gap-1.5 transition cursor-pointer"
                            >
                              <AppIcon name="eye" size="14" />
                              Ver
                            </button>
                            <button
                              type="button"
                              @click="abrirModalWhatsapp(h)"
                              class="h-9 px-3.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-[#1DA851] hover:text-white text-xs font-semibold inline-flex items-center gap-1.5 transition cursor-pointer"
                            >
                              <AppIcon name="whatsapp" size="14" />
                              WhatsApp
                            </button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- ESTADO VAZIO -->
                <div v-else class="text-center py-12 px-4 text-slate-400 space-y-2 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                  <AppIcon name="filter" size="24" class="mx-auto text-slate-300" />
                  <p class="text-xs font-medium text-slate-600">Nenhum demonstrativo encontrado para os filtros selecionados.</p>
                  <button 
                    type="button"
                    @click="limparFiltrosHolerite"
                    class="text-xs font-bold text-[#9c2337] hover:underline cursor-pointer"
                  >
                    Limpar filtros
                  </button>
                </div>

                <!-- PAGINAÇÃO -->
                <div v-if="totalPaginas > 1" class="flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                  <button
                    type="button"
                    @click="paginaAnterior"
                    :disabled="paginaAtual === 1"
                    class="h-11 px-4 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 inline-flex items-center gap-1.5 hover:bg-slate-50 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer"
                  >
                    <AppIcon name="chevron-left" size="15" />
                    Anterior
                  </button>
                  <span class="text-xs text-slate-500 text-center">Página {{ paginaAtual }} de {{ totalPaginas }}</span>
                  <button
                    type="button"
                    @click="proximaPagina"
                    :disabled="paginaAtual === totalPaginas"
                    class="h-11 px-4 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 inline-flex items-center gap-1.5 hover:bg-slate-50 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer"
                  >
                    Próxima
                    <AppIcon name="chevron-right" size="15" />
                  </button>
                </div>
              </div>
            
            </div>

            <!-- PAINEL LATERAL (estilo NewRocket): ciência do ano, atalhos e documentos recentes -->
            <aside class="hidden md:block space-y-6 xl:sticky xl:top-0">
              <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-3">
                <div class="flex items-center justify-between">
                  <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-700">Recebidos em {{ anoReferencia }}</h4>
                  <span class="text-xs font-semibold text-slate-700">{{ resumoCiencia.confirmados }} de {{ resumoCiencia.total }}</span>
                </div>
                <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                  <div class="h-full rounded-full bg-[#9c2337] transition-all duration-500" :style="{ width: resumoCiencia.percentual + '%' }"></div>
                </div>
                <p class="text-xs text-slate-500">
                  O recebimento é registrado ao abrir o demonstrativo ou enviá-lo para o WhatsApp.
                </p>
              </div>

              <div v-if="holerites.length" class="bg-white rounded-2xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-2">
                  <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-700">Documentos recentes</h4>
                  <button type="button" @click="rolarParaHistorico" class="text-xs text-slate-500 hover:text-[#9c2337] cursor-pointer">Ver todos</button>
                </div>
                <button
                  v-for="h in holerites.slice(0, 4)"
                  :key="h.id"
                  type="button"
                  @click="abrirHolerite(h)"
                  class="w-full flex items-center gap-3 py-2.5 border-b border-slate-100 last:border-b-0 text-left cursor-pointer group"
                >
                  <span class="w-7 h-7 rounded-md bg-[#9c2337] text-white text-[10px] font-semibold flex items-center justify-center shrink-0">PDF</span>
                  <span class="min-w-0 flex-1">
                    <span class="block text-sm font-medium text-slate-800 truncate group-hover:text-[#9c2337] transition">{{ h.mes_nome }} / {{ h.ano }}</span>
                    <span class="block text-xs text-slate-400 truncate">{{ h.tipo_folha || 'Folha Mensal' }}</span>
                  </span>
                  <span class="w-2 h-2 rounded-full shrink-0" :class="h.aceite?.confirmado ? 'bg-emerald-500' : 'bg-amber-500'" :title="h.aceite?.confirmado ? 'Recebido' : 'Pendente'"></span>
                </button>
              </div>
            </aside>
          </div>

          <!-- ESPELHO DE PONTO COM ASSINATURA ELETRÔNICA -->
          <PontoView v-else-if="abaAtiva === 'ponto'" :key="competenciaPontoInicial" :colaborador="usuarioLogado" :competenciaInicial="competenciaPontoInicial" />

          <!-- CADERNOS DO COLABORADOR (PDFs) -->
          <CadernosView v-else-if="abaAtiva === 'cadernos'" :key="buscaCadernoInicial" :buscaInicial="buscaCadernoInicial" :abrirArquivo="cadernoParaAbrir" @aberto="cadernoParaAbrir = ''" @progresso="progressoCadernos = $event" />

          <!-- MÓDULO 2: GESTÃO & CONSULTA DE FÉRIAS -->
          <FeriasView v-else-if="abaAtiva === 'ferias'" :ferias="ferias" />

          <!-- MÓDULO 4: GESTÃO DE EPIS (NR-06 COM ASSINATURA ELETRÔNICA) -->
          <div v-else-if="abaAtiva === 'epis'" class="space-y-6">
            <!-- Banner Superior do Módulo de SST (Sólido Corporativo) -->
            <div class="bg-[#141414] border-l-4 border-[#9c2337] text-white rounded-2xl p-6 sm:p-7 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
              <div class="space-y-1.5">
                <div class="flex items-center gap-2">
                  <span class="text-xs bg-white/10 text-white font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider border border-white/10">
                    SST • NR-06
                  </span>
                  <span class="text-xs text-rose-200">Portaria MTP nº 672/2021</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-semibold tracking-tight">Ficha Eletrônica de EPIs</h2>
                <p class="text-xs text-slate-300 max-w-xl leading-relaxed">
                  Controle digital de fornecimento, guarda e conservação de Equipamentos de Proteção Individual da Vila Boa Construções e Serviços LTDA.
                </p>
              </div>

              <!-- Resumo Rápido -->
              <div class="flex items-center gap-2 self-stretch sm:self-auto">
                <div class="flex-1 sm:flex-none bg-black/25 backdrop-blur-md px-4 py-3 rounded-2xl border border-white/10 text-center">
                  <span class="text-[10px] text-rose-200 uppercase font-bold block">Pendentes</span>
                  <span class="text-xl font-semibold" :class="epis.total_pendentes > 0 ? 'text-amber-300' : 'text-white'">
                    {{ epis.total_pendentes }}
                  </span>
                </div>
                <div class="flex-1 sm:flex-none bg-black/25 backdrop-blur-md px-4 py-3 rounded-2xl border border-white/10 text-center">
                  <span class="text-[10px] text-rose-200 uppercase font-bold block">Assinadas</span>
                  <span class="text-xl font-semibold text-emerald-300">
                    {{ epis.total_assinados }}
                  </span>
                </div>
              </div>
            </div>

            <!-- SEÇÃO 1: ENTREGAS PENDENTES DE ASSINATURA -->
            <div v-if="epis.pendentes && epis.pendentes.length > 0" class="space-y-3">
              <div class="flex items-center justify-between px-1">
                <h3 class="text-xs sm:text-sm font-semibold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                  <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping"></span>
                  <span>Aguardando Sua Assinatura Digital</span>
                </h3>
                <span class="text-xs font-bold text-amber-800 bg-amber-100 px-2.5 py-0.5 rounded-full">
                  Obrigatório (NR-06)
                </span>
              </div>

              <div 
                v-for="entrega in epis.pendentes" 
                :key="entrega.id"
                class="bg-white rounded-2xl p-5 sm:p-6 border-2 border-amber-300 shadow-md space-y-4"
              >
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-slate-100 gap-2">
                  <div>
                    <div class="flex items-center gap-2">
                      <span class="text-xs font-mono font-bold text-slate-400">ID: {{ entrega.id }}</span>
                      <span class="text-xs bg-amber-100 text-amber-900 font-bold px-2 py-0.5 rounded-md">
                        Pendente de Assinatura
                      </span>
                    </div>
                    <h4 class="text-sm font-bold text-slate-900 mt-1">{{ entrega.motivo }}</h4>
                    <p class="text-xs text-slate-500">Canteiro: {{ entrega.canteiro_obra }}</p>
                  </div>

                  <div class="text-left sm:text-right text-xs">
                    <span class="text-slate-400 block text-[10px]">DATA DE FORNECIMENTO:</span>
                    <strong class="text-slate-800 text-sm">{{ entrega.data_entrega_formatada }}</strong>
                    <span class="text-slate-500 block text-[10px]">{{ entrega.tecnico_responsavel }}</span>
                  </div>
                </div>

                <div class="bg-slate-50 rounded-2xl p-3 space-y-1.5 border border-slate-200">
                  <span class="text-[10px] uppercase font-bold text-slate-500 block">Itens para conferência física no canteiro:</span>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    <div 
                      v-for="item in entrega.itens" 
                      :key="item.item" 
                      class="flex items-center justify-between bg-white p-2 rounded-xl border border-slate-200"
                    >
                      <div>
                        <span class="font-bold text-slate-800 text-xs">{{ item.descricao }}</span>
                        <span class="text-[10px] text-slate-400 block font-mono">CA {{ item.ca }} • {{ item.fabricante }}</span>
                      </div>
                      <span class="text-xs font-bold text-[#9c2337] ml-2 shrink-0">{{ item.quantidade }} {{ item.unidade }}</span>
                    </div>
                  </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between pt-1 gap-3">
                  <span class="text-[11px] text-slate-500 leading-tight">
                    Ao assinar, você confirma o recebimento dos EPIs e a capacitação para o uso no canteiro de obras.
                  </span>

                  <button 
                    type="button" 
                    @click="abrirAssinaturaEpi(entrega)"
                    class="w-full sm:w-auto py-3.5 px-6 rounded-2xl bg-[#9c2337] hover:bg-[#831b2d] active:scale-[0.98] text-white font-bold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-lg shadow-[#9c2337]/25 transition cursor-pointer"
                  >
                    <AppIcon name="pen-tool" size="14" />
                    <span>Assinar Termo de Recebimento</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- SEÇÃO 2: HISTÓRICO DE FICHAS ASSINADAS -->
            <div class="bg-white rounded-2xl p-5 sm:p-7 border border-slate-200 shadow-sm space-y-4">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                  <h3 class="text-sm sm:text-base font-bold text-slate-900">Histórico de Fichas Assinadas</h3>
                  <p class="text-xs text-slate-400">Comprovantes com autenticidade eletrônica válida</p>
                </div>
                <span class="text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full">
                  {{ epis.total_assinados }} Ficha(s) Válida(s)
                </span>
              </div>

              <div v-if="epis.assinados && epis.assinados.length > 0" class="divide-y divide-slate-100">
                <div 
                  v-for="entrega in epis.assinados" 
                  :key="entrega.id"
                  class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50 p-3 rounded-2xl transition"
                >
                  <div class="flex items-start space-x-3.5">
                    <div class="w-11 h-11 rounded-2xl bg-slate-100 text-slate-600 border border-slate-200 flex items-center justify-center font-bold text-lg shrink-0">
                      <AppIcon name="check" size="18" />
                    </div>
                    <div>
                      <div class="flex items-center gap-2">
                        <span class="text-xs font-mono font-bold text-slate-400">ID: {{ entrega.id }}</span>
                        <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded">
                          Assinatura Eletrônica Válida
                        </span>
                      </div>
                      <h4 class="text-xs sm:text-sm font-bold text-slate-900 mt-0.5">{{ entrega.motivo }}</h4>
                      <p class="text-[11px] text-slate-400">
                        {{ entrega.itens.length }} item(ns) entregue(s) • Canteiro: {{ entrega.canteiro_obra }}
                      </p>
                      <p class="text-[10px] font-mono text-slate-400 mt-1">
                        Assinado em: {{ entrega.assinatura?.data_hora }} • Hash: {{ entrega.assinatura?.hash_autenticidade?.slice(0, 16) }}...
                      </p>
                    </div>
                  </div>

                  <div class="flex items-center sm:self-center">
                    <button 
                      type="button" 
                      @click="abrirReciboEpi(entrega)"
                      class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-[#9c2337] hover:text-white text-slate-700 font-bold text-xs flex items-center justify-center gap-2 transition cursor-pointer"
                    >
                      <AppIcon name="document" size="14" />
                      <span>Visualizar Ficha / PDF</span>
                    </button>
                  </div>
                </div>
              </div>

              <div v-else class="text-center py-8 text-xs text-slate-400">
                Nenhuma ficha anterior registrada.
              </div>
            </div>
          </div>

          <!-- MÓDULO 5: CHAMADOS & AUTOATENDIMENTO AO RH -->
          <ChamadosView 
            v-else-if="abaAtiva === 'chamados'" 
            :chamados="chamados"
            @chamado-criado="chamados.unshift($event)"
          />

          <!-- MÓDULO 6: MEUS DADOS CADASTRAIS (ESTILO PERFIL FINTECH / IOS SETTINGS) -->
          <div v-else-if="abaAtiva === 'perfil'" class="max-w-2xl mx-auto space-y-4 pb-12 sm:pb-6 animate-slide-up">
            
            <!-- 1. Card de Identidade do Colaborador (Estilo Perfil VIP Fintech) -->
            <div class="bg-white rounded-2xl p-5 sm:p-7 border border-slate-200/90 shadow-2xs">
              <div class="flex items-center space-x-4">
                <!-- Avatar Circular com Anel Suave e Status Online -->
                <div class="relative shrink-0">
                  <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-[#9c2337] text-white flex items-center justify-center font-semibold text-xl sm:text-2xl shadow-md ring-4 ring-rose-50">
                    {{ usuarioLogado?.nome ? usuarioLogado.nome.slice(0, 2).toUpperCase() : 'VB' }}
                  </div>
                  <span class="w-4 h-4 rounded-full bg-emerald-500 border-2 border-white absolute bottom-0 right-0 shadow-[0_0_8px_rgba(16,185,129,0.8)]"></span>
                </div>

                <!-- Nome, Cargo e Badges em Alinhamento Perfeito -->
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-800 bg-emerald-50 border border-emerald-200/80 px-2.5 py-0.5 rounded-full inline-flex items-center gap-1">
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                      <span>CLT Ativo</span>
                    </span>
                    <span class="text-[10px] font-mono font-bold text-slate-600 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-full">
                      #{{ usuarioLogado?.matricula }}
                    </span>
                  </div>

                  <h3 class="text-lg sm:text-2xl font-semibold text-slate-900 tracking-tight leading-tight mt-1 truncate">
                    {{ usuarioLogado?.nome }}
                  </h3>
                  <p class="text-xs text-slate-500 font-medium truncate mt-0.5">
                    {{ usuarioLogado?.cargo }} • {{ usuarioLogado?.departamento }}
                  </p>
                </div>
              </div>
            </div>

            <!-- 2. Ficha Funcional em Lista Agrupada Nativa (Grouped Inset Rows) -->
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
              <div class="px-5 py-3.5 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                  Ficha Funcional & Contrato CLT
                </span>
                <span class="text-[10px] text-slate-400 font-medium">Dados Oficiais</span>
              </div>

              <div class="divide-y divide-slate-100 text-xs">
                <!-- Linha 1: Matrícula -->
                <div class="p-4 flex items-center justify-between hover:bg-slate-50/60 transition">
                  <div class="flex items-center space-x-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0 border border-slate-200/60">
                      <AppIcon name="document" size="16" />
                    </div>
                    <div>
                      <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Matrícula Funcional</span>
                      <span class="font-bold text-slate-900 text-sm font-mono">{{ usuarioLogado?.matricula }}</span>
                    </div>
                  </div>
                  <span class="text-[10px] bg-slate-100 text-slate-600 border border-slate-200 px-2.5 py-0.5 rounded-full font-bold">Oficial</span>
                </div>

                <!-- Linha 2: CPF com Máscara e Botão de Visibilidade LGPD -->
                <div class="p-4 flex items-center justify-between hover:bg-slate-50/60 transition">
                  <div class="flex items-center space-x-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0 border border-slate-200/60">
                      <AppIcon name="lock" size="16" />
                    </div>
                    <div>
                      <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">CPF do Colaborador</span>
                      <div class="flex items-center gap-2 mt-0.5">
                        <span class="font-bold text-slate-900 text-sm font-mono">
                          {{ cpfVisivel ? usuarioLogado?.cpf : mascararCpf(usuarioLogado?.cpf) }}
                        </span>
                        <button 
                          type="button" 
                          @click="cpfVisivel = !cpfVisivel" 
                          class="text-slate-400 hover:text-slate-700 transition p-1 cursor-pointer rounded-md hover:bg-slate-100" 
                          :title="cpfVisivel ? 'Ocultar CPF (Privacidade)' : 'Visualizar CPF completo'"
                        >
                          <AppIcon :name="cpfVisivel ? 'eye-off' : 'eye'" size="14" />
                        </button>
                      </div>
                    </div>
                  </div>
                  <button 
                    type="button" 
                    @click="exibirModalLgpd = true"
                    class="text-[10px] bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 px-2.5 py-1 rounded-full font-bold cursor-pointer transition flex items-center gap-1 shadow-2xs"
                    title="Clique para abrir as informações de proteção LGPD"
                  >
                    <AppIcon name="shield-check" size="11" class="text-slate-500" />
                    <span>LGPD Ativa</span>
                  </button>
                </div>

                <!-- Linha 3: Cargo Registrado -->
                <div class="p-4 flex items-center justify-between hover:bg-slate-50/60 transition">
                  <div class="flex items-center space-x-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0 border border-slate-200/60">
                      <AppIcon name="user" size="16" />
                    </div>
                    <div class="min-w-0">
                      <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Cargo Oficial</span>
                      <span class="font-bold text-slate-900 text-sm block truncate">{{ usuarioLogado?.cargo }}</span>
                    </div>
                  </div>
                </div>

                <!-- Linha 4: Departamento / Setor -->
                <div class="p-4 flex items-center justify-between hover:bg-slate-50/60 transition">
                  <div class="flex items-center space-x-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0 border border-slate-200/60">
                      <AppIcon name="filter" size="16" />
                    </div>
                    <div class="min-w-0">
                      <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Departamento / Setor</span>
                      <span class="font-bold text-slate-900 text-sm block truncate">{{ usuarioLogado?.departamento }}</span>
                    </div>
                  </div>
                </div>

                <!-- Linha 5: Empregadora -->
                <div class="p-4 flex items-center justify-between hover:bg-slate-50/60 transition bg-slate-50/30">
                  <div class="flex items-center space-x-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0 border border-slate-200 font-semibold text-xs">
                      VB
                    </div>
                    <div class="min-w-0">
                      <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Empregadora Contratante</span>
                      <span class="font-bold text-slate-900 text-xs block truncate">Vila Boa Construções e Serviços LTDA</span>
                      <span class="text-[10px] text-slate-400 block font-mono">CNPJ: 02.785.261/0001-90</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 3. Contato & Compartilhamento de Ficha -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-2xs space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                  Contato & Comunicação
                </span>
                <span class="text-[10px] bg-emerald-50 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded-full font-bold inline-flex items-center gap-1">
                  <AppIcon name="check" size="10" />
                  <span>WhatsApp Ativo</span>
                </span>
              </div>

              <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="space-y-0.5">
                  <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Número de Celular Cadastrado</span>
                  <div class="text-xl sm:text-2xl font-semibold font-mono text-slate-900 tracking-tight">
                    {{ usuarioLogado?.telefone_atual }}
                  </div>
                  <p class="text-[10px] text-slate-400">
                    Confirmado em: {{ formatarDataHora(usuarioLogado?.telefone_confirmado_em) }}
                  </p>
                </div>

                <button 
                  type="button"
                  @click="compartilharMeusDadosWhatsapp"
                  class="h-11 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 active:scale-95 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-xs transition cursor-pointer shrink-0"
                  title="Compartilhar meus dados cadastrais via WhatsApp"
                >
                  <AppIcon name="share" size="14" />
                  <span>Compartilhar Meus Dados</span>
                </button>
              </div>
            </div>

            <!-- 4. Ação de Logout em Destaque no Mobile e Desktop -->
            <div class="pt-2 space-y-3">
              <button 
                type="button"
                @click="fazerLogout"
                class="w-full h-12 px-5 rounded-xl bg-rose-50 hover:bg-rose-100 active:scale-[0.98] border border-rose-200/80 text-[#9c2337] font-bold text-xs sm:text-sm flex items-center justify-center gap-2 transition cursor-pointer shadow-2xs"
                title="Encerrar sessão com segurança"
              >
                <AppIcon name="log-out" size="16" />
                <span>Sair da Minha Conta</span>
              </button>

              <div class="text-center text-[10px] text-slate-400 space-y-0.5">
                <p>Portal do Colaborador · Vila Boa Construções e Serviços LTDA</p>
                <p class="font-mono text-[9px] text-slate-400 flex items-center justify-center gap-1.5 flex-wrap">
                  <span>Versão 2.6 •</span>
                  <button type="button" @click="exibirModalLgpd = true" class="underline hover:text-slate-600 transition cursor-pointer font-sans">
                    Protegido pela LGPD (Lei 13.709/18)
                  </button>
                  <span>• Art. 464 CLT</span>
                </p>
              </div>
            </div>

          </div>
        </main>

        <!-- FOOTER / STATUS BAR FIXA NO DESKTOP (ESTILO APP NATIVO SHELL) -->
        <footer class="hidden md:flex h-9 shrink-0 bg-white border-t border-slate-200 px-6 items-center justify-between text-[11px] text-slate-500 select-none z-20">
          <span class="font-medium text-slate-600">Portal do Colaborador · Vila Boa Construções e Serviços</span>
          <span class="inline-flex items-center gap-1.5">
            <AppIcon name="shield-check" size="12" class="text-slate-500" />
            <span>Conformidade eSocial S-1210 · Art. 464 CLT</span>
          </span>
        </footer>

        <!-- BOTTOM NAVIGATION BAR FIXA EXCLUSIVA PARA CELULARES (PINNED FOOTER ESTILO NATIVO SUPERAPP) -->
        <nav class="md:hidden h-16 shrink-0 bg-white/95 backdrop-blur-md border-t border-slate-200 px-4 flex items-center justify-around z-30 safe-bottom shadow-lg select-none">
          <!-- Aba 1: Holerites -->
          <button 
            type="button"
            @click="navegarParaAba('holerites')" 
            class="flex flex-col items-center justify-center flex-1 py-1 transition-all active:scale-95 cursor-pointer relative"
            :class="abaAtiva === 'holerites' ? 'text-[#9c2337]' : 'text-slate-500 hover:text-slate-700'"
          >
            <div class="relative">
              <AppIcon name="document" size="20" />
              <span v-if="abaAtiva === 'holerites'" class="w-1.5 h-1.5 rounded-full bg-[#9c2337] absolute -bottom-1 left-1/2 -translate-x-1/2"></span>
            </div>
            <span class="text-[10px] mt-1 tracking-normal" :class="abaAtiva === 'holerites' ? 'font-semibold' : 'font-normal'">Holerites</span>
          </button>

          <!-- Aba 2: Meus Dados -->
          <button
            type="button"
            @click="navegarParaAba('ponto')"
            class="flex flex-col items-center justify-center flex-1 py-1 transition-all active:scale-95 cursor-pointer relative"
            :class="abaAtiva === 'ponto' ? 'text-[#9c2337]' : 'text-slate-500 hover:text-slate-700'"
          >
            <div class="relative">
              <AppIcon name="calendar" size="20" />
              <span v-if="abaAtiva === 'ponto'" class="w-1.5 h-1.5 rounded-full bg-[#9c2337] absolute -bottom-1 left-1/2 -translate-x-1/2"></span>
            </div>
            <span class="text-[10px] mt-1 tracking-normal" :class="abaAtiva === 'ponto' ? 'font-semibold' : 'font-normal'">Ponto</span>
          </button>

          <button
            type="button"
            @click="navegarParaAba('cadernos')"
            class="flex flex-col items-center justify-center flex-1 py-1 transition-all active:scale-95 cursor-pointer relative"
            :class="abaAtiva === 'cadernos' ? 'text-[#9c2337]' : 'text-slate-500 hover:text-slate-700'"
          >
            <div class="relative">
              <AppIcon name="landmark" size="20" />
              <span v-if="abaAtiva === 'cadernos'" class="w-1.5 h-1.5 rounded-full bg-[#9c2337] absolute -bottom-1 left-1/2 -translate-x-1/2"></span>
            </div>
            <span class="text-[10px] mt-1 tracking-normal" :class="abaAtiva === 'cadernos' ? 'font-semibold' : 'font-normal'">Cadernos</span>
          </button>
        </nav>
      </div>
    </div>

    <!-- Modal de Detalhes do Holerite -->
    <!-- Modal de Envio e Confirmação de WhatsApp com Token -->
    <ConfirmarWhatsappModal 
      v-if="holeriteParaWhatsapp"
      :holerite="holeriteParaWhatsapp"
      :colaborador="usuarioLogado"
      :token="tokenAutenticado"
      @close="holeriteParaWhatsapp = null"
      @enviado="onWhatsappEnviado"
    />

    <!-- Aviso: troca de telefone depende do líder da equipe -->
    <div v-if="avisoAlteracaoTelefone" class="fixed inset-0 z-[70] bg-black/50 flex items-end sm:items-center justify-center p-0 sm:p-4">
      <div class="w-full sm:max-w-md bg-white rounded-t-2xl sm:rounded-2xl p-6 space-y-4 text-center safe-bottom">
        <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-600 flex items-center justify-center mx-auto">
          <AppIcon name="phone" size="24" />
        </div>
        <h3 class="text-lg font-semibold text-slate-900">Pedido de troca de telefone</h3>
        <p class="text-sm text-slate-600 leading-relaxed">
          Foi gerada uma tarefa de confirmação para trocar seu telefone para
          <strong class="text-slate-900">{{ avisoAlteracaoTelefone.telefone_novo }}</strong>.
          Procure o <strong class="text-slate-900">líder da sua equipe</strong> para concluir a alteração.
        </p>
        <p class="text-xs text-slate-500">Enquanto isso, continuamos usando o número atual. Protocolo {{ avisoAlteracaoTelefone.protocolo }}</p>
        <button type="button" @click="seguirDepoisDoAviso" class="w-full h-12 rounded-full bg-[#9c2337] hover:bg-[#831b2d] text-white font-semibold text-sm cursor-pointer">
          Entendi, continuar
        </button>
      </div>
    </div>

    <HoleriteModal 
      v-if="holeriteSelecionado" 
      :holerite="holeriteSelecionado" 
      :colaborador="usuarioLogado"
      :focarCiencia="focarCienciaModal"
      v-model:ocultarValores="ocultarValores"
      @close="holeriteSelecionado = null; focarCienciaModal = false"
      @ciencia-confirmada="onHoleriteCienciaConfirmada"
    />

    <!-- Modal de Assinatura Eletrônica de EPI (Biometria Touch / Mouse) -->
    <EpiSignatureModal 
      v-if="entregaParaAssinar" 
      :entrega="entregaParaAssinar"
      @close="entregaParaAssinar = null"
      @assinado="onEpiAssinado"
    />

    <!-- Modal de Visualização da Ficha de EPI Assinada (PDF / Impressão A4) -->
    <EpiReciboModal 
      v-if="reciboParaVisualizar"
      :entrega="reciboParaVisualizar"
      @close="reciboParaVisualizar = null"
    />

    <!-- Modal de Pacote de Comprovação de Renda (3 Últimos Holerites em 1 PDF) -->
    <PacoteRendaModal 
      v-if="pacoteRenda"
      :pacote="pacoteRenda"
      @close="pacoteRenda = null"
    />

    <!-- Modal de Informe de Rendimentos IRPF Oficial -->
    <InformeRendimentoModal 
      v-if="informeSelecionado"
      :informe="informeSelecionado"
      @close="informeSelecionado = null"
    />

    <!-- Modal Oficial de LGPD & Segurança -->
    <LgpdModal 
      v-if="exibirModalLgpd" 
      @close="exibirModalLgpd = false" 
    />
    <!-- TOAST NOTIFICATION DE FEEDBACK FLUTUANTE -->
    <div 
      v-if="toastMensagem" 
      class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-[#141414] text-white text-xs font-bold px-4 py-2.5 rounded-full shadow-2xl flex items-center gap-2 border border-white/10 animate-fade-in"
    >
      <AppIcon name="check" size="14" class="text-white/70" />
      <span>{{ toastMensagem }}</span>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { maskCPF, maskDate, maskPhone, formatCurrency } from './utils/masks.js';
import AppIcon from './components/AppIcon.vue';
import Sidebar from './components/Sidebar.vue';
import MobileDrawer from './components/MobileDrawer.vue';
import FeriasView from './components/FeriasView.vue';
import PontoView from './components/PontoView.vue';
import CadernosView from './components/CadernosView.vue';
import NotificacoesBotao from './components/NotificacoesBotao.vue';
import { temMenu, pode } from './utils/permissoes.js';
import { lerDestinoDaUrl } from './utils/avisosWhatsapp.js';
import { resumirProgresso } from './utils/progressoCadernos.js';
import ChamadosView from './components/ChamadosView.vue';
import HoleriteModal from './components/HoleriteModal.vue';
import ConfirmarWhatsappModal from './components/ConfirmarWhatsappModal.vue';
import PacoteRendaModal from './components/PacoteRendaModal.vue';
import InformeRendimentoModal from './components/InformeRendimentoModal.vue';
import ValidacaoPublicaView from './components/ValidacaoPublicaView.vue';
import EpiSignatureModal from './components/EpiSignatureModal.vue';
import EpiReciboModal from './components/EpiReciboModal.vue';
import LgpdModal from './components/LgpdModal.vue';
import LogoLoader from './components/LogoLoader.vue';

// Validação Pública de Autenticidade via QR Code (Livre Acesso para Bancos e Terceiros)
function checarRotaValidacao() {
  if (typeof window === 'undefined') return false;
  const hash = window.location.hash || '';
  const path = window.location.pathname || '';
  return hash.startsWith('#/validar') || hash.startsWith('#validar') || path === '/validar';
}

const ehRotaValidacao = ref(checarRotaValidacao());

// Helper para persistência de preferências de privacidade na memória local (LocalStorage)
function carregarPreferenciaPrivacidade(chave, padrao = false) {
  try {
    const salvo = localStorage.getItem(chave);
    return salvo !== null ? JSON.parse(salvo) : padrao;
  } catch (e) {
    return padrao;
  }
}

// Controles de LGPD e Privacidade (Persistidos no Dispositivo)
const exibirModalLgpd = ref(false);
const cpfVisivel = ref(carregarPreferenciaPrivacidade('vb_cpf_visivel', false));
watch(cpfVisivel, (val) => {
  try {
    localStorage.setItem('vb_cpf_visivel', JSON.stringify(val));
  } catch (e) {}
});

const mostrarMocks = ref(true);

function mascararCpf(cpf) {
  if (!cpf) return '***.***.***-**';
  const limpo = cpf.replace(/\D/g, '');
  if (limpo.length !== 11) return '***.***.***-**';
  return `${limpo.slice(0, 3)}.***.***-${limpo.slice(-2)}`;
}

// ==================== SISTEMA DE ROTAS & PERSISTÊNCIA ====================
// Módulos liberados; os demais seguem desativados.
const ROTAS_VALIDAS = ['holerites', 'ponto', 'cadernos'];

function obterRotaValida() {
  if (typeof window === 'undefined') return 'holerites';
  
  // 1. Tenta hash da URL (#/ferias ou #ferias)
  const hash = window.location.hash.replace(/^#\/?/, '').trim().toLowerCase();
  if (ROTAS_VALIDAS.includes(hash)) {
    return hash;
  }

  // 2. Tenta pathname da URL (/ferias)
  const path = window.location.pathname.replace(/^\//, '').trim().toLowerCase();
  if (ROTAS_VALIDAS.includes(path)) {
    return path;
  }

  // 3. Tenta localStorage da última navegação
  const rotaSalva = localStorage.getItem('vb_aba_ativa');
  if (rotaSalva && ROTAS_VALIDAS.includes(rotaSalva)) {
    return rotaSalva;
  }

  return 'holerites';
}

const abaAtiva = ref(obterRotaValida());

function navegarParaAba(novaAba, atualizarHistorico = true) {
  if (!ROTAS_VALIDAS.includes(novaAba)) return;
  abaAtiva.value = novaAba;
  localStorage.setItem('vb_aba_ativa', novaAba);

  // Mantém a URL do navegador perfeitamente sincronizada (#/ferias, #/ponto, etc.)
  const targetHash = `#/${novaAba}`;
  if (window.location.hash !== targetHash) {
    if (atualizarHistorico) {
      window.history.pushState({ aba: novaAba }, '', targetHash);
    } else {
      window.history.replaceState({ aba: novaAba }, '', targetHash);
    }
  }
}

function sincronizarRotaUrl() {
  ehRotaValidacao.value = checarRotaValidacao();
  if (!ehRotaValidacao.value) {
    const rotaAtual = obterRotaValida();
    if (abaAtiva.value !== rotaAtual) {
      abaAtiva.value = rotaAtual;
    }
  }
}

// Controle de fluxo
const verificandoSessao = ref(true);
const etapaAtual = ref('login'); // 'login' | 'seguranca' | 'telefone' | 'dashboard'
const carregando = ref(false);
const mensagemErro = ref('');
const ocultarValores = ref(carregarPreferenciaPrivacidade('vb_ocultar_valores', false));
watch(ocultarValores, (val) => {
  try {
    localStorage.setItem('vb_ocultar_valores', JSON.stringify(val));
  } catch (e) {}
});

// Rate Limiting Progressivo (10s -> 1min -> 10min)
const bloqueioSegundos = ref(0);
let intervaloContagem = null;

const tempoFormatadoBloqueio = computed(() => {
  const m = Math.floor(bloqueioSegundos.value / 60);
  const s = bloqueioSegundos.value % 60;
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
});

function iniciarContagemRegressiva(segundos) {
  if (intervaloContagem) {
    clearInterval(intervaloContagem);
  }
  bloqueioSegundos.value = segundos;
  intervaloContagem = setInterval(() => {
    if (bloqueioSegundos.value > 0) {
      bloqueioSegundos.value--;
    } else {
      clearInterval(intervaloContagem);
      intervaloContagem = null;
      mensagemErro.value = '';
    }
  }, 1000);
}

// Layout Desktop e Mobile
// Sidebar responsiva: recolhida por padrão no tablet (< 1024px) para ganho de área útil
const sidebarManualOverride = ref(false);
const sidebarRecolhida = ref(typeof window !== 'undefined' ? window.innerWidth < 1024 : false);

function alternarSidebar() {
  sidebarRecolhida.value = !sidebarRecolhida.value;
  sidebarManualOverride.value = true;
}

function atualizarResponsividadeTablet() {
  if (typeof window !== 'undefined' && !sidebarManualOverride.value) {
    sidebarRecolhida.value = window.innerWidth < 1024;
  }
}
const drawerMobileAberto = ref(false);
const menuItemAbertoId = ref(null);
const detalhesComposicaoAberto = ref(false);
const toastMensagem = ref('');

function exibirToast(msg) {
  toastMensagem.value = msg;
  setTimeout(() => {
    toastMensagem.value = '';
  }, 2800);
}

function alternarMenuHolerite(id) {
  menuItemAbertoId.value = (menuItemAbertoId.value === id) ? null : id;
}

// Fecha o menu flutuante do histórico ao clicar fora dele
// (os gatilhos e o próprio menu usam @click.stop)
watch(menuItemAbertoId, (aberto) => {
  if (aberto) {
    setTimeout(() => document.addEventListener('click', fecharMenuHolerite, { once: true }));
  } else {
    document.removeEventListener('click', fecharMenuHolerite);
  }
});

function fecharMenuHolerite() {
  menuItemAbertoId.value = null;
}

async function copiarChaveAutenticacao(h) {
  const chave = h.autenticacao_digital || h.id;
  if (!chave) return;
  try {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      await navigator.clipboard.writeText(chave);
    } else {
      const el = document.createElement('textarea');
      el.value = chave;
      document.body.appendChild(el);
      el.select();
      document.execCommand('copy');
      document.body.removeChild(el);
    }
    exibirToast('Código de autenticação copiado!');
    menuItemAbertoId.value = null;
  } catch (err) {
    console.error('Erro ao copiar chave:', err);
    exibirToast('Não foi possível copiar o código.');
  }
}
const menuUsuarioAberto = ref(false);

// Form login
const formLogin = ref({
  cpf: '',
  data_nascimento: ''
});

// Dados de etapas intermediárias
const dadosEtapaSeguranca = ref({});
const respostaSelecionada = ref('');
const dadosEtapaTelefone = ref({});
const editandoTelefone = ref(false);
const novoTelefoneInput = ref('');

// Dados dos Módulos do Colaborador
const usuarioLogado = ref(null);
const podeRh = (tela, acao) => pode(usuarioLogado.value, tela, acao);
// Login do Painel do RH, na mesma pasta do portal
const urlPainelRh = window.location.pathname.replace(/\/?$/, '/') + 'admin';

// Link de aviso do RH (?abrir=caderno:arquivo | ponto:mm/aaaa | holerite:id).
// Guardado na sessão para sobreviver ao login e aberto assim que o painel carrega.
const destinoLink = ref((() => {
  const daUrl = lerDestinoDaUrl();
  try {
    if (daUrl) sessionStorage.setItem('vb_destino', JSON.stringify(daUrl));
    return daUrl || JSON.parse(sessionStorage.getItem('vb_destino') || 'null');
  } catch (e) {
    return daUrl;
  }
})());
const cadernoParaAbrir = ref('');
const competenciaPontoInicial = ref('');

function irParaDestino() {
  const d = destinoLink.value;
  if (!d) return;
  destinoLink.value = null;
  try { sessionStorage.removeItem('vb_destino'); } catch (e) { /* sem armazenamento */ }
  window.history.replaceState({}, document.title, window.location.pathname + window.location.hash.split('?')[0]);

  if (d.tipo === 'caderno') {
    cadernoParaAbrir.value = d.valor;
    navegarParaAba('cadernos');
  } else if (d.tipo === 'ponto') {
    competenciaPontoInicial.value = d.valor;
    navegarParaAba('ponto');
  } else if (d.tipo === 'holerite') {
    navegarParaAba('holerites');
    const h = holerites.value.find((x) => String(x.id) === String(d.valor));
    if (h) abrirHolerite(h);
  }
}


const holerites = ref([]);
const holeriteSelecionado = ref(null);
const pacoteRenda = ref(null);
const gerandoPacoteRenda = ref(false);
const carregandoHolerites = ref(false);
const anoReferencia = ref('2026');
const anosDisponiveis = ['2026', '2025', '2024'];

// Filtros do Histórico de Demonstrativos
const buscaHolerite = ref('');
const filtroFolha = ref('todos'); // 'todos' | 'mensal' | 'adiantamento' | '13_salario' | 'plr'
const filtroStatusCiencia = ref('todos'); // 'todos' | 'confirmado' | 'pendente'
const ordenacaoHolerite = ref('recente'); // 'recente' | 'antigo' | 'maior_valor' | 'menor_valor'

const contadoresFolha = computed(() => {
  const lista = holerites.value || [];
  return {
    todos: lista.length,
    mensal: lista.filter(h => (h.tipo_folha_codigo || '').includes('mensal') || (h.tipo_folha || '').toLowerCase().includes('mensal')).length,
    adiantamento: lista.filter(h => (h.tipo_folha_codigo || '').includes('adiantamento') || (h.tipo_folha || '').toLowerCase().includes('adiantamento') || (h.tipo_folha || '').toLowerCase().includes('vale')).length,
    ferias: lista.filter(h => (h.tipo_folha_codigo || '').includes('ferias') || (h.tipo_folha || '').toLowerCase().includes('férias') || (h.tipo_folha || '').toLowerCase().includes('ferias')).length,
    treze: lista.filter(h => (h.tipo_folha_codigo || '').includes('13') || (h.tipo_folha || '').includes('13')).length,
    plr: lista.filter(h => (h.tipo_folha_codigo || '').includes('plr') || (h.tipo_folha || '').toLowerCase().includes('plr')).length,
  };
});

const contadoresStatus = computed(() => {
  const lista = holerites.value || [];
  return {
    todos: lista.length,
    confirmado: lista.filter(h => h.aceite?.confirmado).length,
    pendente: lista.filter(h => !h.aceite?.confirmado).length,
  };
});

const temFiltrosAtivos = computed(() => {
  return (
    buscaHolerite.value.trim() !== '' ||
    filtroFolha.value !== 'todos' ||
    filtroStatusCiencia.value !== 'todos' ||
    ordenacaoHolerite.value !== 'recente'
  );
});

function limparFiltrosHolerite() {
  buscaHolerite.value = '';
  filtroFolha.value = 'todos';
  filtroStatusCiencia.value = 'todos';
  ordenacaoHolerite.value = 'recente';
  paginaAtual.value = 1;
}

async function mudarAnoReferencia(ano) {
  if (anoReferencia.value === ano && holerites.value.length > 0) return;
  anoReferencia.value = ano;
  paginaAtual.value = 1;
  const token = localStorage.getItem('vb_auth_token') || '';
  if (token) {
    carregandoHolerites.value = true;
    try {
      await carregarHolerites(token);
    } finally {
      carregandoHolerites.value = false;
    }
  }
}

const holeritesFiltrados = computed(() => {
  let lista = [...(holerites.value || [])];

  // 1. Filtro por Tipo de Folha
  if (filtroFolha.value === 'mensal') {
    lista = lista.filter(h => (h.tipo_folha_codigo || '').includes('mensal') || (h.tipo_folha || '').toLowerCase().includes('mensal'));
  } else if (filtroFolha.value === 'adiantamento') {
    lista = lista.filter(h => (h.tipo_folha_codigo || '').includes('adiantamento') || (h.tipo_folha || '').toLowerCase().includes('adiantamento') || (h.tipo_folha || '').toLowerCase().includes('vale'));
  } else if (filtroFolha.value === 'ferias') {
    lista = lista.filter(h => (h.tipo_folha_codigo || '').includes('ferias') || (h.tipo_folha || '').toLowerCase().includes('férias') || (h.tipo_folha || '').toLowerCase().includes('ferias'));
  } else if (filtroFolha.value === '13_salario') {
    lista = lista.filter(h => (h.tipo_folha_codigo || '').includes('13') || (h.tipo_folha || '').includes('13'));
  } else if (filtroFolha.value === 'plr') {
    lista = lista.filter(h => (h.tipo_folha_codigo || '').includes('plr') || (h.tipo_folha || '').toLowerCase().includes('plr'));
  }

  // 2. Filtro por Status de Ciência DP (CLT Art. 464)
  if (filtroStatusCiencia.value === 'confirmado') {
    lista = lista.filter(h => h.aceite?.confirmado);
  } else if (filtroStatusCiencia.value === 'pendente') {
    lista = lista.filter(h => !h.aceite?.confirmado);
  }

  // 3. Busca Textual (mês, competência, tipo, data de pagamento ou valor)
  const termo = buscaHolerite.value.trim().toLowerCase();
  if (termo) {
    lista = lista.filter(h => {
      const mes = (h.mes_nome || '').toLowerCase();
      const comp = (h.competencia || '').toLowerCase();
      const tipo = (h.tipo_folha || '').toLowerCase();
      const dataPagto = (h.data_pagamento || '').toLowerCase();
      const valorLiquido = String(h.salario_liquido || '').replace('.', ',');
      const valorFormatado = formatCurrency(h.salario_liquido || 0).toLowerCase();
      return (
        mes.includes(termo) ||
        comp.includes(termo) ||
        tipo.includes(termo) ||
        dataPagto.includes(termo) ||
        valorLiquido.includes(termo) ||
        valorFormatado.includes(termo)
      );
    });
  }

  // 4. Ordenação
  if (ordenacaoHolerite.value === 'antigo') {
    lista.reverse();
  } else if (ordenacaoHolerite.value === 'maior_valor') {
    lista.sort((a, b) => (b.salario_liquido || 0) - (a.salario_liquido || 0));
  } else if (ordenacaoHolerite.value === 'menor_valor') {
    lista.sort((a, b) => (a.salario_liquido || 0) - (b.salario_liquido || 0));
  }

  return lista;
});

// Paginação estilo OpeRH
const itensPorPagina = ref(5);
const paginaAtual = ref(1);

const totalPaginas = computed(() => {
  const t = Math.ceil(holeritesFiltrados.value.length / itensPorPagina.value);
  return t > 0 ? t : 1;
});

const holeritesPaginados = computed(() => {
  const start = (paginaAtual.value - 1) * itensPorPagina.value;
  return holeritesFiltrados.value.slice(start, start + itensPorPagina.value);
});

function paginaAnterior() {
  if (paginaAtual.value > 1) {
    paginaAtual.value--;
  }
}

function proximaPagina() {
  if (paginaAtual.value < totalPaginas.value) {
    paginaAtual.value++;
  }
}

function getBadgeTipoFolha(h) {
  const codigo = h.tipo_folha_codigo || '';
  const tipo = (h.tipo_folha || '').toLowerCase();

  let rotulo = 'Folha Mensal';
  if (codigo === 'adiantamento' || tipo.includes('adiantamento') || tipo.includes('vale')) {
    rotulo = 'Adiantamento (Vale)';
  } else if (codigo === '13_salario' || tipo.includes('13')) {
    rotulo = tipo.includes('1ª') ? '13º (1ª Parcela)' : (tipo.includes('2ª') ? '13º (2ª Parcela)' : '13º Salário');
  } else if (codigo === 'plr' || tipo.includes('plr')) {
    rotulo = 'PLR';
  }

  return {
    classe: 'bg-slate-100 text-slate-700 border border-slate-200/90 rounded-full font-bold px-2.5 py-0.5 text-[10px]',
    rotulo
  };
}

// Seletor de Tipo de Documento e IRPF
const tipoDocumentoAtivo = ref('holerites');
const informesRendimentos = ref([]);
const informeSelecionado = ref(null);
const carregandoInforme = ref(false);

// Notificações WhatsApp
const notificacoesWhatsapp = ref([]);
const enviandoWhatsapp = ref(false);

// Módulos Corporativos
const ferias = ref(null);
const chamados = ref([]);
const epis = ref({ pendentes: [], assinados: [], total_pendentes: 0, total_assinados: 0 });
const entregaParaAssinar = ref(null);
const reciboParaVisualizar = ref(null);

// Mock Demo rápido
const colaboradoresDemo = ref([]);

const tituloAbaAtiva = computed(() => {
  switch (abaAtiva.value) {
    case 'holerites': return 'Holerites & Salários';
    case 'perfil': return 'Meus Dados Cadastrais';
    default: return 'Holerites & Salários';
  }
});

// Manipulação das máscaras
function handleCpfInput(e) {
  formLogin.value.cpf = maskCPF(e.target.value);
}

function handleDataInput(e) {
  formLogin.value.data_nascimento = maskDate(e.target.value);
}

function handleTelefoneInput(e) {
  novoTelefoneInput.value = maskPhone(e.target.value);
}

function preencherDemo(demo) {
  formLogin.value.cpf = demo.cpf;
  formLogin.value.data_nascimento = demo.data_nascimento;
  mensagemErro.value = '';
}

// 1. Passo: Fazer Login
async function fazerLogin() {
  carregando.value = true;
  mensagemErro.value = '';

  try {
    const res = await fetch('/api/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        cpf: formLogin.value.cpf,
        data_nascimento: formLogin.value.data_nascimento
      })
    });

    const data = await res.json();
    if (!res.ok) {
      mensagemErro.value = data.erro || 'Falha ao autenticar colaborador.';
      // Se estiver bloqueado mas com pergunta associada, leva para a tela de segurança com a nova pergunta
      if (data.pergunta) {
        dadosEtapaSeguranca.value = data;
        respostaSelecionada.value = '';
        etapaAtual.value = 'seguranca';
      }
      if (data.segundos_restantes) {
        iniciarContagemRegressiva(data.segundos_restantes);
      }
      return;
    }

    dadosEtapaSeguranca.value = data;
    respostaSelecionada.value = '';
    codigoToken.value = '';
    fluxoToken.value = !!data.token_sms?.ativo;
    if (data.token_sms?.ativo) {
      // 1º confirma o telefone; o código vem depois, para o número confirmado
      dadosEtapaTelefone.value = {
        phone_token: data.phone_token,
        colaborador: { nome: data.colaborador?.primeiro_nome, ...data.telefone }
      };
      editandoTelefone.value = false;
      novoTelefoneInput.value = '';
      etapaAtual.value = 'telefone';
    } else {
      etapaAtual.value = 'seguranca';
    }
  } catch (err) {
    mensagemErro.value = 'Erro de conexão com o servidor da Vila Boa Construções e Serviços.';
  } finally {
    carregando.value = false;
  }
}

// 2. Passo: Validar Resposta do Dado Pessoal Aleatório
const codigoToken = ref('');
// Com token: CPF → telefone (etapa 2) → código (etapa 3)
const fluxoToken = ref(false);
const avisoAlteracaoTelefone = ref(null);

function seguirDepoisDoAviso() {
  avisoAlteracaoTelefone.value = null;
  etapaAtual.value = 'token';
}

async function validarToken() {
  if (codigoToken.value.length !== 6 || carregando.value) return;
  carregando.value = true;
  mensagemErro.value = '';
  try {
    const res = await fetch('/api/login/token/validar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        temp_token: dadosEtapaSeguranca.value.temp_token,
        codigo: codigoToken.value
      })
    });
    const data = await res.json();
    if (!res.ok) {
      mensagemErro.value = data.erro || 'Código inválido.';
      return;
    }
    await concluirLogin(data);
  } catch (err) {
    mensagemErro.value = 'Falha ao validar o código.';
  } finally {
    carregando.value = false;
  }
}

function responderPerguntas() {
  mensagemErro.value = '';
  etapaAtual.value = 'seguranca';
}

async function validarRespostaSeguranca() {
  if (!respostaSelecionada.value || bloqueioSegundos.value > 0) return;

  carregando.value = true;
  mensagemErro.value = '';

  try {
    const res = await fetch('/api/verify-security', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        temp_token: dadosEtapaSeguranca.value.temp_token,
        resposta: respostaSelecionada.value,
        resposta_hash: dadosEtapaSeguranca.value.pergunta.resposta_hash
      })
    });

    const data = await res.json();
    if (!res.ok) {
      mensagemErro.value = data.erro || 'Resposta incorreta.';
      respostaSelecionada.value = '';

      // Atualiza imediatamente para a nova validação sorteada pelo backend
      if (data.nova_pergunta) {
        dadosEtapaSeguranca.value.pergunta = data.nova_pergunta;
      }
      if (data.temp_token) {
        dadosEtapaSeguranca.value.temp_token = data.temp_token;
      }

      if (data.segundos_restantes) {
        iniciarContagemRegressiva(data.segundos_restantes);
      }
      return;
    }

    if (intervaloContagem) {
      clearInterval(intervaloContagem);
      intervaloContagem = null;
    }
    bloqueioSegundos.value = 0;

    dadosEtapaTelefone.value = data;
    editandoTelefone.value = false;
    novoTelefoneInput.value = '';
    etapaAtual.value = 'telefone';
  } catch (err) {
    mensagemErro.value = 'Falha ao validar resposta de segurança.';
  } finally {
    carregando.value = false;
  }
}

// 3. Passo: Confirmar o Telefone Cadastrado (Objetivo Primário)
async function confirmarTelefoneMesmo() {
  const tel = dadosEtapaTelefone.value.colaborador?.telefone_atual;
  await enviarConfirmacaoTelefone(tel);
}

function iniciarEdicaoTelefone() {
  editandoTelefone.value = true;
  novoTelefoneInput.value = dadosEtapaTelefone.value.colaborador?.telefone_atual || '';
}

async function salvarNovoTelefone() {
  if (novoTelefoneInput.value.length < 14) {
    mensagemErro.value = 'Informe um telefone válido com DDD.';
    return;
  }
  await enviarConfirmacaoTelefone(novoTelefoneInput.value);
}

async function enviarConfirmacaoTelefone(telefone) {
  carregando.value = true;
  mensagemErro.value = '';

  try {
    const res = await fetch('/api/confirm-phone', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        phone_token: dadosEtapaTelefone.value.phone_token,
        telefone: telefone
      })
    });

    const data = await res.json();
    if (!res.ok) {
      mensagemErro.value = data.erro || 'Falha ao confirmar telefone.';
      return;
    }

    if (data.etapa_necessaria === 'token') {
      dadosEtapaSeguranca.value = data;
      codigoToken.value = '';
      // Pediu número novo: avisa sobre a tarefa do líder antes de seguir para o código
      if (data.alteracao_telefone) {
        avisoAlteracaoTelefone.value = data.alteracao_telefone;
        return;
      }
      etapaAtual.value = 'token';
      return;
    }

    await concluirLogin(data);
  } catch (err) {
    mensagemErro.value = 'Erro ao registrar confirmação do telefone.';
  } finally {
    carregando.value = false;
  }
}

// Login concluído: guarda o acesso, carrega os módulos e abre o painel
async function concluirLogin(data) {
  localStorage.setItem('vb_auth_token', data.auth_token);
  usuarioLogado.value = data.colaborador;
  await carregarTodosModulos(data.auth_token);
  const destino = obterRotaValida();
  navegarParaAba(destino, false);
  etapaAtual.value = 'dashboard';
  irParaDestino();
}

// Carregamento dos módulos integrados
async function carregarTodosModulos(token) {
  await Promise.all([
    carregarHolerites(token),
    carregarInformesRendimentos(token),
    carregarNotificacoesWhatsapp(token),
    carregarEpis(token),
    carregarFerias(token),
    carregarChamados(token),
    carregarProgressoCadernos(token),
    carregarPontosPendentes(token)
  ]);
}

// Progresso de leitura dos cadernos exibido no menu lateral
const progressoCadernos = ref(null);
const cadernosPendentes = ref([]);
async function carregarProgressoCadernos(token) {
  try {
    const res = await fetch('/api/cadernos', { headers: { 'Authorization': `Bearer ${token}` } });
    const data = await res.json();
    if (!res.ok) return;
    const p = resumirProgresso(data.cadernos || []);
    progressoCadernos.value = { lidos: p.lidos, total: p.total };
    cadernosPendentes.value = (data.cadernos || []).filter((c) => c.obrigatorio && !c.leitura);
  } catch (e) {
    // o menu apenas fica sem o indicador
  }
}

// Espelhos de ponto que ainda faltam assinar
const pontosPendentes = ref([]);
async function carregarPontosPendentes(token) {
  try {
    const res = await fetch('/api/ponto', { headers: { 'Authorization': `Bearer ${token}` } });
    const data = await res.json();
    if (res.ok) pontosPendentes.value = (data.competencias || []).filter((c) => !c.assinado_em);
  } catch (e) {
    // sem notificações de ponto
  }
}

// Atualiza tudo ao abrir o sino
function carregarPendencias() {
  const token = localStorage.getItem('vb_auth_token') || '';
  carregarProgressoCadernos(token);
  carregarPontosPendentes(token);
}

// Notificações do cabeçalho: holerites novos, espelhos para assinar e cadernos para ler
const notificacoes = computed(() => {
  const lista = [];
  const holeritesNovos = holerites.value.filter((h) => !h.aceite?.confirmado);
  if (holeritesNovos.length) {
    lista.push({
      titulo: holeritesNovos.length === 1 ? 'Você tem 1 holerite novo' : `Você tem ${holeritesNovos.length} holerites novos`,
      descricao: holeritesNovos.length === 1 ? `${holeritesNovos[0].mes_nome} de ${holeritesNovos[0].ano}` : `O mais recente é ${holeritesNovos[0].mes_nome} de ${holeritesNovos[0].ano}`,
      icone: 'document',
      cor: 'bg-rose-50 text-[#9c2337]',
      acao: () => (holeritesNovos.length === 1 ? abrirHolerite(holeritesNovos[0]) : (navegarParaAba('holerites'), filtroStatusCiencia.value = 'pendente', paginaAtual.value = 1, setTimeout(rolarParaHistorico, 100)))
    });
  }
  pontosPendentes.value.forEach((c) => lista.push({
    titulo: 'Assine seu espelho de ponto',
    descricao: c.mes_nome,
    icone: 'pen-tool',
    cor: 'bg-amber-50 text-amber-700',
    acao: () => navegarParaAba('ponto')
  }));
  cadernosPendentes.value.forEach((c) => lista.push({
    titulo: 'Leia o caderno',
    descricao: c.titulo,
    icone: 'landmark',
    cor: 'bg-slate-100 text-slate-700',
    acao: () => { buscaCadernoInicial.value = c.titulo; navegarParaAba('cadernos'); }
  }));
  return lista;
});

async function carregarNotificacoesWhatsapp(token) {
  try {
    const res = await fetch('/api/whatsapp/notificacoes', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const data = await res.json();
    if (res.ok && data.notificacoes) {
      notificacoesWhatsapp.value = data.notificacoes;
    }
  } catch (e) {
    console.error('Falha ao carregar notificações WhatsApp:', e);
  }
}

// Compartilhamento de Documentos e Dados via WhatsApp
function compartilharHoleriteWhatsappLegado(h) {
  if (!h) return;
  const origin = typeof window !== 'undefined' ? window.location.origin : 'https://vilaboa.com.br';
  const url = `${origin}/#/validar?id=${encodeURIComponent(h.id)}&auth=${encodeURIComponent(h.autenticacao_digital || '')}`;
  const texto = encodeURIComponent(
    `*Demonstrativo de Pagamento - Vila Boa Construções*\n` +
    `Colaborador: ${usuarioLogado.value?.nome || ''} (Matrícula: ${usuarioLogado.value?.matricula || ''})\n` +
    `Competência: ${h.mes_nome}/${h.ano} (${h.tipo_folha || 'Folha Mensal'})\n` +
    `Salário Líquido: ${formatCurrency(h.salario_liquido)}\n` +
    `Data Pagto: ${h.data_pagamento}\n` +
    `Autenticação eSocial / Art. 464 CLT: ${h.autenticacao_digital || ''}\n` +
    `Consultar Autenticidade: ${url}`
  );
  window.open(`https://api.whatsapp.com/send?text=${texto}`, '_blank');
}

function compartilharMeusDadosWhatsapp() {
  const u = usuarioLogado.value;
  if (!u) return;
  const texto = encodeURIComponent(
    `*Ficha Cadastral do Colaborador - Vila Boa Construções*\n` +
    `Nome: ${u.nome}\n` +
    `CPF: ${u.cpf}\n` +
    `Matrícula: ${u.matricula}\n` +
    `Cargo: ${u.cargo}\n` +
    `Departamento: ${u.departamento}\n` +
    `Telefone / WhatsApp: ${u.telefone_atual}\n` +
    `Vínculo Ativo CLT.`
  );
  window.open(`https://api.whatsapp.com/send?text=${texto}`, '_blank');
}
async function enviarAvisoWhatsapp(holerite) {
  if (!holerite || enviandoWhatsapp.value) return;
  enviandoWhatsapp.value = true;

  try {
    const token = localStorage.getItem('vb_auth_token') || '';
    const res = await fetch('/api/whatsapp/reenviar-holerite', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        holerite_id: holerite.id,
        competencia: holerite.competencia,
        tipo_folha: holerite.tipo_folha
      })
    });

    const data = await res.json();
    if (res.ok && data.notificacao) {
      notificacoesWhatsapp.value.unshift(data.notificacao);
      if (data.notificacao.link_whatsapp) {
        window.open(data.notificacao.link_whatsapp, '_blank');
      }
    } else {
      alert(data.erro || 'Não foi possível disparar o aviso via WhatsApp.');
    }
  } catch (err) {
    alert('Erro de comunicação ao disparar notificação WhatsApp.');
  } finally {
    enviandoWhatsapp.value = false;
  }
}

async function carregarInformesRendimentos(token) {
  try {
    const res = await fetch('/api/informes-rendimentos', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const data = await res.json();
    if (res.ok && data.informes) {
      informesRendimentos.value = data.informes;
    }
  } catch (e) {
    console.error('Falha ao carregar informes de rendimentos:', e);
  }
}

async function abrirAbaIrpf() {
  return; // Módulo desativado
  tipoDocumentoAtivo.value = 'irpf';
  if (informesRendimentos.value.length === 0) {
    const token = localStorage.getItem('vb_auth_token') || '';
    if (token) {
      await carregarInformesRendimentos(token);
    }
  }
}

async function abrirInformeRendimentos(ano) {
  if (carregandoInforme.value) return;
  carregandoInforme.value = true;

  try {
    const token = localStorage.getItem('vb_auth_token') || '';
    const res = await fetch(`/api/informes-rendimentos/${ano}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const data = await res.json();
    if (res.ok && data.informe) {
      informeSelecionado.value = data.informe;
    } else {
      alert(data.erro || 'Não foi possível carregar o informe de rendimentos.');
    }
  } catch (err) {
    alert('Erro de comunicação ao carregar o informe de rendimentos.');
  } finally {
    carregandoInforme.value = false;
  }
}

async function carregarHolerites(token) {
  try {
    const res = await fetch(`/api/holerites?ano=${anoReferencia.value}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const data = await res.json();
    if (res.ok && data.holerites) {
      holerites.value = data.holerites;
    }
  } catch (e) {
    console.error('Falha ao carregar holerites:', e);
  }
}

async function carregarEpis(token) {
  try {
    const res = await fetch('/api/epis', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const data = await res.json();
    if (res.ok) {
      epis.value = data;
    }
  } catch (e) {
    console.error('Falha ao carregar EPIs:', e);
  }
}


async function carregarFerias(token) {
  try {
    const res = await fetch('/api/ferias', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const data = await res.json();
    if (res.ok && data.ferias) {
      ferias.value = data.ferias;
    }
  } catch (e) {
    console.error('Falha ao carregar férias:', e);
  }
}

async function carregarChamados(token) {
  try {
    const res = await fetch('/api/chamados', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const data = await res.json();
    if (res.ok && data.chamados) {
      chamados.value = data.chamados;
    }
  } catch (e) {
    console.error('Falha ao carregar chamados:', e);
  }
}

const holeriteParaWhatsapp = ref(null);
const tokenAutenticado = computed(() => {
  return typeof localStorage !== 'undefined' ? (localStorage.getItem('vb_auth_token') || '') : '';
});

function abrirModalWhatsapp(h) {
  holeriteParaWhatsapp.value = h;
}

function onWhatsappEnviado(dados) {
  const h = holerites.value.find(x => x.id === dados?.holeriteId);
  if (h) registrarRecebimento(h);
  toastMensagem.value = 'Demonstrativo enviado para o WhatsApp com sucesso!';
  setTimeout(() => {
    toastMensagem.value = '';
  }, 4000);
}

const resumoCiencia = computed(() => {
  const total = holerites.value.length;
  const confirmados = holerites.value.filter(h => h.aceite?.confirmado).length;
  return { total, confirmados, percentual: total ? Math.round((confirmados / total) * 100) : 0 };
});


function rolarParaHistorico() {
  document.getElementById('historico-demonstrativos')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Busca geral do portal: módulos, holerites e cadernos
const buscaGlobal = ref('');
const buscaGlobalAberta = ref(false);
const cadernosBusca = ref(null);

async function carregarCadernosBusca() {
  if (cadernosBusca.value) return;
  try {
    const res = await fetch('/api/cadernos', { headers: { 'Authorization': `Bearer ${localStorage.getItem('vb_auth_token') || ''}` } });
    const data = await res.json();
    cadernosBusca.value = res.ok ? (data.cadernos || []) : [];
  } catch (e) {
    cadernosBusca.value = [];
  }
}

const resultadosBusca = computed(() => {
  const termo = buscaGlobal.value.trim().toLowerCase();
  if (!termo) return [];
  const bate = (...textos) => textos.some(t => (t || '').toString().toLowerCase().includes(termo));

  const modulos = [
    { titulo: 'Holerites & Salários', aba: 'holerites', icone: 'document', chaves: 'holerite demonstrativo salario pagamento folha' },
    { titulo: 'Espelho de Ponto', aba: 'ponto', icone: 'calendar', chaves: 'ponto espelho marcacao jornada banco de horas assinatura' },
    { titulo: 'Cadernos', aba: 'cadernos', icone: 'landmark', chaves: 'cadernos manual programa politica pdf' }
  ]
    .filter(m => bate(m.titulo, m.chaves))
    .map(m => ({ ...m, categoria: 'Página do portal', tipo: 'aba' }));

  const holeritesEncontrados = holerites.value
    .filter(h => bate(`${h.mes_nome} ${h.ano}`, h.competencia, h.tipo_folha))
    .slice(0, 6)
    .map(h => ({ titulo: `${h.mes_nome} / ${h.ano}`, categoria: `Holerite · ${h.tipo_folha || 'Folha Mensal'}`, icone: 'document', tipo: 'holerite', holerite: h }));

  const cadernosEncontrados = (cadernosBusca.value || [])
    .filter(c => bate(c.titulo))
    .slice(0, 6)
    .map(c => ({ titulo: c.titulo, categoria: 'Caderno (PDF)', icone: 'landmark', tipo: 'caderno', caderno: c }));

  return [...modulos, ...holeritesEncontrados, ...cadernosEncontrados];
});

const buscaCadernoInicial = ref('');

function selecionarResultado(r) {
  if (r.tipo === 'aba') navegarParaAba(r.aba);
  if (r.tipo === 'holerite') abrirHolerite(r.holerite);
  if (r.tipo === 'caderno') {
    buscaCadernoInicial.value = r.caderno.titulo;
    navegarParaAba('cadernos');
  }
  buscaGlobal.value = '';
  buscaGlobalAberta.value = false;
}

function fecharBuscaGlobal() {
  setTimeout(() => { buscaGlobalAberta.value = false; }, 120);
}

const focarCienciaModal = ref(false);

function abrirHolerite(h, focarCiencia = false) {
  focarCienciaModal.value = focarCiencia;
  holeriteSelecionado.value = h;
  registrarRecebimento(h);
}

// Registra automaticamente a ciência/recebimento ao abrir ou enviar para o WhatsApp
const registrandoRecebimento = new Set();
async function registrarRecebimento(h) {
  if (!h?.id || h.aceite?.confirmado || registrandoRecebimento.has(h.id)) return;
  registrandoRecebimento.add(h.id);
  try {
    const token = localStorage.getItem('vb_auth_token') || '';
    const res = await fetch('/api/holerites/aceitar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
      body: JSON.stringify({ holerite_id: h.id })
    });
    const data = await res.json();
    if (res.ok && data.holerite) onHoleriteCienciaConfirmada(data.holerite);
  } catch (e) {
    console.error('Falha ao registrar recebimento do holerite:', e);
  } finally {
    registrandoRecebimento.delete(h.id);
  }
}

function onHoleriteCienciaConfirmada(holeriteAtualizado) {
  if (holeriteSelecionado.value?.id === holeriteAtualizado.id) {
    holeriteSelecionado.value = holeriteAtualizado;
  }
  const idx = holerites.value.findIndex(h => h.id === holeriteAtualizado.id);
  if (idx !== -1) {
    holerites.value[idx] = holeriteAtualizado;
  }
}

async function gerarPacoteRenda() {
  return; // Módulo desativado
  if (gerandoPacoteRenda.value) return;
  gerandoPacoteRenda.value = true;

  try {
    const token = localStorage.getItem('vb_auth_token') || '';
    const res = await fetch('/api/holerites/pacote-renda?meses=3', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const data = await res.json();
    if (res.ok && data.pacote) {
      pacoteRenda.value = data.pacote;
    } else {
      alert(data.erro || 'Não foi possível gerar o pacote de rendimentos no momento.');
    }
  } catch (err) {
    alert('Erro de comunicação ao gerar o pacote de comprovação de renda.');
  } finally {
    gerandoPacoteRenda.value = false;
  }
}

function abrirAssinaturaEpi(entrega) {
  entregaParaAssinar.value = entrega;
}

function abrirReciboEpi(entrega) {
  reciboParaVisualizar.value = entrega;
}

async function onEpiAssinado(entregaAtualizada) {
  entregaParaAssinar.value = null;
  const token = localStorage.getItem('vb_auth_token');
  if (token) {
    await carregarEpis(token);
  }
  reciboParaVisualizar.value = entregaAtualizada;
}

function voltarParaLogin() {
  etapaAtual.value = 'login';
  mensagemErro.value = '';
}

function fazerLogout() {
  localStorage.removeItem('vb_auth_token');
  localStorage.removeItem('vb_aba_ativa');
  window.history.replaceState(null, '', window.location.pathname);
  usuarioLogado.value = null;
  holerites.value = [];
  ferias.value = null;
  chamados.value = [];
  epis.value = { pendentes: [], assinados: [], total_pendentes: 0, total_assinados: 0 };
  entregaParaAssinar.value = null;
  reciboParaVisualizar.value = null;
  drawerMobileAberto.value = false;
  etapaAtual.value = 'login';
  formLogin.value.cpf = '';
  formLogin.value.data_nascimento = '';
  mensagemErro.value = '';
}

function formatarDataHora(dataHora) {
  if (!dataHora) return 'Hoje';
  try {
    const d = new Date(dataHora);
    return d.toLocaleString('pt-BR');
  } catch {
    return dataHora;
  }
}

// Carrega lista demo pública para os botões de 1 clique
async function carregarDemos() {
  try {
    const res = await fetch('/api/colaboradores-demo');
    const data = await res.json();
    if (data.colaboradores_para_teste) {
      colaboradoresDemo.value = data.colaboradores_para_teste;
    }
  } catch (err) {
    console.warn('Não foi possível carregar lista de demo:', err);
  }
}

// Restaura sessão existente com apresentação suave da animação do logo
async function verificarSessaoExistente() {
  if (ehRotaValidacao.value) {
    verificandoSessao.value = false;
    return;
  }

  // Intercepta token e ID de abertura automática passados na URL (enviados via WhatsApp)
  let tokenUrl = null;
  let holeriteIdParaAbrir = null;

  try {
    if (typeof window !== 'undefined') {
      const urlObj = new URL(window.location.href);
      tokenUrl = urlObj.searchParams.get('token') || urlObj.searchParams.get('auth_token');
      holeriteIdParaAbrir = urlObj.searchParams.get('abrir_holerite') || urlObj.searchParams.get('holerite_id');

      if (!tokenUrl && window.location.hash.includes('?')) {
        const hashParams = new URLSearchParams(window.location.hash.split('?')[1]);
        tokenUrl = hashParams.get('token') || hashParams.get('auth_token');
        if (!holeriteIdParaAbrir) {
          holeriteIdParaAbrir = hashParams.get('abrir_holerite') || hashParams.get('holerite_id');
        }
      }

      if (tokenUrl) {
        localStorage.setItem('vb_auth_token', tokenUrl);
      }
    }
  } catch (e) {
    console.warn('Erro ao processar parâmetros da URL:', e);
  }

  const tokenSalvo = localStorage.getItem('vb_auth_token');
  const timerMinimo = new Promise(resolve => setTimeout(resolve, tokenSalvo ? 1400 : 1200));

  if (!tokenSalvo) {
    await timerMinimo;
    verificandoSessao.value = false;
    return;
  }

  try {
    const [res] = await Promise.all([
      fetch('/api/me', {
        headers: {
          'Authorization': `Bearer ${tokenSalvo}`
        }
      }),
      timerMinimo
    ]);

    if (res.ok) {
      const data = await res.json();
      if (data.colaborador) {
        usuarioLogado.value = data.colaborador;
        await carregarTodosModulos(tokenSalvo);
        
        // Restaura exatamente a tela em que o colaborador estava antes do recarregamento
        const rotaDestino = obterRotaValida();
        navegarParaAba(rotaDestino, false);
        etapaAtual.value = 'dashboard';
        irParaDestino();

        // Se veio instrução para abrir holerite específico via link seguro do WhatsApp, abre imediatamente
        if (holeriteIdParaAbrir && holerites.value.length > 0) {
          const hAlvo = holerites.value.find(item => String(item.id) === String(holeriteIdParaAbrir)) || holerites.value[0];
          if (hAlvo) {
            abrirHolerite(hAlvo);
          }
        }

        // Limpa parâmetros da URL para discrição e segurança
        if (typeof window !== 'undefined' && (tokenUrl || holeriteIdParaAbrir)) {
          window.history.replaceState({}, document.title, window.location.pathname);
        }
      } else {
        localStorage.removeItem('vb_auth_token');
      }
    } else {
      localStorage.removeItem('vb_auth_token');
    }
  } catch (err) {
    console.warn('Falha ao restaurar sessão:', err);
  } finally {
    verificandoSessao.value = false;
  }
}

onMounted(async () => {
  if (typeof window !== 'undefined') {
    window.addEventListener('resize', atualizarResponsividadeTablet, { passive: true });
    atualizarResponsividadeTablet();
  }
  window.addEventListener('popstate', sincronizarRotaUrl);
  window.addEventListener('hashchange', sincronizarRotaUrl);
  await carregarDemos();
  await verificarSessaoExistente();
});

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('resize', atualizarResponsividadeTablet);
  }
  window.removeEventListener('popstate', sincronizarRotaUrl);
  window.removeEventListener('hashchange', sincronizarRotaUrl);
});
</script>

<style>
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-4px); }
  75% { transform: translateX(4px); }
}
.animate-shake {
  animation: shake 0.2s ease-in-out 0s 2;
}

@keyframes slideUp {
  from {
    transform: translateY(16px);
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
