<style>

    .radio-tile-group {
        display: -webkit-box;
        display: flex;
        flex-wrap: wrap;
        -webkit-box-pack: center;
        justify-content: center;
    }
    .radio-tile-group .input-container {
        position: relative;
        height: 7rem;
        width: 7rem;
        margin: 0.5rem;
    }
    .radio-tile-group .input-container .radio-button {
        opacity: 0;
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 100%;
        margin: 0;
        cursor: pointer;
    }
    .radio-tile-group .input-container .radio-tile {
        display: -webkit-box;
        display: flex;
        -webkit-box-orient: vertical;
        -webkit-box-direction: normal;
        flex-direction: column;
        -webkit-box-align: center;
        align-items: center;
        -webkit-box-pack: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        border: 2px solid #079ad9;
        border-radius: 5px;
        padding: 1rem;
        -webkit-transition: -webkit-transform 300ms ease;
        transition: -webkit-transform 300ms ease;
        transition: transform 300ms ease;
        transition: transform 300ms ease, -webkit-transform 300ms ease;
    }
    .radio-tile-group .input-container i{
        color: #079ad9;
    }
    .radio-tile-group .input-container .radio-tile-label {
        text-align: center;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #079ad9;
    }
    .radio-tile-group .input-container .radio-button + .radio-tile {
        transition: 0.3s;
    }
    .radio-tile-group .input-container .radio-button:checked + .radio-tile {
        background-color: #079ad9;
        border: 2px solid #079ad9;
        color: white;
        -webkit-transform: scale(1.1, 1.1);
        transform: scale(1.1, 1.1);
    }
    .radio-tile-group .input-container .radio-button:checked + .radio-tile i {
        color: white;
        background-color: #079ad9;
    }
    .radio-tile-group .input-container .radio-button:checked + .radio-tile .radio-tile-label {
        color: white;
        background-color: #079ad9;
    }

</style>

<div class="radio-tile-group">
    <?php
    $checked = "checked";
    if (empty($advancedCustom->doNotShowEncoderAutomaticHLS)) {
        if (empty($_COOKIE['format'])) {
            $_COOKIE['format'] = 'inputAutoHLS';
        }
        ?> 
        <div class="input-container">
            <input type="radio" id="inputAutoHLS" name="format" class="radio-button" 
                   <?php echo ($_COOKIE['format'] === 'inputAutoHLS') ? 'checked' : ''; ?>>
            <div class="radio-tile">
                <div class="icon fly-icon">
                    <i class="fas fa-file-video fa-2x"></i>
                </div>
                <label for="inputAutoHLS" class="radio-tile-label">HLS</label>
            </div>
        </div>
        <?php
    }
    if (empty($advancedCustom->doNotShowEncoderAutomaticMP4)) {
        if (empty($_COOKIE['format'])) {
            $_COOKIE['format'] = 'inputAutoMP4';
        }
        ?> 
        <div class="input-container">
            <input type="radio" id="inputAutoMP4" name="format" class="radio-button"
                   <?php echo ($_COOKIE['format'] === 'inputAutoMP4') ? 'checked' : ''; ?>>
            <div class="radio-tile">
                <div class="icon fly-icon">
                    <i class="fas fa-file-video fa-2x"></i>
                </div>
                <label for="inputAutoMP4" class="radio-tile-label">MP4</label>
            </div>
        </div>
        <?php
    }
    if (empty($advancedCustom->doNotShowEncoderAutomaticWebm)) {
        ?> 
        <div class="input-container">
            <input type="radio" id="inputAutoWebm" name="format" class="radio-button"
                   <?php echo ($_COOKIE['format'] === 'inputAutoWebm') ? 'checked' : ''; ?>>
            <div class="radio-tile">
                <div class="icon fly-icon">
                    <i class="fas fa-file-video fa-2x"></i>
                </div>
                <label for="inputAutoWebm" class="radio-tile-label">WEBM</label>
            </div>
        </div>
        <?php
    }
    if (empty($advancedCustom->doNotShowEncoderAutomaticAudio)) { // disabled for now
        ?> 
        <div class="input-container">
            <input type="radio" id="inputAutoAudio" name="format" class="radio-button"
                   <?php echo ($_COOKIE['format'] === 'inputAutoAudio') ? 'checked' : ''; ?>>
            <div class="radio-tile">
                <div class="icon fly-icon">
                    <i class="fas fa-file-audio fa-2x"></i>
                </div>
                <label for="inputAutoHLS" class="radio-tile-label">Audio</label>
            </div>
        </div>
        <?php
    }
    ?> 
</div>