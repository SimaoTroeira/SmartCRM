<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class FileUploadController extends Controller
{
    public function upload(Request $request)
    {
        // Validação do arquivo
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls',
        ]);

        // Salvar o arquivo no armazenamento
        $path = $request->file('file')->storeAs('uploads', $request->file('file')->getClientOriginalName());

        // Ler o arquivo CSV ou Excel
        $data = Excel::toArray([], storage_path('app/' . $path));

        // Retornar os dados processados para o frontend
        return response()->json($data[0]);
    }

}
