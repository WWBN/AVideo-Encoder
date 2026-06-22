<?php

class FTPDownloader
{
    private $ftpUrl;
    private $ftpHost;
    private $ftpUser;
    private $ftpPass;
    private $ftpPort;
    private $ftpConn;
    private $remotePath = '/';

    public function __construct($ftpUrl)
    {
        global $global;
        $this->ftpUrl = addLastSlash($ftpUrl);
        $this->parseFtpUrl($ftpUrl);
    }

    private function parseFtpUrl($ftpUrl)
    {
        global $global;
        $parsedUrl = parse_url($ftpUrl);
        if (!$parsedUrl || !isset($parsedUrl['scheme']) || $parsedUrl['scheme'] !== 'ftp') {
            throw new Exception("Invalid FTP URL");
        }

        $this->ftpHost = $parsedUrl['host'] ?? '';
        $this->ftpUser = $parsedUrl['user'] ?? 'anonymous';
        $this->ftpPass = $parsedUrl['pass'] ?? '';
        $this->ftpPort = $parsedUrl['port'] ?? 21;
        $this->remotePath = $parsedUrl['path'] ?? '/';

        if (empty($this->ftpHost) || preg_match('/^(localhost|.*\.local)$/i', $this->ftpHost)) {
            throw new Exception("Invalid FTP host");
        }

        if (!empty($global['allowPrivateNetworkURLs'])) {
            return;
        }

        $ips = [];
        if (filter_var($this->ftpHost, FILTER_VALIDATE_IP)) {
            $ips[] = $this->ftpHost;
        } else {
            $ipv4 = @gethostbynamel($this->ftpHost);
            if (is_array($ipv4)) {
                $ips = array_merge($ips, $ipv4);
            }
            $ipv6 = @dns_get_record($this->ftpHost, DNS_AAAA);
            if (is_array($ipv6)) {
                foreach ($ipv6 as $record) {
                    if (!empty($record['ipv6'])) {
                        $ips[] = $record['ipv6'];
                    }
                }
            }
        }

        $ips = array_values(array_unique(array_filter($ips)));
        if (empty($ips)) {
            throw new Exception("Could not resolve FTP host");
        }

        foreach ($ips as $ip) {
            if (ip_is_private($ip)) {
                throw new Exception("Private or reserved FTP host is not allowed");
            }
        }
    }

    public function connect()
    {
        $this->ftpConn = ftp_connect($this->ftpHost, $this->ftpPort);
        if (!$this->ftpConn) {
            throw new Exception("Could not connect to FTP server");
        }

        if (!ftp_login($this->ftpConn, $this->ftpUser, $this->ftpPass)) {
            throw new Exception("Could not log in to FTP server");
        }

        ftp_pasv($this->ftpConn, true); // Enable passive mode
    }

    public function queueFiles()
    {
        $files = ftp_nlist($this->ftpConn, $this->remotePath);
        if ($files === false) {
            throw new Exception("Could not list files in directory");
        }

        foreach ($files as $file) {
            if (preg_match('/\.(mp4|mp3)$/i', $file)) {
                $basename = basename($file);
                $link = "{$this->ftpUrl}{$basename}";
                addVideo($link, Login::getStreamerId(), $basename);
            }
        }
    }

    static function copy($ftpUrl, $savePath)
    {
        _error_log("FTP copy($ftpUrl, $savePath)");

        $savePath = str_replace('..', '', $savePath);

        $savePathEscaped = escapeshellarg($savePath);
        $ftpUrlEscaped = escapeshellarg($ftpUrl);

        $command = "wget -O {$savePathEscaped} {$ftpUrlEscaped} ";

        exec($command);

        return file_exists($savePath) && filesize($savePath) > 20;
    }


    public function close()
    {
        if ($this->ftpConn) {
            ftp_close($this->ftpConn);
        }
    }
}
