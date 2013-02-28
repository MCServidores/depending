<table class="table table-hover">
	<thead>
	<h4>{{ listTitle }} ({{ pagination.totalText }})
	{% if pagination is not empty and pagination.data is not empty %}
	<span class="pull-right"><small>Halaman {{ pagination.currentPage }} dari {{ pagination.totalPage }}</small></span>
	{% endif %}
	</h4>
	</thead>
	{% if users is not empty %}
	{% for pengguna in users %}

	<tbody>
	<tr>
		<td class="span1"><img src="{{ pengguna.Avatar }}?d=retro" class="img-polaroid" /></td>
		<td><a href="/user/profile/{{ pengguna.Uid }}"><strong>{{ pengguna.Name }}</strong></a><br/><blockquote><small>{{ pengguna.Signature|striptags }}</small></blockquote></td>
		<td class="span1"><button class="btn btn-mini disabled">Report</button></td>
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