<table class="table table-bordered js-paginated-table">
    <thead>
    <tr>
        <th class="text-center">{{ 'ContratingCompanyRUC'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'ContratingCompanyName'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'SessionName'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'ContratingCompanyCreatedByUser'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'StudentName'|get_plugin_lang('ProikosPlugin') }}</th>
        <th>{{ 'StudentSubscriptionDate'|get_plugin_lang('ProikosPlugin') }}</th>
    </tr>
    </thead>
    <tbody>
    {% for item in items %}
    <tr>
        <td class="text-center">{{ item.ruc }}</td>
        <td>{{ item.company_name }}</td>
        <td>{{ item.session_name }}</td>
        <td>{{ item.quota_created_by }}</td>
        <td>{{ item.student_name }}</td>
        <td>{{ item.student_subscription_date }}</td>
    </tr>
    {% endfor %}
    </tbody>
</table>

<script src="../js/table-pagination/main.js"></script>