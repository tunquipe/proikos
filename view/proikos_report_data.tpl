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
        <th class="th-header">Nº</th>
        <th class="th-header">Codigo</th>
        <th class="th-header">Fecha</th>
        <th class="th-header">Nº Horas</th>
        <th class="th-header">{{ 'Course'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'Session'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'LastNamesAndFirstNames'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'DNI'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'ContratingCompanyRUC'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'ContratingCompanyName'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'Sede'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'EntranceExam'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'Workshop'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'ExitExam'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'Score'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'Status'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'Observations'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'CertificatesAttached'|get_plugin_lang('ProikosPlugin') }}</th>
    </tr>
    {% if data.users %}
        {% for user in data.users %}

        <tr>
            <td>{{ user.id }}</td>
            <td>PROK{{ user.id }}</td>
            <td>{{ user.registration_date }}</td>
            <td>{{ user.time_course }}</td>
            <td>{{ user.session_category_name }}</td>
            <td>{{ user.session_name }}</td>
            <td>{{ user.student }}</td>
            <td>{{ user.DNI }}</td>
            <td>{{ user.ruc_company }}</td>
            <td>{{ user.name_company }}</td>
            <td>{{ user.area }}</td>

            <td class="text-center {% if user.exams.examen_de_entrada is not defined or user.exams.examen_de_entrada == 0 %}default-text{% elseif user.exams.examen_de_entrada <= 10 %}red-text{% elseif user.exams.examen_de_entrada >= 10 %}blue-text{% endif %}">
                {% if user.exams.examen_de_entrada == 0 or user.exams.examen_de_entrada is empty %}
                -
                {% else %}
                {{ user.exams.examen_de_entrada }}
                {% endif %}
            </td>

            <td class="text-center {% if user.exams.taller is not defined or user.exams.taller == 0 %}default-text{% elseif user.exams.taller <= 10 %}red-text{% elseif user.exams.taller >= 10 %}blue-text{% endif %}">
                {% if user.exams.taller == 0 or user.exams.taller is empty %}
                -
                {% else %}
                {{ user.exams.taller }}
                {% endif %}
            </td>

            <td class="text-center {% if user.exams.examen_de_salida is not defined or user.exams.examen_de_salida == 0 %}default-text{% elseif user.exams.examen_de_salida <= 10 %}red-text{% elseif user.exams.examen_de_salida >= 10 %}blue-text{% endif %}">
                {% if user.exams.examen_de_salida == 0 or user.exams.examen_de_salida is empty %}
                -
                {% else %}
                {{ user.exams.examen_de_salida }}
                {% endif %}
            </td>


            <td>{{ user.score }}</td>
            <td style="text-align: center">
                {% if user.score >= 70 %}
                    <span class="label label-success">Aprobado (*)</span>
                {% else %}
                    {{ user.status }}
                {% endif %}
            </td>

            <td style="text-align: center">
                {% if user.certificate_status == 1 %}
                    <span class="label label-success">Vigente</span>
                {% elseif user.certificate_status == 2 %}
                    <span class="label label-warning">Caducado</span>
                {% elseif user.certificate_status == 3 %}
                    <span class="label label-default">No generado</span>
                {% else %}
                - <!-- Si no tiene un valor 1, 2 o 3, puedes mostrar un guion o un texto por defecto -->
                {% endif %}
            </td>

            <td style="text-align: center">{{ user.cert }}</td>
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