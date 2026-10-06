# Sofiene's Windows environment

The existing Git 2.46.1, Node.js 24.20.0 and npm 11.6.1 were reused. XAMPP's PHP
8.2.12 and MariaDB 10.4.32 were left intact. Current Laravel Doctrine requires
PHP 8.3+, and the university asks for MySQL, so separate tools were installed:

| Tool | Version | Location |
| --- | --- | --- |
| PHP | 8.4.26 | `%LOCALAPPDATA%\OliveTraceTools\php84` |
| Composer | 2.10.3 | `%LOCALAPPDATA%\OliveTraceTools\composer.phar` |
| MySQL Community | 8.4.9 | `%LOCALAPPDATA%\OliveTraceTools\mysql84\mysql-8.4.9-winx64` |

The PHP and Composer archives were checked against official SHA-256 hashes.
MySQL uses its own data directory at `%LOCALAPPDATA%\OliveTraceTools\mysql-data`.
It listens only on `127.0.0.1:3306`; XAMPP configuration/data were not changed.
It runs as a user process, not an automatically installed Windows service.

Activate these tools in each new PowerShell session:

```powershell
cd C:\Users\sofie\Desktop\Career\Projects\OliveTrace
. .\scripts\Use-OliveTrace.ps1
.\scripts\Start-LocalMySql.ps1
php -v
composer --version
git --version
node -v
npm.cmd -v
mysql --version
mysqladmin --defaults-extra-file="$env:LOCALAPPDATA\OliveTraceTools\mysql-admin.cnf" ping
php artisan serve
```

The activation changes PATH only in that terminal. Outside it, `php` may still
resolve to XAMPP's PHP 8.2. That is intentional so unrelated projects retain their
existing runtime. The helpers are optional and specific to this installation;
teammates with suitable tools use the standard README setup.

MySQL's local application user has access only to `olivetrace` and
`olivetrace_testing`. Its generated password is only in the ignored `.env`.
The generated local root credentials are in
`%LOCALAPPDATA%\OliveTraceTools\mysql-admin.cnf`, outside the repository.
Use the latter for local administration without placing passwords on the command
line. Treat both files as private.

To stop this isolated server normally:

```powershell
mysqladmin --defaults-extra-file="$env:LOCALAPPDATA\OliveTraceTools\mysql-admin.cnf" shutdown
```

PHP extensions verified: Ctype, cURL, DOM, Fileinfo, Filter, Hash, Mbstring,
OpenSSL, PCRE, PDO, Session, Tokenizer, XML, `pdo_mysql`, plus Zip, Intl and
`pdo_sqlite`/SQLite3 for local tests. No Laravel CLI installer is required.

Official sources: [PHP Windows downloads](https://windows.php.net/download/),
[Composer downloads](https://getcomposer.org/download/),
[MySQL manual archive setup](https://dev.mysql.com/doc/refman/8.4/en/windows-install-archive.html).
