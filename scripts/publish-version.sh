## Script para gerar nova versÃ£o na branch de produÃ§Ã£o a partir de uma branch estÃ¡vel ##

exibir_uso_correto() {
    echo "Usar: $0 <versao> [versao_producao] [versao_candidata]"
    echo "Onde:"
    echo "  <versao_producao> indentificador da versÃ£o a publicar."
    echo "     Exemplo:"
    echo "       $0 v7.6.0-minc14 => gerarÃ¡ versÃ£o identificada como v7.6.0-minc14"
    echo "  <versao_candidata> indentificador da versÃ£o candidata a prÃ³xima publicaÃ§Ã£o."
    echo "     Exemplo:"
    echo "       $0 v7.6.0-minc15-RC => gerarÃ¡ versÃ£o identificada como v7.6.0-minc15-RC"
}

# VerificaÃ§Ãµes
ENV_ERROR=0
if [[ -z "$COMMIT_MSG_NEW_VERSION" ]]; then
    (( ENV_ERROR++ ))
    echo "#ENV_ERROR_$ENV_ERROR"
    echo "  Este script requer a variÃ¡vel de ambiente \$COMMIT_MSG_NEW_VERSION definida."
    echo "  Essa variÃ¡vel deve conter a mensagem dos commits de atualizaÃ§Ã£o de versÃ£o."
    echo "  Exemplo: 'Atualiza identificador de versÃ£o'"
fi
if [ -e "$STABLE_BRANCH/." ]; then
    (( ENV_ERROR++ ))
    echo "#ENV_ERROR_$ENV_ERROR"
    echo "  Este script requer a variÃ¡vel de ambiente \$STABLE_BRANCH definida." 
    echo "  Essa variÃ¡vel deve conter o nome da branch estÃ¡vel que alimenta a branch de produÃ§Ã£o."
    echo "  Exemplo: 'develop'"
fi
if [ -e "$PROD_BRANCH/." ]; then
    (( ENV_ERROR++ ))
    echo "#ENV_ERROR_$ENV_ERROR"
    echo "  Este script requer a variÃ¡vel de ambiente \$PROD_BRANCH definida." 
    echo "  Essa variÃ¡vel deve conter o nome da branch de produÃ§Ã£o."
    echo "  Exemplo: 'master'"
fi
if [ $ENV_ERROR -gt 0 ]; then
    exit 1
fi
if [[ $# -ne 2 ]]; then
    echo "Erro: quantidade parÃ¢metros incorreta."
    exibir_uso_correto
    exit 1
fi

git rev-parse --git-dir > /dev/null 2>&1;
if [[ $? -ne 0 || ! -f "version.txt" ]];
then
    echo "Este script precisa ser executado na raiz do repositÃ³rio."
    echo "Pois Ã© lÃ¡ que estÃ¡ o arquivo 'version.txt' que precisa ser atualizado."
    exit 1
fi

# InÃ­cio das operaÃ§Ãµes

# Posiciona-se no commit mais recente da branch estÃ¡vel

git switch $STABLE_BRANCH
git pull

# Efetua ajustes da nova versÃ£o

git checkout -b release/$1
echo $1 > version.txt
git add version.txt
git commit -m "$COMMIT_MSG_NEW_VERSION"

# Incorpora a branch estÃ¡vel Ã  branch de produÃ§Ã£o

git switch $PROD_BRANCH
git pull
git merge --no-ff --no-edit -Xtheirs release/$1
git push
git tag $1
git push origin $1

# Atualizar elementos relativos ao prÃ³ximo release

git switch $STABLE_BRANCH
git pull
echo $2 > version.txt
git add version.txt
git commit -m "$COMMIT_MSG_NEW_VERSION"
git push

