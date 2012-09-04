Event.observe(window, 'load', function() {
	Event.observe('unreadAlerts_menu', 'click', function(e) {
		Event.stop(e);
		var popup_id = e.target.identify() + '_popup';
		Effect.toggle(popup_id, 'blind');
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