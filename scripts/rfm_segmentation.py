from pathlib import Path
import pandas as pd
import numpy as np
from sklearn.preprocessing import StandardScaler
from sklearn.cluster import KMeans
import json
import sys
from datetime import datetime
import re
from kneed import KneeLocator
import warnings
from sklearn.decomposition import PCA

warnings.filterwarnings("ignore", category=UserWarning, module="joblib")


def detectar_info_ficheiro(nome_arquivo):
    nome_base = Path(nome_arquivo).stem.lower()
    meses = {
        "jan": 1, "fev": 2, "mar": 3, "abr": 4, "mai": 5, "jun": 6,
        "jul": 7, "ago": 8, "set": 9, "out": 10, "nov": 11, "dez": 12,
    }

    tipo = None
    for t in ["vendas", "clientes", "produtos"]:
        if t in nome_base:
            tipo = t
            break

    atualizar = nome_base.endswith("_novo")

    padrao = re.search(
        r"(jan|fev|mar|abr|mai|jun|jul|ago|set|out|nov|dez)?(?:[-_]?)(jan|fev|mar|abr|mai|jun|jul|ago|set|out|nov|dez)?(\d{4})",
        nome_base,
    )

    meses_intervalo, ano = None, None
    if padrao:
        m1, m2, a = padrao.groups()
        ano = int(a)
        if m1 and m2:
            meses_intervalo = (meses[m1], meses[m2])
        elif m1:
            meses_intervalo = (meses[m1], meses[m1])

    return {
        "tipo": tipo or "desconhecido",
        "meses": meses_intervalo,
        "ano": ano,
        "atualizacao": atualizar,
    }


def carregar_dados_vendas(dados_path):
    vendas_files = [f for f in dados_path.glob("*.json") if "vendas" in f.stem.lower()]
    df_list = []

    for file_path in vendas_files:
        try:
            df_temp = pd.read_json(file_path)
            df_list.append(df_temp)
        except Exception as e:
            print(f"Erro ao ler {file_path.name}: {e}")
    
    return pd.concat(df_list, ignore_index=True) if df_list else None


def identificar_coluna_data(df):
    padroes_possiveis = ["data", "compra", "venda", "registro", "registo"]
    for col in df.columns:
        amostra = df[col].dropna().head(10).astype(str)
        if any(p in col.lower() for p in padroes_possiveis):
            try:
                convertida = pd.to_datetime(amostra, errors="coerce")
                if convertida.notnull().all():
                    return col
            except Exception:
                continue
    return None


def calcular_rfm(df, coluna_data):
    if coluna_data:
        df[coluna_data] = pd.to_datetime(df[coluna_data])
        referencia_data = df[coluna_data].max() + pd.Timedelta(days=1)
        rfm = df.groupby("ClienteID").agg({
            coluna_data: lambda x: (referencia_data - x.max()).days,
            "ClienteID": "count",
            "ValorTotal": "sum",
        }).rename(columns={
            coluna_data: "Recência",
            "ClienteID": "Frequência",
            "ValorTotal": "Monetário",
        })
    else:
        rfm = df.groupby("ClienteID").agg({
            "ClienteID": "count",
            "ValorTotal": "sum",
        }).rename(columns={
            "ClienteID": "Frequência",
            "ValorTotal": "Monetário",
        })
    return rfm[rfm["Monetário"] > 0]


def escolher_modelo_cluster(rfm_scaled):
    sse = []
    k_max = min(10, len(rfm_scaled))
    for k in range(1, k_max + 1):
        kmeans = KMeans(n_clusters=k, random_state=42).fit(rfm_scaled)
        sse.append(kmeans.inertia_)
    if len(sse) < 3:
        return 2, "KMeans"
    knee = KneeLocator(range(1, k_max + 1), sse, curve="convex", direction="decreasing")
    k = knee.knee or 2
    kmeans = KMeans(n_clusters=k, random_state=42).fit(rfm_scaled)
    return kmeans.labels_, "KMeans"


def nomear_clusters(rfm, colunas_existentes):
    resumo = rfm[colunas_existentes + ["Cluster"]].groupby("Cluster").mean()
    resumo_norm = (resumo - resumo.mean()) / resumo.std()
    resumo_norm["score"] = 0.7 * resumo_norm.get("Monetário", 0) - 0.3 * resumo_norm.get("Recência", 0)
    ordenados = resumo_norm.sort_values(by="score", ascending=False).reset_index()
    nomes_padrao = [
        "Campeões", "Clientes Valiosos", "Clientes Regulares", "Em Risco",
        "Clientes Perdidos", "Pouca Frequência", "Baixo Valor", "Inativos"
    ]
    return {
        row["Cluster"]: (nomes_padrao[i] if i < len(nomes_padrao) else f"Segmento {i+1}")
        for i, row in ordenados.iterrows()
    }


def juntar_nomes_clientes(rfm, dados_path):
    clientes_path = dados_path / "clientes.json"
    if clientes_path.exists():
        try:
            df_clientes = pd.read_json(clientes_path)
            df_clientes = df_clientes.rename(columns={"IDCliente": "ClienteID", "cliente_id": "ClienteID"})
            if "Nome" in df_clientes.columns:
                return rfm.reset_index().merge(df_clientes[["ClienteID", "Nome"]], on="ClienteID", how="left")
        except Exception as e:
            print(f"Erro ao ler clientes.json para nomes: {e}")
    return rfm.reset_index()


def salvar_resultados(rfm, agrupado, campanha_path, usar_recency, metodo_usado):
    resultados_path = campanha_path / "resultados_rfm.json"
    clientes_path = campanha_path / "clientes_segmentados_rfm.json"

    descricao_texto = f"A Segmentação {'RFM' if usar_recency else 'FM'} resultou em {agrupado['Cluster'].nunique()} clusters utilizando o algoritmo {metodo_usado}."
    if not usar_recency:
        descricao_texto += " O campo 'Recência' (data da última compra) não foi encontrado, portanto foi ignorado."

    with open(resultados_path, "w", encoding="utf-8") as f:
        json.dump({
            "descricao": descricao_texto,
            "dados": agrupado.to_dict(orient="records")
        }, f, indent=2, ensure_ascii=False)

    with open(clientes_path, "w", encoding="utf-8") as f:
        json.dump(rfm.to_dict(orient="records"), f, indent=2, ensure_ascii=False)

    print(f" Ficheiros gerados:\n- {resultados_path}\n- {clientes_path}")


def gerar_clusters_json_com_pontos(rfm, rfm_scaled, campanha_path):
    try:
        pca = PCA(n_components=2)
        pontos = pca.fit_transform(rfm_scaled)

        # Normalizar para intervalo [0, 1]
        x_vals = pontos[:, 0]
        y_vals = pontos[:, 1]

        x_min, x_max = x_vals.min(), x_vals.max()
        y_min, y_max = y_vals.min(), y_vals.max()

        x_norm = (x_vals - x_min) / (x_max - x_min)
        y_norm = (y_vals - y_min) / (y_max - y_min)

        dados_clientes = rfm.copy()
        dados_clientes["x"] = x_norm
        dados_clientes["y"] = y_norm

        colunas_exportar = [
            "ClienteID", "Nome", "Cluster", "Segmento",
            "Recência", "Frequência", "Monetário", "x", "y"
        ]
        if all(c in dados_clientes.columns for c in colunas_exportar):
            dados_clientes = dados_clientes[colunas_exportar]

        clusters_json_path = campanha_path / "clusters.json"
        with open(clusters_json_path, "w", encoding="utf-8") as f:
            json.dump(dados_clientes.to_dict(orient="records"), f, indent=2, ensure_ascii=False)

        print(" Ficheiro clusters.json criado com x e y normalizados entre 0 e 1.")

    except Exception as e:
        print(f"[ERRO ao gerar clusters.json]: {e}")




def gerar_clusters_por_regiao(base_path, empresa_id, campanha_id):
    try:
        base_empresa = Path(base_path) / f"empresa_id_{empresa_id}"
        dados_path = base_empresa / "dados_importados"
        campanha_path = base_empresa / "campanhas" / f"campanha_id_{campanha_id}"
        campanha_path.mkdir(parents=True, exist_ok=True)

        # Carregar ficheiros
        vendas_files = [f for f in dados_path.glob("*.json") if "vendas" in f.stem.lower()]
        produtos_file = next((f for f in dados_path.glob("*.json") if "produto" in f.stem.lower()), None)
        clientes_file = next((f for f in dados_path.glob("*.json") if "cliente" in f.stem.lower()), None)
        clientes_segmentados_path = campanha_path / "clientes_segmentados_rfm.json"

        if not vendas_files or not produtos_file or not clientes_file or not clientes_segmentados_path.exists():
            print("[ERRO]: Faltam ficheiros de vendas, produtos, clientes ou segmentos.")
            return

        df_vendas = pd.concat([pd.read_json(f) for f in vendas_files], ignore_index=True)
        df_produtos = pd.read_json(produtos_file)
        df_clientes = pd.read_json(clientes_file)
        df_segmentos = pd.read_json(clientes_segmentados_path)

        # Normalizar nomes de colunas para merge
        df_clientes = df_clientes.rename(columns={
            "cliente_id": "ClienteID", "IDCliente": "ClienteID",
            "Localidade": "Regiao", "Cidade": "Regiao", "Distrito": "Regiao"
        })
        if "Regiao" not in df_clientes.columns:
            for alt_col in ["Localidade", "Cidade", "Distrito"]:
                if alt_col in df_clientes.columns:
                    df_clientes["Regiao"] = df_clientes[alt_col]
                    break
        df_clientes = df_clientes[["ClienteID", "Regiao"]]

        # Juntar dados principais
        df = df_vendas.merge(df_clientes, on="ClienteID", how="left").merge(df_produtos, on="ProdutoID", how="left")
        if df.empty or "Regiao" not in df.columns:
            print("[ERRO]: Nenhuma região encontrada.")
            return

        df["ValorTotal"] = df["Quantidade"] * df["PreçoUnitário"]
        agrupado = (
            df.groupby(["Regiao", "ProdutoID", "NomeProduto", "Categoria", "Marca"])
            .agg({"Quantidade": "sum", "ValorTotal": "sum"})
            .reset_index()
        )

        melhor_produto_regiao = (
            agrupado.sort_values("ValorTotal", ascending=False)
            .groupby("Regiao")
            .first()
            .reset_index()
        )

        # Contar segmentos por região
        if "Regiao" not in df_segmentos.columns:
            df_segmentos = df_segmentos.merge(df_clientes, on="ClienteID", how="left")

        contagens = df_segmentos.groupby(["Regiao", "Segmento"]).size().unstack(fill_value=0).reset_index()

        # Merge e formatação final
        final = melhor_produto_regiao.merge(contagens, on="Regiao", how="left").fillna(0)
        final = final.round(2)
        final = final.rename(columns={
            "NomeProduto": "ProdutoMaisComprado",
            "Quantidade": "QuantidadeTotal",
            "ValorTotal": "ValorTotal"
        })

        # Salvar
        clusters_path = campanha_path / "clusters_por_regiao.json"
        with open(clusters_path, "w", encoding="utf-8") as f:
            json.dump(final.to_dict(orient="records"), f, indent=2, ensure_ascii=False)

        print("Ficheiro clusters_por_regiao.json atualizado com segmentos por região.")

    except Exception as e:
        print(f"[ERRO ao gerar clusters_por_regiao.json]: {e}")



def rfm_segmentation(base_path, empresa_id, campanha_id):
    campanha_path = Path(base_path) / f"empresa_id_{empresa_id}" / "campanhas" / f"campanha_id_{campanha_id}"
    campanha_path.mkdir(parents=True, exist_ok=True)
    dados_path = Path(base_path) / f"empresa_id_{empresa_id}" / "dados_importados"

    print("Início da segmentação RFM...")
    df = carregar_dados_vendas(dados_path)
    if df is None or df.empty:
        print("Nenhuma tabela de vendas válida foi lida.")
        return

    col_renames = {
        "cliente_id": "ClienteID", "IDCliente": "ClienteID",
        "Total": "ValorTotal", "valor_total": "ValorTotal",
        "Data": "DataCompra", "data_compra": "DataCompra",
    }
    df = df.rename(columns={k: v for k, v in col_renames.items() if k in df.columns})

    if not {"ClienteID", "ValorTotal"}.issubset(df.columns):
        print("Colunas essenciais em falta (ClienteID ou ValorTotal).")
        return

    coluna_data = identificar_coluna_data(df)
    usar_recency = coluna_data is not None

    rfm = calcular_rfm(df, coluna_data)
    rfm_log = np.log1p(rfm)
    rfm_scaled = StandardScaler().fit_transform(rfm_log)

    cluster_ids, metodo_usado = escolher_modelo_cluster(rfm_scaled)
    rfm["Cluster"] = cluster_ids

    colunas_rfm = ["Recência", "Frequência", "Monetário"]
    colunas_existentes = [col for col in colunas_rfm if col in rfm.columns]

    if not colunas_existentes:
        print("[ERRO]: Nenhuma coluna RFM disponível para nomear clusters.")
        return

    nomes_clusters = nomear_clusters(rfm, colunas_existentes)
    rfm["Segmento"] = rfm["Cluster"].map(nomes_clusters)

    rfm = juntar_nomes_clientes(rfm, dados_path)

    agrupado = rfm.groupby(["Cluster", "Segmento"]).agg({
        "Recência": "mean",
        "Frequência": "mean",
        "Monetário": ["mean", "sum", "count"]
    }).reset_index()

    agrupado.columns = [
        "Cluster", "Segmento", "Recência Média", "Frequência Média",
        "Média Monetária", "Total Monetário", "Quantidade de Compras"
    ]

    agrupado = agrupado.sort_values(by="Total Monetário", ascending=False).replace([np.inf, -np.inf], np.nan).fillna(0).round(2)
    rfm = rfm.replace([np.inf, -np.inf], np.nan).fillna(0).round(2)

    salvar_resultados(rfm, agrupado, campanha_path, usar_recency, metodo_usado)
    gerar_clusters_json_com_pontos(rfm, rfm_scaled, campanha_path)
    gerar_clusters_por_regiao(base_path, empresa_id, campanha_id)




if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Parâmetros ausentes. Usa: python rfm_segmentation.py <empresa_id> <campanha_id> [base_path]")
        sys.exit(1)

    try:
        empresa_id = int(sys.argv[1])
        campanha_id = int(sys.argv[2])
        base_path = sys.argv[3] if len(sys.argv) > 3 else "C:/Users/Admin/Desktop/SmartCRM/dados_smart_crm"
        rfm_segmentation(base_path, empresa_id, campanha_id)
    except Exception as erro:
        print(f"[ERRO AO INICIAR SCRIPT]: {erro}")


