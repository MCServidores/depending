{######################## MASTER ########################}
{% extends "layout.tpl" %}

{######################## Title ########################}
{% block title %} {{title}} {% endblock %}

{######################## Content ########################}
{% block content %} 

	<div class="row-fluid">
		<div class="span4 offset4">

			<h3>{{title}}</h3>
			<hr>
			
			<a href="/auth/logingithub" class="btn btn-large"><i class="icon-github-alt"></i> Login with Github</a>
			<hr>

			{% if result.error %}
			   <div class="alert alert-error"><a href="#" class="close" data-dismiss="alert">&times;</a>{{ result.error|raw }}</div>
			{% endif %}
			<form method="POST" action="/auth/login">
				<input name="username" type="text" placeholder="Username/Email" class="span6" value="{{ postData.username }}">
				<input name="password" type="password" placeholder="Password" class="span6">

				<hr>

				<button type="submit" class="btn btn-main">Login</button> &nbsp;&nbsp;Or
				<a href="/auth/forgot" class="btn btn-link">Forgot password?</a>
			</form>
		</div>
	</div>

{% endblock %}