import pandas as pd
import numpy as np
from mlxtend.frequent_patterns import apriori, association_rules
import json, sys
from pathlib import Path

def mapear_colunas(df, mapping=None):
    # mapeamento default de sinónimos para as colunas-chave
    default = {
        "cliente_id": "ClienteID", "idcliente": "ClienteID", "IDCliente": "ClienteID",
        "produto_id": "ProdutoID","idproduto": "ProdutoID","produto": "ProdutoID",
    }
    if mapping: default.update(mapping)
    df.columns = [c.strip() for c in df.columns]
    return df.rename(columns={k:v for k,v in default.items() if k in df.columns})

def extrair_categoricas(df):
    # tudo o que for object / string exceto ClienteID, ProdutoID
    excl = {"ClienteID","ProdutoID"}
    cat = [c for c in df.select_dtypes(include="object").columns if c not in excl]
    return [c for c in cat if df[c].nunique()>1]

def gerar_regras(basket, min_support, min_confidence):
    itemsets = apriori(basket, min_support=min_support, use_colnames=True)
    if itemsets.empty: return pd.DataFrame()
    rules = association_rules(itemsets, metric="confidence", min_threshold=min_confidence)
    return rules.sort_values(["confidence","lift"], ascending=False)

def prepara_basket(df, col_chave, col_item):
    # cria matriz binária ClienteID x col_item
    mat = df.groupby([col_chave, col_item]).size().unstack(fill_value=0)
    return mat.applymap(lambda x: 1 if x>0 else 0)

def cross_selling_recommendation_flexivel(base_path, empresa_id, campanha_id,
                                          min_support=0.05, min_confidence=0.3):
    base = Path(base_path)/f"empresa_id_{empresa_id}"/"dados_importados"
    vendas = base/"vendas.json"
    if not vendas.exists():
        print("❌ vendas.json não encontrado."); return

    df = pd.read_json(vendas)
    df = mapear_colunas(df)
    if not {"ClienteID","ProdutoID"}.issubset(df.columns):
        print("❌ ClienteID e ProdutoID obrigatórios."); return

    # deteta colunas categóricas extra (ex: Categoria, Canal, Sexo)
    extras = extrair_categoricas(df)

    # dicionário para armazenar resultados
    resultados = {}

    # 1) Recomendação produto→produto
    basket_prod = prepara_basket(df, "ClienteID", "ProdutoID")
    regras_prod = gerar_regras(basket_prod, min_support, min_confidence)
    resultados["produto"] = regras_prod

    # 2) Para cada extra categórica, produto→categoria e category→category
    for col in extras:
        # Cliente → valor de col
        basket_cat = prepara_basket(df, "ClienteID", col)
        resultados[col] = gerar_regras(basket_cat, min_support, min_confidence)

    # criar pasta de saída
    out_dir = Path(base_path)/f"empresa_id_{empresa_id}"/"campanhas"/f"campanha_id_{campanha_id}"
    out_dir.mkdir(parents=True, exist_ok=True)

    # exportar cada DataFrame de regras para JSON
    for key, rules in resultados.items():
        fname = f"recomendacoes_{key}.json"
        if rules.empty:
            print(f"⚠️ Sem regras para '{key}'.")
            continue

        df_out = rules[['antecedents','consequents','support','confidence','lift']].copy()
        df_out['antecedents'] = df_out['antecedents'].apply(lambda x: list(x))
        df_out['consequents'] = df_out['consequents'].apply(lambda x: list(x))
        df_out = df_out.round(3)

        with open(out_dir/fname, "w", encoding="utf-8") as f:
            json.dump(df_out.to_dict(orient="records"), f, indent=2, ensure_ascii=False)
        print(f"✅ Regras exportadas: {fname}")

if __name__ == "__main__":
    if len(sys.argv)<3:
        print("Uso: python cross_selling_recommendation_flexivel.py <empresa_id> <campanha_id> [base_path]")
        sys.exit(1)
    emp = int(sys.argv[1]); camp = int(sys.argv[2])
    bp = sys.argv[3] if len(sys.argv)>3 else "dados_smart_crm"
    cross_selling_recommendation_flexivel(bp, emp, camp)
