// Sessão do Painel do RH (/admin), separada da sessão do colaborador
export const CHAVE_TOKEN_ADMIN = 'vb_admin_token';

export function tokenAdmin() {
  try {
    return localStorage.getItem(CHAVE_TOKEN_ADMIN) || '';
  } catch (e) {
    return '';
  }
}

/** A página aberta é o painel? (/admin ou /subpasta/admin) */
export function ehPainelAdmin() {
  return /\/admin\/?$/.test(window.location.pathname);
}
