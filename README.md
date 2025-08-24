# FileShare - Compartilhamento Temporário de Arquivos

Um sistema de compartilhamento de arquivos similar ao DontPad, mas focado no upload e compartilhamento temporário de arquivos.

## 🚀 Funcionalidades

### Página Principal
- Criação de páginas de compartilhamento com identificador personalizado
- Interface simples e intuitiva
- Validação de identificadores (máx. 255 caracteres)

### Página de Compartilhamento
- Upload de até 2 arquivos por página
- Tamanho máximo: 50GB por arquivo
- Drag & drop para facilitar o upload
- Barra de progresso durante o upload
- Tempo de expiração configurável (segundos, minutos, horas)
- Limite máximo de 24 horas
- Download direto dos arquivos
- Exclusão individual de arquivos

## 🔒 Segurança

### Validações de Arquivo
- Bloqueio de extensões perigosas (.exe, .bat, .php, etc.)
- Validação de nome de arquivo (prevenção de path traversal)
- Armazenamento em storage privado
- Nomes únicos (UUID) para evitar conflitos

### Rate Limiting
- Máximo 5 uploads por hora por IP
- Máximo 5 uploads por hora por identificador
- Throttling nativo do Laravel como backup

### Outras Medidas
- CSRF protection
- Validação de MIME types
- Logs de erro
- Limpeza automática de arquivos expirados

## 🏗️ Estrutura do Projeto

### Backend (Laravel)
- **Models**: `FileShare`, `UploadedFile`
- **Controllers**: `FileShareController`
- **Requests**: `FileUploadRequest` (validações customizadas)
- **Middleware**: `RateLimitFileUploads`
- **Commands**: `CleanupExpiredFiles`

### Frontend (Vue.js + Inertia.js)
- **Páginas**: 
  - `FileShare/Index.vue` (página inicial)
  - `FileShare/Show.vue` (página de compartilhamento)
- **Componentes**: Interface responsiva com Tailwind CSS

### Banco de Dados
```sql
-- Tabela de compartilhamentos
file_shares:
  - id (primary key)
  - identifier (string, unique, 255 chars)
  - timestamps

-- Tabela de arquivos
uploaded_files:
  - id (primary key)
  - file_share_id (foreign key)
  - original_name (string)
  - stored_name (string, UUID)
  - extension (string)
  - size (bigint, bytes)
  - mime_type (string)
  - duration_seconds (integer)
  - expires_at (timestamp)
  - timestamps
```

## 🚦 Rotas

```php
GET  /                      # Página inicial
GET  /{identifier}          # Página de compartilhamento
POST /{identifier}/upload   # Upload de arquivo
GET  /download/{file}       # Download de arquivo
DELETE /file/{file}         # Exclusão de arquivo
```

## ⚙️ Configuração

### Requisitos
- PHP 8.1+
- Laravel 11+
- SQLite (configurado por padrão)
- Node.js (para frontend)

### Instalação
```bash
# Clone e instale dependências
composer install
npm install

# Configure o ambiente
cp .env.example .env
php artisan key:generate

# Execute as migrações
php artisan migrate

# Inicie o servidor
composer run dev
```

### Comandos Úteis
```bash
# Limpeza manual de arquivos expirados
php artisan cleanup:expired-files

# Verificar logs
tail -f storage/logs/laravel.log
```

## 🔧 Configurações Personalizáveis

### Limites de Sistema
- **Máximo de arquivos por página**: 2 (configurável em `FileShareController`)
- **Tamanho máximo por arquivo**: 50GB (configurável em `FileUploadRequest`)
- **Tempo máximo de duração**: 24 horas (configurável em `FileShareController`)

### Rate Limiting
- **Uploads por IP/hora**: 10 (configurável em `RateLimitFileUploads`)
- **Uploads por identificador/hora**: 5 (configurável em `RateLimitFileUploads`)

### Extensões Bloqueadas
Configurável em `FileUploadRequest::rules()`:
```php
$dangerousExtensions = ['exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar', 'php', 'asp', 'jsp'];
```

## 🔄 Manutenção Automática

### Limpeza de Arquivos
- **Agendamento**: A cada hora (configurado em `routes/console.php`)
- **Comando**: `cleanup:expired-files`
- **Ação**: Remove arquivos expirados do sistema de arquivos e banco de dados

### Monitoramento
- Logs automáticos de erros
- Tracking de uploads por IP e identificador
- Cache para rate limiting

## 🎯 Como Usar

1. **Acesse a página inicial** em `http://localhost:8000`
2. **Digite um identificador** único para sua página
3. **Clique em "Criar Página"** para ir para a página de compartilhamento
4. **Faça upload dos arquivos**:
   - Arraste o arquivo ou clique para selecionar
   - Defina o tempo de duração
   - Clique em "Enviar"
5. **Compartilhe o link** da página com outros usuários
6. **Gerencie os arquivos** (download/exclusão) conforme necessário

## 📝 Notas Importantes

- Arquivos são armazenados em `storage/app/private/uploads/`
- Apenas arquivos não expirados são exibidos
- Downloads são diretos (sem limitação de bandwidth)
- Identificadores são case-sensitive
- Páginas vazias (sem arquivos) não são salvas no banco

## 🚨 Limitações Conhecidas

- Upload de arquivos muito grandes pode ser limitado pela configuração do PHP
- Sem autenticação (acesso público a todas as páginas)
- Sem criptografia de arquivos (apenas storage privado)
- Rate limiting baseado em cache (pode ser resetado)

## 🔮 Melhorias Futuras

- [ ] Autenticação opcional
- [ ] Criptografia de arquivos
- [ ] Preview de arquivos (imagens, PDFs)
- [ ] Compressão automática
- [ ] Notificações de expiração
- [ ] Interface de administração
- [ ] Métricas de uso
- [ ] Suporte a URLs customizadas
