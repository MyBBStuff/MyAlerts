;
(function ($, window, document, my_post_key, undefined) {
    this.MybbStuff = this.MybbStuff || {};

    this.MybbStuff.MyAlerts = (function MyAlertsModule(window, $) {
        var module = function MyAlerts() {
            var latestAlertsProxy = $.proxy(this.getLatestAlerts, this),
                deleteAlertProxy = $.proxy(this.deleteAlert, this),
                markAllReadProxy = $.proxy(this.markAllRead, this),
                markReadAlertProxy = $.proxy(this.markReadAlert, this),
                markUnreadAlertProxy = $.proxy(this.markUnreadAlert, this),
                bodySelector = $("body");

            var urlGetLatest = (typeof myAlertsBcMode !== 'undefined' && myAlertsBcMode == '1')
              ? 'alerts.php?action=get_latest_alerts&ajax=1'
              : 'xmlhttp.php?action=getLatestAlerts';
            if (typeof pages !== 'undefined') {
                urlGetLatest += '&pages='+pages;
            }
            this.urlGetLatest = urlGetLatest;

            bodySelector.on("click", "#getLatestAlerts", latestAlertsProxy);
            bodySelector.on("click", ".deleteAlertButton", deleteAlertProxy);
            bodySelector.on("click", ".markAllReadButton", markAllReadProxy);
            bodySelector.on("click", ".markReadAlertButton", markReadAlertProxy);
            bodySelector.on("click", ".markUnreadAlertButton", markUnreadAlertProxy);

            if (typeof myalerts_autorefresh !== 'undefined' && myalerts_autorefresh > 0
                &&
                // Only autorefresh if we're on the first page of alerts, otherwise
                // we could interrupt and confuse users who are paging through their
                // alerts.
                typeof page !== 'undefined' && page == 1
               ) {
                window.setInterval(function () {
                    $.get(urlGetLatest, function (data) {
                        $('#latestAlertsListing').html(data.template);
                    });
                }, myalerts_autorefresh * 1000);
            }

            if (typeof unreadAlerts !== 'undefined' && unreadAlerts > 0) {
                document.title = document.title + ' (' + unreadAlerts + ')';
            }
        };

        module.prototype.markAllRead = function markAllRead(event) {
            event.preventDefault();
            let url = (typeof myAlertsBcMode !== 'undefined' && myAlertsBcMode == '1')
              ? 'alerts.php?action=mark_all_read&ajax=1'
              : 'xmlhttp.php?action=markAllRead';
            $.get(url+'&my_post_key='+my_post_key, function (data) {
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

        module.prototype.getLatestAlerts = function getLatestAlerts(event) {
            event.preventDefault();
            $.get(this.urlGetLatest, function (data) {
                $('#latestAlertsListing').html(data.template);
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

        module.prototype.markUnreadAlert = function markUnreadAlert(event) {
            event.preventDefault();

            var button = $(event.currentTarget),
                alertId = button.attr("id").substring(15);

            $.getJSON('xmlhttp.php?action=myalerts_mark_unread', {
                accessMethod: 'js',
                id: alertId,
                my_post_key: my_post_key
            }, function (data) {
                if (data.success) {
                    $(button.parents('tr').get(0)).removeClass('alert--read').addClass('alert--unread');
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
