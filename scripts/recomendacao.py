#!/usr/bin/env python3
# recomendacao.py

import sys
import json
import warnings
from pathlib import Path
import pandas as pd
from mlxtend.frequent_patterns import apriori, association_rules

warnings.filterwarnings("ignore")

def detectar_ficheiros_vendas(dados_path: Path):
    return list(dados_path.glob("*vendas*.json"))

def mapear_colunas(df: pd.DataFrame):
    df = df.loc[:, ~df.columns.duplicated()]  #  Remove colunas duplicadas
    cols = {c: c for c in df.columns}
    for c in df.columns:
        lc = c.lower()
        if "cliente" in lc and "id" in lc:
            cols[c] = "ClienteID"
        if "produto" in lc and "id" in lc:
            cols[c] = "ProdutoID"
    return df.rename(columns=cols)

def ler_vendas(dados_path: Path) -> pd.DataFrame:
    files = detectar_ficheiros_vendas(dados_path)
    if not files:
        print(" Nenhum ficheiro de vendas encontrado em", dados_path)
        sys.exit(1)
    dfs = []
    for fp in files:
        try:
            df = pd.read_json(fp)
            dfs.append(df)
        except Exception as e:
            print(f"[WARN] falha ao ler {fp.name}: {e}")
    if not dfs:
        print(" Todos os ficheiros de vendas falharam ao ler.")
        sys.exit(1)
    df = pd.concat(dfs, ignore_index=True)
    return mapear_colunas(df)

def ler_produtos(dados_path: Path) -> pd.DataFrame:
    fp = next(dados_path.glob("*produtos*.json"), None)
    if not fp:
        print(" produtos.json não encontrado em", dados_path)
        sys.exit(1)
    try:
        df = pd.read_json(fp)
        return mapear_colunas(df)
    except Exception as e:
        print(f" falha ao ler {fp.name}: {e}")
        sys.exit(1)

def preparar_basket(df: pd.DataFrame, chave: str, item: str) -> pd.DataFrame:
    mat = df.groupby([chave, item]).size().unstack(fill_value=0)
    return (mat > 0).astype(int)

def gerar_regras(basket: pd.DataFrame, min_support: float, min_confidence: float) -> pd.DataFrame:
    itemsets = apriori(basket, min_support=min_support, use_colnames=True)
    if itemsets.empty:
        return pd.DataFrame()
    rules = association_rules(itemsets, metric="confidence", min_threshold=min_confidence)
    return rules.sort_values(["confidence", "lift"], ascending=False)

def export_json(obj, path: Path):
    with open(path, "w", encoding="utf-8") as f:
        json.dump(obj, f, indent=2, ensure_ascii=False)

def cross_selling(base_path: str, empresa_id: int, campanha_id: int,
                  min_support=0.005, min_confidence=0.2):
    base = Path(base_path) / f"empresa_id_{empresa_id}"
    dados = base / "dados_importados"
    saida = base / "campanhas" / f"campanha_id_{campanha_id}"
    saida.mkdir(parents=True, exist_ok=True)

    # 1. Leitura e mapeamento
    vendas = ler_vendas(dados)
    produtos = ler_produtos(dados)

    # --- DEBUG ---
    print(" Colunas vendas:", vendas.columns.tolist())
    print(" Colunas produtos:", produtos.columns.tolist())

    print(" Tipos ProdutoID:")
    print("  - vendas:", vendas["ProdutoID"].dtype)
    print("  - produtos:", produtos["ProdutoID"].dtype)

    print(" Exemplo ProdutoID em vendas:", vendas["ProdutoID"].head(3).tolist())
    print(" Exemplo ProdutoID em produtos:", produtos["ProdutoID"].head(3).tolist())

    print(" Clientes únicos:", vendas["ClienteID"].nunique())
    print(" Total linhas vendas:", len(vendas))
    # -------------

    if not {"ClienteID", "ProdutoID"}.issubset(vendas.columns):
        print(" Coluna ClienteID ou ProdutoID ausente em vendas.")
        sys.exit(1)
    if "ProdutoID" not in produtos.columns:
        print(" Coluna ProdutoID ausente em produtos.")
        sys.exit(1)

    # Força os tipos para string para garantir merge correto
    vendas["ProdutoID"] = vendas["ProdutoID"].astype(str)
    produtos["ProdutoID"] = produtos["ProdutoID"].astype(str)

    # 2. Merge com produtos válidos
    produtos = produtos.drop_duplicates(subset="ProdutoID")  #  evita problemas de join
    df = vendas.merge(produtos, on="ProdutoID", how="left")
    if df["ProdutoID"].isna().all():
        print(" Nenhuma correspondência entre vendas e produtos.")
        sys.exit(1)

    # 3. Produto → Produto
    basket_prod = preparar_basket(df, "ClienteID", "ProdutoID")
    rules_prod = gerar_regras(basket_prod, min_support, min_confidence)

    # out_prod = []
    # if not rules_prod.empty:
    #     dfp = rules_prod[['antecedents', 'consequents', 'support', 'confidence', 'lift']].copy()
    #     dfp['antecedents'] = dfp['antecedents'].apply(list)
    #     dfp['consequents'] = dfp['consequents'].apply(list)
    #     out_prod = dfp.round(3).to_dict(orient='records')
    out_prod = []
    if not rules_prod.empty:
        dfp = rules_prod[['antecedents', 'consequents', 'support', 'confidence', 'lift']].copy()
        dfp['antecedents'] = dfp['antecedents'].apply(list)
        dfp['consequents'] = dfp['consequents'].apply(list)
        dfp = dfp.round(3)

    # Criar mapa ProdutoID → NomeProduto
    produto_map = dict(zip(produtos["ProdutoID"], produtos["NomeProduto"]))

    def substituir_ids_por_nomes(lst):
        return [produto_map.get(pid, pid) for pid in lst]

    dfp['antecedents'] = dfp['antecedents'].apply(substituir_ids_por_nomes)
    dfp['consequents'] = dfp['consequents'].apply(substituir_ids_por_nomes)

    out_prod = dfp.to_dict(orient='records')



    export_json(out_prod, saida / "recomendacoes_produto.json")
    print(f" produtos: {len(out_prod)} regras exportadas.")

    # 4. Atributos categóricos
    cat_cols = [c for c in df.select_dtypes(include="object").columns
                if c not in ("ClienteID", "ProdutoID") and df[c].nunique() > 1]
    attr_out = {}
    for col in cat_cols:
        basket = preparar_basket(df, "ClienteID", col)
        rules = gerar_regras(basket, min_support, min_confidence)
        arr = []
        if not rules.empty:
            dfa = rules[['antecedents', 'consequents', 'support', 'confidence', 'lift']].copy()
            dfa['antecedents'] = dfa['antecedents'].apply(list)
            dfa['consequents'] = dfa['consequents'].apply(list)
            arr = dfa.round(3).to_dict(orient='records')
        attr_out[col] = arr
        print(f" {col}: {len(arr)} regras.")

    export_json(attr_out, saida / "recomendacoes_attr.json")

if __name__ == "__main__":
    try:
        print("Script recomendacao.py iniciado.")
        print(f"Argumentos recebidos: {sys.argv}")

        if len(sys.argv) < 3:
            print("Uso: python recomendacao.py <empresa_id> <campanha_id> [base_path] [min_sup] [min_conf]")
            sys.exit(1)

        empresa = int(sys.argv[1])
        campanha = int(sys.argv[2])
        base_dir = sys.argv[3] if len(sys.argv) > 3 else "dados_smart_crm"
        min_sup = float(sys.argv[4]) if len(sys.argv) > 4 else 0.005
        min_conf = float(sys.argv[5]) if len(sys.argv) > 5 else 0.2

        cross_selling(base_dir, empresa, campanha, min_sup, min_conf)
        print("Script terminado com sucesso.")
    except Exception as e:
        print(f"[ERRO AO INICIAR SCRIPT]: {e}")
