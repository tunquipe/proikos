<style>
    .actions label {
        display: unset !important;
    }

    form[name="search_simple"] .form-group:nth-of-type(1),
    form[name="search_simple"] .form-group:nth-of-type(2) {
        width: 30%;
    }
    form[name="search_simple"] .form-group:nth-of-type(4) {
        vertical-align: bottom;
        margin-left: 4px;
    }
    #search_simple_submit{
        margin-left: 20px;
        margin-top: 20px;
    }
    #toolbarData .col-sm-2.text-right {
        margin-top: 15px;
    }

    #cm-content .container {
        width: 90%;
    }
    .red-text {
        color: red;
    }

    .blue-text {
        color: blue;
    }

    .default-text {
        color: #000;
    }

    .dash {
        text-align: center;
    }
</style>
<div class="pagination">
    <ul class="pagination">
        {% if data.pagination.currentPage > 1 %}
        <li><a href="?page={{ data.pagination.currentPage - 1 }}&perPage={{ perPage }}" aria-label="{{ 'Previous'|get_plugin_lang('ProikosPlugin') }}">&laquo; {{ 'Previous'|get_plugin_lang('ProikosPlugin') }}</a></li>
        {% else %}
        <li class="disabled"><a href="#" aria-label="Previous">&laquo; {{ 'Previous'|get_plugin_lang('ProikosPlugin') }}</a></li>
        {% endif %}

        <li class="disabled"><span>{{ 'Page'|get_plugin_lang('ProikosPlugin') }} {{ data.pagination.currentPage }} {{ 'Of'|get_plugin_lang('ProikosPlugin') }} {{ data.pagination.totalPages }}</span></li>

        {% if data.pagination.currentPage < data.pagination.totalPages %}
        <li><a href="?page={{ data.pagination.currentPage + 1 }}&perPage={{ perPage }}" aria-label="{{ 'Next'|get_plugin_lang('ProikosPlugin') }}">{{ 'Next'|get_plugin_lang('ProikosPlugin') }} &raquo;</a></li>
        {% else %}
        <li class="disabled"><a href="#" aria-label="Next">{{ 'Next'|get_plugin_lang('ProikosPlugin') }} &raquo;</a></li>
        {% endif %}
    </ul>
</div>
<table class="table table-hover table-striped table-bordered data_table" id="user_tables">
    <tr class="row_odd">
        <th class="th-header">Nº de log</th>
        <th class="th-header" style="width: 300px;">{{ 'Session'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">Codigo de Registro - Session</th>
        <th class="th-header">ID de usuario</th>
        <th class="th-header">{{ 'LastNamesAndFirstNames'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'DNI'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'ContratingCompanyRUC'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'ContratingCompanyName'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'EntranceExam'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'Workshop'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'ExitExam'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'Average'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'Score'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'Status'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'Actions'|get_plugin_lang('ProikosPlugin') }}</th>

    </tr>
    {% if data.users %}
    {% for user in data.users %}

    <tr>
        <td>{{ user.id }}</td>
        <td>{{ user.session_name }}</td>
        <td>{{ user.registration_session_user }}</td>
        <td>{{ user.user_id }}</td>
        <td>{{ user.student }}</td>
        <td>{{ user.dni }}</td>
        <td>{{ user.ruc_company }}</td>
        <td>{{ user.name_company }}</td>

        <td class="text-center {% if user.entrance_exam is not defined or user.entrance_exam == 0 %}default-text{% elseif user.entrance_exam <= 10 %}red-text{% elseif user.entrance_exam >= 10 %}blue-text{% endif %}">
            {% if user.entrance_exam == 0 or user.entrance_exam is empty %}
            0
            {% else %}
            {{ user.entrance_exam }}
            {% endif %}
        </td>

        <td class="text-center {% if user.workshop is not defined or user.workshop == 0 %}default-text{% elseif user.workshop <= 10 %}red-text{% elseif user.workshop >= 10 %}blue-text{% endif %}">
            {% if user.workshop == 0 or user.workshop is empty %}
            0
            {% else %}
            {{ user.workshop }}
            {% endif %}
        </td>

        <td class="text-center {% if user.exit_exam is not defined or user.exit_exam == 0 %}default-text{% elseif user.exit_exam <= 10 %}red-text{% elseif user.exit_exam >= 10 %}blue-text{% endif %}">
            {% if user.exit_exam == 0 or user.exit_exam is empty %}
            0
            {% else %}
            {{ user.exit_exam }}
            {% endif %}
        </td>

        <td class="text-center {% if user.promedio_ponderado is not defined or user.promedio_ponderado == 0 %}default-text{% elseif user.promedio_ponderado <= 10 %}red-text{% elseif user.promedio_ponderado >= 10 %}blue-text{% endif %}">
            <strong>{{ user.promedio_ponderado }}</strong>
        </td>
        <td class="text-center">{{ user.score }}% </td>
        <td style="text-align: center">
            {{ user.status }}
        </td>
        <td style="text-align: center">
            {{ user.actions }}
        </td>

    </tr>
    {% endfor %}
    {% endif %}
</table>

<div class="pagination">
    <ul class="pagination">
        {% if data.pagination.currentPage > 1 %}
        <li><a href="?page={{ data.pagination.currentPage - 1 }}&perPage={{ perPage }}" aria-label="{{ 'Previous'|get_plugin_lang('ProikosPlugin') }}">&laquo; {{ 'Previous'|get_plugin_lang('ProikosPlugin') }}</a></li>
        {% else %}
        <li class="disabled"><a href="#" aria-label="Previous">&laquo; {{ 'Previous'|get_plugin_lang('ProikosPlugin') }}</a></li>
        {% endif %}

        <li class="disabled"><span>{{ 'Page'|get_plugin_lang('ProikosPlugin') }} {{ data.pagination.currentPage }} {{ 'Of'|get_plugin_lang('ProikosPlugin') }} {{ data.pagination.totalPages }}</span></li>

        {% if data.pagination.currentPage < data.pagination.totalPages %}
        <li><a href="?page={{ data.pagination.currentPage + 1 }}&perPage={{ perPage }}" aria-label="{{ 'Next'|get_plugin_lang('ProikosPlugin') }}">{{ 'Next'|get_plugin_lang('ProikosPlugin') }} &raquo;</a></li>
        {% else %}
        <li class="disabled"><a href="#" aria-label="Next">{{ 'Next'|get_plugin_lang('ProikosPlugin') }} &raquo;</a></li>
        {% endif %}
    </ul>
</div>

<div>
    <h5>Nota:</h5>
    <ul>
        <li>(*) Datos externos</li>
    </ul>
</div>