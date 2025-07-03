<script src="<?php echo $global['webSiteRootURL']; ?>youtube-cookie-extension/script.js?2" type="text/javascript"></script>
<div class="panel-footer">
    <div class="container" style="margin-top: 40px;">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-youtube-play text-danger"></i> YouTube Cookie Status
                </h3>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label>
                        <i class="fa fa-puzzle-piece"></i> Extension status:
                    </label>
                    <span id="extensionStatus" class="label label-default">
                        <i class="fa fa-spinner fa-spin"></i> Checking...
                    </span>
                </div>
                <div class="form-group">
                    <label>
                        <i class="fa fa-lock"></i> YouTube cookies:
                    </label>
                    <span id="cookieStatus" class="label label-default">
                        <i class="fa fa-spinner fa-spin"></i> Checking...
                    </span>
                </div>
                <input type="hidden" id="youtubeCookie" name="youtubeCookie" value="">
            </div>
        </div>
    </div>
    <!-- Warning message -->
    <div class="alert alert-danger text-center" role="alert" style="margin-top: 50px;">
        <h4><strong>YouTube Authentication Deprecated</strong></h4>
        <p>
            Due to recent changes in YouTube's authentication policies, OAuth login is no longer supported for downloading videos via this application.
        </p>
        <p>
            We are currently working to restore the YouTube download feature and will provide a solution as soon as one is available.
        </p>
        <hr>
        <p>
            Thank you for your patience and understanding as we work on this issue.
        </p>
    </div>
</div>
