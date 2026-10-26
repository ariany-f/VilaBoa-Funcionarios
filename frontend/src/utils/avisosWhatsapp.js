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
