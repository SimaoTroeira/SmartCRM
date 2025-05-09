<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecutarScriptPython implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $script,
        public int $empresaId,
        public int $campanhaId
    ) {}


    public function handle(): void
    {
        // $scriptPath = base_path("../scripts/{$this->script}");
        $scriptPath = base_path("scripts/{$this->script}");
        $basePath = config('smartcrm.storage_path');

        $comando = "python \"$scriptPath\" {$this->empresaId} {$this->campanhaId} \"$basePath\" 2>&1";

        Log::info("Comando executado: $comando");

        exec($comando, $output, $status);

        Log::info("Output do script:\n" . implode("\n", $output));

        if ($status === 0) {
            Log::info("Script Python executado com sucesso para campanha {$this->campanhaId}.");
        } else {
            Log::error("Erro ao executar o script Python para campanha {$this->campanhaId}. Código: $status");
        }
    }
}
