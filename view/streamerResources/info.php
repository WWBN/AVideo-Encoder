<div class="panel panel-success">
    <div class="panel-heading">
        <span class="fas fa-broadcast-tower"></span> <?php echo __('Streamer info'); ?>
    </div>
    <div class="panel-body">
        <p>
            <span class="fas fa-link"></span>
            <strong><?php echo __('Streamer URL'); ?>:</strong>
            <a href="<?php echo Login::getStreamerURL(); ?>" target="_blank">
                <?php echo Login::getStreamerURL(); ?>
            </a>
        </p>
        <p>
            <span class="fas fa-user-circle"></span>
            <strong><?php echo __('User'); ?>:</strong> <?php echo Login::getStreamerUser(); ?>
        </p>
        <p>
            <span class="fa-solid fa-file-upload"></span>
            <strong><?php echo __('Max File Size'); ?>:</strong> <?php echo get_max_file_size(); ?>
        </p>
        <?php if (Login::isAdmin()) { ?>
        <hr>
        <p>
            <span class="fas fa-server"></span>
            <strong><?php echo __('PHP Version'); ?>:</strong> <?php echo phpversion(); ?>
        </p>
        <p>
            <span class="fas fa-file-code"></span>
            <strong><?php echo __('php.ini location'); ?>:</strong>
            <code><?php echo php_ini_loaded_file() ?: __('(none loaded)'); ?></code>
            <?php $extraInis = php_ini_scanned_files();
            if ($extraInis): ?>
            <br><small class="text-muted"><?php echo __('Also scanning'); ?>: <code><?php echo $extraInis; ?></code></small>
            <?php endif; ?>
        </p>
        <p>
            <span class="fas fa-upload"></span>
            <strong><?php echo __('upload_max_filesize'); ?>:</strong> <?php echo ini_get('upload_max_filesize'); ?>
            &nbsp;|&nbsp;
            <strong><?php echo __('post_max_size'); ?>:</strong> <?php echo ini_get('post_max_size'); ?>
            <br><small class="text-muted"><?php echo __('Max File Size uses the smaller of these two values'); ?></small>
        </p>
        <p>
            <span class="fas fa-memory"></span>
            <strong><?php echo __('Memory Limit'); ?>:</strong> <?php echo ini_get('memory_limit'); ?>
        </p>
        <p>
            <span class="fas fa-clock"></span>
            <strong><?php echo __('Max Execution Time'); ?>:</strong> <?php echo ini_get('max_execution_time'); ?>s
        </p>
        <p>
            <span class="fas fa-hdd"></span>
            <strong><?php echo __('Disk Free Space'); ?>:</strong>
            <?php
                $bytes = disk_free_space('/');
                if ($bytes !== false) {
                    $units = ['B','KB','MB','GB','TB'];
                    $i = floor(log($bytes, 1024));
                    echo round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
                } else {
                    echo 'N/A';
                }
            ?>
        </p>
        <p>
            <span class="fas fa-microchip"></span>
            <strong><?php echo __('OS'); ?>:</strong> <?php echo php_uname('s') . ' ' . php_uname('r'); ?>
        </p>
        <p>
            <span class="fas fa-code"></span>
            <strong><?php echo __('Loaded Extensions'); ?>:</strong>
            <?php echo implode(', ', array_filter(get_loaded_extensions(), function($e) {
                return in_array($e, ['curl','gd','mbstring','mysqli','openssl','zip','exif','ffmpeg']);
            })); ?>
        </p>
        <?php } ?>
    </div>
</div>
