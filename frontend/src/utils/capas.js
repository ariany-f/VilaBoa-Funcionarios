// Endereço da imagem de capa de um caderno (cad.capa é a versão; sem capa = null)
export function urlCapa(cad) {
  return cad?.capa ? `/api/cadernos/capa?nome=${encodeURIComponent(cad.arquivo)}&v=${cad.capa}` : null;
}
