from pathlib import Path
import pandas as pd
import numpy as np
from sklearn.preprocessing import StandardScaler
from sklearn.cluster import KMeans
import json
import sys
from datetime import datetime


def rfm_segmentation(base_path, empresa_id, campanha_id):
    campanha_path = Path(base_path) / f"empresa_id_{empresa_id}" / "campanhas" / f"campanha_id_{campanha_id}"
    campanha_path.mkdir(parents=True, exist_ok=True)

    try:
        print("Início da segmentação RFM...")

        dados_path = Path(base_path) / f"empresa_id_{empresa_id}" / "dados_importados"

        required_files = {
            'vendas.json': ['ClienteID', 'ValorTotal']
        }

        column_aliases = {
            'ClienteID': ['ClienteID', 'IDCliente', 'cliente_id'],
            'ValorTotal': ['ValorTotal', 'Total', 'valor_total'],
            'DataCompra': ['DataCompra', 'Data', 'data_compra']
        }

        dataframes = {}
        ficheiros_em_falta = []
        colunas_em_falta = {}

        for file, required_cols in required_files.items():
            file_path = dados_path / file
            if not file_path.exists():
                ficheiros_em_falta.append(file)
                continue

            df = pd.read_json(file_path)

            renamed_cols = {}
            for std_col, aliases in column_aliases.items():
                for alias in aliases:
                    if alias in df.columns:
                        renamed_cols[alias] = std_col
                        break

            df = df.rename(columns=renamed_cols)

            faltando = []
            for col in required_cols:
                if col not in df.columns:
                    faltando.append(col)

            if faltando:
                colunas_em_falta[file] = faltando
            else:
                dataframes[file] = df

        # ⚠️ Se faltar qualquer coisa, não continua
        if ficheiros_em_falta or colunas_em_falta:
            print("Ficheiros em falta:", ficheiros_em_falta)
            print("Colunas em falta:", colunas_em_falta)
            return

        vendas_df = dataframes['vendas.json']
        usar_recency = 'DataCompra' in vendas_df.columns

        if usar_recency:
            vendas_df['DataCompra'] = pd.to_datetime(vendas_df['DataCompra'])
            referencia_data = vendas_df['DataCompra'].max() + pd.Timedelta(days=1)
            print(f"Data de referência: {referencia_data}")
            rfm = vendas_df.groupby('ClienteID').agg({
                'DataCompra': lambda x: (referencia_data - x.max()).days,
                'ClienteID': 'count',
                'ValorTotal': 'sum'
            }).rename(columns={
                'DataCompra': 'Recency',
                'ClienteID': 'Frequency',
                'ValorTotal': 'Monetary'
            })
        else:
            print("Aviso: coluna 'DataCompra' em falta. Cálculo de Recência será ignorado.")
            rfm = vendas_df.groupby('ClienteID').agg({
                'ClienteID': 'count',
                'ValorTotal': 'sum'
            }).rename(columns={
                'ClienteID': 'Frequency',
                'ValorTotal': 'Monetary'
            })

        rfm = rfm[rfm['Monetary'] > 0]
        rfm_log = np.log1p(rfm)

        scaler = StandardScaler()
        rfm_scaled = scaler.fit_transform(rfm_log)

        n_clusters = min(4, len(rfm_scaled))
        if n_clusters < 2:
            print("Dados insuficientes para clustering.")
            return

        kmeans = KMeans(n_clusters=n_clusters, random_state=42)
        rfm['Cluster'] = kmeans.fit_predict(rfm_scaled)

        resumo = rfm.groupby('Cluster').agg({
            'Frequency': 'mean',
            'Monetary': ['mean', 'sum', 'count']
        })

        if usar_recency:
            recency_summary = rfm.groupby('Cluster')['Recency'].mean().rename("RecencyMedia")
            resumo.columns = ['FrequencyMedia', 'MonetaryMedia', 'MonetaryTotal', 'QtdClientes']
            resumo = resumo.reset_index().merge(recency_summary, on='Cluster')
        else:
            resumo.columns = ['FrequencyMedia', 'MonetaryMedia', 'MonetaryTotal', 'QtdClientes']
            resumo = resumo.reset_index()

        output_file = campanha_path / 'resultados_rfm.json'
        descricao_texto = (
            f"Segmentação RFM com {n_clusters} clusters."
            if usar_recency else
            f"Segmentação FM com {n_clusters} clusters (sem recência)."
        )

        resultado = {
            "descricao": descricao_texto,
            "dados": resumo.to_dict(orient="records")
        }

        print(f"A guardar ficheiro em: {output_file}")
        with open(output_file, "w", encoding="utf-8") as f:
            json.dump(resultado, f, indent=2, ensure_ascii=False)

        print("Ficheiro salvo com sucesso.")

    except Exception as e:
        print(f"[ERRO CRÍTICO]: {str(e)}")

# --- Execução via terminal ---
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
