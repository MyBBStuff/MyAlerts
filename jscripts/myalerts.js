window.MybbStuff = window.MybbStuff || {};

window.MybbStuff.MyAlerts = (function(window, $, undefined) {
    var module = function MyAlerts() {
        $("body").on("click", ".myalerts_popup_hook", this.openPopup);

        $("body").on("click", ".myalerts_popup", this.stopMultiSlide);


        $("body").on("click", "body:not('.myalerts_popup:visible')", this.closeVisiblePopup);

        $("body").on("click", "#getUnreadAlerts", this.getUnreadAlerts);

        $("body").on("click", ".deleteAlertButton", this.deleteAlert);

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

    module.prototype.openPopup = function openPopup(event) {
        event.preventDefault();
        var clickedElement = $(event.currentTarget),
            popup_id = "#" + clickedElement.attr("id") + "_popup";

        if ($(popup_id).length > 0) {
            $(popup_id).attr("top", clickedElement.height() + "px").slideToggle("fast", function() {
                var toMarkRead = [];
                $('[id^="alert_row_popup_"]').each(function() {
                    toMarkRead.push($(this).attr('id').substr(16));
                });

                $.get('xmlhttp.php?action=markRead', {
                    my_post_key: my_post_key,
                    toMarkRead: toMarkRead
                });
            });
        }
    };

    module.prototype.stopMultiSlide = function stopMultiSlide(event) {
        event.preventDefault();
    };

    module.prototype.closeVisiblePopup = function closeVisiblePopup(event) {
        $('.myalerts_popup:visible').hide();
    };

    module.prototype.getUnreadAlerts = function getUnreadAlerts(event) {
        event.preventDefault();
        $.get('xmlhttp.php?action=getNewAlerts', function(data) {
            $('#latestAlertsListing').prepend(data);
        });
    };

    module.prototype.deleteAlert = function deleteAlert(event) {
        event.preventDefault();
        var deleteButton = $(this);

        $.getJSON(deleteButton.attr('href'), {accessMethod: 'js'}, function(data) {
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
                alert(data.error);
            }
        });
    };

    return module;
}(window, jQuery));

var myalerts = new MybbStuff.MyAlerts();




