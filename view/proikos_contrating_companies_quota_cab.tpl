{{ form }}
<table class="table table-bordered js-paginated-table">
    <thead>
    <tr>
        <th>ID</th>
        <th>{{ 'Modalidades Configuradas'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'Categorías de la Sesión'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="text-center">{{ 'ContratingCompanyUserQuota'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="text-center">{{ 'ContratingCompanyUserQuotaTotalPrice'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="text-center">{{ 'ContratingCompanyUserQuotaDispon'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'ContratingCompanyValidityDate'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'ContratingCompanyCreationDate'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'ContratingCompanyCreatedByUser'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="text-center">{{ 'Actions'|get_plugin_lang('ProikosPlugin') }}</th>
    </tr>
    </thead>
    <tbody>
    {% for item in items %}
    <tr>
        <th scope="row">{{ item.id }}</th>
        <td>{{ item.modalidades }}</td>
        <td>{{ item.categorias_session }}</td>
        <td class="text-center">{{ item.total_user_quota }}</td>
        <td class="text-center">{{ item.total_price_unit_quota }}</td>
        <td class="text-center">{{ item.quota_dispon }}</td>
        <td>{{ item.formatted_validity_date }}</td>
        <td>{{ item.formatted_created_at }}</td>
        <td>{{ item.user_name }}</td>
        <td class="text-center">{{ item.actions }}</td>
    </tr>
    {% endfor %}
    </tbody>
</table>

<script src="../js/table-pagination/main.js"></script>
