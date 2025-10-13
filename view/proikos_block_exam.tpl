<!-- Modal para Bloquear Exámenes -->
<div class="modal fade" id="modalBloquearQuiz" tabindex="-1" role="dialog" aria-labelledby="modalBloquearQuizLabel" >
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalBloquearQuizLabel">Bloquear Exámenes a Usuario</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="loadingQuizzes" class="text-center">
                    <div class="spinner-border text-danger" role="status">
                        <span class="sr-only">Cargando exámenes...</span>
                    </div>
                    <p>Cargando exámenes disponibles...</p>
                </div>

                <div id="quizBlockForm" style="display: none;">
                    <form id="formBloquearQuiz">

                        <div class="alert alert-info">
                            <strong>Usuario ID:</strong> <span id="quiz_block_user_id_display">-</span>
                        </div>

                        <div class="form-group">
                            <label for="quiz_select"><strong>Selecciona los exámenes a bloquear:</strong></label>
                            <select id="quiz_select" name="quiz_ids[]" multiple class="form-control" size="4" required>
                                <!-- Se llena dinámicamente -->
                            </select>
                            <small class="form-text text-muted">
                                <i class="fa fa-info-circle"></i> Mantén presionado Ctrl (Cmd en Mac) para seleccionar múltiples exámenes
                            </small>
                        </div>

                        <div class="alert alert-warning" id="selectedCount" style="display: none;">
                            <i class="fa fa-exclamation-triangle"></i> <span id="countText">0 exámenes seleccionados</span>
                        </div>

                        <input type="hidden" id="quiz_block_user_id" name="user_id">
                        <input type="hidden" id="quiz_block_course_id" name="course_id">
                        <input type="hidden" id="quiz_block_session_id" name="session_id">
                        <input type="hidden" id="quiz_block_record_id" name="record_id">

                    </form>
                </div>

                <!-- Mensaje de error -->
                <div id="errorQuizMessage" class="alert alert-danger" style="display: none;">
                    <strong>Error:</strong> <span id="errorQuizText"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="saveQuizBlockBtn">
                    <i class="fa fa-lock"></i> Bloquear Exámenes
                </button>
                <button type="button" class="btn btn-warning" id="clearQuizBlockBtn" style="display: none;">
                    <i class="fa fa-unlock"></i> Desbloquear Todos
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .modal-header.bg-danger {
        background-color: #dc3545 !important;
    }

    #quiz_select {
        border: 2px solid #dc3545;
        border-radius: 5px;
    }

    #quiz_select option {
        padding: 8px;
        cursor: pointer;
    }

    #quiz_select option:hover {
        background-color: #f8d7da;
    }

    #quiz_select option:checked {
        background-color: #dc3545;
        color: white;
        font-weight: bold;
    }

    .spinner-border {
        width: 3rem;
        height: 3rem;
    }
</style>


<script>
    $(document).ready(function() {

        /**
         * Evento para abrir modal al hacer clic en el botón de bloquear
         */
        $(document).on('click', 'a[data-user-id]', function(e) {
            e.preventDefault();

            const userId = $(this).data('user-id');
            const courseId = $(this).data('course-id');
            const sessionId = $(this).data('session-id');

            if (!userId) {
                mostrarErrorQuiz('ID de usuario no válido');
                return;
            }
            //console.log('Abriendo modal para usuario:', userId, 'Curso:', courseId, 'Sesión:', sessionId);

            limpiarModalQuiz();

            $('#quiz_block_user_id').val(userId);
            $('#quiz_block_user_id_display').text(userId);
            $('#quiz_block_course_id').val(courseId);
            $('#quiz_block_session_id').val(sessionId);

            $('#loadingQuizzes').show();
            $('#quizBlockForm').hide();
            $('#errorQuizMessage').hide();

            $('#modalBloquearQuiz').modal('show');

            cargarExamenesDisponibles(userId, courseId, sessionId);
        });

        /**
         * Cargar exámenes disponibles del curso
         */
        function cargarExamenesDisponibles(userId, courseId, sessionId) {
            let urlAjax = '{{ url_ajax }}'
            $.ajax({
                url: urlAjax + '?action=get_course_quizzes',
                method: 'POST',
                dataType: 'json',
                data: {
                    user_id: userId,
                    course_id: courseId,
                    session_id: sessionId
                },
                timeout: 10000,
                success: function(response) {
                    if (response.success && response.quizzes) {
                        llenarSelectQuizzes(response.quizzes, response.blocked_ids || []);

                        // Si hay registro, llenar el record_id
                        if (response.record_id) {
                            $('#quiz_block_record_id').val(response.record_id);
                            $('#clearQuizBlockBtn').show();
                        }

                        $('#loadingQuizzes').hide();
                        $('#quizBlockForm').fadeIn('fast');
                    } else {
                        mostrarErrorQuiz(response.message || 'No se pudieron cargar los exámenes');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', {status: xhr.status, error: error});

                    let mensajeError = 'Error al cargar los exámenes';
                    if (xhr.status === 404) {
                        mensajeError = 'Archivo no encontrado: get_course_quizzes.php';
                    } else if (status === 'timeout') {
                        mensajeError = 'Tiempo de espera agotado';
                    }

                    mostrarErrorQuiz(mensajeError);
                }
            });
        }

        /**
         * Llenar el select con los exámenes
         */
        function llenarSelectQuizzes(quizzes, blockedIds) {
            const $select = $('#quiz_select');
            $select.empty();

            if (quizzes.length === 0) {
                $select.append('<option value="" disabled>No hay exámenes disponibles</option>');
                $('#saveQuizBlockBtn').prop('disabled', true);
                return;
            }

            quizzes.forEach(function(quiz) {
                const isBlocked = blockedIds.includes(quiz.id.toString()) || blockedIds.includes(parseInt(quiz.id));
                //(quiz.active == 1 ? ' (Activo) ' : ' (Inactivo)')
                const option = $('<option>')
                    .val(quiz.id)
                    .text(quiz.title)
                    .prop('selected', isBlocked);

                $select.append(option);
            });

            // Actualizar contador inicial
            actualizarContador();

            $('#saveQuizBlockBtn').prop('disabled', false);
        }

        /**
         * Actualizar contador de exámenes seleccionados
         */
        $('#quiz_select').on('change', function() {
            actualizarContador();
        });

        function actualizarContador() {
            const selected = $('#quiz_select').val();
            const count = selected ? selected.length : 0;

            if (count > 0) {
                $('#selectedCount').show();
                $('#countText').text(count + ' examen(es) seleccionado(s) para bloquear');
            } else {
                $('#selectedCount').hide();
            }
        }

        /**
         * Guardar bloqueos al hacer clic en el botón
         */
        $('#saveQuizBlockBtn').click(function() {
            const userId = $('#quiz_block_user_id').val();
            const courseId = $('#quiz_block_course_id').val();
            const sessionId = $('#quiz_block_session_id').val();
            const recordId = $('#quiz_block_record_id').val();
            const quizIds = $('#quiz_select').val();

            if (!quizIds || quizIds.length === 0) {
                mostrarAlertaQuiz('warning', '⚠️ Debes seleccionar al menos un examen para bloquear');
                return;
            }

            // Deshabilitar botón mientras se guarda
            $('#saveQuizBlockBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');
            let urlAjax = '{{ url_ajax }}'
            $.ajax({
                url: urlAjax + '?action=save_quiz_block',
                method: 'POST',
                dataType: 'json',
                data: {
                    user_id: userId,
                    course_id: courseId,
                    session_id: sessionId,
                    record_id: recordId,
                    quiz_ids: quizIds
                },
                timeout: 30000,
                success: function(response) {
                    if (response.success) {
                        mostrarAlertaQuiz('success', '✓ ' + response.message);

                        setTimeout(function() {
                            $('#modalBloquearQuiz').modal('hide');
                            // location.reload(); // Descomentar si quieres recargar
                        }, 1500);
                    } else {
                        mostrarAlertaQuiz('danger', '✗ Error: ' + response.message);
                    }

                    $('#saveQuizBlockBtn').prop('disabled', false).html('<i class="fa fa-lock"></i> Bloquear Exámenes');
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    mostrarAlertaQuiz('danger', '✗ Error al guardar: ' + error);
                    $('#saveQuizBlockBtn').prop('disabled', false).html('<i class="fa fa-lock"></i> Bloquear Exámenes');
                }
            });
        });

        /**
         * Desbloquear todos los exámenes
         */
        $('#clearQuizBlockBtn').click(function() {
            let urlAjax = '{{ url_ajax }}'

            if (!confirm('¿Estás seguro de desbloquear TODOS los exámenes para este usuario?')) {
                return;
            }

            const userId = $('#quiz_block_user_id').val();
            const courseId = $('#quiz_block_course_id').val();
            const sessionId = $('#quiz_block_session_id').val();

            $.ajax({

                url: urlAjax + '?action=delete_quiz_block',
                method: 'POST',
                dataType: 'json',
                data: {
                    user_id: userId,
                    course_id: courseId,
                    session_id: sessionId
                },
                success: function(response) {
                    if (response.success) {
                        mostrarAlertaQuiz('success', 'Todos los exámenes han sido desbloqueados');
                        setTimeout(function() {
                            $('#modalBloquearQuiz').modal('hide');
                            // location.reload(); // Descomentar si quieres recargar
                        }, 1500);
                    } else {
                        mostrarAlertaQuiz('danger', '✗ Error: ' + response.message);
                    }
                },
                error: function() {
                    mostrarAlertaQuiz('danger', '✗ Error al desbloquear exámenes');
                }
            });
        });

        /**
         * Funciones auxiliares
         */
        function mostrarErrorQuiz(mensaje) {
            $('#loadingQuizzes').hide();
            $('#quizBlockForm').hide();
            $('#errorQuizMessage').show();
            $('#errorQuizText').text(mensaje);
        }

        function mostrarAlertaQuiz(tipo, mensaje) {
            const alertHTML = `
            <div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
                ${mensaje}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;
            $('#quizBlockForm').prepend(alertHTML);

            setTimeout(function() {
                $('.alert').fadeOut('slow', function() { $(this).remove(); });
            }, 3000);
        }

        function limpiarModalQuiz() {
            $('#quiz_select').empty();
            $('#quiz_block_user_id').val('');
            $('#quiz_block_user_id_display').text('-');
            $('#quiz_block_course_id').val('');
            $('#quiz_block_session_id').val('');
            $('#quiz_block_record_id').val('');
            $('#selectedCount').hide();
            $('#clearQuizBlockBtn').hide();
            $('.alert').remove();
        }


        /**
         * Resetear modal cuando se cierra
         */
        $('#modalBloquearQuiz').on('hidden.bs.modal', function() {
            limpiarModalQuiz();
        });

    });
</script>