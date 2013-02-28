{######################## MASTER ########################}
{% extends "layout.tpl" %}

{######################## Title ########################}
{% block title %} {{title}} {% endblock %}

{######################## Content ########################}
{% block content %} 

	<div class="row">
		<div class="span4 offset4">

			<h3>{{title}}</h3>
			<hr>

			{% if acl.isContainGithubData == false %}
			<a href="/auth/registergithub" class="btn btn-large"><i class="icon-github-alt"></i> Register with Github</a>
			{% else %}
			{% if result.error %}
			   <div class="alert alert-error"><a href="#" class="close" data-dismiss="alert">&times;</a>{{ result.error|raw }}</div>
			{% endif %}
			<form method="POST" action="/auth/register">
				<input name="username" type="text" placeholder="Username" class="span6" value="{{ postData.username }}">
				<input name="email" type="text" placeholder="Email" class="span6" value="{{ postData.email }}">
				<input name="password" type="password" placeholder="Password" class="span6">
				<input name="cpassword" type="password" placeholder="Password Confirmation" class="span6">

				<hr>

				<button type="submit" class="btn btn-main">Register</button> 
				<a href="/auth/login" class="btn btn-link">Already have an account?</a>
			</form>
			{% endif %}

		</div>
	</div>

{% endblock %}