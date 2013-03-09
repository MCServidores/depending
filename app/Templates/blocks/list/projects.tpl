<table class="table table-hover">
	<thead>
	<h4>{{ listTitle }} ({{ pagination.totalText }})
	{% if pagination is not empty and pagination.data is not empty %}
	<span class="pull-right"><small>Page {{ pagination.currentPage }} of {{ pagination.totalPage }}</small></span>
	{% endif %}
	</h4>
	</thead>
	<tbody>
	{% if repos is not empty %}
	{% for repo in repos %}

	<tr>
		<td><i class="icon {{ repo.IsPackage|toIcon }}"></i> <a href="/{{ repo.FullName }}"  class="btn-link"><strong>{{ repo.FullName }}</strong></a></td>
		<td class="span2">
			<i class="icon-circle{{ repo.Rid|isGreenStatus }} has-tip" data-original-title="Up to date"></i>
			<i class="icon-circle{{ repo.Rid|isYellowStatus }} has-tip" data-original-title="Need to date"></i>
			<i class="icon-circle{{ repo.Rid|isRedStatus }} has-tip" data-original-title="Out to date"></i>
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

{% if pagination is not empty and pagination.data is not empty %}
<div class="pagination">
  <ul>
    <li><a href="{{ currentQueryUrl }}page={{ pagination.previousPage }}"><i class="icon icon-backward"></i></a></li>
    {% for paging in pagination.pages %}
    <li class="{{ paging.class }}"><a href="{{ currentQueryUrl }}page={{ paging.number }}">{{ paging.number }}</a></li>
    {% endfor %}
    <li><a href="{{ currentQueryUrl }}page={{ pagination.nextPage }}"><i class="icon icon-forward"></i></a></li>
  </ul>
</div>
{% endif %}