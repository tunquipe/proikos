{{ form }}
<table class="table table-bordered">
    <thead>
    <tr>
        <th>#</th>
        <th>{{ 'CompanyName'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'NameBusiness'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'RUC'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'TaxResidence'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'EconomicActivity'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'NumberOfWorkers'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'Logo'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'CodeReference'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'Status'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'Actions'|get_plugin_lang('ProikosPlugin') }}</th>
    </tr>
    </thead>
    <tbody>
    {% for entity in entities %}
        <tr>
            <th scope="row">{{ entity.id }}</th>
            <td>{{ entity.name_entity }}</td>
            <td>{{ entity.business_name }}</td>
            <td>{{ entity.ruc }}</td>
            <td>{{ entity.tax_residence }}</td>
            <td>{{ entity.economic_activity }}</td>
            <td>{{ entity.number_of_workers }}</td>
            <td><img width="100px" src="{{ entity.picture }}" /></td>
            <td>{{ entity.code_reference }}</td>
            <td>{{ entity.status }}</td>
            <td>{{ entity.actions }}</td>
        </tr>
    {% endfor %}
    </tbody>
</table>