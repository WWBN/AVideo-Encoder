<div class="container-fluid">
            <div class="alert alert-success"><?php printf("You are running YouPHPTube Encoder version %s!", $config->getVersion()); ?></div>
            <?php
            if (empty($_POST['updateFile'])) {
                $updateFiles = getUpdatesFiles();
                if (!empty($updateFiles)) {
                    ?>
                    <div class="alert alert-warning">
                        <form method="post" class="form-compact well form-horizontal" >
                            <fieldset>
                                <legend>Update YouPHPTube System</legend>
                                <label for="updateFile" class="sr-only">Select the update</label>
                                <select class="selectpicker" data-width="fit" name="updateFile" id="updateFile" required autofocus>
                                    <?php
                                    foreach ($updateFiles as $value) {
                                        echo "<option value=\"{$value['filename']}\">Version {$value['version']}</option>";
                                    }
                                    ?>
                                </select>
                                <?php printf("We detected a total of %s pending updates, if you want to do it now click (Update Now) button", "<strong class='badge'>" . count($updateFiles) . "</strong>"); ?>
                                <hr>
                                <button type="submit" class="btn btn-warning btn-lg center-block " href="?update=1" > <span class="glyphicon glyphicon-refresh"></span> Update Now </button>
                            </fieldset>
                        </form>
                    </div>

                    <script>
                        $(document).ready(function () {
                            $('#updateFile').selectpicker();
                        });
                    </script>
                    <?php
                } else {
                    ?>
                    <div class="alert alert-success">
                        <h2>Your system is up to date</h2>
                    </div>
                    <?php
                }
            } else {
                $obj = new stdClass();
                $templine = '';
                $lines = file("{$global['systemRootPath']}update/{$_POST['updateFile']}");
                $obj->error = "";
                foreach ($lines as $line) {
                    if (substr($line, 0, 2) == '--' || $line == '')
                        continue;
                    $templine .= $line;
                    if (substr(trim($line), -1, 1) == ';') {
                        if (!$global['mysqli']->query($templine)) {
                            $obj->error = ('Error performing query \'<strong>' . $templine . '\': ' . $global['mysqli']->error . '<br /><br />');
                            echo json_encode($obj);
                            //exit;
                        }
                        $templine = '';
                    }
                }

                //$renamed = rename("{$global['systemRootPath']}updateDb.sql", "{$global['systemRootPath']}updateDb.sql.old");
                ?>
                <div class="alert alert-success">
                    <?php
                    printf("Your update from file %s is done, click continue", $_POST['updateFile']);
                    ?><hr>
                    <a class="btn btn-success" href="?done=1" > <span class="glyphicon glyphicon-ok"></span> Continue </a>
                </div>
                <?php
            }
            ?></div>