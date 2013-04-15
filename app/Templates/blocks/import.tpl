<div class="span6 offset3" id="loader-container">
	<h3 class="main-text">Import Project</h3>
	<p>Click bellow button to start import and synchronize <strong>Depending</strong> with your Github projects</p>
	<hr/>

	<div class="accordion" id="importer">
	  <div class="accordion-group">
	    <div class="accordion-heading">
	      <a class="accordion-toggle" data-toggle="collapse" data-parent="#importer" href="#importer-all">
	      Synchronize All Projects
	      </a>
	    </div>
	    <div id="importer-all" class="accordion-body collapse in">
	      <div class="accordion-inner">
	        <button class="btn btn-main btn-large loader-btn" data-loading-text="<i class='icon-spin icon-refresh'></i> Synchronizing..."><i class="icon icon-refresh" id="loader-img"></i> Synchronize all of my projects</button>
	      </div>
	    </div>
	  </div>
	  <div class="accordion-group">
	    <div class="accordion-heading">
	      <a class="accordion-toggle" data-toggle="collapse" data-parent="#importer" href="#importer-partial">
	      Synchronize Partial Projects
	      </a>
	    </div>
	    <div id="importer-partial" class="accordion-body collapse">
	      <div class="accordion-inner">
			<!-- Additional buttons to fetch repo partially -->
	        <button class="btn btn-main loader-btn" data-partial="user" data-loading-text="<i class='icon-spin icon-refresh'></i> Synchronizing..."><i class="icon icon-refresh" id="loader-img"></i> Synchronize my own projects</button>
			<button class="btn btn-main loader-btn" data-partial="organizations" data-loading-text="<i class='icon-spin icon-refresh'></i> Synchronizing..."><i class="icon icon-refresh" id="loader-img"></i> Synchronize my organizations projects</button>
	      </div>
	    </div>
	  </div>
	</div>
	<hr/>

	<div id="loader-result">
	{% if repos is not empty %}
		{% include "blocks/list/repo.tpl" %}
	{% endif %}
	</div>
</div>