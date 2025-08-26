<?php

namespace App\Services;

use App\Models\FileShare;
use App\Models\UploadedFile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class FileShareService
{
    /**
     * Encontra ou cria um FileShare
     */
    public function findOrCreate(string $identifier, ?string $password = null, ?int $durationSeconds = null): FileShare
    {
        $fileShare = FileShare::where('identifier', $identifier)->first();
        
        if (!$fileShare) {
            $fileShare = new FileShare(['identifier' => $identifier]);
            
            if ($password) {
                $fileShare->setPassword($password);
                $fileShare->duration_seconds = $durationSeconds;
                $fileShare->expires_at = Carbon::now()->addSeconds($durationSeconds);
            }
            
            $fileShare->save();
        }
        
        return $fileShare;
    }

    /**
     * Encontra um FileShare existente (não cria novo)
     */
    public function find(string $identifier): ?FileShare
    {
        return FileShare::where('identifier', $identifier)->first();
    }

    /**
     * Verifica se a página existe e não está expirada
     */
    public function isPageAccessible(string $identifier): bool
    {
        $fileShare = FileShare::where('identifier', $identifier)->first();
        
        if (!$fileShare) {
            return true; // Página não existe, pode ser criada
        }
        
        if ($fileShare->isExpired()) {
            $this->deleteExpiredPage($fileShare);
            return true; // Página expirou e foi removida, pode ser recriada
        }
        
        return true;
    }

    /**
     * Valida a senha da página
     */
    public function validatePagePassword(FileShare $fileShare, ?string $password = null): bool
    {
        if (!$fileShare->hasPassword()) {
            return true; // Página sem senha
        }
        
        if (!$password) {
            return false; // Página com senha mas nenhuma senha fornecida
        }
        
        return $fileShare->checkPassword($password);
    }

    /**
     * Remove arquivos expirados de um compartilhamento
     */
    public function cleanupExpiredFiles(FileShare $fileShare): void
    {
        $expiredFiles = $fileShare->uploadedFiles()->where('expires_at', '<=', now())->get();
        
        foreach ($expiredFiles as $file) {
            $this->deleteFile($file);
        }
        
        $this->cleanupEmptyFileShare($fileShare);
    }

    /**
     * Remove página expirada
     */
    public function deleteExpiredPage(FileShare $fileShare): void
    {
        // Remove todos os arquivos
        foreach ($fileShare->uploadedFiles as $file) {
            $this->deleteFile($file);
        }
        
        // Remove a página
        $fileShare->delete();
    }

    /**
     * Remove o identificador se não há mais arquivos na página
     */
    public function cleanupEmptyFileShare(FileShare $fileShare): void
    {
        $fileShare->refresh();
        
        // Se a página não tem senha e não tem arquivos ativos, remove ela
        if (!$fileShare->hasPassword()) {
            $activeFilesCount = $fileShare->uploadedFiles()->where('expires_at', '>', now())->count();
            if ($activeFilesCount === 0) {
                $fileShare->delete();
            }
        }
    }

    /**
     * Remove a senha de uma página
     */
    public function removePagePassword(FileShare $fileShare): void
    {
        $fileShare->removePassword();
        $fileShare->duration_seconds = null;
        $fileShare->expires_at = null;
        $fileShare->save();
    }

    /**
     * Deleta uma página e todos os seus arquivos
     */
    public function deletePage(FileShare $fileShare): void
    {
        // Remove todos os arquivos
        foreach ($fileShare->uploadedFiles as $file) {
            $this->deleteFile($file);
        }
        
        // Remove a página
        $fileShare->delete();
    }

    /**
     * Remove um arquivo do sistema de arquivos e do banco
     */
    private function deleteFile(UploadedFile $file): void
    {
        // Remove o arquivo físico
        $filePath = 'uploads/' . $file->stored_name;
        if (Storage::disk('local')->exists($filePath)) {
            Storage::disk('local')->delete($filePath);
        }
        
        // Remove do banco
        $file->delete();
    }

    /**
     * Calcula duração em segundos baseado na unidade
     */
    public function calculateDurationInSeconds(int $duration, string $unit): int
    {
        switch ($unit) {
            case 'second':
                return $duration;
            case 'minute':
                return $duration * 60;
            case 'hour':
                return $duration * 3600;
            default:
                return $duration;
        }
    }

    /**
     * Aplica limite máximo de 24 horas
     */
    public function applyMaxDurationLimit(int $durationInSeconds): array
    {
        $maxDuration = 24 * 3600; // 24 horas
        $limitReached = false;
        
        if ($durationInSeconds > $maxDuration) {
            $durationInSeconds = $maxDuration;
            $limitReached = true;
        }
        
        return [$durationInSeconds, $limitReached];
    }
}
