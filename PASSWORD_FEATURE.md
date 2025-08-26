# Funcionalidade de Senha Implementada

## Resumo das Mudanças

### 1. **Banco de Dados**
- Adicionados campos à tabela `file_shares`:
  - `password_hash`: Hash da senha (nullable)
  - `duration_seconds`: Duração em segundos (nullable)
  - `expires_at`: Data de expiração da página (nullable)

### 2. **Backend**

#### Modelo FileShare
- Métodos para gerenciar senha: `hasPassword()`, `checkPassword()`, `setPassword()`, `removePassword()`
- Método para verificar expiração: `isExpired()`

#### FileShareService
- Serviço criado para centralizar a lógica de negócio
- Métodos para criar/encontrar páginas, validar senhas, limpar arquivos/páginas expiradas

#### Controller Refatorado
- Novo método `create()` para criar páginas com ou sem senha
- Método `validatePassword()` para autenticação
- Métodos `removePassword()` e `deletePage()` para gerenciar páginas
- Lógica simplificada usando o FileShareService

#### Requests
- `CreatePageRequest`: Validação para criação de páginas com senha

#### Jobs
- `CleanupExpiredPagesJob`: Limpeza de páginas expiradas
- `CleanupExpiredFilesJob`: Atualizado para incluir limpeza de páginas

### 3. **Frontend**

#### Página Inicial (Index.vue)
- Campo de senha opcional
- Campos de duração que aparecem quando há senha
- Lógica para criação de páginas com/sem senha

#### Página de Senha (PasswordPrompt.vue)
- Nova página para autenticação em páginas protegidas
- Interface amigável para inserir senha

#### Componentes Criados
- `ExistingFiles.vue`: Lista e gerencia arquivos existentes
- `FileUpload.vue`: Formulário de upload de arquivos
- `PageActions.vue`: Ações da página (remover senha, deletar página)

#### Página Principal Refatorada (Show.vue)
- Completamente refatorada usando os novos componentes
- Código mais limpo e organizacional

### 4. **Rotas**
- `POST /create`: Criar página com ou sem senha
- `POST /{identifier}/validate-password`: Validar senha da página
- `DELETE /{identifier}/password`: Remover senha da página
- `DELETE /{identifier}`: Deletar página completamente

## Como Usar

### Criar Página Sem Senha
1. Acesse a página inicial
2. Digite um identificador
3. Clique em "Criar Página"

### Criar Página Com Senha
1. Acesse a página inicial
2. Digite um identificador
3. Digite uma senha
4. Os campos de duração aparecerão automaticamente
5. Defina o tempo de duração (máx. 24 horas)
6. Clique em "Criar Página"

### Acessar Página Protegida
1. Acesse o link da página
2. Se protegida, será solicitada a senha
3. Digite a senha e clique em "Acessar"

### Gerenciar Página
1. Dentro da página, acesse "Configurações da página"
2. Opções disponíveis:
   - **Remover senha**: Torna a página pública
   - **Deletar página**: Remove permanentemente a página e todos os arquivos

## Características Técnicas

### Segurança
- Senhas são hashadas usando bcrypt
- Validação de entrada rigorosa
- Proteção CSRF em todas as operações

### Expiração
- Páginas com senha têm expiração automática
- Limite máximo de 24 horas
- Limpeza automática via jobs
- Quando a página expira, todos os arquivos são deletados

### Funcionalidades Mantidas
- Upload de até 2 arquivos por página
- Limite de 50GB por arquivo
- Download e exclusão de arquivos
- Interface responsiva
- Modo escuro/claro

### Melhorias de Código
- Separação de responsabilidades
- Componentes reutilizáveis
- Serviços para lógica de negócio
- Código mais limpo e manutenível

## Testes Recomendados

1. **Página sem senha**
   - Criar página
   - Upload de arquivos
   - Download de arquivos
   - Exclusão de arquivos

2. **Página com senha**
   - Criar página com senha
   - Acessar com senha correta/incorreta
   - Upload/download de arquivos
   - Remover senha da página
   - Deletar página

3. **Expiração**
   - Criar página com tempo curto
   - Verificar expiração automática
   - Tentar acessar página expirada

4. **Limite de tempo**
   - Tentar criar página com mais de 24 horas
   - Verificar se o limite é aplicado
