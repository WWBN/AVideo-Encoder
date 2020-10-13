
<div class="panel panel-default">
    <div class="panel-heading">
        <?php
        if (!empty($advancedCustom->showOnlyEncoderAutomaticResolutions)) {
            include './index_formats.php';
        }
        ?>
    </div>
    <div class="panel-body">
        <ul class="nav nav-tabs">
            <li class="active">
                <a data-toggle="tab" href="#upload">
                    <center>
                        <i class="fas fa-file" aria-hidden="true"></i><br>From File
                    </center>
                </a>
            </li>
            <?php
            if (empty($global['disableImportVideo'])) {
                ?>
                <li ><a data-toggle="tab" href="#download"><center><i class="fas fa-globe" aria-hidden="true"></i><br>Import Video</center></a></li>
                <?php
            }
            if (Login::canBulkEncode()) {
                ?>
                <li><a data-toggle="tab" href="#bulk"><center><span class="glyphicon glyphicon-duplicate"></span><br>Bulk Encode</center></a></li>
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
                        <span class="glyphicon glyphicon-info-sign"></span> Share videos from YouTube and a few <a href="https://rg3.github.io/youtube-dl/supportedsites.html" target="_blank">more sites</a>.
                    </div>
                    <form id="downloadForm" onsubmit="">
                        <div class="form-group">
                            <div class="input-group">
                                <input type="url" class="form-control" id="inputVideoURL" placeholder="http://...">
                                <span class="input-group-btn">
                                    <button class="btn btn-secondary" type="submit">
                                        <i class="fas fa-check" aria-hidden="true"></i> Share
                                    </button>
                                </span>
                            </div>
                        </div>

                        <?php
                        if (!empty($_SESSION['login']->categories)) {
                            ?>
                            <div class="form-group">
                                <select class="form-control" id="download_categories_id" name="download_categories_id">

                                    <option value="0">Category - Use site default</option>
                                    <?php
                                    array_multisort(array_column($_SESSION['login']->categories, 'hierarchyAndName'), SORT_ASC, $_SESSION['login']->categories);
                                    foreach ($_SESSION['login']->categories as $key => $value) {
                                        echo '<option value="' . $value->id . '">' . $value->hierarchyAndName . '</option>';
                                    }
                                    ?>
                                </select>
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
                                    <i class="fas fa-check-square" aria-hidden="true"></i>
                                </button>
                            </span>
                            <span class="input-group-btn">
                                <button class="btn btn-secondary" id="uncheckBtn">
                                    <i class="far fa-square" aria-hidden="true"></i>
                                </button>
                            </span>
                        </div>
                    </div>

                    <?php
                    if (!empty($_SESSION['login']->categories)) {
                        ?>
                        <div class="form-group">
                            <select class="form-control" id="bulk_categories_id" name="bulk_categories_id">

                                <option value="0">Category - Use site default</option>
                                <?php
                                array_multisort(array_column($_SESSION['login']->categories, 'hierarchyAndName'), SORT_ASC, $_SESSION['login']->categories);
                                foreach ($_SESSION['login']->categories as $key => $value) {
                                    echo '<option value="' . $value->id . '">' . $value->hierarchyAndName . '</option>';
                                }
                                ?>
                            </select>
                        </div> 
                        <?php
                    }
                    ?>
                    <ul class="list-group" id="files">
                    </ul>
                    <button class="btn btn-block btn-primary" id="addQueueBtn">Add on Queue</button>
                </div>
            <?php } ?>
        </div> 
    </div>
</div>
