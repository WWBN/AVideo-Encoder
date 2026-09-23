<div class="container-fluid">
            <?php include __DIR__ . '/software.php'; ?>
            <h3><?php echo __('Database updates'); ?></h3>
            <div class="alert alert-info"><?php printf(__('Database schema version: %s'), htmlspecialchars($config->getVersion(), ENT_QUOTES, 'UTF-8')); ?></div>
            <?php
            if (empty($_POST['updateFile'])) {
                $updateFiles = getUpdatesFiles();
                if (!empty($updateFiles)) {
                    ?>
                    <div class="alert alert-warning">
                        <form method="post" class="form-compact well form-horizontal">
                            <fieldset>
                                <legend><?php echo __('Update AVideo System'); ?></legend>
                                <label for="updateFile" class="sr-only"><?php echo __('Select the update'); ?></label>
                                <select class="selectpicker" data-width="fit" name="updateFile" id="updateFile" required autofocus>
                                    <?php
                                    foreach ($updateFiles as $value) {
                                        echo "<option value=\"{$value['filename']}\">Version {$value['version']}</option>";
                                    }
                                    ?>
                                </select>
                                <?php printf(__('We detected a total of %s pending updates, if you want to do it now click (Update Now) button'), "<strong class='badge'>" . count($updateFiles) . "</strong>"); ?>
                                <hr>
                                <button type="submit" class="btn btn-warning btn-lg center-block" href="?update=1"> <span class="glyphicon glyphicon-refresh"></span> <?php echo __('Update Now'); ?> </button>
                            </fieldset>
                        </form>
                    </div>

                    <script>
                        $(document).ready(function () {
                            //$('#updateFile').selectpicker();
                        });
                    </script>
                    <?php
                } else {
                    ?>
                    <div class="alert alert-success">
                        <p><?php echo __('The database is up to date. Software updates are checked separately above.'); ?></p>
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
                        if(!empty($global['tablesPrefix'])){
                            $templine = addPrefixIntoQuery($templine, $global['tablesPrefix']);
                        }
                        try {
                            if (!$global['mysqli']->query($templine)) {
                                throw new mysqli_sql_exception($global['mysqli']->error, $global['mysqli']->errno);
                            }
                        } catch (mysqli_sql_exception $e) {
                            // Early fresh installs already had retry_count but were recorded as 8.1.
                            $retryCountQuery = 'ALTER TABLE `' . ($global['tablesPrefix'] ?? '') . 'encoder_queue` ADD COLUMN `retry_count` INT NOT NULL DEFAULT 0;';
                            if ($e->getCode() !== 1060 || $_POST['updateFile'] !== 'updateDb.v8.2.sql' || trim($templine) !== $retryCountQuery) {
                                _error_log('Encoder database update failed: ' . $e->getMessage());
                                $obj->error = __('Update failed. Check the server error log and try again.');
                                break;
                            }
                        }
                        $templine = '';
                    }
                }

                ?>
                <?php if (!empty($obj->error)) { ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($obj->error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <?php } else { ?>
                <div class="alert alert-success">
                    <?php
                    printf(__('Your update from file %s is done, click continue'), $_POST['updateFile']);
                    ?><hr>
                    <a class="btn btn-success" href="?done=1"> <span class="glyphicon glyphicon-ok"></span> <?php echo __('Continue'); ?> </a>
                </div>
                <?php } ?>
                <?php
            }
            ?></div>
