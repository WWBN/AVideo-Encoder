<?php
if (Login::isAdmin()) {
    
    $ffmpegArray = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 29, 30, 31, 32);
    if (empty($global['disableConfigurations'])) {
        ?>
        <div id="config" class="tab-pane fade">
            <?php
            if (empty($_POST['sort']) && $config->currentVersionGreaterThen("1.0")) {
                $_POST['sort']['`order`'] = 'asc';
            }
            $frows = Format::getAll();
            foreach ($frows as $value) {
                if (!in_array($value['id'], $ffmpegArray)) {
                    continue;
                }
                ?>
                <div class="input-group input-group-sm">
                    <span class="input-group-addon"><?php echo $value['name']; ?></span>
                    <input type="text" class="form-control formats" placeholder="Code" id="format_<?php echo $value['id']; ?>" value="<?php echo htmlentities($value['code']); ?>">
                </div>    
                <?php
            }
            ?>
            <hr>

            <div class="form-group">
                <div>
                    <label>Resolutions</label>
                </div>
                <div id="resolutions" class="checkboxes">
                <?php
                    $resolutionDisabled = "";
                    if ($config->currentVersionLowerThen("3.7")) {
                        $resolutionDisabled = " disabled ";
                ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Please upgrade to enable this feature
                    </div>
                <?php

                    }

                    $availableResolutions = Format::getAvailableResolutions();
                    $selectedResolutions = $config->getSelectedResolutions();                                        
                    foreach($availableResolutions as $key => $resolution) {
                        $resolutionChecked = (array_search($resolution, $selectedResolutions, true) !== false) || !empty($resolutionDisabled) ? "checked" : "";
                        
                        $label = "<span class='label label-default'>{$resolution}p ";
                        if($resolution == 720){
                            $label .= '<span class="label label-danger">HD</span>';
                        }else if($resolution == 1080){
                            $label .= '<span class="label label-danger">FHD</span>';
                        }else if($resolution == 1440){
                            $label .= '<span class="label label-danger">FHD+</span>';
                        }else if($resolution == 2160){
                            $label .= '<span class="label label-danger">4K</span>';
                        }
                        $label .= " </span>";
                        
                        echo "<label for='resolution-${resolution}'>".
                            "<input ${resolutionChecked} ${resolutionDisabled} type='checkbox' name='resolutions' id='resolution-${resolution}' value='${resolution}'>".
                            "{$label}".
                        "</label>";
                    }
                ?>
                </div>

                <?php 
                    if (empty($resolutionDisabled)) { 
                ?>

                <script>
                    (function($) {
                        function updateStatus() {
                            // at least one resolution must be selected
                            var $item = $("#resolutions input[type='checkbox']:checked");
                            $item.prop("disabled", $item.length === 1);
                        }
                        $(document).ready(function() {                            
                            $("#resolutions input[type='checkbox']").on("click", function() {
                                updateStatus();
                            });
                            updateStatus();                                 
                        });
                    })(jQuery);
                </script>

                <?php 
                    }
                ?>                
            </div>

            <div class="form-group">
                <label for="allowedStreamers">Allowed Streamers Sites (One per line. Leave blank for public)</label>
                <textarea class="form-control" id="allowedStreamers" placeholder="Leave Blank for Public" required="required"><?php echo $config->getAllowedStreamersURL(); ?></textarea>
            </div>

            <div class="form-group">
                <label for="defaultPriority">Default Priority</label>
                <select class="" id="defaultPriority">
                    <?php
                    $priority = $config->getDefaultPriority();
                    for ($index = 1; $index <= 10; $index++) {
                        echo '<option value="' . $index . '" ' . ($priority == $index ? "selected" : "") . ' >' . $index . '</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="defaultPriority">Auto remove</label>
                <input type="checkbox" class="" id="autodelete" value="1" <?php if (!empty($ad)) { ?>checked="true"<?php } ?>>
                <small>Will remove queue and the files when the encoder process is done</small>
            </div>

            <button class="btn btn-success btn-block" id="saveConfig"> Save </button>
        </div>
        <div id="update" class="tab-pane fade <?php if (!empty($_POST['updateFile'])) { ?>in active<?php } ?>">
            <?php
            include '../update/update.php';
            ?>
        </div>
        <?php
    }
}
?>