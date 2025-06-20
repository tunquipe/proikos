{{ form_edit }}

{% if users %}
<table class="table table-bordered js-paginated-table">
    <thead>
    <tr>
        <th>#</th>
        <th>{{ 'LastNamesAndFirstNames'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'Email'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'Cellular'|get_plugin_lang('ProikosPlugin') }}</th>
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
        <td>{{ user.phone }}</td>
        <td>{{ user.ruc }}</td>
        <td>{{ user.company }}</td>
        <td>{{ user.actions }}</td>
    </tr>
    {% endfor %}
    </tbody>
</table>

<script src="../js/table-pagination/main.js"></script>
{% endif %}