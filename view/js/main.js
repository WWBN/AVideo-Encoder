var modal;

/**
 * URL into hostname and path in javascript?
 * @param {type} href
 * @returns {Element|getLocation.l}
 * var l = getLocation("http://example.com/path");
console.debug(l.hostname)
>> "example.com"
console.debug(l.pathname)
>> "/path"
 */
var getLocation = function(href) {
    var l = document.createElement("a");
    l.href = href;
    return l;
};

$(function () {
   modal = modal || (function () {
    var pleaseWaitDiv = $("#pleaseWaitDialog");
    if (pleaseWaitDiv.length === 0) {
        pleaseWaitDiv = $('<div id="pleaseWaitDialog" class="modal fade"  data-backdrop="static" data-keyboard="false"><div class="modal-dialog"><div class="modal-content"><div class="modal-body"><h2>Processing...</h2><div class="progress"><div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%"></div></div></div></div></div></div>').appendTo('body');
    }

    return {
        showPleaseWait: function () {
            pleaseWaitDiv.modal();
        },
        hidePleaseWait: function () {
            pleaseWaitDiv.modal('hide');
        },
        setProgress: function (valeur) {
            pleaseWaitDiv.find('.progress-bar').css('width', valeur + '%').attr('aria-valuenow', valeur);
        },
        setText: function (text) {
            pleaseWaitDiv.find('h2').html(text);
        },
    };
})();
});