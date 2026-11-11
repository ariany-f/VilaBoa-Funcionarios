// Aviso de prazo (opcional) de trilhas e cadernos: devolve null quando não há prazo ou já foi concluído.
export function prazoInfo(prazo, concluido = false) {
  if (!prazo || concluido) return null;
  const [a, m, d] = prazo.split('-').map(Number);
  const hoje = new Date();
  const dias = Math.round((new Date(a, m - 1, d) - new Date(hoje.getFullYear(), hoje.getMonth(), hoje.getDate())) / 86400000);
  const data = `${String(d).padStart(2, '0')}/${String(m).padStart(2, '0')}/${a}`;
  if (dias < 0) return { texto: `Prazo vencido em ${data}`, classe: 'bg-rose-50 text-rose-700 border-rose-200' };
  if (dias === 0) return { texto: 'Prazo termina hoje', classe: 'bg-rose-50 text-rose-700 border-rose-200' };
  if (dias <= 7) return { texto: `Prazo: ${dias} ${dias === 1 ? 'dia' : 'dias'} (${data})`, classe: 'bg-amber-50 text-amber-800 border-amber-200' };
  return { texto: `Prazo: ${data}`, classe: 'bg-slate-50 text-slate-600 border-slate-200' };
}
