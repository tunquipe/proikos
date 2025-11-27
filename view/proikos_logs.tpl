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
        <th class="th-header">Nº de log</th>
        <th class="th-header" style="width: 300px;">{{ 'Session'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">Codigo de Registro - Session</th>
        <th class="th-header">ID de usuario</th>
        <th class="th-header">{{ 'LastNamesAndFirstNames'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'DNI'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'ContratingCompanyRUC'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'ContratingCompanyName'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'EntranceExam'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'Workshop'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'ExitExam'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'Average'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'Score'|get_plugin_lang('ProikosPlugin') }}</th>
        <th class="th-header">{{ 'Status'|get_plugin_lang('ProikosPlugin') }}</th>
        {% if is_platform_admin %}
            <th class="th-header" width="120px">{{ 'Actions'|get_plugin_lang('ProikosPlugin') }}</th>
        {% endif %}

    </tr>
    {% if data.users %}
    {% for user in data.users %}

    <tr>
        <td>{{ user.id }}</td>
        <td>{{ user.session_name }}</td>
        <td>{{ user.registration_session_user }}</td>
        <td>{{ user.user_id }}</td>
        <td>{{ user.student }}</td>
        <td>{{ user.dni }}</td>
        <td>{{ user.ruc_company }}</td>
        <td>{{ user.name_company }}</td>

        <td class="text-center {% if user.entrance_exam is not defined or user.entrance_exam == 0 %}default-text{% elseif user.entrance_exam <= 10 %}red-text{% elseif user.entrance_exam >= 10 %}blue-text{% endif %}">
            {% if user.entrance_exam == 0 or user.entrance_exam is empty %}
            0
            {% else %}
            {{ user.entrance_exam }}
            {% endif %}
        </td>

        <td class="text-center {% if user.workshop is not defined or user.workshop == 0 %}default-text{% elseif user.workshop <= 10 %}red-text{% elseif user.workshop >= 10 %}blue-text{% endif %}">
            {% if user.workshop == 0 or user.workshop is empty %}
            0
            {% else %}
            {{ user.workshop }}
            {% endif %}
        </td>

        <td class="text-center {% if user.exit_exam is not defined or user.exit_exam == 0 %}default-text{% elseif user.exit_exam <= 10 %}red-text{% elseif user.exit_exam >= 10 %}blue-text{% endif %}">
            {% if user.exit_exam == 0 or user.exit_exam is empty %}
            0
            {% else %}
            {{ user.exit_exam }}
            {% endif %}
        </td>

        <td class="text-center {% if user.promedio_ponderado is not defined or user.promedio_ponderado == 0 %}default-text{% elseif user.promedio_ponderado <= 10 %}red-text{% elseif user.promedio_ponderado >= 10 %}blue-text{% endif %}">
            <strong>{{ user.promedio_ponderado }}</strong>
        </td>
        <td class="text-center">{{ user.score }}% </td>
        <td style="text-align: center">
            {{ user.status }}
        </td>
        {% if is_platform_admin %}
        <td style="text-align: center">
            <div class="btn-group" role="group" aria-label="...">
                {{ user.actions }}
            </div>
        </td>
        {% endif %}

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

<!-- Modal para editar notas -->
<div class="modal fade" id="editScoresModal" tabindex="-1" role="dialog" aria-labelledby="editScoresModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="editScoresModalLabel">
                    <i class="fa fa-edit"></i> Editar Notas
                </h4>
            </div>
            <form id="formEditScores">
                <div class="modal-body">
                    <!-- Mensaje de alerta -->
                    <div id="editScoresMessage" class="alert" style="display: none;"></div>

                    <input type="hidden" id="edit_record_id" name="record_id">

                    <div class="alert alert-info">
                        <strong>Estudiante:</strong> <span id="studentName"></span>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_entrance_exam">
                                    Examen de Entrada (10%)
                                </label>
                                <input type="number"
                                       class="form-control score-input"
                                       id="edit_entrance_exam"
                                       name="entrance_exam"
                                       min="0"
                                       max="20"
                                       step="0.01"
                                       required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_workshop">
                                    Taller (60%)
                                </label>
                                <input type="number"
                                       class="form-control score-input"
                                       id="edit_workshop"
                                       name="workshop"
                                       min="0"
                                       max="20"
                                       step="0.01"
                                       required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_exit_exam">
                                    Examen de Salida (30%)
                                </label>
                                <input type="number"
                                       class="form-control score-input"
                                       id="edit_exit_exam"
                                       name="exit_exam"
                                       min="0"
                                       max="20"
                                       step="0.01"
                                       required>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Promedio Ponderado (Calculado)</label>
                                <div class="input-group">
                                    <input type="text"
                                           class="form-control"
                                           id="calculated_promedio"
                                           readonly
                                           style="background-color: #f5f5f5; font-weight: bold;">
                                    <span class="input-group-addon">
                                        <i class="fa fa-calculator"></i>
                                    </span>
                                </div>
                                <small class="text-muted">
                                    Fórmula: (Entrada × 0.10) + (Taller × 0.60) + (Salida × 0.30)
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Estado</label>
                                <div id="calculated_status" class="form-control"
                                     style="background-color: #f5f5f5; font-weight: bold;">
                                    -
                                </div>
                                <small class="text-muted">
                                    Aprobado: >= 13 | Desaprobado: < 13
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fa fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSaveScores">
                        <i class="fa fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {

        // Abrir modal con datos del registro
        $(document).on('click', '.btn-edit-scores', function(e) {
            e.preventDefault();

            var id = $(this).data('id');
            var entrance = $(this).data('entrance');
            var workshop = $(this).data('workshop');
            var exit = $(this).data('exit');
            var student = $(this).data('student');

            // Llenar el formulario
            $('#edit_record_id').val(id);
            $('#edit_entrance_exam').val(entrance);
            $('#edit_workshop').val(workshop);
            $('#edit_exit_exam').val(exit);
            $('#studentName').text(student);

            // Limpiar mensajes previos
            $('#editScoresMessage').hide().removeClass('alert-success alert-danger');

            // Calcular promedio inicial
            calculatePromedio();

            // Abrir modal
            $('#editScoresModal').modal('show');
        });

        // Calcular promedio cuando cambian las notas
        $(document).on('input', '.score-input', function() {
            calculatePromedio();
        });

        // Función para calcular el promedio ponderado
        function calculatePromedio() {
            var entrance = parseFloat($('#edit_entrance_exam').val()) || 0;
            var workshop = parseFloat($('#edit_workshop').val()) || 0;
            var exit = parseFloat($('#edit_exit_exam').val()) || 0;

            // Validar que estén en rango 0-20
            entrance = Math.min(20, Math.max(0, entrance));
            workshop = Math.min(20, Math.max(0, workshop));
            exit = Math.min(20, Math.max(0, exit));

            // Calcular promedio ponderado
            var promedio = (entrance * 0.10) + (workshop * 0.60) + (exit * 0.30);
            promedio = Math.round(promedio * 100) / 100; // Redondear a 2 decimales

            $('#calculated_promedio').val(promedio.toFixed(2));

            // Determinar estado
            var status = promedio >= 13 ? 'Aprobado' : 'Desaprobado';
            var statusClass = promedio >= 13 ? 'text-success' : 'text-danger';

            $('#calculated_status')
                .text(status)
                .removeClass('text-success text-danger')
                .addClass(statusClass);

            return promedio;
        }

        // Función para mostrar mensajes
        function showMessage(type, message) {
            var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            $('#editScoresMessage')
                .removeClass('alert-success alert-danger')
                .addClass(alertClass)
                .html(message)
                .show();
        }

        // Enviar formulario
        $('#formEditScores').on('submit', function(e) {
            e.preventDefault();

            var $btn = $('#btnSaveScores');
            var originalText = $btn.html();

            $btn.html('<i class="fa fa-spinner fa-spin"></i> Guardando...').prop('disabled', true);
            var url_ajax = '{{ url_ajax }}';

            $.ajax({
                url: url_ajax + '?action=update_scores_logs',
                type: 'POST',
                dataType: 'json',
                data: {
                    record_id: $('#edit_record_id').val(),
                    entrance_exam: $('#edit_entrance_exam').val(),
                    workshop: $('#edit_workshop').val(),
                    exit_exam: $('#edit_exit_exam').val()
                },
                success: function(response) {
                    if (response.success) {
                        showMessage('success', '<i class="fa fa-check"></i> ' + response.message);

                        // Recargar después de 1.5 segundos
                        setTimeout(function() {
                            $('#editScoresModal').modal('hide');
                            location.reload();
                        }, 1500);
                    } else {
                        showMessage('error', '<i class="fa fa-exclamation-triangle"></i> ' + (response.message || 'Error al actualizar las notas.'));
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Error:', xhr, status, error);
                    showMessage('error', '<i class="fa fa-exclamation-triangle"></i> Error de conexión. Intente nuevamente.');
                },
                complete: function() {
                    $btn.html(originalText).prop('disabled', false);
                }
            });
        });
    });
</script>