<?php
$config = dirname(__FILE__) . '/../videos/configuration.php';
if (!file_exists($config)) {
    header("Location: install/index.php");
}
if (!empty($_POST['webSiteRootURL'])) {
    $_GET['webSiteRootURL'] = $_POST['webSiteRootURL'];
}
if (!empty($_POST['user'])) {
    $_GET['user'] = $_POST['user'];
}
if (!empty($_POST['pass'])) {
    $_GET['pass'] = $_POST['pass'];
}
//header('Access-Control-Allow-Origin: *');
require_once $config;
require_once '../objects/Encoder.php';
require_once '../objects/Configuration.php';
require_once '../objects/Format.php';
require_once '../objects/Streamer.php';
require_once '../objects/Login.php';
require_once '../locale/function.php';

if (!empty($_GET['webSiteRootURL']) && !empty($_GET['user']) && !empty($_GET['pass']) && empty($_GET['justLogin'])) {
    Login::logoff();
}
$rows = Encoder::getAllQueue();
$config = new Configuration();
$streamerURL = Streamer::getStreamerURL();

$config = new Configuration();

$updateFiles = getUpdatesFiles();

$ad = $config->getAutodelete();

if (empty($_COOKIE['format']) && !empty($_SESSION['format'])) {
    $_COOKIE['format'] = $_SESSION['format'];
}
?>
<!DOCTYPE html>
<html lang="<?php echo strtolower(@$_SESSION['lang']); ?>">

<head>
    <?php
    include __DIR__ . '/index.header.php';
    ?>
</head>

<body>
    <?php
    if (empty($_GET['noNavbar'])) {
    ?>
        <nav class="navbar navbar-default navbar-fixed-top">
            <div class="container">
                <div class="navbar-header">
                    <a class="navbar-brand" href="<?php echo $streamerURL; ?>">
                        <?php
                        if (!empty($_SESSION['login']->siteLogo)) {
                        ?>
                            <img src="<?php echo $_SESSION['login']->siteLogo; ?>?<?php echo uniqid(); ?>" class="img img-responsive" />
                        <?php
                        }
                        ?>
                    </a>
                </div>
                <div id="navbar" class="navbar-collapse collapse">
                    <ul class="nav navbar-nav navbar-right">
                        <li>
                            <div class="navbar-lang-btn">
                                <div class="select_lang">
                                    <form method="post" action="" id="form_lang">
                                        <select class="selectpicker" data-width="fit" name="lang" onchange='changeLang();'>
                                            <?php
                                            $dir_lang = '../locale';
                                            if (file_exists($dir_lang) && is_dir($dir_lang)) {
                                                $scan_arr = scandir($dir_lang);
                                                $files_arr = array_diff($scan_arr, array('.', '..', 'function.php', 'locale.json.php', 'index.php'));
                                                foreach ($files_arr as $file_lang) {
                                                    $t_lang = basename($file_lang, '.php');
                                                    display_lang(json_decode($langs_codes, true), $t_lang);
                                                }
                                            }
                                            ?>
                                        </select>
                                    </form>
                                </div>
                            </div>
                        </li>
                        <?php
                        if (Login::isLogged()) {
                        ?>
                            <!--
                                    <li><a href="<?php echo $streamerURL; ?>"><span class="glyphicon glyphicon-film"></span> Stream Site</a></li>
                                -->
                            <li><a href="logoff" class="buttonLogoff btn btn-default"><span class="glyphicon glyphicon-log-out"></span> <?php echo __('Logoff'); ?></a></li>

                        <?php
                        }
                        ?>
                    </ul>
                </div><!--/.nav-collapse -->
            </div>
        </nav>
        <script>
            $(document).ready(function() {
                $('.selectpicker').selectpicker();
            });
        </script>
    <?php
    }
    ?>
    <div class="container-fluid main-container">
        <?php
        if (!Login::canUpload()) {
        ?>
            <div class="row">
                <div class="col-xs-1 col-md-2"></div>
                <div class="col-xs-10 col-md-8 ">
                    <form class="form-compact well form-horizontal" id="loginForm">
                        <fieldset>
                            <legend><?php echo __('Please sign in'); ?></legend>
                            <div class="form-group">
                                <label class="col-md-4 control-label"><?php echo __('Streamer Site'); ?></label>
                                <div class="col-md-8 inputGroupContainer">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="glyphicon glyphicon-globe"></i></span>
                                        <input id="siteURL" placeholder="http://www.your-tube-site.com" class="form-control" type="url" value="<?php echo $streamerURL; ?>" required />
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-4 control-label"><?php echo __('User'); ?></label>
                                <div class="col-md-8 inputGroupContainer">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                                        <input id="inputUser" placeholder="<?php echo __('User'); ?>" class="form-control" type="text" value="<?php echo @$_REQUEST['user']; ?>" required />
                                    </div>
                                </div>
                            </div>


                            <div class="form-group">
                                <label class="col-md-4 control-label"><?php echo __('Password'); ?></label>
                                <div class="col-md-8 inputGroupContainer">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                                        <input id="inputPassword" placeholder="<?php echo __('Password'); ?>" class="form-control" type="password" value="<?php echo @$_REQUEST['pass']; ?>" />
                                    </div>
                                </div>
                            </div>
                            <!-- Button -->
                            <div class="form-group">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-success  btn-block" id="mainButton"><span class="fas fa-sign-in"></span> <?php echo __('Sign in'); ?></button>
                                </div>
                            </div>
                        </fieldset>

                    </form>
                </div>
                <div class="col-xs-1 col-md-2"></div>
            </div>
            <script>
                var encodedPass = <?php
                                    // if pass all parameters submit the form
                                    echo (!empty($streamerURL) && !empty($_REQUEST['user']) && !empty($_REQUEST['pass'])) ? 'true' : 'false';
                                    ?>;
                $(document).ready(function() {
                    $('#loginForm').submit(function(evt) {
                        evt.preventDefault();
                        modal.showPleaseWait();
                        $.ajax({
                            url: 'login',
                            data: {
                                "user": $('#inputUser').val(),
                                "pass": $('#inputPassword').val(),
                                "siteURL": $('#siteURL').val(),
                                "encodedPass": encodedPass
                            },
                            xhrFields: {
                                //withCredentials: true
                            },
                            type: 'post',
                            success: function(response) {
                                if (response.error) {
                                    modal.hidePleaseWait();
                                    swal("<?php echo __('Sorry!'); ?>", response.error, "error");
                                } else
                                if (!response.streamer) {
                                    modal.hidePleaseWait();
                                    swal("<?php echo __('Sorry!'); ?>", "<?php echo __('We could not find your streamer site!'); ?>", "error");
                                } else if (!response.isLogged) {
                                    modal.hidePleaseWait();
                                    swal("<?php echo __('Sorry!'); ?>", "<?php echo __('Your user or password is wrong!'); ?>", "error");
                                } else {
                                    var url = new URL(document.location);
                                    url.searchParams.append('justLogin', 1);
                                    url.searchParams.append('playlists_id', <?php echo intval(@$_REQUEST['playlists_id']); ?>);
                                    if (typeof response.PHPSESSID !== 'undefined' && response.PHPSESSID) {
                                        PHPSESSID = response.PHPSESSID;
                                        url.searchParams.append('PHPSESSID', response.PHPSESSID);
                                    }
                                    document.location = url;
                                }
                            }
                        });
                        return false;
                    });

                    $('#inputPassword').keyup(function() {
                        encodedPass = false;
                    });

                    <?php
                    // if pass all parameters submit the form
                    if (!empty($streamerURL) && !empty($_GET['user']) && !empty($_GET['pass']) && empty($_GET['justLogin'])) {
                        echo '$(\'#loginForm\').submit()';
                    }
                    ?>

                });
            </script>
        <?php
        } else {
            $advancedCustom = getAdvancedCustomizedObjectData();
            if (empty($advancedCustom)) {
                error_log("ERROR on get {$aURL} " . $json_file);
                $advancedCustom = new stdClass();
            }
            $result = json_decode($_SESSION['login']->result);
            if (empty($result->videoHLS)) {
                $advancedCustom->doNotShowEncoderHLS = true;
                $advancedCustom->doNotShowEncoderAutomaticHLS = true;
            } else if (!isset($advancedCustom->doNotShowEncoderHLS)) {
                $advancedCustom->doNotShowEncoderHLS = false;
                $advancedCustom->doNotShowEncoderAutomaticHLS = false;
            }
            fixAdvancedCustom($advancedCustom);
        ?>

            <div class="col-md-4" id="rightContainer">
                <div class="panel panel-default">
                    <div class="panel-heading tabbable-line">
                        <ul class="nav nav-tabs">
                            <li class="nav-item active  <?php echo getCSSAnimationClassAndStyle('animate__bounceInDown', 'tabsRight', 0.1); ?>">
                                <a data-toggle="tab" href="#basicOptions" class="nav-link"><i class="fas fa-cog"></i> <?php echo __('Basic'); ?></a>
                            </li>
                            <?php
                            if (empty($global['hideAdvanced'])) {
                            ?>
                                <li class="nav-item <?php echo getCSSAnimationClassAndStyle('animate__bounceInDown', 'tabsRight', 0.1); ?>">
                                    <a data-toggle="tab" href="#advancedOptions" class="nav-link"><i class="fas fa-cogs"></i> <?php echo __('Advanced'); ?></a>
                                </li>
                            <?php
                            }
                            ?>
                        </ul>
                    </div>
                    <div class="panel-body">
                        <div class="tab-content">
                            <div id="basicOptions" class="tab-pane fade in active">
                                <?php
                                include './index_shareVideos.php';
                                ?>
                            </div>
                            <div id="advancedOptions" class="tab-pane fade">
                                <?php
                                //include $global['systemRootPath'].'view/streamerResources/videoPoster.php';
                                include $global['systemRootPath'] . 'view/streamerResources/userGroups.php';
                                include $global['systemRootPath'] . 'view/streamerResources/status.php';
                                //include $global['systemRootPath'].'view/streamerResources/tags.php';
                                //include $global['systemRootPath'].'view/streamerResources/videoOwner.php';
                                include $global['systemRootPath'] . 'view/streamerResources/videosId.php';
                                include $global['systemRootPath'] . 'view/streamerResources/resolutions.php';
                                include $global['systemRootPath'] . 'view/streamerResources/info.php';
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- The main CSS file -->
            <div class="col-md-8">
                <div id="noNavbarPlaceholder"></div>
                <div class="panel panel-default">
                    <div class="panel-heading tabbable-line">
                        <ul class="nav nav-tabs" id="mainTabs">
                            <li <?php
                                if (empty($_POST['updateFile'])) {
                                ?> class="nav-item active <?php echo getCSSAnimationClassAndStyle('animate__bounceInDown', 'tabsRight', 0.1); ?>" <?php
                                                                                                                                                } else {
                                                                                                                                                    ?> class="nav-item <?php echo getCSSAnimationClassAndStyle('animate__bounceInDown', 'tabsRight', 0.1); ?>" <?php
                                                                                                                                                                                                                                                            } ?>>
                                <a data-toggle="tab" href="#encoding" class="nav-link"><span class="glyphicon glyphicon-tasks"></span> <?php echo __('Sharing Queue'); ?></a>
                            </li>
                            <li class="nav-item <?php echo getCSSAnimationClassAndStyle('animate__bounceInDown', 'tabsRight', 0.1); ?>">
                                <a data-toggle="tab" href="#log" class="nav-link"><span class="glyphicon glyphicon-cog"></span> <?php echo __('Queue Log'); ?></a>
                            </li>

                            <li class="nav-item <?php echo getCSSAnimationClassAndStyle('animate__bounceInDown', 'tabsRight', 0.1); ?>">
                                <a data-toggle="tab" href="#signin" class="nav-link"><i class="fa-solid fa-right-to-bracket"></i> <?php echo __('Sign In'); ?></a>
                            </li>

                            <?php
                            if (Login::isAdmin()) {
                                if (empty($global['disableConfigurations'])) {
                            ?>
                                    <li class="nav-item <?php echo getCSSAnimationClassAndStyle('animate__bounceInDown', 'tabsRight', 0.1); ?>">
                                        <a data-toggle="tab" href="#config" class="nav-link"><span class="glyphicon glyphicon-cog"></span> <?php echo __('Configurations'); ?></a>
                                    </li>
                                    <li <?php
                                        if (!empty($_POST['updateFile'])) {
                                        ?> class="nav-item active <?php echo getCSSAnimationClassAndStyle('animate__bounceInDown', 'tabsRight', 0.1); ?>" <?php
                                                                                                                                                        } else {
                                                                                                                                                            ?> class="nav-item <?php echo getCSSAnimationClassAndStyle('animate__bounceInDown', 'tabsRight', 0.1); ?>" <?php
                                                                                                                                                                                                                                                                    } ?>>
                                        <a data-toggle="tab" href="#update" class="nav-link"><span class="fas fa-wrench"></span> <?php echo __('Update'); ?> <?php if (!empty($updateFiles)) { ?>
                                                <label class="label label-danger"><?php echo count($updateFiles); ?></label><?php } ?>
                                        </a>
                                    </li>
                                <?php
                                }
                                ?>
                                <li class="nav-item <?php echo getCSSAnimationClassAndStyle('animate__bounceInDown', 'tabsRight', 0.1); ?>">
                                    <a data-toggle="tab" href="#streamers" class="nav-link"><span class="glyphicon glyphicon-user"></span> <?php echo __('Streamers'); ?></a>
                                </li>
                            <?php
                            }
                            ?>
                        </ul>
                    </div>
                    <div class="panel-body">
                        <div class="tab-content">
                            <div id="encoding" class="tab-pane fade in active">
                            </div>
                            <div id="log" class="tab-pane fade">
                                <table id="grid" class="table table-condensed table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th data-column-id="title" data-formatter="title"><?php echo __('Title'); ?></th>
                                            <th data-column-id="status" data-formatter="status"><?php echo __('Status'); ?></th>
                                            <th data-column-id="created" data-formatter="dates" data-order="desc"><?php echo __('Dates'); ?></th>
                                            <th data-column-id="commands" data-formatter="commands" data-sortable="false" data-width="200px"></th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                            <div id="signin" class="tab-pane fade">
                                <?php
                                //include './index_signin.php';
                                include './index_signin_deprecated.php';
                                ?>
                            </div>
                            <?php
                            include './index_configurations.php';
                            ?>
                            <?php
                            if (Login::isAdmin()) {
                            ?>
                                <div id="streamers" class="tab-pane fade">
                                    <table id="gridStreamer" class="table table-condensed table-hover table-striped">
                                        <thead>
                                            <tr>
                                                <th data-column-id="siteURL" data-width="40%"><?php echo __('URL'); ?></th>
                                                <th data-column-id="user" data-width="30%"><?php echo __('User'); ?></th>
                                                <th data-column-id="priority" data-formatter="priority" data-width="15%"><?php echo __('Priority'); ?></th>
                                                <th data-column-id="isAdmin" data-formatter="admin" data-width="15%"><?php echo __('Admin'); ?></th>
                                                <th data-column-id="commands" data-formatter="commands" data-sortable="false" data-width="100px"></th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                var encodingNowIds = new Array();

                function checkFiles() {
                    var path = $('#path').val();
                    if (!path) {
                        return false;
                    }
                    $.ajax({
                        url: 'listFiles.json?<?php echo getPHPSessionIDURL(); ?>',
                        data: {
                            "path": path
                        },
                        xhrFields: {
                            //withCredentials: true
                        },
                        type: 'post',
                        success: function(response) {
                            $('#files').empty();
                            if (response) {
                                for (i = 0; i < response.length; i++) {
                                    if (!response[i])
                                        continue;

                                    $('<li class="list-group-item"><span class="label label-success" style="display: none;"><span class="glyphicon glyphicon-ok"></span> <?php echo __('Added on queue'); ?>.. </span><div class="material-switch pull-right"><input id="someSwitchOption' + response[i].id + '" class="someSwitchOption" type="checkbox"/><label for="someSwitchOption' + response[i].id + '" class="label-primary"></label></div></li>')
                                        .appendTo('#files')
                                        .attr('id', 'li' + i)
                                        .attr('path', response[i].path)
                                        .append(response[i].name);
                                }
                            }
                        }
                    });
                }

                function isAChannel() {
                    return /^(http(s)?:\/\/)?((w){3}.)?youtu(be|.be)?(\.com)?\/(channel|user).+/gm.test($('#inputVideoURL').val());
                }

                function setDownloadProgress(id, progress, setText) {
                    var selector = "#downloadProgress" + id;
                    progress = parseInt(progress);

                    $(selector).slideDown();
                    if (progress < 0) {
                        progress = 0;
                    } else if (progress > 100) {
                        progress = 100;
                    }
                    var text = "<strong><?php echo __('Downloading'); ?></strong> " + progress + '%';
                    if (progress < 100) {
                        $(selector).addClass('active');
                        $(selector).find('.progress-bar').removeClass('progress-bar-success');
                        $(selector).find('.progress-bar').addClass('progress-bar-danger');
                    } else {
                        text = "<strong><?php echo __('Downloaded'); ?></strong>";
                        $(selector).removeClass('active');
                        $(selector).find('.progress-bar').removeClass('progress-bar-danger');
                        $(selector).find('.progress-bar').addClass('progress-bar-success');
                    }
                    if (setText) {
                        $("#encodingProgress" + id).find('.progress-completed').html(text);
                    }

                    //console.log('progress-bar', progress);
                    $(selector).find('.progress-bar').css({
                        'width': progress + '%'
                    });
                }

                function setEncodingProgress(id, progress, text) {
                    var selector = "#encodingProgress" + id;
                    if (!isNaN(progress)) {
                        progress = parseInt(progress);
                        $(selector).slideDown();
                        if (progress < 0) {
                            progress = 0;
                        } else if (progress > 100) {
                            progress = 100;
                        }
                        $(selector).find('.progress-completed').html("<strong>" + text + "</strong> <span class=\"badge\">" + progress + '%</span>');
                        $(selector).find('.progress-bar').css({
                            'width': progress + '%'
                        });
                        if (progress < 100) {
                            $(selector).addClass('active');
                            $(selector).find('.progress-bar').removeClass('progress-bar-success');
                            $(selector).find('.progress-bar').addClass('progress-bar-primary');

                        } else {
                            $(selector).removeClass('active');
                            $(selector).find('.progress-bar').removeClass('progress-bar-primary');
                            $(selector).find('.progress-bar').addClass('progress-bar-success');

                        }
                        setDownloadProgress(id, 100, false);
                    }
                }

                var checkProgressTimeout = 3000; //4 secongs
                function checkProgress() {
                    $.ajax({
                        url: 'status?<?php echo getPHPSessionIDURL(); ?>',
                        xhrFields: {
                            //withCredentials: true
                        },
                        success: function(response) {
                            if (response.queue_list.length) {
                                for (i = 0; i < response.queue_list.length; i++) {
                                    createQueueItem(response.queue_list[i], response.queue_list[i - 1]);
                                }

                            }
                            if (response.downloaded.length > 0) {
                                for (i = 0; i < response.downloaded.length; i++) {
                                    var id = response.downloaded[i].id;
                                    setDownloadProgress(id, 100, true);
                                }
                            }
                            if (response.encoding.length > 0) {
                                var newEncodingNowIds = new Array();
                                for (i = 0; i < response.encoding.length; i++) {
                                    var id = response.encoding[i].id;
                                    newEncodingNowIds.push(id);
                                }

                                for (i = 0; i < encodingNowIds.length; i++) {
                                    var id = encodingNowIds[i];
                                    // if start encode next before get 100%
                                    if (newEncodingNowIds.indexOf(id) == -1) {
                                        removeQueueItem(id);
                                    }
                                }
                                encodingNowIds = newEncodingNowIds;

                                for (i = 0; i < encodingNowIds.length; i++) {
                                    var id = encodingNowIds[i];
                                    var setText = true;
                                    var text = '';
                                    try {
                                        if (response.encoding_status[i].progress) {
                                            setText = false;
                                            setEncodingProgress(id, response.encoding_status[i].progress, text);
                                        }
                                        text = response.encoding[i].name + " [<?php echo __('Downloading'); ?> ...]";
                                    } catch (error) {

                                    }
                                    try {
                                        if (response.download_status[i] && response.encoding_status[i].progress) {
                                            text = response.encoding[i].name + " [" + response.encoding_status[i].from + " to " + response.encoding_status[i].to + "] " + response.encoding_status[i].remainTimeHuman;
                                        }

                                        if (response.download_status[i]) {
                                            setDownloadProgress(id, response.download_status[i].progress, setText);
                                        }
                                    } catch (error) {}

                                }

                                setTimeout(function() {
                                    checkProgress();
                                }, checkProgressTimeout);
                            } else {
                                while ((id = encodingNowIds.pop()) != null) {
                                    removeQueueItem(id);
                                }
                                setTimeout(function() {
                                    checkProgress();
                                }, checkProgressTimeout * 2);
                            }
                            if (response.transferring.length > 0) {
                                for (i = 0; i < response.transferring.length; i++) {
                                    var id = response.transferring[i].id;
                                    removeQueueItem(id);
                                }
                            }

                        }
                    });
                }

                var checkProgressRemoveTimeout = [];
                var createQueueTemplate = <?php echo json_encode(file_get_contents($global['systemRootPath'] . 'view/encodeProgressTemplate.html')); ?>;

                function createQueueItem(queueItem, queueItemAfter) {
                    clearTimeout(checkProgressRemoveTimeout[queueItem.id]);
                    if ($('#encodeProgress' + queueItem.id).length) {
                        return false;
                    }
                    console.log(queueItemAfter);

                    var itemsArray = {};
                    itemsArray.id = queueItem.id;
                    itemsArray.site = queueItem.streamer_site;
                    itemsArray.priority = queueItem.streamer_priority;
                    itemsArray.title = queueItem.title;
                    itemsArray.name = queueItem.name;

                    var item = arrayToTemplate(itemsArray, createQueueTemplate);

                    if (typeof queueItemAfter === 'undefined' || !$("#" + queueItemAfter.id).length) {
                        $("#encoding").append(item);
                    } else {
                        $(item).insertAfter("#" + queueItemAfter.id);
                    }
                }

                function removeQueueItem(id) {
                    checkProgressRemoveTimeout[id] = setTimeout(function() {
                        $("#encodeProgress" + id).fadeOut("slow", function() {
                            $(this).remove();
                        });
                        $("#downloadProgress" + id).slideUp("fast", function() {
                            $(this).remove();
                        });
                    }, 3000);
                }

                var streamerMaxFileSize = 0;

                $(document).ready(function() {
                    checkProgress();

                    $("input[name='format']").click(function() {
                        Cookies.set('format', $(this).attr('id'), {
                            sameSite: 'None'
                        });
                    });

                    var streamerURL = "<?php echo $streamerURL; ?>";
                    <?php
                    /**
                     * If you are over https change the URL to https
                     */
                    $url = parse_url($global['webSiteRootURL']);
                    if ($url['scheme'] == 'https') {
                    ?>
                        streamerURL = streamerURL.replace(/^http:\/\//i, 'https://');
                    <?php
                    }
                    ?>
                    $.ajax({
                        url: streamerURL + 'status',
                        xhrFields: {
                            //withCredentials: true
                        },
                        success: function(response) {
                            $('#max_file_size').text(response.max_file_size);
                            streamerMaxFileSize = response.file_upload_max_size;
                            $('#currentStorageUsage').text((response.currentStorageUsage / 60).toFixed(2) + " <?php echo __('Minutes'); ?>");
                        }
                    });

                    $("#addQueueBtn").click(function() {
                        $('#files li').each(function() {
                            if ($(this).find('.someSwitchOption').is(":checked")) {
                                var id = $(this).attr('id');
                                $.ajax({
                                    url: 'queue?<?php echo getPHPSessionIDURL(); ?>',
                                    data: {
                                        "fileURI": $(this).attr('path'),
                                        "audioOnly": $('#inputAudioOnly').is(":checked"),
                                        "spectrum": $('#inputAudioSpectrum').is(":checked"),
                                        "webm": $('#inputWebM').is(":checked"),
                                        "inputHLS": $('#inputHLS').is(":checked"),
                                        "inputLow": $('#inputLow').is(":checked"),
                                        "inputSD": $('#inputSD').is(":checked"),
                                        "inputHD": $('#inputHD').is(":checked"),
                                        "inputAutoHLS": $('#inputAutoHLS').is(":checked"),
                                        "inputAutoMP4": $('#inputAutoMP4').is(":checked"),
                                        "inputAutoWebm": $('#inputAutoWebm').is(":checked"),
                                        "inputAutoAudio": $('#inputAutoAudio').is(":checked"),
                                        "categories_id": $('#bulk_categories_id').val(),
                                        "releaseDate": $('#bulk_releaseDate').val(),
                                        "callback": $('#callback').val(),
                                        "usergroups_id": $(".usergroups_id:checked").map(function() {
                                            return $(this).val();
                                        }).get()
                                    },
                                    xhrFields: {
                                        //withCredentials: true
                                    },
                                    type: 'post',
                                    success: function(response) {
                                        $('#' + id).find('.label').fadeIn();
                                    }
                                });
                            }

                        })

                    });

                    $("#pathBtn").click(function() {
                        checkFiles();
                    });

                    $("#checkBtn").click(function() {
                        $('#files').find('input:checkbox').prop('checked', true);
                    });
                    $("#uncheckBtn").click(function() {
                        $('#files').find('input:checkbox').prop('checked', false);
                    });

                    $('#saveConfig').click(function() {
                        modal.showPleaseWait();
                        var formats = new Array();
                        var count = 0;
                        $(".formats").each(function(index) {
                            var id = $(this).attr('id');
                            var parts = id.split("_");
                            formats[count++] = [parts[1], $(this).val()];
                        });

                        var resolutions = $("input[name='resolutions']:checked").map(function() {
                            return $(this).val();
                        }).toArray();

                        $.ajax({
                            url: 'saveConfig?<?php echo getPHPSessionIDURL(); ?>',
                            data: {
                                "formats": formats,
                                "allowedStreamers": $("#allowedStreamers").val(),
                                "defaultPriority": $("#defaultPriority").val(),
                                "autodelete": $("#autodelete").is(":checked"),
                                "resolutions": resolutions
                            },
                            xhrFields: {
                                //withCredentials: true
                            },
                            type: 'post',
                            success: function(response) {
                                console.log(response);
                                modal.hidePleaseWait();
                            }
                        });
                        return false;
                    });


                    $('#downloadForm').submit(function(evt) {
                        evt.preventDefault();
                        var videos_id = $('#update_video_id').val();
                        if (videos_id) {
                            swal({
                                    title: "<?php echo __('You will overwrite the video ID:'); ?> " + videos_id,
                                    text: "<?php echo __('The video will be replaced with this new file, are you sure you want to proceed?'); ?>",
                                    icon: "warning",
                                    buttons: true,
                                    dangerMode: true,
                                })
                                .then(function(confirm) {
                                    if (confirm) {
                                        submitDownloadForm();
                                    }
                                });
                        } else {
                            submitDownloadForm();
                        }
                        return false;
                    });

                    $('#inputAudioOnly').change(function() {
                        if ($(this).is(":checked")) {
                            $('#webm').fadeOut("slow", function() {
                                $('#spectrum').fadeIn();
                            });
                        } else {
                            $('#spectrum').fadeOut("slow", function() {
                                $('#webm').fadeIn();
                            });
                        }
                    });

                    var grid = $("#grid").bootgrid({
                        ajax: true,
                        url: "queue.json?<?php echo getPHPSessionIDURL(); ?>",
                        xhrFields: {
                            //withCredentials: true
                        },
                        formatters: {
                            "commands": function(column, row) {
                                var reQueue = '';
                                var deleteQueue = '';
                                var sendFileQueue = '';
                                var edit = '';
                                var return_vars = JSON.parse(row.return_vars);

                                if (row.status != 'queue' && row.status != 'encoding') {
                                    reQueue = '<button type="button" class="btn btn-default command-reQueue" data-toggle="tooltip" title="<?php echo __('Re-Queue'); ?>"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span></button>'
                                }
                                deleteQueue = '<button type="button" class="btn btn-danger command-deleteQueue" data-toggle="tooltip" title="<?php echo __('Delete Queue'); ?>"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button>'
                                if (row.status === 'done' || row.status === 'transferring') {
                                    sendFileQueue = '<button type="button" class="btn btn-default command-sendFileQueue" data-toggle="tooltip" title="<?php echo __('Send Notify'); ?>"><span class="glyphicon glyphicon-send" aria-hidden="true"></span></button>'
                                }
                                if (return_vars.videos_id) {
                                    edit = '<button type="button" class="btn btn-default command-editFile" data-toggle="tooltip" title="<?php echo __('Edit'); ?>"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></button>'
                                }

                                return '<div class="btn-group">' + edit + sendFileQueue + reQueue + deleteQueue + '</div>';
                            },
                            "dates": function(column, row) {
                                return "Created: " + row.created + "<br>Modified: " + row.modified;
                            },
                            "status": function(column, row) {
                                let content = '';

                                // Define label class for status
                                let labelClass = 'label-warning';
                                if (row.status === "error") {
                                    labelClass = 'label-danger';
                                } else if (row.status === "done") {
                                    labelClass = 'label-success';
                                } else if (row.status === "queue") {
                                    labelClass = 'label-primary';
                                } else if (row.status === "encoding") {
                                    labelClass = 'label-info';
                                }

                                // Main status
                                content += `<span class="label ${labelClass} text-uppercase"><i class="fa fa-cogs"></i> ${row.status}</span><br>`;

                                // ETA
                                if (row.encoding_status && row.encoding_status.remainTimeHuman) {
                                    content += `<small><i class="fa fa-clock-o"></i> ETA: ${row.encoding_status.remainTimeHuman}</small><br>`;
                                }

                                // Status observation or error message
                                if (row.status_obs) {
                                    content += `<small style="white-space: normal;"><i class="fa fa-info-circle"></i> ${row.status_obs}</small>`;
                                }

                                return content;
                            },

                            "title": function(column, row) {
                                var l = getLocation(row.streamer);
                                videos_id = 0;
                                var json = JSON.parse(row.return_vars)
                                if (typeof json.videos_id !== 'undefined') {
                                    videos_id = json.videos_id;
                                }
                                var title = '<a href="' + row.streamer + 'video/' + videos_id + '" target="_blank" class="btn btn-primary">' + l.hostname + ' <span class="badge"><?php echo __('Priority'); ?> ' + row.priority + '</span></a>';
                                title += '<br><span class="label label-primary">' + row.format + ' [' + row.id + ']</span>';

                                for (const index in row.fileInfo) {
                                    if (typeof row.fileInfo[index].text === 'undefined') {
                                        continue;
                                    }
                                    title += '<br><span class="label label-success fileSize" >' + row.fileInfo[index].text + '</span>';
                                }
                                var filename = row.title;
                                if (filename.startsWith("original_v_")) {
                                    filename = '<a href="' + row.streamer + 'videos/' + filename + '" target="_blank">' + filename + '</a>';
                                }
                                title += '<br>' + filename;


                                return title;
                            }
                        }
                    }).on("loaded.rs.jquery.bootgrid", function() {
                        /* Executes after data is loaded and rendered */
                        grid.find(".command-reQueue").on("click", function(e) {
                            modal.showPleaseWait();
                            var row_index = $(this).closest('tr').index();
                            var row = $("#grid").bootgrid("getCurrentRows")[row_index];
                            console.log(row);
                            $.ajax({
                                url: 'queue?<?php echo getPHPSessionIDURL(); ?>',
                                data: {
                                    "id": row.id,
                                    "fileURI": row.fileURI
                                },
                                xhrFields: {
                                    //withCredentials: true
                                },
                                type: 'post',
                                success: function(response) {
                                    $("#grid").bootgrid("reload");
                                    modal.hidePleaseWait();
                                }
                            });
                        });

                        grid.find(".command-deleteQueue").on("click", function(e) {
                            modal.showPleaseWait();
                            var row_index = $(this).closest('tr').index();
                            var row = $("#grid").bootgrid("getCurrentRows")[row_index];
                            console.log(row);
                            $.ajax({
                                url: 'deleteQueue?<?php echo getPHPSessionIDURL(); ?>',
                                data: {
                                    "id": row.id
                                },
                                xhrFields: {
                                    //withCredentials: true
                                },
                                type: 'post',
                                success: function(response) {
                                    $("#grid").bootgrid("reload");
                                    modal.hidePleaseWait();
                                    if (response.error) {
                                        avideoAlertError(response.msg);
                                    }
                                }
                            });
                        });
                        grid.find(".command-sendFileQueue").on("click", function(e) {
                            modal.showPleaseWait();
                            var row_index = $(this).closest('tr').index();
                            var row = $("#grid").bootgrid("getCurrentRows")[row_index];
                            console.log(row);
                            $.ajax({
                                url: 'send.json?<?php echo getPHPSessionIDURL(); ?>',
                                data: {
                                    "id": row.id
                                },
                                xhrFields: {
                                    //withCredentials: true
                                },
                                type: 'post',
                                success: function(response) {
                                    $("#grid").bootgrid("reload");
                                    modal.hidePleaseWait();
                                }
                            });
                        });
                        grid.find(".command-editFile").on("click", function(e) {
                            var row_index = $(this).closest('tr').index();
                            var row = $("#grid").bootgrid("getCurrentRows")[row_index];
                            var return_vars = JSON.parse(row.return_vars);
                            avideoModalIframe('<?php echo $streamerURL; ?>view/managerVideosLight.php?avideoIframe=1&videos_id=' + return_vars.videos_id);
                        });
                        $('[data-toggle="popover"]').popover();
                    });



                    var gridStreamer = $("#gridStreamer").bootgrid({
                        ajax: true,
                        url: "streamers.json?<?php echo getPHPSessionIDURL(); ?>",
                        xhrFields: {
                            //withCredentials: true
                        },
                        formatters: {
                            "priority": function(column, row) {
                                var tag = "<select class='priority form-control' rowId='" + row.id + "'>";
                                for (i = 1; i <= 10; i++) {
                                    var selected = "";
                                    if (row.priority == i) {
                                        selected = "selected";
                                    }
                                    tag += "<option value='" + i + "' " + selected + ">" + i + "</option>";
                                }
                                tag += "</select>";
                                return tag;
                            },
                            "admin": function(column, row) {
                                var tag = "<select class='isAdmin form-control' rowId='" + row.id + "'>";
                                tag += "<option value='1' " + (row.isAdmin == "1" ? "selected" : "") + "><?php echo __('Yes'); ?></option>";
                                tag += "<option value='0' " + (row.isAdmin == "1" ? "" : "selected") + "><?php echo __('No'); ?></option>";
                                tag += "</select>";
                                return tag;
                            },
                            "commands": function(column, row) {
                                var deleteBtn = '<button type="button" class="btn btn-danger command-delete" data-toggle="tooltip" title="<?php echo __('Delete Queue'); ?>"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button>';

                                return deleteBtn;
                            }
                        }
                    }).on("loaded.rs.jquery.bootgrid", function() {
                        gridStreamer.find(".command-delete").on("click", function(e) {
                            modal.showPleaseWait();
                            var row_index = $(this).closest('tr').index();
                            var row = $("#gridStreamer").bootgrid("getCurrentRows")[row_index];
                            console.log(row);
                            $.ajax({
                                url: 'removeStreamer?<?php echo getPHPSessionIDURL(); ?>',
                                data: {
                                    "id": row.id
                                },
                                xhrFields: {
                                    //withCredentials: true
                                },
                                type: 'post',
                                success: function(response) {
                                    $("#gridStreamer").bootgrid("reload");
                                    modal.hidePleaseWait();
                                }
                            });
                        });

                        gridStreamer.find(".priority").on("change", function(e) {
                            modal.showPleaseWait();
                            $.ajax({
                                url: 'priority?<?php echo getPHPSessionIDURL(); ?>',
                                data: {
                                    "id": $(this).attr('rowId'),
                                    "priority": $(this).val()
                                },
                                xhrFields: {
                                    //withCredentials: true
                                },
                                type: 'post',
                                success: function(response) {
                                    modal.hidePleaseWait();
                                }
                            });
                        });

                        gridStreamer.find(".isAdmin").on("change", function(e) {
                            modal.showPleaseWait();
                            $.ajax({
                                url: 'isAdmin?<?php echo getPHPSessionIDURL(); ?>',
                                data: {
                                    "id": $(this).attr('rowId'),
                                    "isAdmin": $(this).val()
                                },
                                xhrFields: {
                                    //withCredentials: true
                                },
                                type: 'post',
                                success: function(response) {
                                    modal.hidePleaseWait();
                                }
                            });
                        });
                    });
                    $('[data-toggle="tooltip"]').tooltip();
                });

                function submitDownloadForm() {

                    if (isAChannel()) {
                        <?php
                        if (Login::canBulkEncode()) {
                        ?>
                            var span = document.createElement("span");
                            span.innerHTML = "<?php echo __('This is a Channel, are you sure you want to download all videos on this channel?'); ?><br><?php echo __('It may take a while to complete'); ?><br>Start Index: <input type='number'  id='startIndex' value='0' style='width:100px;'><br>End Index: <input type='number'  id='endIndex' value='100' style='width:100px;'>";

                            swal({
                                    title: "<?php echo __('Are you sure?'); ?>",
                                    content: span,
                                    icon: "warning",
                                    buttons: true,
                                    dangerMode: true,
                                })
                                .then(function(confirm) {
                                    if (confirm) {
                                        modal.showPleaseWait();
                                        $.ajax({
                                            url: 'youtubeDl.json?<?php echo getPHPSessionIDURL(); ?>',
                                            data: {
                                                "audioOnly": $('#inputAudioOnly').is(":checked"),
                                                "spectrum": $('#inputAudioSpectrum').is(":checked"),
                                                "webm": $('#inputWebM').is(":checked"),
                                                "override_status": $('#override_status').val(),
                                                "videoURL": $('#inputVideoURL').val(),
                                                "update_video_id": $('#update_video_id').val(),
                                                "inputHLS": $('#inputHLS').is(":checked"),
                                                "inputLow": $('#inputLow').is(":checked"),
                                                "inputSD": $('#inputSD').is(":checked"),
                                                "inputHD": $('#inputHD').is(":checked"),
                                                "inputAutoHLS": $('#inputAutoHLS').is(":checked"),
                                                "inputAutoMP4": $('#inputAutoMP4').is(":checked"),
                                                "inputAutoWebm": $('#inputAutoWebm').is(":checked"),
                                                "inputAutoAudio": $('#inputAutoAudio').is(":checked"),
                                                "categories_id": $('#download_categories_id').val(),
                                                "releaseDate": $('#download_releaseDate').val(),
                                                "callback": $('#callback').val(),
                                                "usergroups_id": $(".usergroups_id:checked").map(function() {
                                                    return $(this).val();
                                                }).get(),
                                                "startIndex": $('#startIndex').val(),
                                                "endIndex": $('#endIndex').val()
                                            },
                                            xhrFields: {
                                                //withCredentials: true
                                            },
                                            type: 'post',
                                            success: function(response) {
                                                if (response.text) {
                                                    avideoAlertSuccess("<?php echo __('All your videos were imported'); ?>");
                                                }
                                                console.log(response);
                                            }
                                        });
                                        setTimeout(function() {
                                            avideoAlertInfo("<?php echo __('All your videos channel will be process, this may take a while to be complete'); ?>");
                                        }, 500);
                                        modal.hidePleaseWait();
                                    }
                                });
                        <?php
                        } else {
                        ?>
                            avideoAlertError("<?php echo __('Channel Import is disabled'); ?>");
                        <?php
                        }
                        ?>
                    } else {
                        modal.showPleaseWait();
                        $.ajax({
                            url: 'youtubeDl.json?<?php echo getPHPSessionIDURL(); ?>',
                            data: {
                                "videoURL": $('#inputVideoURL').val(),
                                "audioOnly": $('#inputAudioOnly').is(":checked"),
                                "spectrum": $('#inputAudioSpectrum').is(":checked"),
                                "webm": $('#inputWebM').is(":checked"),
                                "override_status": $('#override_status').val(),
                                "update_video_id": $('#update_video_id').val(),
                                "inputHLS": $('#inputHLS').is(":checked"),
                                "inputLow": $('#inputLow').is(":checked"),
                                "inputSD": $('#inputSD').is(":checked"),
                                "inputHD": $('#inputHD').is(":checked"),
                                "inputAutoHLS": $('#inputAutoHLS').is(":checked"),
                                "inputAutoMP4": $('#inputAutoMP4').is(":checked"),
                                "inputAutoWebm": $('#inputAutoWebm').is(":checked"),
                                "inputAutoAudio": $('#inputAutoAudio').is(":checked"),
                                "categories_id": $('#download_categories_id').val(),
                                "releaseDate": $('#download_releaseDate').val(),
                                "callback": $('#callback').val(),
                                "usergroups_id": $(".usergroups_id:checked").map(function() {
                                    return $(this).val();
                                }).get()
                            },
                            xhrFields: {
                                //withCredentials: true
                            },
                            type: 'post',
                            success: function(response) {
                                if (response.text) {
                                    let message = response.text;
                                    if (Array.isArray(message)) {
                                        message = message.join(', '); // join all array elements into a string
                                    }
                                    avideoAlert(response.title, message, response.type);
                                    avideoAlert(response.title, response.text, response.type);
                                }
                                console.log(response);
                                modal.hidePleaseWait();
                            }
                        });
                    }
                }

                function resetAutocompleteVideosID() {
                    $("#videoSearch").val('');
                    $("#update_video_id").val('');
                    $("#inputNextVideo-poster").attr("src", "view/img/notfound.jpg");
                }
            </script>
        <?php
        }
        ?>

    </div>

</body>

</html>