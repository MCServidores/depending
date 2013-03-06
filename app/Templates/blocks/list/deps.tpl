<table class="table table-hover">
	<tbody>
	{% if deps is not empty %}
	{% for dep in deps %}

	<tr>
		<td><i class="icon icon-inbox"></i> <a href="{{ dep.vendor|toPackagist }}" target="_blank" class="btn-link"><strong>{{ dep.vendor }}</strong></a></td>
		<td class="span1">
			<label class="label label-info">{{ dep.version }}</label>
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