/*
 * jQuery File Upload Plugin JS Example
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * https://opensource.org/licenses/MIT
 */

/* global $, window */
var selectedFileName = "";
$(function () {
    'use strict';
    // Initialize the jQuery File Upload widget:
    $('#fileupload').fileupload({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url: 'view/jquery-file-upload/server/php/?PHPSESSID=' + PHPSESSID,
        maxChunkSize: 5000000, // 5 MB
        add: function (e, data) {
            selectedFileName = data.files[0].name;
            var videos_id = $('#update_video_id').val();
            var that = this;
            if (videos_id) {
                swal({
                    title: "You will overwrite the video ID: " + videos_id,
                    text: "The video will be replaced with this new file, are you sure you want to proceed?",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                })
                    .then(function (confirm) {
                        if (confirm) {
                            $.getJSON('view/jquery-file-upload/server/php/', { file: data.files[0].name, PHPSESSID: PHPSESSID, from: 1 }, function (result) {
                                var file = result.file;
                                data.uploadedBytes = file && file.size;
                                $.blueimp.fileupload.prototype
                                    .options.add.call(that, e, data);
                            });
                        }
                    });
            } else {
                $.getJSON('view/jquery-file-upload/server/php/', { file: data.files[0].name, PHPSESSID: PHPSESSID, from: 2 }, function (result) {
                    var file = result.file;
                    data.uploadedBytes = file && file.size;
                    $.blueimp.fileupload.prototype
                        .options.add.call(that, e, data);
                });
            }
        },
        maxRetries: 100,
        retryTimeout: 500,
        fail: function (e, data) {
            // jQuery Widget Factory uses "namespace-widgetname" since version 1.10.0:
            var fu = $(this).data('blueimp-fileupload') || $(this).data('fileupload'),
                retries = data.context.data('retries') || 0,
                retry = function () {
                    $.getJSON('view/jquery-file-upload/server/php/', { file: data.files[0].name, PHPSESSID: PHPSESSID, from: 3 })
                        .done(function (result) {
                            var file = result.file;
                            data.uploadedBytes = file && file.size;
                            // clear the previous data:
                            data.data = null;
                            data.submit();
                        })
                        .fail(function () {
                            fu._trigger('fail', e, data);
                        });
                };
            if (data.errorThrown !== 'abort' &&
                data.uploadedBytes < data.files[0].size &&
                retries < fu.options.maxRetries) {
                retries += 1;
                data.context.data('retries', retries);
                window.setTimeout(retry, retries * fu.options.retryTimeout);
                return;
            }
            data.context.removeData('retries');
            $.blueimp.fileupload.prototype
                .options.fail.call(this, e, data);
        }
    });

    // Enable iframe cross-domain access via redirect option:
    $('#fileupload').fileupload(
        'option',
        'redirect',
        window.location.href.replace(
            /\/[^\/]*$/,
            '/cors/result.html?%s'
        )
    );

    // Load existing files:
    $('#fileupload').addClass('fileupload-processing');
    $.ajax({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url: $('#fileupload').fileupload('option', 'url'),
        dataType: 'json',
        context: $('#fileupload')[0]
    }).always(function () {
        $(this).removeClass('fileupload-processing');
    }).done(function (result) {
        $(this).fileupload('option', 'done')
            .call(this, $.Event('done'), { result: result });
    });
    $('#fileupload').bind('fileuploadsubmit', async function (e, data) {
        if ($('#videos_id').val() == '') {
            e.preventDefault();
            var response = await createVideo();
            console.log(response);
            if (!empty(response) && !empty(response.videos_id)) {
                $('#videos_id').val(response.videos_id);
                editVideos(response.videos_id);
            }else{
                $('#videos_id').val(0);
            }
            
            data.submit();
        } else {
            data.formData = {
                "audioOnly": $('#inputAudioOnly').is(":checked"),
                "spectrum": $('#inputAudioSpectrum').is(":checked"),
                "webm": $('#inputWebM').is(":checked"),
                "override_status": $('#override_status').val(),
                "videos_id": $('#videos_id').val(),
                "update_video_id": $('#update_video_id').val(),
                "inputHLS": $('#inputHLS').is(":checked"),
                "inputLow": $('#inputLow').is(":checked"),
                "inputSD": $('#inputSD').is(":checked"),
                "inputHD": $('#inputHD').is(":checked"),
                "inputAutoHLS": $('#inputAutoHLS').is(":checked"),
                "inputAutoMP4": $('#inputAutoMP4').is(":checked"),
                "inputAutoWebm": $('#inputAutoWebm').is(":checked"),
                "inputAutoAudio": $('#inputAutoAudio').is(":checked"),
                "title": $('#title').val(),
                "description": $('#description').val(),
                "categories_id": $('#categories_id_upload').val(),
                "callback": $('#callback').val(),
                "usergroups_id": $(".usergroups_id:checked").map(function () {
                    return $(this).val();
                }).get(),
                PHPSESSID: PHPSESSID
            };
        }

    }).bind('fileuploaddone', function (e, data) {
        //console.log(e);
        //console.log(data);

        console.log('fileuploaddone', data.result.files);
        $.ajax({
            url: 'view/jquery-file-upload/server/php/fileuploadchunkdone.php?PHPSESSID=' + PHPSESSID,
            data: {
                "file": data.result.files[0].name,
                "audioOnly": $('#inputAudioOnly').is(":checked"),
                "spectrum": $('#inputAudioSpectrum').is(":checked"),
                "webm": $('#inputWebM').is(":checked"),
                "override_status": $('#override_status').val(),
                "videos_id": $('#videos_id').val(),
                "update_video_id": $('#update_video_id').val(),
                "inputHLS": $('#inputHLS').is(":checked"),
                "inputLow": $('#inputLow').is(":checked"),
                "inputSD": $('#inputSD').is(":checked"),
                "inputHD": $('#inputHD').is(":checked"),
                "inputAutoHLS": $('#inputAutoHLS').is(":checked"),
                "inputAutoMP4": $('#inputAutoMP4').is(":checked"),
                "inputAutoWebm": $('#inputAutoWebm').is(":checked"),
                "inputAutoAudio": $('#inputAutoAudio').is(":checked"),
                "title": $('#title').val(),
                "description": $('#description').val(),
                "categories_id": $('#categories_id_upload').val(),
                "releaseDate": $('#releaseDate').val(),
                "callback": $('#callback').val(),
                "timezone": timezone,
                "usergroups_id": $(".usergroups_id:checked").map(function () {
                    return $(this).val();
                }).get(),
                PHPSESSID: PHPSESSID
            },
            type: 'post',
            success: function (response) {
                $('#videos_id').val('');
                console.log(response);
            }
        });
    });
});
async function createVideo() {
    var editorEnabled = isEditorEnabled();
    if(!editorEnabled){
        console.log("Not create video");
        return false;
    }
    console.log("Form submit handler called");
    modal.showPleaseWait();
    try {
        var title = $('#title').val();
        if(empty(title)){
            title = selectedFileName.replace(/\.[^/.]+$/, "");
        }
        const response = await $.ajax({
            url: webSiteRootURL + 'objects/videoAddNew.json.php',
            type: 'POST',
            data: {
                user: $('#user').val(),
                pass: $('#pass').val(),
                title: title,
                description: $('#description').val(),
                categories_id: $('#categories_id_upload').val(),
                videoGroups: $(".usergroups_id:checked").map(function () {
                    return $(this).val();
                }).get(),
            }
        });

        console.log("AJAX Success:", response);
        // Return the AJAX response
        modal.hidePleaseWait();
        return response;
    } catch (error) {
        modal.hidePleaseWait();
        //avideoToastError('Error occurred during AJAX request.');
        console.error("AJAX Error", error);
        // Handle the error here and optionally return or throw an error
        return false;
    }
}

function editVideos(videos_id) {
    var url = webSiteRootURL + 'view/managerVideosLight2.php?videos_id=' + videos_id;
    url += '&user=' + $('#user').val();
    url += '&pass=' + $('#pass').val();
    avideoModalIframe(url);
}