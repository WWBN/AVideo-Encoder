<link rel="stylesheet" href="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/css/jquery.fileupload.css">
<link rel="stylesheet" href="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/css/jquery.fileupload-ui.css">
<!-- CSS adjustments for browsers with JavaScript disabled -->
<noscript><link rel="stylesheet" href="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/css/jquery.fileupload-noscript.css"></noscript>
<noscript><link rel="stylesheet" href="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/css/jquery.fileupload-ui-noscript.css"></noscript>
<form id="fileupload" action="https://jquery-file-upload.appspot.com/" method="POST" enctype="multipart/form-data">
    <div class="form-group">
        <input type="text" class="form-control" id="title" name="title" placeholder="Title">
    </div>
    <div class="form-group">
        <textarea class="form-control" id="description" name="description" placeholder="Description"></textarea>
    </div>
    <?php
    if (!empty($_SESSION['login']->categories)) {
        ?>
        <div class="form-group">
            <select class="form-control" id="categories_id" name="categories_id">

                <option value="0">Category - Use site default</option>
                <?php
                foreach ($_SESSION['login']->categories as $key => $value) {
                    echo '<option value="' . $value->id . '">' . $value->name . '</option>';
                }
                ?>
            </select>
        </div> 
        <?php
    }
    ?>
    <!-- Redirect browsers with JavaScript disabled to the origin page -->
    <noscript><input type="hidden" name="redirect" value="https://blueimp.github.io/jQuery-File-Upload/"></noscript>
    <!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
    <div class="row fileupload-buttonbar">
        <div class="col-lg-12">
            <!-- The fileinput-button span is used to style the file input field as button -->
            <span class="btn btn-success fileinput-button col-sm-12">
                <i class="glyphicon glyphicon-plus"></i>
                <span>Add files...</span>
                <input type="file" name="files[]" multiple>
            </span>
            <button type="submit" class="btn btn-primary start col-sm-4" style="border-top-right-radius: 0; border-bottom-right-radius: 0;">
                <i class="glyphicon glyphicon-upload"></i>
                <span>Start upload</span>
            </button>
            <button type="reset" class="btn btn-warning cancel col-sm-4" style="border-radius: 0;">
                <i class="glyphicon glyphicon-ban-circle"></i>
                <span>Cancel upload</span>
            </button>
            <button type="button" class="btn btn-danger delete col-sm-4" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                <i class="glyphicon glyphicon-trash"></i>
                <span>Delete</span>
            </button>
            <input type="checkbox" class="toggle">
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
    <p class="size">Processing...</p>
    <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="progress-bar progress-bar-success" style="width:0%;"></div></div>
    <br>
    {% if (!i && !o.options.autoUpload) { %}
    <button class="btn btn-primary start" disabled>
    <i class="glyphicon glyphicon-upload"></i>
    <span>Start</span>
    </button>
    {% } %}
    {% if (!i) { %}
    <button class="btn btn-warning cancel">
    <i class="glyphicon glyphicon-ban-circle"></i>
    <span>Cancel</span>
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
    <div><span class="label label-danger">Error</span> {%=file.error%}</div>
    {% } %}
    </td>
    <td>
    <span class="size">{%=o.formatFileSize(file.size)%}</span>
    </td>
    <td>
    {% if (file.deleteUrl) { %}
    <button class="btn btn-danger delete" data-type="{%=file.deleteType%}" data-url="{%=file.deleteUrl%}"{% if (file.deleteWithCredentials) { %} data-xhr-fields='{"withCredentials":true}'{% } %}>
    <i class="glyphicon glyphicon-trash"></i>
    <span>Delete</span>
    </button>
    <input type="checkbox" name="delete" value="1" class="toggle">
    {% } else { %}
    <button class="btn btn-warning cancel">
    <i class="glyphicon glyphicon-ban-circle"></i>
    <span>Cancel</span>
    </button>
    {% } %}
    </td>
    </tr>
    {% } %}
</script>
<!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
<script src="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/js/vendor/jquery.ui.widget.js"></script>
<!-- The Templates plugin is included to render the upload/download listings -->
<script src="https://blueimp.github.io/JavaScript-Templates/js/tmpl.min.js"></script>
<!-- The Load Image plugin is included for the preview images and image resizing functionality -->
<script src="https://blueimp.github.io/JavaScript-Load-Image/js/load-image.all.min.js"></script>
<!-- The Canvas to Blob plugin is included for image resizing functionality -->
<script src="https://blueimp.github.io/JavaScript-Canvas-to-Blob/js/canvas-to-blob.min.js"></script>
<!-- blueimp Gallery script -->
<script src="https://blueimp.github.io/Gallery/js/jquery.blueimp-gallery.min.js"></script>
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
<script src="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/js/main.js"></script>
<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE 8 and IE 9 -->
<!--[if (gte IE 8)&(lt IE 10)]>
<script src="<?php echo $global['webSiteRootURL']; ?>view/jquery-file-upload/js/cors/jquery.xdr-transport.js"></script>
<![endif]-->