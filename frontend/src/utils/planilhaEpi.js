/**
 * Importação de fichas de EPI por planilha (.xlsx ou .csv), uma linha por item entregue.
 * As linhas do mesmo colaborador na mesma competência viram uma ficha.
 *
 * Colunas (a ordem não importa, maiúsculas e acentos também não):
 * Matrícula (ou Chapa), Competência, Função, Equipe, Data, Quantidade, Unidade, Código, Descrição, C.A., Valor, Situação
 */
import { lerLinhasPlanilha, paraData } from './planilhaEscala.js';

const COLUNAS = {
  matricula: ['matricula', 'matr', 'mat'],
  chapa: ['chapa'],
  competencia: ['competencia', 'comp', 'mes/ano', 'mes'],
  cargo: ['funcao', 'cargo'],
  equipe: ['equipe'],
  data: ['data', 'data entrega', 'data da entrega'],
  quantidade: ['quantidade', 'qtd', 'qtde', 'quant'],
  unidade: ['unidade', 'un', 'und', 'unid'],
  codigo: ['codigo', 'cod', 'cod. epi', 'codigo epi'],
  descricao: ['descricao', 'epi', 'descricao do epi', 'item'],
  ca: ['c.a.', 'ca', 'c.a', 'certificado de aprovacao'],
  valor: ['valor', 'valor unitario', 'preco'],
  situacao: ['situacao', 'tipo', 'motivo']
};

const MODELO = [
  ['Matrícula', 'Competência', 'Função', 'Equipe', 'Data', 'Quantidade', 'Unidade', 'Código', 'Descrição', 'C.A.', 'Valor', 'Situação'],
  ['VB-1040', '11/2026', 'Operador de Máquinas Pesadas', '45', '10/11/2026', '1', 'UN', 'EPI0001', 'Capacete de segurança com jugular', '31469', '38,90', 'Primeira Entrega'],
  ['VB-1040', '11/2026', 'Operador de Máquinas Pesadas', '45', '10/11/2026', '1', 'PAR', 'EPI0014', 'Botina com bico de aço', '41370', '189,00', 'Troca']
];

const semAcento = (s) => String(s ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').trim().toLowerCase();
const digitos = (s) => String(s ?? '').replace(/\D/g, '');

/** Baixa o modelo da planilha em .csv (abre no Excel). */
export function baixarModeloEpi() {
  const csv = '﻿' + MODELO.map((l) => l.join(';')).join('\r\n');
  const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
  const a = Object.assign(document.createElement('a'), { href: url, download: 'modelo-fichas-epi.csv' });
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

function texto(v) {
  if (typeof v === 'number') return String(v).replace('.', ',');
  return String(v ?? '').trim();
}

function competenciaDe(valor, dataIso) {
  if (typeof valor === 'number' && valor > 20000) {
    const iso = paraData(valor);
    return `${iso.slice(5, 7)}/${iso.slice(0, 4)}`;
  }
  const v = String(valor ?? '').trim();
  let m = v.match(/^(\d{1,2})[/\-.](\d{4})$/);
  if (m) return `${m[1].padStart(2, '0')}/${m[2]}`;
  m = v.match(/^(\d{4})-(\d{1,2})/);
  if (m) return `${m[2].padStart(2, '0')}/${m[1]}`;
  m = v.match(/^\d{1,2}\/(\d{1,2})\/(\d{4})$/);
  if (m) return `${m[1].padStart(2, '0')}/${m[2]}`;
  return dataIso ? `${dataIso.slice(5, 7)}/${dataIso.slice(0, 4)}` : '';
}

/**
 * Lê a planilha e devolve { fichas, avisos }. Cada ficha já vem no formato do POST /rh/epis.
 * colaboradores: [{ matricula, nome, cargo }] para achar o colaborador pela chapa.
 */
export async function lerPlanilhaEpi(arquivo, colaboradores) {
  const linhas = await lerLinhasPlanilha(arquivo);
  const iCab = linhas.findIndex((l) => l.some((c) => semAcento(c).startsWith('descri')) && l.some((c) => ['matricula', 'chapa', 'matr'].includes(semAcento(c))));
  if (iCab < 0) throw new Error('Não achei o cabeçalho com as colunas "Matrícula" (ou "Chapa") e "Descrição". Baixe o modelo para ver o formato.');
  const cab = linhas[iCab].map(semAcento);
  const col = {};
  for (const [campo, nomes] of Object.entries(COLUNAS)) {
    const i = cab.findIndex((c) => nomes.includes(c));
    if (i >= 0) col[campo] = i;
  }
  const valor = (l, campo) => (col[campo] === undefined ? '' : l[col[campo]]);

  const porChapa = new Map(colaboradores.map((c) => [Number(digitos(c.matricula)), c.matricula]));
  const porMatricula = new Map(colaboradores.map((c) => [c.matricula.toUpperCase(), c.matricula]));
  const fichas = new Map();
  const avisos = [];

  linhas.slice(iCab + 1).forEach((l, n) => {
    if (!l.some((c) => String(c ?? '').trim())) return;
    const linha = iCab + n + 2;
    const chapa = digitos(valor(l, 'chapa'));
    const matricula = porMatricula.get(texto(valor(l, 'matricula')).toUpperCase())
      || (chapa ? porChapa.get(Number(chapa)) : '')
      || (digitos(valor(l, 'matricula')) ? porChapa.get(Number(digitos(valor(l, 'matricula')))) : '');
    if (!matricula) {
      avisos.push(`Linha ${linha}: colaborador ${texto(valor(l, 'matricula')) || chapa || '(em branco)'} não encontrado.`);
      return;
    }
    const dataIso = paraData(valor(l, 'data'));
    const competencia = competenciaDe(valor(l, 'competencia'), dataIso);
    if (!competencia) {
      avisos.push(`Linha ${linha}: informe a competência ou a data.`);
      return;
    }
    const chave = `${matricula}|${competencia}`;
    if (!fichas.has(chave)) {
      fichas.set(chave, { matricula, competencia, chapa, cargo: texto(valor(l, 'cargo')), equipe: texto(valor(l, 'equipe')), itens: [] });
    }
    fichas.get(chave).itens.push({
      data: dataIso ? dataIso.split('-').reverse().join('/') : texto(valor(l, 'data')),
      quantidade: texto(valor(l, 'quantidade')) || '1',
      unidade: texto(valor(l, 'unidade')),
      codigo: texto(valor(l, 'codigo')),
      descricao: texto(valor(l, 'descricao')),
      ca: texto(valor(l, 'ca')),
      valor: typeof valor(l, 'valor') === 'number' ? valor(l, 'valor').toFixed(2).replace('.', ',') : texto(valor(l, 'valor')),
      situacao: /troca|repos/i.test(texto(valor(l, 'situacao'))) ? 'Troca' : 'Primeira Entrega'
    });
  });

  if (!fichas.size && !avisos.length) throw new Error('A planilha não tem itens abaixo do cabeçalho.');
  return { fichas: [...fichas.values()], avisos };
}
