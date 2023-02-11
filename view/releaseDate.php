<?php
if (empty($releaseDateId)) {
    $releaseDateId = 'releaseDate';
}
?>
<div class="form-group">
    <label for="<?php echo $releaseDateId; ?>" ><?php echo __("Release Date"); ?></label>
    <select class="form-control" id="<?php echo $releaseDateId; ?>" >
        <option value='now'><?php echo __('Now'); ?></option>
        <option value='in-1-hour'><?php echo __('1 hour'); ?></option>
        <?php
        $relaseOptions = array('1 day', '2 days', '1 week', '1 month', '1 year');
        foreach ($relaseOptions as $value) {
            echo "<option value='" . date('Y-m-d H:i:s', strtotime($value)) . "'>" . __($value) . "</option>";
        }
        ?>
    </select>
</div>