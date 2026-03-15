#!/bin/sh
# Прогон регистрации нового пользователя (2 шага).
#
# Использование:
#   ./reg.sh --login=user@example.com --password=Secret123!
#
# Запускать из d:/var/home/wepps.platform/tools/bruno/WeppPlatformV1/

LOGIN=""
PASSWORD=""

for arg in "$@"; do
  case $arg in
    --login=*)    LOGIN="${arg#--login=}" ;;
    --password=*) PASSWORD="${arg#--password=}" ;;
  esac
done

if [ -z "$LOGIN" ] || [ -z "$PASSWORD" ]; then
  echo "Использование: ./reg.sh --login=user@example.com --password=Secret123!"
  exit 1
fi

echo ""
echo "==> Шаг 1: инициация регистрации ($LOGIN)"
bru run profile/profile.post.bru --env local \
  --env-var "new_user_login=$LOGIN" --noproxy

echo ""
echo "==> Проверьте почту ($LOGIN) и вставьте токен из ссылки письма:"
printf "    token: "
read -r TOKEN

if [ -z "$TOKEN" ]; then
  echo "Токен не введён. Прерывание."
  exit 1
fi

echo ""
echo "==> Шаг 2: подтверждение регистрации"
bru run profile/profile.confirmReg.bru --env local \
  --env-var "reg_token=$TOKEN" \
  --env-var "reg_password=$PASSWORD" \
  --noproxy
