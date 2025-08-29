<?php

require_once __DIR__ . '/../config.php';
api_block_anonymous_users();
$action = $_GET['action'] ?? null;
$plugin = ProikosPlugin::create();
$tool_name = 'Gestionar usuarios';
$message = null;
$actionLinks = null;

$courseId = $_GET['course_id'] ?? '%';
$sessionId = $_GET['session_id'] ?? '%';


$tpl = new Template($tool_name);
$isAdmin = api_is_platform_admin();

$urlPluginProikos = api_get_path(WEB_PLUGIN_PATH).'proikos/src/ajax.php?action=get_session_exercises';

$sessions = [];
$sessions['%'] = $plugin->get_lang('SelectSession');
if (!empty($courseId)) {

    $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
    $sql = "SELECT * FROM session s WHERE s.session_mode = 1;";
    $result = Database::query($sql);
    $sessionsList = [];

    if (Database::num_rows($result) > 0) {
        while ($row = Database::fetch_array($result)) {
            $sessionsList[] =  [
                'id' => intval($row['id']),
                'name' => $row['name'],
            ];
        }
    }

    foreach ($sessionsList as $session) {
        $sessions[$session['id']] = $session['name'];
    }
}

$form = new FormValidator('search_simple', 'get', null, null, null, FormValidator::LAYOUT_GRID);
$form->addElement(
    'select',
    'session_id',
    get_lang('AsynchronousCourses'),
    $sessions,
    ['style' => 'width: 200px;']
);

// Select de ejercicios (inicialmente vacío)
$form->addElement(
    'select',
    'exercise_id',
    get_lang('Exercises'),
    ['0' => get_lang('SelectSession')],
    ['style' => 'width: 200px;', 'id' => 'exercise_select', 'disabled' => 'disabled']
);

$form->setDefaults([
    'session_id' => $sessionId
]);

$form->addButtonSearch(get_lang('Search'), 'search_btn');

$form->addHtml(
    <<<EOT
    <script>
        $(document).ready(function() {
            // Inicializar bootstrap-select si existe
            if ($.fn.selectpicker) {
                $('#session_select, #exercise_select').selectpicker();
            }

            // Cuando cambie la sesión
            $('#search_simple_session_id').change(function() {
                var sessionId = $(this).val();
                var exerciseSelect = $('#exercise_select');

                if (sessionId && sessionId != '0') {
                    // Deshabilitar select de ejercicios mientras carga
                    exerciseSelect.prop('disabled', true);
                    exerciseSelect.html('<option value="0">' + 'Cargando...' + '</option>');

                    // Refrescar bootstrap-select si existe
                    if ($.fn.selectpicker) {
                        exerciseSelect.selectpicker('refresh');
                    }

                    // Hacer petición AJAX para obtener ejercicios
                    $.ajax({
                        url: '{$urlPluginProikos}',
                        type: 'POST',
                        data: {
                            session_id: sessionId,
                            course_id: '{$courseId}'
                        },
                        dataType: 'json',
                        success: function(response) {
                            exerciseSelect.html('');
                            exerciseSelect.append('<option value="0">' + 'Seleccione un ejercicio' + '</option>');

                            if (response.success && response.exercises.length > 0) {
                                $.each(response.exercises, function(index, exercise) {
                                    exerciseSelect.append(
                                        '<option value="' + exercise.id + '">' +
                                        exercise.title + ' (' + exercise.course_title + ')' +
                                        '</option>'
                                    );
                                });
                                exerciseSelect.prop('disabled', false);
                                // Refrescar bootstrap-select después de agregar opciones
                                if ($.fn.selectpicker) {
                                    exerciseSelect.selectpicker('refresh');
                                }
                            } else {
                                exerciseSelect.append('<option value="0">' + 'No hay ejercicios disponibles' + '</option>');
                                // Refrescar bootstrap-select
                                if ($.fn.selectpicker) {
                                    exerciseSelect.selectpicker('refresh');
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error al cargar ejercicios:', error);
                            exerciseSelect.html('<option value="0">' + 'Error al cargar ejercicios' + '</option>');
                            // Refrescar bootstrap-select en caso de error
                            if ($.fn.selectpicker) {
                                exerciseSelect.selectpicker('refresh');
                            }
                        }
                    });
                } else {
                    // Si no hay sesión seleccionada, resetear select de ejercicios
                    exerciseSelect.html('<option value="0">' + 'Seleccione una sesión' + '</option>');
                    exerciseSelect.prop('disabled', true);
                    // Refrescar bootstrap-select
                    if ($.fn.selectpicker) {
                        exerciseSelect.selectpicker('refresh');
                    }
                }
            });

            // Cuando cambie el ejercicio (opcional, para hacer algo adicional)
            $('#exercise_select').change(function() {
                var exerciseId = $(this).val();
                var sessionId = $('#session_select').val();

                if (exerciseId && exerciseId != '0' && sessionId && sessionId != '0') {
                    // Aquí puedes agregar lógica adicional si necesitas
                    console.log('Ejercicio seleccionado:', exerciseId, 'Sesión:', sessionId);

                    // O redirigir automáticamente si lo deseas
                    // window.location.href = '{$urlPluginProikos}?course_id={$courseId}&session_id=' + sessionId + '&exercise_id=' + exerciseId;
                }
            });
        });
    </script>
EOT
);

$actionsLeft = $form->returnForm();
$toolbarActions = Display::toolbarAction('toolbarData', [$actionsLeft], [9, 1, 2]);


//

//


$actionLinks .= Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_PLUGIN_PATH) . 'proikos/start.php'
);

$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionLinks])
);
$tpl->assign('message', $message);
$content = $tpl->fetch('proikos/view/proikos_smowl.tpl');
$tpl->assign('content', $toolbarActions . $content);
$tpl->display_one_col_template();
