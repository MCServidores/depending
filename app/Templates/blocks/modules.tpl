<div class="row-fluid">
	<div class="span12">
		<center>
		<h1 class="main-text">Depending On
		<img src="/asset/img/logo-composer-transparent.png" style="width:180px;height:auto;"/>
		Should Be Fun!</h1>
		</center>
	</div>
</div>
<div class="push"></div>
<div class="row-fluid">
	<div class="span4">
		<h4><i class="icon-info-sign"></i> What</h4>
		<p><strong>Depending</strong> is an online tool to monitor your PHP project dependencies. It shows your project dependencies status, right away after you're commiting new code into your Github repository.</p><a href="http://getcomposer.org/" target="_blank" class="btn btn-small"><i class="icon icon-magic"></i> Get Composer</a> <a href="https://packagist.org/about" target="_blank" class="btn btn-small"><i class="icon icon-inbox"></i> About Packagist</a> 
	</div>

	<div class="span4">
		<h4><i class="icon-warning-sign"></i> Why</h4>
		<p>Using old composer package is bad. Most of composer package are updated all the time, and most of us did not aware of this changes. We need someone (or something) to remind us.</p>
	</div>

	<div class="span4">
		<h4><i class="icon-question-sign"></i> How</h4>
		<p><strong>Depending</strong> connects to your GitHub account, read your repositories information. Then, <strong>Depending</strong> determines your project dependencies status, and report back to you.</p><a href="/auth/login" class="btn btn-small btn-main"><i class="icon icon-circle-arrow-right"></i> Get Started</a>
	</div>
</div>
<hr/>
<div class="row-fluid">
	<div class="span4">
	<h4>Recent Builds</h4>
	<ul class="nav nav-tabs nav-stacked">
	{% for activeLog in repos.actives %}
		{% set active = activeLog.getReposs.getFirst %}
		<li><a href="/{{ active.FullName }}"  class="btn-link"><i class="icon {{ active.IsPackage|toIcon }}"></i> <strong>{{ active.FullName }}</strong> <span class="pull-right">
		<i class="icon-circle{{ active.Rid|isGreenStatus }} has-tip" data-original-title="Up to date"></i>
		<i class="icon-circle{{ active.Rid|isYellowStatus }} has-tip" data-original-title="Need to update"></i>
		<i class="icon-circle{{ active.Rid|isRedStatus }} has-tip" data-original-title="Out of date"></i>
		</span></a></li>
	{% endfor %}
	</ul>
	</div>
	<div class="span4">
	<h4>Recent Projects</h4>
	<ul class="nav nav-tabs nav-stacked">
	{% for project in repos.projects %}
		<li><a href="/{{ project.FullName }}"  class="btn-link"><i class="icon {{ project.IsPackage|toIcon }}"></i> <strong>{{ project.FullName }}</strong> <span class="pull-right">
		<i class="icon-circle{{ project.Rid|isGreenStatus }} has-tip" data-original-title="Up to date"></i>
		<i class="icon-circle{{ project.Rid|isYellowStatus }} has-tip" data-original-title="Need to update"></i>
		<i class="icon-circle{{ project.Rid|isRedStatus }} has-tip" data-original-title="Out of date"></i>
		</span></a></li>
	{% endfor %}
	</ul>
	</div>
	<div class="span4">
	<h4>Recent Packages</h4>
	<ul class="nav nav-tabs nav-stacked">
	{% for package in repos.packages %}
		<li><a href="/{{ package.FullName }}"  class="btn-link"><i class="icon {{ package.IsPackage|toIcon }}"></i> <strong>{{ package.FullName }}</strong> <span class="pull-right">
		<i class="icon-circle{{ package.Rid|isGreenStatus }} has-tip" data-original-title="Up to date"></i>
		<i class="icon-circle{{ package.Rid|isYellowStatus }} has-tip" data-original-title="Need to update"></i>
		<i class="icon-circle{{ package.Rid|isRedStatus }} has-tip" data-original-title="Out of date"></i>
		</span></a></li>
	{% endfor %}
	</ul>
	</div>
</div>
{{ something }}