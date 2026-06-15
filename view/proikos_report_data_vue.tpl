<style>
    .actions label {
        display: unset !important;
    }

    form[name="search_simple"] .form-group:nth-of-type(1),
    form[name="search_simple"] .form-group:nth-of-type(2) {
        width: 30%;
    }
    form[name="search_simple"] .form-group:nth-of-type(4) {
        vertical-align: bottom;
        margin-left: 4px;
    }
    #search_simple_submit{
        margin-left: 20px;
        margin-top: 20px;
    }
    #toolbarData .col-sm-2.text-right {
        margin-top: 15px;
    }

    #cm-content .container {
        width: 90%;
    }
    .red-text {
        color: red;
    }

    .blue-text {
        color: blue;
    }

    .default-text {
        color: #000;
    }

    .dash {
        text-align: center;
    }

    /* Preloader Styles */
    .vue-preloader {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 80px 20px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 8px;
        margin: 20px 0;
        border: 1px solid #dee2e6;
    }

    .spinner-container {
        position: relative;
        width: 70px;
        height: 70px;
    }

    .spinner-ring {
        position: absolute;
        width: 100%;
        height: 100%;
        border: 4px solid transparent;
        border-radius: 50%;
        animation: spin 1.2s linear infinite;
    }

    .spinner-ring:nth-child(1) {
        border-top-color: #007bff;
    }

    .spinner-ring:nth-child(2) {
        border-right-color: #28a745;
        animation-delay: 0.15s;
        width: 55px;
        height: 55px;
        top: 7.5px;
        left: 7.5px;
    }

    .spinner-ring:nth-child(3) {
        border-bottom-color: #6f42c1;
        animation-delay: 0.3s;
        width: 40px;
        height: 40px;
        top: 15px;
        left: 15px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .preloader-text {
        margin-top: 25px;
        font-size: 18px;
        color: #495057;
        font-weight: 600;
    }

    .preloader-subtext {
        margin-top: 8px;
        font-size: 14px;
        color: #6c757d;
    }

    .progress-bar-container {
        width: 250px;
        height: 4px;
        background-color: #dee2e6;
        border-radius: 2px;
        margin-top: 20px;
        overflow: hidden;
    }

    .progress-bar-animated {
        height: 100%;
        background: linear-gradient(90deg, #007bff, #28a745, #007bff);
        background-size: 200% 100%;
        animation: progressAnimation 1.5s ease-in-out infinite;
        width: 100%;
    }

    @keyframes progressAnimation {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* Error State */
    .vue-error {
        text-align: center;
        padding: 50px;
        background-color: #fff5f5;
        border: 1px solid #f5c6cb;
        border-radius: 8px;
        margin: 20px 0;
    }

    .vue-error .error-icon {
        font-size: 50px;
        color: #dc3545;
        margin-bottom: 15px;
    }

    .vue-error h4 {
        color: #721c24;
        margin-bottom: 10px;
    }

    .vue-error p {
        color: #856404;
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
        background-color: #f8f9fa;
        border-radius: 8px;
        margin: 20px 0;
    }

    .empty-state .empty-icon {
        font-size: 60px;
        color: #adb5bd;
        margin-bottom: 20px;
    }

    /* Vue Pagination */
    .vue-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        gap: 5px;
        margin: 15px 0;
    }

    .vue-pagination button {
        padding: 6px 12px;
        border: 1px solid #dee2e6;
        background-color: #fff;
        color: #007bff;
        cursor: pointer;
        border-radius: 4px;
        transition: all 0.2s;
        font-size: 14px;
    }

    .vue-pagination button:hover:not(:disabled) {
        background-color: #007bff;
        color: #fff;
        border-color: #007bff;
    }

    .vue-pagination button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        color: #6c757d;
    }

    .vue-pagination button.active {
        background-color: #007bff;
        color: #fff;
        border-color: #007bff;
    }

    .vue-pagination .page-info {
        padding: 6px 15px;
        color: #6c757d;
        font-size: 14px;
    }

    /* Data loaded animation */
    .data-loaded {
        animation: fadeInUp 0.4s ease;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(15px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Stats summary */
    .stats-summary {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }

    .stat-item {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 10px 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .stat-item .stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .stat-item .stat-icon.blue {
        background-color: #e7f1ff;
        color: #007bff;
    }

    .stat-item .stat-icon.green {
        background-color: #e8f5e9;
        color: #28a745;
    }

    .stat-item .stat-value {
        font-size: 20px;
        font-weight: 700;
        color: #212529;
    }

    .stat-item .stat-label {
        font-size: 12px;
        color: #6c757d;
    }
    /* Cache Indicator Styles */
    .cache-indicator {
        border-left: 3px solid #ff9800;
        transition: all 0.3s ease;
    }

    .cache-indicator.cache-fresh {
        border-left-color: #9c27b0;
    }

    .cache-indicator:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .stat-item .stat-icon.orange {
        background-color: #fff3e0;
        color: #ff9800;
    }

    .stat-item .stat-icon.purple {
        background-color: #f3e5f5;
        color: #9c27b0;
    }

    .cache-pulse {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
    }

    /* Tooltip para más información */
    .cache-tooltip {
        position: relative;
        cursor: help;
    }

    .cache-tooltip:hover::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        padding: 8px 12px;
        background: rgba(0, 0, 0, 0.9);
        color: white;
        border-radius: 4px;
        white-space: nowrap;
        z-index: 1000;
        font-size: 12px;
        margin-bottom: 5px;
    }
</style>

<div id="vue-data-app">
    <!-- Preloader -->
    <div v-if="loading" class="vue-preloader">
        <div class="spinner-container">
            <div class="spinner-ring"></div>
            <div class="spinner-ring"></div>
            <div class="spinner-ring"></div>
        </div>
        <p class="preloader-text">[[ loadingMessage ]]</p>
        <p class="preloader-subtext">Por favor espere, esto puede tomar unos segundos...</p>
        <div class="progress-bar-container">
            <div class="progress-bar-animated"></div>
        </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="vue-error">
        <div class="error-icon">
            <i class="fa fa-exclamation-triangle"></i>
        </div>
        <h4>Error al cargar los datos</h4>
        <p>[[ errorMessage ]]</p>
        <button class="btn btn-primary" @click="loadData">
            <i class="fa fa-refresh"></i> Reintentar
        </button>
    </div>

    <!-- Data Loaded -->
    <div v-else class="data-loaded">
        <!-- Stats Summary -->
        <div class="stats-summary">
            <div class="stat-item">
                <div class="stat-icon blue">
                    <i class="fa fa-users"></i>
                </div>
                <div>
                    <div class="stat-value">[[ pagination.totalUsers ]]</div>
                    <div class="stat-label">Total Registros</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon green">
                    <i class="fa fa-file-text"></i>
                </div>
                <div>
                    <div class="stat-value">[[ pagination.currentPage ]] / [[ pagination.totalPages ]]</div>
                    <div class="stat-label">Página</div>
                </div>
            </div>

            <!-- NUEVO: Indicador de caché -->
            <div v-if="cacheInfo" class="stat-item cache-indicator" :class="cacheInfo.from_cache ? 'cache-active' : 'cache-fresh'">
                <div class="stat-icon" :class="cacheInfo.from_cache ? 'orange' : 'purple'">
                    <i class="fa" :class="cacheInfo.from_cache ? 'fa-database' : 'fa-refresh'"></i>
                </div>
                <div>
                    <div class="stat-value" style="font-size: 14px;">
                        [[ cacheInfo.from_cache ? 'En Caché' : 'Recién Actualizado' ]]
                    </div>
                    <div class="stat-label" style="font-size: 11px;">
            <span v-if="cacheInfo.last_update">
                <i class="fa fa-clock-o"></i> [[ cacheInfo.last_update.relative ]]
            </span>
                        <span v-if="cacheInfo.time_left && cacheInfo.from_cache" style="margin-left: 5px;">
                <i class="fa fa-hourglass-half"></i> Expira en [[ cacheInfo.time_left.formatted_short || cacheInfo.time_left.formatted ]]
            </span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Pagination Top -->
        <div v-if="pagination.totalPages > 0" class="vue-pagination">
            <button @click="goToPage(pagination.currentPage - 1)" :disabled="pagination.currentPage <= 1">
                &laquo; {{ 'Previous'|get_plugin_lang('ProikosPlugin') }}
            </button>
            <span class="page-info">
                {{ 'Page'|get_plugin_lang('ProikosPlugin') }} [[ pagination.currentPage ]] {{ 'Of'|get_plugin_lang('ProikosPlugin') }} [[ pagination.totalPages ]]
            </span>
            <button @click="goToPage(pagination.currentPage + 1)" :disabled="pagination.currentPage >= pagination.totalPages">
                {{ 'Next'|get_plugin_lang('ProikosPlugin') }} &raquo;
            </button>
        </div>

        <!-- Empty State -->
        <div v-if="users.length === 0" class="empty-state">
            <div class="empty-icon">
                <i class="fa fa-inbox"></i>
            </div>
            <h4>No se encontraron registros</h4>
            <p>Intente con otros filtros de búsqueda</p>
        </div>

        <!-- Table -->
        <table v-else class="table table-hover table-striped table-bordered data_table" id="user_tables">
            <thead>
            <tr class="row_odd">
                <th class="th-header">Nº</th>
                <th class="th-header">{{ 'Course'|get_plugin_lang('ProikosPlugin') }}</th>
                <th class="th-header">{{ 'Session'|get_plugin_lang('ProikosPlugin') }}</th>
                <th class="th-header">{{ 'LastNamesAndFirstNames'|get_plugin_lang('ProikosPlugin') }}</th>
                <th class="th-header">{{ 'DNI'|get_plugin_lang('ProikosPlugin') }}</th>
                <th class="th-header">{{ 'ContratingCompanyRUC'|get_plugin_lang('ProikosPlugin') }}</th>
                <th class="th-header">{{ 'ContratingCompanyName'|get_plugin_lang('ProikosPlugin') }}</th>
                <th class="th-header">{{ 'Sede'|get_plugin_lang('ProikosPlugin') }}</th>
                <th class="th-header">{{ 'EntranceExam'|get_plugin_lang('ProikosPlugin') }}</th>
                <th class="th-header">{{ 'Workshop'|get_plugin_lang('ProikosPlugin') }}</th>
                <th class="th-header">{{ 'ExitExam'|get_plugin_lang('ProikosPlugin') }}</th>
                <th class="th-header">{{ 'Average'|get_plugin_lang('ProikosPlugin') }}</th>
                <th class="th-header">{{ 'Score'|get_plugin_lang('ProikosPlugin') }}</th>
                <th style="width: 120px" class="th-header">{{ 'Status'|get_plugin_lang('ProikosPlugin') }}</th>
                <th class="th-header">{{ 'DownloadCertificate'|get_plugin_lang('ProikosPlugin') }}</th>
                <th style="width: 100px" class="th-header">{{ 'CertificateValidity'|get_plugin_lang('ProikosPlugin') }}</th>
                <th style="width: 100px" class="th-header">{{ 'CertificatesAttachedStudent'|get_plugin_lang('ProikosPlugin') }}</th>
                <th style="width: 90px" class="th-header">{{ 'Incidents'|get_plugin_lang('ProikosPlugin') }}</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(user, index) in users" :key="user.id">
                <td>[[ user.id ]]</td>
                <td>[[ user.session_category_name ]]</td>
                <td>[[ user.session_name ]]</td>
                <td>[[ user.student ]]</td>
                <td>[[ user.DNI ]]</td>
                <td>[[ user.ruc_company ]]</td>
                <td>[[ user.name_company ]]</td>
                <td>[[ user.area ]]</td>
                <td class="text-center" :class="getScoreClass(user.exams ? user.exams.examen_de_entrada : 0)">
                    [[ user.exams && user.exams.examen_de_entrada ? user.exams.examen_de_entrada : 0 ]]
                </td>
                <td class="text-center" :class="getScoreClass(user.exams ? user.exams.taller : 0)">
                    [[ user.exams && user.exams.taller ? user.exams.taller : 0 ]]
                </td>
                <td class="text-center" :class="getScoreClass(user.exams ? user.exams.examen_de_salida : 0)">
                    [[ user.exams && user.exams.examen_de_salida ? user.exams.examen_de_salida : 0 ]]
                </td>
                <td class="text-center" :class="getScoreClass(user.promedio_ponderado !== '-' ? user.promedio_ponderado : 0)">
                    <strong>[[ user.promedio_ponderado === '-' || user.promedio_ponderado <= 0 ? '-' : formatNumber(user.promedio_ponderado) ]]</strong>
                </td>
                <td class="text-center">
                    [[ user.score === '-' || user.score <= 0 ? '-' : formatNumber(user.score) ]]
                </td>
                <td style="text-align: center">
                    <span v-html="user.status"></span>
                    <br>
                    <span v-if="user.certificate_status == 1" style="font-size: 12px"><strong>Certificado:</strong> Vigente</span>
                    <span v-else-if="user.certificate_status == 2" style="font-size: 12px"><strong>Certificado:</strong> Caducado</span>
                    <span v-else-if="user.certificate_status == 3" style="font-size: 12px"><strong>Certificado:</strong> No generado</span>
                    <span v-else>-</span>
                </td>
                <td style="text-align: center" v-html="user.download"></td>
                <td>
                    <div v-if="user.certificate_date && user.certificate_date.created_at != '-'" style="font-size: 12px">
                        <strong>F.E:</strong> [[ user.certificate_date.created_at ]]<br>
                        <strong>F.V:</strong> [[ user.certificate_date.expiration_date ]]
                    </div>
                    <span v-else>-</span>
                </td>
                <td style="text-align: center">
                    <span v-html="user.cert"></span>
                    <span v-html="user.check_document"></span>
                </td>
                <td style="text-align: center">
                    <div class="link-group" role="group">
                        <span v-html="user.sustenance"></span>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>

        <!-- Pagination Bottom -->
        <div v-if="pagination.totalPages > 0" class="vue-pagination">
            <button @click="goToPage(1)" :disabled="pagination.currentPage <= 1" title="Primera página">
                <i class="fa fa-angle-double-left"></i>
            </button>
            <button @click="goToPage(pagination.currentPage - 1)" :disabled="pagination.currentPage <= 1">
                &laquo; {{ 'Previous'|get_plugin_lang('ProikosPlugin') }}
            </button>

            <template v-for="page in visiblePages">
                <button :key="page"
                        @click="goToPage(page)"
                        :class="{ active: page === pagination.currentPage }">
                    [[ page ]]
                </button>
            </template>

            <button @click="goToPage(pagination.currentPage + 1)" :disabled="pagination.currentPage >= pagination.totalPages">
                {{ 'Next'|get_plugin_lang('ProikosPlugin') }} &raquo;
            </button>
            <button @click="goToPage(pagination.totalPages)" :disabled="pagination.currentPage >= pagination.totalPages" title="Última página">
                <i class="fa fa-angle-double-right"></i>
            </button>

            <span class="page-info">
                Mostrando [[ users.length ]] de [[ pagination.totalUsers ]] registros
            </span>
        </div>
    </div>
</div>

<div>
    <h5>Nota:</h5>
    <ul>
        <li>(*) Datos externos</li>
    </ul>
</div>

<!-- Modal Exportación Excel -->
<style>
    .export-date-row { display:flex; gap:8px; align-items:flex-end; justify-content:center; margin-bottom:10px; }
    .export-date-col { text-align:left; }
    .export-date-col label { display:block; font-size:12px; color:#555; margin-bottom:3px; font-weight:600; }
    .export-date-col select { width:110px; }
    .export-date-separator { font-size:20px; color:#aaa; padding-bottom:4px; }
    #exportDateError { color:#c0392b; font-size:13px; margin-top:8px; display:none; }
    .export-range-badge { display:inline-block; background:#e8f5e9; color:#1d7b3e; border:1px solid #a5d6a7; border-radius:4px; padding:4px 10px; font-size:12px; margin-top:10px; }
</style>

<div class="modal fade" id="modalExportXls" tabindex="-1" role="dialog" aria-labelledby="modalExportXlsLabel" aria-hidden="true">
    <div class="modal-dialog" role="document" style="max-width: 500px;">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #1d7b3e; color: #fff;">
                <h5 class="modal-title" id="modalExportXlsLabel">
                    <i class="fa fa-file-excel-o"></i> Exportar Excel
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" style="color:#fff; opacity:1;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center" style="padding: 30px 24px;">

                <!-- Estado: selección de fechas -->
                <div id="exportStateSelect">
                    <div id="exportDateSection">
                        <p style="font-size:15px; font-weight:600; color:#333; margin-bottom:4px;">
                            <i class="fa fa-calendar" style="color:#1d7b3e;"></i> Seleccione el período a exportar
                        </p>
                        <p style="font-size:12px; color:#888; margin-bottom:18px;">Máximo 3 meses por exportación</p>

                        <div class="export-date-row">
                            <div class="export-date-col">
                                <label>Desde</label>
                                <div style="display:flex; gap:4px;">
                                    <select id="expFromMonth" class="form-control input-sm"></select>
                                    <select id="expFromYear"  class="form-control input-sm" style="width:78px;"></select>
                                </div>
                            </div>
                            <div class="export-date-separator">→</div>
                            <div class="export-date-col">
                                <label>Hasta <span style="font-weight:400; color:#aaa;">(máx. 3 meses)</span></label>
                                <div style="display:flex; gap:4px;">
                                    <select id="expToMonth" class="form-control input-sm"></select>
                                    <select id="expToYear"  class="form-control input-sm" style="width:78px;"></select>
                                </div>
                            </div>
                        </div>

                        <div id="exportDateError"></div>
                        <div id="exportRangeBadge" class="export-range-badge" style="display:none;"></div>
                    </div>

                    <div id="exportSessionNote" style="display:none; padding:10px 0 4px;">
                        <p style="font-size:15px; font-weight:600; color:#333; margin-bottom:4px;">
                            <i class="fa fa-graduation-cap" style="color:#1d7b3e;"></i> Exportar sesión seleccionada
                        </p>
                        <p style="font-size:12px; color:#888;">Se exportarán todos los inscritos de la sesión. El rango de fechas no aplica.</p>
                    </div>

                    <div style="margin-top:20px;">
                        <button class="btn btn-success" onclick="confirmExportDates()" style="padding:8px 28px; font-size:14px;">
                            <i class="fa fa-cog"></i> Generar reporte
                        </button>
                    </div>
                </div>

                <!-- Estado: generando -->
                <div id="exportStateGenerating" style="display:none;">
                    <div style="margin-bottom: 18px;">
                        <div class="spinner-container" style="margin: 0 auto 15px;">
                            <div class="spinner-ring"></div>
                            <div class="spinner-ring"></div>
                            <div class="spinner-ring"></div>
                        </div>
                    </div>
                    <p class="preloader-text" style="font-size: 16px; font-weight: 600; color: #333; margin-bottom: 6px;">Generando reporte...</p>
                    <p style="font-size: 13px; color: #666;">Por favor espere, esto puede tomar unos segundos.</p>
                    <div id="exportGeneratingRange" style="margin-top:10px; font-size:12px; color:#888;"></div>
                    <div class="progress-bar-container" style="margin: 15px auto 0;">
                        <div class="progress-bar-animated"></div>
                    </div>
                </div>

                <!-- Estado: listo -->
                <div id="exportStateReady" style="display:none;">
                    <div style="font-size: 54px; color: #1d7b3e; margin-bottom: 12px;">
                        <i class="fa fa-check-circle"></i>
                    </div>
                    <p id="exportReadyMessage" style="font-size: 15px; font-weight: 600; color: #333; margin-bottom: 6px;"></p>
                    <p id="exportReadyRange" style="font-size:13px; color:#888; margin-bottom:16px;"></p>
                    <a id="exportDownloadBtn" href="#" class="btn btn-success btn-lg" style="padding: 10px 30px; font-size: 15px;">
                        <i class="fa fa-download"></i> Descargar Excel
                    </a>
                    <div id="exportCachedNote" style="margin-top: 14px; display:none;">
                        <p style="font-size: 12px; color: #888; margin-bottom: 8px;">
                            <i class="fa fa-info-circle"></i> Este archivo fue generado hoy y está en caché.
                        </p>
                        <button class="btn btn-default btn-sm" onclick="regenerateExport()">
                            <i class="fa fa-refresh"></i> Regenerar
                        </button>
                        <button class="btn btn-default btn-sm" onclick="backToExportSelect()" style="margin-left:6px;">
                            <i class="fa fa-calendar"></i> Cambiar período
                        </button>
                    </div>
                    <div id="exportNewRangeNote" style="margin-top:12px; display:none;">
                        <button class="btn btn-default btn-sm" onclick="backToExportSelect()">
                            <i class="fa fa-calendar"></i> Exportar otro período
                        </button>
                    </div>
                </div>

                <!-- Estado: error -->
                <div id="exportStateError" style="display:none;">
                    <div style="font-size: 54px; color: #c0392b; margin-bottom: 12px;">
                        <i class="fa fa-times-circle"></i>
                    </div>
                    <p style="font-size: 15px; font-weight: 600; color: #333; margin-bottom: 8px;">Error al generar el reporte</p>
                    <p id="exportErrorMessage" style="font-size: 13px; color: #666; margin-bottom: 18px;"></p>
                    <button class="btn btn-primary" onclick="retryExport()" style="margin-right:8px;">
                        <i class="fa fa-refresh"></i> Reintentar
                    </button>
                    <button class="btn btn-default" onclick="backToExportSelect()">
                        <i class="fa fa-calendar"></i> Cambiar período
                    </button>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ver Detalles de Incidencia -->
<div class="modal fade" id="modalVerIncidencia" tabindex="-1" role="dialog" aria-labelledby="modalVerIncidenciaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalVerIncidenciaLabel">Detalles de Incidencia</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="loadingSpinner" class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    <p>Cargando datos...</p>
                </div>

                <div id="incidenciaContent" style="display: none;">
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong>Información del Usuario</strong>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>ID Usuario:</strong> <span id="incidencia_user_id">-</span></p>
                                    <p><strong>Estudiante:</strong> <span id="incidencia_user_name">-</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>ID Sesión:</strong> <span id="incidencia_session_id">-</span></p>
                                    <p><strong>Nombre de la Sesión:</strong> <span id="incidencia_session_name">-</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong>Tipos de Incidencia</strong>
                        </div>
                        <div class="card-body">
                            <div id="incidencia_codes" class="badge-group"></div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong>Comentario</strong>
                        </div>
                        <div class="card-body">
                            <p id="incidencia_comment" class="mb-0">-</p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-light">
                            <strong>Información de Registro</strong>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Creado:</strong> <span id="incidencia_created">-</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Actualizado:</strong> <span id="incidencia_updated">-</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="errorMessage" class="alert alert-danger" style="display: none;">
                    <strong>Error:</strong> <span id="errorText"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
    .badge-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .modal-title{
        margin: 0;
        padding: 0;
        font-weight: 800;
        font-size: 16px;
    }
    .modal-header .close {
        margin-top: -22px;
    }
    .badge-group .badge {
        padding: 8px 12px;
        font-size: 14px;
    }
    #incidencia_codes .badge-info{
        background-color: #da0000 !important;
    }
    .modal-header.bg-info {
        background-color: #17a2b8 !important;
    }
    .modal-header.bg-info .text-white {
        color: white !important;
    }
    .spinner-border {
        width: 3rem;
        height: 3rem;
    }
</style>

<script>
    // Vue.js App
    var vueApp = new Vue({
        el: '#vue-data-app',
        delimiters: ['[[', ']]'],
        data: {
            loading: true,
            loadingMessage: 'Cargando datos...',
            error: false,
            errorMessage: '',
            users: [],
            cacheInfo: null, // NUEVO
            pagination: {
                currentPage: 1,
                totalPages: 0,
                totalUsers: 0,
                perPage: {{ perPage }}
        },
        params: {{ vue_params|raw }}
    },
    computed: {
        visiblePages: function() {
            var pages = [];
            var start = Math.max(1, this.pagination.currentPage - 2);
            var end = Math.min(this.pagination.totalPages, this.pagination.currentPage + 2);

            for (var i = start; i <= end; i++) {
                pages.push(i);
            }
            return pages;
        }
    },
    mounted: function() {
        this.pagination.currentPage = this.params.page || 1;
        this.pagination.perPage = this.params.perPage || 25;
        this.loadData();
    },
    methods: {
        formatNumber: function(value) {
            var num = parseFloat(value);
            if (isNaN(num)) return '0.00';
            return num.toFixed(2);
        },

        loadData: function() {
            var self = this;
            self.loading = true;
            self.error = false;
            self.loadingMessage = 'Consultando base de datos...';

            var url = this.params.ajaxUrl + '?action=get_data_report';
            url += '&keyword=' + encodeURIComponent(this.params.keyword || '');
            url += '&course_id=' + encodeURIComponent(this.params.course_id || '%');
            url += '&session_id=' + encodeURIComponent(this.params.session_id || '%');
            url += '&ruc=' + encodeURIComponent(this.params.ruc || '0');
            url += '&date_from=' + encodeURIComponent(this.params.date_from || '');
            url += '&date_to=' + encodeURIComponent(this.params.date_to || '');
            url += '&page=' + this.pagination.currentPage;
            url += '&perPage=' + this.pagination.perPage;

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                timeout: 60000,
                success: function(response) {
                    self.loadingMessage = 'Procesando datos...';

                    setTimeout(function() {
                        if (response.success) {
                            self.users = response.data.users || [];

                            // Actualizar información del caché
                            self.cacheInfo = response.cache_info || null;

                            if (response.data.pagination) {
                                self.pagination.currentPage = response.data.pagination.currentPage || 1;
                                self.pagination.totalPages = response.data.pagination.totalPages || 0;
                                self.pagination.totalUsers = response.data.pagination.totalUsers || 0;
                                self.pagination.perPage = response.data.pagination.perPage || 25;
                            }
                        } else {
                            self.error = true;
                            self.errorMessage = response.message || 'Error desconocido';
                        }
                        self.loading = false;
                    }, 300);
                },
                error: function(xhr, status, error) {
                    self.loading = false;
                    self.error = true;
                    if (status === 'timeout') {
                        self.errorMessage = 'Tiempo de espera agotado. Por favor, intente nuevamente.';
                    } else {
                        self.errorMessage = 'Error de conexión: ' + error;
                    }
                    console.error('Error AJAX:', xhr, status, error);
                }
            });
        },

        goToPage: function(page) {
            if (page >= 1 && page <= this.pagination.totalPages && page !== this.pagination.currentPage) {
                this.pagination.currentPage = page;
                this.loadData();

                // Scroll to top
                $('html, body').animate({
                    scrollTop: $('#vue-data-app').offset().top - 100
                }, 300);
            }
        },

        getScoreClass: function(score) {
            var numScore = parseFloat(score) || 0;
            if (numScore === 0) return 'default-text';
            if (numScore <= 10) return 'red-text';
            return 'blue-text';
        }
    }
    });

    // jQuery para el modal de incidencias (mantener código existente)
    $(document).ready(function() {
        const sustenance_options = {
            99 : 'Sin observaciones',
            1 : 'Falta examen entrada',
            2 : 'Falta examen salida',
            3 : 'Falta taller',
            4 : 'No ingreso al curso',
            5 : 'No alcanzo nota minima',
            6 : 'Copio',
            7 : 'Conducta inapropiada',
            8 : 'No respondio al llamado',
            9 : 'Realizo otra actividad',
            10 : 'Suplantación',
            11 : 'Otros'
        };

        $(document).on('click', '.viewModalSustenance', function(e) {
            e.preventDefault();
            const sustenanceId = $(this).data('sustenance-id');

            if (!sustenanceId) {
                mostrarError('ID de incidencia no válido');
                return;
            }

            limpiarModal();
            $('#loadingSpinner').show();
            $('#incidenciaContent').hide();
            $('#errorMessage').hide();
            $('#modalVerIncidencia').modal('show');
            cargarDetallesIncidencia(sustenanceId);
        });

        function cargarDetallesIncidencia(sustenanceId) {
            let urlAjax = '{{ url_ajax }}';
            $.ajax({
                url: urlAjax + '?action=get_sustenance_by_id',
                method: 'POST',
                dataType: 'json',
                data: { sustenance_id: sustenanceId },
                timeout: 10000,
                success: function(response) {
                    if (response.success && response.data) {
                        mostrarDetallesIncidencia(response.data);
                    } else {
                        mostrarError(response.message || 'No se pudieron cargar los datos');
                    }
                },
                error: function(xhr, status, error) {
                    let mensajeError = 'Error al cargar los datos';
                    if (xhr.status === 404) {
                        mensajeError = 'Archivo no encontrado';
                    } else if (status === 'timeout') {
                        mensajeError = 'Tiempo de espera agotado';
                    }
                    mostrarError(mensajeError);
                }
            });
        }

        function mostrarDetallesIncidencia(data) {
            $('#incidencia_user_id').text(data.user_id || '-');
            $('#incidencia_user_name').text(data.user_name || '-');
            $('#incidencia_session_id').text(data.session_id || '-');
            $('#incidencia_session_name').text(data.session_name || '-');
            mostrarCodigosIncidencia(data.sustenance_codes || '');
            $('#incidencia_comment').text(data.comment || 'Sin comentario');
            $('#incidencia_created').text(formatearFecha(data.created_at) || '-');
            $('#incidencia_updated').text(formatearFecha(data.updated_at) || '-');
            $('#loadingSpinner').hide();
            $('#incidenciaContent').fadeIn('fast');
        }

        function mostrarCodigosIncidencia(codesString) {
            const $codesContainer = $('#incidencia_codes');
            $codesContainer.empty();

            if (!codesString) {
                $codesContainer.append('<span class="text-muted">Sin incidencias registradas</span>');
                return;
            }

            const codes = codesString.split(',').map(c => c.trim());
            codes.forEach(function(code) {
                const intCode = parseInt(code);
                const label = sustenance_options[intCode] || 'Desconocido';
                let badgeClass = 'badge-warning';
                if (intCode === 11) badgeClass = 'badge-success';
                else if ([5, 6, 9].includes(intCode)) badgeClass = 'badge-danger';
                else if ([0, 1, 2, 3, 4, 7].includes(intCode)) badgeClass = 'badge-info';
                $codesContainer.append('<span class="badge ' + badgeClass + '">' + label + '</span>');
            });
        }

        function mostrarError(mensaje) {
            $('#loadingSpinner').hide();
            $('#incidenciaContent').hide();
            $('#errorMessage').show();
            $('#errorText').text(mensaje);
        }

        function limpiarModal() {
            $('#incidencia_user_id').text('-');
            $('#incidencia_user_name').text('-');
            $('#incidencia_session_id').text('-');
            $('#incidencia_session_name').text('-');
            $('#incidencia_codes').empty();
            $('#incidencia_comment').text('-');
            $('#incidencia_created').text('-');
            $('#incidencia_updated').text('-');
            $('#errorMessage').hide();
        }

        function formatearFecha(fecha) {
            if (!fecha) return null;
            const date = new Date(fecha);
            if (isNaN(date.getTime())) return fecha;
            return date.toLocaleString('es-ES', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        $('#modalVerIncidencia').on('hidden.bs.modal', function() {
            limpiarModal();
            $('#loadingSpinner').hide();
            $('#incidenciaContent').hide();
        });
    });
</script>

<script>
    var _exportBaseUrl = '{{ data_report_url }}';
    var _exportCurrentDateFrom = '';
    var _exportCurrentDateTo   = '';

    var _expMonthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    function _buildMonthOptions(selectId, selectedVal) {
        var $s = $('#' + selectId).empty();
        $s.append('<option value="">Mes</option>');
        for (var i = 1; i <= 12; i++) {
            var v = String(i).padStart(2,'0');
            $s.append('<option value="'+v+'"'+(v===selectedVal?' selected':'')+'>'+_expMonthNames[i-1]+'</option>');
        }
    }

    function _buildYearOptions(selectId, selectedVal) {
        var $s = $('#' + selectId).empty();
        var now = new Date().getFullYear();
        $s.append('<option value="">Año</option>');
        for (var y = now; y >= now - 5; y--) {
            $s.append('<option value="'+y+'"'+(String(y)===String(selectedVal)?' selected':'')+'>'+y+'</option>');
        }
    }

    function _lastDayOf(year, month) {
        return new Date(parseInt(year), parseInt(month), 0).getDate();
    }

    function _monthLabel(ym) { // ym = "YYYY-MM"
        if (!ym) return '';
        var parts = ym.split('-');
        return _expMonthNames[parseInt(parts[1])-1] + ' ' + parts[0];
    }

    function _rangeLabel(from, to) {
        return _monthLabel(from.substring(0,7)) + ' → ' + _monthLabel(to.substring(0,7));
    }

    function _hasSessionSelected() {
        var s = vueApp.params.session_id;
        return s && s !== '%' && s !== '0';
    }

    function openExportModal() {
        var p = vueApp.params;

        // Si hay una sesión seleccionada, se exporta toda la sesión sin filtro de fecha
        if (_hasSessionSelected()) {
            $('#exportDateSection').hide();
            $('#exportSessionNote').show();
        } else {
            $('#exportDateSection').show();
            $('#exportSessionNote').hide();
        }

        var preFrom = (p.date_from || '').substring(0,7); // "YYYY-MM"
        var preTo   = (p.date_to   || '').substring(0,7);

        _buildMonthOptions('expFromMonth', preFrom.substring(5,7));
        _buildYearOptions ('expFromYear',  preFrom.substring(0,4));
        _buildMonthOptions('expToMonth',   preTo.substring(5,7));
        _buildYearOptions ('expToYear',    preTo.substring(0,4));

        $('#exportDateError').hide().text('');
        $('#exportRangeBadge').hide();

        // Actualizar badge cuando cambian los selectores
        $('#expFromMonth, #expFromYear, #expToMonth, #expToYear').off('change.exp').on('change.exp', _updateRangeBadge);
        _updateRangeBadge();

        $('#exportStateSelect').show();
        $('#exportStateGenerating').hide();
        $('#exportStateReady').hide();
        $('#exportStateError').hide();
        $('#modalExportXls').modal('show');
    }

    function _updateRangeBadge() {
        var fm = $('#expFromMonth').val(), fy = $('#expFromYear').val();
        var tm = $('#expToMonth').val(),   ty = $('#expToYear').val();
        if (fm && fy && tm && ty) {
            var from = new Date(parseInt(fy), parseInt(fm)-1, 1);
            var to   = new Date(parseInt(ty), parseInt(tm)-1, 1);
            var diff = (to.getFullYear()-from.getFullYear())*12 + (to.getMonth()-from.getMonth());
            var label = _expMonthNames[parseInt(fm)-1]+' '+fy+' → '+_expMonthNames[parseInt(tm)-1]+' '+ty;
            if (diff < 0) {
                $('#exportRangeBadge').hide();
            } else {
                var months = diff + 1;
                $('#exportRangeBadge').text(label + ' (' + months + ' mes' + (months>1?'es':'') + ')').show();
            }
        } else if (fm && fy) {
            $('#exportRangeBadge').text(_expMonthNames[parseInt(fm)-1]+' '+fy+' (1 mes)').show();
        } else {
            $('#exportRangeBadge').hide();
        }
    }

    function confirmExportDates() {
        // Con sesión seleccionada se ignora el rango de fechas y se exporta toda la sesión
        if (_hasSessionSelected()) {
            _exportCurrentDateFrom = '';
            _exportCurrentDateTo   = '';
            $('#exportStateSelect').hide();
            $('#exportStateGenerating').show();
            $('#exportGeneratingRange').text('Sesión seleccionada');
            $('.preloader-text').text('Generando reporte...');
            startExport();
            return;
        }

        var fm = $('#expFromMonth').val(), fy = $('#expFromYear').val();
        var tm = $('#expToMonth').val(),   ty = $('#expToYear').val();
        var $err = $('#exportDateError');
        $err.hide().text('');

        if (!fm || !fy) { $err.text('Seleccione el mes y año de inicio.').show(); return; }

        var fromDate = new Date(parseInt(fy), parseInt(fm)-1, 1);
        var toDate;

        if (tm && ty) {
            toDate = new Date(parseInt(ty), parseInt(tm)-1, 1);
            if (toDate < fromDate) { $err.text('La fecha "Hasta" no puede ser anterior a "Desde".').show(); return; }
            var diff = (toDate.getFullYear()-fromDate.getFullYear())*12 + (toDate.getMonth()-fromDate.getMonth());
            if (diff > 2) {
                toDate = new Date(fromDate.getFullYear(), fromDate.getMonth()+2, 1);
                ty = toDate.getFullYear();
                tm = String(toDate.getMonth()+1).padStart(2,'0');
                $err.text('Rango ajustado a 3 meses máximo.').show();
            }
        } else {
            toDate = fromDate;
            ty = fy; tm = fm;
        }

        _exportCurrentDateFrom = fy+'-'+fm+'-01';
        var toLastDay = _lastDayOf(ty, tm);
        _exportCurrentDateTo = ty+'-'+tm+'-'+toLastDay;

        var rangeText = _rangeLabel(_exportCurrentDateFrom, _exportCurrentDateTo);
        $('#exportStateSelect').hide();
        $('#exportStateGenerating').show();
        $('#exportGeneratingRange').text(rangeText);
        $('.preloader-text').text('Generando reporte...');

        startExport();
    }

    function backToExportSelect() {
        $('#exportStateReady').hide();
        $('#exportStateError').hide();
        $('#exportStateGenerating').hide();
        $('#exportStateSelect').show();
        _exportCurrentDateFrom = '';
        _exportCurrentDateTo   = '';
    }

    function retryExport() {
        $('#exportStateError').hide();
        $('#exportStateGenerating').show();
        $('.preloader-text').text('Generando reporte...');
        startExport();
    }

    function startExport() {
        var p = vueApp.params;
        // Con sesión seleccionada no se envían fechas (se exporta toda la sesión)
        var dateFrom = _hasSessionSelected() ? '' : (_exportCurrentDateFrom || p.date_from || '');
        var dateTo   = _hasSessionSelected() ? '' : (_exportCurrentDateTo   || p.date_to   || '');

        $.ajax({
            url: _exportBaseUrl,
            type: 'GET',
            dataType: 'json',
            timeout: 300000,
            data: {
                action: 'xls_async',
                course_id: p.course_id || '%',
                session_id: p.session_id || '%',
                keyword: p.keyword || '',
                ruc: p.ruc || '0',
                date_from: dateFrom,
                date_to: dateTo
            },
            success: function(response) {
                if (response && response.success && response.token) {
                    $('#exportStateGenerating').hide();
                    $('#exportStateReady').show();
                    var downloadUrl = _exportBaseUrl + '?action=xls_download&token=' + encodeURIComponent(response.token);
                    $('#exportDownloadBtn').attr('href', downloadUrl);
                    var rangeText = dateFrom ? _rangeLabel(dateFrom, dateTo) : '';
                    $('#exportReadyRange').text(rangeText ? 'Período: ' + rangeText : '');
                    if (response.cached) {
                        $('#exportReadyMessage').text('¡Reporte disponible! (generado hoy)');
                        $('#exportCachedNote').show();
                        $('#exportNewRangeNote').hide();
                    } else {
                        $('#exportReadyMessage').text('¡Reporte generado correctamente!');
                        $('#exportCachedNote').hide();
                        $('#exportNewRangeNote').show();
                    }
                } else if (response && response.generating) {
                    $('.preloader-text').text('Procesando reporte...');
                    setTimeout(startExport, 5000);
                } else {
                    showExportError((response && response.message) || 'Error desconocido al generar el reporte.');
                }
            },
            error: function(xhr, status) {
                if (status === 'parseerror' && xhr.status === 200) {
                    $('.preloader-text').text('Procesando reporte...');
                    setTimeout(startExport, 5000);
                } else if (status === 'timeout') {
                    showExportError('El servidor tardó demasiado. Intente con un rango menor.');
                } else {
                    showExportError('Error del servidor (código ' + xhr.status + '). Intente nuevamente.');
                }
            }
        });
    }

    function regenerateExport() {
        var p = vueApp.params;
        // Con sesión seleccionada no se envían fechas (mismo criterio que startExport)
        var dateFrom = _hasSessionSelected() ? '' : (_exportCurrentDateFrom || p.date_from || '');
        var dateTo   = _hasSessionSelected() ? '' : (_exportCurrentDateTo   || p.date_to   || '');
        $.ajax({
            url: _exportBaseUrl,
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'xls_delete_cache',
                course_id: p.course_id || '%',
                session_id: p.session_id || '%',
                keyword: p.keyword || '',
                ruc: p.ruc || '0',
                date_from: dateFrom,
                date_to: dateTo
            },
            complete: function() {
                $('#exportStateReady').hide();
                $('#exportStateGenerating').show();
                $('.preloader-text').text('Generando reporte...');
                $('#exportGeneratingRange').text(dateFrom ? _rangeLabel(dateFrom, dateTo) : '');
                startExport();
            }
        });
    }

    function showExportError(msg) {
        $('#exportStateGenerating').hide();
        $('#exportStateError').show();
        $('#exportErrorMessage').text(msg);
    }
</script>

<script>
    // Scripts para gestión de caché
    $(document).ready(function() {
        // Limpiar todo el caché (solo admin)
        $('#btn-clear-cache').click(function() {
            if (confirm('¿Está seguro de limpiar TODO el caché del sistema?\n\nEsto afectará a todos los usuarios y hará que las próximas consultas sean más lentas.')) {
                var $btn = $(this);
                $btn.prop('disabled', true).find('img').css('opacity', '0.5');

                $.ajax({
                    url: '{{ data_report_url }}',
                    data: { action: 'clear_cache' },
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('✓ ' + response.message);
                            location.reload();
                        } else {
                            alert('✗ ' + (response.message || 'Error desconocido'));
                            $btn.prop('disabled', false).find('img').css('opacity', '1');
                        }
                    },
                    error: function() {
                        alert('✗ Error al comunicarse con el servidor');
                        $btn.prop('disabled', false).find('img').css('opacity', '1');
                    }
                });
            }
        });

        // Limpiar solo caché expirado (solo admin)
        $('#btn-clear-expired-cache').click(function() {
            var $btn = $(this);
            $btn.prop('disabled', true).find('img').css('opacity', '0.5');

            $.ajax({
                url: '{{ data_report_url }}',
                data: { action: 'clear_expired_cache' },
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('✓ ' + response.message);
                        location.reload();
                    } else {
                        alert('✗ ' + (response.message || 'Error desconocido'));
                    }
                    $btn.prop('disabled', false).find('img').css('opacity', '1');
                },
                error: function() {
                    alert('✗ Error al comunicarse con el servidor');
                    $btn.prop('disabled', false).find('img').css('opacity', '1');
                }
            });
        });

        // Limpiar solo mi caché (gestores de cupo)
        $('#btn-clear-user-cache').click(function() {
            if (confirm('¿Desea limpiar su caché personal?\n\nEsto solo afectará sus consultas.')) {
                var $btn = $(this);
                $btn.prop('disabled', true).find('img').css('opacity', '0.5');

                $.ajax({
                    url: '{{ data_report_url }}',
                    data: { action: 'clear_user_cache' },
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('✓ ' + response.message);
                            location.reload();
                        } else {
                            alert('✗ ' + (response.message || 'Error desconocido'));
                        }
                        $btn.prop('disabled', false).find('img').css('opacity', '1');
                    },
                    error: function() {
                        alert('✗ Error al comunicarse con el servidor');
                        $btn.prop('disabled', false).find('img').css('opacity', '1');
                    }
                });
            }
        });

        // Click en info de caché para ver detalles
        $('#cache-info').click(function() {
            $.ajax({
                url: '{{ data_report_url }}',
                data: { action: 'cache_info' },
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var info = response.data;
                        var message = 'Información del Caché:\n\n';
                        message += '👤 Rol: ' + info.current_user_role + '\n';
                        message += '━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
                        message += '📁 Total de archivos: ' + info.total_files + '\n';
                        message += '👨‍💼 Archivos de Admins: ' + info.admin_files + '\n';
                        message += '🏢 Archivos de Gestores: ' + info.contractor_files + '\n';

                        // Desglose por RUC si hay
                        if (info.by_ruc && Object.keys(info.by_ruc).length > 0) {
                            message += '\n📋 Desglose por RUC:\n';
                            for (var ruc in info.by_ruc) {
                                message += '   • RUC ' + ruc + ': ' + info.by_ruc[ruc] + ' archivos\n';
                            }
                        }

                        message += '\n━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
                        message += '✓ Archivos válidos: ' + info.valid_files + '\n';
                        message += '✗ Archivos expirados: ' + info.expired_files + '\n';
                        message += '🔵 Archivos de tu rol: ' + info.role_files + '\n';
                        message += '💾 Tamaño total: ' + info.total_size_mb + ' MB\n';
                        message += '⏱️ Duración: ' + info.cache_lifetime_formatted + '\n';
                        alert(message);
                    }
                }
            });
        });
    });
</script>
