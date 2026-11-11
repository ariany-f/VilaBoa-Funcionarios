// Converte o texto simples dos comunicados (**negrito**, linhas "- item", [texto](https://link)) em HTML seguro.
// Tudo é escapado ANTES de aplicar a formatação, então HTML cru nunca chega ao DOM.
const escapar = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');

function inline(linha) {
  let h = escapar(linha);
  h = h.replace(/\[([^\]\n]{1,200})\]\(((?:https?:\/\/|mailto:)[^\s)]+)\)/gi, (_, t, u) =>
    `<a href="${u}" target="_blank" rel="noopener noreferrer" class="underline text-[#9c2337] font-medium break-words">${t}</a>`);
  h = h.replace(/\*\*([^*\n]+)\*\*/g, '<strong class="font-semibold text-slate-900">$1</strong>');
  return h;
}

export function textoParaHtml(texto) {
  const linhas = String(texto || '').replace(/\r\n?/g, '\n').split('\n');
  const saida = [];
  let lista = false;
  for (const l of linhas) {
    const item = l.match(/^\s*[-•]\s+(.*)$/);
    if (item) {
      if (!lista) { saida.push('<ul class="list-disc pl-5 space-y-0.5">'); lista = true; }
      saida.push(`<li>${inline(item[1])}</li>`);
      continue;
    }
    if (lista) { saida.push('</ul>'); lista = false; }
    saida.push(l.trim() === '' ? '<div class="h-2"></div>' : `<p>${inline(l)}</p>`);
  }
  if (lista) saida.push('</ul>');
  return saida.join('');
}

export const dataBr = (d) => (d || '').slice(0, 10).split('-').reverse().join('/');
export const horaBr = (d) => (d || '').slice(11, 16);
