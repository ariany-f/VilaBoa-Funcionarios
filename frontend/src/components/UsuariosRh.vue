<template>
  <div class="space-y-6">
    <!-- Cabeçalho -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-800">Usuários do painel</h3>
        <p class="text-xs text-slate-500 mt-0.5">Quem entra no Painel do RH, o que cada um pode fazer (perfil) e quais empresas cada um enxerga.</p>
      </div>
      <button v-if="visao === 'usuarios' && podeEditar" type="button" @click="abrirUsuario(null)" class="h-10 px-5 rounded-full bg-[#9c2337] hover:bg-[#831b2d] text-white text-sm font-semibold cursor-pointer shrink-0">+ Novo usuário</button>
      <button v-else-if="visao === 'perfis' && podePerfis" type="button" @click="abrirPerfil(null)" class="h-10 px-5 rounded-full bg-[#9c2337] hover:bg-[#831b2d] text-white text-sm font-semibold cursor-pointer shrink-0">+ Novo perfil</button>
    </div>

    <!-- Usuários | Perfis -->
    <div class="flex items-center gap-1 bg-white border border-slate-200 rounded-full p-1 self-start w-fit" role="tablist">
      <button
        v-for="v in [{ id: 'usuarios', rotulo: `Usuários (${usuarios.length})` }, { id: 'perfis', rotulo: `Perfis e permissões (${perfis.length})` }]"
        :key="v.id"
        type="button"
        role="tab"
        :aria-selected="visao === v.id"
        @click="visao = v.id"
        class="h-8 px-4 rounded-full text-xs font-semibold transition cursor-pointer whitespace-nowrap"
        :class="visao === v.id ? 'bg-[#141414] text-white' : 'text-slate-600 hover:text-slate-900'"
      >{{ v.rotulo }}</button>
    </div>

    <p v-if="mensagem" class="p-3 rounded-xl border text-xs font-medium" :class="mensagemErro ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700'" role="status">{{ mensagem }}</p>

    <p v-if="carregando" class="py-10 text-center text-sm text-slate-400">Carregando usuários...</p>

    <!-- ===================== USUÁRIOS ===================== -->
    <div v-else-if="visao === 'usuarios'" class="bg-white rounded-2xl border border-slate-200">
      <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center gap-2 border-b border-slate-100">
        <input v-model="busca" type="text" placeholder="Buscar nome ou e-mail..." class="h-9 w-full sm:w-64 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
        <select v-model="filtroPerfil" class="h-9 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-700 outline-none">
          <option :value="0">Todos os perfis</option>
          <option v-for="p in perfis" :key="p.id" :value="p.id">{{ p.nome }}</option>
        </select>
        <select v-model="filtroSituacao" class="h-9 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-700 outline-none">
          <option value="ativos">Ativos</option>
          <option value="desativados">Desativados</option>
          <option value="todos">Ativos e desativados</option>
        </select>
      </div>

      <ul class="divide-y divide-slate-100">
        <li v-for="u in usuariosFiltrados" :key="u.id" class="p-4 sm:px-5 flex flex-col lg:flex-row lg:items-center gap-3" :class="u.ativo ? '' : 'bg-slate-50/70'">
          <div class="flex items-center gap-3 min-w-0 lg:w-[30%]">
            <span class="w-10 h-10 rounded-full flex items-center justify-center text-xs font-semibold shrink-0" :class="u.ativo ? 'bg-[#9c2337] text-white' : 'bg-slate-200 text-slate-500'">{{ iniciais(u.nome) }}</span>
            <span class="min-w-0">
              <span class="flex items-center gap-2">
                <span class="text-sm font-semibold truncate" :class="u.ativo ? 'text-slate-900' : 'text-slate-500'">{{ u.nome }}</span>
                <span v-if="u.id === eu" class="text-[10px] font-semibold uppercase tracking-wider px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 shrink-0">Você</span>
                <span v-if="!u.ativo" class="text-[10px] font-semibold uppercase tracking-wider px-1.5 py-0.5 rounded bg-slate-200 text-slate-600 shrink-0">Desativado</span>
              </span>
              <span class="block text-xs text-slate-500 truncate">{{ u.email }}</span>
            </span>
          </div>

          <div class="flex-1 min-w-0 grid sm:grid-cols-2 gap-2 text-xs">
            <div class="min-w-0">
              <span class="block text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Perfil</span>
              <span class="flex flex-wrap gap-1">
                <span v-for="pid in u.perfis" :key="pid" class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-medium">{{ nomePerfil(pid) }}</span>
                <span v-if="!u.perfis.length" class="text-amber-700">Sem perfil (não entra no painel)</span>
              </span>
            </div>
            <div class="min-w-0">
              <span class="block text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Empresas</span>
              <span class="flex flex-wrap gap-1">
                <span v-if="u.empresas === null" class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-medium">Todas as empresas</span>
                <template v-else>
                  <span v-for="e in u.empresas" :key="e" class="px-2 py-0.5 rounded-full bg-rose-50 text-[#9c2337] font-medium">{{ e }}</span>
                  <span v-if="!u.empresas.length" class="text-amber-700">Nenhuma (não entra no painel)</span>
                </template>
              </span>
            </div>
          </div>

          <div v-if="podeEditar" class="flex items-center gap-1.5 shrink-0 lg:w-[250px] lg:justify-end">
            <template v-if="u.pode_gerenciar">
              <button type="button" @click="abrirUsuario(u)" class="h-9 px-4 rounded-full border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-100 cursor-pointer">Editar</button>
              <button type="button" @click="abrirSenha(u)" class="h-9 w-9 rounded-full text-slate-500 hover:bg-slate-100 hover:text-slate-800 flex items-center justify-center cursor-pointer" title="Redefinir senha" aria-label="Redefinir senha">
                <AppIcon name="key" size="15" />
              </button>
              <button v-if="u.ativo && u.id !== eu" type="button" @click="abrirStatus(u)" class="h-9 px-3 rounded-full text-sm font-semibold text-slate-500 hover:bg-rose-50 hover:text-rose-700 cursor-pointer">Desativar</button>
              <button v-else-if="!u.ativo" type="button" @click="abrirStatus(u)" class="h-9 px-3 rounded-full text-sm font-semibold text-emerald-700 hover:bg-emerald-50 cursor-pointer">Reativar</button>
            </template>
            <span v-else class="text-[11px] text-slate-400 max-w-[200px] text-right">Acessa empresas que você não acessa</span>
          </div>
        </li>
        <li v-if="!usuariosFiltrados.length" class="py-12 text-center text-sm text-slate-400">Nenhum usuário encontrado.</li>
      </ul>
    </div>

    <!-- ===================== PERFIS ===================== -->
    <div v-else class="space-y-4">
      <p class="text-xs text-slate-500">Cada usuário recebe um ou mais perfis. Mudar as permissões de um perfil vale na hora para todos os usuários dele.</p>
      <ul class="grid gap-4 lg:grid-cols-2">
        <li v-for="p in perfis" :key="p.id" class="bg-white rounded-2xl border border-slate-200 p-5 flex flex-col gap-4">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="text-base font-semibold text-slate-900 truncate">{{ p.nome }}</p>
              <p class="text-xs text-slate-500">{{ p.usuarios }} usuário(s) · {{ p.acoes.length }} permissão(ões)</p>
            </div>
            <div v-if="podePerfis" class="flex items-center gap-1 shrink-0">
              <button type="button" @click="abrirPerfil(p)" class="h-9 px-4 rounded-full border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-100 cursor-pointer">Editar</button>
              <button type="button" @click="abrirRemoverPerfil(p)" class="h-9 px-3 rounded-full text-sm font-semibold text-slate-500 hover:bg-rose-50 hover:text-rose-700 cursor-pointer">Remover</button>
            </div>
          </div>
          <dl class="space-y-2">
            <div v-for="t in telas" :key="t.slug" class="flex items-start gap-3 text-xs">
              <dt class="w-32 shrink-0 font-semibold text-slate-700 pt-0.5">{{ t.nome }}</dt>
              <dd class="flex flex-wrap gap-1 min-w-0">
                <template v-if="t.acoes.some((a) => p.acoes.includes(a.id))">
                  <span v-for="a in t.acoes.filter((a) => p.acoes.includes(a.id))" :key="a.id" class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">{{ a.nome }}</span>
                </template>
                <span v-else class="text-slate-400 pt-0.5">Sem acesso</span>
              </dd>
            </div>
          </dl>
        </li>
      </ul>
    </div>

    <!-- Modal: criar / editar usuário -->
    <ModalCadastro
      :aberto="modalUsuario"
      :titulo="editando ? 'Editar usuário' : 'Novo usuário'"
      :descricao="editando ? editando.email : 'A pessoa entra no painel com este e-mail e a senha definida aqui.'"
      :rotulo-salvar="editando ? 'Salvar alterações' : 'Criar usuário'"
      tamanho="lg"
      :enviando="enviando"
      :pode-salvar="podeSalvarUsuario"
      :erro="erroModal"
      :sujo="usuarioSujo"
      @fechar="modalUsuario = false"
      @salvar="salvarUsuario"
    >
      <div class="grid sm:grid-cols-2 gap-4">
        <label class="block space-y-1.5 sm:col-span-2">
          <span class="text-xs font-semibold text-slate-700">Nome completo</span>
          <input ref="campoNome" v-model="form.nome" type="text" maxlength="120" autocomplete="off" class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
        </label>
        <label class="block space-y-1.5">
          <span class="text-xs font-semibold text-slate-700">E-mail de acesso</span>
          <input v-model="form.email" type="email" autocomplete="off" placeholder="nome@empresa.com.br" class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
        </label>
        <label class="block space-y-1.5">
          <span class="text-xs font-semibold text-slate-700">CPF <span class="font-normal text-slate-400">(opcional)</span></span>
          <input :value="form.cpf" @input="form.cpf = mascaraCpf($event.target.value)" type="text" inputmode="numeric" placeholder="000.000.000-00" class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
          <span class="block text-[11px] text-slate-400">Liga o usuário ao cadastro de colaborador dele, se tiver.</span>
        </label>
      </div>

      <div v-if="!editando" class="space-y-1.5">
        <span class="text-xs font-semibold text-slate-700">Senha inicial</span>
        <div class="flex gap-2">
          <input v-model="form.senha" type="text" autocomplete="new-password" spellcheck="false" class="flex-1 min-w-0 h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm font-mono outline-none focus:bg-white focus:border-slate-400" />
          <button type="button" @click="form.senha = gerarSenha()" class="h-11 px-4 rounded-lg border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-100 cursor-pointer shrink-0">Gerar outra</button>
        </div>
        <span class="block text-[11px]" :class="form.senha.length && form.senha.length < 8 ? 'text-rose-700' : 'text-slate-400'">Pelo menos 8 caracteres. Passe a senha para a pessoa por um canal seguro.</span>
      </div>

      <fieldset class="space-y-2">
        <legend class="text-xs font-semibold text-slate-700 mb-1.5">Perfil</legend>
        <label v-for="p in perfis" :key="p.id" class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition" :class="form.perfis.includes(p.id) ? 'border-[#9c2337] bg-rose-50/40' : 'border-slate-200 hover:bg-slate-50'">
          <input type="checkbox" :value="p.id" v-model="form.perfis" class="mt-0.5 w-4 h-4 accent-[#9c2337] shrink-0" />
          <span class="min-w-0">
            <span class="block text-sm font-semibold text-slate-800">{{ p.nome }}</span>
            <span class="block text-xs text-slate-500">{{ resumoPerfil(p) }}</span>
          </span>
        </label>
      </fieldset>

      <fieldset class="space-y-2">
        <legend class="text-xs font-semibold text-slate-700 mb-1.5">Empresas que pode acessar</legend>
        <label v-if="acessaTodas" class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition" :class="form.todas ? 'border-[#9c2337] bg-rose-50/40' : 'border-slate-200 hover:bg-slate-50'">
          <input type="checkbox" v-model="form.todas" class="mt-0.5 w-4 h-4 accent-[#9c2337] shrink-0" />
          <span>
            <span class="block text-sm font-semibold text-slate-800">Todas as empresas</span>
            <span class="block text-xs text-slate-500">Inclusive as que forem cadastradas depois.</span>
          </span>
        </label>
        <div v-if="!form.todas" class="grid sm:grid-cols-2 gap-2">
          <label v-for="e in empresas" :key="e" class="flex items-center gap-3 h-11 px-3 rounded-xl border cursor-pointer transition" :class="form.empresas.includes(e) ? 'border-[#9c2337] bg-rose-50/40' : 'border-slate-200 hover:bg-slate-50'">
            <input type="checkbox" :value="e" v-model="form.empresas" class="w-4 h-4 accent-[#9c2337] shrink-0" />
            <span class="text-sm font-medium text-slate-800 truncate">{{ e }}</span>
          </label>
        </div>
        <p class="text-[11px] text-slate-400">A pessoa só vê colaboradores, cadernos, treinamentos e comunicados das empresas marcadas.</p>
      </fieldset>
    </ModalCadastro>

    <!-- Modal: redefinir senha -->
    <ModalCadastro
      :aberto="!!senhaDe"
      titulo="Redefinir senha"
      :descricao="senhaDe ? `${senhaDe.nome} · ${senhaDe.email}` : ''"
      tamanho="sm"
      :rotulo-salvar="senhaSalva ? 'Fechar' : 'Redefinir senha'"
      :enviando="enviando"
      :pode-salvar="senhaSalva || novaSenha.length >= 8"
      :erro="erroModal"
      @fechar="senhaDe = null"
      @salvar="senhaSalva ? (senhaDe = null) : salvarSenha()"
    >
      <template v-if="!senhaSalva">
        <div class="space-y-1.5">
          <span class="text-xs font-semibold text-slate-700">Nova senha</span>
          <div class="flex gap-2">
            <input v-model="novaSenha" type="text" autocomplete="new-password" spellcheck="false" class="flex-1 min-w-0 h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm font-mono outline-none focus:bg-white focus:border-slate-400" />
            <button type="button" @click="novaSenha = gerarSenha()" class="h-11 px-3 rounded-lg border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-100 cursor-pointer shrink-0">Gerar</button>
          </div>
          <span class="block text-[11px] text-slate-400">Pelo menos 8 caracteres. A senha antiga deixa de funcionar na hora.</span>
        </div>
      </template>
      <div v-else class="space-y-3">
        <p class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium">Senha redefinida. Passe a nova senha para {{ senhaDe?.nome.split(' ')[0] }} por um canal seguro.</p>
        <div class="flex gap-2">
          <code class="flex-1 min-w-0 h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm font-mono flex items-center truncate">{{ novaSenha }}</code>
          <button type="button" @click="copiar(novaSenha)" class="h-11 px-4 rounded-lg border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-100 cursor-pointer shrink-0">{{ copiado ? 'Copiada' : 'Copiar' }}</button>
        </div>
      </div>
    </ModalCadastro>

    <!-- Modal: desativar / reativar -->
    <ModalCadastro
      :aberto="!!statusDe"
      :titulo="statusDe?.ativo ? 'Desativar usuário' : 'Reativar usuário'"
      tamanho="sm"
      :rotulo-salvar="statusDe?.ativo ? 'Desativar' : 'Reativar'"
      :enviando="enviando"
      :erro="erroModal"
      @fechar="statusDe = null"
      @salvar="salvarStatus"
    >
      <p v-if="statusDe?.ativo" class="text-sm text-slate-700"><strong>{{ statusDe.nome }}</strong> não vai mais conseguir entrar no painel, e quem estiver com o painel aberto sai na próxima ação. Os registros feitos por essa pessoa continuam. Você pode reativar depois.</p>
      <p v-else-if="statusDe" class="text-sm text-slate-700"><strong>{{ statusDe.nome }}</strong> volta a entrar no painel com a mesma senha e os mesmos perfis de antes.</p>
    </ModalCadastro>

    <!-- Modal: criar / editar perfil -->
    <ModalCadastro
      :aberto="modalPerfil"
      :titulo="perfilEditando ? 'Editar perfil' : 'Novo perfil'"
      :descricao="perfilEditando ? `Vale para ${perfilEditando.usuarios} usuário(s) com este perfil.` : 'Dê um nome e marque o que quem tiver este perfil pode fazer.'"
      :rotulo-salvar="perfilEditando ? 'Salvar perfil' : 'Criar perfil'"
      tamanho="lg"
      :enviando="enviando"
      :pode-salvar="formPerfil.nome.trim() !== ''"
      :erro="erroModal"
      :sujo="perfilSujo"
      @fechar="modalPerfil = false"
      @salvar="salvarPerfil"
    >
      <label class="block space-y-1.5">
        <span class="text-xs font-semibold text-slate-700">Nome do perfil</span>
        <input v-model="formPerfil.nome" type="text" maxlength="60" placeholder="Ex.: RH - Ponto" class="w-full h-11 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-slate-400" />
      </label>
      <div class="space-y-3">
        <div v-for="t in telas" :key="t.slug" class="rounded-xl border border-slate-200 p-4">
          <div class="flex items-start justify-between gap-3 mb-2">
            <div class="min-w-0">
              <p class="text-sm font-semibold text-slate-800">{{ t.nome }}</p>
              <p v-if="t.descricao" class="text-[11px] text-slate-500">{{ t.descricao }}</p>
            </div>
            <button type="button" @click="alternarTela(t)" class="text-xs font-semibold text-[#9c2337] hover:underline cursor-pointer shrink-0">{{ t.acoes.every((a) => formPerfil.acoes.includes(a.id)) ? 'Desmarcar' : 'Marcar tudo' }}</button>
          </div>
          <div class="space-y-1.5">
            <label v-for="a in t.acoes" :key="a.id" class="flex items-center gap-2.5 text-sm text-slate-700 cursor-pointer">
              <input type="checkbox" :value="a.id" v-model="formPerfil.acoes" class="w-4 h-4 accent-[#9c2337] shrink-0" />
              {{ a.nome }}
            </label>
          </div>
        </div>
      </div>
    </ModalCadastro>

    <!-- Modal: remover perfil -->
    <ModalCadastro
      :aberto="!!perfilRemovendo"
      titulo="Remover perfil"
      tamanho="sm"
      rotulo-salvar="Remover perfil"
      :enviando="enviando"
      :pode-salvar="!!perfilRemovendo && !perfilRemovendo.usuarios"
      :erro="erroModal"
      @fechar="perfilRemovendo = null"
      @salvar="removerPerfil"
    >
      <p v-if="perfilRemovendo?.usuarios" class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-medium">{{ perfilRemovendo.usuarios }} usuário(s) usam o perfil "{{ perfilRemovendo.nome }}". Troque o perfil deles antes de remover.</p>
      <p v-else-if="perfilRemovendo" class="text-sm text-slate-700">Tem certeza que quer remover o perfil <strong>{{ perfilRemovendo.nome }}</strong>? Nenhum usuário usa este perfil.</p>
    </ModalCadastro>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import AppIcon from './AppIcon.vue';
import ModalCadastro from './ModalCadastro.vue';
import { pode } from '../utils/permissoes.js';
import { tokenAdmin } from '../utils/sessaoAdmin.js';

/**
 * Tela "Usuários" do painel do RH: usuários do /admin (criar, editar, desativar, redefinir senha),
 * perfis com as permissões de cada um e as empresas que cada usuário acessa.
 * Uso: <UsuariosRh :colaborador="usuarioLogado" />
 */
const props = defineProps({ colaborador: { type: Object, default: null } });

const podeEditar = computed(() => pode(props.colaborador, 'rh_usuarios', 'editar'));
const podePerfis = computed(() => pode(props.colaborador, 'rh_usuarios', 'perfis'));

const auth = () => ({ Authorization: `Bearer ${tokenAdmin()}` });
const visao = ref('usuarios');
const carregando = ref(true);
const usuarios = ref([]);
const perfis = ref([]);
const telas = ref([]);
const empresas = ref([]);
const acessaTodas = ref(false);
const eu = ref(0);

const mensagem = ref('');
const mensagemErro = ref(false);
let temporizador = null;
function avisar(texto, erro = false) {
  mensagem.value = texto;
  mensagemErro.value = erro;
  clearTimeout(temporizador);
  temporizador = setTimeout(() => { mensagem.value = ''; }, 7000);
}

async function carregar() {
  try {
    const res = await fetch('/api/rh/usuarios', { headers: auth() });
    const data = await res.json();
    if (!res.ok) throw new Error(data.erro);
    usuarios.value = data.usuarios;
    perfis.value = data.perfis;
    telas.value = data.telas;
    empresas.value = data.empresas;
    acessaTodas.value = data.acessa_todas;
    eu.value = data.eu;
  } catch (e) {
    avisar(e.message || 'Não foi possível carregar os usuários.', true);
  } finally {
    carregando.value = false;
  }
}
onMounted(carregar);

async function post(rota, corpo) {
  const res = await fetch(`/api/rh/${rota}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...auth() },
    body: JSON.stringify(corpo)
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.erro || 'Não foi possível concluir. Tente novamente.');
  return data;
}

// Mudou algo do próprio usuário logado: o painel recarrega permissões, menus e empresas
const avisarPainelSeFuiEu = (id) => { if (id === eu.value) window.dispatchEvent(new Event('vb-empresas-atualizadas')); };

const iniciais = (nome) => (nome || '').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase();
const nomePerfil = (id) => perfis.value.find((p) => p.id === id)?.nome || `Perfil ${id}`;
function resumoPerfil(p) {
  const comAcesso = telas.value.filter((t) => t.acoes.some((a) => p.acoes.includes(a.id))).map((t) => t.nome);
  return comAcesso.length ? comAcesso.join(', ') : 'Sem nenhuma permissão';
}

// ---------------- Lista ----------------
const busca = ref('');
const filtroPerfil = ref(0);
const filtroSituacao = ref('ativos');
const usuariosFiltrados = computed(() => {
  const termo = busca.value.trim().toLowerCase();
  return usuarios.value.filter((u) =>
    (filtroSituacao.value === 'todos' || (filtroSituacao.value === 'ativos') === u.ativo) &&
    (!filtroPerfil.value || u.perfis.includes(filtroPerfil.value)) &&
    (!termo || u.nome.toLowerCase().includes(termo) || u.email.toLowerCase().includes(termo))
  );
});

// ---------------- Senhas ----------------
// Sem letras/números que se confundem (0/O, 1/l/I), sempre com número e maiúscula
function gerarSenha() {
  const letras = 'abcdefghjkmnpqrstuvwxyz';
  const maiusculas = 'ABCDEFGHJKMNPQRSTUVWXYZ';
  const numeros = '23456789';
  const todos = letras + maiusculas + numeros;
  const aleatorio = (n) => crypto.getRandomValues(new Uint32Array(1))[0] % n;
  const senha = [maiusculas[aleatorio(maiusculas.length)], numeros[aleatorio(numeros.length)]];
  while (senha.length < 10) senha.push(todos[aleatorio(todos.length)]);
  for (let i = senha.length - 1; i > 0; i--) {
    const j = aleatorio(i + 1);
    [senha[i], senha[j]] = [senha[j], senha[i]];
  }
  return senha.join('');
}

const copiado = ref(false);
async function copiar(texto) {
  try {
    await navigator.clipboard.writeText(texto);
    copiado.value = true;
    setTimeout(() => { copiado.value = false; }, 2000);
  } catch (e) { /* o texto continua na tela para copiar à mão */ }
}

function mascaraCpf(v) {
  const d = (v || '').replace(/\D/g, '').slice(0, 11);
  return d.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
}

// ---------------- Criar / editar usuário ----------------
const modalUsuario = ref(false);
const editando = ref(null);
const form = ref({ nome: '', email: '', cpf: '', senha: '', perfis: [], todas: false, empresas: [] });
let formInicial = '';
const erroModal = ref('');
const enviando = ref(false);
const campoNome = ref(null);

function abrirUsuario(u) {
  editando.value = u;
  erroModal.value = '';
  form.value = u
    ? { nome: u.nome, email: u.email, cpf: mascaraCpf(u.cpf || ''), senha: '', perfis: [...u.perfis], todas: u.empresas === null && acessaTodas.value, empresas: u.empresas ? [...u.empresas] : (acessaTodas.value ? [] : [...empresas.value]) }
    : { nome: '', email: '', cpf: '', senha: gerarSenha(), perfis: [], todas: false, empresas: empresas.value.length === 1 ? [...empresas.value] : [] };
  formInicial = JSON.stringify(form.value);
  modalUsuario.value = true;
  nextTick(() => campoNome.value?.focus());
}

const usuarioSujo = computed(() => modalUsuario.value && JSON.stringify(form.value) !== formInicial);
const podeSalvarUsuario = computed(() => {
  const f = form.value;
  return f.nome.trim() !== '' && /\S+@\S+\.\S+/.test(f.email) && f.perfis.length > 0 &&
    (f.todas || f.empresas.length > 0) && (editando.value || f.senha.length >= 8) &&
    (!editando.value || usuarioSujo.value);
});

async function salvarUsuario() {
  if (!podeSalvarUsuario.value || enviando.value) return;
  enviando.value = true;
  erroModal.value = '';
  const f = form.value;
  try {
    const resp = await post('usuarios', {
      id: editando.value?.id,
      nome: f.nome,
      email: f.email,
      cpf: f.cpf,
      perfis: f.perfis,
      todas_empresas: f.todas,
      empresas: f.todas ? [] : f.empresas,
      ...(editando.value ? {} : { senha: f.senha })
    });
    modalUsuario.value = false;
    if (editando.value) {
      avisar(`${f.nome.split(' ')[0]} foi atualizado(a).`);
    } else {
      // A senha inicial aparece de novo para quem criou poder copiar
      senhaDe.value = { id: resp.id, nome: f.nome, email: f.email };
      novaSenha.value = f.senha;
      senhaSalva.value = true;
      avisar(`Usuário ${f.email} criado.`);
    }
    avisarPainelSeFuiEu(editando.value?.id);
    await carregar();
  } catch (e) {
    erroModal.value = e.message;
  } finally {
    enviando.value = false;
  }
}

// ---------------- Redefinir senha ----------------
const senhaDe = ref(null);
const novaSenha = ref('');
const senhaSalva = ref(false);
function abrirSenha(u) {
  erroModal.value = '';
  senhaSalva.value = false;
  novaSenha.value = gerarSenha();
  senhaDe.value = u;
}
async function salvarSenha() {
  if (enviando.value) return;
  enviando.value = true;
  erroModal.value = '';
  try {
    await post('usuarios/senha', { id: senhaDe.value.id, senha: novaSenha.value });
    senhaSalva.value = true;
  } catch (e) {
    erroModal.value = e.message;
  } finally {
    enviando.value = false;
  }
}

// ---------------- Desativar / reativar ----------------
const statusDe = ref(null);
function abrirStatus(u) {
  erroModal.value = '';
  statusDe.value = u;
}
async function salvarStatus() {
  if (enviando.value) return;
  enviando.value = true;
  erroModal.value = '';
  const u = statusDe.value;
  try {
    await post('usuarios/status', { id: u.id, ativo: !u.ativo });
    statusDe.value = null;
    avisar(u.ativo ? `${u.nome} foi desativado(a) e não entra mais no painel.` : `${u.nome} foi reativado(a).`);
    await carregar();
  } catch (e) {
    erroModal.value = e.message;
  } finally {
    enviando.value = false;
  }
}

// ---------------- Perfis ----------------
const modalPerfil = ref(false);
const perfilEditando = ref(null);
const formPerfil = ref({ nome: '', acoes: [] });
let perfilInicial = '';
const perfilSujo = computed(() => modalPerfil.value && JSON.stringify(formPerfil.value) !== perfilInicial);

function abrirPerfil(p) {
  perfilEditando.value = p;
  erroModal.value = '';
  formPerfil.value = p ? { nome: p.nome, acoes: [...p.acoes] } : { nome: '', acoes: [] };
  perfilInicial = JSON.stringify(formPerfil.value);
  modalPerfil.value = true;
}

function alternarTela(t) {
  const ids = t.acoes.map((a) => a.id);
  const todas = ids.every((id) => formPerfil.value.acoes.includes(id));
  formPerfil.value.acoes = todas
    ? formPerfil.value.acoes.filter((id) => !ids.includes(id))
    : [...new Set([...formPerfil.value.acoes, ...ids])];
}

async function salvarPerfil() {
  if (enviando.value) return;
  enviando.value = true;
  erroModal.value = '';
  try {
    await post('perfis', { id: perfilEditando.value?.id, nome: formPerfil.value.nome, acoes: formPerfil.value.acoes });
    modalPerfil.value = false;
    avisar(perfilEditando.value ? `Perfil "${formPerfil.value.nome}" atualizado.` : `Perfil "${formPerfil.value.nome}" criado. Agora é só escolher esse perfil no cadastro do usuário.`);
    // As permissões do próprio usuário podem ter mudado
    window.dispatchEvent(new Event('vb-empresas-atualizadas'));
    await carregar();
  } catch (e) {
    erroModal.value = e.message;
  } finally {
    enviando.value = false;
  }
}

const perfilRemovendo = ref(null);
function abrirRemoverPerfil(p) {
  erroModal.value = '';
  perfilRemovendo.value = p;
}
async function removerPerfil() {
  if (enviando.value || !perfilRemovendo.value) return;
  enviando.value = true;
  erroModal.value = '';
  const nome = perfilRemovendo.value.nome;
  try {
    await post('perfis/remover', { id: perfilRemovendo.value.id });
    perfilRemovendo.value = null;
    avisar(`Perfil "${nome}" removido.`);
    await carregar();
  } catch (e) {
    erroModal.value = e.message;
  } finally {
    enviando.value = false;
  }
}
</script>
