jQuery.noConflict();

jQuery(document).ready(function($)
{
    var unreadAlertsList = null;
    $('#unreadAlerts_menu').on('click', function(event) {
        event.preventDefault();

        if (!unreadAlertsList)
        {
            $.get('xmlhttp.php?action=getNewAlerts&method=ajax', function(data)
            {
                if (!data)
                {
                    $('#unreadAlerts_menu_popup').html(myalerts_empty_listing);
                    unreadAlertsList = 'No Alerts';
                }
                else
                {
                    $('#unreadAlerts_menu_popup').html(data);
                    unreadAlertsList = data;
                }
            });
            $(this).html('0');
        }
    });

    //  Automatic alerts refresh
    if (myalerts_autorefresh && (myalerts_autorefresh !== 0))
    {
        window.setInterval(function() {
            $.get('xmlhttp.php?action=getNewAlerts', function(data) {
                $('#latestAlertsListing').prepend(data);
            });
        }, (myalerts_autorefresh * 1000));
    }
});