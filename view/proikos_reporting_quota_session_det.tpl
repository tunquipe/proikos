<form id="report_user_quota" action="{{ url_self }}" method="post">
    <table class="table table-bordered ">
        <thead>
        <tr>
            <th></th>
            <th class="text-center">{{ 'RegistrationID'|get_plugin_lang('ProikosPlugin') }}</th>
            <th class="text-center">{{ 'ContratingCompanyRUC'|get_plugin_lang('ProikosPlugin') }}</th>
            <th>{{ 'ContratingCompanyName'|get_plugin_lang('ProikosPlugin') }}</th>
            <th>{{ 'SessionName'|get_plugin_lang('ProikosPlugin') }}</th>
            <th>{{ 'ContratingCompanyCreatedByUser'|get_plugin_lang('ProikosPlugin') }}</th>
            <th>{{ 'IdUser'|get_plugin_lang('ProikosPlugin') }}</th>
            <th>{{ 'StudentName'|get_plugin_lang('ProikosPlugin') }}</th>
            <th style="width: 120px">{{ 'Status'|get_plugin_lang('ProikosPlugin') }}</th>
            <th>{{ 'StudentSubscriptionDate'|get_plugin_lang('ProikosPlugin') }}</th>
            {% if _u.is_admin %}
            <th>{{ 'Actions'|get_plugin_lang('ProikosPlugin') }}</th>
            {% endif %}
        </tr>
        </thead>
        <tbody>
        {% for item in items %}
        <tr style="font-size: 13px;" class="{{ item.class }}">
            <td><input type="checkbox" name="ids[]" value="{{ item.id }}"/></td>
            <td class="text-center">{{ item.id }}</td>
            <td class="text-center">{{ item.ruc }}</td>
            <td>{{ item.company_name }}</td>
            <td>{{ item.session_name }}</td>
            <td>{{ item.quota_created_by }}</td>
            <td style="text-align: center">{{ item.user_id }}</td>
            <td>
                {% if item.student_name %}
                {{ item.student_name }}
                {% else %}
                -
                {% endif %}
            </td>
            <td>
                {% if item.status == 1 %}
                <div class="alert alert-success quote_user" role="alert">Inscrito</div>
                {% else %}
                <div class="alert alert-warning quote_user" role="alert">Sin inscripción</div>
                {% endif %}
            </td>
            <td>
                {% if item.student_subscription_date %}
                {{ item.student_subscription_date }}
                {% else %}
                -
                {% endif %}
            </td>
            {% if _u.is_admin %}
            <td>{{ item.actions }}</td>
            {% endif %}
        </tr>
        {% endfor %}
        </tbody>
    </table>
    <input type="hidden" name="action" value="">
    {# Paginación #}
    {% if total_pages > 1 %}
    <div class="left-center">
        <ul class="pagination">
            {# Botón Anterior #}
            <li class="{% if current_page == 1 %}disabled{% endif %}">
                <a href="{% if current_page > 1 %}{{ url_self }}?page={{ current_page - 1 }}{% else %}#{% endif %}">
                    &laquo; Anterior
                </a>
            </li>

            {# Números de página #}
            {% for i in 1..total_pages %}
            {% if i == current_page %}
            <li class="active"><a href="#">{{ i }}</a></li>
            {% elseif i == 1 or i == total_pages or (i >= current_page - 2 and i <= current_page + 2) %}
            <li><a href="{{ url_self }}?page={{ i }}">{{ i }}</a></li>
            {% elseif i == current_page - 3 or i == current_page + 3 %}
            <li class="disabled"><a href="#">...</a></li>
            {% endif %}
            {% endfor %}

            {# Botón Siguiente #}
            <li class="{% if current_page == total_pages %}disabled{% endif %}">
                <a href="{% if current_page < total_pages %}{{ url_self }}?page={{ current_page + 1 }}{% else %}#{% endif %}">
                    Siguiente &raquo;
                </a>
            </li>
        </ul>

        {# Información de registros #}
        <p class="text-muted">
            Mostrando {{ ((current_page - 1) * per_page) + 1 }} -
            {{ (current_page * per_page) > total_records ? total_records : (current_page * per_page) }}
            de {{ total_records }} registros
        </p>
    </div>
    {% endif %}

<div class="btn-toolbar">
    <div class="btn-group">
        <a class="btn btn-default" href="#" onclick="javascript: setCheckbox(true, 'report_user_quota'); return false;">
            Seleccionar todo
        </a>
        <a class="btn btn-default" href="#" onclick="javascript: setCheckbox(false, 'report_user_quota'); return false;">
            Anular seleccionar todos
        </a>
    </div>
    <div class="btn-group">
        <button class="btn btn-default" type="button">Acciones</button>
        <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu">
            <li>
                <a data-action="delete_select" href="#" onclick="javascript:action_click(this, 'report_user_quota');">
                    Eliminar seleccionados
                </a>
            </li>
        </ul>
    </div>
</div>

</form>