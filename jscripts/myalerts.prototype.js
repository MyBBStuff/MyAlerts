/*
 *    Prototype version of the MyAlerts JS - just for LeeFish :)
 *
 *    @author Euan T. <euan@euantor.com>
 */

$$('.myalerts_popup_hook').each(function(elmt)
{
	elmt.observe('click', function(ev)
	{
		Event.stop(ev);
		var popup_id = ev.target.identify() + '_popup';

		Effect.toggle(popup_id, 'blind');

		return false;
	});
});

if (typeof unreadAlerts !== 'undefined')
{
	if (unreadAlerts > 0)
	{
		document.title = document.title + ' (' + unreadAlerts + ')';
	}
}