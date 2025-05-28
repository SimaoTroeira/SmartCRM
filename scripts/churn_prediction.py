from pathlib import Path
import pandas as pd
import numpy as np
import json
import sys
from datetime import datetime


def calcular_dias(data_str):
    try:
        data = pd.to_datetime(data_str)
        return (datetime.today() - data).days
    except:
        return None


def churn_prediction(base_path, empresa_id, campanha_id):
    base_empresa = Path(base_path) / f"empresa_id_{empresa_id}"
    pesos = {"recencia": 0.25, "frequencia": 0.35, "valor": 0.35, "tempo_cliente": 0.05}

    dados_path = base_empresa / "dados_importados"
    campanha_path = base_empresa / "campanhas" / f"campanha_id_{campanha_id}"
    campanha_path.mkdir(parents=True, exist_ok=True)

    clientes_file = dados_path / "clientes.json"
    vendas_files = list(dados_path.glob("*vendas*.json"))

    if not clientes_file.exists():
        print("[ERRO]: Ficheiro clientes.json não encontrado.")
        return

    df_clientes = pd.read_json(clientes_file)

    if "ClienteID" not in df_clientes.columns:
        print("[ERRO]: ClienteID em falta em clientes.json")
        return

    df_vendas = pd.DataFrame()
    if vendas_files:
        try:
            df_vendas = pd.concat([pd.read_json(f) for f in vendas_files], ignore_index=True)
        except:
            print("[AVISO]: Erro ao carregar vendas.json. Ignorando vendas.")

    resultados = []
    for _, cliente in df_clientes.iterrows():
        cliente_id = cliente["ClienteID"]
        dias_desde_ultima = None
        if not df_vendas.empty:
            vendas_cliente = df_vendas[df_vendas["ClienteID"] == cliente_id]
            if not vendas_cliente.empty and "DataVenda" in vendas_cliente.columns:
                datas = pd.to_datetime(vendas_cliente["DataVenda"], errors="coerce").dropna()
                if not datas.empty:
                    ultima_compra_real = datas.max()
                    dias_desde_ultima = (datetime.today() - ultima_compra_real).days

        if dias_desde_ultima is None:
            dias_desde_ultima = calcular_dias(cliente.get("UltimaCompra"))

        tempo_cliente = calcular_dias(cliente.get("DataCadastro"))
        regiao = cliente.get("Regiao") or cliente.get("Localidade") or cliente.get("Cidade") or cliente.get("Distrito") or cliente.get("País")

        total_compras = cliente.get("TotalCompras", np.nan)
        valor_total = cliente.get("ValorTotalGasto", np.nan)

        if not df_vendas.empty:
            vendas_cliente = df_vendas[df_vendas["ClienteID"] == cliente_id]
            if not vendas_cliente.empty and "DataVenda" in vendas_cliente.columns:
                datas = pd.to_datetime(vendas_cliente["DataVenda"], errors="coerce").dropna()
                if len(datas) > 1:
                    dias_total = (datas.max() - datas.min()).days or 1
                    freq_mensal = len(vendas_cliente) / (dias_total / 30)
                else:
                    freq_mensal = 0
            else:
                freq_mensal = 0
        else:
            freq_mensal = total_compras / (tempo_cliente / 30) if tempo_cliente and total_compras else 0

        recencia_norm = min(dias_desde_ultima or 0, 365) / 365
        tempo_cliente_norm = min(tempo_cliente or 0, 365 * 5) / (365 * 5)
        freq_norm = min(freq_mensal, 10) / 10
        valor_norm = min(valor_total or 0, 10000) / 10000

        recencia_risco = recencia_norm
        tempo_cliente_risco = 1 - tempo_cliente_norm
        frequencia_risco = 1 - freq_norm
        valor_risco = 1 - valor_norm

        score = (
            pesos["recencia"] * recencia_risco +
            pesos["tempo_cliente"] * tempo_cliente_risco +
            pesos["frequencia"] * frequencia_risco +
            pesos["valor"] * valor_risco
        )

        if dias_desde_ultima <= 30 and freq_mensal >= 3 and (valor_total or 0) >= 1000:
            score = 0

        resultado = {
            "ClienteID": cliente_id,
            "Nome": cliente.get("Nome", ""),
            "RecenciaDias": dias_desde_ultima,
            "TempoClienteDias": tempo_cliente,
            "FrequenciaMensal": round(freq_mensal, 2),
            "ValorTotalGasto": round(valor_total or 0, 2),
            "ScoreChurnRaw": round(score, 4),
            "Regiao": regiao or "Desconhecido"
        }

        resultados.append(resultado)

    for r in resultados:
        r["ScoreChurn"] = round(r["ScoreChurnRaw"], 4)

    df_resultados = pd.DataFrame(resultados)

    p33 = df_resultados["ScoreChurn"].quantile(0.33)
    p66 = df_resultados["ScoreChurn"].quantile(0.66)

    def classificar(score):
        if score >= p66:
            return "Alto Risco"
        elif score >= p33:
            return "Médio Risco"
        return "Baixo Risco"

    df_resultados["Classificacao"] = df_resultados["ScoreChurn"].apply(classificar)

    with open(campanha_path / "clientes_churn.json", "w", encoding="utf-8") as f:
        json.dump(df_resultados.to_dict(orient="records"), f, indent=2, ensure_ascii=False)

    print("Ficheiro gerado:\n- clientes_churn.json")


if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usa: python churn_prediction.py <empresa_id> <campanha_id> [base_path]")
        sys.exit(1)

    try:
        empresa_id = int(sys.argv[1])
        campanha_id = int(sys.argv[2])
        base_path = sys.argv[3] if len(sys.argv) > 3 else "C:/Users/Admin/Desktop/SmartCRM/dados_smart_crm"
        churn_prediction(base_path, empresa_id, campanha_id)
    except Exception as e:
        print(f"[ERRO AO INICIAR SCRIPT]: {e}")
