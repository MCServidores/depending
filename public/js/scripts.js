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

	$('a.has-tip').hover(function(){
		$(this).tooltip({
			placement: 'right',
			title: $(this).attr('data-original-title'),
		});
	},function(){
		$(this).tooltip('hide');
	});
});