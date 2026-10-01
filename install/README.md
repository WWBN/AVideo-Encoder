# Guided installation

Open `/install/` before configuring the application. Setup checks its requirements, tests the database connection without writing data, and provides English error messages with repair commands. Expand **Ubuntu setup help and troubleshooting commands** for PHP packages, service checks, file permissions, routing, and product-specific dependencies. The examples target Ubuntu 22.04 and 24.04; use extension packages matching the PHP version serving the site.

Run Composer/npm as the application owner. Run CLI installation as the account that will run PHP, so that the generated configuration remains readable by that account. Installers do not change operating-system packages or permissions automatically.

Use a new database for a new installation. A failed schema import can leave empty tables because MySQL DDL is not transactional. Initial application records are transactional. Correct the reported cause and retry; setup does not delete existing application data. If database initialization succeeds but configuration publication fails, follow the recovery command and preserve the prepared file instead of reinstalling.

After completion, setup hides the form, requirements, paths, and troubleshooting details. Reopening setup does not execute the application configuration or reveal database credentials. Recover existing installations from their configuration backup; do not reinstall over populated tables.

## Shared presentation

Keep these files byte-identical across Streamer, Encoder and Encoder Network:

- `installer.css` and `installer.js`
- `ubuntu-help.php` and `ubuntu-help-functions.php`
- `assets/logo.png` and `assets/favicon.png`

The images are copies of the Streamer `view/img/logo.png` and `view/img/favicon.png`. Each repository carries its own copies so installations do not depend on a neighboring checkout or a CDN.

## Verification

Use an isolated MySQL/MariaDB server and a test account with CREATE/DROP privileges. The integration scripts create and remove only their temporary databases and files; they never read the real application configuration. Supply a password through `MYSQL_PWD` when needed. PHP must have the required extensions enabled.

```sh
python3 tests/installer_integration.py --php php --mysql mysql --port 3306
```

From the Streamer checkout, compare all three installers and check their locked pages without a database:

```sh
python3 tests/installers_consistency.py --php php --encoder /path/to/AVideo-Encoder --network /path/to/AVideo-Encoder-Network
```

HTTP/database tests use a local mock Streamer for remote administrator verification. Test a real Streamer connection and a complete encoding/upload workflow in your deployment as well.

## Encoder settings

A unique table prefix allows several Encoders to share a database. Prefixes apply to tables, foreign-key references, and constraint names. Other applications' tables are preserved. A manually imported schema may include the built-in format catalog; the Encoder application tables must be empty.

The web installer verifies the administrator with the selected Streamer. The initial stored password uses the Streamer encoded-password protocol. Multiple allowed Streamer URLs are supported, one per line; an empty list allows all Streamers. FFmpeg/FFprobe must be executable through the PHP service PATH.

The CLI retains unattended provisioning without contacting the Streamer, which may still be starting. Set `STREAMER_PASSWORD`; there is no default administrator password. `install.php` retains its positional arguments, and `cli.php` reads the deployment environment. Both return a nonzero status on failure.

## Monitor cron

`install/cron.php` runs `objects/EncoderMonitor.php`. It belongs to this Encoder installation, not to a Streamer: one Encoder serves several AVideo sites with one cron. It must run every minute as the web server user, never as root, on the same host or container as the encoding workers, because it checks their process IDs. Set `recoverDeadWorkers` to `false` when that is not possible.

Install it once for the folder where this Encoder lives. As root, the installer writes `/etc/cron.d/avideo-encoder-<hash>`, so several Encoders on one server get separate entries:

```sh
sudo php /path/to/encoder/install/installCron.php            # detects the web server user
sudo php /path/to/encoder/install/installCron.php --user=www-data
php /path/to/encoder/install/installCron.php --print         # only shows the entry
```

Only root can install it, for security: the web server user can never add a cron, and the installer has no user-crontab fallback. The root-owned `/etc/cron.d` entry still runs the job as the web server user, never as root, because `install/cron.php` is writable by that user. To check it, run `ls -l /etc/cron.d/avideo-encoder-*` and `cat /etc/cron.d/avideo-encoder-*`. The Encoder Docker image runs the installer on every start and starts the cron service. When the Encoder runs inside another container, run the installer inside that container. The admin page shows the exact command for its folder.

Each run requeues transient errors and jobs whose worker process died (up to `Encoder::MAX_AUTO_RETRIES`), and starts the queue when jobs wait while nothing runs. It asks the job's own Streamer to e-mail the video owner when a job processes for more than one hour, waits for more than one day, fails, or stays failed for more than one day. While nothing changes, at most one reminder per day is sent, for up to 7 days. Only the newest job of a video is reported, and the Streamer ignores alerts for videos that no longer wait for the encoder. A Streamer that does not answer is paused for an hour, and each run stops sending after 50 seconds. Errors that the automatic retry will handle are reported only after the retries stop. Encoder administrators (Streamers marked as admin) receive alerts for low disk space, a stalled queue and an error spike, at most every 6 hours.

The admin page warns when the `8.3` database update is pending, when the cron is not installed or has not run for 10 minutes, when its last run failed (including being started as root), and which AVideo sites did not accept alerts in the last 24 hours. A site needs `objects/aVideoEncoderAlert.json.php`, so older AVideo versions are listed there until they are updated. Thresholds can be changed in `videos/configuration.php`, using the keys of `EncoderMonitor::defaults()`:

```php
$global['encoderMonitor'] = ['processingAlertMinutes' => 120, 'adminAlerts' => false];
```
