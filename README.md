<p align="center">
	<img src="https://avideo.tube/website/assets/151/images/avideo_encoder1.png" alt="AVideo Encoder" />
</p>

# AVideo - Encoder

<p align="center">
	<a href="https://github.com/WWBN/AVideo-Encoder/actions/workflows/validate.yml"><img alt="Validate/Lint" src="https://github.com/WWBN/AVideo-Encoder/actions/workflows/validate.yml/badge.svg?branch=master"></a>
	<a href="https://github.com/WWBN/AVideo-Encoder/actions/workflows/tests.yml"><img alt="PHPUnit Tests" src="https://github.com/WWBN/AVideo-Encoder/actions/workflows/tests.yml/badge.svg?branch=master"></a>
	<a href="https://github.com/WWBN/AVideo-Encoder/actions/workflows/codeql.yaml"><img alt="CodeQL" src="https://github.com/WWBN/AVideo-Encoder/actions/workflows/codeql.yaml/badge.svg?branch=master"></a>
	<a href="https://github.com/WWBN/AVideo-Encoder/actions/workflows/docker-image.yml"><img alt="Docker Image CI" src="https://github.com/WWBN/AVideo-Encoder/actions/workflows/docker-image.yml/badge.svg?branch=master"></a>
</p>

This repository contains the dedicated encoder service for <a href="https://avideo.com/" target="_blank">AVideo</a>. It is responsible for ingesting media, managing encoding jobs, generating derivatives, and sending processed media back to an AVideo streamer instance.

AVideo is an open source video platform that lets you run your own video site, import content from supported providers, and manage transcoding on your own infrastructure.

## Highlights

- Dedicated encoding queue for the AVideo platform
- FFmpeg-based video and audio processing
- Import support for remote media sources and video platforms
- Dockerized deployment option
- GitHub Actions workflows for linting, tests, security analysis, and Docker image publishing

## Related Links

- Official site: <a href="https://avideo.tube/" target="_blank">avideo.tube</a>
- Main platform repository: <a href="https://github.com/WWBN/AVideo" target="_blank">WWBN/AVideo</a>
- Public encoder: <a href="https://encoder.avideo.com/" target="_blank">encoder.avideo.com</a>
- Encoder network: <a href="http://git-encoder-network.avideo.tube/" target="_blank">AVideo Encoder Network</a>
- Installation tutorial: <a href="https://tutorials.avideo.com/video/streamer-and-encoder" target="_blank">Streamer and Encoder tutorial</a>

## Demo Instances

- <a href="https://flix.avideo.com/" target="_blank">Flix Style Demo</a>
- <a href="https://tutorials.avideo.com/" target="_blank">Tutorials Gallery</a>
- <a href="http://demo.avideo.com/" target="_blank">Full-Access Demo</a>

# First thing...
I would humbly like to thank God for giving me the necessary knowledge, motivation, resources and idea to be able to execute this project. Without God's permission this would never be possible.

**For of Him, and through Him, and to Him, are all things: to whom be glory for ever. Amen.**
`Apostle Paul in Romans 11:36`
# This Software must be used for Good, never Evil. It is expressly forbidden to use AVideo to build porn sites, violence, racism or anything else that affects human integrity or denigrates the image of anyone.

# Now you can read the rest...

## Important Information

> The streamer can run on multiple environments, including Windows, but the encoder is designed around Linux tooling and shell access. A Linux server with administrative access is strongly recommended.
> Hosting panels such as cPanel, Plesk, Webmin, and similar environments can block required system-level dependencies or command execution. For production deployments, prefer a VPS or dedicated server where you control the operating system packages and services.

I do not want to read, I just want installation instructions.

Start here: https://tutorials.avideo.com/video/streamer-and-encoder

### Need help installing or configuring AVideo?

https://streamphp.com/services



<p align="center">
	<a href="https://encoder.avideo.com/" target="_blank">View Public Encoder</a>
</p>

# Why do I need the Encoder?
You should install the encoder when:

- You want private or dedicated transcoding capacity
- Your infrastructure is faster than the public encoder service
- Your streamer is on a private network or behind a firewall
- Your server does not expose a public IP address

The public encoder cannot reliably push media back to streamer instances that are only reachable through private address space such as:

- 10.0.0.0/8
- 127.0.0.0/8 (Localhost)
- 172.16.0.0/12
- 192.168.0.0/16

In these cases, a private encoder installation is required.

# AVideo Platform Script
Go get it <a href="https://github.com/WWBN/AVideo" target="_blank">here</a>

<p align="center">
	<img src="https://avideo.tube/website/assets/151/images/avideo_encoder1.png" alt="AVideo Platform" />
	<br />
	<a href="https://demo.avideo.com/" target="_blank">View Demo</a>
</p>

# Server Requirements

The repository depends on operating system tools in addition to PHP application code. Based on the current codebase and Docker image, the practical requirements are:

- Linux server with shell access
- Apache 2.x with `mod_rewrite` enabled
- PHP 8.1+ for current CI coverage
- MySQL or MariaDB
- FFmpeg and FFprobe
- Python 3
- `yt-dlp`
- `exiftool`
- PHP command execution functions such as `exec` and `shell_exec`

> Note: the current Dockerfile still uses a legacy `php:7-apache` base image even though the repository CI validates PHP 8.1 to 8.3. If you rely on Docker for production, treat the image definition as a compatibility item that should be reviewed separately.

# What is new on this version?
Since version 4.x, the streamer and encoder are separated so they can be deployed independently.

- The streamer site is the main user-facing application.
- The encoder site is responsible for queueing and processing media conversions.
- You can use the public encoder service or operate your own private encoder infrastructure.
- A private encoder is the recommended choice when you need predictable throughput, network isolation, or infrastructure-level control.

<p align="center">
	<img src="https://avideo.tube/website/assets/151/images/avideo_encoder1.png" alt="Download AVideo Encoder" />
	<br />
	<a href="https://github.com/WWBN/AVideo-Encoder" target="_blank">Download Encoder</a>
</p>

# Older version
If you want the old version with Streamer and Encoder together (Version 3.4.1) download it <a href="https://github.com/WWBN/AVideo/releases/tag/3.4.1">here</a>

# Docker

This repository includes a Docker environment for the AVideo Encoder. You can build the image directly from this repository or pull a published image when available.

The container can be configured through environment variables that mirror the installer options:

- `SERVER_NAME` defines the name of the server used for internal configuration - default is `localhost`
- `SERVER_URL` defines the external URL of the encoder - default is `https://localhost/`
- `DB_MYSQL_HOST` defines the database hostname - default is `database`
- `DB_MYSQL_PORT` defines the database port - default is `3306`
- `DB_MYSQL_NAME` defines the database name - default is `avideo`
- `DB_MYSQL_USER` defines the database username - default is `avideo`
- `DB_MYSQL_PASSWORD` defines the database password - default is `avideo`
- `STREAMER_URL` defines the streamer url - default is `https://localhost/`
- `STREAMER_USER` defines the streamer username - default is `admin`
- `STREAMER_PASSWORD` defines the streamer password - default is `password`
- `STREAMER_PRIORITY` defines the streamer priority - default is `1`
- `CREATE_TLS_CERTIFICATE` defines, if the image should generate its ssl selfsigned certificate - default is `yes`
- `TLS_CERTIFICATE_FILE` defines the location of the HTTPS tls certificate - default is `/etc/apache2/ssl/localhost.crt`
- `TLS_CERTIFICATE_KEY` defines the location of the HTTPS tls certificate key - default is `/etc/apache2/ssl/localhost.key`
- `CONTACT_EMAIL` defines the contact mail address - default is `admin@localhost`
- `PHP_POST_MAX_SIZE` defines the PHP max POST size for uploads - default is `100M`
- `PHP_UPLOAD_MAX_FILESIZE` defines the PHP max upload file size - default is `100M`
- `PHP_MAX_EXECUTION_TIME` defines the PHP max execution time for threads during encoding - default is `7200`
- `PHP_MEMORY_LIMIT` defines the PHP memory limit - default is `512M`
