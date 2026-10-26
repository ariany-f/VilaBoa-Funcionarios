// Permissões do perfil vindas do backend (mesmo modelo do sistema Vila Boa):
// colaborador.permissoes = { tela: { acao: true } } e colaborador.menus = [{ name, url, icon }]

export function pode(colaborador, tela, acao) {
  return !!colaborador?.permissoes?.[tela]?.[acao];
}

export function temMenu(colaborador, url) {
  return (colaborador?.menus || []).some((m) => m.url === url);
}
