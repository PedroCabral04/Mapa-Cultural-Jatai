#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
CDIR=$( pwd )
cd $DIR

MSYS2_ARG_CONV_EXCL="*" docker compose exec -w /var/www/src mapas bash -c "pnpm install --recursive && pnpm run watch"

cd $CDIR
