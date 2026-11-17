<?php

// Loaded by include_config.php before the first connection, so it cannot depend on functions.php.

if (!function_exists('getMySQLSSLConnectionSettings')) {
    /**
     * SSL/TLS settings for the database connection, from $global['mysqlSSL'] in videos/configuration.php.
     * true encrypts without checking the server certificate; an array may set ca, capath, cert, key,
     * cipher and verifyServerCert (default: checked when ca or capath is set). Returns false when unset.
     */
    function getMySQLSSLConnectionSettings($config)
    {
        if (empty($config)) {
            return false;
        }
        if (!is_array($config)) {
            $config = [];
        }
        $settings = ['key' => null, 'cert' => null, 'ca' => null, 'capath' => null, 'cipher' => null];
        foreach (array_keys($settings) as $name) {
            if (!empty($config[$name]) && is_string($config[$name])) {
                $settings[$name] = $config[$name];
            }
        }
        if (isset($config['verifyServerCert'])) {
            $settings['verifyServerCert'] = !empty($config['verifyServerCert']);
        } else {
            $settings['verifyServerCert'] = !empty($settings['ca']) || !empty($settings['capath']);
        }
        $settings['flags'] = MYSQLI_CLIENT_SSL | ($settings['verifyServerCert'] ? MYSQLI_CLIENT_SSL_VERIFY_SERVER_CERT : MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
        return $settings;
    }
}

if (!function_exists('_mysql_new_connection')) {
    /**
     * Opens a database connection like new mysqli(), over SSL/TLS when $global['mysqlSSL'] is set.
     */
    function _mysql_new_connection($host, $user, $pass, $database = '', $port = null)
    {
        global $global;
        $ssl = getMySQLSSLConnectionSettings($global['mysqlSSL'] ?? false);
        if (empty($ssl)) {
            return new mysqli($host, $user, $pass, $database, $port);
        }
        $mysqli = mysqli_init();
        if (!empty($ssl['key']) || !empty($ssl['cert']) || !empty($ssl['ca']) || !empty($ssl['capath']) || !empty($ssl['cipher'])) {
            $mysqli->ssl_set($ssl['key'], $ssl['cert'], $ssl['ca'], $ssl['capath'], $ssl['cipher']);
        }
        $mysqli->real_connect($host, $user, $pass, $database, empty($port) ? null : (int) $port, null, $ssl['flags']);
        return $mysqli;
    }
}
