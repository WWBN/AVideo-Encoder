    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Encoder</title>
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $streamerURL; ?>videos/favicon.png" />
    <link rel="icon" type="image/png" href="<?php echo $streamerURL; ?>videos/favicon.png" />
    <link rel="shortcut icon" href="<?php echo $streamerURL; ?>videos/favicon.ico" sizes="16x16,24x24,32x32,48x48,144x144" />
    <meta name="msapplication-TileImage" content="<?php echo $streamerURL; ?>videos/favicon.png">

    <script src="<?php echo $global['webSiteRootURL']; ?>view/js/setTimezoneCookie.js" type="text/javascript"></script>
    <script src="<?php echo $global['webSiteRootURL']; ?>node_modules/jquery/dist/jquery.min.js" type="text/javascript"></script>
    <link href="<?php echo $global['webSiteRootURL']; ?>node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <script src="<?php echo $global['webSiteRootURL']; ?>node_modules/bootstrap/dist/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="<?php echo $streamerURL; ?>node_modules/sweetalert/dist/sweetalert.min.js" type="text/javascript"></script>
    <link href="<?php echo $streamerURL; ?>node_modules/@fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css" />
    <link href="<?php echo $streamerURL; ?>node_modules/jquery-toast-plugin/dist/jquery.toast.min.css" rel="stylesheet" type="text/css" />
    <script src="<?php echo $streamerURL; ?>node_modules/jquery-toast-plugin/dist/jquery.toast.min.js" type="text/javascript"></script>

    <script src="<?php echo $streamerURL; ?>view/js/script.js" type="text/javascript"></script>
    <script src="<?php echo $streamerURL; ?>node_modules/js-cookie/dist/js.cookie.js" type="text/javascript"></script>

    <script src="<?php echo $global['webSiteRootURL']; ?>view/js/polyfill.min.js" type="text/javascript"></script>

    <link rel="stylesheet" href="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/css/jquery.fileupload.css" />
    <link rel="stylesheet" href="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/css/jquery.fileupload-ui.css" />
    <!-- CSS adjustments for browsers with JavaScript disabled -->
    <noscript>
        <link rel="stylesheet" href="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/css/jquery.fileupload-noscript.css" />
    </noscript>
    <noscript>
        <link rel="stylesheet" href="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/css/jquery.fileupload-ui-noscript.css" />
    </noscript>
    <link href="<?php echo $global['webSiteRootURL']; ?>view/bootgrid/jquery.bootgrid.min.css" rel="stylesheet" type="text/css" />
    <script src="<?php echo $global['webSiteRootURL']; ?>view/bootgrid/jquery.bootgrid.min.js" type="text/javascript"></script>

    <script src="<?php echo $global['webSiteRootURL']; ?>view/js/main.js?<?php echo filectime($global['systemRootPath'] . 'view/js/main.js'); ?>" type="text/javascript"></script>
    <link href="<?php echo $global['webSiteRootURL']; ?>view/css/style.css?<?php echo filectime($global['systemRootPath'] . 'view/css/style.css'); ?>" rel="stylesheet" type="text/css" />

    <link href="<?php echo $streamerURL; ?>view/css/main.css" rel="stylesheet" type="text/css" crossorigin="anonymous" />
    <link href="<?php echo $streamerURL; ?>view/theme.css.php" rel="stylesheet" type="text/css" />
    <link href="<?php echo $streamerURL; ?>node_modules/animate.css/animate.min.css" rel="stylesheet" type="text/css" />


    <script src="<?php echo $streamerURL; ?>view/js/jquery-ui/jquery-ui.min.js" type="text/javascript"></script>
    <link href="<?php echo $streamerURL; ?>view/js/jquery-ui/jquery-ui.min.css" rel="stylesheet" type="text/css" />
    <script>
        var webSiteRootPath = '<?php echo $global['webSiteRootPath']; ?>';
        var webSiteRootURL = '<?php echo $streamerURL; ?>';
        var PHPSESSID = '<?php echo session_id(); ?>';
    </script>

    <link href="<?php echo $streamerURL; ?>view/css/flagstrap/css/flags.css" rel="stylesheet" type="text/css" media="print" onload="this.media='all'" />
    <link href="<?php echo $streamerURL; ?>view/bootstrap/bootstrapSelectPicker/css/bootstrap-select.min.css" rel="stylesheet" type="text/css" />
    <script src="<?php echo $streamerURL; ?>view/bootstrap/bootstrapSelectPicker/js/bootstrap-select.js" type="text/javascript"></script>

    <script>
        function changeLang() {
            document.getElementById('form_lang').submit();
        }
    </script>

    <style>
        <?php
        if (!empty($_GET['noNavbar'])) {
        ?>body,
        body>div.main-container {
            padding: 0;
        }

        <?php
        }
        ?>.bootstrap-select button.dropdown-toggle span.lanG {
            display: none
        }

        .buttonLogoff {
            padding: 8px 12px !important;
            margin-top: 5px !important;
            border-radius: 4px !important;
        }

        .select_lang {
            padding: 5px 0 0 10px;
        }

        .select_lang .lanG {
            font-size: 11px;
            padding-left: 10px
        }

        .select_lang .dropdown-menu>li>a {
            padding: 3px 10px !important;
        }
    </style>