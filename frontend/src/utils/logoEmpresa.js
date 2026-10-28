import { ref } from 'vue';
import { baseDoPortal } from './capas.js';

// Logo exibido no portal do colaborador: o da empresa dele, ou o da Vila Boa quando a empresa não tem logo.
export const LOGO_PADRAO = '/img/logo-vilaboa-color.png';
const CHAVE = 'vb_logo_empresa';

export function urlLogoEmpresa(nome, versao) {
  return versao ? `${baseDoPortal()}api/empresas/logo?nome=${encodeURIComponent(nome)}&v=${versao}` : null;
}

function lerSalvo() {
  try { return localStorage.getItem(CHAVE) || LOGO_PADRAO; } catch (e) { return LOGO_PADRAO; }
}

export const logoEmpresa = ref(LOGO_PADRAO);
// Usado na tela de abertura, antes de a sessão ser restaurada
export const logoSalvo = lerSalvo;

export function definirLogoEmpresa(colaborador) {
  logoEmpresa.value = (colaborador && urlLogoEmpresa(colaborador.empresa, colaborador.empresa_logo)) || LOGO_PADRAO;
  try {
    if (colaborador) localStorage.setItem(CHAVE, logoEmpresa.value);
    else localStorage.removeItem(CHAVE);
  } catch (e) { /* sem localStorage: só não lembra na próxima abertura */ }
}
