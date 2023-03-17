
<div class="panel panel-default">
    <div class="panel-heading">
        <?php
        if (!empty($advancedCustom->showOnlyEncoderAutomaticResolutions)) {
            include './index_formats.php';
        }
        ?>
    </div>
    <div class="panel-body <?php echo getCSSAnimationClassAndStyle('animate__bounceInLeft'); ?>">
        <ul class="nav nav-tabs">
            <li class="active">
                <a data-toggle="tab" href="#upload">
                    <center>
                        <i class="fas fa-file" aria-hidden="true"></i><br><?php echo __('From File'); ?>
                    </center>
                </a>
            </li>
            <?php
            if (empty($global['disableImportVideo'])) {
                ?>
                <li><a data-toggle="tab" href="#download"><center><i class="fas fa-globe" aria-hidden="true"></i><br><?php echo __('Import Video'); ?></center></a></li>
                <?php
            }
            if (Login::canBulkEncode()) {
                ?>
                <li><a data-toggle="tab" href="#bulk"><center><span class="glyphicon glyphicon-duplicate"></span><br><?php echo __('Bulk Encode'); ?></center></a></li>
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
                        <span class="glyphicon glyphicon-info-sign"></span> <?php echo __('Share videos from YouTube and a few'); ?> <a href="https://rg3.github.io/youtube-dl/supportedsites.html" target="_blank"><?php echo __('more sites'); ?></a>.
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
                                    <select class="form-control categories_id" id="download_categories_id" name="download_categories_id">

                                        <option value="0"><?php echo __('Category - Use site default'); ?></option>
                                        <?php
                                        array_multisort(array_column($_SESSION['login']->categories, 'hierarchyAndName'), SORT_ASC, $_SESSION['login']->categories);
                                        foreach ($_SESSION['login']->categories as $key => $value) {
                                            echo '<option value="' . $value->id . '">' . $value->hierarchyAndName . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <?php
                                    if (Login::canCreateCategory()) {
                                        ?>
                                        <button class="btn btn-primary" type="button" onclick="addNewCategory();"><i class="fas fa-plus"></i></button>
                                        <script>
                                            var reloadIfIsNotEditingCategoryTimeout;
                                            function addNewCategory() {
                                                clearTimeout(reloadIfIsNotEditingCategoryTimeout);
                                                avideoModalIframe('<?php echo $streamerURL; ?>categories');
                                                reloadIfIsNotEditingCategoryTimeout = setTimeout(function () {
                                                    reloadIfIsNotEditingCategory();
                                                }, 500);
                                            }

                                            function reloadIfIsNotEditingCategory() {
                                                clearTimeout(reloadIfIsNotEditingCategoryTimeout);
                                                if (!avideoModalIframeIsVisible()) {
                                                    loadCategories();
                                                } else {
                                                    reloadIfIsNotEditingCategoryTimeout = setTimeout(function () {
                                                        reloadIfIsNotEditingCategory();
                                                    }, 500);
                                                }
                                            }
                                        </script>
                                        <?php
                                    }
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
                        <span class="glyphicon glyphicon-info-sign pull-left" style="font-size: 2em; padding: 0 10px;"></span> <?php echo __('Bulk add your server local files on queue.'); ?>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <input type="text" id="path"  class="form-control" placeholder="<?php echo __('Local Path of videos i.e. /media/videos'); ?>" />
                            <span class="input-group-btn">
                                <button class="btn btn-primary" id="pathBtn">
                                    <span class="glyphicon glyphicon-list"></span> <?php echo __('List Files'); ?>
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
                                <select class="form-control categories_id" id="bulk_categories_id" name="bulk_categories_id">

                                    <option value="0"><?php echo __('Category - Use site default'); ?></option>
                                    <?php
                                    array_multisort(array_column($_SESSION['login']->categories, 'hierarchyAndName'), SORT_ASC, $_SESSION['login']->categories);
                                    foreach ($_SESSION['login']->categories as $key => $value) {
                                        echo '<option value="' . $value->id . '">' . $value->hierarchyAndName . '</option>';
                                    }
                                    ?>
                                </select>
                                <?php
                                if (Login::canCreateCategory()) {
                                    ?>
                                    <button class="btn btn-primary" type="button" onclick="addNewCategory();"><i class="fas fa-plus"></i></button>
                                    <script>
                                        var reloadIfIsNotEditingCategoryTimeout;
                                        function addNewCategory() {
                                            clearTimeout(reloadIfIsNotEditingCategoryTimeout);
                                            avideoModalIframe('<?php echo $streamerURL; ?>categories');
                                            reloadIfIsNotEditingCategoryTimeout = setTimeout(function () {
                                                reloadIfIsNotEditingCategory();
                                            }, 500);
                                        }

                                        function reloadIfIsNotEditingCategory() {
                                            clearTimeout(reloadIfIsNotEditingCategoryTimeout);
                                            if (!avideoModalIframeIsVisible()) {
                                                loadCategories();
                                            } else {
                                                reloadIfIsNotEditingCategoryTimeout = setTimeout(function () {
                                                    reloadIfIsNotEditingCategory();
                                                }, 500);
                                            }
                                        }
                                    </script>
                                    <?php
                                }
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
        <div class="availableResolutionsLabels">
            <?php
            $resolutionsInfo = Format::getAvailableResolutionsInfo();

            foreach ($resolutionsInfo as $value) {
                if (empty($value['resolutionChecked'])) {
                    continue;
                }
                echo $value['label'];
            }
            ?>
        </div>
        <div class="clearfix"></div>
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
