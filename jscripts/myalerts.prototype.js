Event.observe(window, 'load', function() {
	Event.observe('unreadAlerts_menu', 'click', function(e) {
		Event.stop(e);

		var popup_id = e.target.identify() + '_popup';

		console.log(popup_id);

		Effect.toggle(popup_id, 'blind');
	});
});

if (typeof unreadAlerts !== 'undefined')
{
	if (unreadAlerts > 0)
	{
		document.title = document.title + ' (' + unreadAlerts + ')';
	}
}