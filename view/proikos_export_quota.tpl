<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
                {{ form }}
            </div>
        </div>

        {% if message %}
        {{ message }}
        {% endif %}

        {% if filter_info %}
        <div class="alert alert-info">
            <strong>{{ 'AppliedFilters'|get_plugin_lang('ProikosPlugin') }}:</strong> {{ filter_info|raw }}
        </div>
        {% endif %}

        {% if table %}
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    {{ 'Results'|get_plugin_lang('ProikosPlugin') }}
                    ({{ total_records }} {{ 'Records'|get_plugin_lang('ProikosPlugin') }})
                </h3>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    {{ table }}
                </div>
            </div>
        </div>
        {% endif %}
    </div>
</div>

{{ js_content|raw }}