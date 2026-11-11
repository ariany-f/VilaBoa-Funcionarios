// Progresso "meio gamificado" dos cadernos: pontos, nível e conquistas.
// Leituras obrigatórias valem 100 pontos; opcionais, 50.

const NIVEIS = [
  { minimo: 0, nome: 'Começando' },
  { minimo: 0.01, nome: 'Aprendiz' },
  { minimo: 0.5, nome: 'Leitor' },
  { minimo: 1, nome: 'Leitor Vila Boa' }
];

// Pontos do caderno: o valor configurado nele (`pontos`), ou o padrão (100 obrigatório, 50 opcional)
const somar = (lista, padrao) => lista.reduce((t, c) => t + (Number.isFinite(Number(c.pontos)) && c.pontos !== '' && c.pontos != null ? Number(c.pontos) : padrao), 0);

export function resumirProgresso(cadernos) {
  const obrigatorios = cadernos.filter((c) => c.obrigatorio);
  const opcionais = cadernos.filter((c) => !c.obrigatorio);
  const lidosObrig = obrigatorios.filter((c) => c.leitura).length;
  const lidosOpc = opcionais.filter((c) => c.leitura).length;
  const fracao = obrigatorios.length ? lidosObrig / obrigatorios.length : 1;

  let nivel = NIVEIS.filter((n) => fracao >= n.minimo).pop().nome;
  if (fracao === 1 && opcionais.length && lidosOpc === opcionais.length) nivel = 'Leitor Ouro';

  const conquistas = [
    { id: 'primeira', nome: 'Primeira leitura', descricao: 'Leu o primeiro caderno', icone: 'document', ok: lidosObrig + lidosOpc > 0 },
    { id: 'metade', nome: 'Meio caminho', descricao: 'Leu metade dos seus cadernos', icone: 'refresh-cw', ok: fracao >= 0.5 && obrigatorios.length > 0 },
    { id: 'em_dia', nome: 'Em dia', descricao: 'Leu todos os seus cadernos', icone: 'shield-check', ok: obrigatorios.length > 0 && fracao === 1 },
    { id: 'curioso', nome: 'Curioso', descricao: 'Leu uma leitura opcional', icone: 'search', ok: lidosOpc > 0 }
  ];

  return {
    total: obrigatorios.length,
    lidos: lidosObrig,
    percentual: Math.round(fracao * 100),
    opcionaisTotal: opcionais.length,
    opcionaisLidos: lidosOpc,
    pontos: somar(obrigatorios.filter((c) => c.leitura), 100) + somar(opcionais.filter((c) => c.leitura), 50),
    pontosMaximos: somar(obrigatorios, 100) + somar(opcionais, 50),
    nivel,
    conquistas,
    proximo: obrigatorios.find((c) => !c.leitura) || null
  };
}
