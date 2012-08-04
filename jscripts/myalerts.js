jQuery.noConflict();

jQuery(document).ready(function($)
{
	$('body').on({
		click: function(event)
		{
			event.preventDefault();
			var popup_id = $(this).attr('id') + '_popup';
			console.log(popup_id);

			$('#' + popup_id).attr('top', $(this).height() + 'px').slideToggle('slow');
			return false;
		}
	}, '.myalerts_popup_hook');
});
