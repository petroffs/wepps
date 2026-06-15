#!/bin/sh
# Запуск авто-тестов: auth + все GET
# Использование:
#   ./test.sh                        — только GET (без auth)
#   ./test.sh --password=mypassword  — GET + auth
#
# Запускать из d:/var/home/wepps.platform/tools/bruno/WeppPlatformV1/

PASS=""
for arg in "$@"; do
  case $arg in
    --password=*) PASS="${arg#--password=}" ;;
  esac
done

BASE_CMD="bru run APP/goods APP/news APP/slides --env local ----noproxy"

if [ -n "$PASS" ]; then
  $BASE_CMD auth.login.bru auth.refresh.bru auth.confirm.bru --env-var "user_password=$PASS"
else
  $BASE_CMD
fi
