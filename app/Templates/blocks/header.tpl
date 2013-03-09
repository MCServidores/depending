<div class="navbar navbar-inverse navbar-fixed-top main-color">
  <div class="navbar-inner main-color">
    <div class="container-fluid">
      <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="brand brand-text" href="/"><b class="logo-main">D</b>epending
      <span style="font-size:14px;">
      <i class="icon-circle c-green"></i>
      <i class="icon-circle c-yellow"></i>
      <i class="icon-circle c-red"></i>
      </span>
      </a>
      <div class="nav-collapse collapse">
      	<div class="pull-right">
        {% if acl.isLogin == true %}
		<div class="dropdown pull-right">
			<div class="btn-group pull-right">
			<a class="btn btn-main" href="/{{ user.Name }}"><img src="{{ user.Avatar }}?s=18&d=retro"/> {{ user.Name }}</a>
			<a class="btn btn-main dropdown-toggle" data-toggle="dropdown" href="#"> <span class="caret"></span></a>
			<ul id="account" class="dropdown-menu" role="menu" aria-labelledby="drop1">
				<li><a href="/setting">Setting</a></li>
				<li><a href="/auth/logout">Logout</a></li>
			</ul>
			</div>
		</div>
		{% else %}
		<a href="/auth/login" class="btn btn-main pull-right"><i class="icon icon-github-alt"></i> Login</a>
		{% endif %}
		</div>
        <ul class="nav">
          <li class="{{ menuHomeActive }}"><a href="/home">Home</a></li>
          <li class="{{ menuProjectActive }}"><a href="/project">Projects</a></li>
        </ul>
      </div><!--/.nav-collapse -->
    </div>
  </div>
</div>