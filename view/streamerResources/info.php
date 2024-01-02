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
    </div>
</div>