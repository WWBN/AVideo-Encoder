<?php
if (empty($advancedCustom->doNotAllowUpdateVideoId)) {
    ?>
        <div class="panel panel-default">
            <div class="panel-heading"><i class="fas fa-desktop"></i> <?php echo __('Update existing video'); ?></div>
            <div class="panel-body">
                <img id="inputNextVideo-poster" src="view/img/notfound.jpg" class="ui-state-default img img-responsive" alt="" />
                <input type="text" class="form-control" id="videoSearch" name="videoSearch" placeholder="<?php echo __('Search for a video'); ?>" />
                <input type="number" class="form-control" id="update_video_id" name="update_video_id" placeholder="<?php echo __('Video Id'); ?>" />
            </div>
        </div>

        <script>
            $(function() {
                $("#videoSearch").autocomplete({
                    minLength: 0,
                    source: function(req, res) {
                        $.ajax({
                            url: '<?php echo Login::getStreamerURL(); ?>objects/videos.json.php?rowCount=6',
                            data: {
                                searchPhrase: req.term,
                                users_id: '<?php echo Login::getStreamerUserId(); ?>',
                                user: '<?php echo Login::getStreamerUser(); ?>',
                                pass: '<?php echo Login::getStreamerPass(); ?>',
                                encodedPass: true
                            },
                            /*
                             xhrFields: {
                             //withCredentials: true
                             },
                             */
                            type: 'post',
                            success: function(data) {
                                res(data.rows);
                            }
                        });
                    },
                    focus: function(event, ui) {
                        $("#videoSearch").val(ui.item.title);
                        return false;
                    },
                    select: function(event, ui) {
                        $("#videoSearch").val(ui.item.title);
                        $("#update_video_id").val(ui.item.id);
                        console.log(ui.item.videosURL);
                        console.log(ui.item.videosURL.jpg);
                        $("#inputNextVideo-poster").attr("src", ui.item.videosURL.jpg.url);
                        return false;
                    }
                }).autocomplete("instance")._renderItem = function(ul, item) {
                    return $("<li>").append("<div>" + item.title + "</div>").appendTo(ul);
                };
            });
        </script>
    <?php
    }
?>