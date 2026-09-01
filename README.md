<p align="center">
	<img src="https://raw.githubusercontent.com/WWBN/AVideo-Encoder/master/view/img/logo.png" alt="AVideo Encoder" />
</p>

<p align="center">
	<a href="https://github.com/WWBN/AVideo-Encoder/actions/workflows/validate.yml"><img alt="Validate/Lint" src="https://github.com/WWBN/AVideo-Encoder/actions/workflows/validate.yml/badge.svg?branch=master"></a>
	<a href="https://github.com/WWBN/AVideo-Encoder/actions/workflows/tests.yml"><img alt="PHPUnit Tests" src="https://github.com/WWBN/AVideo-Encoder/actions/workflows/tests.yml/badge.svg?branch=master"></a>
	<a href="https://github.com/WWBN/AVideo-Encoder/actions/workflows/codeql.yaml"><img alt="CodeQL" src="https://github.com/WWBN/AVideo-Encoder/actions/workflows/codeql.yaml/badge.svg?branch=master"></a>
	<a href="https://github.com/WWBN/AVideo-Encoder/actions/workflows/docker-image.yml"><img alt="Docker Image CI" src="https://github.com/WWBN/AVideo-Encoder/actions/workflows/docker-image.yml/badge.svg?branch=master"></a>
	<br/>
	<a href="https://github.com/WWBN/AVideo-Encoder/stargazers"><img src="https://img.shields.io/github/stars/WWBN/AVideo-Encoder?style=flat-square" alt="GitHub Stars"/></a>
	<a href="https://github.com/WWBN/AVideo-Encoder/network/members"><img src="https://img.shields.io/github/forks/WWBN/AVideo-Encoder?style=flat-square" alt="GitHub Forks"/></a>
	<a href="https://github.com/WWBN/AVideo-Encoder/commits/master"><img src="https://img.shields.io/github/last-commit/WWBN/AVideo-Encoder?style=flat-square" alt="Last Commit"/></a>
</p>

### [!IMPORTANT] Domain Update Notice

Our previous domains — **youphptube.com** and **youphp.tube** — are being retired following a dispute initiated by **Google LLC (YouTube)**.
Although we firmly believe that **YouPHPTube** has always been an **independent, open-source project**, created to empower developers and organizations to host their own video platforms, we have decided to **respect the process and move forward peacefully**.

🆕 **Please update your bookmarks and references to the new official domain:**

👉 [https://streamphp.com/](https://streamphp.com/)

Thank you for your continued support and for standing with open-source freedom.

## Introduction to the AVideo Encoder

This repository contains the dedicated encoder service for <a href="https://github.com/WWBN/AVideo" target="_blank">AVideo</a>. It is responsible for ingesting media, managing encoding jobs, generating derivatives (HLS, MP4, WebM, MP3), and sending processed media back to one or more AVideo streamer instances.

AVideo is an open source video platform that lets you run your own video site, import content from supported providers, and manage transcoding on your own infrastructure.

## 🌟 Key Features of the AVideo Encoder

1. **⚙️ Dedicated Encoding Queue**: A private, dedicated encoding queue for your AVideo platform, independent of the shared public encoder.
2. **🎞️ FFmpeg-Based Processing**: Convert video and audio into web-ready [HLS, MP4, WebM, and MP3](https://github.com/WWBN/AVideo/wiki/VideoHLS-Plugin), including multiple audio tracks and multi-resolution renditions.
3. **📥 Remote Media Import**: Import support for remote media sources and video platforms (YouTube, Vimeo, Dailymotion, and more) via `yt-dlp`.
4. **🔐 Encrypted HLS Output**: Produce encrypted HLS streams compatible with AVideo's [content protection](https://github.com/WWBN/AVideo/wiki/VideoHLS-Plugin#download-protection).
5. **🔗 Multi-Streamer & Network Support**: Register one encoder with multiple streamer sites, or join the [AVideo Encoder Network](http://git-encoder-network.avideo.tube/) to share capacity.
6. **🐳 Dockerized Deployment**: Build and run the encoder as a container, fully configurable through environment variables.
7. **✅ CI-Covered Codebase**: GitHub Actions workflows for linting, PHPUnit tests, CodeQL security analysis, and Docker image publishing run on every change.

## 🔗 Related Links

- Main platform repository: <a href="https://github.com/WWBN/AVideo" target="_blank">WWBN/AVideo</a>
- Public encoder: <a href="https://encoder.avideo.com/" target="_blank">encoder.avideo.com</a>
- Encoder network: <a href="http://git-encoder-network.avideo.tube/" target="_blank">AVideo Encoder Network</a>
- Installation tutorial: <a href="https://tutorials.avideo.com/video/streamer-and-encoder" target="_blank">Streamer and Encoder tutorial</a>

## 🌐 Demo Instances

- **[AVideo Platform Full-Access Demo](http://demo.avideo.com/)** — **User**: admin / **Password**: 123 (non-admin, comment-only: test / test)
- **[AVideo Platform Flix Demo](https://flix.avideo.com/)** — **User**: test / **Password**: test
- **[AVideo Platform Gallery Demo](https://tutorials.avideo.com/)** — comment/like/subscribe only, uploads disabled

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
	<a href="https://demo.avideo.com/" target="_blank">View Demo</a>
</p>

# 🖥️ Server Requirements

The repository depends on operating system tools in addition to PHP application code. Based on the current codebase and Docker image, the practical requirements are:

[![Minimum PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://php.net/) - **PHP**: Version 8.1 or higher, matching current CI coverage (PHP 8.1–8.3).

[![Minimum MySQL Version](https://img.shields.io/badge/MySQL-5.0%2B-blue)](https://www.mysql.com/) - **MySQL/MariaDB**: Required to store the encoding queue and configuration.

[![Minimum Apache Version](https://img.shields.io/badge/Apache-2.x%20%28mod__rewrite%29-blue)](https://httpd.apache.org/) - **Apache**: Version 2.x with the `mod_rewrite` module enabled.

Additional requirements:

- Linux server with shell access
- FFmpeg and FFprobe
- Python 3 with `yt-dlp` installed
- `exiftool`
- PHP command execution functions such as `exec` and `shell_exec`

> Note: the current Dockerfile still uses a legacy `php:7-apache` base image even though the repository CI validates PHP 8.1 to 8.3. If you rely on Docker for production, treat the image definition as a compatibility item that should be reviewed separately.

# Crucial Advisory: Strictly Avoid Using Control Panels for Installation

**Important**: For the installation of the Encoder, it is imperative to use a Linux distribution, specifically Ubuntu, **without any type of control panel**. This includes avoiding panels like cPanel, Plesk, Webmin, VestaCP, and similar.

Control panels significantly interfere with the necessary system access and processes required for a successful installation. They restrict the installation of essential libraries and the compilation of critical software required by the encoder, such as FFmpeg and yt-dlp.

**Please be advised**: Installing the encoder on a server with any control panel is highly discouraged and is likely to result in installation failure. We cannot provide support or guarantee success in such scenarios.

# What is new on this version?
Since version 4.x, the streamer and encoder are separated so they can be deployed independently.

- The streamer site is the main user-facing application.
- The encoder site is responsible for queueing and processing media conversions.
- You can use the public encoder service or operate your own private encoder infrastructure.
- A private encoder is the recommended choice when you need predictable throughput, network isolation, or infrastructure-level control.

<p align="center">
	<a href="https://github.com/WWBN/AVideo-Encoder" target="_blank">Download Encoder</a>
</p>

# Older version
If you want the old version with Streamer and Encoder together (Version 3.4.1) download it <a href="https://github.com/WWBN/AVideo/releases/tag/3.4.1">here</a>

# 🐳 Docker

This repository includes a Docker environment for the AVideo Encoder. You can build the image directly from this repository or pull a published image when available.

The container can be configured through environment variables that mirror the installer options:

| Variable | Description | Default |
| --- | --- | --- |
| `SERVER_NAME` | Name of the server used for internal configuration | `localhost` |
| `SERVER_URL` | External URL of the encoder | `https://localhost/` |
| `DB_MYSQL_HOST` | Database hostname | `database` |
| `DB_MYSQL_PORT` | Database port | `3306` |
| `DB_MYSQL_NAME` | Database name | `avideo` |
| `DB_MYSQL_USER` | Database username | `avideo` |
| `DB_MYSQL_PASSWORD` | Database password | `avideo` |
| `STREAMER_URL` | Streamer URL | `https://localhost/` |
| `STREAMER_USER` | Streamer username | `admin` |
| `STREAMER_PASSWORD` | Streamer password | `password` |
| `STREAMER_PRIORITY` | Streamer priority | `1` |
| `CREATE_TLS_CERTIFICATE` | Whether the image should generate a self-signed SSL certificate | `yes` |
| `TLS_CERTIFICATE_FILE` | Location of the HTTPS TLS certificate | `/etc/apache2/ssl/localhost.crt` |
| `TLS_CERTIFICATE_KEY` | Location of the HTTPS TLS certificate key | `/etc/apache2/ssl/localhost.key` |
| `CONTACT_EMAIL` | Contact email address | `admin@localhost` |
| `PHP_POST_MAX_SIZE` | PHP max POST size for uploads | `100M` |
| `PHP_UPLOAD_MAX_FILESIZE` | PHP max upload file size | `100M` |
| `PHP_MAX_EXECUTION_TIME` | PHP max execution time for threads during encoding | `7200` |
| `PHP_MEMORY_LIMIT` | PHP memory limit | `512M` |

# 🛠️ Errors and Troubleshooting

Encountered an issue? Our [error identification guide](https://github.com/WWBN/AVideo/wiki/How-to-find-errors-on-AVideo-Platform) is designed to help you troubleshoot and resolve common problems efficiently.

## 🌟 AVideo Platform Certified Support

Require specialized assistance? Our team of certified AVideo Platform developers is here to help. For professional support and expert consulting on installation, consulting, or plugins, reach out to [Daniel Neto](https://streamphp.com/marketplace/). We're committed to ensuring a seamless and effective AVideo Encoder installation and setup.
