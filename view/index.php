<?php
$config = dirname(__FILE__) . '/../videos/configuration.php';
if (!file_exists($config)) {
    header("Location: install/index.php");
}
//header('Access-Control-Allow-Origin: *');
require_once $config;
require_once '../objects/Encoder.php';
require_once '../objects/Configuration.php';
require_once '../objects/Format.php';
require_once '../objects/Streamer.php';
require_once '../objects/Login.php';
$rows = Encoder::getAllQueue();
$config = new Configuration();
if (empty($_POST['sort']) && $config->currentVersionGreaterThen("1.0")) {
    $_POST['sort']['`order`'] = 'asc';
}
$frows = Format::getAll();
$streamerURL = @$_GET['webSiteRootURL'];
if (empty($streamerURL)) {
    $streamerURL = Streamer::getFirstURL();
}
$config = new Configuration();

$ffmpegArray = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

$updateFiles = getUpdatesFiles();

$ad = $config->getAutodelete();
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
        <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
        <script src="view/js/jquery-3.2.0.min.js" type="text/javascript"></script>
        <link href="view/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
        <script src="view/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
        <link href="view/js/seetalert/sweetalert.css" rel="stylesheet" type="text/css"/>
        <script src="view/js/seetalert/sweetalert.min.js" type="text/javascript"></script>
        <script src="view/js/main.js" type="text/javascript"></script>
        <link href="view/css/style.css" rel="stylesheet" type="text/css"/>
    </head>

    <body>        
        <nav class="navbar navbar-default navbar-fixed-top">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="<?php echo $global['webSiteRootURL']; ?>" >
                        <?php
                        if(!empty($_SESSION['login']->siteLogo)){
                            ?>
                            <img src="<?php echo $_SESSION['login']->siteLogo; ?>" class="img-responsive ">    
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
                            <li><a href="<?php echo Login::getStreamerURL(); ?>"><span class="glyphicon glyphicon-film"></span> Stream Site</a></li>
                            <li><a href="logoff"><span class="glyphicon glyphicon-log-out"></span> Logoff</a></li>
                            <?php
                        }
                        ?>
                    </ul>
                </div><!--/.nav-collapse -->
            </div>
        </nav>
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
                                    <label class="col-md-4 control-label">YouPHPTube Streamer Site</label>  
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
                                        <button type="submit" class="btn btn-success  btn-block" id="mainButton" ><span class="fa fa-sign-in"></span> Sign in</button>
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
                                        swal("Sorry!", "We could not found your streamer site!", "error");
                                    } else if (!response.isLogged) {
                                        modal.hidePleaseWait();
                                        swal("Sorry!", "Your user or password is wrong!", "error");
                                    } else {
                                        document.location = document.location;
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
    if (!empty($streamerURL) && !empty($_GET['user']) && !empty($_GET['pass'])) {
        echo '$(\'#loginForm\').submit()';
    }
    ?>

                    });

                </script>    
                <?php
            } else {
                $json_file = url_get_contents(Login::getStreamerURL() . "plugin/CustomizeAdvanced/advancedCustom.json.php");
                // convert the string to a json object
                $advancedCustom = json_decode($json_file);
                ?>

                <link href="view/bootgrid/jquery.bootgrid.min.css" rel="stylesheet" type="text/css"/>
                <script src="view/bootgrid/jquery.bootgrid.min.js" type="text/javascript"></script>
                <!-- The main CSS file -->
                <link href="view/mini-upload-form/assets/css/style.css" rel="stylesheet" />
                <div class="col-md-8">

                    <ul class="nav nav-tabs">
                        <li <?php if (empty($_POST['updateFile'])) { ?>class="active"<?php } ?>><a data-toggle="tab" href="#encoding"><span class="glyphicon glyphicon-tasks"></span> Encoding Queue</a></li>
                        <li><a data-toggle="tab" href="#log"><span class="glyphicon glyphicon-cog"></span> Queue Log</a></li>

                        <?php
                        if (Login::isAdmin()) {
                            if (empty($global['disableConfigurations'])) {
                                ?>
                                <li><a data-toggle="tab" href="#config"><span class="glyphicon glyphicon-cog"></span> Configurations</a></li>
                                <li <?php if (!empty($_POST['updateFile'])) { ?>class="active"<?php } ?>><a data-toggle="tab" href="#update" ><span class="fa fa-wrench"></span> Update <?php if (!empty($updateFiles)) { ?><label class="label label-danger"><?php echo count($updateFiles); ?></label><?php } ?></a></li>
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
                        if (Login::isAdmin()) {
                            if (empty($global['disableConfigurations'])) {
                                ?>
                                <div id="config" class="tab-pane fade">
                                    <?php
                                    foreach ($frows as $value) {
                                        if (!in_array($value['id'], $ffmpegArray)) {
                                            continue;
                                        }
                                        ?>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon"><?php echo $value['id']; ?> - <?php echo $value['name']; ?></span>
                                            <input type="text" class="form-control formats" placeholder="Code" id="format_<?php echo $value['id']; ?>" value="<?php echo $value['code']; ?>">
                                        </div>    
                                        <?php
                                    }
                                    ?>
                                    <hr>
                                    <div class="form-group">
                                        <label for="allowedStreamers">Allowed Streamers Sites (One per line. Leave blank for public)</label>
                                        <textarea class="form-control" id="allowedStreamers" placeholder="Leave Blank for Public" required="required"><?php echo $config->getAllowedStreamersURL(); ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="defaultPriority">Default Priority</label>
                                        <select class="" id="defaultPriority">
                                            <?php
                                            $priority = $config->getDefaultPriority();
                                            for ($index = 1; $index <= 10; $index++) {
                                                echo '<option value="' . $index . '" ' . ($priority == $index ? "selected" : "") . ' >' . $index . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="defaultPriority">Auto remove</label>
                                        <input type="checkbox" class="" id="autodelete" value="1" <?php if (!empty($ad)) { ?>checked="true"<?php } ?>>
                                        <small>Will remove queue and the files when the encoder process is done</small>
                                    </div>

                                    <button class="btn btn-success btn-block" id="saveConfig"> Save </button>
                                </div>
                                <div id="update" class="tab-pane fade <?php if (!empty($_POST['updateFile'])) { ?>in active<?php } ?>">
                                    <?php
                                    include '../update/update.php';
                                    ?>
                                </div>
                                <?php
                            }
                        }
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
                <div class="col-md-4" >
                    <div class="panel panel-default">
                        <div class="panel-heading">Submit Files</div>
                        <div class="panel-body">

                            <ul class="nav nav-tabs">
                                <li class="active"><a data-toggle="tab" href="#upload"><span class="glyphicon glyphicon-upload"></span> Upload</a></li>
                                <li ><a data-toggle="tab" href="#download"><span class="glyphicon glyphicon-download"></span> Download</a></li>
                                <?php
                                if (empty($global['disableBulkEncode'])) {
                                    ?>
                                    <li><a data-toggle="tab" href="#bulk"><span class="glyphicon glyphicon-duplicate"></span> Bulk Encode</a></li>
                                <?php } ?>
                            </ul>

                            <div class="tab-content">
                                <div id="upload" class="tab-pane fade in active">
                                    <form id="upload" method="post" action="<?= $global['webSiteRootURL'] ?>upload" enctype="multipart/form-data">

                                        <div class="form-group">
                                            <input type="text" class="form-control" id="title" name="title" placeholder="Title">
                                        </div>
                                        <div class="form-group">
                                            <textarea class="form-control" id="description" name="description" placeholder="Description"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <select class="form-control" id="categories_id" name="categories_id">

                                                <option value="0">Category - Use site default</option>
                                                <?php
                                                foreach ($_SESSION['login']->categories as $key => $value) {
                                                    echo '<option value="' . $value->id . '">' . $value->name . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div> 

                                        <hr>
                                        <div id="drop">
                                            Drop Your Files Here

                                            <a>Browse</a>
                                            <input type="file" name="upl" multiple />
                                        </div>

                                        <ul>
                                            <!-- The file uploads will be shown here -->
                                        </ul>

                                    </form>
                                </div>
                                <div id="download" class="tab-pane fade">
                                    <div class="alert alert-info">
                                        <span class="glyphicon glyphicon-info-sign"></span> Download videos from YouTube.com and a few <a href="https://rg3.github.io/youtube-dl/supportedsites.html" target="_blank">more sites</a>.
                                    </div>
                                    <form id="downloadForm" onsubmit="">
                                        <div class="form-group">
                                            <div class="input-group">
                                                <input type="url" class="form-control" id="inputVideoURL" placeholder="http://...">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-secondary" type="submit">
                                                        <span class="glyphicon glyphicon-download"></span> Download
                                                    </button>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <select class="form-control" id="download_categories_id" name="download_categories_id">

                                                <option value="0">Category - Use site default</option>
                                                <?php
                                                foreach ($_SESSION['login']->categories as $key => $value) {
                                                    echo '<option value="' . $value->id . '">' . $value->name . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div> 
                                    </form>
                                </div>

                                <?php
                                if (empty($global['disableBulkEncode'])) {
                                    ?>

                                    <div id="bulk" class="tab-pane fade">
                                        <div class="alert alert-info">
                                            <span class="glyphicon glyphicon-info-sign pull-left" style="font-size: 2em; padding: 0 10px;"></span> Bulk add your server local files on queue.
                                        </div>

                                        <div class="form-group">
                                            <div class="input-group">
                                                <input type="text" id="path"  class="form-control" placeholder="Local Path of videos i.e. /media/videos"/>
                                                <span class="input-group-btn">
                                                    <button class="btn btn-secondary" id="pathBtn">
                                                        <span class="glyphicon glyphicon-list"></span> List Files
                                                    </button>
                                                </span>
                                                <span class="input-group-btn">
                                                    <button class="btn btn-secondary" id="checkBtn">
                                                        <i class="fa fa-check-square-o" aria-hidden="true"></i>
                                                    </button>
                                                </span>
                                                <span class="input-group-btn">
                                                    <button class="btn btn-secondary" id="uncheckBtn">
                                                        <i class="fa fa-square-o" aria-hidden="true"></i>
                                                    </button>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <select class="form-control" id="bulk_categories_id" name="bulk_categories_id">

                                                <option value="0">Category - Use site default</option>
                                                <?php
                                                foreach ($_SESSION['login']->categories as $key => $value) {
                                                    echo '<option value="' . $value->id . '">' . $value->name . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div> 
                                        <ul class="list-group" id="files">
                                        </ul>
                                        <button class="btn btn-block btn-primary" id="addQueueBtn">Add on Queue</button>
                                    </div>
                                <?php } ?>
                            </div> 
                        </div>
                    </div>

                    <div class="panel panel-default">
                        <div class="panel-heading">Resolutions</div>
                        <div class="panel-body">
                            <?php
                            if (empty($advancedCustom->doNotShowEncoderResolutionLow)) {
                                ?> 
                                <label style="" id="">
                                    <input type="checkbox" id="inputLow" checked="checked"> Low
                                </label>
                                <?php
                            }
                            if (empty($advancedCustom->doNotShowEncoderResolutionSD)) {
                                ?> 
                                <label id="">
                                    <input type="checkbox" id="inputSD" checked="checked"> SD
                                </label>
                                <?php
                            }
                            if (empty($advancedCustom->doNotShowEncoderResolutionHD)) {
                                ?> 
                                <label>
                                    <input type="checkbox" id="inputHD" checked="checked"> HD
                                </label>
                                <?php
                            }
                            ?> 
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading">Advanced</div>
                        <div class="panel-body">
                            <label>
                                <input type="checkbox" id="inputAudioOnly">
                                <span class="glyphicon glyphicon-headphones"></span> Extract Audio
                            </label><br>
                            <label style="display: none;" id="spectrum">
                                <input type="checkbox" id="inputAudioSpectrum">
                                <span class="glyphicon glyphicon-equalizer"></span> Create Video Spectrum
                            </label>
                            <label  id="webm">
                                <input type="checkbox" id="inputWebM">
                                <i class="fa fa-chrome" aria-hidden="true"></i> Extract WebM Video
                                <small class="label label-warning">
                                    For Chrome Browsers
                                </small>
                            </label>
                        </div>
                    </div>

                    <div class="alert alert-success">
                        <span class="glyphicon glyphicon-send"></span>  
                        All converted files will be submited to the streamer site 
                        <strong><?php echo Login::getStreamerURL(); ?></strong><br>
                        <span class="label label-danger">The encoder Max File Size is: <strong><?php echo get_max_file_size(); ?></strong></span>
                        <span class="label label-danger">The Streamer Max File Size is: <strong id="max_file_size">Loading ...</strong></span>
                        <span class="label label-danger">The Streamer Max Video Storage Limit is: <strong id="videoStorageLimitMinutes">Loading ...</strong></span>
                        <span class="label label-danger">The Streamer Current Video Storage is: <strong id="currentStorageUsage">Loading ...</strong></span>
                    </div>
                </div>

                <!-- JavaScript Includes -->
                <script src="view/mini-upload-form/assets/js/jquery.knob.js"></script>

                <!-- jQuery File Upload Dependencies -->
                <script src="view/mini-upload-form/assets/js/jquery.ui.widget.js"></script>
                <script src="view/mini-upload-form/assets/js/jquery.iframe-transport.js"></script>
                <script src="view/mini-upload-form/assets/js/jquery.fileupload.js"></script>

                <!-- Our main JS file -->
                <script src="view/mini-upload-form/assets/js/script.js"></script>
                <script>
                    var encodingNowId = "";
                    function checkFiles() {
                        var path = $('#path').val();
                        if (!path) {
                            return false;
                        }
                        $.ajax({
                            url: 'listFiles.json',
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
                    function checkProgress() {
                        $.ajax({
                            url: 'status',
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
                        item += '<div class="progress progress-striped active " id="encodingProgress' + queueItem.id + '" style="margin: 0;">';
                        item += '<div class="progress-bar  progress-bar-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0;">';
                        item += '<span class="sr-only">0% Complete</span></div><span class="progress-type"><span class="badge ">Priority ' + queueItem.streamer_priority + '</span> ' + queueItem.title + '</span><span class="progress-completed">' + queueItem.name + '</span>';
                        item += '</div><div class="progress progress-striped active " id="downloadProgress' + queueItem.id + '" style="height: 10px;"><div class="progress-bar  progress-bar-danger" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0;"></div></div> ';
                        item += '</div>';
                        if (typeof queueItemAfter === 'undefined' || !$("#" + queueItemAfter.id).length) {
                            $("#encoding").append(item);
                        } else {
                            $(item).insertAfter("#" + queueItemAfter.id);
                        }
                    }

                    var streamerMaxFileSize = 0;
                    function updateFileSizes() {
                        if (!streamerMaxFileSize) {
                            return false;
                        }
                        $('.fileSize').each(function (i, obj) {
                            var fileSize = $(obj).attr("value");
                            if (fileSize > streamerMaxFileSize) {
                                $(obj).removeClass("label-success");
                                $(obj).addClass("label-danger");
                                $(obj).text($(obj).text() + " [File is too big]");
                            }
                        });
                    }

                    $(document).ready(function () {
                        checkProgress();
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
                                updateFileSizes();
                            }
                        });

                        $("#addQueueBtn").click(function () {
                            $('#files li').each(function () {
                                if ($(this).find('.someSwitchOption').is(":checked")) {
                                    var id = $(this).attr('id');
                                    $.ajax({
                                        url: 'queue',
                                        data: {
                                            "fileURI": $(this).attr('path'),
                                            "audioOnly": $('#inputAudioOnly').is(":checked"),
                                            "spectrum": $('#inputAudioSpectrum').is(":checked"),
                                            "webm": $('#inputWebM').is(":checked"),
                                            "inputLow": $('#inputLow').is(":checked"),
                                            "inputSD": $('#inputSD').is(":checked"),
                                            "inputHD": $('#inputHD').is(":checked"),
                                            "categories_id": $('#bulk_categories_id').val()
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
                                url: 'saveConfig',
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
                            modal.showPleaseWait();
                            evt.preventDefault();
                            $.ajax({
                                url: 'youtubeDl.json',
                                data: {
                                    "videoURL": $('#inputVideoURL').val(),
                                    "audioOnly": $('#inputAudioOnly').is(":checked"),
                                    "spectrum": $('#inputAudioSpectrum').is(":checked"),
                                    "webm": $('#inputWebM').is(":checked"),
                                    "inputLow": $('#inputLow').is(":checked"),
                                    "inputSD": $('#inputSD').is(":checked"),
                                    "inputHD": $('#inputHD').is(":checked"),
                                    "categories_id": $('#download_categories_id').val()
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
                            url: "queue.json",
                            formatters: {
                                "commands": function (column, row) {
                                    var reQueue = '';
                                    var deleteQueue = '';
                                    var sendFileQueue = '';

                                    if (row.status != 'queue' && row.status != 'encoding') {
                                        reQueue = '<button type="button" class="btn btn-xs btn-default command-reQueue" data-toggle="tooltip" data-placement="left" title="Re-Queue"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span></button>'
                                    }
                                    deleteQueue = '<button type="button" class="btn btn-xs btn-default command-deleteQueue" data-toggle="tooltip" data-placement="left" title="Delete Queue"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button>'
                                    if (row.status === 'done') {
                                        sendFileQueue = '<button type="button" class="btn btn-xs btn-default command-sendFileQueue" data-toggle="tooltip" data-placement="left" title="Send Notify"><span class="glyphicon glyphicon-send" aria-hidden="true"></span></button>'
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
                                    if (row.mp4_filesize_Low) {
                                        title += '<br><span class="label label-success fileSize" value="' + row.mp4_filesize_Low + '">MP4 Low Size: ' + row.mp4_filesize_human_Low + '</span>';
                                    }
                                    if (row.mp4_filesize_SD) {
                                        title += '<br><span class="label label-success fileSize" value="' + row.mp4_filesize_SD + '">MP4 SD Size: ' + row.mp4_filesize_human_SD + '</span>';
                                    }
                                    if (row.mp4_filesize_HD) {
                                        title += '<br><span class="label label-success fileSize" value="' + row.mp4_filesize_HD + '">MP4 HD Size: ' + row.mp4_filesize_human_HD + '</span>';
                                    }
                                    if (row.webm_filesize_Low) {
                                        title += '<br><span class="label label-success fileSize" value="' + row.webm_filesize_Low + '">WEBM Low Size: ' + row.webm_filesize_human_Low + '</span>';
                                    }
                                    if (row.webm_filesize_SD) {
                                        title += '<br><span class="label label-success fileSize" value="' + row.webm_filesize_SD + '">WEBM SD Size: ' + row.webm_filesize_human_SD + '</span>';
                                    }
                                    if (row.webm_filesize_HD) {
                                        title += '<br><span class="label label-success fileSize" value="' + row.webm_filesize_HD + '">WEBM HD Size: ' + row.webm_filesize_human_HD + '</span>';
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
                                    url: 'queue',
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
                                    url: 'deleteQueue',
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
                                    url: 'send.json',
                                    data: {"id": row.id},
                                    type: 'post',
                                    success: function (response) {
                                        $("#grid").bootgrid("reload");
                                        modal.hidePleaseWait();
                                    }
                                });
                            });
                            $('[data-toggle="popover"]').popover();
                            updateFileSizes();
                        });



                        var gridStreamer = $("#gridStreamer").bootgrid({
                            ajax: true,
                            url: "streamers.json",
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
                                    var deleteBtn = '<button type="button" class="btn btn-xs btn-default command-delete" data-toggle="tooltip" data-placement="left" title="Delete Queue"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button>';

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
                                    url: 'removeStreamer',
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
                                    url: 'priority',
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
                                    url: 'isAdmin',
                                    data: {"id": $(this).attr('rowId'), "isAdmin": $(this).val()},
                                    type: 'post',
                                    success: function (response) {
                                        modal.hidePleaseWait();
                                    }
                                });
                            });
                        });
                    }
                    );

                </script>
                <?php
            }
            ?>

        </div>

    </body>
</html>
