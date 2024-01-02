<?php
if (empty($advancedCustom->showOnlyEncoderAutomaticResolutions)) {
?>
    <div class="panel panel-default">
        <div class="panel-heading"><i class="fas fa-desktop"></i> <?php echo __('Resolutions'); ?></div>
        <div class="panel-body">
            <?php
            if (empty($advancedCustom->doNotShowEncoderHLS)) {
            ?>
                <label style="" id="">
                    <input type="checkbox" id="inputHLS" checked="checked" onclick="if ($(this).is(':checked')) {
                                                    $('.mp4Checkbox').prop('checked', false);
                                                }" /> <?php echo __('Multi Bitrate HLS'); ?>
                </label><br>
            <?php
            }
            if (empty($advancedCustom->doNotShowEncoderResolutionLow)) {
            ?>
                <label style="" id="">
                    <input type="checkbox" id="inputLow" <?php if (!empty($advancedCustom->doNotShowEncoderHLS)) echo 'checked="checked"'; ?> class="mp4Checkbox" onclick="if ($(this).is(':checked')) {
                                                    $('#inputHLS').prop('checked', false);
                                                }" /> <?php echo __('Low'); ?>
                </label>
            <?php
            }
            if (empty($advancedCustom->doNotShowEncoderResolutionSD)) {
            ?>
                <label id="">
                    <input type="checkbox" id="inputSD" <?php if (!empty($advancedCustom->doNotShowEncoderHLS)) echo 'checked="checked"'; ?> class="mp4Checkbox" onclick="if ($(this).is(':checked')) {
                                                    $('#inputHLS').prop('checked', false);
                                                }" /> <?php echo __('SD'); ?>
                </label>
            <?php
            }
            if (empty($advancedCustom->doNotShowEncoderResolutionHD)) {
            ?>
                <label>
                    <input type="checkbox" id="inputHD" <?php if (!empty($advancedCustom->doNotShowEncoderHLS)) echo 'checked="checked"'; ?> class="mp4Checkbox" onclick="if ($(this).is(':checked')) {
                                                    $('#inputHLS').prop('checked', false);
                                                }" /> <?php echo __('HD'); ?>
                </label>
            <?php
            }
            ?>
        </div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading"><i class="fas fa-cogs"></i> <?php echo __('Advanced'); ?></div>
        <div class="panel-body">
            <?php if (empty($advancedCustom->doNotShowExtractAudio)) { ?>
                <label>
                    <input type="checkbox" id="inputAudioOnly" />
                    <span class="glyphicon glyphicon-headphones"></span> <?php echo __('Extract Audio'); ?>
                </label><br>
            <?php } ?>
            <?php if (empty($advancedCustom->doNotShowCreateVideoSpectrum)) { ?>
                <label style="display: none;" id="spectrum">
                    <input type="checkbox" id="inputAudioSpectrum" />
                    <span class="glyphicon glyphicon-equalizer"></span> <?php echo __('Create Video Spectrum'); ?>
                </label>
            <?php } ?>
            <?php
            if (empty($global['disableWebM'])) {
                if (empty($global['defaultWebM']))
                    $checked = '';
                else
                    $checked = 'checked="checked"';
            ?>
                <label id="webm">
                    <input type="checkbox" id="inputWebM" <?php echo $checked; ?> />
                    <i class="fas fa-chrome" aria-hidden="true"></i> <?php echo __('Extract WebM Video'); ?> <small class="text-muted">(<?php echo __('The encode process will be slow'); ?>)</small>
                    <br><small class="label label-warning">
                        <?php echo __('For Chrome Browsers'); ?>
                    </small>
                </label>
            <?php
            }
            ?>
        </div>
    </div>
<?php
}
?>