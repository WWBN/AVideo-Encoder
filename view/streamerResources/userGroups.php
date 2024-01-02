<?php
if (empty($_SESSION['login']->userGroups) || !empty($global['hideUserGroups'])) {
    return '';
}
?>
<div class="panel panel-default">
    <div class="panel-heading clearfix"><i class="fas fa-users"></i>
        <?php echo __('User Groups'); ?>

        <?php
        if (Login::isStreamerAdmin()) {
        ?>
            <button class="btn btn-primary btn-xs pull-right" type="button" onclick="addNewUserGroup();"><i class="fas fa-plus"></i></button>
            <script>
                var reloadIfIsNotEditingUserGroupTimeout;

                function addNewUserGroup() {
                    clearTimeout(reloadIfIsNotEditingUserGroupTimeout);
                    avideoModalIframe('<?php echo $streamerURL; ?>usersGroups');
                    reloadIfIsNotEditingUserGroupTimeout = setTimeout(function() {
                        reloadIfIsNotEditingUserGroup();
                    }, 500);
                }

                function reloadIfIsNotEditingUserGroup() {
                    clearTimeout(reloadIfIsNotEditingUserGroupTimeout);
                    if (!avideoModalIframeIsVisible()) {
                        loadUserGroups();
                    } else {
                        reloadIfIsNotEditingUserGroupTimeout = setTimeout(function() {
                            reloadIfIsNotEditingUserGroup();
                        }, 500);
                    }
                }
            </script>
        <?php
        }
        ?>
    </div>
    <div class="panel-body" id="userGroupsList">
        <div class="row">
            <?php
            foreach ($_SESSION['login']->userGroups as $key => $value) {
            ?>
                <div class="col-xs-6 <?php echo getCSSAnimationClassAndStyle('animate__flipInX', 'usergroups'); ?>">
                    <label class="single-line-ellipsis">
                        <input type="checkbox" class="usergroups_id" name="usergroups_id[]" value="<?php echo $value->id; ?>" />
                        <i class="fas fa-lock"></i> <?php echo $value->group_name; ?>
                    </label>
                </div>
            <?php
            }
            ?>
        </div>
        <div class="alert alert-info" style="margin-bottom: 0px;"><i class="fas fa-info-circle"></i> <?php echo __('Uncheck all to make it public'); ?></div>

    </div>
</div>
<script>
    function loadUserGroups() {
        modal.showPleaseWait();
        $.ajax({
            url: '<?php echo $streamerURL; ?>objects/usersGroups.json.php',
            success: function(response) {
                $('#userGroupsList .row').empty();
                for (var item in response.rows) {
                    if (typeof response.rows[item] != 'object') {
                        continue;
                    }
                    $('#userGroupsList .row').append('<div class="col-xs-6"><label class="single-line-ellipsis"><input type="checkbox" class="usergroups_id" name="usergroups_id[]" value="' + response.rows[item].id + '"> <i class="fas fa-lock"></i> ' + response.rows[item].group_name + '</label></div>');
                }
                modal.hidePleaseWait();
            }
        });
    }
    $(document).ready(function() {
        //loadUserGroups();
    });
</script>