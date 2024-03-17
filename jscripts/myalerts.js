;
(function ($, window, document, my_post_key, undefined) {
    this.MybbStuff = this.MybbStuff || {};

    this.MybbStuff.MyAlerts = (function MyAlertsModule(window, $) {
        var module = function MyAlerts() {
            var latestAlertsProxy = $.proxy(this.getLatestAlerts, this),
                deleteAlertProxy = $.proxy(this.deleteAlert, this),
                markAllReadProxy = $.proxy(this.markAllRead, this),
                markReadAlertProxy = $.proxy(this.markReadAlert, this),
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
            if (confirm(myalerts_modal_mark_all_read_confirm)) {
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
        }

        module.prototype.getLatestAlerts = function getLatestAlerts(event) {
            event.preventDefault();
            $.get(this.urlGetLatest, function (data) {
                $('#latestAlertsListing').html(data.template);
            });
        };

        module.prototype.stripParenAppendix = function stripParenAppendix(str) {
            if (str[str.length - 1] == ')') {
                let openParenPos = str.lastIndexOf(' (');
                if (openParenPos >= 0) {
                    str = str.substring(0, openParenPos);
                }
            }

            return str;
        }

        module.prototype.updateVisibleCounts = function updateVisibleCounts(unread_count_fmt, unread_count) {
                // Update the header
                $('.alerts a').html(MybbStuff.MyAlerts.prototype.stripParenAppendix($('.alerts a').html()) + ' (' + unread_count_fmt + ')');
                if (unread_count == 0) {
                    $('.alerts').removeClass('alerts--new');
                } else if (!$('.alerts').hasClass('alerts--new')) {
                    $('.alerts').addClass('alerts--new');
                }

                // Update the browser window's title
                let title_bare = MybbStuff.MyAlerts.prototype.stripParenAppendix(window.document.title);
                if (unread_count > 0) {
                    window.document.title = title_bare + ' (' + unread_count_fmt + ')';
                } else {
                    window.document.title = title_bare;
                }

                // Update the UCP sidebar item "View Alerts"
                let sb_text = $('.usercp_nav_myalerts strong').html();
                if (sb_text) {
                    sb_text_bare = MybbStuff.MyAlerts.prototype.stripParenAppendix(sb_text);
                    if (unread_count > 0) {
                        $('.usercp_nav_myalerts').html('<strong>' + sb_text_bare + ' (' + unread_count_fmt + ')</strong>');
                    } else {
                        $('.usercp_nav_myalerts').html(sb_text_bare);
                    }
                }
        }

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
                    MybbStuff.MyAlerts.prototype.updateVisibleCounts(data.unread_count_fmt, data.unread_count)
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
                    MybbStuff.MyAlerts.prototype.updateVisibleCounts(data.unread_count_fmt, data.unread_count)
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
