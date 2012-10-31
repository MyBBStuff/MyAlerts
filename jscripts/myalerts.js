jQuery.noConflict();

jQuery(document).ready(function($)
{
	$('body').on({
		click: function(event)
		{
			event.preventDefault();
			var popup_id = $(this).attr('id') + '_popup';

			$('#' + popup_id).attr('top', $(this).height() + 'px').slideToggle('slow', function() {
				var toMarkRead = new Array;
				$('[id^="alert_row_popup_"]').each(function() {
					toMarkRead.push($(this).attr('id').substr(16));
				});

				$.get('xmlhttp.php?action=markRead', {
					my_post_key: my_post_key,
					toMarkRead: toMarkRead
				}, function(data) {

				});
			});
			return false;
		}
	}, '.myalerts_popup_hook');

	$('html').on('click', function() {
		$('.myalerts_popup:visible').hide();
	});

	$('#getUnreadAlerts').on('click', function(event) {
		event.preventDefault();
		$.get('xmlhttp.php?action=getNewAlerts', function(data) {
			$('#latestAlertsListing').prepend(data);
		});
	});

	$('.deleteAlertButton').on('click', function(event) {
		event.preventDefault();
		var deleteButton = $(this);

		$.getJSON(deleteButton.attr('href'), {accessMethod: 'js'}, function(data) {
			if (data['success'])
			{
				deleteButton.parents('tr').get(0).remove();
				if (data['template'])
				{
					$('#latestAlertsListing').html(data['template']);
				}
			}
			else
			{
				alert(data['error']);
			}
		});
	});

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

});
