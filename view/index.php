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
$streamerURL = @$_REQUEST['webSiteRootURL'];
if (empty($streamerURL)) {
    $streamerURL = Streamer::getFirstURL();
}
$streamerURL = addLastSlash($streamerURL);

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
        <link rel="apple-touch-icon" sizes="180x180" href="<?php echo Login::getStreamerURL(); ?>videos/favicon.png">
        <link rel="icon" type="image/png" href="<?php echo Login::getStreamerURL(); ?>videos/favicon.png">
        <link rel="shortcut icon" href="<?php echo Login::getStreamerURL(); ?>videos/favicon.ico" sizes="16x16,24x24,32x32,48x48,144x144">
        <meta name="msapplication-TileImage" content="<?php echo Login::getStreamerURL(); ?>videos/favicon.png">        

        <script src="<?php echo $global['webSiteRootURL']; ?>node_modules/jquery/dist/jquery.min.js" type="text/javascript"></script>
        <link href="<?php echo $global['webSiteRootURL']; ?>node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
        <script src="<?php echo $global['webSiteRootURL']; ?>node_modules/bootstrap/dist/js/bootstrap.min.js" type="text/javascript"></script>
        <script src="<?php echo Login::getStreamerURL(); ?>node_modules/sweetalert/dist/sweetalert.min.js" type="text/javascript"></script>
        <link href="<?php echo Login::getStreamerURL(); ?>node_modules/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo Login::getStreamerURL(); ?>node_modules/jquery-toast-plugin/dist/jquery.toast.min.css" rel="stylesheet" type="text/css"/>
        <script src="<?php echo Login::getStreamerURL(); ?>node_modules/jquery-toast-plugin/dist/jquery.toast.min.js" type="text/javascript"></script>

        <script src="<?php echo Login::getStreamerURL(); ?>view/js/script.js" type="text/javascript"></script>
        <script src="<?php echo Login::getStreamerURL(); ?>node_modules/js-cookie/dist/js.cookie.js" type="text/javascript"></script>

        <script src="<?php echo $global['webSiteRootURL']; ?>view/js/polyfill.min.js" type="text/javascript"></script>

        <link rel="stylesheet" href="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/css/jquery.fileupload.css">
        <link rel="stylesheet" href="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/css/jquery.fileupload-ui.css">
        <!-- CSS adjustments for browsers with JavaScript disabled -->
        <noscript><link rel="stylesheet" href="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/css/jquery.fileupload-noscript.css"></noscript>
        <noscript><link rel="stylesheet" href="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/css/jquery.fileupload-ui-noscript.css"></noscript>
        <link href="<?php echo $global['webSiteRootURL']; ?>view/bootgrid/jquery.bootgrid.min.css" rel="stylesheet" type="text/css"/>
        <script src="<?php echo $global['webSiteRootURL']; ?>view/bootgrid/jquery.bootgrid.min.js" type="text/javascript"></script>

        <script src="<?php echo $global['webSiteRootURL']; ?>view/js/main.js?<?php echo filectime($global['systemRootPath'] . "view/js/main.js"); ?>" type="text/javascript"></script>
        <link href="<?php echo $global['webSiteRootURL']; ?>view/css/style.css?<?php echo filectime($global['systemRootPath'] . "view/css/style.css"); ?>" rel="stylesheet" type="text/css"/>

        <link href="<?php echo Login::getStreamerURL(); ?>view/css/main.css"" rel="stylesheet" crossorigin="anonymous">
        <link href="<?php echo Login::getStreamerURL(); ?>view/theme.css.php" rel="stylesheet" type="text/css"/>
        <link href="<?php echo Login::getStreamerURL(); ?>node_modules/animate.css/animate.min.css" rel="stylesheet" type="text/css"/>


        <script src="<?php echo Login::getStreamerURL(); ?>view/js/jquery-ui/jquery-ui.min.js" type="text/javascript"></script>
        <link href="<?php echo Login::getStreamerURL(); ?>view/js/jquery-ui/jquery-ui.min.css" rel="stylesheet" type="text/css"/>
        <script>
            var webSiteRootPath = '<?php echo $global['webSiteRootPath']; ?>';
            var webSiteRootURL = '<?php echo Login::getStreamerURL(); ?>';
            var PHPSESSID = '<?php echo session_id(); ?>';
        </script>
        <style>
<?php
if (!empty($_GET['noNavbar'])) {
    ?>
                body, body > div.main-container{
                    padding: 0;
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
                        <a class="navbar-brand" href="<?php echo Login::getStreamerURL(); ?>" >
                            <?php
                            if (!empty($_SESSION['login']->siteLogo)) {
                                ?>
                                <img src="<?php echo $_SESSION['login']->siteLogo; ?>?<?php echo uniqid(); ?>" class="img img-responsive " >    
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
                                            <input  id="inputUser" placeholder="User" class="form-control"  type="text" value="<?php echo @$_REQUEST['user']; ?>" required >
                                        </div>
                                    </div>
                                </div>


                                <div class="form-group">
                                    <label class="col-md-4 control-label">Password</label>  
                                    <div class="col-md-8 inputGroupContainer">
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                                            <input  id="inputPassword" placeholder="Password" class="form-control"  type="password" value="<?php echo @$_REQUEST['pass']; ?>" >
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
            echo (!empty($streamerURL) && !empty($_REQUEST['user']) && !empty($_REQUEST['pass'])) ? 'true' : 'false';
            ?>;
                    $(document).ready(function () {
                        $('#loginForm').submit(function (evt) {
                            evt.preventDefault();
                            modal.showPleaseWait();
                            $.ajax({
                                url: 'login',
                                data: {"user": $('#inputUser').val(), "pass": $('#inputPassword').val(), "siteURL": $('#siteURL').val(), "encodedPass": encodedPass},
                                xhrFields: {
                                    //withCredentials: true
                                },
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

                <div class="col-md-4" id="rightContainer" >
                    <div class="panel panel-default ">
                        <div class="panel-heading tabbable-line">
                            <ul class="nav nav-tabs">
                                <li class="nav-item active  <?php echo getCSSAnimationClassAndStyle('animate__bounceInDown', 'tabsRight', 0.1); ?>">
                                    <a data-toggle="tab" href="#basicOptions" class="nav-link"><i class="fas fa-cog"></i> Basic</a>
                                </li>
                                <?php
                                if (empty($global['hideAdvanced'])) {
                                    ?>
                                    <li class="nav-item  <?php echo getCSSAnimationClassAndStyle('animate__bounceInDown', 'tabsRight', 0.1); ?>">
                                        <a data-toggle="tab" href="#advancedOptions" class="nav-link"><i class="fas fa-cogs"></i> Advanced</a>
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
                                    if (!empty($_SESSION['login']->userGroups) && empty($global['hideUserGroups'])) {
                                        ?>
                                        <div class="panel panel-default">
                                            <div class="panel-heading clearfix"><i class="fas fa-users"></i> 
                                                User Groups

                                                <?php
                                                if (Login::isStreamerAdmin()) {
                                                    ?>
                                                    <button class="btn btn-primary btn-xs pull-right " type="button" onclick="addNewUserGroup();"><i class="fas fa-plus"></i></button>
                                                    <script>
                                                        var reloadIfIsNotEditingUserGroupTimeout;
                                                        function addNewUserGroup() {
                                                            clearTimeout(reloadIfIsNotEditingUserGroupTimeout);
                                                            avideoModalIframe('<?php echo $streamerURL; ?>usersGroups');
                                                            reloadIfIsNotEditingUserGroupTimeout = setTimeout(function () {
                                                                reloadIfIsNotEditingUserGroup();
                                                            }, 500);
                                                        }

                                                        function reloadIfIsNotEditingUserGroup() {
                                                            clearTimeout(reloadIfIsNotEditingUserGroupTimeout);
                                                            if (!avideoModalIframeIsVisible()) {
                                                                loadUserGroups();
                                                            } else {
                                                                reloadIfIsNotEditingUserGroupTimeout = setTimeout(function () {
                                                                    reloadIfIsNotEditingUserGroup();
                                                                }, 500);
                                                            }
                                                        }
                                                    </script>
                                                    <?php
                                                }
                                                ?>
                                            </div>
                                            <div class="panel-body" id="userGroupsList">
                                                <div class="row">
                                                    <?php
                                                    foreach ($_SESSION['login']->userGroups as $key => $value) {
                                                        ?>
                                                        <div class="col-xs-6  <?php echo getCSSAnimationClassAndStyle('animate__flipInX', 'usergroups'); ?>">
                                                            <label>
                                                                <input type="checkbox" class="usergroups_id" name="usergroups_id[]" value="<?php echo $value->id; ?>">
                                                                <i class="fas fa-lock"></i> <?php echo $value->group_name; ?>
                                                            </label>
                                                        </div>
                                                        <?php
                                                    }
                                                    ?>
                                                </div>
                                                <div class="alert alert-info" style="margin-bottom: 0px;"><i class="fas fa-info-circle"></i> Unckeck all to make it public</div>

                                            </div>
                                        </div>
                                        <script>
                                            function loadUserGroups() {
                                                modal.showPleaseWait();
                                                $.ajax({
                                                    url: '<?php echo $streamerURL; ?>objects/usersGroups.json.php',
                                                    success: function (response) {
                                                        $('#userGroupsList').empty();
                                                        for (var item in response.rows) {
                                                            if (typeof response.rows[item] != 'object') {
                                                                continue;
                                                            }
                                                            $('#userGroupsList').append('<label><input type="checkbox" class="usergroups_id" name="usergroups_id[]" value="' + response.rows[item].id + '"><i class="fas fa-lock"></i> ' + response.rows[item].group_name + '</label><br>');
                                                        }
                                                        modal.hidePleaseWait();
                                                    }
                                                });
                                            }
                                            $(document).ready(function () {
                                                //loadUserGroups();
                                            });
                                        </script>
                                        <?php
                                    }

                                    if (empty($advancedCustom->doNotAllowEncoderOverwriteStatus)) {
                                        ?>
                                        <div class="panel panel-default">
                                            <div class="panel-heading"><i class="fas fa-desktop"></i> Override status</div>
                                            <div class="panel-body">
                                                <select class="form-control" id="override_status" name="override_status">
                                                    <option value="">Use site default</option>
                                                    <option value="a">Active</option>
                                                    <option value="i">Inactive</option>
                                                    <option value="u">Unlisted</option>
                                                    <option value="s">Unlisted but Searchable</option>
                                                </select>
                                            </div>
                                        </div>
                                        <?php
                                    }

                                    if (empty($advancedCustom->doNotAllowUpdateVideoId)) {
                                        ?>
                                        <div class="panel panel-default">
                                            <div class="panel-heading"><i class="fas fa-desktop"></i> Update existing video</div>
                                            <div class="panel-body">
                                                <img id="inputNextVideo-poster" src="view/img/notfound.jpg" class="ui-state-default img img-responsive" alt="">
                                                <input type="text" class="form-control" id="videoSearch" name="videoSearch" placeholder="Search for a video">
                                                <input type="number" class="form-control" id="update_video_id" name="update_video_id" placeholder="Video Id">
                                            </div>
                                        </div>

                                        <script>
                                            $(function () {
                                                $("#videoSearch").autocomplete({
                                                    minLength: 0,
                                                    source: function (req, res) {
                                                        $.ajax({
                                                            url: '<?php echo Login::getStreamerURL(); ?>objects/videos.json.php?rowCount=6',
                                                            data: {
                                                                searchPhrase: req.term,
                                                                users_id: '<?php echo Login::getStreamerUserId(); ?>',
                                                                user: '<?php echo Login::getStreamerUser(); ?>',
                                                                pass: '<?php echo Login::getStreamerPass(); ?>',
                                                                encodedPass: true
                                                            },
                                                            /*
                                                             xhrFields: {
                                                             //withCredentials: true
                                                             },
                                                             */
                                                            type: 'post',
                                                            success: function (data) {
                                                                res(data.rows);
                                                            }
                                                        });
                                                    },
                                                    focus: function (event, ui) {
                                                        $("#videoSearch").val(ui.item.title);
                                                        return false;
                                                    },
                                                    select: function (event, ui) {
                                                        $("#videoSearch").val(ui.item.title);
                                                        $("#update_video_id").val(ui.item.id);
                                                        console.log(ui.item.videosURL);
                                                        console.log(ui.item.videosURL.jpg);
                                                        $("#inputNextVideo-poster").attr("src", ui.item.videosURL.jpg.url);
                                                        return false;
                                                    }
                                                }).autocomplete("instance")._renderItem = function (ul, item) {
                                                    return $("<li>").append("<div>" + item.title + "</div>").appendTo(ul);
                                                };
                                            });
                                        </script>
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
                                                <?php if (empty($advancedCustom->doNotShowExtractAudio)) { ?>
                                                    <label>
                                                        <input type="checkbox" id="inputAudioOnly">
                                                        <span class="glyphicon glyphicon-headphones"></span> Extract Audio
                                                    </label><br>
                                                <?php } ?>
                                                <?php if (empty($advancedCustom->doNotShowCreateVideoSpectrum)) { ?>
                                                    <label style="display: none;" id="spectrum">
                                                        <input type="checkbox" id="inputAudioSpectrum">
                                                        <span class="glyphicon glyphicon-equalizer"></span> Create Video Spectrum
                                                    </label>
                                                <?php } ?>
                                                <?php
                                                if (empty($global['disableWebM'])) {
                                                    if (empty($global['defaultWebM']))
                                                        $checked = '';
                                                    else
                                                        $checked = 'checked="checked"';
                                                    ?>
                                                    <label  id="webm">
                                                        <input type="checkbox" id="inputWebM" <?php echo $checked; ?>>
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
                                        ?>
                                        class="nav-item active <?php echo getCSSAnimationClassAndStyle('animate__bounceInDown', 'tabsRight', 0.1); ?>"
                                        <?php
                                    } else {
                                        ?>
                                        class="nav-item <?php echo getCSSAnimationClassAndStyle('animate__bounceInDown', 'tabsRight', 0.1); ?>"
                                        <?php
                                    }
                                    ?>>
                                    <a data-toggle="tab" href="#encoding" class="nav-link"><span class="glyphicon glyphicon-tasks"></span> Sharing Queue</a>
                                </li>
                                <li class="nav-item <?php echo getCSSAnimationClassAndStyle('animate__bounceInDown', 'tabsRight', 0.1); ?>">
                                    <a data-toggle="tab" href="#log" class="nav-link"><span class="glyphicon glyphicon-cog"></span> Queue Log</a>
                                </li>

                                <?php
                                if (Login::isAdmin()) {
                                    if (empty($global['disableConfigurations'])) {
                                        ?>
                                        <li class="nav-item <?php echo getCSSAnimationClassAndStyle('animate__bounceInDown', 'tabsRight', 0.1); ?>">
                                            <a data-toggle="tab" href="#config" class="nav-link"><span class="glyphicon glyphicon-cog"></span> Configurations</a>
                                        </li>
                                        <li <?php
                            if (!empty($_POST['updateFile'])) {
                                            ?>
                                                class="nav-item active <?php echo getCSSAnimationClassAndStyle('animate__bounceInDown', 'tabsRight', 0.1); ?>"
                                                <?php
                                            } else {
                                                ?>
                                                class="nav-item <?php echo getCSSAnimationClassAndStyle('animate__bounceInDown', 'tabsRight', 0.1); ?>"
                                                <?php
                                            }
                                            ?>>
                                            <a data-toggle="tab" href="#update" class="nav-link" ><span class="fas fa-wrench"></span> Update <?php if (!empty($updateFiles)) { ?>
                                                    <label class="label label-danger"><?php echo count($updateFiles); ?></label><?php } ?>
                                            </a>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                    <li class="nav-item <?php echo getCSSAnimationClassAndStyle('animate__bounceInDown', 'tabsRight', 0.1); ?>">
                                        <a data-toggle="tab" href="#streamers"  class="nav-link"><span class="glyphicon glyphicon-user"></span> Streamers</a>
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
                                                <th data-column-id="title" data-formatter="title">Title</th>
                                                <th data-column-id="status" data-formatter="status">Status</th>
                                                <th data-column-id="created" data-formatter="dates"  data-order="desc">Dates</th>
                                                <th data-column-id="commands" data-formatter="commands" data-sortable="false"  data-width="120px"></th>
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
                            data: {"path": path},
                            xhrFields: {
                                //withCredentials: true
                            },
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
                            xhrFields: {
                                //withCredentials: true
                            },
                            success: function (response) {
                                if (response.queue_list.length) {
                                    for (i = 0; i < response.queue_list.length; i++) {
                                        createQueueItem(response.queue_list[i], response.queue_list[i - 1]);
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
                                            $("#encodeProgress" + id).slideUp("normal", function () {
                                                $(this).remove();
                                            });
                                        }
                                    }
                                    encodingNowIds = newEncodingNowIds;

                                    for (i = 0; i < encodingNowIds.length; i++) {
                                        var id = encodingNowIds[i];
                                        $("#downloadProgress" + id).slideDown();


                                        if (response.download_status[i] && !response.encoding_status[i].progress) {
                                            $("#encodingProgress" + id).find('.progress-completed').html("<strong>" + response.encoding[i].name + " [Downloading ...] </strong> " + response.download_status[i].progress + '%');
                                        } else {
                                            $("#encodingProgress" + id).find('.progress-completed').html("<strong>" + response.encoding[i].name + " [" + response.encoding_status[i].from + " to " + response.encoding_status[i].to + "] </strong> " + response.encoding_status[i].progress + '% ' + response.encoding_status[i].remainTimeHuman);
                                            $("#encodingProgress" + id).find('.progress-bar').css({'width': response.encoding_status[i].progress + '%'});
                                        }
                                        if (response.download_status[i]) {
                                            $("#downloadProgress" + id).find('.progress-bar').css({'width': response.download_status[i].progress + '%'});
                                        }
                                        if (response.encoding_status[i].progress >= 100) {
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

                                    }
                                    setTimeout(function () {
                                        checkProgress();
                                    }, 1000);
                                } else {
                                    while ((id = encodingNowIds.pop()) != null) {
                                        $("#encodeProgress" + id).slideUp("normal", function () {
                                            $(this).remove();
                                        });
                                    }
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
                        var item = '<div id="encodeProgress' + queueItem.id + '" class="animate__animated animate__flipInX">';
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
                            xhrFields: {
                                //withCredentials: true
                            },
                            success: function (response) {
                                $('#max_file_size').text(response.max_file_size);
                                streamerMaxFileSize = response.file_upload_max_size;
                                $('#currentStorageUsage').text((response.currentStorageUsage / 60).toFixed(2) + " Minutes");
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
                                            "releaseDate": $('#bulk_releaseDate').val(),
                                            "callback": $('#callback').val(),
                                            "usergroups_id": $(".usergroups_id:checked").map(function () {
                                                return $(this).val();
                                            }).get()
                                        },
                                        xhrFields: {
                                            //withCredentials: true
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

                            var resolutions = $("input[name='resolutions']:checked").map(function () {
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
                                success: function (response) {
                                    console.log(response);
                                    modal.hidePleaseWait();
                                }
                            });
                            return false;
                        });


                        $('#downloadForm').submit(function (evt) {
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
                                        .then(function (confirm) {
                                            if (confirm) {
                                                submitDownloadForm();
                                            }
                                        });
                            } else {
                                submitDownloadForm();
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
                            xhrFields: {
                                //withCredentials: true
                            },
                            formatters: {
                                "commands": function (column, row) {
                                    var reQueue = '';
                                    var deleteQueue = '';
                                    var sendFileQueue = '';
                                    var edit = '';
                                    var return_vars = JSON.parse(row.return_vars);

                                    if (row.status != 'queue' && row.status != 'encoding') {
                                        reQueue = '<button type="button" class="btn btn-xs btn-default command-reQueue" data-toggle="tooltip" title="Re-Queue"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span></button>'
                                    }
                                    deleteQueue = '<button type="button" class="btn btn-xs btn-default command-deleteQueue" data-toggle="tooltip" title="Delete Queue"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button>'
                                    if (row.status === 'done' || row.status === 'transferring') {
                                        sendFileQueue = '<button type="button" class="btn btn-xs btn-default command-sendFileQueue" data-toggle="tooltip" title="Send Notify"><span class="glyphicon glyphicon-send" aria-hidden="true"></span></button>'
                                    }
                                    if (return_vars.videos_id) {
                                        edit = '<button type="button" class="btn btn-xs btn-default command-editFile" data-toggle="tooltip" title="Edit"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></button>'
                                    }

                                    return edit + sendFileQueue + reQueue + deleteQueue;
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

                                    var remainTimeHuman = '';
                                    if (row.encoding_status.remainTimeHuman) {
                                        remainTimeHuman = '<span class="label label-default">ETA ' + row.encoding_status.remainTimeHuman + '</span>';
                                    }

                                    return btn + status + "<br>" + row.status_obs + "<br>" + remainTimeHuman;
                                },
                                "title": function (column, row) {
                                    var l = getLocation(row.streamer);
                                    videos_id = 0;
                                    var json = JSON.parse(row.return_vars)
                                    if (typeof json.videos_id !== 'undefined') {
                                        videos_id = json.videos_id;
                                    }
                                    var title = '<a href="' + row.streamer + '" target="_blank" class="btn btn-primary btn-xs">[' + videos_id + '] ' + l.hostname + ' <span class="badge">Priority ' + row.priority + '</span></a>';
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
                                    xhrFields: {
                                        //withCredentials: true
                                    },
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
                                    xhrFields: {
                                        //withCredentials: true
                                    },
                                    type: 'post',
                                    success: function (response) {
                                        $("#grid").bootgrid("reload");
                                        modal.hidePleaseWait();
                                        if (response.error) {
                                            avideoAlertError(response.msg);
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
                                    xhrFields: {
                                        //withCredentials: true
                                    },
                                    type: 'post',
                                    success: function (response) {
                                        $("#grid").bootgrid("reload");
                                        modal.hidePleaseWait();
                                    }
                                });
                            });
                            grid.find(".command-editFile").on("click", function (e) {
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
                                    xhrFields: {
                                        //withCredentials: true
                                    },
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
                                    xhrFields: {
                                        //withCredentials: true
                                    },
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
                                    xhrFields: {
                                        //withCredentials: true
                                    },
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
                    function submitDownloadForm() {

                        if (isAChannel()) {
    <?php
    if (Login::canBulkEncode()) {
        ?>
                                var span = document.createElement("span");
                                span.innerHTML = "This is a Channel, are you sure you want to download all videos on this channel?<br>It may take a while to complete<br>Start Index: <input type='number'  id='startIndex' value='0' style='width:100px;'><br>End Index: <input type='number'  id='endIndex' value='100' style='width:100px;'>";

                                swal({
                                    title: "Are you sure?",
                                    content: span,
                                    icon: "warning",
                                    buttons: true,
                                    dangerMode: true,
                                })
                                        .then(function (confirm) {
                                            if (confirm) {
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
                                                        "usergroups_id": $(".usergroups_id:checked").map(function () {
                                                            return $(this).val();
                                                        }).get(),
                                                        "startIndex": $('#startIndex').val(),
                                                        "endIndex": $('#endIndex').val()
                                                    },
                                                    xhrFields: {
                                                        //withCredentials: true
                                                    },
                                                    type: 'post',
                                                    success: function (response) {
                                                        if (response.text) {
                                                            avideoAlertSuccess("All your videos were imported");
                                                        }
                                                        console.log(response);
                                                    }
                                                });
                                                setTimeout(function () {
                                                    avideoAlertInfo("All your videos channel will be process, this may take a while to be complete");
                                                }, 500);
                                                modal.hidePleaseWait();
                                            }
                                        });
        <?php
    } else {
        ?>
                                avideoAlertError("Channel Import is disabled");
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
                                    "usergroups_id": $(".usergroups_id:checked").map(function () {
                                        return $(this).val();
                                    }).get()
                                },
                                xhrFields: {
                                    //withCredentials: true
                                },
                                type: 'post',
                                success: function (response) {
                                    if (response.text) {
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
