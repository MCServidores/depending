<table class="table table-hover">
	{% if logs is not empty %}
	{% for log in logs %}

	<tbody>
	<tr>
		<td><i class="icon icon icon-exchange"></i> <a href="{{ log.CommitUrl }}"  target="blank" class="btn-link"><strong>{{ log.After|limitHash }}</strong></a></td>
		<td><small>{{ log.CommitMessage }}</small></td>
		<td class="span1"><label class="label label-{{log.Status|translateToSuccessText}}">{{ log.Status|translateToLogText }}</label></td>
	</tr>
	{% endfor %}
	{% else %}
	<tr>
		<div class="alert alert-error">Data not found</div>
	</tr>
	{% endif %}
	</tbody>
</table>