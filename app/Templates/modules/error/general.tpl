{######################## MASTER ########################}
{% extends "layout.tpl" %}

{######################## Title ########################}
{% block title %} {{title}} {% endblock %}

{######################## Content ########################}
{% block content %} 

	<div class="row-fluid">
		<div class="span6 offset3">
			<div class="alert alert-error">
				<h3>{{ title }}</h3>
				<p>{{ content.message|raw }}</p>
				<button class="btn" onClick="javascript:window.history.back();">Back</button>
			</div>
			<center><img src="/asset/img/y-u-no-guy.jpg"/></center>
		</div>
	</div>

{% endblock %}