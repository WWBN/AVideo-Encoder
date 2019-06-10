<link href="view/mini-upload-form/assets/css/style.css" rel="stylesheet" />
<form id="upload" method="post" action="<?= $global['webSiteRootURL'] ?>upload" enctype="multipart/form-data">
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
    <hr>
    <div id="drop">
        Drop Your Files Here

        <a>Browse</a>
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