import { ref, watch } from 'vue';

// Empresa escolhida no menu lateral do painel do RH. '' = Todas (padrão).
// As telas do RH leem este valor para filtrar tudo pela empresa; a lista é preenchida pelo RhView.
const CHAVE = 'vb_rh_empresa_contexto';

function lerSalvo() {
  try { return localStorage.getItem(CHAVE) || ''; } catch (e) { return ''; }
}

export const empresaContexto = ref(lerSalvo());
export const empresasContexto = ref([]);

watch(empresaContexto, (v) => {
  try { v ? localStorage.setItem(CHAVE, v) : localStorage.removeItem(CHAVE); } catch (e) { /* só não lembra */ }
});

// Se a empresa salva deixou de existir, volta para "Todas"
export function conferirEmpresaContexto() {
  if (empresaContexto.value && empresasContexto.value.length && !empresasContexto.value.includes(empresaContexto.value)) {
    empresaContexto.value = '';
  }
}

// Item de "todas as empresas" aparece em qualquer empresa escolhida
export const doContexto = (empresaDoItem) => !empresaContexto.value || !empresaDoItem || empresaDoItem === 'todas' || empresaDoItem === empresaContexto.value;
