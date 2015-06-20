;
(function ($, window, document, my_post_key, undefined) {
    this.MybbStuff = this.MybbStuff || {};

    this.MybbStuff.MyAlerts = (function MyAlertsModule(window, $) {
        var module = function MyAlerts() {
            $("body").on("click", "#getUnreadAlerts", this.getUnreadAlerts);

            $("body").on("click", ".deleteAlertButton", this.deleteAlert).bind(this);

            if (typeof myalerts_autorefresh !== 'undefined' && myalerts_autorefresh > 0)
            {
                window.setInterval(function() {
                    $.get('xmlhttp.php?action=getNewAlerts', function(data) {
                        $('#latestAlertsListing').prepend(data);
                    });
                }, myalerts_autorefresh * 1000);
            }

            if (typeof unreadAlerts !== 'undefined' && unreadAlerts > 0)
            {
                document.title = document.title + ' (' + unreadAlerts + ')';
            }
        };

        module.prototype.getUnreadAlerts = function getUnreadAlerts(event) {
            event.preventDefault();
            $.get('xmlhttp.php?action=getNewAlerts', function(data) {
                $('#latestAlertsListing').html(data.template);
            });
        };

        module.prototype.deleteAlert = function deleteAlert(event) {
            event.preventDefault();

            var deleteButton = $(event.currentTarget),
                alertId = deleteButton.attr("id").substring(13);

            $.getJSON('xmlhttp.php?action=myalerts_delete', {accessMethod: 'js', id: alertId, my_post_key: my_post_key}, function(data) {
                if (data.success)
                {
                    deleteButton.parents('tr').get(0).remove();
                    if (data.template)
                    {
                        $('#latestAlertsListing').html(data.template);
                    }
                }
                else
                {
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

    $(document).ready(function() {
        var myalerts = new MybbStuff.MyAlerts();
    });
})(jQuery, window, document, my_post_key);



