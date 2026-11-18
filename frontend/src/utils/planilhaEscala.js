/**
 * Lê a planilha da escala de folgas exportada do RM (PRC-153, item 2) direto no navegador:
 * .xlsx (Excel) ou .csv. Aceita tanto a planilha já configurada (TRABALHO / FOLGA nas colunas)
 * quanto a exportação crua, em que a folga é o dia com a entrada zerada.
 *
 * Devolve { dias: [{ data: 'aaaa-mm-dd', folga, horarios: [4 textos] }], letra, horario }.
 */

const decodificador = new TextDecoder('utf-8');

// ---------- ZIP (o .xlsx é um zip de XMLs) ----------
async function lerZip(buffer) {
  const v = new DataView(buffer);
  let fim = -1;
  for (let i = buffer.byteLength - 22; i >= Math.max(0, buffer.byteLength - 65557); i--) {
    if (v.getUint32(i, true) === 0x06054b50) { fim = i; break; }
  }
  if (fim < 0) throw new Error('Arquivo não é uma planilha .xlsx válida. Se veio em .xls, salve como .xlsx no Excel.');
  const total = v.getUint16(fim + 10, true);
  let p = v.getUint32(fim + 16, true);
  const arquivos = {};
  for (let n = 0; n < total; n++) {
    if (v.getUint32(p, true) !== 0x02014b50) break;
    const metodo = v.getUint16(p + 10, true);
    const tamCompactado = v.getUint32(p + 20, true);
    const tamNome = v.getUint16(p + 28, true);
    const tamExtra = v.getUint16(p + 30, true);
    const tamComent = v.getUint16(p + 32, true);
    const local = v.getUint32(p + 42, true);
    const nome = decodificador.decode(new Uint8Array(buffer, p + 46, tamNome));
    arquivos[nome] = { metodo, tamCompactado, local };
    p += 46 + tamNome + tamExtra + tamComent;
  }
  return async (nome) => {
    const a = arquivos[nome];
    if (!a) return null;
    const inicio = a.local + 30 + v.getUint16(a.local + 26, true) + v.getUint16(a.local + 28, true);
    const dados = new Uint8Array(buffer, inicio, a.tamCompactado);
    if (a.metodo === 0) return decodificador.decode(dados);
    if (a.metodo !== 8 || typeof DecompressionStream === 'undefined') {
      throw new Error('Não foi possível abrir a planilha neste navegador. Atualize o navegador ou envie em .csv.');
    }
    const stream = new Blob([dados]).stream().pipeThrough(new DecompressionStream('deflate-raw'));
    return await new Response(stream).text();
  };
}

const xml = (texto) => new DOMParser().parseFromString(texto, 'application/xml');
const filhos = (el, nome) => Array.from(el.getElementsByTagName(nome));

function colunaIndice(ref) {
  const letras = (ref.match(/^[A-Z]+/) || [''])[0];
  return letras.split('').reduce((t, l) => t * 26 + l.charCodeAt(0) - 64, 0) - 1;
}

async function linhasXlsx(buffer) {
  const abrir = await lerZip(buffer);
  const compartilhadas = [];
  const sst = await abrir('xl/sharedStrings.xml');
  if (sst) {
    filhos(xml(sst), 'si').forEach((si) => compartilhadas.push(filhos(si, 't').map((t) => t.textContent).join('')));
  }
  // Primeira aba do arquivo (pelo workbook), com sheet1 como reserva
  let caminho = 'xl/worksheets/sheet1.xml';
  try {
    const wb = xml(await abrir('xl/workbook.xml'));
    const rid = filhos(wb, 'sheet')[0]?.getAttribute('r:id');
    const rels = xml(await abrir('xl/_rels/workbook.xml.rels'));
    const alvo = filhos(rels, 'Relationship').find((r) => r.getAttribute('Id') === rid)?.getAttribute('Target');
    if (alvo) caminho = alvo.startsWith('/') ? alvo.slice(1) : 'xl/' + alvo.replace(/^\.\//, '');
  } catch (e) { /* usa sheet1 */ }
  const folha = await abrir(caminho);
  if (!folha) throw new Error('A planilha está vazia.');

  return filhos(xml(folha), 'row').map((row) => {
    const linha = [];
    filhos(row, 'c').forEach((c) => {
      const tipo = c.getAttribute('t');
      const valor = c.getElementsByTagName('v')[0]?.textContent ?? '';
      let conteudo;
      if (tipo === 's') conteudo = compartilhadas[Number(valor)] ?? '';
      else if (tipo === 'inlineStr') conteudo = filhos(c, 't').map((t) => t.textContent).join('');
      else if (tipo === 'str' || tipo === 'b' || tipo === 'e') conteudo = valor;
      else conteudo = valor === '' ? '' : Number(valor);
      linha[colunaIndice(c.getAttribute('r') || '')] = conteudo;
    });
    return Array.from(linha, (x) => x ?? '');
  });
}

function linhasCsv(texto) {
  const primeira = texto.split(/\r?\n/)[0] || '';
  const sep = [';', '\t', ','].sort((a, b) => primeira.split(b).length - primeira.split(a).length)[0];
  return texto.split(/\r?\n/).filter((l) => l.trim()).map((l) => l.split(sep).map((c) => c.trim().replace(/^"|"$/g, '')));
}

// ---------- Interpretação ----------
const semAcento = (s) => String(s ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').trim().toLowerCase();

function paraData(valor) {
  if (typeof valor === 'number' && valor > 20000 && valor < 80000) {
    const d = new Date(Date.UTC(1899, 11, 30) + Math.round(valor) * 86400000);
    return d.toISOString().slice(0, 10);
  }
  const m = String(valor).trim().match(/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})/);
  if (m) {
    const ano = m[3].length === 2 ? '20' + m[3] : m[3];
    return `${ano}-${m[2].padStart(2, '0')}-${m[1].padStart(2, '0')}`;
  }
  const iso = String(valor).trim().match(/^(\d{4})-(\d{2})-(\d{2})/);
  return iso ? `${iso[1]}-${iso[2]}-${iso[3]}` : '';
}

function paraHorario(valor) {
  if (typeof valor === 'number' && valor >= 0 && valor < 1) {
    const min = Math.round(valor * 1440);
    return `${String(Math.floor(min / 60)).padStart(2, '0')}:${String(min % 60).padStart(2, '0')}`;
  }
  return String(valor ?? '').trim().toUpperCase();
}

const ehZerado = (h) => h === '' || /^0{1,2}(:00){0,2}$/.test(h) || h === '-' || h === '--:--';

export async function lerPlanilhaEscala(arquivo) {
  const nome = arquivo.name || '';
  const linhas = /\.csv$/i.test(nome)
    ? linhasCsv(await arquivo.text())
    : await linhasXlsx(await arquivo.arrayBuffer());

  const iCabecalho = linhas.findIndex((l) => l.some((c) => semAcento(c) === 'data'));
  if (iCabecalho < 0) throw new Error('Não achei a coluna "Data" na planilha. Use a escala exportada do RM.');
  const cab = linhas[iCabecalho].map(semAcento);
  const colData = cab.indexOf('data');
  const colDia = cab.indexOf('dia');
  let colHorarios = cab.map((c, i) => (/^(entrada|saida)/.test(c) ? i : -1)).filter((i) => i >= 0);
  if (!colHorarios.length) colHorarios = cab.map((_, i) => i).filter((i) => i !== colData && i !== colDia);
  colHorarios = colHorarios.slice(0, 4);

  const dias = [];
  for (const l of linhas.slice(iCabecalho + 1)) {
    const data = paraData(l[colData]);
    if (!data) continue;
    const horarios = colHorarios.map((i) => paraHorario(l[i]));
    const folga = horarios.some((h) => /FOLGA|DSR|DESCANSO/.test(h)) || horarios.every(ehZerado);
    dias.push({ data, folga, horarios });
  }
  if (!dias.length) throw new Error('Nenhum dia encontrado na planilha.');
  dias.sort((a, b) => a.data.localeCompare(b.data));

  // Nome do arquivo do PRC: "12 Escala 5x1 Letra A" (mês + letra); horário 073 tem escala própria
  const letra = (nome.match(/letra\s*([a-z])\b/i) || [])[1]?.toUpperCase() || '';
  const horario = (nome.match(/\b(0\d{2})\b/) || [])[1] || '';
  return { dias, letra, horario };
}
