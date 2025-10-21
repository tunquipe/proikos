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
</style>
<div class="pagination">
    <ul class="pagination">
        {% if data.pagination.currentPage > 1 %}
        <li><a href="?page={{ data.pagination.currentPage - 1 }}&perPage={{ perPage }}" aria-label="{{ 'Previous'|get_plugin_lang('ProikosPlugin') }}">&laquo; {{ 'Previous'|get_plugin_lang('ProikosPlugin') }}</a></li>
        {% else %}
        <li class="disabled"><a href="#" aria-label="Previous">&laquo; {{ 'Previous'|get_plugin_lang('ProikosPlugin') }}</a></li>
        {% endif %}

        <li class="disabled"><span>{{ 'Page'|get_plugin_lang('ProikosPlugin') }} {{ data.pagination.currentPage }} {{ 'Of'|get_plugin_lang('ProikosPlugin') }} {{ data.pagination.totalPages }}</span></li>

        {% if data.pagination.currentPage < data.pagination.totalPages %}
        <li><a href="?page={{ data.pagination.currentPage + 1 }}&perPage={{ perPage }}" aria-label="{{ 'Next'|get_plugin_lang('ProikosPlugin') }}">{{ 'Next'|get_plugin_lang('ProikosPlugin') }} &raquo;</a></li>
        {% else %}
        <li class="disabled"><a href="#" aria-label="Next">{{ 'Next'|get_plugin_lang('ProikosPlugin') }} &raquo;</a></li>
        {% endif %}
    </ul>
</div>
<table class="table table-hover table-striped table-bordered data_table" id="user_tables">
    <tr class="row_odd">
        <th class="th-header">Nº</th>
        
        <!--<th class="th-header">Fecha</th>
        <th class="th-header">Nº Horas</th> -->
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
        <th class="th-header">{{ 'Score'|get_plugin_lang('ProikosPlugin') }}</th>
        <th style="width: 120px" class="th-header">{{ 'Status'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'DownloadCertificate'|get_plugin_lang('ProikosPlugin') }}</th>
        <th style="width: 100px" class="th-header">{{ 'CertificateValidity'|get_plugin_lang('ProikosPlugin') }}</th>
        <th style="width: 100px" class="th-header">{{ 'CertificatesAttachedStudent'|get_plugin_lang('ProikosPlugin') }}</th>
        <th style="width: 90px" class="th-header">{{ 'Incidents'|get_plugin_lang('ProikosPlugin') }}</th>

    </tr>
    {% if data.users %}
    {% for user in data.users %}

    <tr>
        <td>{{ user.id }}</td>

        <!--<td>{{ user.registration_date }}</td>
            <td>{{ user.time_course }}</td> -->
        <td>{{ user.session_category_name }}</td>
        <td>{{ user.session_name }}</td>
        <td>{{ user.student }}</td>
        <td>{{ user.DNI }}</td>
        <td>{{ user.ruc_company }}</td>
        <td>{{ user.name_company }}</td>
        <td>{{ user.area }}</td>

        <td class="text-center {% if user.exams.examen_de_entrada is not defined or user.exams.examen_de_entrada == 0 %}default-text{% elseif user.exams.examen_de_entrada <= 10 %}red-text{% elseif user.exams.examen_de_entrada >= 10 %}blue-text{% endif %}">
            {% if user.exams.examen_de_entrada == 0 or user.exams.examen_de_entrada is empty %}
            -
            {% else %}
            {{ user.exams.examen_de_entrada }}
            {% endif %}
        </td>

        <td class="text-center {% if user.exams.taller is not defined or user.exams.taller == 0 %}default-text{% elseif user.exams.taller <= 10 %}red-text{% elseif user.exams.taller >= 10 %}blue-text{% endif %}">
            {% if user.exams.taller == 0 or user.exams.taller is empty %}
            -
            {% else %}
            {{ user.exams.taller }}
            {% endif %}
        </td>

        <td class="text-center {% if user.exams.examen_de_salida is not defined or user.exams.examen_de_salida == 0 %}default-text{% elseif user.exams.examen_de_salida <= 10 %}red-text{% elseif user.exams.examen_de_salida >= 10 %}blue-text{% endif %}">
            {% if user.exams.examen_de_salida == 0 or user.exams.examen_de_salida is empty %}
            -
            {% else %}
            {{ user.exams.examen_de_salida }}
            {% endif %}
        </td>


        <td>{{ user.score }}</td>
        <td style="text-align: center">
            {{ user.status }}
            <br>
            {% if user.certificate_status == 1 %}
            <span style="font-size: 12px"><strong>Certificado:</strong> Vigente</span>
            {% elseif user.certificate_status == 2 %}
            <span style="font-size: 12px"><strong>Certificado:</strong> Caducado</span>
            {% elseif user.certificate_status == 3 %}
            <span style="font-size: 12px"><strong>Certificado:</strong> No generado</span>
            {% else %}
            - <!-- Si no tiene un valor 1, 2 o 3, puedes mostrar un guion o un texto por defecto -->
            {% endif %}
        </td>

        <td style="text-align: center">
            {{ user.download }}
        </td>
        <td>
            {% if user.certificate_date.created_at != '-' %}
            <div style="font-size: 12px">
                <strong>F.E:</strong> {{ user.certificate_date.created_at }}<br>
                <strong>F.V:</strong> {{ user.certificate_date.expiration_date }}
            </div>
            {% else %}
            -
            {% endif %}
        </td>
        <td style="text-align: center">
            {{ user.cert }}
            {{ user.check_document }}
        </td>
        <td style="text-align: center">
            <div class="link-group" role="group" >
                {{ user.sustenance }}
            </div>
        </td>

    </tr>
    {% endfor %}
    {% endif %}
</table>

<div class="pagination">
    <ul class="pagination">
        {% if data.pagination.currentPage > 1 %}
        <li><a href="?page={{ data.pagination.currentPage - 1 }}&perPage={{ perPage }}" aria-label="{{ 'Previous'|get_plugin_lang('ProikosPlugin') }}">&laquo; {{ 'Previous'|get_plugin_lang('ProikosPlugin') }}</a></li>
        {% else %}
        <li class="disabled"><a href="#" aria-label="Previous">&laquo; {{ 'Previous'|get_plugin_lang('ProikosPlugin') }}</a></li>
        {% endif %}

        <li class="disabled"><span>{{ 'Page'|get_plugin_lang('ProikosPlugin') }} {{ data.pagination.currentPage }} {{ 'Of'|get_plugin_lang('ProikosPlugin') }} {{ data.pagination.totalPages }}</span></li>

        {% if data.pagination.currentPage < data.pagination.totalPages %}
        <li><a href="?page={{ data.pagination.currentPage + 1 }}&perPage={{ perPage }}" aria-label="{{ 'Next'|get_plugin_lang('ProikosPlugin') }}">{{ 'Next'|get_plugin_lang('ProikosPlugin') }} &raquo;</a></li>
        {% else %}
        <li class="disabled"><a href="#" aria-label="Next">{{ 'Next'|get_plugin_lang('ProikosPlugin') }} &raquo;</a></li>
        {% endif %}
    </ul>
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
                <!-- Indicador de carga -->
                <div id="loadingSpinner" class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    <p>Cargando datos...</p>
                </div>

                <!-- Contenedor de datos (oculto hasta cargar) -->
                <div id="incidenciaContent" style="display: none;">

                    <!-- Información del Usuario -->
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

                    <!-- Tipos de Incidencia -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong>Tipos de Incidencia</strong>
                        </div>
                        <div class="card-body">
                            <div id="incidencia_codes" class="badge-group">
                                <!-- Se llena dinámicamente -->
                            </div>
                        </div>
                    </div>

                    <!-- Comentario -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong>Comentario</strong>
                        </div>
                        <div class="card-body">
                            <p id="incidencia_comment" class="mb-0">-</p>
                        </div>
                    </div>

                    <!-- Fechas -->
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

                <!-- Mensaje de error -->
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
    $(document).ready(function() {

        /**
         * Opciones disponibles de incidencia
         */
        const sustenance_options = {
            0 : 'Sin observaciones',
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

        /**
         * Evento para abrir modal al hacer clic en elementos con clase .viewModalSustenance
         */
        $(document).on('click', '.viewModalSustenance', function(e) {
            e.preventDefault();

            const sustenanceId = $(this).data('sustenance-id');

            if (!sustenanceId) {
                mostrarError('ID de incidencia no válido');
                return;
            }

            console.log('Abriendo modal para incidencia ID:', sustenanceId);

            // Limpiar modal
            limpiarModal();

            // Mostrar spinner
            $('#loadingSpinner').show();
            $('#incidenciaContent').hide();
            $('#errorMessage').hide();

            // Abrir modal
            $('#modalVerIncidencia').modal('show');

            // Cargar datos
            cargarDetallesIncidencia(sustenanceId);
        });

        /**
         * Cargar detalles de incidencia desde el servidor
         */
        function cargarDetallesIncidencia(sustenanceId) {
            let urlAjax = '{{ url_ajax }}'
            $.ajax({
                url: urlAjax + '?action=get_sustenance_by_id',
                method: 'POST',
                dataType: 'json',
                data: {
                    sustenance_id: sustenanceId
                },
                timeout: 10000,
                success: function(response) {
                    if (response.success && response.data) {
                        mostrarDetallesIncidencia(response.data);
                    } else {
                        mostrarError(response.message || 'No se pudieron cargar los datos');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', {status: xhr.status, error: error});

                    let mensajeError = 'Error al cargar los datos';
                    if (xhr.status === 404) {
                        mensajeError = 'Archivo no encontrado: get_incidencia_details.php';
                    } else if (status === 'timeout') {
                        mensajeError = 'Tiempo de espera agotado';
                    }

                    mostrarError(mensajeError);
                }
            });
        }

        /**
         * Mostrar detalles de incidencia en el modal
         */
        function mostrarDetallesIncidencia(data) {
            // Información del usuario
            $('#incidencia_user_id').text(data.user_id || '-');
            $('#incidencia_user_name').text(data.user_name || '-');
            $('#incidencia_session_id').text(data.session_id || '-');
            $('#incidencia_session_name').text(data.session_name || '-');

            // Tipos de incidencia
            mostrarCodigosIncidencia(data.sustenance_codes || '');

            // Comentario
            $('#incidencia_comment').text(data.comment || 'Sin comentario');

            // Calificación
            const grade = data.grade ? parseFloat(data.grade).toFixed(2) : '-';
            $('#incidencia_grade').text(grade !== '-' ? grade : 'No especificada');

            // Fechas
            $('#incidencia_created').text(formatearFecha(data.created_at) || '-');
            $('#incidencia_updated').text(formatearFecha(data.updated_at) || '-');

            // Mostrar contenido y ocultar spinner
            $('#loadingSpinner').hide();
            $('#incidenciaContent').fadeIn('fast');
        }

        /**
         * Mostrar códigos de incidencia como badges
         */
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

                // Asignar color según el tipo
                let badgeClass = 'badge-warning';
                if (intCode === 11) { // SIN OBSERVACIONES
                    badgeClass = 'badge-success';
                } else if ([5, 6, 9].includes(intCode)) { // COPIA, CONDUCTA INAPROPIADA, SUPLANTACIÓN
                    badgeClass = 'badge-danger';
                } else if ([0, 1, 2, 3, 4, 7].includes(intCode)) { // Faltas y no ingreso
                    badgeClass = 'badge-info';
                }

                $codesContainer.append(
                    '<span class="badge ' + badgeClass + '">' + label + '</span>'
                );
            });
        }

        /**
         * Mostrar mensaje de error
         */
        function mostrarError(mensaje) {
            $('#loadingSpinner').hide();
            $('#incidenciaContent').hide();
            $('#errorMessage').show();
            $('#errorText').text(mensaje);
        }

        /**
         * Limpiar modal
         */
        function limpiarModal() {
            $('#incidencia_user_id').text('-');
            $('#incidencia_user_name').text('-');
            $('#incidencia_session_id').text('-');
            $('#incidencia_session_name').text('-');
            $('#incidencia_codes').empty();
            $('#incidencia_comment').text('-');
            $('#incidencia_grade').text('-');
            $('#incidencia_created').text('-');
            $('#incidencia_updated').text('-');
            $('#errorMessage').hide();
        }

        /**
         * Formatear fecha
         */
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

        /**
         * Resetear modal cuando se cierra
         */
        $('#modalVerIncidencia').on('hidden.bs.modal', function() {
            limpiarModal();
            $('#loadingSpinner').hide();
            $('#incidenciaContent').hide();
        });

    });
</script>
