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
                setUnreadOnlyProxy = $.proxy(this.setUnreadOnly, this),
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
            bodySelector.on("click", "#unreadOnlyCheckbox", setUnreadOnlyProxy);

            var refreshHeader = (typeof myAlertsAutoRefreshHdrInt !== 'undefined' && myAlertsAutoRefreshHdrInt > 0);

            if (typeof myalerts_autorefresh !== 'undefined' && myalerts_autorefresh > 0
                &&
                // Only autorefresh if we're on the first page of alerts, otherwise
                // we could interrupt and confuse users who are paging through their
                // alerts.
                typeof page !== 'undefined' && page == 1
               ) {
                refreshHeader = false; // Don't hit the server twice unnecessarily - we update the header here anyway,
                                       // albeit at a potentially different frequency.
                window.setInterval(function () {
                    $.get(urlGetLatest, function (data) {
                        $('#latestAlertsListing').html(data.template);
                        MybbStuff.MyAlerts.prototype.updateVisibleCounts(data.unread_count_fmt, data.unread_count);
                    });
                }, myalerts_autorefresh * 1000);
            }

            if (refreshHeader) {
                window.setInterval(function () {
                    $.get('xmlhttp.php?action=get_num_unread_alerts', function (data) {
                        MybbStuff.MyAlerts.prototype.updateVisibleCounts(data.unread_count_fmt, data.unread_count);
                    })
                }, myAlertsAutoRefreshHdrInt * 1000);
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
                        $('#myalerts_alerts_modal #alerts_content').html(data['template']);
                        MybbStuff.MyAlerts.prototype.updateVisibleCounts(0, 0);
                    }
                });
            }
        }

        module.prototype.getLatestAlerts = function getLatestAlerts(event) {
            event.preventDefault();
            $.get(this.urlGetLatest, function (data) {
                $('#latestAlertsListing').html(data.template);
                MybbStuff.MyAlerts.prototype.updateVisibleCounts(data.unread_count_fmt, data.unread_count);
            });
        };

        module.prototype.stripParenAppendix = function stripParenAppendix(str) {
            if (str[str.length - 1] == ')') {
                let openParenPos = str.lastIndexOf('(');
                if (openParenPos >= 0) {
                    str = str.substring(0, openParenPos);
                }
            }

            return str;
        }

        module.prototype.updateVisibleCounts = function updateVisibleCounts(unread_count_fmt, unread_count) {
                // Update the header
                let $hdr_alerts_el = $('.alerts a');
                if ($hdr_alerts_el.length) {
                    let hdr_alerts_text = $hdr_alerts_el.html();
                    $hdr_alerts_el.html(MybbStuff.MyAlerts.prototype.stripParenAppendix(hdr_alerts_text) + '(' + unread_count_fmt + ')');
                    if (unread_count == 0) {
                        $('.alerts').removeClass('alerts--new');
                    } else if (!$('.alerts').hasClass('alerts--new')) {
                        $('.alerts').addClass('alerts--new');
                    }
                }

                // Update the browser window's title
                let title_bare = MybbStuff.MyAlerts.prototype.stripParenAppendix(window.document.title);
                if (unread_count > 0) {
                    window.document.title = title_bare + '(' + unread_count_fmt + ')';
                } else {
                    window.document.title = title_bare;
                }

                // Update the UCP sidebar item "View Alerts"
                let sb_text = $('.usercp_nav_myalerts strong').html();
                if (!sb_text) {
                    sb_text = $('.usercp_nav_myalerts').html();
                }
                if (sb_text) {
                    sb_text_bare = MybbStuff.MyAlerts.prototype.stripParenAppendix(sb_text);
                    if (unread_count > 0) {
                        $('.usercp_nav_myalerts').html('<strong>' + sb_text_bare + '(' + unread_count_fmt + ')</strong>');
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

        module.prototype.markReadOrUnreadAlert = function markReadOrUnreadAlert(event, self, markRead) {
            event.preventDefault();

            var button = $(event.currentTarget),
                offset = markRead ? 15 : 17,
                inPopup = button.attr("id").substring(0, 6) == 'popup_';

            if (inPopup) {
                offset += 6;
            }

            var alertId = button.attr("id").substring(offset);

            $.getJSON('xmlhttp.php?action='+(markRead ? 'myalerts_mark_read' : 'myalerts_mark_unread'), {
                accessMethod: 'js',
                id: alertId,
                my_post_key: my_post_key
            }, function (data) {
                if (data.success) {
                    if (markRead) {
                        remClass = 'alert--unread';
                        addClass = 'alert--read';
                    } else {
                        remClass = 'alert--read';
                        addClass = 'alert--unread';
                    }
                    $($('#markread_alert_'      +alertId).parents('.alert').get(0)).removeClass(remClass).addClass(addClass);
                    $($('#popup_markread_alert_'+alertId).parents('.alert').get(0)).removeClass(remClass).addClass(addClass);
                    $('#markread_alert_'  +alertId).toggleClass('hidden');
                    $('#markunread_alert_'+alertId).toggleClass('hidden');
                    $('#popup_markread_alert_'  +alertId).toggleClass('hidden');
                    $('#popup_markunread_alert_'+alertId).toggleClass('hidden');
                    MybbStuff.MyAlerts.prototype.updateVisibleCounts(data.unread_count_fmt, data.unread_count)

                    // If we're marking an alert read and showing only unread in the modal,
                    // then make sure we hide the newly-read alert.
                    let cbxOnlyUnread = document.getElementById('unreadOnlyCheckbox');
                    if (cbxOnlyUnread && cbxOnlyUnread.checked && markRead) {
                        $.get(self.urlGetLatest+'&modal=1', function (data) {
                            $('#myalerts_alerts_modal #alerts_content').html(data['template']);
                        });
                    }
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
            MybbStuff.MyAlerts.prototype.markReadOrUnreadAlert(event, this, true);
        }

        module.prototype.markUnreadAlert = function markUnreadAlert(event) {
            MybbStuff.MyAlerts.prototype.markReadOrUnreadAlert(event, this, false);
        }

        module.prototype.setUnreadOnly = function setUnreadOnly(event) {
            Cookie.set('myalerts_unread_only', event.currentTarget.checked ? '1' : '0');
            $.get(this.urlGetLatest+'&modal=1', function (data) {
                $('#myalerts_alerts_modal #alerts_content').html(data['template']);
            });
        }

        return module;
    })(window, jQuery);

    $(document).ready(function () {
        var myalerts = new MybbStuff.MyAlerts();
    });
})(jQuery, window, document, my_post_key);
