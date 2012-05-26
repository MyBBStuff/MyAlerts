jQuery.noConflict();

jQuery(document).ready(function($) {
    //  Manual alerts refresh
    $('#getUnreadAlerts').on('click', function(event) {
        event.preventDefault();

        $.get('xmlhttp.php?action=getNewAlerts', function(data) {
            $('#latestAlertsListing').prepend(data);
        });
    });

    //  Automatic alerts refresh
    if (myalerts_autorefresh !== 0)
    {
        window.setInterval(function() {
            $.get('xmlhttp.php?action=getNewAlerts', function(data) {
                $('#latestAlertsListing').prepend(data);
            });
        }, (myalerts_autorefresh * 1000));
    }
});