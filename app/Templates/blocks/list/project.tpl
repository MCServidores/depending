<table class="table table-hover">
	<tbody>
	{% if repos is not empty %}
	{% for repo in repos %}

	<tr>
		<td><i class="icon {{ repo.IsPackage|toIcon }}"></i> <a href="/{{ repo.FullName }}"  class="btn-link"><strong>{{ repo.FullName }}</strong></a></td>
		<td class="span3">
			<a href="/{{ repo.FullName }}"><img src="/{{ repo.FullName }}.png" /></a>
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