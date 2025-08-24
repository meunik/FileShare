<?php

namespace App\Jobs;

use App\Models\UploadedFile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CleanupExpiredFilesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Iniciando limpeza automática de arquivos expirados');
        
        $expiredFiles = UploadedFile::where('expires_at', '<=', now())->get();
        
        if ($expiredFiles->isEmpty()) {
            Log::info('Nenhum arquivo expirado encontrado');
            return;
        }
        
        $count = 0;
        foreach ($expiredFiles as $file) {
            try {
                $file->delete();
                $count++;
                Log::info("Arquivo expirado removido: {$file->original_name}");
            } catch (\Exception $e) {
                Log::error("Erro ao remover arquivo {$file->original_name}: {$e->getMessage()}");
            }
        }
        
        Log::info("Limpeza automática concluída. {$count} arquivos removidos.");
    }
}
