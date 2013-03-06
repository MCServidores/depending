<ul id="mainTab" class="nav nav-tabs">
	{% for tab in tabs %}
	<li class="{{ tab.liClass }}"><a href="#{{ tab.id }}" data-toggle="tab">{{ tab.link }}</a></li>
	{% endfor %}
	{% if tabOption is not empty %}
	<a href="{{ tabOption.href }}" class="btn btn-mini pull-right">{{ tabOption.text|raw }}</a>
	{% endif %}
</ul>

<div id="mainTabContent" class="tab-content">
	{% for tab in tabs %}
	<div class="tab-pane fade {{ tab.tabClass }}" id="{{ tab.id }}">
		{% if (tab.data is not empty) %}
		{{ tab.data|raw }}
		{% else %}
		<div class="well"><center><small>Data not found</small></center></div>
		{% endif %}
	</div>
	{% endfor %}
</div>