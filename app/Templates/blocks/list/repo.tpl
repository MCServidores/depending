<table class="table table-hover">
	<thead>
	<h3 class="main-text">Your Repositories</h3>
	</thead>
	{% if repos is not empty %}
	{% for repo in repos %}

	<tbody>
	<tr>
		<td><i class="icon icon-github-sign"></i> <a href="{{ repo.UrlHtml }}" target="_blank"><strong>{{ repo.FullName }}</strong></a></td>
		<td class="span3">
			<a href="/{{ repo.FullName }}"><img src="/{{ repo.FullName }}.png" /></a>
		</td>
		<td class="span1">
			{% if repo.Status == 1 %}
			<button class="btn btn-mini btn-danger btn-disable-hook" data-loading-text="<i class='icon-spinner icon-spin'></i>" data-repo="{{ repo.FullName }}">Disable</button>
			{% else %}
			<button class="btn btn-mini btn-success btn-enable-hook" data-loading-text="<i class='icon-spinner icon-spin'></i>" data-repo="{{ repo.FullName }}">Enable</button>
			{% endif %}
		</td>
	</tr>
	{% endfor %}
	{% else %}
	<tr>
		<div class="alert alert-error">Data not found</div>
	</tr>
	{% endif %}
	</tbody>
</table>