# WordPress Docker Development Environment

This is a Docker based local development environment for WordPress for the Wizeline Academy Advanced PHP Certification. It is based on the wp-local-docker project by 10up that can be found [here](https://github.com/10up/wp-local-docker).

For internal use only, please do not distribute

## Setup

1. Clone repository
```bash
    $ git clone git@github.com:wizelineacademy/talent-php-wp.git <my-project-name>
```
2. Change directory to project folder
```bash
    cd <my-project-name>
```
3. Start up the docker containers
```bash
    $ docker-compose up -d
```
4. Run setup to download and install WordPress.
```bash
    $ sh bin/setup.sh
```

---

Default MySQL connection information (from within PHP-FPM container):

```
Database: advanced_php_project
Username: wizeline
Password: academyGDL
Host: mysql
```

Default WordPress admin credentials:

```
Username: admin
Password: password
```

Note: if you provided details different to the above during setup, use those instead.
