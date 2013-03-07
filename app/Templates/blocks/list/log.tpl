<table class="table table-hover">
	<thead>
		<h6>Last five commits build</h6>
	</thead>
	<tbody>
	{% if logs is not empty %}
	{% for log in logs %}

	<tr>
		<td class="span1"><strong>{{ loop.index }}</strong></td>
		<td class="span1"><a href="#!" class="c-{{ log.Status|toStatusIcon }} has-tip has-log" data-loading-text="<i class='icon-spin icon-spinner'></i>" data-log="{{ log.Id }}" data-original-title="See build details"><strong><i class="icon-circle"></i></strong></a></td>
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