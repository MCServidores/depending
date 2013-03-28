$(document).ready(function(){
	{% if alertMessage is not empty %}
	$('.notifications').notify({

		{% if alertType is not empty %}
		type: '{{ alertType }}',
		{% else %}
		type: 'bangTidy',
		{% endif %}

		message: {html: "{{ alertMessage|raw }}" },

		{% if alertTimeout is not empty %}
		fadeOut: { enabled: true, delay: {{ alertTimeout }} },
		{% endif %}

	}).show();
	{% endif %}

	{% if parseCode == true %}
	var codeParseable = document.getElementsByClassName('codeParseable');

	for (i=0;i<codeParseable.length;i++) {
		var codeParseableElem = codeParseable[i];
		var editor = CodeMirror.fromTextArea(codeParseableElem, {
			lineNumbers: false
		});
		editor.setOption('theme', 'monokai');
	}
	{% endif %}

	// Convinience reusable AJAX
	var callInternalProvider = function(url,btnHandler,errorHandler,successHandler,postData,resultContainer) {
		var thisBtn = btnHandler;
		thisBtn.button('loading');

		if (typeof resultContainer !== 'undefined') {
			resultContainer.html('<center><i class="icon-spinner icon-spin icon-3x"></i></center>');
		}

		$.ajaxSetup({ error: function(jqXHR, textStatus, errorThrown){
				if (typeof resultContainer !== 'undefined') {
					resultContainer.html('<div class="alert alert-error"><strong>'+errorThrown+'</strong><br/>Sorry, something goes really wrong. Please try again later.</div>');
				}

				thisBtn.button('reset');
			} 
		})

		// Call the provider
		$.ajax({
		  type:"POST",
		  url: url,
		  data: postData,
		}).done(function(data) {
			thisBtn.button('reset');

			if (data.success) {
				successHandler(data);
			} else {
				errorHandler(data);
			}
		});
	};

	// Deps status toggle
	$('#deps-status').click(function(){
		$(this).popover();
	})

	// Log refresher
	$('#refresh-log').click(function(){
		var url = $(this).attr('href');
		var icon = $(this).children('i');
		icon.removeClass('icon').addClass('icon-spin');
		setTimeout(function(){
			window.location.href = url;
		},3000);
		return false;
	})

	// Log details toogle
	$('.has-log').click(function(){
		var resultContainer = $('#log-details');
		var buildUrl = '/build';
		var btnHandler = $(this);
		var statusLabel = $(this).parent().nextAll('td:last').find('label');
		var previousLogLabel = 'label-success';
		var previousLogText = statusLabel.html();

		if (statusLabel.html() == 'scheduled') {
			previousLogLabel = 'label-warning';
			statusLabel.removeClass('label-warning').addClass('label-info');
			statusLabel.html('running...');
		}

		var successHandler = function(data) {
			btnHandler.removeClass('c-grey c-green c-yellow c-red');

			statusLabel.removeClass('label-info').addClass('label-success');
			statusLabel.html('finished');

			switch (data.status) {
				case 3:
					btnHandler.addClass('c-green');
					break;

				case 2:
					btnHandler.addClass('c-yellow');
					break;

				case 1:
					btnHandler.addClass('c-red');
					break;

				default:
					btnHandler.addClass('c-grey');
					statusLabel.removeClass('label-info label-success').addClass('label-warning');
					statusLabel.html('scheduled');
					break;
			}

			resultContainer.html(data.html);
		};
		var errorHandler = function(data) {
			statusLabel.removeClass('label-info').addClass(previousLogLabel);
			statusLabel.html(previousLogText);
			resultContainer.html('<div class="alert alert-error">Sorry, something goes really wrong. Please try again later.</div>');
		};
		var logData = {'id':btnHandler.attr('data-log')};

		callInternalProvider(buildUrl,btnHandler,errorHandler,successHandler,logData,resultContainer);
	})

	// Import loader section
	var enableHookToggle = function() {
		var enablerBtn = $('.btn-enable-hook');
		var disablerBtn = $('.btn-disable-hook');

		enablerBtn.click(function(){
			var EnableUrl = '/setting/enable';
			var btnHandler = $(this);
			var successHandler = function(data) {
				// Switch this button to disable
				btnHandler.removeClass('btn-success btn-enable-hook').addClass('btn-danger btn-disable-hook');
				btnHandler.text('Disable');

				// Enable the hook functionalities
				enableHookToggle();
			};
			var errorHandler = function(data) {};
			var hookData = {'repo':btnHandler.attr('data-repo')};

			callInternalProvider(EnableUrl,btnHandler,errorHandler,successHandler,hookData);
		})

		disablerBtn.click(function(){
			var DisableUrl = '/setting/disable';
			var btnHandler = $(this);
			var successHandler = function(data) {
				// Switch this button to disable
				btnHandler.removeClass('btn-danger btn-disable-hook').addClass('btn-success btn-enable-hook');
				btnHandler.text('Enable');

				// Enable the hook functionalities
				enableHookToggle();
			};
			var errorHandler = function(data) {};
			var hookData = {'repo':btnHandler.attr('data-repo')};

			callInternalProvider(DisableUrl,btnHandler,errorHandler,successHandler,hookData);
		})
	};

	enableHookToggle();

	$('#loader-btn').click(function(){
		var resultContainer = $('#loader-result');
		var ImportUrl = '/home/import';
		var btnHandler = $(this);
		var successHandler = function(data) {
			resultContainer.html(data.html);

			// Enable the hook functionalities
			enableHookToggle();
		};
		var errorHandler = function(data) {
			console.log(data);
			resultContainer.html('<div class="alert alert-error">Sorry, something goes really wrong. Please try again later.</div>');
		};

		callInternalProvider(ImportUrl,btnHandler,errorHandler,successHandler,{},resultContainer);
	})
})