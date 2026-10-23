/**
 * Helper para resolver endpoints da API de forma universal,
 * funcionando tanto na raiz (/) quanto em subpastas (/appvilaboafuncionario/).
 */
export function apiUrl(endpoint) {
  const clean = endpoint.startsWith('/') ? endpoint.slice(1) : endpoint;
  let pathname = window.location.pathname;
  if (!pathname.endsWith('/')) {
    pathname += '/';
  }
  return pathname + clean;
}

export function apiFetch(endpoint, options) {
  return fetch(apiUrl(endpoint), options);
}
