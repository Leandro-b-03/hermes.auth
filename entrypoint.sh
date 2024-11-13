#!/bin/bash

#-----------------------------------------------------------------------------------------------------------------------------------------------------------
# The  entrypoint.sh  script is responsible for checking if the  composer install
# command has been run. If it hasn’t, it runs the command and sets the  COMPOSER_INSTALL_RUN  environment variable to  true.
# The script then runs the command passed to the  docker run  command. In this case, it runs the  php artisan serve  command.
#-----------------------------------------------------------------------------------------------------------------------------------------------------------

# Read .env file and check if composer install has run
if [ -z "$COMPOSER_INSTALL_RUN" ] || [ "$COMPOSER_INSTALL_RUN" == "false" ]; then
  composer install
  echo "COMPOSER_INSTALL_RUN=true" >> .env
fi

# Run the command passed to the docker run command
php aritsan serve --host=0.0.0.0 --port 7002