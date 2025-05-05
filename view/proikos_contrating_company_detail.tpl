{{ form }}
<table class="table table-bordered js-paginated-table">
    <thead>
    <tr>
        <th>ID</th>
        <th class="text-center">{{ 'ContratingCompanyUserQuota'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'ContratingCompanyCreationDate'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'Evento'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'ContratingCompanyCreatedByUser'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="text-center">{{ 'Actions'|get_plugin_lang('ProikosPlugin') }}</th>
    </tr>
    </thead>
    <tbody>
    {% for item in items %}
    <tr>
        <th scope="row">{{ item.id }}</th>
        <td class="text-center">{{ item.user_quota }}</td>
        <td>{{ item.formatted_created_at }}</td>
        <td>
            {% if item.event == 'add_quota' %}
                Agregar Cupos
            {% elseif item.event == 'user_subscription_to_course' %}
                Usuario Suscrito
            {% endif %}
        </td>
        <td>{{ item.user_name }}</td>
        <td class="text-center">{{ item.actions }}</td>
    </tr>
    {% endfor %}
    </tbody>
</table>

<script src="../js/table-pagination/main.js"></script>
