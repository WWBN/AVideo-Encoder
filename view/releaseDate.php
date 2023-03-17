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
    <label for="<?php echo $releaseDateId; ?>Option"><?php echo __('Release Date'); ?></label>
    <select class="form-control" id="<?php echo $releaseDateId; ?>Option">
        <option value="<?php echo date('Y-m-d H:i'); ?>"><?php echo __('Now'); ?> (<?php echo date('Y-m-d H:i'); ?>)</option>
        <optgroup label="<?php echo __('Hours'); ?>">
            <?php
            $relaseOptions = array();
            $relaseOptions[] = '1 ' . __('Hour');
            for ($i = 2; $i < 24; $i++) {
                $relaseOptions[] = "{$i} " . __('Hours');
            }
            foreach ($relaseOptions as $value) {
                echo "<option value='" . date('Y-m-d H:i', strtotime($value)) . "'>" . __($value) . "</option>";
            }
            ?>
        </optgroup>
        <optgroup label="<?php echo __('Days'); ?>">
            <?php
            $relaseOptions = array();
            $relaseOptions[] = '1 ' . __('Day');
            for ($i = 2; $i < 31; $i++) {
                $relaseOptions[] = "{$i} " . __('Days');
            }
            foreach ($relaseOptions as $value) {
                echo "<option value='" . date('Y-m-d H:i', strtotime($value)) . "'>" . __($value) . "</option>";
            }
            ?>
        </optgroup>
        <optgroup label="<?php echo __('Months'); ?>">
            <?php
            $relaseOptions = array();
            $relaseOptions[] = '1 ' . __('Month');
            for ($i = 2; $i < 12; $i++) {
                $relaseOptions[] = "{$i} " . __('Months');
            }

            foreach ($relaseOptions as $value) {
                echo "<option value='" . date('Y-m-d H:i', strtotime($value)) . "'>" . __($value) . "</option>";
            }
            ?>
        </optgroup>
        <optgroup label="<?php echo __('Years'); ?>">
            <?php
            $relaseOptions = array();
            $relaseOptions[] = '1 ' . __('Year');
            for ($i = 2; $i < 10; $i++) {
                $relaseOptions[] = "{$i} " . __('Years');
            }

            foreach ($relaseOptions as $value) {
                echo "<option value='" . date('Y-m-d H:i', strtotime($value)) . "'>" . __($value) . "</option>";
            }
            ?>
        </optgroup>
    </select>
</div>

<div class="form-group col-sm-5">
    <label for="<?php echo $releaseDateId; ?>"><?php echo __('Date Time'); ?>:</label>
    <input type="text" id="<?php echo $releaseDateId; ?>" class="form-control input-sm" placeholder="<?php echo __('Date Time'); ?>" value="<?php echo date('Y-m-d H:i'); ?>" required />
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
