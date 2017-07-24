<?php
require_once 'configuration.php';
require_once './objects/Encoder.php';
require_once './objects/Format.php';
require_once './objects/Streamer.php';
require_once './objects/Login.php';
$rows = Encoder::getAllQueue();
$frows = Format::getAll();
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="">
        <meta name="author" content="">

        <title>YouPHPTube Encoder</title>
        <script src="view/js/jquery-3.2.0.min.js" type="text/javascript"></script>
        <link href="view/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
        <script src="view/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
        <link href="view/js/seetalert/sweetalert.css" rel="stylesheet" type="text/css"/>
        <script src="view/js/seetalert/sweetalert.min.js" type="text/javascript"></script>
        <script src="view/js/main.js" type="text/javascript"></script>
        <link href="view/css/style.css" rel="stylesheet" type="text/css"/>
        <style>
            body {
                margin: 30px 0px;
            }
            .progress {
                position: relative;
                height: 25px;
            }
            .progress > .progress-type {
                position: absolute;
                left: 0px;
                font-weight: 800;
                padding: 3px 30px 2px 10px;
                color: rgb(255, 255, 255);
                background-color: rgba(25, 25, 25, 0.2);
            }
            .progress > .progress-completed {
                position: absolute;
                right: 0px;
                font-weight: 800;
                padding: 3px 10px 2px;
            }
        </style>
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
                    <a class="navbar-brand" href="#">Encoder</a>
                </div>
                <div id="navbar" class="navbar-collapse collapse">
                    <ul class="nav navbar-nav navbar-right">
                        <?php
                        if (!Login::isLogged()) {
                            ?>
                            <li><a href="#"><span class="glyphicon glyphicon-log-in"></span> Login</a></li>
                            <?php
                        } else {
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
                    <div class="col-xs-1 col-sm-2 col-lg-4"></div>
                    <div class="col-xs-10 col-sm-8 col-lg-4">
                        <form class="form-compact well form-horizontal"  id="loginForm">
                            <fieldset>
                                <legend>Please sign in</legend>


                                <div class="form-group">
                                    <label class="col-md-4 control-label">YouPHPTube Site</label>  
                                    <div class="col-md-8 inputGroupContainer">
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="glyphicon glyphicon-globe"></i></span>
                                            <input  id="siteURL" placeholder="http://www.your-tube-site.com" class="form-control"  type="url" value="" required >
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-4 control-label">User</label>  
                                    <div class="col-md-8 inputGroupContainer">
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                                            <input  id="inputUser" placeholder="User" class="form-control"  type="text" value="" required >
                                        </div>
                                    </div>
                                </div>


                                <div class="form-group">
                                    <label class="col-md-4 control-label">Password</label>  
                                    <div class="col-md-8 inputGroupContainer">
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                                            <input  id="inputPassword" placeholder="Password" class="form-control"  type="password" value="" >
                                        </div>
                                    </div>
                                </div>
                                <!-- Button -->
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-success  btn-block" id="mainButton" ><span class="fa fa-sign-in"></span> Sign in</button>
                                    </div>
                                </div>
                                </div>
                            </fieldset>

                        </form>

                    </div>
                    <div class="col-xs-1 col-sm-2 col-lg-4"></div>
                </div>
                <script>
                    $(document).ready(function () {
                        $('#loginForm').submit(function (evt) {
                            evt.preventDefault();
                            modal.showPleaseWait();
                            $.ajax({
                                url: 'login',
                                data: {"user": $('#inputUser').val(), "pass": $('#inputPassword').val(), "siteURL": $('#siteURL').val()},
                                type: 'post',
                                success: function (response) {
                                    if (!response.isLogged) {
                                        modal.hidePleaseWait();
                                        swal("Sorry!", "Your user or password is wrong!", "error");
                                    } else {
                                        document.location = document.location;
                                    }
                                }
                            });
                            return false;
                        });
                    });

                </script>    
                <?php
            } else {
                ?>

                <link href="view/bootgrid/jquery.bootgrid.min.css" rel="stylesheet" type="text/css"/>
                <script src="view/bootgrid/jquery.bootgrid.min.js" type="text/javascript"></script>
                <!-- The main CSS file -->
                <link href="view/mini-upload-form/assets/css/style.css" rel="stylesheet" />
                <div class="col-md-6">

                    <ul class="nav nav-tabs">
                        <li class="active"><a data-toggle="tab" href="#encoding"><span class="glyphicon glyphicon-tasks"></span> Encoding Queue</a></li>
                        <li><a data-toggle="tab" href="#log"><span class="glyphicon glyphicon-cog"></span> Queue Log</a></li>
                        <?php
                        if (empty($global['disableConfigurations'])) {
                            ?>
                            <li><a data-toggle="tab" href="#config"><span class="glyphicon glyphicon-cog"></span> Configurations</a></li>
                            <?php
                        }
                        ?>
                    </ul>

                    <div class="tab-content">
                        <div id="encoding" class="tab-pane fade in active">
                        </div>
                        <div id="log" class="tab-pane fade">
                            <table id="grid" class="table table-condensed table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th data-column-id="title" data-formatter="title">Title</th>
                                        <th data-column-id="status" data-formatter="status">Status</th>
                                        <th data-column-id="created" data-formatter="dates">Dates</th>
                                        <th data-column-id="commands" data-formatter="commands" data-sortable="false"  data-width="100px"></th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        <?php
                        if (empty($global['disableConfigurations'])) {
                            ?>
                            <div id="config" class="tab-pane fade">
                                <?php
                                foreach ($frows as $value) {
                                    if ($value['id'] >= 7) {
                                        break;
                                    }
                                    ?>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-addon"><?php echo $value['id']; ?> - <?php echo $value['name']; ?></span>
                                        <input type="text" class="form-control" placeholder="Code" id="format_<?php echo $value['id']; ?>" value="<?php echo $value['code']; ?>">
                                    </div>    
                                    <?php
                                }
                                ?>
                                <button class="btn btn-success btn-block" id="saveConfig"> Save </button>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="col-md-6" >

                    <ul class="nav nav-tabs">
                        <li class="active"><a data-toggle="tab" href="#download"><span class="glyphicon glyphicon-download"></span> Download</a></li>
                        <li><a data-toggle="tab" href="#upload"><span class="glyphicon glyphicon-upload"></span> Upload</a></li>
                        <?php
                        if (empty($global['disableBulkEncode'])) {
                            ?>
                            <li><a data-toggle="tab" href="#bulk"><span class="glyphicon glyphicon-duplicate"></span> Bulk Encode</a></li>
                        <?php } ?>
                        <li class="pull-right">
                            <a>
                                <label>
                                    <input type="checkbox" id="inputAudioOnly">
                                    <span class="glyphicon glyphicon-headphones"></span> Extract Audio
                                </label>
                                <label style="display: none;" id="spectrum">
                                    <input type="checkbox" id="inputAudioSpectrum">
                                    <span class="glyphicon glyphicon-stats"></span> Create Video Spectrum
                                </label>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div id="download" class="tab-pane fade in active">
                            <div class="alert alert-info">
                                <span class="glyphicon glyphicon-info-sign pull-left" style="font-size: 2em; padding: 0 10px;"></span> Download videos from YouTube.com and a few <a href="https://rg3.github.io/youtube-dl/supportedsites.html" target="_blank">more sites</a>.
                            </div>
                            <form id="downloadForm" onsubmit="">
                                <div class="input-group">
                                    <input type="url" class="form-control" id="inputVideoURL" placeholder="http://...">
                                    <span class="input-group-btn">
                                        <button class="btn btn-secondary" type="submit">
                                            <span class="glyphicon glyphicon-download"></span> Download
                                        </button>
                                    </span>
                                </div>
                            </form>
                        </div>

                        <div id="upload" class="tab-pane fade">
                            <form id="upload" method="post" action="<?= $global['webSiteRootURL'] ?>upload.php" enctype="multipart/form-data">
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
                        <?php
                        if (empty($global['disableBulkEncode'])) {
                            ?>

                            <div id="bulk" class="tab-pane fade">
                                <div class="alert alert-info">
                                    <span class="glyphicon glyphicon-info-sign pull-left" style="font-size: 2em; padding: 0 10px;"></span> Bulk add your server local files on queue.
                                </div>
                                <div class="input-group">
                                    <input type="text" id="path"  class="form-control" placeholder="Local Path of videos i.e. /media/videos"/>
                                    <span class="input-group-btn">
                                        <button class="btn btn-secondary" id="pathBtn">
                                            <span class="glyphicon glyphicon-list"></span> List Files
                                        </button>
                                    </span>
                                </div>
                                <ul class="list-group" id="files">
                                </ul>
                                <button class="btn btn-block btn-primary" id="addQueueBtn">Add on Queue</button>
                            </div>
                        <?php } ?>
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
                            url: 'listFiles.json.php',
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
                            url: 'status.php',
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
                                    $("#encodeProgress" + id).find('.progress-completed').html("<strong>" + response.encoding.name + "[" + response.encoding_status.from + " to " + response.encoding_status.to + "] </strong> " + response.encoding_status.progress + '%');
                                    $("#encodeProgress" + id).find('.progress-bar').css({'width': response.encoding_status.progress + '%'});

                                    $("#downloadProgress" + id).slideDown();
                                    if (response.download_status) {
                                        $("#downloadProgress" + id).find('.progress-bar').css({'width': response.download_status.progress + '%'});
                                    }
                                    if (response.encoding_status.progress >= 100) {
                                        $("#encodeProgress" + id).find('.progress-bar').css({'width': '100%'});
                                        setTimeout(function () {
                                            $("#encodeProgress" + id).slideUp("normal", function () {
                                                $(this).remove();
                                            });
                                            $("#downloadProgress" + id).fadeOut("normal", function () {
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
                        item += '<div class="progress progress-striped active " style="margin: 0;">';
                        item += '<div class="progress-bar  progress-bar-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0;">';
                        item += '<span class="sr-only">0% Complete</span></div><span class="progress-type">' + queueItem.title + '</span><span class="progress-completed">' + queueItem.name + '</span>';
                        item += '</div><div class="progress progress-striped active " id="downloadProgress' + queueItem.id + '" style="height: 10px;"><div class="progress-bar  progress-bar-danger" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0;"></div></div> ';
                        item += '</div>';
                        if (typeof queueItemAfter === 'undefined' || !$("#" + queueItemAfter.id).length) {
                            $("#encoding").append(item);
                        } else {
                            $(item).insertAfter("#" + queueItemAfter.id);
                        }
                    }

                    $(document).ready(function () {
                        checkProgress();

                        $("#addQueueBtn").click(function () {
                            $('#files li').each(function () {
                                if ($(this).find('.someSwitchOption').is(":checked")) {
                                    var id = $(this).attr('id');
                                    $.ajax({
                                        url: 'queue.php',
                                        data: {"fileURI": $(this).attr('path'), "audioOnly": $('#inputAudioOnly').is(":checked"), "spectrum": $('#inputAudioSpectrum').is(":checked")},
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

                        $('#saveConfig').click(function () {
                            modal.showPleaseWait();
                            $.ajax({
                                url: 'saveConfig.php',
                                data: {"formats": [[1, $("#format_1").val()], [2, $("#format_2").val()], [3, $("#format_3").val()], [4, $("#format_4").val()], [5, $("#format_5").val()], [6, $("#format_6").val()]]},
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
                                url: 'youtubeDl.json.php',
                                data: {"videoURL": $('#inputVideoURL').val(), "audioOnly": $('#inputAudioOnly').is(":checked"), "spectrum": $('#inputAudioSpectrum').is(":checked")},
                                type: 'post',
                                success: function (response) {
                                    console.log(response);
                                    modal.hidePleaseWait();
                                }
                            });
                            return false;
                        });

                        $('#inputAudioOnly').change(function () {
                            if ($(this).is(":checked")) {
                                $('#spectrum').fadeIn();
                            } else {
                                $('#spectrum').fadeOut();
                            }
                        });

                        var grid = $("#grid").bootgrid({
                            ajax: true,
                            url: "queue.json.php",
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
                                    return "Priority: " + row.priority + " [" + row.format + "]" + "<br>" + row.title;
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
                                    url: 'queue.php',
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
                                    url: 'deleteQueue.php',
                                    data: {"id": row.id},
                                    type: 'post',
                                    success: function (response) {
                                        $("#grid").bootgrid("reload");
                                        modal.hidePleaseWait();
                                    }
                                });
                            });
                            grid.find(".command-sendFileQueue").on("click", function (e) {
                                modal.showPleaseWait();
                                var row_index = $(this).closest('tr').index();
                                var row = $("#grid").bootgrid("getCurrentRows")[row_index];
                                console.log(row);
                                $.ajax({
                                    url: 'send.json.php',
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
                    }
                    );

                </script>
                <?php
            }
            ?>

        </div>

    </body>
</html>
