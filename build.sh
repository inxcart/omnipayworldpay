#!/usr/bin/env bash
CWD_BASENAME=${PWD##*/}
CWD_BASEDIR=${PWD}
echo ${CWD_BASEDIR}

# Cleanup
rm pre-scoper/ -rf
rm vendor/ -rf
rm build/ -rf

# Composer install and scoping
composer install --no-dev --prefer-dist
mv vendor/ pre-scoper/
php-scoper add-prefix -p ThirtyBeesMollie -n

# Cleanup
mv build/pre-scoper/ vendor/
rm pre-scoper/ -rf
rm build/ -rf

# Dump autoload
composer -o dump-autoload

FILES+=("logo.gif")
FILES+=("logo.png")
FILES+=("${CWD_BASENAME}.php")
FILES+=("index.php")
FILES+=("classes/**")
FILES+=("controllers/**")
FILES+=("mails/**")
FILES+=("translations/**")
FILES+=("upgrade/**")
FILES+=("views/**")

MODULE_VERSION="$(sed -ne "s/\\\$this->version *= *['\"]\([^'\"]*\)['\"] *;.*/\1/p" ${CWD_BASENAME}.php)"
MODULE_VERSION=${MODULE_VERSION//[[:space:]]}
ZIP_FILE="${CWD_BASENAME}/${CWD_BASENAME}-v${MODULE_VERSION}.zip"

echo "Going to zip ${CWD_BASENAME} version ${MODULE_VERSION}"

cd ..
rm ${ZIP_FILE}
for E in "${FILES[@]}"; do
  find ${CWD_BASENAME}/${E}  -type f -exec zip -9 ${ZIP_FILE} {} \;
done
