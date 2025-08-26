<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileUploadRequest;
use App\Models\FileShare;
use App\Models\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Carbon\Carbon;

class FileShareController extends Controller
{
    /**
     * Exibe a página inicial para criar um identificador de compartilhamento
     */
    public function index()
    {
        return Inertia::render('FileShare/Index');
    }

    /**
     * Exibe a página de compartilhamento baseada no identificador
     */
    public function show(string $identifier)
    {
        $fileShare = FileShare::where('identifier', $identifier)->first();
        
        $existingFiles = [];
        if ($fileShare) {
            $this->cleanupExpiredFiles($fileShare);
            
            $existingFiles = $fileShare->activeFiles->map(function ($file) {
                return [
                    'id' => $file->id,
                    'original_name' => $file->original_name,
                    'size' => $file->formatted_size,
                    'expires_at' => $file->expires_at->format('d/m/Y H:i:s'),
                ];
            });
        }

        return Inertia::render('FileShare/Show', [
            'identifier' => $identifier,
            'existingFiles' => $existingFiles,
            'maxFiles' => 2,
            'maxFileSize' => 50 * 1024 * 1024 * 1024, // 50GB em bytes
        ]);
    }

    /**
     * Faz upload de um arquivo
     */
    public function upload(FileUploadRequest $request, string $identifier)
    {
        $fileShare = FileShare::where('identifier', $identifier)->first();
        
        if ($fileShare) {
            $this->cleanupExpiredFiles($fileShare);
            $activeFilesCount = $fileShare->activeFiles->count();
            
            if ($activeFilesCount >= 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Limite máximo de 2 arquivos atingido.'
                ], 422);
            }
        } else {
            $fileShare = FileShare::create(['identifier' => $identifier]);
        }

        // Calcula a duração em segundos
        $duration = (int) $request->duration;
        $unit = $request->unit;
        
        switch ($unit) {
            case 'second':
                $durationInSeconds = $duration;
                break;
            case 'minute':
                $durationInSeconds = $duration * 60;
                break;
            case 'hour':
                $durationInSeconds = $duration * 3600;
                break;
            default:
                $durationInSeconds = $duration; // fallback para seconds
                break;
        }

        // Limite máximo de 24 horas (86400 segundos)
        $maxDuration = 24 * 3600;
        $limitReached = false;
        
        if ($durationInSeconds > $maxDuration) {
            $durationInSeconds = $maxDuration;
            $limitReached = true;
        }

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $size = $file->getSize();
            $mimeType = $file->getMimeType();

            $storedName = Str::uuid() . '.' . $extension;
            $path = $file->storeAs('uploads', $storedName, 'local');
            $expiresAt = Carbon::now()->addSeconds($durationInSeconds);

            $uploadedFile = UploadedFile::create([
                'file_share_id' => $fileShare->id,
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'extension' => $extension,
                'size' => $size,
                'mime_type' => $mimeType,
                'duration_seconds' => $durationInSeconds,
                'expires_at' => $expiresAt,
            ]);

            $response = [
                'success' => true,
                'file' => [
                    'id' => $uploadedFile->id,
                    'original_name' => $uploadedFile->original_name,
                    'size' => $uploadedFile->formatted_size,
                    'expires_at' => $uploadedFile->expires_at->format('d/m/Y H:i:s'),
                ]
            ];

            if ($limitReached) {
                // Formatar o tempo original solicitado
                $originalTime = '';
                switch ($unit) {
                    case 'second':
                        $originalTime = $duration . ' segundo' . ($duration != 1 ? 's' : '');
                        break;
                    case 'minute':
                        $originalTime = $duration . ' minuto' . ($duration != 1 ? 's' : '');
                        break;
                    case 'hour':
                        $originalTime = $duration . ' hora' . ($duration != 1 ? 's' : '');
                        break;
                }
                
                $response['message'] = "Arquivo enviado com sucesso! O tempo solicitado ({$originalTime}) excede o limite máximo. O arquivo foi configurado para expirar em 24 horas.";
            }

            return response()->json($response);
            
        } catch (\Exception $e) {
            Log::error('Erro no upload de arquivo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer upload do arquivo. Tente novamente.'
            ], 500);
        }
    }

    /**
     * Remove um arquivo
     */
    public function deleteFile(UploadedFile $file)
    {
        try {
            $fileShare = $file->fileShare;
            $file->delete();
            $this->cleanupEmptyFileShare($fileShare);
            
            return response()->json([
                'success' => true,
                'message' => 'Arquivo removido com sucesso.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover o arquivo.'
            ], 500);
        }
    }

    /**
     * Faz download de um arquivo
     */
    public function download(UploadedFile $file)
    {
        if ($file->is_expired) abort(404, 'Arquivo expirado ou não encontrado.');
        $filePath = storage_path('app/private/uploads/' . $file->stored_name);
        if (!file_exists($filePath)) abort(404, 'Arquivo não encontrado.');
        return response()->download($filePath, $file->original_name);
    }

    /**
     * Remove arquivos expirados de um compartilhamento
     */
    private function cleanupExpiredFiles(FileShare $fileShare)
    {
        $expiredFiles = $fileShare->uploadedFiles()->where('expires_at', '<=', now())->get();
        foreach ($expiredFiles as $file) $file->delete();
        $this->cleanupEmptyFileShare($fileShare);
    }

    /**
     * Remove o identificador se não há mais arquivos na página
     */
    private function cleanupEmptyFileShare(FileShare $fileShare)
    {
        $fileShare->refresh();
        $activeFilesCount = $fileShare->uploadedFiles()->where('expires_at', '>', now())->count();
        if ($activeFilesCount === 0) $fileShare->delete();
    }
}
