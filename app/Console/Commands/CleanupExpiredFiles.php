<?php

namespace App\Console\Commands;

use App\Models\UploadedFile;
use Illuminate\Console\Command;

class CleanupExpiredFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:expired-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove arquivos expirados do sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando limpeza de arquivos expirados...');
        
        $expiredFiles = UploadedFile::where('expires_at', '<=', now())->get();
        
        if ($expiredFiles->isEmpty()) {
            $this->info('Nenhum arquivo expirado encontrado.');
            return;
        }
        
        $count = 0;
        foreach ($expiredFiles as $file) {
            try {
                $file->delete();
                $count++;
                $this->line("Removido: {$file->original_name}");
            } catch (\Exception $e) {
                $this->error("Erro ao remover {$file->original_name}: {$e->getMessage()}");
            }
        }
        
        $this->info("Limpeza concluída. {$count} arquivos removidos.");
    }
}
