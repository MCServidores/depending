{{ build.Title|raw }}

========================================================================
{{label.Name}}{{label.UsedVersion}} {{label.LatestVersion}}
========================================================================
{% if vendors is not empty %}
{% for vendor in vendors %}
{{ vendor.Name }}{{ vendor.UsedVersion|raw }} {{ vendor.LatestVersion|raw }}
{% endfor %}
{% endif %}
------------------------------------------------------------------------
{{ build.ResultText }}{{ build.ResultStatus|raw }}
========================================================================

Executed at {{clock}}