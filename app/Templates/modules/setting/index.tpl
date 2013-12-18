{######################## MASTER ########################}
{% extends "layout.tpl" %}

{######################## Title ########################}
{% block title %} {{title}} {% endblock %}

{######################## Content ########################}
{% block sidebar_left %} {% include "blocks/sidebar/menu.tpl" %} {% endblock %}
{% block content %} 
	{% if content is not empty %}
	<h3>{{ content.title }}</h3>
	<hr>

	{% if content.error %}
	   <div class="alert alert-error"><a href="#" class="close" data-dismiss="alert">&times;</a>{{ content.error|raw }}</div>
	{% endif %}
		{% if content.isStatic is not empty %}
			{% for itemData in content.dataStatic %}
			<p class="row-fluid"><span class="span2">{{itemData.label}}</span><strong>{{ itemData.content }}</strong><p>
			{% endfor %}
		{% else %}
		<form method="POST" action="{{ currentUrl }}">
			{% if content.otherInput is not empty %}
				{% set input = content.otherInput %}
				<p class="row-fluid"><span class="span2">Id</span><strong>{{ user.AdditionalData.id }}</strong><p>
				<p class="row-fluid"><span class="span2">Username</span><strong>{{ user.AdditionalData.login }}</strong><p>
				<p><button name="{{ input.name }}" type="{{ input.type }}" placeholder="{{ input.placeholder }}" class="btn" value="{{ input.value }}"><i class="{{ input.class }}"></i> {{ input.text }}</button></p>
			{% else %}
				{% for input in content.inputs %}
					{% if input.type == "text" or input.type == "password" %}
					<p><input name="{{ input.name }}" type="{{ input.type }}" placeholder="{{ input.placeholder }}" class="span{{ input.size }}" value="{{ input.value }}"></p>
					{% elseif input.type == "textarea" %}
					<p><textarea name="{{ input.name }}" placeholder="{{ input.placeholder }}" class="span{{ input.size }}">{{ input.value }}</textarea></p>
					{% elseif input.type == "radio" %}
					<p>{{ input.placeholder }} 
						{% for option in input.options %}
						<label class="checkbox"><input name="{{ input.name }}[]" type="radio" placeholder="" value="{{ option.value }}" {{ option.value == input.value ? 'checked' : '' }}> {{ option.label }}</label>
						{% endfor %}
					</p>
					{% endif %}
				{% endfor %}
				<hr>
				<button type="submit" class="btn btn-main">Update</button>
			{% endif %}
		</form>
		{% endif %}
	{% else %}
	<div class="alert alert-info"><center>Pick a menu on left sidebar to update some setting</center></div>
	{% endif %}
{% endblock %}