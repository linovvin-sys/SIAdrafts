# Deploying to Railway

The repo ships a `Dockerfile` (PHP 8.3 + Apache, so `.htaccess` works) and `railway.json`, which tells Railway to use it.

## Steps
1. Railway → **New Project → Deploy from GitHub repo** → pick this repo/branch.
2. In the project, **+ New → Database → MySQL**.
3. On the web service → **Variables**, set (use references to the MySQL service):
   - `DB_HOST=${{MySQL.MYSQLHOST}}`
   - `DB_PORT=${{MySQL.MYSQLPORT}}`
   - `DB_USERNAME=${{MySQL.MYSQLUSER}}`
   - `DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}`
   - `DB_DATABASE=${{MySQL.MYSQLDATABASE}}`
   - `CSRF_SECRET` (random 32+ chars), `SMTP_*`, `PAYMONGO_*`, `RECAPTCHA_*` (see `SIAdrafts/.env.example`)
4. Import the schema once: `mysql -h <host> -P <port> -u <user> -p <db> < enrollment_db_sia_final-4.sql`
   (use the MySQL service's public connection details, or `railway connect MySQL`).
5. Service → **Settings → Networking → Generate Domain**. The root URL redirects to `/SIAdrafts/Frontend/View/index`.

Note: `storage/` and `Backend/uploads` live on the container's ephemeral disk; attach a Railway Volume if uploads must persist.
