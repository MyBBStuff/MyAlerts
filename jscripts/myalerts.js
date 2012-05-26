jQuery.noConflict();

jQuery(document).ready(function($) {
    $('#getUnreadAlerts').on('click', function(event) {
        event.preventDefault();

        $.get('xmlhttp.php?action=getNewAlerts', function(data) {
            $('#latestAlertsListing').prepend(data);
        });
    });
});