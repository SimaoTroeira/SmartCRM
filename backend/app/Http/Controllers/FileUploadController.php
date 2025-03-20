<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use ZipArchive;

class FileUploadController extends Controller
{
    public function upload(Request $request)
    {
        // Validação do arquivo
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls,zip',
        ]);

        // Verificar se o arquivo é um ZIP
        if ($request->file('file')->getClientOriginalExtension() === 'zip') {
            return $this->handleZipFile($request->file('file'));
        }

        // Processar arquivos CSV ou Excel únicos
        $path = $request->file('file')->storeAs('uploads', $request->file('file')->getClientOriginalName());
        $data = Excel::toArray([], storage_path('app/' . $path));

        return response()->json($data[0]);
    }

    private function handleZipFile($file)
    {
        $zip = new ZipArchive;
        $filePath = $file->getRealPath();
        $extractPath = storage_path('app/uploads/' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

        if ($zip->open($filePath) === TRUE) {
            $zip->extractTo($extractPath);
            $zip->close();

            $files = Storage::files('uploads/' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $allData = [];

            foreach ($files as $file) {
                if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['csv', 'xlsx', 'xls'])) {
                    $data = Excel::toArray([], storage_path('app/' . $file));
                    $allData[pathinfo($file, PATHINFO_BASENAME)] = $data[0];
                }
            }

            return response()->json($allData);
        } else {
            return response()->json(['error' => 'Failed to open ZIP file'], 500);
        }
    }
}
