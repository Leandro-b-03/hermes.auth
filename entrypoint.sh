#!/bin/bash

#-----------------------------------------------------------------------------------------------------------------------------------------------------------
# The  entrypoint.sh  script is responsible for checking if the  composer install
# command has been run. If it hasn’t, it runs the command and sets the  COMPOSER_INSTALL_RUN  environment variable to  true.
# The script then runs the command passed to the  docker run  command. In this case, it runs the  php artisan serve  command.
#-----------------------------------------------------------------------------------------------------------------------------------------------------------

# Read .env file and check if composer install has run
if grep -q "COMPOSER_INSTALL_RUN=false" .env; then 
  composer install --ignore-platform-reqs --no-interaction --no-plugins --no-scripts --prefer-dist
  sed -i 's/COMPOSER_INSTALL_RUN=false/COMPOSER_INSTALL_RUN=true/g' .env 
elif ! grep -q "COMPOSER_INSTALL_RUN" .env; then
  composer install --ignore-platform-reqs --no-interaction --no-plugins --no-scripts --prefer-dist
  echo "COMPOSER_INSTALL_RUN=true" >> .env
fi

# Run the command passed to the docker run command
php artisan serve --host=0.0.0.0 --port 7002