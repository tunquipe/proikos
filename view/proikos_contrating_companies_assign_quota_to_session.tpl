{{ form }}

<table class="table table-bordered js-paginated-table">
    <thead>
    <tr>
        <th>ID</th>
        <th class="text-center">{{ 'Mode'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="text-center">{{ 'Category'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="text-center">{{ 'Session'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="text-center">{{ 'AssignedQuotas'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'ContratingCompanyCreationDate'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'ContratingCompanyAsignedByUser'|get_plugin_lang('ProikosPlugin') }}</th>
    </tr>
    </thead>
    <tbody>
    {% for item in items %}
    <tr>
        <th scope="row">{{ item.id }}</th>
        <td>{{ item.session_mode_name }}</td>
        <td>{{ item.category_name }}</td>
        <td>{{ item.session_name }}</td>
        <td class="text-right">{{ item.user_quota }}</td>
        <td>{{ item.formatted_created_at }}</td>
        <td>{{ item.user_name }}</td>
    </tr>
    {% endfor %}
    </tbody>
</table>

<script src="../js/table-pagination/main.js"></script>
