;
(function ($, window, document, my_post_key, undefined) {
    this.MybbStuff = this.MybbStuff || {};

    this.MybbStuff.MyAlerts = (function MyAlertsModule(window, $) {
        var module = function MyAlerts() {
            var unreadAlertsProxy = $.proxy(this.getUnreadAlerts, this),
                deleteAlertProxy = $.proxy(this.deleteAlert, this),
                markAllReadProxy = $.proxy(this.markAllRead, this),
                markReadAlertProxy = $.proxy(this.markReadAlert, this),
                bodySelector = $("body");

            bodySelector.on("click", "#getUnreadAlerts", unreadAlertsProxy);

            bodySelector.on("click", ".deleteAlertButton", deleteAlertProxy);
            bodySelector.on("click", ".markAllReadButton", markAllReadProxy);
            bodySelector.on("click", ".markReadAlertButton", markReadAlertProxy);

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

        module.prototype.markAllRead = function markAllRead(event) {
            event.preventDefault();
            $.get('xmlhttp.php?action=markAllRead&my_post_key='+my_post_key, function (data) {
                if (data.error) {
                    $.jGrowl(data.error, {theme:'jgrowl_error'});
                } else {
                    $('#myalerts_alerts_modal tbody:first').html(data['template']);
                    var msg = $('.alerts a').html();
                    var appendix = ' (' + unreadAlerts + ')';
                    if (msg.length >= appendix.length && msg.substring(msg.length - appendix.length) == appendix) {
                        msg = msg.substring(0, msg.length - appendix.length);
                        $('.alerts a').html(msg + ' (0)');
                    }
                    if (window.document.title.length >= appendix.length && window.document.title.substring(window.document.title.length - appendix.length) == appendix) {
                        window.document.title = window.document.title.substring(0, window.document.title.length - appendix.length);
                    }
                    $('.alerts').removeClass('alerts--new');
                }
            });
        }

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

        module.prototype.markReadAlert = function markReadAlert(event) {
            event.preventDefault();

            var button = $(event.currentTarget),
                alertId = button.attr("id").substring(15);

            $.getJSON('xmlhttp.php?action=myalerts_mark_read', {
                accessMethod: 'js',
                id: alertId,
                my_post_key: my_post_key
            }, function (data) {
                if (data.success) {
                    $(button.parents('tr').get(0)).removeClass('alert--unread').addClass('alert--read');
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
