// Uso: node scripts/ferramentas/extrai-dados.js <ref>/app.js <ref>/dados.json
// Extrai os arrays/objetos de dados do app.js de referência para JSON.
const fs = require('fs');
const src = fs.readFileSync(process.argv[2], 'utf8');
function bloco(inicio) {
  const i = src.indexOf(inicio);
  if (i < 0) throw new Error('não achei ' + inicio);
  let j = src.slice(i).search(/[\[{]/) + i, prof = 0;
  const abre = src[j], fecha = abre === '[' ? ']' : '}';
  for (let k = j; k < src.length; k++) {
    if (src[k] === abre) prof++;
    else if (src[k] === fecha && --prof === 0) return src.slice(j, k + 1);
  }
}
const sce = { trustAsHtml: s => s };
const ev = t => new Function('$sce', 'return ' + t)(sce);
const out = {
  acessoRapido: ev(bloco('vm.acessoRapido = [')),
  pesquisas: ev(bloco('vm.categorias = [\n      { nome:')),
  transparencia: ev(bloco('vm.itens = [')),
  liderancas: ev(bloco('var liderancas = {')),
  postos: ev(bloco('vm.postos = [')),
};
fs.writeFileSync(process.argv[3], JSON.stringify(out, null, 1));
console.log(Object.entries(out).map(([k, v]) => k + ':' + Object.keys(v).length).join(' '));
