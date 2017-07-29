<?php
require_once '../objects/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Install YouPHPTube</title>
        <link href="../view/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
        <link href="../view/js/seetalert/sweetalert.css" rel="stylesheet" type="text/css"/>
        <script src="../view/js/jquery-3.2.0.min.js" type="text/javascript"></script>
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
                        if (modRewriteEnabled()) {
                            ?>
                            <div class="alert alert-success">
                                <span class="glyphicon glyphicon-check"></span>
                                <strong>Mod Rewrite module is Present</strong>
                            </div>                  
                            <?php
                        } else {
                            ?>
                            <div class="alert alert-danger">
                                <span class="glyphicon glyphicon-unchecked"></span>
                                <strong>Mod Rewrite is not enabled</strong>
                                <details>
                                    In order to use mod_rewrite you can type the following command in the terminal:<br>
                                    <pre><code>a2enmod rewrite</code></pre><br>
                                    Restart apache2 after<br>
                                    <pre><code>/etc/init.d/apache2 restart</code></pre>
                                </details>
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
                                <strong>Since YouPHPTube 2.1 we use exiftool to determine if an video is landscape or portrait</strong>
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
                            </div>                  
                            <?php
                        } else {
                            ?>
                            <div class="alert alert-danger">
                                <span class="glyphicon glyphicon-unchecked"></span>
                                <strong>FFmpeg is not enabled</strong>
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
                                        The video directory does not exists, YouPHPTube had no permition to create it, you must create it manualy!
                                        <br>
                                        <pre><code>sudo mkdir <?php echo $dir; ?></code></pre>
                                        <?php
                                    }
                                    ?>
                                    <br>
                                    Then you can set the permissions.
                                    <br>
                                    <pre><code>sudo chmod -R 777 <?php echo $dir; ?></code></pre>
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
                                <strong>Your post_max_size is <?php echo ini_get('post_max_size'); ?>, it must be at least 100M</strong>

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
                                <strong>Your upload_max_filesize is <?php echo ini_get('upload_max_filesize'); ?>, it must be at least 100M</strong>

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
                            <div class="form-group">
                                <label for="webSiteRootURL">Your Site URL</label>
                                <input type="text" class="form-control" id="webSiteRootURL" placeholder="Enter your URL (http://yoursite.com)" value="<?php echo getURLToApplication(); ?>" required="required">
                            </div>
                            <div class="form-group">
                                <label for="systemRootPath">System Path to Application</label>
                                <input type="text" class="form-control" id="systemRootPath" placeholder="System Path to Application (/var/www/[application_path])" value="<?php echo getPathToApplication(); ?>" required="required">
                            </div>
                            <div class="form-group">
                                <label for="databaseHost">Database Host</label>
                                <input type="text" class="form-control" id="databaseHost" placeholder="Enter Database Host" value="localhost" required="required">
                            </div>
                            <div class="form-group">
                                <label for="databaseUser">Database User</label>
                                <input type="text" class="form-control" id="databaseUser" placeholder="Enter Database User" value="root" required="required">
                            </div>
                            <div class="form-group">
                                <label for="databasePass">Database Password</label>
                                <input type="password" class="form-control" id="databasePass" placeholder="Enter Database Password">
                            </div>
                            <div class="form-group">
                                <label for="databaseName">Database Name</label>
                                <input type="text" class="form-control" id="databaseName" placeholder="Enter Database Name" value="youPHPTube-Encoder" required="required">
                            </div>

                            <div class="form-group">
                                <label for="allowedStreamers">Allowed Streamers Sites (One per line. Leave blank for public)</label>
                                <textarea class="form-control" id="allowedStreamers" placeholder="Leave Blank for Public" value=""></textarea>
                            </div>

                            <div class="form-group">
                                <label for="defaultPriority">Default Priority</label>
                                <select class="" id="defaultPriority">
                                    <?php
                                    for ($index = 1; $index <= 10; $index++) {
                                        echo '<option value="' . $index . '">' . $index . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>


                            <div class="form-group">
                                <label for="createTables">Do you want to create database and tables?</label>

                                <select class="" id="createTables">
                                    <option value="2">Create database and tables</option>
                                    <option value="1">Create only tables (Do not create database)</option>
                                    <option value="0">Do not create any, I will import the script manually</option>
                                </select>
                            </div>
                            <div class="alert alert-info" id="streamer" >

                                <div class="form-group">
                                    <label for="siteURL">YouPHPTube URL</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="glyphicon glyphicon-globe"></i></span>
                                        <input  id="siteURL" placeholder="http://www.your-tube-site.com" class="form-control"  type="url" value="<?php echo @$_GET['webSiteRootURL']; ?>" required >
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="inputUser">YouPHPTube admin User</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                                        <input  id="inputUser" placeholder="User" class="form-control"  type="text" value="<?php echo @$_GET['user']; ?>" required >
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="siteURL">YouPHPTube admin Password</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                                        <input  id="inputPassword" placeholder="Password" class="form-control"  type="password" value="" >
                                    </div>
                                </div>
                                <div class="alert alert-warning">
                                    If you do not have YouPHPTube Streamer Site yet, download it <a href="https://github.com/DanielnetoDotCom/YouPHPTube" target="_blank">here</a>. Then, please, go back here and finish this installation.
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit</button>
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
                $('#siteURL').keyUp(function(){
                    $('#allowedStreamers').val($(this).val());
                });
                
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

                    var siteURL = $('#siteURL').val();
                    var inputUser = $('#inputUser').val();
                    var inputPassword = $('#inputPassword').val();

                    $.ajax({
                        url: siteURL + '/login',
                        data: {"user": $('#inputUser').val(), "pass": $('#inputPassword').val(), "siteURL": $('#siteURL').val()},
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
                                        defaultPriority: defaultPriority
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
                                            swal("Sorry!", "Unknow error!", "error");
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
