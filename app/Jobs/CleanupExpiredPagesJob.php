<?php

namespace App\Jobs;

use App\Models\FileShare;
use App\Services\FileShareService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CleanupExpiredPagesJob implements ShouldQueue
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
        Log::info('Iniciando limpeza de páginas expiradas');
        
        $expiredPages = FileShare::where('expires_at', '<=', now())
            ->whereNotNull('expires_at')
            ->get();
        
        $count = 0;
        foreach ($expiredPages as $page) {
            $fileShareService->deleteExpiredPage($page);
            $count++;
        }
        
        Log::info("Limpeza concluída. {$count} páginas expiradas foram removidas.");
    }
}
