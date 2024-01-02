<?php
$videoTagsIsEnabled = !empty($_SESSION['login']->streamerDetails->plugins->VideoTags);
if(!$videoTagsIsEnabled){
    return '';
}
?>
<div class="panel panel-default">
    <div class="panel-heading clearfix">
        <i class="fa-solid fa-tags"></i>
        <?php echo __('Tags'); ?>        
    </div>
    <div class="panel-body" >
        // load the tag types<br>
        <?php
        foreach ($_SESSION['login']->streamerDetails->plugins->VideoTags->videoTagsTypes as $key => $value) {
            echo "{$value->name} <br>";
        }
        ?><br>

        // make it autosearch on the streamer for tags<br>

    </div>
</div>
<script>    
    $(document).ready(function() {
        //loadUserGroups();
    });
</script>