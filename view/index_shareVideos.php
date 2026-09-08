
<div class="panel panel-default encoder-upload-panel">
    <div class="panel-heading">
        <?php
        if (!empty($advancedCustom->showOnlyEncoderAutomaticResolutions)) {
            include './index_formats.php';
        }
        ?>
    </div>
    <div class="panel-body <?php echo getCSSAnimationClassAndStyle('animate__bounceInLeft'); ?>">
        <ul class="nav nav-tabs nav-tabs-icons" role="tablist">
            <li class="active" role="presentation">
                <a data-toggle="tab" href="#upload" role="tab" aria-controls="upload">
                    <i class="fas fa-file-upload" aria-hidden="true"></i>
                    <span class="encoder-tab-label"><?php echo __('From File'); ?></span>
                </a>
            </li>
            <?php
            if (empty($global['disableImportVideo'])) {
                ?>
                <li role="presentation">
                    <a data-toggle="tab" href="#download" role="tab" aria-controls="download">
                        <i class="fas fa-cloud-download-alt" aria-hidden="true"></i>
                        <span class="encoder-tab-label"><?php echo __('Import Video'); ?></span>
                    </a>
                </li>
                <?php
            }
            if (Login::canBulkEncode()) {
                ?>
                <li role="presentation">
                    <a data-toggle="tab" href="#bulk" role="tab" aria-controls="bulk">
                        <i class="fas fa-layer-group" aria-hidden="true"></i>
                        <span class="encoder-tab-label"><?php echo __('Bulk Encode'); ?></span>
                    </a>
                </li>
            <?php } ?>
        </ul>
        <div class="tab-content" style="padding: 10px 0;">
            <div id="upload" class="tab-pane fade in active">
                <?php
                include '../view/jquery-file-upload/form.php';
                ?>
            </div>

            <?php
            if (empty($global['disableImportVideo'])) {
                ?>
                <div id="download" class="tab-pane fade">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle" aria-hidden="true"></i> <?php echo __('Share videos from YouTube and a few'); ?> <a href="https://rg3.github.io/youtube-dl/supportedsites.html" target="_blank"><?php echo __('more sites'); ?></a>.
                    </div>
                    <form id="downloadForm" onsubmit="">
                        <div class="form-group">
                            <div class="input-group">
                                <input type="url" class="form-control" id="inputVideoURL" placeholder="http://..." />
                                <span class="input-group-btn">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-check" aria-hidden="true"></i> <?php echo __('Share'); ?>
                                    </button>
                                </span>
                            </div>
                        </div>
                        <?php
                        $releaseDateId = 'download_releaseDate';
                        include $global['systemRootPath'] . 'view/releaseDate.php';
                        ?>
                        <div class="clearfix"></div>
                        <?php
                        if (!empty($_SESSION['login']->categories)) {
                            ?>
                            <div class="form-group">
                                <div style="display: flex;">
                                    <?php
                                    echo getCategoriesSelect('download_categories_id');
                                    ?>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </form>
                </div>

                <?php
            }
            if (Login::canBulkEncode()) {
                ?>

                <div id="bulk" class="tab-pane fade">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle" aria-hidden="true"></i> <?php echo __('Bulk add your server local files on queue.'); ?>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <input type="text" id="path"  class="form-control" placeholder="<?php echo __('Local Path of videos i.e. /media/videos'); ?>" />
                            <span class="input-group-btn">
                                <button class="btn btn-primary" id="pathBtn">
                                    <i class="fas fa-list" aria-hidden="true"></i> <?php echo __('List Files'); ?>
                                </button>
                            </span>
                            <span class="input-group-btn">
                                <button class="btn btn-primary" id="checkBtn">
                                    <i class="fas fa-check-square" aria-hidden="true"></i>
                                </button>
                            </span>
                            <span class="input-group-btn">
                                <button class="btn btn-primary" id="uncheckBtn">
                                    <i class="far fa-square" aria-hidden="true"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                    <?php
                    $releaseDateId = 'bulk_releaseDate';
                    include $global['systemRootPath'] . 'view/releaseDate.php';
                    ?>
                    <div class="clearfix"></div>
                    <?php
                    if (!empty($_SESSION['login']->categories)) {
                        ?>
                        <div class="form-group">
                            <div style="display: flex;">
                                <?php
                                echo getCategoriesSelect('bulk_categories_id');
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                    <ul class="list-group" id="files">
                    </ul>
                    <button class="btn btn-block btn-primary" id="addQueueBtn"><?php echo __('Add on Queue'); ?></button>
                </div>
            <?php } ?>
        </div>
    </div>
    <div class="panel-footer">
        <div class="available-resolutions">
            <div class="available-resolutions-title">
                <i class="fas fa-tv" aria-hidden="true"></i>
                <span><?php echo __('Resolutions'); ?></span>
            </div>
            <div class="availableResolutionsLabels">
                <?php
                // Show resolutions that will actually be encoded for this user
                $resolutionsInfo = Format::getAvailableResolutionsInfoForUser(Login::getStreamerId());

                if (!empty($resolutionsInfo)) {
                    foreach ($resolutionsInfo as $value) {
                        if (empty($value['resolutionChecked'])) {
                            continue;
                        }
                        echo $value['label'];
                    }
                } else {
                    echo '<small class="text-warning">' . __('No resolutions available for encoding') . '</small>';
                }
                ?>
            </div>
        </div>
        <?php
        if (!empty($_REQUEST['callback'])) {
            $json = json_decode($_REQUEST['callback']);
            if (!empty($json)) {
                foreach ($json as $key => $value) {
                    echo '<strong>' . htmlentities($key) . '</strong>: ' . $value . '<br>';
                }
                echo '<input type="hidden" class="callback" name="callback" id="callback" value="' . base64_encode(json_encode($json)) . '">';
            }
        }
        ?>
    </div>
</div>
