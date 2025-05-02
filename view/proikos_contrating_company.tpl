{{ form }}
<table class="table table-bordered">
    <thead>
    <tr>
        <th>#</th>
        <th>{{ 'ContratingCompanyRUC'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'ContratingCompanyName'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'ContratingCompanyUserQuota'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'Status'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'Actions'|get_plugin_lang('ProikosPlugin') }}</th>
    </tr>
    </thead>
    <tbody>
    {% for contrating_company in contrating_companies %}
        <tr>
            <th scope="row">{{ contrating_company.id }}</th>
            <td>{{ contrating_company.ruc }}</td>
            <td>{{ contrating_company.name }}</td>
            <td>{{ contrating_company.user_quota }}</td>
            <td>{{ (contrating_company.status == 1 ? 'Activo' : 'Inactivo') }}</td>
            <td>{{ contrating_company.actions }}</td>
        </tr>
    {% endfor %}
    </tbody>
</table>