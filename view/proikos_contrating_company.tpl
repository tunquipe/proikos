{{ form }}
<table class="table table-bordered js-paginated-table">
    <thead>
    <tr>
        <th>ID</th>
        <th>{{ 'ContratingCompanyRUC'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'ContratingCompanyName'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="text-center">{{ 'ContratingCompanyUserQuota'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'Status'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="text-center">{{ 'Actions'|get_plugin_lang('ProikosPlugin') }}</th>
    </tr>
    </thead>
    <tbody>
    {% for contrating_company in contrating_companies %}
        <tr>
            <th scope="row">{{ contrating_company.id }}</th>
            <td>{{ contrating_company.ruc }}</td>
            <td>{{ contrating_company.name }}</td>
            <td class="text-center">{{ contrating_company.total_user_quota }}</td>
            <td>{{ (contrating_company.status == 1 ? 'Activo' : 'Inactivo') }}</td>
            <td class="text-center">{{ contrating_company.actions }}</td>
        </tr>
    {% endfor %}
    </tbody>
</table>

<script src="../js/table-pagination/main.js"></script>
