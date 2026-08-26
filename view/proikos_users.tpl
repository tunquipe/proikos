{{ form_edit }}

{% if form_report %}
{{ form_report }}
{% if report_filter_info %}
<div class="alert alert-info">{{ report_filter_info }}</div>
<p class="text-muted">{{ report_total }} registro(s)</p>
{% endif %}
{% if report_table %}
<div class="table-responsive">
    {{ report_table }}
</div>
{% endif %}
{{ js_content }}
{% endif %}

{% if current_page %}
<form method="get" action="{{ list_url }}" class="form-inline" style="margin-bottom: 15px;">
    <input type="hidden" name="action" value="list">
    <div class="input-group" style="max-width: 420px;">
        <input type="text" name="search" value="{{ search }}" class="form-control"
               placeholder="{{ 'SearchUser'|get_plugin_lang('ProikosPlugin') }}">
        <span class="input-group-btn">
            <button type="submit" class="btn btn-primary">
                <em class="fa fa-search"></em>
            </button>
            {% if search %}
            <a href="{{ list_url }}" class="btn btn-default">
                <em class="fa fa-times"></em>
            </a>
            {% endif %}
        </span>
    </div>
</form>

<p class="text-muted">{{ total_users }} usuario(s)</p>
{% endif %}

{% if users %}
<table class="table table-bordered">
    <thead>
    <tr>
        <th>#</th>
        <th>{{ 'LastNamesAndFirstNames'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'Email'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'Username'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'NumberDocument'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'ContratingCompanyRUC'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'ContratingCompanyName'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'Actions'|get_plugin_lang('ProikosPlugin') }}</th>
    </tr>
    </thead>
    <tbody>
    {% for user in users %}
    <tr>
        <td>{{ user.id }}</td>
        <td>{{ user.name }}</td>
        <td>{{ user.email }}</td>
        <td>{{ user.username }}</td>
        <td>{{ user.number_document }}</td>
        <td>{{ user.ruc }}</td>
        <td>{{ user.company }}</td>
        <td>{{ user.actions }}</td>
    </tr>
    {% endfor %}
    </tbody>
</table>

{% if total_pages > 1 %}
{% set base_url = list_url ~ (search ? '&search=' ~ search|url_encode : '') %}
<nav aria-label="Paginacion">
    <ul class="pagination">
        <li class="{{ current_page == 1 ? 'disabled' : '' }}">
            <a href="{{ current_page == 1 ? '#' : base_url ~ '&page=' ~ (current_page - 1) }}">&laquo;</a>
        </li>
        {% if page_start > 1 %}
        <li><a href="{{ base_url }}&page=1">1</a></li>
        {% if page_start > 2 %}<li class="disabled"><span>&hellip;</span></li>{% endif %}
        {% endif %}
        {% for p in page_start..page_end %}
        <li class="{{ p == current_page ? 'active' : '' }}">
            <a href="{{ base_url }}&page={{ p }}">{{ p }}</a>
        </li>
        {% endfor %}
        {% if page_end < total_pages %}
        {% if page_end < total_pages - 1 %}<li class="disabled"><span>&hellip;</span></li>{% endif %}
        <li><a href="{{ base_url }}&page={{ total_pages }}">{{ total_pages }}</a></li>
        {% endif %}
        <li class="{{ current_page == total_pages ? 'disabled' : '' }}">
            <a href="{{ current_page == total_pages ? '#' : base_url ~ '&page=' ~ (current_page + 1) }}">&raquo;</a>
        </li>
    </ul>
</nav>
{% endif %}

{% elseif current_page %}
<div class="alert alert-info">No se encontraron usuarios.</div>
{% endif %}
