<link rel="stylesheet" type="text/css" href="<?php echo $streamerURL; ?>view/css/social.css"/>
<link rel="stylesheet" type="text/css" href="<?php echo $streamerURL; ?>view/css/DataTables/datatables.min.css"/>
<script src="<?php echo $streamerURL; ?>view/css/DataTables/datatables.min.js" type="text/javascript"></script>
<script src="<?php echo $streamerURL; ?>plugin/SocialMediaPublisher/script.js" type="text/javascript"></script>

<style>
    tr.accessTokenExpired td {
        text-decoration: line-through;
    }

    .showIfExpired,
    .showIfNotExpired,
    .showIfCanRefreshAccessToken {
        display: none;
    }

    tr.canRefreshAccessToken .showIfCanRefreshAccessToken,
    tr.accessTokenExpired .showIfExpired,
    tr.accessTokenNotExpired .showIfNotExpired {
        display: inline-block;
    }
</style>
<div class="container-fluid">


    <div class="panel panel-default ">
        <div class="panel-heading" id="linkSocialMediasButtons">
            <div class="social-network btn-group btn-group-justified" style="display: flex;">
                <button type="button" class="btn btn-default icoYoutube " onclick="openYPT('youtube')">
                    <div class="largeSocialIcon"><i class="fab fa-youtube"></i></div>
                    YouTube
                </button>
            </div>
        </div>
        <div class="panel-body">
            <table id="Publisher_user_preferencesTable" class="display table table-bordered table-responsive table-striped table-hover table-condensed " width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th><?php echo __("Profile"); ?></th>
                        <th><?php echo __("Expires in"); ?></th>
                        <th><?php echo __("Connection"); ?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th><?php echo __("Profile"); ?></th>
                        <th><?php echo __("Expires in"); ?></th>
                        <th><?php echo __("Connection"); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>


<div id="Publisher_user_preferencesbtnModelLinks" style="display: none;">
    <div class="btn-group btn-group-justified"  style="display: flex;">
        <button type="button" class="revalidate_Publisher_user_preferences btn btn-success showIfCanRefreshAccessToken">
            <i class="fa-solid fa-arrows-rotate"></i>
        </button>
        <button type="button" class="delete_Publisher_user_preferences btn btn-danger ">
            <i class="fa-solid fa-trash"></i>
        </button>
    </div>
</div>

<script type="text/javascript">
    
    var yptURL = '<?php echo Streamer::RESTREAMER_URL; ?>';

    function saveYPT(provider, name, parameters) {
        console.log('saveYPT', provider, name, parameters);
        yptPopupOpened = 0;
        $.ajax({
            url: '<?php echo $global['webSiteRootURL']; ?>view/index_signin.save.json.php',
            type: "POST",
            data: {
                provider: provider,
                name: name,
                json: parameters,
            },
            success: function(response) {
                modal.hidePleaseWait();
                reloadSocialAccountsTables();
            }
        });
        yptWin.close();
    }

    var Publisher_user_preferencestableVar;
    $(document).ready(function() {
        Publisher_user_preferencestableVar = $('#Publisher_user_preferencesTable').DataTable({
            serverSide: true,
            "ajax": "<?php echo $global['webSiteRootURL']; ?>view/index_signin.json.php",
            "columns": [
                {
                    "data": "profile"
                },
                {
                    sortable: false,
                    "data": "expires_at_human"
                },
                {
                    width: '100px',
                    sortable: false,
                    data: null,
                    defaultContent: $('#Publisher_user_preferencesbtnModelLinks').html()
                }
            ],
            select: true,
            "createdRow": function(row, data, dataIndex) {
                // Check if accessTokenExpired is true in the data and add 'expired' class to the row
                if (data.accessTokenExpired === true) {
                    $(row).addClass('accessTokenExpired');
                    $(row).find('td').addClass('text-muted');
                } else {
                    $(row).addClass('accessTokenNotExpired');
                }
                if (data.canRefreshAccessToken === true) {
                    $(row).addClass('canRefreshAccessToken');
                } else {
                    $(row).addClass('canNotRefreshAccessToken');
                }
            }
        });

        $('#Publisher_user_preferencesTable').on('click', 'button.delete_Publisher_user_preferences', function(e) {
            e.preventDefault();
            var tr = $(this).closest('tr')[0];
            var data = Publisher_user_preferencestableVar.row(tr).data();
            swal({
                    title: "<?php echo __("Are you sure?"); ?>",
                    text: "<?php echo __("You will not be able to recover this action!"); ?>",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                })
                .then(function(willDelete) {
                    if (willDelete) {
                        modal.showPleaseWait();
                        $.ajax({
                            type: "POST",
                            url: '<?php echo $global['webSiteRootURL']; ?>view/index_signin.delete.json.php',
                            data: data

                        }).done(function(resposta) {
                            if (resposta.error) {
                                avideoAlertError(resposta.msg);
                            }
                            if (typeof Publisher_user_preferencestableVar !== 'undefined') {
                                Publisher_user_preferencestableVar.ajax.reload();
                            }
                            if (typeof Publisher_social_mediastableVar !== 'undefined') {
                                Publisher_social_mediastableVar.ajax.reload();
                            }
                            modal.hidePleaseWait();
                        });
                    } else {

                    }
                });
        });
        $('#Publisher_user_preferencesTable').on('click', 'button.revalidate_Publisher_user_preferences', function(e) {
            e.preventDefault();
            var tr = $(this).closest('tr')[0];
            var data = Publisher_user_preferencestableVar.row(tr).data();
            modal.showPleaseWait();
            $.ajax({
                type: "POST",
                url: '<?php echo $global['webSiteRootURL']; ?>view/index_signin.revalidate.json.php',
                data: data
            }).done(function(resposta) {
                if (resposta.error) {
                    avideoAlertError(resposta.msg);
                }
                if (typeof Publisher_user_preferencestableVar !== 'undefined') {
                    Publisher_user_preferencestableVar.ajax.reload();
                }
                if (typeof Publisher_social_mediastableVar !== 'undefined') {
                    Publisher_social_mediastableVar.ajax.reload();
                }
                modal.hidePleaseWait();
            });
        });
    });
</script>