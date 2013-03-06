<div class="span6 offset3" id="project-container">
	<h3 class="main-text"><i class="icon icon-github-sign"></i> {{ repo.FullName }} <span class="pull-right"><img src="/{{ repo.FullName }}.png" /></span></h3>
	<hr/>
	<ul class="nav nav-tabs nav-stacked">
		{% if lastLog is not empty %}
		<li><a href="{{ lastLog.CommitUrl }}" target="_blank"><i class="icon icon-exchange"></i> {{ lastLog.After }}</a></li>
		<li><a href="{{ lastLog.CommitUrl }}" target="_blank"><i class="icon icon-quote-left"></i> {{ lastLog.CommitMessage}}</a></li>
		{% else %}
		<li><a href="#!"><i class="icon icon-exchange"></i> - </a></li>
		<li><a href="#!"><i class="icon icon-quote-left"></i> -</a></li>
		{% endif %}
	</ul>
	<hr/>
	{% include "blocks/tab.tpl" %}
</div>