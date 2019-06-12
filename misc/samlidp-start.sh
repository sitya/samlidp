#!/bin/bash

sed -i -e "s/REMOTE_LOGSERVER_AND_PORT/@@$REMOTE_LOGSERVER_AND_PORT/" /etc/rsyslog.conf && \
/app/bin/console d:s:u -f && \
/app/bin/console samli:createDomainOne && \
/rsyslog-start.sh

if [ ! -z "$SAMLIDP_RUNNING_MODE" ]; then
  if [ "$SAMLIDP_RUNNING_MODE" = "frontend" ]; then
  	sed -i -e "s/SAMLIDP_HOSTNAME/$SAMLIDP_HOSTNAME/" /etc/nginx/sites-available/default.conf && \
  	cd /etc/pki && openssl aes-256-cbc -md md5 -d -a -k $VAULT_PASS -in wildcard_certificate.key.enc -out wildcard_certificate.key && \
  	/start.sh
  elif [ "$SAMLIDP_RUNNING_MODE" = "backend" ]; then
  	echo "crond started."
  	crond -l 2 -f
  else
  	echo "Wrong value set for \$SAMLIDP_RUNNING_MODE envvar: it must be set to 'frontend' or 'backend'."
  fi
else
  echo "Missing \$SAMLIDP_RUNNING_MODE envvar: it must be set to 'frontend' or 'backend'."
  exit 1
fi
