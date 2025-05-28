<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use PHPUnit\Event\Code\Throwable;
use Illuminate\Support\Facades\File;

class ExportController extends Controller
{
    public function exportarRfm($campanhaId)
{
    if (!request()->has('clientesBase64')) {
        return response('Faltam os dados dos clientes', 400);
    }

    $clientesBase64 = request('clientesBase64');
    $json = base64_decode($clientesBase64);

    try {
        $clientes = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        return response('Erro ao decodificar os dados dos clientes: ' . $e->getMessage(), 400);
    }

    $pdf = PDF::loadView('exports.rfm', compact('clientes'));

    return response($pdf->output(), 200)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'attachment; filename="segmentacao_rfm_campanha_'.$campanhaId.'.pdf"');
}

}
