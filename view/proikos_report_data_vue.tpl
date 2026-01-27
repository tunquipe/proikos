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
