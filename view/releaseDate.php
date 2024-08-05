<?php

$advancedCustom = getAdvancedCustomizedObjectData();
if(!empty($advancedCustom->disableReleaseDate)){
    return '';
}
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
            $releaseOptions = array();
            $releaseOptions[] = '1 ' . __('Hour');
            for ($i = 2; $i < 24; $i++) {
                $releaseOptions[] = "{$i} " . __('Hours');
            }
            foreach ($releaseOptions as $value) {
                echo "<option value='" . date('Y-m-d H:i', strtotime('+'.$value)) . "'>" . __($value) . "</option>";
            }
            ?>
        </optgroup>
        <optgroup label="<?php echo __('Days'); ?>">
            <?php
            $releaseOptions = array();
            $releaseOptions[] = array('1 ' . __('Day'), '+1 Day');
            for ($i = 2; $i < 31; $i++) {
                $releaseOptions[] = array("{$i} " . __('Days'), "+$i Day");
            }
            foreach ($releaseOptions as $value) {
                echo "<option value='" . date('Y-m-d H:i', strtotime($value[1])) . "'>" . __($value[0]) . "</option>";
            }
            ?>
        </optgroup>
        <optgroup label="<?php echo __('Months'); ?>">
            <?php
            $releaseOptions = array();
            $releaseOptions[] = array('1 ' . __('Month'), '+1 Month');
            for ($i = 2; $i < 12; $i++) {
                $releaseOptions[] = array("{$i} " . __('Months'), "+$i Months");
            }

            foreach ($releaseOptions as $value) {
                echo "<option value='" . date('Y-m-d H:i', strtotime($value[1])) . "'>" . __($value[0]) . "</option>";
            }
            ?>
        </optgroup>
        <optgroup label="<?php echo __('Years'); ?>">
            <?php
            $releaseOptions = array();
            $releaseOptions[] = array('1 ' . __('Year'), '+1 Year');
            for ($i = 2; $i < 10; $i++) {
                $releaseOptions[] = array("{$i} " . __('Years'), "+{$i} Years");
            }

            foreach ($releaseOptions as $value) {
                echo "<option value='" . date('Y-m-d H:i', strtotime($value[1])) . "'>" . __($value[0]) . "</option>";
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
