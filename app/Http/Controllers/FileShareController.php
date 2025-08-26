<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePageRequest;
use App\Http\Requests\FileUploadRequest;
use App\Models\FileShare;
use App\Models\UploadedFile;
use App\Services\FileShareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Carbon\Carbon;

class FileShareController extends Controller
{
    public function __construct(
        private FileShareService $fileShareService
    ) {}

    /**
     * Exibe a página inicial para criar um identificador de compartilhamento
     */
    public function index()
    {
        return Inertia::render('FileShare/Index');
    }

    /**
     * Cria uma nova página ou redireciona para página existente
     */
    public function create(CreatePageRequest $request)
    {
        $identifier = trim($request->identifier);
        $password = $request->password;
        $duration = $request->duration;
        $unit = $request->unit;

        // Verifica se a página pode ser acessada
        if (!$this->fileShareService->isPageAccessible($identifier)) {
            return response()->json([
                'success' => false,
                'message' => 'Esta página não pode ser acessada.'
            ], 422);
        }

        // Se tem senha, cria a página com senha
        if ($password) {
            $durationInSeconds = $this->fileShareService->calculateDurationInSeconds($duration, $unit);
            [$limitedDuration, $limitReached] = $this->fileShareService->applyMaxDurationLimit($durationInSeconds);
            
            $fileShare = $this->fileShareService->findOrCreate($identifier, $password, $limitedDuration);
            
            $response = ['success' => true, 'redirect' => "/{$identifier}"];
            
            if ($limitReached) {
                $originalTime = $this->formatDurationText($duration, $unit);
                $response['message'] = "Página criada com sucesso! O tempo solicitado ({$originalTime}) excede o limite máximo. A página foi configurada para expirar em 24 horas.";
            }
            
            return response()->json($response);
        }

        // Página sem senha, simplesmente redireciona
        return response()->json([
            'success' => true,
            'redirect' => "/{$identifier}"
        ]);
    }

    /**
     * Exibe a página de compartilhamento baseada no identificador
     */
    public function show(string $identifier, Request $request)
    {
        $fileShare = FileShare::where('identifier', $identifier)->first();
        
        // Se a página existe, verifica se está expirada
        if ($fileShare && $fileShare->isExpired()) {
            $this->fileShareService->deleteExpiredPage($fileShare);
            $fileShare = null;
        }

        // Se a página tem senha, verifica autenticação
        if ($fileShare && $fileShare->hasPassword()) {
            $sessionToken = session("page_access_{$identifier}");
            $headerToken = $request->header('X-Session-Token');
            
            // Verifica se tem token válido na sessão ou no header
            if (!$sessionToken || ($headerToken && $headerToken !== $sessionToken)) {
                return Inertia::render('FileShare/PasswordPrompt', [
                    'identifier' => $identifier,
                    'error' => null
                ]);
            }
        }

        // Se não existe a página, mostra uma página vazia para criar
        $existingFiles = [];
        if ($fileShare) {
            $this->fileShareService->cleanupExpiredFiles($fileShare);
            
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
            'hasPassword' => $fileShare ? $fileShare->hasPassword() : false,
            'pageExpiresAt' => $fileShare && $fileShare->expires_at ? $fileShare->expires_at->format('d/m/Y H:i:s') : null,
            'pageExists' => $fileShare !== null,
        ]);
    }

    /**
     * Valida senha da página
     */
    public function validatePassword(Request $request, string $identifier)
    {
        $request->validate([
            'password' => 'required|string'
        ]);

        $fileShare = FileShare::where('identifier', $identifier)->first();
        
        if (!$fileShare) {
            return response()->json(['success' => false, 'message' => 'Página não encontrada.'], 404);
        }

        if ($this->fileShareService->validatePagePassword($fileShare, $request->password)) {
            // Cria um token temporário de sessão para esta página
            $sessionToken = Str::random(32);
            session(["page_access_{$identifier}" => $sessionToken]);
            
            return response()->json([
                'success' => true,
                'session_token' => $sessionToken
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Senha incorreta.'], 422);
    }

    /**
     * Faz upload de um arquivo
     */
    public function upload(FileUploadRequest $request, string $identifier)
    {
        $fileShare = FileShare::where('identifier', $identifier)->first();
        
        if ($fileShare) {
            // Se a página tem senha, verifica se o usuário está autenticado
            if ($fileShare->hasPassword()) {
                $sessionToken = session("page_access_{$identifier}");
                if (!$sessionToken) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Acesso negado. Página protegida por senha.'
                    ], 403);
                }
            }
            
            $this->fileShareService->cleanupExpiredFiles($fileShare);
            $activeFilesCount = $fileShare->activeFiles->count();
            
            if ($activeFilesCount >= 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Limite máximo de 2 arquivos atingido.'
                ], 422);
            }
        } else {
            // Não permite upload em páginas que não existem
            // O usuário deve primeiro criar a página
            return response()->json([
                'success' => false,
                'message' => 'Página não encontrada. Crie a página primeiro.'
            ], 404);
        }

        // Calcula a duração em segundos
        $duration = (int) $request->duration;
        $unit = $request->unit;
        
        $durationInSeconds = $this->fileShareService->calculateDurationInSeconds($duration, $unit);
        [$limitedDuration, $limitReached] = $this->fileShareService->applyMaxDurationLimit($durationInSeconds);

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $size = $file->getSize();
            $mimeType = $file->getMimeType();

            $storedName = Str::uuid() . '.' . $extension;
            $path = $file->storeAs('uploads', $storedName, 'local');
            $expiresAt = Carbon::now()->addSeconds($limitedDuration);

            $uploadedFile = UploadedFile::create([
                'file_share_id' => $fileShare->id,
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'extension' => $extension,
                'size' => $size,
                'mime_type' => $mimeType,
                'duration_seconds' => $limitedDuration,
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
                $originalTime = $this->formatDurationText($duration, $unit);
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
            $this->fileShareService->cleanupEmptyFileShare($fileShare);
            
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
        if ($file->is_expired) {
            abort(404, 'Arquivo expirado ou não encontrado.');
        }
        
        $filePath = storage_path('app/private/uploads/' . $file->stored_name);
        if (!file_exists($filePath)) {
            abort(404, 'Arquivo não encontrado.');
        }
        
        return response()->download($filePath, $file->original_name);
    }

    /**
     * Remove a senha de uma página
     */
    public function removePassword(string $identifier)
    {
        $fileShare = FileShare::where('identifier', $identifier)->first();
        
        if (!$fileShare) {
            return response()->json(['success' => false, 'message' => 'Página não encontrada.'], 404);
        }

        $this->fileShareService->removePagePassword($fileShare);
        
        return response()->json([
            'success' => true,
            'message' => 'Senha removida com sucesso. A página agora é pública.'
        ]);
    }

    /**
     * Deleta uma página e todos os seus arquivos
     */
    public function deletePage(string $identifier)
    {
        $fileShare = FileShare::where('identifier', $identifier)->first();
        
        if (!$fileShare) {
            return response()->json(['success' => false, 'message' => 'Página não encontrada.'], 404);
        }

        $this->fileShareService->deletePage($fileShare);
        
        return response()->json([
            'success' => true,
            'message' => 'Página deletada com sucesso.'
        ]);
    }

    /**
     * Formata texto de duração
     */
    private function formatDurationText(int $duration, string $unit): string
    {
        switch ($unit) {
            case 'second':
                return $duration . ' segundo' . ($duration != 1 ? 's' : '');
            case 'minute':
                return $duration . ' minuto' . ($duration != 1 ? 's' : '');
            case 'hour':
                return $duration . ' hora' . ($duration != 1 ? 's' : '');
            default:
                return $duration . ' segundo(s)';
        }
    }
}
