<?php

namespace App\Jobs;

use App\Models\FileShare;
use App\Models\UploadedFile;
use App\Services\FileShareService;
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
    public function handle(FileShareService $fileShareService): void
    {
        Log::info('Iniciando limpeza automática de arquivos e páginas expiradas');
        
        // Limpar arquivos expirados
        $expiredFiles = UploadedFile::where('expires_at', '<=', now())->get();
        $filesCount = 0;
        
        foreach ($expiredFiles as $file) {
            try {
                $file->delete();
                $filesCount++;
                Log::info("Arquivo expirado removido: {$file->original_name}");
            } catch (\Exception $e) {
                Log::error("Erro ao remover arquivo {$file->original_name}: {$e->getMessage()}");
            }
        }
        
        // Limpar páginas expiradas
        $expiredPages = FileShare::where('expires_at', '<=', now())
            ->whereNotNull('expires_at')
            ->get();
        
        $pagesCount = 0;
        foreach ($expiredPages as $page) {
            try {
                $fileShareService->deleteExpiredPage($page);
                $pagesCount++;
                Log::info("Página expirada removida: {$page->identifier}");
            } catch (\Exception $e) {
                Log::error("Erro ao remover página {$page->identifier}: {$e->getMessage()}");
            }
        }
        
        Log::info("Limpeza automática concluída. {$filesCount} arquivos e {$pagesCount} páginas removidos.");
    }
}
