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

        # Normalizar nomes de colunas
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

        # --- Tentar detectar uma coluna de data automaticamente ---
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
                        coluna_data: "Recency",
                        "ClienteID": "Frequency",
                        "ValorTotal": "Monetary",
                    }
                )
            )
        else:
            print(
                "⚠️ Nenhuma coluna de data válida encontrada. Cálculo de Recência será ignorado."
            )
            rfm = (
                df.groupby("ClienteID")
                .agg({"ClienteID": "count", "ValorTotal": "sum"})
                .rename(columns={"ClienteID": "Frequency", "ValorTotal": "Monetary"})
            )

        rfm = rfm[rfm["Monetary"] > 0]
        rfm_log = np.log1p(rfm)
        scaler = StandardScaler()
        rfm_scaled = scaler.fit_transform(rfm_log)

        n_clusters = min(4, len(rfm_scaled))
        if n_clusters < 2:
            print("Dados insuficientes para clustering.")
            return

        kmeans = KMeans(n_clusters=n_clusters, random_state=42)
        rfm["Cluster"] = kmeans.fit_predict(rfm_scaled)

        # Classificação dos clusters
        if usar_recency:
            resumo = rfm.groupby("Cluster")[["Recency", "Frequency", "Monetary"]].mean()
        else:
            resumo = rfm.groupby("Cluster")[["Frequency", "Monetary"]].mean()

        cluster_labels = {}
        for cluster_id in resumo.index:
            if usar_recency:
                r, f, m = resumo.loc[cluster_id]
                if (
                    r < resumo["Recency"].median()
                    and f > resumo["Frequency"].median()
                    and m > resumo["Monetary"].median()
                ):
                    nome = "Top Customers"
                elif (
                    r > resumo["Recency"].median()
                    and f < resumo["Frequency"].median()
                    and m < resumo["Monetary"].median()
                ):
                    nome = "Lost Customers"
                elif m > resumo["Monetary"].median():
                    nome = "Medium Value Customers"
                else:
                    nome = "Low Value Customers"
            else:
                f, m = resumo.loc[cluster_id]
                if f > resumo["Frequency"].median() and m > resumo["Monetary"].median():
                    nome = "Top Customers"
                elif (
                    f < resumo["Frequency"].median() and m < resumo["Monetary"].median()
                ):
                    nome = "Lost Customers"
                elif m > resumo["Monetary"].median():
                    nome = "Medium Value Customers"
                else:
                    nome = "Low Value Customers"
            cluster_labels[cluster_id] = nome

        rfm["Segmento"] = rfm["Cluster"].map(cluster_labels)

        # Agrupamento final
        group_cols = {
            "Frequency": "mean",
            "Monetary": ["mean", "sum", "count"],
        }
        if usar_recency:
            group_cols["Recency"] = "mean"

        agrupado = rfm.groupby(["Cluster", "Segmento"]).agg(group_cols).reset_index()

        # Flatten MultiIndex columns
        flat_cols = ["Cluster", "Segmento"]
        if usar_recency:
            flat_cols += [
                "FrequencyMedia",
                "MonetaryMedia",
                "MonetaryTotal",
                "QtdClientes",
                "RecencyMedia",
            ]
        else:
            flat_cols += [
                "FrequencyMedia",
                "MonetaryMedia",
                "MonetaryTotal",
                "QtdClientes",
            ]

        agrupado.columns = flat_cols
        agrupado = agrupado.sort_values(by="MonetaryTotal", ascending=False)

        # Guardar ficheiro
        output_file = campanha_path / "resultados_rfm.json"

        descricao_texto = (
            f"Segmentação {'RFM' if usar_recency else 'FM'} com {n_clusters} clusters."
        )
        if not usar_recency:
            descricao_texto += " O campo 'Recency' (data da última compra) não foi encontrado nos dados e foi ignorado."

        resultado = {
            "descricao": descricao_texto,
            "dados": agrupado.to_dict(orient="records"),
        }

        with open(output_file, "w", encoding="utf-8") as f:
            json.dump(resultado, f, indent=2, ensure_ascii=False)

        print(f"✅ Ficheiro salvo em: {output_file}")

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
