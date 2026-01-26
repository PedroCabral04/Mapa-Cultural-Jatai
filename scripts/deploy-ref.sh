## Script para deploy de uma referÃ«ncia qualquer (branch, tag ou PR) ##

exibir_uso_correto() {
    echo "Usar: $0 <versao> [opcao]"
    echo "Onde:"
    echo "  <versao> indica a branch, a tag ou o PR a publicar."
    echo "     no caso de branch ou tag, escreva o nome dela;"
    echo "     no caso de PR, escreva pull/<numero_do_pr>"
    echo "     Exemplos:"
    echo "       $0 v7.6.0-minc14 => publicarÃ¡ a tag v7.6.0-minc14"
    echo "       $0 $STABLE_BRANCH => publicarÃ¡ a branch $STABLE_BRANCH"
    echo "       $0 pull/39 => publicarÃ¡ o PR#39"
    echo "  [opcao] indica o que deve ser feito apÃ³s a preparaÃ§Ã£o do diretÃ³rio"
    echo "     se omitida, farÃ¡ build da versÃ£o desejada, seguirÃ¡"
    echo "       realizando o build, derrubando a versÃ£o corrente, e concluirÃ¡"
    echo "       subindo a nova versÃ£o."
    echo "     --build-only farÃ¡ somente o build, sem mexer na instÃ¢ncia em execuÃ§Ã£o"
    echo "     --clone-only farÃ¡ somente a preparaÃ§Ã£o do diretÃ³rio e nem faz o build"
}
configurar_detalhes_minc() {
    mkdir $DEPLOY_DIR/docker-data
    ln -s $PRIVATE_FILES_PATH $DEPLOY_DIR/docker-data/private-files
    ln -s $PUBLIC_FILES_PATH $DEPLOY_DIR/docker-data/public-files
    cp -r $SPECS_PATH/. $DEPLOY_DIR
}

# VerificaÃ§Ãµes
ENV_ERROR=0
if [[ -z "$SPECS_PATH" ]]; then
    (( ENV_ERROR++ ))
    echo "#ENV_ERROR_$ENV_ERROR"
    echo "  Este script requer a variÃ¡vel de ambiente \$SPECS_PATH contendo um diretÃ³rio vÃ¡lido."
    echo "  Esse diretÃ³rio deve conter arquivos de especificaÃ§Ãµes nÃ£o publicados no repositÃ³rio."
fi
if [[ ! -f "$SPECS_PATH/.env" ]]; then
    (( ENV_ERROR++ ))
    echo "#ENV_ERROR_$ENV_ERROR"
    echo "  O arquivo '.env' deve estar no diretÃ³rio especificado na variÃ¡vel de ambiente \$SPECS_PATH."
    echo "  Esse arquivo deve conter as variÃ¡veis de ambiente necessÃ¡rias para o funcionamento do sistema."
fi
if [ ! -d "$PRIVATE_FILES_PATH" ]; then
    (( ENV_ERROR++ ))
    echo "#ENV_ERROR_$ENV_ERROR"
    echo "  Este script requer a variÃ¡vel de ambiente \$PRIVATE_FILES_PATH contendo um diretÃ³rio vÃ¡lido." 
    echo "  Esse diretÃ³rio serÃ¡ usado para persistir os arquivos privados do sistema."
fi
if [ ! -d "$PUBLIC_FILES_PATH" ]; then
    (( ENV_ERROR++ ))
    echo "#ENV_ERROR_$ENV_ERROR"
    echo "  Este script requer a variÃ¡vel de ambiente \$PUBLIC_FILES_PATH contendo um diretÃ³rio vÃ¡lido." 
    echo "  Esse diretÃ³rio serÃ¡ usado para persistir os arquivos pÃºblicos do sistema."
fi
if [ -z "$SOURCE_REPO" ]; then
    (( ENV_ERROR++ ))
    echo "#ENV_ERROR_$ENV_ERROR"
    echo "  Este script requer a variÃ¡vel de ambiente \$SOURCE_REPO definida." 
    echo "  Essa variÃ¡vel deve conter a URL do repositÃ³rio de origem."
fi
if [ -z "$STABLE_BRANCH" ]; then
    (( ENV_ERROR++ ))
    echo "#ENV_ERROR_$ENV_ERROR"
    echo "  Este script requer a variÃ¡vel de ambiente \$STABLE_BRANCH definida." 
    echo "  Essa variÃ¡vel deve conter o nome da branch estÃ¡vel considerada para atualizar PRs."
fi
if [ $ENV_ERROR -gt 0 ]; then
    exit 1
fi
if [[ $# -lt 1 || $# -gt 2 ]]; then
    echo "Erro: quantidade parÃ¢metros incorreta."
    exibir_uso_correto
    exit 1
else
if [[ $# -ge 2 && ( $2 != '--build-only' && $2 != '--clone-only' ) ]]; then
    echo "Erro: segundo argumento Ã© invÃ¡lido."
    exibir_uso_correto
    exit 1
fi
fi

# InÃ­cio das operaÃ§Ãµes
DEPLOY_DIR=/opt/mapas-deployed-`date +%Y%m%d%H%M`
if [ ${1:0:5} = 'pull/' ]; then
   echo 'deploying PR#'${1:5:${#1}-5}
   git clone --recurse-submodules -b $STABLE_BRANCH $SOURCE_REPO $DEPLOY_DIR && cd $DEPLOY_DIR
   if [ $? -ne 0 ]; then exit $?; fi
   configurar_detalhes_minc
   git checkout -b teste-pr${1:5:${#1}-5}-atualizado && git fetch origin $1/head && git merge --no-ff --no-edit FETCH_HEAD
   if [ $? -ne 0 ]; then exit $?; fi
   echo 'Teste do PR#'${1:5:${#1}-5}' atualizado (commits:'`git rev-parse --short $STABLE_BRANCH`'+'`git rev-parse --short FETCH_HEAD`')' > version.txt
else
   echo 'deploying branch/tag '$1
   git clone --recurse-submodules -b $1 $SOURCE_REPO $DEPLOY_DIR
   if [ $? -ne 0 ]; then exit $?; fi
   configurar_detalhes_minc
fi
cd $DEPLOY_DIR
if [ -z $2 ]; then
   docker compose build
   if [ $? -ne 0 ]; then exit $?; fi
   docker stop $(docker ps -q)
   docker compose up -d
   echo 'Deployed tag "'$1'" on directory "'$DEPLOY_DIR'". It should be already running by now.'
else
if [ $2 = '--build-only' ]; then
   docker compose build
   echo 'Deployed tag "'$1'" on directory "'$DEPLOY_DIR'". Just built, will NOT run.'
else
if [ $2 = '--clone-only' ]; then
   echo 'Deployed tag "'$1'" on directory "'$DEPLOY_DIR'". Just source-code copied, not built neither run.'
fi
fi
fi
