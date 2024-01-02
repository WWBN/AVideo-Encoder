<div class="panel panel-default">
    <div class="panel-heading clearfix">
        <i class="fa-regular fa-image"></i>
        <?php echo __('Video Poster'); ?>        
    </div>
    <div class="panel-body" >
        // Check if is portrait or landscape<br>
        <?php
        if(!empty($_SESSION['login']->plugin->portraitImage)){
            echo "is portrait";
        }else{
            echo "is lanscape";
        }
        ?><br>
        // Load cropie<br>

    </div>
</div>
<script>    
    $(document).ready(function() {
        //loadUserGroups();
    });
</script>