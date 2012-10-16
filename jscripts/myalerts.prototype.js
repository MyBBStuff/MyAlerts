Event.observe(window, 'load', function() {
	Event.observe('unreadAlerts_menu', 'click', function(e) {
		Event.stop(e);
		var popup_id = e.target.identify() + '_popup';
		Effect.toggle(popup_id, 'blind', {
			afterFinish: function() {
				var toMarkRead = new Array;
				$$('[id^="alert_row_popup_"]').each(function(s, index) {
					toMarkRead.push(s.readAttribute('id').substr(16));
				});

				new Ajax.Request('xmlhttp.php?action=markRead',
				{
					method:'get',
					parameters: {
						my_post_key: my_post_key,
						toMarkRead: Object.toJSON(toMarkRead),
						js_type: 'prototype'
					},
					onSuccess: function() {},
					onFailure: function() {}
				});
			}
		});
	});

	Event.observe('getUnreadAlerts', 'click', function(e) {
		Event.stop(e);
		new Ajax.Request('xmlhttp.php?action=getNewAlerts',
		{
			method:'get',
			onSuccess: function(transport) {
				Element.insert('latestAlertsListing', { 'top': transport.responseText })
			},
			onFailure: function() {}
		});
	});

	$$('.deleteAlertButton').invoke('observe', 'click', function(e) {
		Event.stop(e);
		var deleteButton = $(this);
		console.log(deleteButton);

		new Ajax.Request(deleteButton.readAttribute('href'),
		{
			method: 'get',
			parameters: {accessMethod: 'js'},
			onSuccess: function(transport, json) {
				var data = transport.responseJSON;
				if (data['success'])
				{
					deleteButton.up('tr').remove();
					if (data['template'])
					{
						$('latestAlertsListing').replace(data['template']);
					}
				}
				else
				{
					alert(data['error']);
				}
			}
		});
	});

	if (typeof myalerts_autorefresh !== 'undefined' && myalerts_autorefresh > 0)
	{
		window.setInterval(function() {
			new Ajax.Request('xmlhttp.php?action=getNewAlerts',
			{
				method:'get',
				onSuccess: function(transport) {
					Element.insert('latestAlertsListing', { 'top': transport.responseText })
				},
				onFailure: function() {}
			});
		}, myalerts_autorefresh * 1000);
	}

	if (typeof unreadAlerts !== 'undefined' && unreadAlerts > 0)
	{
		document.title = document.title + ' (' + unreadAlerts + ')';
	}
});