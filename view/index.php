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

if (!empty($_GET['webSiteRootURL']) && !empty($_GET['user']) && !empty($_GET['pass']) && empty($_GET['justLogin'])) {
    Login::logoff();
}

$rows = Encoder::getAllQueue();
$config = new Configuration();
$streamerURL = @$_GET['webSiteRootURL'];
if (empty($streamerURL)) {
    $streamerURL = Streamer::getFirstURL();
}
$config = new Configuration();

$updateFiles = getUpdatesFiles();

$ad = $config->getAutodelete();


if (empty($_COOKIE['format']) && !empty($_SESSION['format'])) {
    $_COOKIE['format'] = $_SESSION['format'];
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="">
        <meta name="author" content="">

        <title>Encoder</title>
        <link rel="icon" href="view/img/favicon.png">
        <link href="<?php echo Login::getStreamerURL(); ?>view/css/fontawesome-free-5.5.0-web/css/all.min.css"" rel="stylesheet" crossorigin="anonymous">
        <script src="view/js/jquery-3.2.0.min.js" type="text/javascript"></script>
        <link href="view/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
        <script src="view/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
        <link href="view/js/seetalert/sweetalert.css" rel="stylesheet" type="text/css"/>
        <script src="view/js/seetalert/sweetalert.min.js" type="text/javascript"></script>
        <script src="<?php echo Login::getStreamerURL(); ?>view/js/js-cookie/js.cookie.js" type="text/javascript"></script>
        <script src="view/js/main.js?<?php echo filectime($global['systemRootPath'] . "view/js/main.js"); ?>" type="text/javascript"></script>
        <link href="view/css/style.css?<?php echo filectime($global['systemRootPath'] . "view/css/style.css"); ?>" rel="stylesheet" type="text/css"/>
        
        <script src="https://cdn.polyfill.io/v2/polyfill.min.js"></script>
        
        <script>
            var webSiteRootPath = '<?php echo $global['webSiteRootPath']; ?>';
            var PHPSESSID = '<?php echo session_id(); ?>';
        </script>
        <style>
<?php
if (!empty($_GET['noNavbar'])) {
    ?>
                body{
                    margin: 0;
                }
                .main-container {
                    margin-top: 0;
                    padding-top: 5px;
                }
                #mainTabs{
                    position: fixed;
                    top: 0;
                    padding-top: 5px;
                    background-color: #FFF;
                    z-index: 10;
                    width: 100%;
                }
                #noNavbarPlaceholder{
                    height: 55px;
                }
                #rightContainer{
                    z-index: 11;
                }
            
    <?php
}
?>
        </style>
    </head>

    <body>    
        <?php
        if (empty($_GET['noNavbar'])) {
            ?>
            <nav class="navbar navbar-default navbar-fixed-top">
                <div class="container">
                    <div class="navbar-header">
                        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                            <span class="sr-only">Toggle navigation</span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                        </button>
                        <a class="navbar-brand" href="<?php echo Login::getStreamerURL(); ?>" >
                            <?php
                            if (!empty($_SESSION['login']->siteLogo)) {
                                ?>
                                <img src="<?php echo $_SESSION['login']->siteLogo; ?>?<?php echo uniqid(); ?>" class="img-responsive ">    
                                <?php
                            }
                            ?>
                        </a>
                    </div>
                    <div id="navbar" class="navbar-collapse collapse">
                        <ul class="nav navbar-nav navbar-right">
                            <?php
                            if (Login::isLogged()) {
                                ?>
                                <!--
                                    <li><a href="<?php echo Login::getStreamerURL(); ?>"><span class="glyphicon glyphicon-film"></span> Stream Site</a></li>
                                -->
                                <li><a href="logoff"><span class="glyphicon glyphicon-log-out"></span> Logoff</a></li>
                                <?php
                            }
                            ?>
                        </ul>
                    </div><!--/.nav-collapse -->
                </div>
            </nav>

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
                        <form class="form-compact well form-horizontal"  id="loginForm">
                            <fieldset>
                                <legend>Please sign in</legend>
                                <div class="form-group">
                                    <label class="col-md-4 control-label">Streamer Site</label>  
                                    <div class="col-md-8 inputGroupContainer">
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="glyphicon glyphicon-globe"></i></span>
                                            <input  id="siteURL" placeholder="http://www.your-tube-site.com" class="form-control"  type="url" value="<?php echo $streamerURL; ?>" required >
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-4 control-label">User</label>  
                                    <div class="col-md-8 inputGroupContainer">
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                                            <input  id="inputUser" placeholder="User" class="form-control"  type="text" value="<?php echo @$_GET['user']; ?>" required >
                                        </div>
                                    </div>
                                </div>


                                <div class="form-group">
                                    <label class="col-md-4 control-label">Password</label>  
                                    <div class="col-md-8 inputGroupContainer">
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                                            <input  id="inputPassword" placeholder="Password" class="form-control"  type="password" value="<?php echo @$_GET['pass']; ?>" >
                                        </div>
                                    </div>
                                </div>
                                <!-- Button -->
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-success  btn-block" id="mainButton" ><span class="fas fa-sign-in"></span> Sign in</button>
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
            echo (!empty($streamerURL) && !empty($_GET['user']) && !empty($_GET['pass'])) ? 'true' : 'false';
            ?>;
                    $(document).ready(function () {
                        $('#loginForm').submit(function (evt) {
                            evt.preventDefault();
                            modal.showPleaseWait();
                            $.ajax({
                                url: 'login',
                                data: {"user": $('#inputUser').val(), "pass": $('#inputPassword').val(), "siteURL": $('#siteURL').val(), "encodedPass": encodedPass},
                                type: 'post',
                                success: function (response) {
                                    if (response.error) {
                                        modal.hidePleaseWait();
                                        swal("Sorry!", response.error, "error");
                                    } else
                                    if (!response.streamer) {
                                        modal.hidePleaseWait();
                                        swal("Sorry!", "We could not find your streamer site!", "error");
                                    } else if (!response.isLogged) {
                                        modal.hidePleaseWait();
                                        swal("Sorry!", "Your user or password is wrong!", "error");
                                    } else {
                                        var url = new URL(document.location);
                                        url.searchParams.append('justLogin', 1);
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

                        $('#inputPassword').keyup(function () {
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
                $aURL = Login::getStreamerURL() . "plugin/CustomizeAdvanced/advancedCustom.json.php";
                $json_file = url_get_contents($aURL);
                // convert the string to a json object
                $advancedCustom = json_decode($json_file);
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

                <link href="view/bootgrid/jquery.bootgrid.min.css" rel="stylesheet" type="text/css"/>
                <script src="view/bootgrid/jquery.bootgrid.min.js" type="text/javascript"></script>
                <!-- The main CSS file -->
                <div class="col-md-8">
                    <div id="noNavbarPlaceholder"></div>
                    <ul class="nav nav-tabs" id="mainTabs">
                        <li <?php if (empty($_POST['updateFile'])) { ?>class="active"<?php } ?>><a data-toggle="tab" href="#encoding"><span class="glyphicon glyphicon-tasks"></span> Sharing Queue</a></li>
                        <li><a data-toggle="tab" href="#log"><span class="glyphicon glyphicon-cog"></span> Queue Log</a></li>

                        <?php
                        if (Login::isAdmin()) {
                            if (empty($global['disableConfigurations'])) {
                                ?>
                                <li><a data-toggle="tab" href="#config"><span class="glyphicon glyphicon-cog"></span> Configurations</a></li>
                                <li <?php if (!empty($_POST['updateFile'])) { ?>class="active"<?php } ?>><a data-toggle="tab" href="#update" ><span class="fas fa-wrench"></span> Update <?php if (!empty($updateFiles)) { ?><label class="label label-danger"><?php echo count($updateFiles); ?></label><?php } ?></a></li>
                                <?php
                            }
                            ?>
                            <li><a data-toggle="tab" href="#streamers"><span class="glyphicon glyphicon-user"></span> Streamers</a></li>
                            <?php
                        }
                        ?>
                    </ul>

                    <div class="tab-content">
                        <div id="encoding" class="tab-pane fade <?php if (empty($_POST['updateFile'])) { ?> in active<?php } ?>">
                        </div>
                        <div id="log" class="tab-pane fade">
                            <table id="grid" class="table table-condensed table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th data-column-id="title" data-formatter="title">Title</th>
                                        <th data-column-id="status" data-formatter="status">Status</th>
                                        <th data-column-id="created" data-formatter="dates"  data-order="desc">Dates</th>
                                        <th data-column-id="commands" data-formatter="commands" data-sortable="false"  data-width="100px"></th>
                                    </tr>
                                </thead>
                            </table>
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
                                            <th data-column-id="siteURL" data-width="40%">URL</th>
                                            <th data-column-id="user" data-width="30%">User</th>
                                            <th data-column-id="priority" data-formatter="priority" data-width="15%">Priority</th>
                                            <th data-column-id="isAdmin" data-formatter="admin" data-width="15%">Admin</th>
                                            <th data-column-id="commands" data-formatter="commands" data-sortable="false"  data-width="100px"></th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <div class="col-md-4" id="rightContainer" >
                    <?php
                    include './index_shareVideos.php';
                    if (!empty($_SESSION['login']->userGroups)) {
                        ?>
                        <div class="panel panel-default">
                            <div class="panel-heading"><i class="fas fa-users"></i> User Groups</div>
                            <div class="panel-body">
                                <?php
                                foreach ($_SESSION['login']->userGroups as $key => $value) {
                                    ?>
                                    <label>
                                        <input type="checkbox" class="usergroups_id" name="usergroups_id[]" value="<?php echo $value->id; ?>">
                                        <i class="fas fa-lock"></i> <?php echo $value->group_name; ?>
                                    </label><br>
                                    <?php
                                }
                                ?>
                                <div class="alert alert-info" style="margin-bottom: 0px;"><i class="fas fa-info-circle"></i> Unckeck all to make it public</div>

                            </div>
                        </div>
                        <?php
                    }
                    if (empty($advancedCustom->showOnlyEncoderAutomaticResolutions)) {
                        ?>
                        <div class="panel panel-default">
                            <div class="panel-heading"><i class="fas fa-desktop"></i> Resolutions</div>
                            <div class="panel-body">
                                <?php
                                if (empty($advancedCustom->doNotShowEncoderHLS)) {
                                    ?> 
                                    <label style="" id="">
                                        <input type="checkbox" id="inputHLS" checked="checked" onclick="if ($(this).is(':checked')) {
                                        $('.mp4Checkbox').prop('checked', false);
                                    }"> Multi Bitrate HLS
                                    </label><br>
                                    <?php
                                }
                                if (empty($advancedCustom->doNotShowEncoderResolutionLow)) {
                                    ?> 
                                    <label style="" id="">
                                        <input type="checkbox" id="inputLow" <?php if (!empty($advancedCustom->doNotShowEncoderHLS)) echo 'checked="checked"'; ?> class="mp4Checkbox" onclick="if ($(this).is(':checked')) {
                                                                $('#inputHLS').prop('checked', false);
                                                            }"> Low
                                    </label>
                                    <?php
                                }
                                if (empty($advancedCustom->doNotShowEncoderResolutionSD)) {
                                    ?> 
                                    <label id="">
                                        <input type="checkbox" id="inputSD" <?php if (!empty($advancedCustom->doNotShowEncoderHLS)) echo 'checked="checked"'; ?> class="mp4Checkbox" onclick="if ($(this).is(':checked')) {
                                                                $('#inputHLS').prop('checked', false);
                                                            }"> SD
                                    </label>
                                    <?php
                                }
                                if (empty($advancedCustom->doNotShowEncoderResolutionHD)) {
                                    ?> 
                                    <label>
                                        <input type="checkbox" id="inputHD" <?php if (!empty($advancedCustom->doNotShowEncoderHLS)) echo 'checked="checked"'; ?> class="mp4Checkbox" onclick="if ($(this).is(':checked')) {
                                                                $('#inputHLS').prop('checked', false);
                                                            }"> HD
                                    </label>
                                    <?php
                                }
                                ?> 
                            </div>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-heading"><i class="fas fa-cogs"></i> Advanced</div>
                            <div class="panel-body">
                                <?php if (!empty($advancedCustom->doNotShowExtractAudio)) { ?>
                                    <label>
                                        <input type="checkbox" id="inputAudioOnly">
                                        <span class="glyphicon glyphicon-headphones"></span> Extract Audio
                                    </label><br>
                                <?php } ?>
                                <?php if (!empty($advancedCustom->doNotShowCreateVideoSpectrum)) { ?>
                                <label style="display: none;" id="spectrum">
                                    <input type="checkbox" id="inputAudioSpectrum">
                                    <span class="glyphicon glyphicon-equalizer"></span> Create Video Spectrum
                                </label>
                                <?php } ?>
                                <?php
                                if (empty($global['disableWebM'])) {
                                    ?>
                                    <label  id="webm">
                                        <input type="checkbox" id="inputWebM">
                                        <i class="fas fa-chrome" aria-hidden="true"></i> Extract WebM Video <small class="text-muted">(The encode process will be slow)</small>
                                        <br><small class="label label-warning">
                                            For Chrome Browsers
                                        </small>
                                    </label>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                    <div class="panel panel-success">
                        <div class="panel-heading"><span class="glyphicon glyphicon-send"></span> Streamer info </div>
                        <div class="panel-body">
                            <i class="fas fa-globe"></i> <strong><?php echo Login::getStreamerURL(); ?></strong><br>
                            <i class="fas fa-user"></i> User: <strong><?php echo Login::getStreamerUser(); ?></strong><br>
                        </div>
                    </div>
                </div>

                <script>
                    var encodingNowId = "";
                    function checkFiles() {
                        var path = $('#path').val();
                        if (!path) {
                            return false;
                        }
                        $.ajax({
                            url: 'listFiles.json?<?php echo getPHPSessionIDURL(); ?>',
                            data: {"path": path},
                            type: 'post',
                            success: function (response) {
                                $('#files').empty();
                                if (response) {
                                    for (i = 0; i < response.length; i++) {
                                        if (!response[i])
                                            continue;
                                        $('#files').append('<li class="list-group-item" path="' + response[i].path + '" id="li' + i + '"><span class="label label-success" style="display: none;"><span class="glyphicon glyphicon-ok"></span> Added on queue.. </span> ' + response[i].name + '<div class="material-switch pull-right"><input id="someSwitchOption' + response[i].id + '" class="someSwitchOption" type="checkbox"/><label for="someSwitchOption' + response[i].id + '" class="label-primary"></label></div></li>');
                                    }
                                }
                            }
                        });
                    }

                    function isAChannel() {
                        return /^(http(s)?:\/\/)?((w){3}.)?youtu(be|.be)?(\.com)?\/(channel|user).+/gm.test($('#inputVideoURL').val());
                    }

                    function checkProgress() {
                        $.ajax({
                            url: 'status?<?php echo getPHPSessionIDURL(); ?>',
                            success: function (response) {
                                if (response.queue_list.length) {
                                    for (i = 0; i < response.queue_list.length; i++) {
                                        createQueueItem(response.queue_list[i], response.queue_list[i - 1]);
                                    }

                                }
                                if (response.encoding) {
                                    var id = response.encoding.id;
                                    // if start encode next before get 100%
                                    if (id !== encodingNowId) {
                                        $("#encodeProgress" + encodingNowId).slideUp("normal", function () {
                                            $(this).remove();
                                        });
                                        encodingNowId = id;
                                    }

                                    $("#downloadProgress" + id).slideDown();

                                    if (response.download_status && !response.encoding_status.progress) {
                                        $("#encodingProgress" + id).find('.progress-completed').html("<strong>" + response.encoding.name + " [Downloading ...] </strong> " + response.download_status.progress + '%');
                                    } else {
                                        $("#encodingProgress" + id).find('.progress-completed').html("<strong>" + response.encoding.name + " [" + response.encoding_status.from + " to " + response.encoding_status.to + "] </strong> " + response.encoding_status.progress + '%');
                                        $("#encodingProgress" + id).find('.progress-bar').css({'width': response.encoding_status.progress + '%'});
                                    }
                                    if (response.download_status) {
                                        $("#downloadProgress" + id).find('.progress-bar').css({'width': response.download_status.progress + '%'});
                                    }
                                    if (response.encoding_status.progress >= 100) {
                                        $("#encodingProgress" + id).find('.progress-bar').css({'width': '100%'});
                                        setTimeout(function () {
                                            $("#encodeProgress" + id).fadeOut("slow", function () {
                                                $(this).remove();
                                            });
                                            $("#downloadProgress" + id).slideUp("fast", function () {
                                                $(this).remove();
                                            });
                                        }, 3000);
                                    } else {

                                    }

                                    setTimeout(function () {
                                        checkProgress();
                                    }, 1000);
                                } else if (encodingNowId !== "") {
                                    $("#encodeProgress" + encodingNowId).slideUp("normal", function () {
                                        $(this).remove();
                                    });
                                    encodingNowId = "";
                                    setTimeout(function () {
                                        checkProgress();
                                    }, 5000);
                                } else {
                                    setTimeout(function () {
                                        checkProgress();
                                    }, 5000);
                                }

                            }
                        });
                    }

                    function createQueueItem(queueItem, queueItemAfter) {
                        if ($('#encodeProgress' + queueItem.id).length) {
                            return false;
                        }
                        console.log(queueItemAfter);
                        var item = '<div id="encodeProgress' + queueItem.id + '">';
                        item += '<a href="' + queueItem.streamer_site + '" class="btn btn-default btn-xs" target="_blank">' + queueItem.streamer_site + '</a>';
                        item += '<div class="progress progress-striped active encodingProgress" id="encodingProgress' + queueItem.id + '" style="margin: 0;">';
                        item += '<div class="progress-bar  progress-bar-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0;">';
                        item += '<span class="sr-only">0% Complete</span></div><span class="progress-type"><span class="badge ">Priority ' + queueItem.streamer_priority + '</span> ' + queueItem.title + '</span><span class="progress-completed">' + queueItem.name + '</span>';
                        item += '</div><div class="progress progress-striped active downloadProgress" id="downloadProgress' + queueItem.id + '" style="height: 10px;"><div class="progress-bar  progress-bar-danger" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0;"></div></div> ';
                        item += '</div>';
                        if (typeof queueItemAfter === 'undefined' || !$("#" + queueItemAfter.id).length) {
                            $("#encoding").append(item);
                        } else {
                            $(item).insertAfter("#" + queueItemAfter.id);
                        }
                    }

                    var streamerMaxFileSize = 0;

                    $(document).ready(function () {
                        checkProgress();

                        $("input[name='format']").click(function () {
                            Cookies.set('format', $(this).attr('id'), {sameSite: 'None'});
                        });

                        var streamerURL = "<?php echo Login::getStreamerURL(); ?>";
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
                            success: function (response) {
                                $('#max_file_size').text(response.max_file_size);
                                streamerMaxFileSize = response.file_upload_max_size;
                                $('#currentStorageUsage').text((response.currentStorageUsage / 60).toFixed(2) + " Minutes");
                                if (response.videoStorageLimitMinutes) {
                                    $('#videoStorageLimitMinutes').text(response.videoStorageLimitMinutes + " Minutes");
                                } else {
                                    $('#videoStorageLimitMinutes').text("Unlimited");
                                }
                            }
                        });

                        $("#addQueueBtn").click(function () {
                            $('#files li').each(function () {
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
                                            "usergroups_id": $(".usergroups_id:checked").map(function () {
                                                return $(this).val();
                                            }).get()
                                        },
                                        type: 'post',
                                        success: function (response) {
                                            $('#' + id).find('.label').fadeIn();
                                        }
                                    });
                                }

                            })

                        });

                        $("#pathBtn").click(function () {
                            checkFiles();
                        });

                        $("#checkBtn").click(function () {
                            $('#files').find('input:checkbox').prop('checked', true);
                        });
                        $("#uncheckBtn").click(function () {
                            $('#files').find('input:checkbox').prop('checked', false);
                        });

                        $('#saveConfig').click(function () {
                            modal.showPleaseWait();
                            var formats = new Array();
                            var count = 0;
                            $(".formats").each(function (index) {
                                var id = $(this).attr('id');
                                var parts = id.split("_");
                                formats[count++] = [parts[1], $(this).val()];
                            });

                            $.ajax({
                                url: 'saveConfig?<?php echo getPHPSessionIDURL(); ?>',
                                data: {
                                    "formats": formats,
                                    "allowedStreamers": $("#allowedStreamers").val(),
                                    "defaultPriority": $("#defaultPriority").val(),
                                    "autodelete": $("#autodelete").is(":checked"),
                                },
                                type: 'post',
                                success: function (response) {
                                    console.log(response);
                                    modal.hidePleaseWait();
                                }
                            });
                            return false;
                        });


                        $('#downloadForm').submit(function (evt) {
                            evt.preventDefault();
                            if (isAChannel()) {
    <?php
    if (Login::canBulkEncode()) {
        ?>
                                    swal({
                                        title: "Are you sure?",
                                        text: "This is a Channel, are you sure you want to download all videos on this channel?<br>It may take a while to complete<br>Start Index: <input type='number'  id='startIndex' value='0' style='width:100px;'><br>End Index: <input type='number'  id='endIndex' value='100' style='width:100px;'>",
                                        showCancelButton: true,
                                        confirmButtonColor: '#DD6B55',
                                        confirmButtonText: 'Yes, I am sure!',
                                        cancelButtonText: "No, cancel it!",
                                        closeOnConfirm: true,
                                        closeOnCancel: true,
                                        html: true,
                                        dangerMode: true
                                    },
                                            function (isConfirm) {

                                                if (isConfirm) {
                                                    modal.showPleaseWait();
                                                    $.ajax({
                                                        url: 'youtubeDl.json?<?php echo getPHPSessionIDURL(); ?>',
                                                        data: {
                                                            "videoURL": $('#inputVideoURL').val(),
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
                                                            "categories_id": $('#download_categories_id').val(),
                                                            "usergroups_id": $(".usergroups_id:checked").map(function () {
                                                                return $(this).val();
                                                            }).get(),
                                                            "startIndex": $('#startIndex').val(),
                                                            "endIndex": $('#endIndex').val()
                                                        },
                                                        type: 'post',
                                                        success: function (response) {
                                                            if (response.text) {
                                                                swal({
                                                                    title: "Channel Import is complete",
                                                                    text: "All your videos were imported",
                                                                    type: "success",
                                                                    html: true});
                                                            }
                                                            console.log(response);
                                                        }
                                                    });
                                                    setTimeout(function () {
                                                        swal({
                                                            title: "Channel Import is on queue",
                                                            text: "All your videos channel will be process, this may take a while to be complete",
                                                            type: "success",
                                                            html: true});
                                                    }, 500);
                                                    modal.hidePleaseWait();
                                                } else {

                                                }
                                            });
        <?php
    } else {
        ?>
                                    swal({
                                        title: "Sorry",
                                        text: "Channel Import is disabled",
                                        type: "warning",
                                        html: true});
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
                                        "inputHLS": $('#inputHLS').is(":checked"),
                                        "inputLow": $('#inputLow').is(":checked"),
                                        "inputSD": $('#inputSD').is(":checked"),
                                        "inputHD": $('#inputHD').is(":checked"),
                                        "inputAutoHLS": $('#inputAutoHLS').is(":checked"),
                                        "inputAutoMP4": $('#inputAutoMP4').is(":checked"),
                                        "inputAutoWebm": $('#inputAutoWebm').is(":checked"),
                                        "inputAutoAudio": $('#inputAutoAudio').is(":checked"),
                                        "categories_id": $('#download_categories_id').val(),
                                        "usergroups_id": $(".usergroups_id:checked").map(function () {
                                            return $(this).val();
                                        }).get()
                                    },
                                    type: 'post',
                                    success: function (response) {
                                        if (response.text) {
                                            swal({
                                                title: response.title,
                                                text: response.text,
                                                type: response.type,
                                                html: true});
                                        }
                                        console.log(response);
                                        modal.hidePleaseWait();
                                    }
                                });
                            }
                            return false;
                        });

                        $('#inputAudioOnly').change(function () {
                            if ($(this).is(":checked")) {
                                $('#webm').fadeOut("slow", function () {
                                    $('#spectrum').fadeIn();
                                });
                            } else {
                                $('#spectrum').fadeOut("slow", function () {
                                    $('#webm').fadeIn();
                                });
                            }
                        });

                        var grid = $("#grid").bootgrid({
                            ajax: true,
                            url: "queue.json?<?php echo getPHPSessionIDURL(); ?>",
                            formatters: {
                                "commands": function (column, row) {
                                    var reQueue = '';
                                    var deleteQueue = '';
                                    var sendFileQueue = '';

                                    if (row.status != 'queue' && row.status != 'encoding') {
                                        reQueue = '<button type="button" class="btn btn-xs btn-default command-reQueue" data-toggle="tooltip" title="Re-Queue"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span></button>'
                                    }
                                    deleteQueue = '<button type="button" class="btn btn-xs btn-default command-deleteQueue" data-toggle="tooltip" title="Delete Queue"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button>'
                                    if (row.status === 'done' || row.status === 'transferring') {
                                        sendFileQueue = '<button type="button" class="btn btn-xs btn-default command-sendFileQueue" data-toggle="tooltip" title="Send Notify"><span class="glyphicon glyphicon-send" aria-hidden="true"></span></button>'
                                    }

                                    return sendFileQueue + reQueue + deleteQueue;
                                },
                                "dates": function (column, row) {
                                    return "Created: " + row.created + "<br>Modified: " + row.modified;
                                },
                                "status": function (column, row) {
                                    var btn = '<button class="btn btn-xs btn-default" data-toggle="popover" title="Details" data-content="' + row.status_obs + '"><label class="glyphicon glyphicon-alert"></label></button> ';
                                    var label = "warning";
                                    if (row.status == "error") {
                                        label = "danger";
                                    } else
                                    if (row.status == "done") {
                                        label = "success";
                                    } else
                                    if (row.status == "queue") {
                                        label = "primary";
                                    }
                                    var status = '<span class="label label-' + label + '">' + row.status + '</span>';

                                    return btn + status + "<br>" + row.status_obs;
                                },
                                "title": function (column, row) {
                                    var l = getLocation(row.streamer);
                                    var title = '<a href="' + row.streamer + '" target="_blank" class="btn btn-primary btn-xs">' + l.hostname + ' <span class="badge">Priority ' + row.priority + '</span></a>';
                                    title += '<br><span class="label label-primary">' + row.format + '</span>';

                                    for (const index in row.fileInfo) {
                                        if (typeof row.fileInfo[index].text === 'undefined') {
                                            continue;
                                        }
                                        title += '<br><span class="label label-success fileSize" >' + row.fileInfo[index].text + '</span>';
                                    }

                                    title += '<br>' + row.title;
                                    return title;
                                }
                            }
                        }).on("loaded.rs.jquery.bootgrid", function () {
                            /* Executes after data is loaded and rendered */
                            grid.find(".command-reQueue").on("click", function (e) {
                                modal.showPleaseWait();
                                var row_index = $(this).closest('tr').index();
                                var row = $("#grid").bootgrid("getCurrentRows")[row_index];
                                console.log(row);
                                $.ajax({
                                    url: 'queue?<?php echo getPHPSessionIDURL(); ?>',
                                    data: {"id": row.id, "fileURI": row.fileURI},
                                    type: 'post',
                                    success: function (response) {
                                        $("#grid").bootgrid("reload");
                                        modal.hidePleaseWait();
                                    }
                                });
                            });

                            grid.find(".command-deleteQueue").on("click", function (e) {
                                modal.showPleaseWait();
                                var row_index = $(this).closest('tr').index();
                                var row = $("#grid").bootgrid("getCurrentRows")[row_index];
                                console.log(row);
                                $.ajax({
                                    url: 'deleteQueue?<?php echo getPHPSessionIDURL(); ?>',
                                    data: {"id": row.id},
                                    type: 'post',
                                    success: function (response) {
                                        $("#grid").bootgrid("reload");
                                        modal.hidePleaseWait();
                                        if (response.error) {
                                            swal({
                                                title: "Ops",
                                                text: response.msg,
                                                type: "error",
                                                html: true});
                                        }
                                    }
                                });
                            });
                            grid.find(".command-sendFileQueue").on("click", function (e) {
                                modal.showPleaseWait();
                                var row_index = $(this).closest('tr').index();
                                var row = $("#grid").bootgrid("getCurrentRows")[row_index];
                                console.log(row);
                                $.ajax({
                                    url: 'send.json?<?php echo getPHPSessionIDURL(); ?>',
                                    data: {"id": row.id},
                                    type: 'post',
                                    success: function (response) {
                                        $("#grid").bootgrid("reload");
                                        modal.hidePleaseWait();
                                    }
                                });
                            });
                            $('[data-toggle="popover"]').popover();
                        });



                        var gridStreamer = $("#gridStreamer").bootgrid({
                            ajax: true,
                            url: "streamers.json?<?php echo getPHPSessionIDURL(); ?>",
                            formatters: {
                                "priority": function (column, row) {
                                    var tag = "<select class='priority' rowId='" + row.id + "'>";
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
                                "admin": function (column, row) {
                                    var tag = "<select class='isAdmin' rowId='" + row.id + "'>";
                                    tag += "<option value='1' " + (row.isAdmin == "1" ? "selected" : "") + ">Yes</option>";
                                    tag += "<option value='0' " + (row.isAdmin == "1" ? "" : "selected") + ">No</option>";
                                    tag += "</select>";
                                    return tag;
                                },
                                "commands": function (column, row) {
                                    var deleteBtn = '<button type="button" class="btn btn-xs btn-default command-delete" data-toggle="tooltip" title="Delete Queue"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button>';

                                    return deleteBtn;
                                }
                            }
                        }).on("loaded.rs.jquery.bootgrid", function () {
                            gridStreamer.find(".command-delete").on("click", function (e) {
                                modal.showPleaseWait();
                                var row_index = $(this).closest('tr').index();
                                var row = $("#gridStreamer").bootgrid("getCurrentRows")[row_index];
                                console.log(row);
                                $.ajax({
                                    url: 'removeStreamer?<?php echo getPHPSessionIDURL(); ?>',
                                    data: {"id": row.id},
                                    type: 'post',
                                    success: function (response) {
                                        $("#gridStreamer").bootgrid("reload");
                                        modal.hidePleaseWait();
                                    }
                                });
                            });

                            gridStreamer.find(".priority").on("change", function (e) {
                                modal.showPleaseWait();
                                $.ajax({
                                    url: 'priority?<?php echo getPHPSessionIDURL(); ?>',
                                    data: {"id": $(this).attr('rowId'), "priority": $(this).val()},
                                    type: 'post',
                                    success: function (response) {
                                        modal.hidePleaseWait();
                                    }
                                });
                            });

                            gridStreamer.find(".isAdmin").on("change", function (e) {
                                modal.showPleaseWait();
                                $.ajax({
                                    url: 'isAdmin?<?php echo getPHPSessionIDURL(); ?>',
                                    data: {"id": $(this).attr('rowId'), "isAdmin": $(this).val()},
                                    type: 'post',
                                    success: function (response) {
                                        modal.hidePleaseWait();
                                    }
                                });
                            });
                        });
                        $('[data-toggle="tooltip"]').tooltip();
                    }
                    );

                </script>
                <?php
            }
            ?>

        </div>

    </body>
</html>
