<link href="view/mini-upload-form/assets/css/style.css" rel="stylesheet" />
<form id="upload" method="post" action="<?= $global['webSiteRootURL'] ?>upload" enctype="multipart/form-data">
    <div class="form-group">
        <input type="text" class="form-control" id="title" name="title" placeholder="<?php echo __('Title'); ?>" />
    </div>
    <div class="form-group">
        <textarea class="form-control" id="description" name="description" placeholder="<?php echo __('Description'); ?>"></textarea>
    </div>
    <?php
    if (!empty($_SESSION['login']->categories)) {
        ?>
        <div class="form-group">
            <div style="display: flex;">
                <select class="form-control categories_id" id="categories_id" name="categories_id">

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
    <hr>
    <div id="drop">
        <?php echo __('Drop Your Files Here'); ?>

        <a><?php echo __('Browse'); ?></a>
        <input type="file" name="upl" multiple />
    </div>

    <ul>
        <!-- The file uploads will be shown here -->
    </ul>
</form>
<!-- JavaScript Includes -->
<script src="view/mini-upload-form/assets/js/jquery.knob.js"></script>

<!-- jQuery File Upload Dependencies -->
<script src="view/mini-upload-form/assets/js/jquery.ui.widget.js"></script>
<script src="view/mini-upload-form/assets/js/jquery.iframe-transport.js"></script>
<script src="view/mini-upload-form/assets/js/jquery.fileupload.js"></script>

<!-- Our main JS file -->
<script src="view/mini-upload-form/assets/js/script.js"></script>
