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
{% block content %} {{ content }} {% endblock %}