// Certificados da Academia Vila Boa: abrem uma página A4 (paisagem) pronta para imprimir ou salvar em PDF.
// Cada função devolve false quando o navegador bloqueou o pop-up.

const esc = (t) => String(t ?? '').replace(/[&<>"]/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[m]));

function abrir(html, janela = null) {
  // Uma janela já aberta no clique evita o bloqueio de pop-up quando o certificado só fica pronto depois de uma requisição
  const w = janela || window.open('', '_blank');
  if (!w) return false;
  w.document.write(html);
  w.document.close();
  return true;
}

const IMPRIMIR = '<script>window.onload = () => setTimeout(() => window.print(), 300)<\/script>';

/** Certificado de uma caderno da Academia (conclusão de leitura): c = caderno com `leitura`. */
export function htmlCertificadoCaderno(c, colaborador, logo, imprimir = false) {
  const l = c.leitura;
  const titulo = c.titulo.replace(/\s*Vila Boa/gi, '').replace(/\s+/g, ' ').trim() || c.titulo;
  const cargaHoraria = c.carga_horaria ? ` com carga horária de <strong>${esc(c.carga_horaria)}</strong>` : '';
  return `<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Certificado - ${esc(titulo)}</title>
<style>
  @page { size: A4 landscape; margin: 0 }
  * { box-sizing: border-box }
  body { margin: 0; font-family: Georgia, 'Times New Roman', serif; color: #1f2937; }
  .folha { width: 297mm; height: 210mm; padding: 14mm; }
  .moldura { height: 100%; border: 3px solid #9c2337; padding: 5mm; }
  .interna { height: 100%; border: 1px solid #9c2337; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 10mm 20mm; gap: 6mm; }
  img { height: 16mm; object-fit: contain }
  h1 { margin: 0; font-size: 40pt; letter-spacing: 6px; color: #9c2337; font-weight: normal; text-transform: uppercase }
  p { margin: 0; font-size: 15pt; line-height: 1.6 }
  .nome { font-size: 30pt; font-style: italic; border-bottom: 1px solid #9ca3af; padding: 0 12mm 2mm }
  .curso { font-size: 20pt; font-weight: bold }
  .rodape { font-family: Arial, sans-serif; font-size: 8.5pt; color: #6b7280; margin-top: 4mm; line-height: 1.5 }
  .codigo { font-family: 'Courier New', monospace; word-break: break-all }
</style></head><body><div class="folha"><div class="moldura"><div class="interna">
  <img src="${esc(logo)}" alt="">
  <h1>Certificado</h1>
  <p>Certificamos que</p>
  <div class="nome">${esc(colaborador?.nome || 'Colaborador')}</div>
  <p>concluiu o treinamento</p>
  <div class="curso">${esc(titulo)}</div>
  <p>${cargaHoraria ? 'realizado' + cargaHoraria + ',' : ''} em <strong>${esc((l?.data_hora || '').slice(0, 10))}</strong>.</p>
  <div class="rodape">
    Matrícula ${esc(colaborador?.matricula || '')} · Conclusão registrada eletronicamente em ${esc(l?.data_hora || '')}<br>
    Código de validação: <span class="codigo">${esc((l?.comprovante || '').slice(0, 32))}</span>
  </div>
</div></div></div>${imprimir ? IMPRIMIR : ''}</body></html>`;
}

export function abrirCertificadoCaderno(c, colaborador, logo, janela = null) {
  return abrir(htmlCertificadoCaderno(c, colaborador, logo, true), janela);
}

/** Certificado de trilha, com a assinatura digital da empresa e a do colaborador: c = certificado emitido pelo servidor. */
export function htmlCertificadoTrilha(c, logo, imprimir = false) {
  const e = c.assinatura_empresa || {};
  const f = c.assinatura_colaborador || {};
  const base = new URL(document.baseURI);
  const urlValidacao = `${base.origin}${base.pathname.replace(/[^/]*$/, '')}api/validar-certificado?codigo=${encodeURIComponent(c.codigo)}`;
  return `<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Certificado - ${esc(c.trilha)}</title>
<style>
  @page { size: A4 landscape; margin: 0 }
  * { box-sizing: border-box }
  body { margin: 0; font-family: Georgia, 'Times New Roman', serif; color: #1f2937 }
  .folha { width: 297mm; height: 210mm; padding: 12mm }
  .moldura { height: 100%; border: 3px solid #9c2337; padding: 4mm }
  .interna { height: 100%; border: 1px solid #9c2337; display: flex; flex-direction: column; align-items: center; justify-content: space-between; text-align: center; padding: 9mm 16mm }
  img { height: 15mm; object-fit: contain }
  h1 { margin: 0; font-size: 36pt; letter-spacing: 6px; color: #9c2337; font-weight: normal; text-transform: uppercase }
  p { margin: 0; font-size: 14pt; line-height: 1.5 }
  .nome { font-size: 28pt; font-style: italic; border-bottom: 1px solid #9ca3af; padding: 0 12mm 1mm }
  .curso { font-size: 19pt; font-weight: bold }
  .assinaturas { width: 100%; display: flex; justify-content: space-around; gap: 12mm; font-family: Arial, sans-serif }
  .ass { flex: 1; max-width: 105mm; border-top: 1px solid #374151; padding-top: 2mm; font-size: 9.5pt; line-height: 1.45 }
  .ass strong { display: block; font-size: 10.5pt }
  .selo { font-family: 'Courier New', monospace; font-size: 7pt; color: #6b7280; word-break: break-all }
  .rodape { font-family: Arial, sans-serif; font-size: 8pt; color: #6b7280; line-height: 1.5 }
</style></head><body><div class="folha"><div class="moldura"><div class="interna">
  <img src="${esc(logo)}" alt="">
  <h1>Certificado</h1>
  <p>Certificamos que</p>
  <div class="nome">${esc(c.nome)}</div>
  <p>concluiu a trilha de aprendizagem da <strong>Academia Vila Boa</strong></p>
  <div class="curso">${esc(c.trilha)}</div>
  <p>${c.carga_horaria ? 'com carga horária de <strong>' + esc(c.carga_horaria) + '</strong>, ' : ''}${esc(c.etapas)} ${c.etapas === 1 ? 'etapa concluída' : 'etapas concluídas'} em <strong>${esc((c.concluida_em || '').slice(0, 10))}</strong>.</p>
  <div class="assinaturas">
    <div class="ass"><strong>${esc(f.nome)}</strong>Colaborador · matrícula ${esc(f.matricula)}<br>Assinado digitalmente em ${esc(f.data_hora)}<div class="selo">${esc((f.selo || '').slice(0, 40))}</div></div>
    <div class="ass"><strong>${esc(e.signatario)}</strong>${esc(e.cargo)}<br>Assinado digitalmente em ${esc(e.data_hora)}<div class="selo">${esc((e.selo || '').slice(0, 40))}</div></div>
  </div>
  <div class="rodape">Código de validação: <strong>${esc(c.codigo)}</strong> · Emitido em ${esc(c.emitido_em)}<br>Confira a autenticidade em ${esc(urlValidacao)}</div>
</div></div></div>${imprimir ? IMPRIMIR : ''}</body></html>`;
}

export function abrirCertificadoTrilha(c, logo, janela = null) {
  return abrir(htmlCertificadoTrilha(c, logo, true), janela);
}

/** Endereço público de validação do certificado de trilha. */
export function urlValidacaoCertificado(codigo) {
  const base = new URL(document.baseURI);
  return `${base.origin}${base.pathname.replace(/[^/]*$/, '')}api/validar-certificado?codigo=${encodeURIComponent(codigo)}`;
}
