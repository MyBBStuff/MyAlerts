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

    //  Modal Box
    $('a[name="modal"]').on('click', function(event) {
        event.preventDefault();

        var target = $(this).attr('id');
        target += 'Box';

        $('#mask').css({
            'width': $(window).width(),
            'height': $(document).height()
        });

        $('#mask').fadeTo("slow", 0.8);

        var winH = $(window).height();
        var winW = $(window).width();
        $('#' + target).css('top', (winH / 2) - ($('#' + target).height() / 2));
        $('#' + target).css('left', (winW / 2) - ($('#' + target).width() / 2));
        $('#' + target).fadeIn(2000);

        $.get('xmlhttp.php?action=getNumUnreadAlerts', function(data) {
            $('#alertsModal').text(data);
        });
    });
    
    $('#mask').on('click', function ()
    {
        $(this).hide();
        $('.modalBox').hide();
    }); 
});