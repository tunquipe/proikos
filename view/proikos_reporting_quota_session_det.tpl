
<table class="table table-bordered js-paginated-table">
    <thead>
    <tr>
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

<script src="../js/table-pagination/main.js"></script>