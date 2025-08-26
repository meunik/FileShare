# Docker Setup

## Como executar com Docker

### Opção 1: Usando Docker Compose (Recomendado)

```bash
# Construir e executar
docker-compose up --build

# Em segundo plano
docker-compose up -d --build

# Parar
docker-compose down
```

### Opção 2: Usando Docker diretamente

```bash
# Construir a imagem
docker build -t fileshare .

# Executar o container
docker run -p 8000:80 -v ./storage:/var/www/html/storage -v ./database/database.sqlite:/var/www/html/database/database.sqlite fileshare
```

## Acesso

Depois de executar, acesse: http://localhost:8000

## Logs

```bash
# Ver logs
docker-compose logs -f

# Ver logs apenas da aplicação
docker-compose logs -f app
```

## Comandos úteis

```bash
# Entrar no container
docker-compose exec app bash

# Executar comandos Artisan
docker-compose exec app php artisan <command>

# Limpar cache
docker-compose exec app php artisan cache:clear
```
