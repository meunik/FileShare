#!/bin/sh

# Cores
RED='\e[91m'
GREEN='\e[92m'
YELLOW='\e[93m'
BLUE='\e[94m'
PURPLE='\e[95m'
CYAN='\e[96m'
WHITE='\e[97m'
GRAY='\e[90m'
RESET='\e[0m'

# Constantes
SERVIDOR_PRODUCAO="192.168.0.17"
USUARIO_REMOTO="meunik"
PORTA_SSH=22
SSH_CONTROL_PATH="/tmp/ssh-deploy-control"

# Função para verificar conectividade
check_connectivity() {
    echo -e "${YELLOW}Verificando conectividade com o servidor...${RESET}"
    
    # Testa ping primeiro
    if ping -c 1 -W 3 $SERVIDOR_PRODUCAO >/dev/null 2>&1; then
        echo -e "${GREEN}✓ Ping OK${RESET}"
    else
        echo -e "${RED}✗ Ping falhou - Servidor não responde${RESET}"
        exit 1
    fi
}

# Função para configurar SSH ControlMaster
setup_ssh_control() {
    echo -e "${YELLOW}Estabelecendo conexão SSH reutilizável...${RESET}"
    
    # Remove conexão anterior se existir
    ssh -S "$SSH_CONTROL_PATH" -O exit -p $PORTA_SSH $USUARIO_REMOTO@$SERVIDOR_PRODUCAO 2>/dev/null
    
    # Estabelece conexão master (permitindo senha)
    ssh -M -S "$SSH_CONTROL_PATH" -f -N -o ConnectTimeout=30 -p $PORTA_SSH $USUARIO_REMOTO@$SERVIDOR_PRODUCAO
    if [ $? -ne 0 ]; then
        echo -e "${RED}Erro ao estabelecer conexão SSH${RESET}"
        exit 1
    fi
    echo -e "${GREEN}✓ Conexão SSH estabelecida${RESET}"
}

# Função para executar comandos SSH usando a conexão existente
ssh_exec() {
    OUTPUT=$(ssh -S "$SSH_CONTROL_PATH" -p $PORTA_SSH $USUARIO_REMOTO@$SERVIDOR_PRODUCAO "$1" 2>&1)
    EXIT_CODE=$?
    
    if [ $EXIT_CODE -ne 0 ]; then
        echo -e "${RED}Erro na execução SSH:${RESET}"
        echo "$OUTPUT"
        return $EXIT_CODE
    else
        # Mostra output brevemente e depois limpa (opcional)
        if [ -n "$OUTPUT" ]; then
            echo "$OUTPUT"
            sleep 1
            # Limpa as linhas do output (conta quantas linhas tem)
            LINES=$(echo "$OUTPUT" | wc -l)
            for i in $(seq 1 $LINES); do
                printf "\033[1A\033[2K"
            done
        fi
    fi
}

# Função para transferir dados via SSH usando a conexão existente
ssh_transfer() {
    ssh -S "$SSH_CONTROL_PATH" -p $PORTA_SSH $USUARIO_REMOTO@$SERVIDOR_PRODUCAO "$1"
    return $?
}

# Função para fechar conexão SSH
cleanup_ssh_control() {
    ssh -S "$SSH_CONTROL_PATH" -O exit -p $PORTA_SSH $USUARIO_REMOTO@$SERVIDOR_PRODUCAO 2>/dev/null
}

# Trap para limpar conexão em caso de erro
trap cleanup_ssh_control EXIT

# Verifica conectividade antes de começar
check_connectivity

# Recebe parametros
COMPOSE_FILE="docker-compose.yml"

# Verifica se o arquivo compose existe
if [ ! -f "$COMPOSE_FILE" ]; then
    echo -e "${RED}Erro: Arquivo $COMPOSE_FILE não encontrado!${RESET}"
    exit 1
fi

NOME_SISTEMA=$(grep '^[[:space:]]*image:' "$COMPOSE_FILE" | head -1 | awk '{print $2}' | tr -d '\r\n')
NOME_IMAGEM="${NOME_SISTEMA}"
ID_NOME_CONTAINER_LOCAL=$(docker ps -aq --filter ancestor="${NOME_SISTEMA}" | head -1)
PORTA_APLICACAO=$(grep '^[[:space:]]*ports:' -A1 "$COMPOSE_FILE" | tail -1 | sed -E 's/.*"([0-9]+):[0-9]+".*/\1/')

echo -e "${GRAY}------------------------------------------${RESET}"

echo -e "Porta da aplicação: ${CYAN}${PORTA_APLICACAO}${RESET}"
echo -e "Nome do sistema: ${CYAN}${NOME_SISTEMA}${RESET}"
echo -e "Nome da imagem: ${CYAN}${NOME_IMAGEM}${RESET}"
echo -e "ID do container local: ${CYAN}${ID_NOME_CONTAINER_LOCAL}${RESET}"

# Verifica se existe container/imagem local
if [ -n "$ID_NOME_CONTAINER_LOCAL" ]; then
    echo -e "${GREEN}Usando imagem do container existente: ${CYAN}${NOME_IMAGEM}${RESET}"
elif docker image inspect $NOME_IMAGEM >/dev/null 2>&1; then
    echo -e "${GREEN}Usando imagem local existente: ${CYAN}${NOME_IMAGEM}${RESET}"
else
    echo -e "${RED}Erro: Imagem ${NOME_IMAGEM} não encontrada localmente!${RESET}"
    echo -e "${YELLOW}Execute primeiro: docker build -t ${NOME_IMAGEM} .${RESET}"
    exit 1
fi

echo -e "${GRAY}------------------------------------------${RESET}"

# Configura conexão SSH reutilizável
setup_ssh_control

# Transfere imagem diretamente via SSH
echo -e "${YELLOW}Transferindo imagem via SSH...${RESET}"
if docker save $NOME_IMAGEM | ssh_transfer "docker load"; then
    echo -e "${GREEN}✓ Imagem transferida com sucesso${RESET}"
else
    echo -e "${RED}✗ Erro na transferência da imagem${RESET}"
    exit 1
fi

echo -e "${GRAY}------------------------------------------${RESET}"

# Para e remove container antigo no servidor
echo -e "${YELLOW}Parando e removendo container antigo no servidor...${RESET}"
ssh_exec "
if [ \$(docker ps -aq --filter \"name=^${NOME_SISTEMA}$\") ]; then
    echo 'Removendo container ${NOME_SISTEMA}...'
    docker stop $NOME_SISTEMA
    docker rm $NOME_SISTEMA
else
    echo 'Nenhum contêiner chamado ${NOME_SISTEMA} encontrado.'
fi
"

echo -e "${YELLOW}Iniciando novo container no servidor...${RESET}"
ssh_exec "docker run -d -p $PORTA_APLICACAO:80 --name $NOME_SISTEMA --restart unless-stopped -m=2g $NOME_IMAGEM"
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Container iniciado com sucesso${RESET}"
fi

# Fecha conexão SSH
cleanup_ssh_control

echo -e "${GREEN}Deploy concluído com sucesso!${RESET}"