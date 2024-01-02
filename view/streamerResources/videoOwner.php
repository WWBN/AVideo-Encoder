<?php
$userCanChangeVideoOwner = $_SESSION['login']->streamerDetails->plugins->CustomizeUser->userCanChangeVideoOwner;
if ($_SESSION['login']->isStreamerAdmin) {
    $userCanChangeVideoOwner = true;
}
if (empty($userCanChangeVideoOwner)) {
    return '';
} 
?>
<div class="panel panel-default">
    <div class="panel-heading clearfix">
        <i class="fa-solid fa-user-tie"></i>
        <?php echo __('Video Owner'); ?>
    </div>
    <div class="panel-body">
        // check if user can change owner in the plugin<br>
        <?php
        if (!empty($userCanChangeVideoOwner)) {
            echo 'User can change video owner';
        } else {
            echo 'User can not change video owner';
        }
        ?><br>
        // make it autosearch on the streamer for users<br>
        //

    </div>
</div>
<script>
    $(document).ready(function() {
        //loadUserGroups();
    });
</script>