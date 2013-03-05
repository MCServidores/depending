<div class="span6 offset3" id="loader-container">
	<h3 class="main-text">Import Project</h3>
	<p>Click bellow button to start import and synchronize <strong>Depending</strong> with your Github projects</p>
	<hr/>
	<button class="btn btn-main btn-large" id="loader-btn" data-loading-text="<i class='icon-spin icon-refresh'></i> Synchronizing..."><i class="icon icon-refresh" id="loader-img"></i> Synchronize all of my projects</button>
	<hr/>
	<div id="loader-result">
	{% if repos is not empty %}
		{% include "blocks/list/repo.tpl" %}
	{% endif %}
	</div>
</div>