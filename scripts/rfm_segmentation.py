from pathlib import Path
import pandas as pd
import numpy as np
from sklearn.preprocessing import StandardScaler
from sklearn.cluster import KMeans
import json
import sys
from datetime import datetime


def rfm_segmentation(base_path, empresa_id, campanha_id):
    campanha_path = (
        Path(base_path)
        / f"empresa_id_{empresa_id}"
        / "campanhas"
        / f"campanha_id_{campanha_id}"
    )
    campanha_path.mkdir(parents=True, exist_ok=True)

    try:
        print("Início da segmentação RFM...")

        dados_path = Path(base_path) / f"empresa_id_{empresa_id}" / "dados_importados"
        file_path = dados_path / "vendas.json"
        if not file_path.exists():
            print("Ficheiro vendas.json em falta.")
            return

        df = pd.read_json(file_path)

        col_renames = {
            "cliente_id": "ClienteID",
            "IDCliente": "ClienteID",
            "Total": "ValorTotal",
            "valor_total": "ValorTotal",
            "Data": "DataCompra",
            "data_compra": "DataCompra",
        }
        df = df.rename(
            columns={k: v for k, v in col_renames.items() if k in df.columns}
        )

        if not {"ClienteID", "ValorTotal"}.issubset(df.columns):
            print("Colunas essenciais em falta (ClienteID ou ValorTotal).")
            return

        coluna_data = None
        padroes_possiveis = ["data", "compra", "venda", "registro", "registo"]
        for col in df.columns:
            amostra = df[col].dropna().head(10).astype(str)
            if any(any(p in col.lower() for p in padroes_possiveis) for _ in amostra):
                try:
                    convertida = pd.to_datetime(amostra, errors="coerce")
                    if convertida.notnull().all():
                        coluna_data = col
                        break
                except Exception:
                    continue

        usar_recency = coluna_data is not None

        if usar_recency:
            print(f"Coluna de data detetada automaticamente: {coluna_data}")
            df[coluna_data] = pd.to_datetime(df[coluna_data])
            referencia_data = df[coluna_data].max() + pd.Timedelta(days=1)
            rfm = (
                df.groupby("ClienteID")
                .agg(
                    {
                        coluna_data: lambda x: (referencia_data - x.max()).days,
                        "ClienteID": "count",
                        "ValorTotal": "sum",
                    }
                )
                .rename(
                    columns={
                        coluna_data: "Recência",
                        "ClienteID": "Frequência",
                        "ValorTotal": "Monetário",
                    }
                )
            )
        else:
            print("⚠️ Nenhuma coluna de data válida encontrada. Recência será ignorada.")
            rfm = (
                df.groupby("ClienteID")
                .agg(
                    {
                        "ClienteID": "count",
                        "ValorTotal": "sum",
                    }
                )
                .rename(
                    columns={
                        "ClienteID": "Frequência",
                        "ValorTotal": "Monetário",
                    }
                )
            )

        rfm = rfm[rfm["Monetário"] > 0]
        rfm_log = np.log1p(rfm)
        scaler = StandardScaler()
        rfm_scaled = scaler.fit_transform(rfm_log)

        n_clusters = min(4, len(rfm_scaled))
        if n_clusters < 2:
            print("Dados insuficientes para clustering.")
            return

        kmeans = KMeans(n_clusters=n_clusters, random_state=42)
        rfm["Cluster"] = kmeans.fit_predict(rfm_scaled)

        resumo = rfm.groupby("Cluster")[rfm.columns].mean()
        cluster_labels = {}
        for cluster_id in resumo.index:
            dados = resumo.loc[cluster_id]
            if usar_recency:
                r, f, m = dados["Recência"], dados["Frequência"], dados["Monetário"]
            else:
                f, m = dados["Frequência"], dados["Monetário"]
                r = None

            if (
                usar_recency
                and r < resumo["Recência"].median()
                and f > resumo["Frequência"].median()
                and m > resumo["Monetário"].median()
            ):
                nome = "Clientes Principais"
            elif (
                usar_recency
                and r > resumo["Recência"].median()
                and f < resumo["Frequência"].median()
                and m < resumo["Monetário"].median()
            ):
                nome = "Clientes Perdidos"
            elif m > resumo["Monetário"].median():
                nome = "Clientes Valiosos"
            else:
                nome = "Clientes com Baixo Valor"

            cluster_labels[cluster_id] = nome

        rfm["Segmento"] = rfm["Cluster"].map(cluster_labels)

        clientes_path = dados_path / "clientes.json"
        if clientes_path.exists():
            try:
                df_clientes = pd.read_json(clientes_path)
                df_clientes = df_clientes.rename(
                    columns={"IDCliente": "ClienteID", "cliente_id": "ClienteID"}
                )
                if "Nome" in df_clientes.columns:
                    rfm = rfm.reset_index().merge(
                        df_clientes[["ClienteID", "Nome"]], on="ClienteID", how="left"
                    )
            except Exception as e:
                print(f"Erro ao ler clientes.json para nomes: {e}")

        group_cols = {
            "Frequência": ["mean", "median", "std", "min", "max"],
            "Monetário": ["mean", "sum", "median", "std", "min", "max", "count"],
        }
        if usar_recency:
            group_cols["Recência"] = ["mean", "median", "std", "min", "max"]

        agrupado = rfm.groupby(["Cluster", "Segmento"]).agg(group_cols).reset_index()
        agrupado.columns = [
            "_".join(filter(None, col)).strip("_") for col in agrupado.columns.values
        ]

        renomear_colunas = {
            "Frequência_mean": "Frequência Média",
            "Frequência_median": "Frequência Mediana",
            "Frequência_std": "Frequência Desvio-Padrão",
            "Frequência_min": "Frequência Mínima",
            "Frequência_max": "Frequência Máxima",
            "Monetário_mean": "Média Monetária",
            "Monetário_median": "Mediana Monetária",
            "Monetário_std": "Desvio Monetário",
            "Monetário_sum": "Total Monetário",
            "Monetário_min": "Mínimo Monetário",
            "Monetário_max": "Máximo Monetário",
            "Monetário_count": "Quantidade de Compras",
            "Recência_mean": "Recência Média",
            "Recência_median": "Recência Mediana",
            "Recência_std": "Recência Desvio-Padrão",
            "Recência_min": "Recência Mínima",
            "Recência_max": "Recência Máxima",
        }
        agrupado.rename(columns=renomear_colunas, inplace=True)

        agrupado = agrupado.sort_values(by="Total Monetário", ascending=False)
        agrupado = agrupado.replace([np.inf, -np.inf], np.nan).fillna(0)
        rfm_safe = rfm.replace([np.inf, -np.inf], np.nan).fillna(0)

        descricao_texto = (
            f"Segmentação {'RFM' if usar_recency else 'FM'} com {n_clusters} clusters."
        )
        if not usar_recency:
            descricao_texto += " O campo 'Recência' (data da última compra) não foi encontrado e foi ignorado."

        resultados_path = campanha_path / "resultados_rfm.json"
        clusters_path = campanha_path / "clusters_rfm.json"
        clientes_path = campanha_path / "clientes_segmentados_rfm.json"

        with open(resultados_path, "w", encoding="utf-8") as f:
            json.dump(
                {
                    "descricao": descricao_texto,
                    "dados": agrupado.to_dict(orient="records"),
                },
                f,
                indent=2,
                ensure_ascii=False,
            )

        with open(clusters_path, "w", encoding="utf-8") as f:
            json.dump(
                agrupado.to_dict(orient="records"), f, indent=2, ensure_ascii=False
            )

        with open(clientes_path, "w", encoding="utf-8") as f:
            json.dump(
                rfm_safe.to_dict(orient="records"), f, indent=2, ensure_ascii=False
            )

        print(
            f"✅ Ficheiros gerados:\n- {resultados_path}\n- {clusters_path}\n- {clientes_path}"
        )

    except Exception as e:
        print(f"[ERRO CRÍTICO]: {str(e)}")


if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(
            "Parâmetros ausentes. Usa: python rfm_segmentation.py <empresa_id> <campanha_id> [base_path]"
        )
        sys.exit(1)

    try:
        empresa_id = int(sys.argv[1])
        campanha_id = int(sys.argv[2])
        base_path = (
            sys.argv[3]
            if len(sys.argv) > 3
            else "C:/Users/Admin/Desktop/SmartCRM/dados_smart_crm"
        )
        rfm_segmentation(base_path, empresa_id, campanha_id)
    except Exception as erro:
        print(f"[ERRO AO INICIAR SCRIPT]: {erro}")
