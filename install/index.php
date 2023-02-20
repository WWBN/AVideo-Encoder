<?php
require_once '../objects/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Install AVideo</title>
        <link rel="icon" href="../view/img/favicon.png">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js" integrity="sha512-AA1Bzp5Q0K1KanKKmvN/4d3IRKVlv9PYgwFPvm32nPO6QS8yH1HO7LbgB1pgiOxPtfeg5zEn2ba64MUcqJx6CA==" crossorigin="anonymous"></script>
    </head>

    <body>
        <?php
        if (file_exists('../videos/configuration.php')) {
            require_once '../videos/configuration.php';
            ?>
            <div class="container">
                <h3 class="alert alert-success">
                    <span class="glyphicon glyphicon-ok-circle"></span> 
                    Your system is installed, remove the <code><?php echo $global['systemRootPath']; ?>install</code> directory to continue
                    <hr>
                    <a href="<?php echo $global['webSiteRootURL']; ?>" class="btn btn-success btn-lg center-block">Go to the main page</a>
                </h3>
            </div>
            <?php
        } else {
            ?>
            <div class="container">
                <img src="../view/img/logo.png" alt="Logo" class="img img-responsive center-block"/>
                <div class="row">
                    <div class="col-md-6 ">

                        <?php
                        if (isApache()) {
                            ?>
                            <div class="alert alert-success">
                                <span class="glyphicon glyphicon-check"></span>
                                <strong><?php echo $_SERVER['SERVER_SOFTWARE']; ?> is Present</strong>
                            </div>                  
                            <?php
                        } else {
                            ?>
                            <div class="alert alert-danger">
                                <span class="glyphicon glyphicon-unchecked"></span>
                                <strong>Your server is <?php echo $_SERVER['SERVER_SOFTWARE']; ?>, you must install Apache</strong>
                            </div>                  
                            <?php
                        }
                        ?>


                        <?php
                        if (isPHP("5.6")) {
                            ?>
                            <div class="alert alert-success">
                                <span class="glyphicon glyphicon-check"></span>
                                <strong>PHP <?php echo PHP_VERSION; ?> is Present</strong>
                            </div>                  
                            <?php
                        } else {
                            ?>
                            <div class="alert alert-danger">
                                <span class="glyphicon glyphicon-unchecked"></span>
                                <strong>Your PHP version is <?php echo PHP_VERSION; ?>, you must install PHP 5.6.x or greater</strong>
                            </div>                  
                            <?php
                        }
                        ?>

                        <?php
                        if ($exifTool = isExifToo()) {
                            ?>
                            <div class="alert alert-success">
                                <span class="glyphicon glyphicon-check"></span>
                                <strong>Exiftool [<?php echo $exifTool; ?>] is Present</strong>
                            </div>                  
                            <?php
                        } else {
                            ?>
                            <div class="alert alert-danger">
                                <span class="glyphicon glyphicon-unchecked"></span>
                                <strong>Since AVideo 2.1 we use exiftool to determine if an video is landscape or portrait</strong>
                                <details>
                                    In order to install exiftool type the following command in the terminal:<br>
                                    <pre><code>sudo apt install libimage-exiftool-perl</code></pre>
                                </details>
                            </div>                  
                            <?php
                        }
                        ?>

                        <?php
                        if ($ffmpeg = isFFMPEG()) {
                            ?>
                            <div class="alert alert-success">
                                <span class="glyphicon glyphicon-check"></span>
                                <strong>FFMPEG <?php echo $ffmpeg; ?> is Present</strong>
                                <strong>Make sure your FFMPEG is 3.x or greater</strong>
                            </div>                  
                            <?php
                        } else {
                            ?>
                            <div class="alert alert-danger">
                                <span class="glyphicon glyphicon-unchecked"></span>
                                <strong>FFmpeg is not enabled, make sure your FFMPEG is 3.x or greater</strong>
                                <details>
                                    FFmpeg has been removed from Ubuntu 14.04 and was replaced by Libav. This decision has been reversed so that FFmpeg is available now in Ubuntu 15.04 again, but there is still no official package for 14.04. In this tutorial, I will show you how to install FFmpeg from mc3man ppa. Add the mc3man ppa:
                                    <br>
                                    If you are not using Ubuntu 14.x go to step 2 
                                    <h2>Step 1</h2>
                                    <pre><code>sudo add-apt-repository ppa:mc3man/trusty-media</code></pre>
                                    <br>
                                    And confirm the following message by pressing &lt;enter&gt;:
                                    <br>
                                    <code>
                                        Also note that with apt-get a sudo apt-get dist-upgrade is needed for initial setup & with some package upgrades
                                        More info: https://launchpad.net/~mc3man/+archive/ubuntu/trusty-media
                                        Press [ENTER] to continue or ctrl-c to cancel adding it
                                    </code>
                                    <br>
                                    Update the package list.
                                    <br>
                                    <pre><code>
                                                        sudo apt-get update
                                                        sudo apt-get dist-upgrade
                                                                                            </code></pre>
                                    <br>
                                    Now FFmpeg is available to be installed with apt:
                                    <br>
                                    <h2>Step 2</h2>
                                    <pre><code>sudo apt-get install ffmpeg</code></pre>

                                </details>
                            </div>                  
                            <?php
                        }
                        ?>


                        <?php
                        if ($youtube_dl = isYoutubeDL()) {
                            ?>
                            <div class="alert alert-success">
                                <span class="glyphicon glyphicon-check"></span>
                                <strong>youtube-dl <?php echo $youtube_dl; ?> is Present</strong>
                            </div>                  
                            <?php
                        } else {
                            ?>
                            <div class="alert alert-danger">
                                <span class="glyphicon glyphicon-unchecked"></span>
                                <strong>youtube-dl is not enabled</strong>
                                <details>
                                    <br>
                                    Update the package list.
                                    <br>
                                    <pre><code>
                                                        sudo apt-get update
                                                        sudo apt-get dist-upgrade
                                    </code></pre>
                                    <br>
                                    Install pip:
                                    <br>
                                    <code>
                                        sudo apt-get install python-pip
                                    </code>
                                    <br>
                                    Use pip to install youtube-dl:
                                    <br>
                                    <pre><code>sudo pip install youtube-dl</code></pre>
                                    <br>
                                    Make sure you have the latest version:
                                    <br>
                                    <pre><code>sudo pip install --upgrade youtube-dl</code></pre>
                                    <br>
                                    Add this line in you crontab to make sure you will always have the latest youtube-dl:
                                    <br>
                                    <pre><code>0 1 * * * sudo pip install --upgrade youtube-dl</code></pre>

                                </details>
                            </div>                  
                            <?php
                        }
                        ?>


                        <?php
                        if (checkVideosDir()) {
                            ?>
                            <div class="alert alert-success">
                                <span class="glyphicon glyphicon-check"></span>
                                <strong>Your videos directory is writable</strong>
                            </div>                  
                            <?php
                        } else {
                            ?>
                            <div class="alert alert-danger">
                                <span class="glyphicon glyphicon-unchecked"></span>
                                <strong>Your videos directory must be writable</strong>
                                <details>
                                    <?php
                                    $dir = getPathToApplication() . "videos";
                                    if (!file_exists($dir)) {
                                        ?>
                                        The video directory does not exist, AVideo had no permission to create it, you must create it manually!
                                        <br>
                                        <pre><code>sudo mkdir <?php echo $dir; ?></code></pre>
                                        <?php
                                    }
                                    ?>
                                    <br>
                                    Then you can set the permissions (www-data means apache user).
                                    <br>
                                    <pre><code>sudo chown www-data:www-data <?php echo $dir; ?> && sudo chmod 755 <?php echo $dir; ?> </code></pre>
                                </details>
                            </div>                  
                            <?php
                        }
                        $pathToPHPini = php_ini_loaded_file();
                        if (empty($pathToPHPini)) {
                            $pathToPHPini = "/etc/php/7.0/cli/php.ini";
                        }
                        ?>


                        <?php
                        if (check_max_execution_time()) {
                            ?>
                            <div class="alert alert-success">
                                <span class="glyphicon glyphicon-check"></span>
                                <strong>Your max_execution_time is <?php echo ini_get('max_execution_time'); ?></strong>
                            </div>                  
                            <?php
                        } else {
                            ?>
                            <div class="alert alert-danger">
                                <span class="glyphicon glyphicon-unchecked"></span>
                                <strong>Your max_execution_time is <?php echo ini_get('max_execution_time'); ?>, it must be at least 7200</strong>

                                <details>
                                    Edit the <code>php.ini</code> file 
                                    <br>
                                    <pre><code>sudo nano <?php echo $pathToPHPini; ?></code></pre>
                                </details>
                            </div>                  
                            <?php
                        }
                        ?>

                        <?php
                        if (check_post_max_size()) {
                            ?>
                            <div class="alert alert-success">
                                <span class="glyphicon glyphicon-check"></span>
                                <strong>Your post_max_size is <?php echo ini_get('post_max_size'); ?></strong>
                            </div>                  
                            <?php
                        } else {
                            ?>
                            <div class="alert alert-danger">
                                <span class="glyphicon glyphicon-unchecked"></span>
                                <strong>Your post_max_size is <?php echo ini_get('post_max_size'); ?>, it must be at least 1000M</strong>

                                <details>
                                    Edit the <code>php.ini</code> file 
                                    <br>
                                    <pre><code>sudo nano <?php echo $pathToPHPini; ?></code></pre>
                                </details>
                            </div>                  
                            <?php
                        }
                        ?>

                        <?php
                        if (check_upload_max_filesize()) {
                            ?>
                            <div class="alert alert-success">
                                <span class="glyphicon glyphicon-check"></span>
                                <strong>Your upload_max_filesize is <?php echo ini_get('upload_max_filesize'); ?></strong>
                            </div>                  
                            <?php
                        } else {
                            ?>
                            <div class="alert alert-danger">
                                <span class="glyphicon glyphicon-unchecked"></span>
                                <strong>Your upload_max_filesize is <?php echo ini_get('upload_max_filesize'); ?>, it must be at least 1000M</strong>

                                <details>
                                    Edit the <code>php.ini</code> file 
                                    <br>
                                    <pre><code>sudo nano <?php echo $pathToPHPini; ?></code></pre>
                                </details>
                            </div>                   
                            <?php
                        }
                        ?>

                        <?php
                        if (check_memory_limit()) {
                            ?>
                            <div class="alert alert-success">
                                <span class="glyphicon glyphicon-check"></span>
                                <strong>Your memory_limit is <?php echo ini_get('memory_limit'); ?></strong>
                            </div>                  
                            <?php
                        } else {
                            ?>
                            <div class="alert alert-danger">
                                <span class="glyphicon glyphicon-unchecked"></span>
                                <strong>Your memory_limit is <?php echo ini_get('memory_limit'); ?>, it must be at least 512M</strong>

                                <details>
                                    Edit the <code>php.ini</code> file 
                                    <br>
                                    <pre><code>sudo nano <?php echo $pathToPHPini; ?></code></pre>
                                </details>
                            </div>                   
                            <?php
                        }
                        ?>
                    </div>
                    <div class="col-md-6 ">
                        <form id="configurationForm">
                            <div class="form-group col-md-6 ">
                                <label for="webSiteRootURL">Your Site URL</label>
                                <input type="url" class="form-control" id="webSiteRootURL" placeholder="Enter your URL (http://yoursite.com)" value="<?php echo getURLToApplication(); ?>" required="required">
                            </div>
                            <div class="form-group col-md-6 ">
                                <label for="systemRootPath">System Path to Application</label>
                                <input type="text" class="form-control" id="systemRootPath" placeholder="System Path to Application (/var/www/[application_path])" value="<?php echo getPathToApplication(); ?>" required="required">
                            </div>
                            <div class="form-group col-md-6 ">
                                <label for="databaseHost">Database Host</label>
                                <input type="text" class="form-control" id="databaseHost" placeholder="Enter Database Host" value="localhost" required="required">
                            </div>
                            <div class="form-group col-md-6 ">
                                <label for="databaseUser">Database User</label>
                                <input type="text" class="form-control" id="databaseUser" placeholder="Enter Database User" value="root" required="required">
                            </div>
                            <div class="form-group col-md-6 ">
                                <label for="databasePass">Database Password</label>
                                <input type="password" class="form-control" id="databasePass" placeholder="Enter Database Password">
                            </div>
                            <div class="form-group col-md-6 ">
                                <label for="databaseName">Database Name</label>
                                <input type="text" class="form-control" id="databaseName" placeholder="Enter Database Name" value="aVideo_Encoder" required="required">
                            </div>
                            <div class="form-group col-md-6 ">
                                <label for="databaseName">Optional Tables Prefix</label>
                                <input type="text" class="form-control" id="tablesPrefix" placeholder="Enter Tables Prefix" value="">
                            </div>
                            <div class="form-group col-md-6 ">
                                <label for="createTables">Create database and tables?</label>

                                <select id="createTables"  class="form-control">
                                    <option value="2">Create database and tables</option>
                                    <option value="1">Create only tables (Do not create database)</option>
                                    <option value="0">Do not create any, I will import the script manually</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="allowedStreamers">
                                    Allowed AVideo Streamers Sites (One per line. Leave blank for public) 
                                    <button class="btn btn-xs btn-primary" data-toggle="popover"  type="button"
                                       title="What is this?" 
                                       data-content="Only the listed sites will be allowed to use this encoder installation">
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </button>
                                </label>
                                <textarea class="form-control" id="allowedStreamers" placeholder="Leave Blank for Public" value=""></textarea>
                            </div>

                            <div class="form-group">
                                <label for="defaultPriority">Default Priority
                                    <button class="btn btn-xs btn-primary" data-toggle="popover" type="button"
                                       title="What is this?" 
                                       data-content="When a user send an media, what will be the priority?">
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </button>
                                </label>
                                <select class="" id="defaultPriority">
                                    <?php
                                    for ($index = 1; $index <= 10; $index++) {
                                        echo '<option value="' . $index . '">' . $index . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>


                            <div class="alert alert-info" id="streamer" >

                                <div class="form-group">
                                    <label for="siteURL">AVideo Streamer Site URL
                                    <button class="btn btn-xs btn-primary" data-toggle="popover"  type="button"
                                       title="What is this?" 
                                       data-content="If you do not have AVideo Streamer Site yet, download it https://github.com/DanielnetoDotCom/AVideo">
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </button>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="glyphicon glyphicon-globe"></i></span>
                                        <input  id="siteURL" placeholder="http://www.your-tube-site.com" class="form-control"  type="url" value="" required >
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="inputUser">AVideo Streamer Site admin User</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                                        <input  id="inputUser" placeholder="User" class="form-control"  type="text" value="admin" required >
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="siteURL">AVideo Streamer Site admin Password</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                                        <input  id="inputPassword" placeholder="Password" class="form-control"  type="password" value="" >
                                    </div>
                                </div>
                                <div class="alert alert-warning">
                                    If you do not have AVideo Streamer Site yet, download it <a href="https://github.com/DanielnetoDotCom/AVideo" target="_blank">here</a>. Then, please, go back here and finish this installation.
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Install now</button>
                        </form>
                    </div>            
                </div>

            </div>
        <?php } ?>
        <script src="../view/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
        <script src="../view/js/seetalert/sweetalert.min.js" type="text/javascript"></script>
        <script src="../view/js/main.js" type="text/javascript"></script>

        <script>
            $(function () {
                $('#siteURL').keyup(function () {
                    $('#allowedStreamers').val($(this).val());
                });
                $('[data-toggle="popover"]').popover(); 
                $('#configurationForm').submit(function (evt) {
                    evt.preventDefault();

                    modal.showPleaseWait();
                    var webSiteRootURL = $('#webSiteRootURL').val();
                    var systemRootPath = $('#systemRootPath').val();
                    var databaseHost = $('#databaseHost').val();
                    var databaseUser = $('#databaseUser').val();
                    var databasePass = $('#databasePass').val();
                    var databaseName = $('#databaseName').val();
                    var createTables = $('#createTables').val();
                    var allowedStreamers = $('#allowedStreamers').val();
                    var defaultPriority = $('#defaultPriority').val();
                    var tablesPrefix = $('#tablesPrefix').val();

                    var siteURL = $('#siteURL').val();
                    var inputUser = $('#inputUser').val();
                    var inputPassword = $('#inputPassword').val();

                    $.ajax({
                        url: siteURL + '/login',
                        data: {"user": inputUser, "pass": inputPassword, "siteURL": siteURL},
                        type: 'post',
                        success: function (response) {
                            if (!response.isAdmin) {
                                modal.hidePleaseWait();
                                swal("Sorry!", "Your Streamer site, user or password is wrong!", "error");
                                $('#streamer').removeClass('alert-success');
                                $('#streamer').removeClass('alert-info');
                                $('#streamer').addClass('alert-danger');
                            } else {
                                $('#streamer').removeClass('alert-info');
                                $('#streamer').removeClass('alert-danger');
                                $('#streamer').addClass('alert-success');
                                console.log(webSiteRootURL + 'install/checkConfiguration.php');
                                $.ajax({
                                    url: webSiteRootURL + 'install/checkConfiguration.php',
                                    data: {
                                        webSiteRootURL: webSiteRootURL,
                                        systemRootPath: systemRootPath,
                                        databaseHost: databaseHost,
                                        databaseUser: databaseUser,
                                        databasePass: databasePass,
                                        databaseName: databaseName,
                                        createTables: createTables,
                                        siteURL: siteURL,
                                        inputUser: inputUser,
                                        inputPassword: inputPassword,
                                        allowedStreamers: allowedStreamers,
                                        defaultPriority: defaultPriority,
                                        tablesPrefix: tablesPrefix
                                    },
                                    type: 'post',
                                    success: function (response) {
                                        modal.hidePleaseWait();
                                        if (response.error) {
                                            swal("Sorry!", response.error, "error");
                                        } else {
                                            swal("Congratulations!", response.error, "success");
                                            window.location.reload(false);
                                        }
                                    },
                                    error: function (xhr, ajaxOptions, thrownError) {
                                        modal.hidePleaseWait();
                                        if (xhr.status == 404) {
                                            swal("Sorry!", "Your Site URL is wrong!", "error");
                                        } else {
                                            swal("Sorry!", "Unknown error!", "error");
                                        }
                                    }
                                });
                            }
                        }
                    });
                });
            });
        </script>
    </body>
</html>
