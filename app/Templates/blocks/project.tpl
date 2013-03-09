<div class="span6 offset3" id="project-container">
	<h3 class="main-text"><i class="icon {{ repo.IsPackage|toIcon }}"></i> <a href="{{ repo.UrlHtml }}">{{ repo.FullName }}</a> 
	<span class="pull-right">
	{% if isAllowed %}
	<a href="#!" id="deps-status" class="has-tip" data-toggle="popover" data-html="true" data-placement="bottom" data-content="<div class='alert alert-info'><small>Copy paste below text</small><input type='text' value='{{ repo.Rid|toStatus }}' class='input-medium'/></div>" data-original-title="Get image status">
	{% endif %}
	<img src="/{{ repo.FullName }}.png" />
	{% if isAllowed %}
	</a>
	{% endif %}
	</span><br class="clear"/></h3>
	<h4><i class="icon icon-user"></i> <a href="/{{ owner.Name }}" class="btn-link">{{ owner.Name }}</a> 
	{% if isAllowed %}
	<span class="pull-right">{% if repo.Status == 1 %}
			<button class="btn btn-mini btn-danger btn-disable-hook" data-loading-text="<i class='icon-spinner icon-spin'></i>" data-repo="{{ repo.FullName }}">Disable</button>
			{% else %}
			<button class="btn btn-mini btn-success btn-enable-hook" data-loading-text="<i class='icon-spinner icon-spin'></i>" data-repo="{{ repo.FullName }}">Enable</button>
			{% endif %}
	</span>
	{% endif %}
	</h4>

	<br/>
	<p><blockquote>{{ repo.Description }}</blockquote></p>
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
	<div id="log-details">
	{% if buildHtml is not empty %}
	{{ buildHtml|raw }}
	{% endif %}
	</div>
</div>