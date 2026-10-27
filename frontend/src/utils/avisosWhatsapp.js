// Avisos individuais do RH pelo WhatsApp: cada mensagem fala de um item
// e traz um link que, depois do login, já abre aquele item no portal.

function linkPortal(abrir) {
  return `${window.location.origin}${window.location.pathname}?abrir=${encodeURIComponent(abrir)}`;
}

function primeiroNome(nome) {
  return (nome || '').split(' ')[0];
}

function montarLink(telefone, texto) {
  const digitos = (telefone || '').replace(/\D/g, '');
  const destino = digitos ? `phone=55${digitos}&` : '';
  return `https://api.whatsapp.com/send?${destino}text=${encodeURIComponent(texto)}`;
}

export function avisoCaderno(pessoa, caderno) {
  const pedido = caderno.obrigatorio
    ? 'Você precisa ler este material.'
    : 'É uma leitura opcional, para quando você puder.';
  const texto = `Olá, ${primeiroNome(pessoa.nome)}! Este é o caderno *${caderno.titulo}* da Vila Boa. ${pedido}\n\n` +
    `Acesse pelo link abaixo e entre com seu CPF. O caderno já abre para você:\n${linkPortal('caderno:' + caderno.arquivo)}`;
  return montarLink(pessoa.telefone, texto);
}

export function avisoPonto(pessoa, competencia) {
  const texto = `Olá, ${primeiroNome(pessoa.nome)}! Seu espelho de ponto de *${competencia.mes_nome}* está disponível para você conferir e assinar.\n\n` +
    `Acesse pelo link abaixo e entre com seu CPF:\n${linkPortal('ponto:' + competencia.competencia)}`;
  return montarLink(pessoa.telefone, texto);
}

export function avisoHolerite(pessoa, holerite) {
  const texto = `Olá, ${primeiroNome(pessoa.nome)}! Seu holerite *${holerite.descricao}* está disponível no Portal do Colaborador.\n\n` +
    `Acesse pelo link abaixo e entre com seu CPF. O holerite já abre para você:\n${linkPortal('holerite:' + holerite.id)}`;
  return montarLink(pessoa.telefone, texto);
}

/** Lê ?abrir=tipo:valor da URL (link enviado pelo RH). */
export function lerDestinoDaUrl() {
  try {
    const valor = new URL(window.location.href).searchParams.get('abrir');
    if (!valor || !valor.includes(':')) return null;
    const i = valor.indexOf(':');
    return { tipo: valor.slice(0, i), valor: valor.slice(i + 1) };
  } catch (e) {
    return null;
  }
}

/**
 * Cobrança geral: lista todas as pendências do colaborador, cada uma com o seu link.
 * c = linha do /api/rh/resumo
 */
export function cobrancaGeral(c) {
  const itens = [];
  c.holerites.detalhe.filter((h) => !h.recebido_em).forEach((h) =>
    itens.push(`• Holerite ${h.descricao}: ${linkPortal('holerite:' + h.id)}`));
  (c.ponto?.competencias || []).filter((p) => !p.assinado_em).forEach((p) =>
    itens.push(`• Espelho de ponto de ${p.mes_nome} para assinar: ${linkPortal('ponto:' + p.competencia)}`));
  c.cadernos.detalhe.filter((d) => d.obrigatorio && !d.lido_em).forEach((d) =>
    itens.push(`• Ler o caderno ${d.titulo}: ${linkPortal('caderno:' + d.arquivo)}`));
  if (!itens.length) return null;
  const texto = `Olá, ${primeiroNome(c.nome)}! Você tem pendências no Portal do Colaborador da Vila Boa:\n\n${itens.join('\n')}\n\nEntre com seu CPF pelo link de cada item.`;
  return montarLink(c.telefone, texto);
}
