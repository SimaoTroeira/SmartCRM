<!-- DataNormalizer.vue -->
<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const props = defineProps({
  rows: Array,
  mappedColumns: Array,
  fileType: String,
  companyId: Number
});

const emit = defineEmits(['normalizado', 'erro']);
const toast = useToast();

const normalizarDataExcel = (valor) => {
  if (typeof valor === 'number') {
    const base = new Date(1899, 11, 30);
    const result = new Date(base.getTime() + valor * 86400000);
    return result.toISOString().split('T')[0];
  }
  if (typeof valor === 'string' && /\d{2}\/\d{2}\/\d{4}/.test(valor)) {
    const [dia, mes, ano] = valor.split('/');
    return `${ano}-${mes}-${dia}`;
  }
  return valor;
};

const codigoPostalParaRegiao = (cp) => {
  const inicio = parseInt(cp?.split('-')[0]);
  if (!inicio || isNaN(inicio)) return 'Desconhecido';
  if (inicio >= 1000 && inicio <= 1999) return 'Lisboa';
  if (inicio >= 2000 && inicio <= 2999) return 'Santarém';
  if (inicio >= 3000 && inicio <= 3999) return 'Coimbra';
  if (inicio >= 4000 && inicio <= 4999) return 'Porto';
  if (inicio >= 5000 && inicio <= 5999) return 'Bragança';
  if (inicio >= 6000 && inicio <= 6999) return 'Castelo Branco';
  if (inicio >= 7000 && inicio <= 7999) return 'Évora';
  if (inicio >= 8000 && inicio <= 8999) return 'Faro';
  return 'Desconhecido';
};

const gerarProdutosJson = async (dados) => {
  const marcaIdx = props.mappedColumns.indexOf('Marca');
  const modeloIdx = props.mappedColumns.indexOf('Modelo');
  const versaoIdx = props.mappedColumns.indexOf('Versao');
  const gamaIdx = props.mappedColumns.indexOf('Gama'); // opcional

  if (marcaIdx === -1 || modeloIdx === -1) return;

  const produtos = [];
  const mapa = {};
  let produtoId = 1;

  dados.forEach(row => {
    const marca = row['Marca'] || row[props.mappedColumns[marcaIdx]];
    const modelo = row['Modelo'] || row[props.mappedColumns[modeloIdx]];
    const versao = versaoIdx !== -1 ? (row['Versao'] || row[props.mappedColumns[versaoIdx]] || '') : '';
    const gama = gamaIdx !== -1 ? (row['Gama'] || row[props.mappedColumns[gamaIdx]] || '') : '';

    const chave = `${marca}_${modelo}_${versao}`;
    if (!mapa[chave]) {
      const nome = `${marca} ${modelo} ${versao}`.trim();
      mapa[chave] = produtoId++;
      produtos.push({
        ProdutoID: mapa[chave],
        NomeProduto: nome,
        Marca: marca,
        Modelo: modelo,
        Versao: versao,
        Gama: gama,
        Categoria: 'Desconhecida'
      });
    }

    row['ProdutoID'] = mapa[chave]; // Anexar ao objeto de venda
  });

  try {
    await axios.post('/import/mapped-data', {
      table_name: 'produtos.json',
      data: produtos,
      types: [],
      company_id: props.companyId,
    }, {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    });
    toast.success(`Foram extraídos ${produtos.length} produtos únicos.`);
  } catch (e) {
    console.error('Erro ao guardar produtos.json:', e);
    toast.error('Erro ao guardar produtos extraídos.');
  }
};

const processar = async () => {
  try {
    const index = (nome) => props.mappedColumns.indexOf(nome);

    const dataAberturaIdx = index('Data abertura');
    const dataNascimentoIdx = index('Data nascimento');
    const dataFaturaIdx = index('data fatura');
    const codigoPostalIdx = index('Distrito postal');

    const novosDados = props.rows.map(row => {
      const obj = {};

      props.mappedColumns.forEach((colName, i) => {
        let valor = row[i];

        if ([dataNascimentoIdx, dataAberturaIdx, dataFaturaIdx].includes(i)) {
          valor = normalizarDataExcel(valor);
        }

        obj[colName] = valor;
      });

      if (codigoPostalIdx !== -1) {
        const cp = row[codigoPostalIdx];
        obj.Regiao = codigoPostalParaRegiao(cp);
      }

      return obj;
    });

    if (props.fileType === 'vendas') {
      await gerarProdutosJson(novosDados);
    }

    emit('normalizado', novosDados);
    toast.success('Dados normalizados com sucesso.');
  } catch (err) {
    console.error('Erro ao normalizar dados:', err);
    toast.error('Erro na normalização.');
    emit('erro');
  }
};

onMounted(processar);
</script>