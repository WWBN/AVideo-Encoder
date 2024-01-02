<?php
if (empty($advancedCustom->doNotAllowEncoderOverwriteStatus)) {
?>
    <div class="panel panel-default">
        <div class="panel-heading">
            <i class="fa-solid fa-eye"></i>
            <?php echo __('Override status'); ?>
        </div>
        <div class="panel-body">
            <select class="form-control" id="override_status" name="override_status">
                <option value=""><?php echo __('Use site default'); ?></option>
                <option value="a"><?php echo __('Active'); ?></option>
                <option value="i"><?php echo __('Inactive'); ?></option>
                <option value="u"><?php echo __('Unlisted'); ?></option>
                <option value="s"><?php echo __('Unlisted but Searchable'); ?></option>
            </select>
        </div>
    </div>
<?php
}

?>