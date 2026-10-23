/**
 * Dicionário Didático da Folha de Pagamento - Vila Boa Construções
 * Explicações em linguagem simples e acessível para os colaboradores da obra.
 */

export const GLOSSARIO_RUBRICAS = {
  // Proventos
  '001': {
    titulo: 'Salário Base',
    tipo: 'PROVENTO',
    subtitulo: 'Seu salário contratual normal',
    descricao: 'É o valor fixo combinado na sua contratação para 30 dias de trabalho, de acordo com o seu cargo e convenção da construção civil.',
    comoFunciona: 'Não inclui horas extras nem adicionais. É sobre este valor que são calculadas suas férias, 13º e FGTS.'
  },
  '024': {
    titulo: 'Adicional de Obras / Periculosidade',
    tipo: 'PROVENTO',
    subtitulo: 'Compensação financeira por atividade em obra',
    descricao: 'Valor a mais pago pela Vila Boa em razão das atividades em canteiros de obra, operação de maquinário pesado ou condições operacionais da construção.',
    comoFunciona: 'Soma-se aos seus ganhos mensais e também conta para o cálculo de férias e 13º salário.'
  },
  '015': {
    titulo: 'Horas Extras 50%',
    tipo: 'PROVENTO',
    subtitulo: 'Trabalho além da jornada diária normal',
    descricao: 'Pagamento pelo tempo em que você continuou trabalhando na obra além do seu horário habitual em dias úteis.',
    comoFunciona: 'Cada hora extra vale o preço da sua hora normal mais 50% de acréscimo garantido por lei.'
  },
  '008': {
    titulo: 'Adiantamento Quinzenal (Vale)',
    tipo: 'PROVENTO',
    subtitulo: 'Antecipação de salário no dia 20',
    descricao: 'Parte do seu salário que a Vila Boa adianta no dia 20 de cada mês (normalmente 40%) para ajudar com as despesas do mês antes do pagamento final.',
    comoFunciona: 'No adiantamento não há desconto de INSS nem de imposto de renda; eles só são calculados no fechamento da folha no 5º dia útil.'
  },
  '061': {
    titulo: '1ª Parcela do 13º Salário (50%)',
    tipo: 'PROVENTO',
    subtitulo: 'Metade do seu salário extra de fim de ano',
    descricao: 'Adiantamento de 50% do seu 13º salário pago até novembro pela empresa.',
    comoFunciona: 'Por determinação da lei trabalhista, a primeira parcela é paga limpa: não tem nenhum desconto de INSS ou Imposto de Renda!'
  },
  '062': {
    titulo: '13º Salário Integral (2ª Parcela)',
    tipo: 'PROVENTO',
    subtitulo: 'Quitação final da gratificação natalina',
    descricao: 'É a segunda parte do seu 13º salário, paga até 20 de dezembro.',
    comoFunciona: 'Aqui aparece o valor total do 13º do ano, e nos descontos vem o que você já recebeu na 1ª parcela, mais o INSS e IRRF da gratificação.'
  },
  '080': {
    titulo: 'Participação nos Resultados (PLR)',
    tipo: 'PROVENTO',
    subtitulo: 'Bônus por metas e produtividade da obra',
    descricao: 'Prêmio em dinheiro pago pela empresa pelo atingimento das metas de entrega, segurança e produtividade acordadas com o sindicato.',
    comoFunciona: 'Tem tributação especial e exclusiva pela Receita Federal, sendo isento de impostos até o limite da tabela anual de PLR.'
  },

  // Descontos
  '501': {
    titulo: 'INSS Folha de Pagamento',
    tipo: 'DESCONTO',
    subtitulo: 'Sua contribuição para a Previdência Social',
    descricao: 'Valor recolhido por lei para o Governo Federal que garante seus direitos previdenciários.',
    comoFunciona: 'Garante sua aposentadoria no futuro, auxílio por doença/acidente de trabalho, salário-maternidade e pensão para sua família.'
  },
  '505': {
    titulo: 'IRRF sobre Salário',
    tipo: 'DESCONTO',
    subtitulo: 'Imposto de Renda Retido na Fonte',
    descricao: 'Imposto federal obrigatório retido diretamente pela empresa e repassado à Receita Federal.',
    comoFunciona: 'Só é descontado se o seu salário após abater o INSS e dependentes ficar acima da faixa de isenção da tabela oficial da Receita.'
  },
  '518': {
    titulo: 'Vale Transporte',
    tipo: 'DESCONTO',
    subtitulo: 'Sua cota no transporte até a obra',
    descricao: 'Participação do colaborador no custo do deslocamento de casa para o canteiro de obras e vice-versa.',
    comoFunciona: 'A lei permite descontar até no máximo 6% do salário base. Todo o custo restante das passagens é pago integralmente pela Vila Boa.'
  },
  '522': {
    titulo: 'Coparticipação Plano de Saúde',
    tipo: 'DESCONTO',
    subtitulo: 'Pequena taxa por consulta ou exame realizado',
    descricao: 'A Vila Boa paga a mensalidade principal do seu convênio médico Unimed. Você só paga essa coparticipação nos meses em que usar consultas ou exames.',
    comoFunciona: 'Se você não usou o plano no mês, esse desconto é zero. O valor ajuda a manter o plano acessível para todos.'
  },
  '530': {
    titulo: 'Vale Refeição / Alimentação',
    tipo: 'DESCONTO',
    subtitulo: 'Participação simbólica na alimentação',
    descricao: 'Pequeno valor simbólico estabelecido na convenção coletiva da categoria para o fornecimento do benefício de alimentação/refeição.',
    comoFunciona: 'A maior parte do benefício é subsidiada pela empresa para garantir alimentação de qualidade para a equipe.'
  },
  '560': {
    titulo: 'Adiantamento 1ª Parcela do 13º',
    tipo: 'DESCONTO',
    subtitulo: 'Abatimento do que já foi antecipado em novembro',
    descricao: 'Como você já recebeu 50% do 13º salário em novembro, esse valor é deduzido no contracheque de dezembro para você receber o saldo restante.',
    comoFunciona: 'Não é um dinheiro perdido; é apenas o registro de que a primeira metade já foi depositada na sua conta anteriormente.'
  },
  '561': {
    titulo: 'INSS sobre 13º Salário',
    tipo: 'DESCONTO',
    subtitulo: 'Contribuição previdenciária da gratificação',
    descricao: 'A lei determina que o INSS sobre o 13º salário seja recolhido integralmente apenas na segunda parcela.',
    comoFunciona: 'Conta para seu histórico de contribuição na Previdência Social.'
  },
  '565': {
    titulo: 'IRRF sobre 13º Salário',
    tipo: 'DESCONTO',
    subtitulo: 'Imposto de renda exclusivo do 13º',
    descricao: 'Cálculo do imposto de renda retido exclusivamente sobre a gratificação natalina, separado do salário do mês.',
    comoFunciona: 'Calculado sobre o 13º total já deduzido o INSS.'
  },

  // Bases de cálculo e totais
  'BASE_FGTS': {
    titulo: 'Base de Cálculo do FGTS',
    tipo: 'INFORMATIVO',
    subtitulo: 'Valor de referência para o depósito do seu Fundo de Garantia',
    descricao: 'É a soma dos seus proventos (salário base + horas extras + adicionais) sobre a qual a Vila Boa calcula o valor do FGTS.',
    comoFunciona: 'Serve apenas como referência matemática para saber quanto a empresa vai depositar na sua conta da Caixa.'
  },
  'FGTS_MES': {
    titulo: 'FGTS do Mês (Depósito da Empresa)',
    tipo: 'BENEFICIO_EMPRESA',
    subtitulo: 'Dinheiro guardado na Caixa Econômica Federal em seu nome',
    descricao: 'Valor de 8% pago integralmente pela Vila Boa Construções e depositado todo mês na sua conta vinculada do FGTS na Caixa.',
    comoFunciona: 'IMPORTANTE: Esse valor NÃO é descontado do seu salário! É um direito pago 100% pela empresa para sua reserva financeira, compra de imóvel ou saque em rescisão.'
  },
  'BASE_INSS': {
    titulo: 'Base de Cálculo do INSS',
    tipo: 'INFORMATIVO',
    subtitulo: 'Soma dos seus ganhos sujeitos à Previdência',
    descricao: 'É o valor total de proventos sobre o qual é aplicada a tabela progressiva do INSS.',
    comoFunciona: 'Determina em qual faixa de alíquota do INSS seu contracheque se enquadra.'
  },
  'BASE_IRRF': {
    titulo: 'Base de Cálculo do Imposto de Renda',
    tipo: 'INFORMATIVO',
    subtitulo: 'Valor tributável após abatimentos legais',
    descricao: 'É o valor que sobra dos seus ganhos após subtrair a contribuição do INSS e dependentes legais.',
    comoFunciona: 'Se esse valor for menor que o limite de isenção da Receita Federal, você não paga nada de imposto de renda.'
  },
  'TOTAL_PROVENTOS': {
    titulo: 'Total de Proventos (Salário Bruto)',
    tipo: 'PROVENTO',
    subtitulo: 'A soma de tudo o que você ganhou no mês',
    descricao: 'É o seu salário bruto total: salário base + horas extras + adicionais de obra antes de qualquer desconto.',
    comoFunciona: 'É o valor utilizado como referência para financiamentos habitacionais (Caixa / Minha Casa Minha Vida).'
  },
  'TOTAL_DESCONTOS': {
    titulo: 'Total de Descontos',
    tipo: 'DESCONTO',
    subtitulo: 'A soma de todas as retenções legais e benefícios',
    descricao: 'É a soma de tudo o que foi retido: impostos obrigatórios (INSS, IRRF) e sua cota em benefícios (transporte, refeição, saúde).',
    comoFunciona: 'Todos os descontos obedecem estritamente à legislação trabalhista ou acordos sindicais aprovados.'
  },
  'SALARIO_LIQUIDO': {
    titulo: 'Salário Líquido a Receber',
    tipo: 'VALOR_FINAL',
    subtitulo: 'O dinheiro que entra na sua conta bancária',
    descricao: 'É o resultado final da conta: Total de Proventos (Bruto) MENOS o Total de Descontos.',
    comoFunciona: 'É o valor exato depositado na sua conta corrente/salário no dia do pagamento.'
  }
};

/**
 * Busca a explicação didática de uma rubrica por código ou nome aproximado.
 */
export function obterExplicacaoRubrica(codigo, descricao) {
  if (codigo && GLOSSARIO_RUBRICAS[codigo]) {
    return GLOSSARIO_RUBRICAS[codigo];
  }

  const descNormalizada = (descricao || '').toUpperCase();

  if (descNormalizada.includes('SALÁRIO BASE') || descNormalizada.includes('SALARIO BASE')) {
    return GLOSSARIO_RUBRICAS['001'];
  }
  if (descNormalizada.includes('PERICULOSIDADE') || descNormalizada.includes('ADICIONAL')) {
    return GLOSSARIO_RUBRICAS['024'];
  }
  if (descNormalizada.includes('HORA EXTRA') || descNormalizada.includes('HORAS EXTRAS')) {
    return GLOSSARIO_RUBRICAS['015'];
  }
  if (descNormalizada.includes('ADIANTAMENTO') || descNormalizada.includes('VALE')) {
    return GLOSSARIO_RUBRICAS['008'];
  }
  if (descNormalizada.includes('1ª PARCELA') || descNormalizada.includes('PRIMEIRA PARCELA')) {
    return GLOSSARIO_RUBRICAS['061'];
  }
  if (descNormalizada.includes('13º SALÁRIO') || descNormalizada.includes('DECIMO TERCEIRO')) {
    return GLOSSARIO_RUBRICAS['062'];
  }
  if (descNormalizada.includes('PLR') || descNormalizada.includes('RESULTADOS')) {
    return GLOSSARIO_RUBRICAS['080'];
  }
  if (descNormalizada.includes('INSS')) {
    return GLOSSARIO_RUBRICAS['501'];
  }
  if (descNormalizada.includes('IRRF') || descNormalizada.includes('IMPOSTO DE RENDA')) {
    return GLOSSARIO_RUBRICAS['505'];
  }
  if (descNormalizada.includes('TRANSPORTE') || descNormalizada.includes('VT')) {
    return GLOSSARIO_RUBRICAS['518'];
  }
  if (descNormalizada.includes('SAÚDE') || descNormalizada.includes('SAUDE') || descNormalizada.includes('UNIMED')) {
    return GLOSSARIO_RUBRICAS['522'];
  }
  if (descNormalizada.includes('REFEIÇÃO') || descNormalizada.includes('REFEICAO') || descNormalizada.includes('ALIMENTAÇÃO')) {
    return GLOSSARIO_RUBRICAS['530'];
  }

  // Fallback genérico amigável
  return {
    titulo: descricao || 'Item da Folha de Pagamento',
    tipo: 'INFORMATIVO',
    subtitulo: 'Item discriminado no seu contracheque',
    descricao: 'Este valor faz parte da composição oficial da sua remuneração mensal na Vila Boa Construções.',
    comoFunciona: 'Registrado em conformidade com as normas trabalhistas (CLT) e acordos da construção civil.'
  };
}
