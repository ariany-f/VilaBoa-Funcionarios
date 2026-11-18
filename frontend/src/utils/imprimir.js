// Imprime / salva em PDF sem abrir aba nova: o documento vai para um iframe invisível,
// o navegador abre a caixa de impressão e o iframe é removido logo depois.

let atual = null;

function remover(iframe) {
  if (iframe && iframe.parentNode) iframe.parentNode.removeChild(iframe);
  if (atual === iframe) atual = null;
}

/** Imprime um documento HTML completo (string) sem sair da página. */
export function imprimirHtml(html) {
  remover(atual);
  const iframe = document.createElement('iframe');
  iframe.setAttribute('aria-hidden', 'true');
  iframe.tabIndex = -1;
  Object.assign(iframe.style, { position: 'fixed', right: '0', bottom: '0', width: '0', height: '0', border: '0', visibility: 'hidden' });
  atual = iframe;
  iframe.onload = () => {
    const w = iframe.contentWindow;
    if (!w) return remover(iframe);
    // Fica um pouco para imagens/fontes terminarem; remove ao fechar a caixa de impressão
    setTimeout(() => {
      w.addEventListener('afterprint', () => setTimeout(() => remover(iframe), 100));
      try {
        w.focus();
        w.print();
      } catch (e) {
        remover(iframe);
      }
      // Garantia caso o navegador não avise o fim da impressão
      setTimeout(() => remover(iframe), 120000);
    }, 250);
  };
  iframe.srcdoc = html;
  document.body.appendChild(iframe);
}

/** Imprime um trecho da tela (ex.: a prévia de um documento) com os estilos do portal. */
export function imprimirElemento(el, titulo = '') {
  if (!el) return;
  const estilos = [...document.querySelectorAll('link[rel="stylesheet"], style')]
    .map((n) => (n.tagName === 'LINK' ? `<link rel="stylesheet" href="${n.href}">` : n.outerHTML))
    .join('');
  const esc = (t) => String(t ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  imprimirHtml(`<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>${esc(titulo)}</title>
<base href="${esc(document.baseURI)}">${estilos}
<style>html,body{background:#fff!important;margin:0}body{padding:16px}.no-print{display:none!important}*{-webkit-print-color-adjust:exact;print-color-adjust:exact}</style>
</head><body>${el.outerHTML}</body></html>`);
}
