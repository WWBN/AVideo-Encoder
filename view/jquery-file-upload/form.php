<form id="fileupload" action="" method="POST" enctype="multipart/form-data">
    <div class="form-group">
        <input type="text" class="form-control" id="title" name="title" placeholder="<?php echo __('Title'); ?>">
    </div>
    <div class="form-group">
        <textarea class="form-control" id="description" name="description" placeholder="<?php echo __('Description'); ?>"></textarea>
    </div>
    <?php
    $releaseDateId = 'releaseDate';
    include $global['systemRootPath'].'view/releaseDate.php';
    ?>
    <div class="clearfix"></div>
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
        <script>
            function loadCategories() {
                console.log('loadCategories');
                modal.showPleaseWait();
                $.ajax({
                    url: '<?php echo $streamerURL; ?>objects/categories.json.php',
                    success: function (response) {
                        $('.categories_id').empty();
                        for (var item in response.rows) {
                            if (typeof response.rows[item] != 'object') {
                                continue;
                            }
                            $('.categories_id').append('<option value="' + response.rows[item].id + '"> ' + response.rows[item].hierarchyAndName + '</option>');
                        }
                        modal.hidePleaseWait();
                    }
                });
            }
            $(document).ready(function () {
                //loadCategories();
            });
        </script>
        <?php
    }
    ?>
    <!-- Redirect browsers with JavaScript disabled to the origin page -->
    <noscript><input type="hidden" name="redirect" value=""></noscript>
    <!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
    <div class="row fileupload-buttonbar">
        <div class="col-lg-12">
            <!-- The fileinput-button span is used to style the file input field as button -->
            <span class="btn btn-success fileinput-button col-sm-12">
                <i class="glyphicon glyphicon-plus"></i>
                <span><?php echo __('Add files...'); ?></span>
                <input type="file" name="files[]" multiple />
            </span>
            <button type="submit" class="btn btn-primary start col-sm-4" style="border-top-right-radius: 0; border-bottom-right-radius: 0;">
                <i class="glyphicon glyphicon-upload"></i>
                <span><?php echo __('Start upload'); ?></span>
            </button>
            <button type="reset" class="btn btn-warning cancel col-sm-4" style="border-radius: 0;">
                <i class="glyphicon glyphicon-ban-circle"></i>
                <span><?php echo __('Cancel upload'); ?></span>
            </button>
            <button type="button" class="btn btn-danger delete col-sm-4" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                <i class="glyphicon glyphicon-trash"></i>
                <span><?php echo __('Delete'); ?></span>
            </button>
            <input type="checkbox" class="toggle" name="selectAll" />
            <label for="selectAll"> <?php echo __('Select All'); ?> </label>
            <!-- The global file processing state -->
            <span class="fileupload-process"></span>
        </div>
    </div>
    <!-- The table listing the files available for upload/download -->
    <table role="presentation" class="table table-striped"><tbody class="files"></tbody></table>
</form>
<!-- The template to display files available for upload -->
<script id="template-upload" type="text/x-tmpl">
    {% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-upload fade">
    <td colspan="2">
    <span class="preview"></span>
    {% if (window.innerWidth > 480 || !o.options.loadImageFileTypes.test(file.type)) { %}
    <br>
    <p class="name">{%=file.name%}</p>
    {% } %}
    <strong class="error text-danger"></strong>
    <br>
    <p class="size"><?php echo __('Processing...'); ?></p>
    <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="progress-bar progress-bar-success" style="width:0%;"></div></div>
    <br>
    {% if (!i && !o.options.autoUpload) { %}
    <button class="btn btn-primary start" disabled>
    <i class="glyphicon glyphicon-upload"></i>
    <span><?php echo __('Start'); ?></span>
    </button>
    {% } %}
    {% if (!i) { %}
    <button class="btn btn-warning cancel">
    <i class="glyphicon glyphicon-ban-circle"></i>
    <span><?php echo __('Cancel'); ?></span>
    </button>
    {% } %}
    </td>
    </tr>
    {% } %}
</script>
<!-- The template to display files available for download -->
<script id="template-download" type="text/x-tmpl">
    {% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-download fade">
    <td>
    <span class="preview">
    {% if (file.thumbnailUrl) { %}
    <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" data-gallery><img src="{%=file.thumbnailUrl%}"></a>
    {% } %}
    </span>
    </td>
    <td>
    {% if (window.innerWidth > 480 || !file.thumbnailUrl) { %}
    <p class="name">
    {% if (file.url) { %}
    <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" {%=file.thumbnailUrl?'data-gallery':''%}>{%=file.name%}</a>
    {% } else { %}
    <span>{%=file.name%}</span>
    {% } %}
    </p>
    {% } %}
    {% if (file.error) { %}
    <div><span class="label label-danger"><?php echo __('Error'); ?></span> {%=file.error%}</div>
    {% } %}
    </td>
    <td>
    <span class="size">{%=o.formatFileSize(file.size)%}</span>
    </td>
    <td>
    {% if (file.deleteUrl) { %}
    <button class="btn btn-danger delete" data-type="{%=file.deleteType%}" data-url="{%=file.deleteUrl%}&PHPSESSID={%=PHPSESSID%}"{% if (file.deleteWithCredentials) { %} data-xhr-fields='{"withCredentials":true}'{% } %}>
    <i class="glyphicon glyphicon-trash"></i>
    <span><?php echo __('Delete'); ?></span>
    </button>
    <input type="checkbox" name="delete" value="1" class="toggle">
    {% } else { %}
    <button class="btn btn-warning cancel">
    <i class="glyphicon glyphicon-ban-circle"></i>
    <span><?php echo __('Cancel'); ?></span>
    </button>
    {% } %}
    </td>
    </tr>
    {% } %}
</script>
<!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
<script src="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/js/vendor/jquery.ui.widget.js"></script>
<!-- The Templates plugin is included to render the upload/download listings -->
<script src="<?php echo $global['webSiteRootURL']; ?>view/js/tmpl.min.js"></script>
<!-- The Load Image plugin is included for the preview images and image resizing functionality -->
<script src="<?php echo $global['webSiteRootURL']; ?>view/js/load-image.all.min.js"></script>
<!-- The Canvas to Blob plugin is included for image resizing functionality -->
<script src="<?php echo $global['webSiteRootURL']; ?>view/js/canvas-to-blob.min.js"></script>
<!-- blueimp Gallery script -->
<script src="<?php echo $global['webSiteRootURL']; ?>view/js/jquery.blueimp-gallery.min.js"></script>
<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
<script src="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/js/jquery.iframe-transport.js"></script>
<!-- The basic File Upload plugin -->
<script src="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/js/jquery.fileupload.js"></script>
<!-- The File Upload processing plugin -->
<script src="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/js/jquery.fileupload-process.js"></script>
<!-- The File Upload image preview & resize plugin -->
<script src="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/js/jquery.fileupload-image.js"></script>
<!-- The File Upload audio preview plugin -->
<script src="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/js/jquery.fileupload-audio.js"></script>
<!-- The File Upload video preview plugin -->
<script src="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/js/jquery.fileupload-video.js"></script>
<!-- The File Upload validation plugin -->
<script src="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/js/jquery.fileupload-validate.js"></script>
<!-- The File Upload user interface plugin -->
<script src="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/js/jquery.fileupload-ui.js"></script>
<!-- The main application script -->
<script src="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/js/main.js?<?php echo filectime($global['systemRootPath'] . 'view/jquery-file-upload/js/main.js'); ?>"></script>
<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE 8 and IE 9 -->
<!--[if (gte IE 8)&(lt IE 10)]>
<script src="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/js/cors/jquery.xdr-transport.js"></script>
<![endif]-->
