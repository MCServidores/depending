{######################## MASTER ########################}
{% extends "layout.tpl" %}

{######################## Title ########################}
{% block title %} {{title}} {% endblock %}

{######################## MODULES ########################}
{% block modules %}
	{% if acl.isLogin == false %}
		{% include "blocks/modules.tpl" %} 
	{% endif %}
{% endblock %}

{######################## Content ########################}
{% block content %}
	{% if user is not empty %}
		{% include "blocks/import.tpl" %} 
	{% endif %}
{% endblock %}