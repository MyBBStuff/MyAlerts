;
(function ($, window, document, my_post_key, undefined) {
    this.MybbStuff = this.MybbStuff || {};

    this.MybbStuff.MyAlerts = (function MyAlertsModule(window, $) {
        var module = function MyAlerts() {
            var unreadAlertsProxy = $.proxy(this.getUnreadAlerts, this),
                deleteAlertProxy = $.proxy(this.deleteAlert, this),
                bodySelector = $("body");

            bodySelector.on("click", "#getUnreadAlerts", unreadAlertsProxy);

            bodySelector.on("click", ".deleteAlertButton", deleteAlertProxy);

            if (typeof myalerts_autorefresh !== 'undefined' && myalerts_autorefresh > 0) {
                window.setInterval(function () {
                    $.get('xmlhttp.php?action=getNewAlerts', function (data) {
                        $('#latestAlertsListing').prepend(data);
                    });
                }, myalerts_autorefresh * 1000);
            }

            if (typeof unreadAlerts !== 'undefined' && unreadAlerts > 0) {
                document.title = document.title + ' (' + unreadAlerts + ')';
            }
        };

        module.prototype.getUnreadAlerts = function getUnreadAlerts(event) {
            event.preventDefault();
            $.get('xmlhttp.php?action=getNewAlerts', function (data) {
                $('#latestAlertsListing').prepend(data);
            });
        };

        module.prototype.deleteAlert = function deleteAlert(event) {
            event.preventDefault();

            var deleteButton = $(event.currentTarget),
                alertId = deleteButton.attr("id").substring(13);

            $.getJSON('xmlhttp.php?action=myalerts_delete', {
                accessMethod: 'js',
                id: alertId,
                my_post_key: my_post_key
            }, function (data) {
                if (data.success) {
                    deleteButton.parents('tr').get(0).remove();
                }
                else {
                    for (var i = 0; i < data.errors.length; ++i) {
                        console.log(data.errors[i]);
                    }
                    alert(data.errors[0]);
                }
            });

            return false;
        };

        return module;
    })(window, jQuery);

    $(document).ready(function () {
        var myalerts = new MybbStuff.MyAlerts();
    });
})(jQuery, window, document, my_post_key);



