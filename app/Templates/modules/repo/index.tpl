{######################## MASTER ########################}
{% extends "layout.tpl" %}

{######################## Title ########################}
{% block title %} {{title}} {% endblock %}

{######################## Content ########################}
{% block content %}
	{% if repo is not empty %}
		{% include "blocks/project.tpl" %} 
	{% endif %}
{% endblock %}