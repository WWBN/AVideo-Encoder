<div class="radio-tile-group" id="automaticOptions">
    <?php
    $checked = "checked";
    $countEncodeOptions = 0;
    if (empty($advancedCustom->doNotShowEncoderAutomaticHLS)) {
        $countEncodeOptions++;
        if (empty($_COOKIE['format'])) {
            $_COOKIE['format'] = 'inputAutoHLS';
        }
        ?>
        <div class="input-container <?php echo getCSSAnimationClassAndStyle('animate__flipInY', 'format', 0.2); ?>">
            <input type="radio" id="inputAutoHLS" name="format" class="radio-button"
                   <?php echo ($_COOKIE['format'] === 'inputAutoHLS') ? 'checked' : ''; ?>>
            <div class="radio-tile">
                <div class="icon fly-icon">
                    <i class="fas fa-stream" aria-hidden="true"></i>
                </div>
                <label for="inputAutoHLS" class="radio-tile-label">HLS</label>
            </div>
        </div>
        <?php
    } else {
        $countEncodeOptions++;
        $hlsDisabledReason = !empty($advancedCustom->encoderHLSDisabledReason) ? $advancedCustom->encoderHLSDisabledReason : __('HLS is currently unavailable.');
        ?>
        <div class="input-container <?php echo getCSSAnimationClassAndStyle('animate__flipInY', 'format', 0.2); ?>">
            <div class="radio-tile radio-tile-disabled">
                <div class="icon fly-icon">
                    <i class="fas fa-stream" aria-hidden="true"></i>
                </div>
                <label class="radio-tile-label">HLS</label>
                <span class="hls-disabled-help" data-toggle="tooltip" data-placement="top" title="<?php echo htmlspecialchars($hlsDisabledReason, ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="fas fa-exclamation-circle"></i>
                </span>
            </div>
        </div>
        <?php
    }
    if (empty($advancedCustom->doNotShowEncoderAutomaticMP4)) {
        $countEncodeOptions++;
        if (empty($_COOKIE['format'])) {
            $_COOKIE['format'] = 'inputAutoMP4';
        }
        ?>
        <div class="input-container <?php echo getCSSAnimationClassAndStyle('animate__flipInY', 'format', 0.2); ?>">
            <input type="radio" id="inputAutoMP4" name="format" class="radio-button"
                   <?php echo ($_COOKIE['format'] === 'inputAutoMP4') ? 'checked' : ''; ?>>
            <div class="radio-tile">
                <div class="icon fly-icon">
                    <i class="fas fa-file-video" aria-hidden="true"></i>
                </div>
                <label for="inputAutoMP4" class="radio-tile-label">MP4</label>
            </div>
        </div>
        <?php
    }
    if (empty($global['disableWebM']) && empty($advancedCustom->doNotShowEncoderAutomaticWebm)) {
        $countEncodeOptions++;
        ?>
        <div class="input-container <?php echo getCSSAnimationClassAndStyle('animate__flipInY', 'format', 0.2); ?>">
            <input type="radio" id="inputAutoWebm" name="format" class="radio-button"
                   <?php echo ($_COOKIE['format'] === 'inputAutoWebm') ? 'checked' : ''; ?>>
            <div class="radio-tile">
                <div class="icon fly-icon">
                    <i class="fas fa-film" aria-hidden="true"></i>
                </div>
                <label for="inputAutoWebm" class="radio-tile-label">WEBM</label>
            </div>
        </div>
        <?php
    }
    if (empty($advancedCustom->doNotShowEncoderAutomaticAudio)) { // disabled for now
        $countEncodeOptions++;
        ?>
        <div class="input-container <?php echo getCSSAnimationClassAndStyle('animate__flipInY', 'format', 0.2); ?>">
            <input type="radio" id="inputAutoAudio" name="format" class="radio-button"
                   <?php echo ($_COOKIE['format'] === 'inputAutoAudio') ? 'checked' : ''; ?>>
            <div class="radio-tile">
                <div class="icon fly-icon">
                    <i class="fas fa-music" aria-hidden="true"></i>
                </div>
                <label for="inputAutoAudio" class="radio-tile-label">Audio</label>
            </div>
        </div>
        <?php
    }
    ?>
</div>
<?php
if ($countEncodeOptions <= 1) {
    ?>
    <style>#automaticOptions{display: none;}</style>
    <?php
}
?>
