jQuery.noConflict();

jQuery(document).ready(function($) {
    //  Manual alerts refresh
    $('#unreadAlerts_menu').on('click', function(event) {
        event.preventDefault();

        $.get('xmlhttp.php?action=getNewAlerts&method=ajax', function(data)
        {
            if (!data)
            {
                $('#unreadAlerts_menu_popup').html(myalerts_empty_listing);
            }
            else
            {
                $('#unreadAlerts_menu_popup').html(data);
            }
        });
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