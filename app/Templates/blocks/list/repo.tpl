<table class="table table-hover">
	<thead>
	<h3 class="main-text">Your Repositories</h3>
	</thead>
	<tbody>
	{% if repos is not empty %}
	{% for repo in repos %}

	<tr>
		<td><i class="icon {{ repo.IsPackage|toIcon }}"></i> <a href="/{{ repo.FullName }}" class="btn-link"><strong>{{ repo.FullName }}</strong></a></td>
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