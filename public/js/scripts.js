$(function () {

	// PLACEHOLDER TIP
	$(':input').focusin(function(){
		if ($(this).attr('type') != 'submit') {
			$(this).tooltip({
				placement: 'right',
				title: $(this).attr('placeholder'),
			});
			$(this).tooltip('show');
		}
	});

	$(':input').focusout(function(){
		$(this).tooltip('hide');
	});

	$('i.has-tip,a.has-tip').tooltip({
		placement: 'right',
		title: $(this).attr('data-original-title'),
	});

	$('i.has-tip,a.has-tip').hover(function(){
		$(this).tooltip('show');
	},function(){
		$(this).tooltip('hide');
	});
});