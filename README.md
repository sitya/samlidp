# samlidp

This repository contains the simplified code of samlidp.io. It is focused on what NRENs need, so now it is easy to host a SAML Identity Provider as a Service for the institutes of your NREN.

Using this service is free, but donations are welcome and will go towards further development of this project. Use the wallet addresses below to donate.

	BTC: 1NEvwJhgMeK4bSnRK8ir7v37B6ARhpUhYK

*Thank you for your support and generosity!*

## Requirements

* VM with docker hosting environment
* relational database service
* logging service
* smtp service
* some persistent storage
* a dedicated domain for your service
* a wildcard certificate for your choosen domain (`$SAMLIDP_HOSTNAME`) for this service (the main domain will host your service, the third level domains will host the IdPs)
* open 80/443 ports for incoming traffic and open for outgoing traffic on your firewall

## Installation

1. Clone this repository
1. Grab your wildcard certificate from your CA, use `wildcard_certificate.key/wildcard_certificate.crt` names for them.
1. Encrypt the key: `openssl aes256 -a -salt -k $VAULT_PASS -in wildcard_certificate.key -out wildcard_certificate.key.enc`
then put the certificate and the encrypted key into `conf/credentials` folder. You will need this `$VAULT_PASS` for starting the app, so save it. Depending your CA but you may have to merge the certificatechain into `wildcard_certificate.crt`.
1. Configure storage service in `app/app/config/config.yml` and logging service in `app/app/config/config_prod.yml`. There are lots of [Monolog handlers](https://github.com/Seldaek/monolog/tree/master/src/Monolog/Handler) you can use directly.
1. Generate a self-signed certificate for the attribute release checking service, which will be deployed to `attributes.$SAMLIDP_HOSTNAME`. `cd conf/credentials && openssl req -new -newkey rsa:2048 -x509 -days 3652 -nodes -out attributes.$SAMLIDP_HOSTNAME.crt -keyout attributes.$SAMLIDP_HOSTNAME.key`. Important: value of `CN` must be `$SAMLIDP_HOSTNAME`. In case the attribute release checking service page fails to open and you get an error `Unable to load private key from file "/app/vendor/simplesamlphp/simplesamlphp/cert/attributes.$SAMLIDP_HOSTNAME` make sure the key file has appropriate read permissions and build the image again.
1. Build the image: `docker build -t samli/nren .`
1. Edit `docker-compose.yml`, fill the values environment variables. (See details below)
1. Start the service: `docker-compose up`
1. Connect to your samlidp service on your choosen domain via your browser
1. Register an `admin` user, then run in the container: `app/bin/console fos:user:promote admin ROLE_SUPER_ADMIN`. After logout and login this user can be able to edit all the registered IdPs and users.
1. Test the app, register an IdP, add users, test against an SP...etc

## Customization

1. You can find the templates at `app/app/src/AppBundle/Resources/views/default`, the used english texts at `app/app/Resources/AppBundle/translations`. You can make other translations easily without modifying original templates.
1. Additionally you should customize text of mails (`app/app/src/AppBundle/Resources/views/IdPUser/*.txt`) and login theme of IdP (`misc/themesamlidpio`).
1. While you are doing this customization, you should attach these folders as volumes to your container, so you can see the changes on-the-fly. If you are ready, rebuild then restart the image.

### Parameters

| Parameter | Description |
|-----------|-------------|
| `SAMLIDP_RUNNING_MODE` | `frontend` or `backend`. Backend does the metadata processing of the handled federations. Frontend does everything else. **Required** |
| `SAMLIDP_HOSTNAME` | FQDN for your samlidp instance. **Required** |
| `VAULT_PASS` | Encryption key for samlidp secret variables. At the moment used for encrypt/decrypt key of the wildcard certificate.  **Required** |
| `DATABASE_HOST` | The database server IP address. **Required** |
| `DATABASE_PORT` | The database server port. (3306 for mysql, 5432 for postgresql) **Required** |
| `DATABASE_DRIVER` | The database type. Tested with: `pdo_mysql`, `pdo_pgsql`. **Required**|
| `DATABASE_VERSION` | The database version. **Required** |
| `DATABASE_NAME` | The database database name. **Required** |
| `DATABASE_USER` | The database database user. **Required** |
| `DATABASE_PASSWORD` | The database database password.  **Required** |
| `MAILER_HOST` | SMTP server host **Required**|
| `MAILER_PORT` | SMTP server port **Required**|
| `ENCRYPTION` | SMTP encryption (tls, ssl) **Required**|
| `MAILER_USER` | SMTP username **Required**|
| `MAILER_PASSWORD` | SMTP password **Required**|
| `MAILER_SENDER` | From address for mails sent by the app **Required**|
| `ROLLBAR_ACCESS_TOKEN` | Access token for rollbar.com. If you like to examine the potential exceptions. *Optional*|
| `S3_KEY` | S3 access key. Needed if you use S3 backend for storing logos.  *Optional*|
| `S3_SECRET` | S3 secret key *Optional*|
| `S3_REGIO` | S3 regio *Optional*|
| `S3_BUCKET` | S3 bucket *Optional*|
| `REMOTE_LOGSERVER_AND_PORT` | Remote syslog `@@host:port` for TCP, `@host:port` for UDP *Optional*|


