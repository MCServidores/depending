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