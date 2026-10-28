// Endereço da imagem de capa de um caderno (cad.capa é a versão; sem capa = null).
// <img src> não passa pelo ajuste de subpasta do fetch (main.js), então o caminho é montado
// do mesmo jeito: pasta atual do portal (sem o "/admin") + "api/...".
function baseDoPortal() {
  let pasta = window.location.pathname.replace(/admin\/?$/, '');
  if (!pasta.endsWith('/')) pasta += '/';
  return pasta;
}

export function urlCapa(cad) {
  return cad?.capa ? `${baseDoPortal()}api/cadernos/capa?nome=${encodeURIComponent(cad.arquivo)}&v=${cad.capa}` : null;
}
