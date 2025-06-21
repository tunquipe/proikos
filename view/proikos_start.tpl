<div class="panel-proikos">
    <h3>{{ 'DashboardProikos'|get_plugin_lang('ProikosPlugin') }}</h3>
</div>

<div class="row">
    {% if is_platform_admin or is_drh %}
    <div class="col-md-3">
        <div class="tool-box">
            <a href="{{ src_plugin }}src/entity_management.php" class="tool">
                <img src="{{ src_plugin }}/images/house_proikos.png" alt="">
                <div class="tool-title">Gestionar de empresas</div>
            </a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="tool-box">
            <a href="{{ src_plugin }}src/attendance_fora.php" class="tool">
                <img src="{{ src_plugin }}/images/fora_proikos.png" alt="">
                <div class="tool-title">Generar lista FORA</div>
            </a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="tool-box">
            <a href="{{ src_plugin }}src/participant_report.php" class="tool">
                <img src="{{ src_plugin }}/images/report_proikos.png" alt="">
                <div class="tool-title">Reportes de participantes</div>
            </a>
        </div>
    </div>
    {% endif %}

    <div class="col-md-3">
        <div class="tool-box">
            <a href="{{ src_plugin }}src/contrating_companies.php" class="tool">
                <img src="{{ src_plugin }}/images/contract_proikos.png" alt="">
                <div class="tool-title">Empresas contratistas</div>
            </a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="tool-box">
            <a href="{{ src_plugin }}src/reporting_quota_session_det.php" class="tool">
                <img src="{{ src_plugin }}/images/report_quota.png" alt="">
                <div class="tool-title">Reporte de cupones</div>
            </a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="tool-box">
            <a href="{{ src_plugin }}src/data_report.php" class="tool">
                <img src="{{ src_plugin }}/images/data_report.png" alt="">
                <div class="tool-title">Data</div>
            </a>
        </div>
    </div>
    {% if is_platform_admin %}
    <div class="col-md-3">
        <div class="tool-box">
            <a href="{{ src_plugin }}src/users_management.php?action=list" class="tool">
                <img src="{{ src_plugin }}/images/users.png" alt="">
                <div class="tool-title">Usuarios</div>
            </a>
        </div>
    </div>
    {% endif %}
</div>
