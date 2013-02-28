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
				<p>{{ content.message }}</p>
				<button class="btn" onClick="javascript:window.history.back();">Back</button>
			</div>


		</div>
	</div>

{% endblock %}