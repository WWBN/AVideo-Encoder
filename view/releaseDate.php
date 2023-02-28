<?php
global $releaseDateJSandCSSAdded;
if (empty($releaseDateId)) {
    $releaseDateId = 'releaseDate';
}
if (!isset($releaseDateJSandCSSAdded)) {
?>
    <link href="<?php echo Login::getStreamerURL(); ?>js/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css" rel="stylesheet" type="text/css" />
    <script src="<?php echo Login::getStreamerURL(); ?>js/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js" type="text/javascript"></script>
<?php
    $releaseDateJSandCSSAdded = 1;
}
?><div class="form-group col-sm-7">
    <label for="<?php echo $releaseDateId; ?>Option"><?php echo __("Release Date"); ?></label>
    <select class="form-control" id="<?php echo $releaseDateId; ?>Option">
        <option value='<?php echo date('Y-m-d H:i'); ?>'><?php echo __('Now'); ?> (<?php echo date('Y-m-d H:i'); ?>)</option>
        <optgroup label="Hours">
            <?php
            $relaseOptions = array();
            $relaseOptions[] = '1 Hour';
            for ($i = 2; $i < 24; $i++) {
                $relaseOptions[] = "{$i} Hours";
            }
            foreach ($relaseOptions as $value) {
                echo "<option value='" . date('Y-m-d H:i', strtotime($value)) . "'>" . __($value) . "</option>";
            }
            ?>
        </optgroup>
        <optgroup label="Days">
            <?php
            $relaseOptions = array();
            $relaseOptions[] = '1 Day';
            for ($i = 2; $i < 31; $i++) {
                $relaseOptions[] = "{$i} Days";
            }
            foreach ($relaseOptions as $value) {
                echo "<option value='" . date('Y-m-d H:i', strtotime($value)) . "'>" . __($value) . "</option>";
            }
            ?>
        </optgroup>
        <optgroup label="Months">
            <?php
            $relaseOptions = array();
            $relaseOptions[] = '1 Month';
            for ($i = 2; $i < 12; $i++) {
                $relaseOptions[] = "{$i} Months";
            }

            foreach ($relaseOptions as $value) {
                echo "<option value='" . date('Y-m-d H:i', strtotime($value)) . "'>" . __($value) . "</option>";
            }
            ?>
        </optgroup>
        <optgroup label="Years">
            <?php
            $relaseOptions = array();
            $relaseOptions[] = '1 Year';
            for ($i = 2; $i < 10; $i++) {
                $relaseOptions[] = "{$i} Years";
            }

            foreach ($relaseOptions as $value) {
                echo "<option value='" . date('Y-m-d H:i', strtotime($value)) . "'>" . __($value) . "</option>";
            }
            ?>
        </optgroup>
    </select>
</div>

<div class="form-group col-sm-5">
    <label for="<?php echo $releaseDateId; ?>"><?php echo __("Date Time"); ?>:</label>
    <input type="text" id="<?php echo $releaseDateId; ?>" class="form-control input-sm" placeholder="<?php echo __("Date Time"); ?>" value='<?php echo date('Y-m-d H:i'); ?>' required>
</div>
<div class="clearfix"></div>
<script>
    $('#<?php echo $releaseDateId; ?>Option').change(function() {
        $('#<?php echo $releaseDateId; ?>').val($(this).val());
    });
    $('#<?php echo $releaseDateId; ?>').datetimepicker({
        format: 'yyyy-mm-dd hh:ii',
        autoclose: true
    });
</script>