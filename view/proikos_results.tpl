<div class="proikos-results-container">
    <div class="proikos-header {{ css_status }}">

        {% if status_id == 1 %}
        <div class="proikos-success-icon"><img src="{{ image_icon }}" alt="Evaluaciones pendientes"/></div>
        <h1>EVALUACIONES PENDIENTES</h1>
        <p>Aún tienes {{ pending_count }} evaluación(es) por completar</p>

        {% elseif status_id == 2 %}
        <div class="proikos-success-icon"><img src="{{ image_icon }}" alt="Aprobado"/></div>
        <h1>¡APROBADO!</h1>
        <p>Puntaje Alcanzado con Éxito</p>

        {% else %}
        <div class="proikos-success-icon"><img src="{{ image_icon }}" alt="Desaprobado"/></div>
        <h1>DESAPROBADO</h1>
        <p>No se alcanzó el puntaje mínimo requerido</p>
        {% endif %}

    </div>

    <div class="proikos-content">
        <div class="proikos-score-summary">
            <div class="proikos-score-card {{ css_status_light }}">
                <div class="proikos-score-label">Promedio Final</div>

                {% if status_id == 1 %}
                <div class="proikos-score-value pending-value">En proceso</div>
                <div class="proikos-score-subtitle">Completa todas las evaluaciones</div>
                {% else %}
                <div class="proikos-score-value">{{ weighted_average|number_format(1) }}</div>
                <div class="proikos-score-subtitle">/20
                    {% if status_id == 0 %}(Mínimo: {{ min_passing_score }}){% endif %}
                </div>
                <div class="proikos-progress-bar">
                    <div class="proikos-progress-fill {{ css_status }}" data-progress="{{ (weighted_average / 20 * 100)|number_format(1) }}"></div>
                </div>
                {% endif %}
            </div>

            <div class="proikos-score-card">
                <div class="proikos-score-label">
                    {% if status_id == 1 %}Puntaje Parcial{% else %}Puntaje Obtenido{% endif %}
                </div>
                <div class="proikos-score-value">{{ total_score|number_format(1) }}</div>
                <div class="proikos-score-subtitle">
                    /100 puntos
                    {% if status_id == 1 %} ({{ course_completion|number_format(0) }}% completado){% endif %}
                </div>
                <div class="proikos-progress-bar">
                    <div class="proikos-progress-fill {{ css_status }}" data-progress="{{ total_score|number_format(1) }}"></div>
                </div>
            </div>
        </div>

        {% if status_id == 1 %}
        <div class="proikos-alert-info">
            <strong>ℹ️ Evaluaciones pendientes</strong>
            Tienes evaluaciones sin completar. Una vez que termines todas las evaluaciones, podrás ver tu resultado final y obtener tu certificado.
        </div>
        {% elseif status_id == 0 %}
        <div class="proikos-alert-danger">
            <strong>⚠️ Información importante</strong>
            No has alcanzado el puntaje mínimo para aprobar el curso. Te recomendamos revisar los contenidos y consultar con tu instructor sobre las opciones de recuperación.
        </div>
        {% endif %}

        <div class="proikos-evaluations-section">
            <h2 class="proikos-section-title">📋 Detalle de Evaluaciones</h2>

            <div class="proikos-evaluation-item">
                <div class="proikos-eval-number">1</div>
                <div class="proikos-eval-name">{{ exam_info.entrance.name }}</div>
                <div class="proikos-eval-weight">{{ exam_info.entrance.weight }}</div>

                {% if exam_info.entrance.is_pending %}
                <div class="proikos-eval-score score-pending">Pendiente</div>
                {% elseif exam_info.entrance.is_passed %}
                <div class="proikos-eval-score score-approved">{{ exams.entrance|number_format(1) }}</div>
                {% else %}
                <div class="proikos-eval-score score-failed">{{ exams.entrance|number_format(1) }}</div>
                {% endif %}
            </div>

            <div class="proikos-evaluation-item">
                <div class="proikos-eval-number">2</div>
                <div class="proikos-eval-name">{{ exam_info.workshop.name }}</div>
                <div class="proikos-eval-weight">{{ exam_info.workshop.weight }}</div>

                {% if exam_info.workshop.is_pending %}
                <div class="proikos-eval-score score-pending">Pendiente</div>
                {% elseif exam_info.workshop.is_passed %}
                <div class="proikos-eval-score score-approved">{{ exams.workshop|number_format(1) }}</div>
                {% else %}
                <div class="proikos-eval-score score-failed">{{ exams.workshop|number_format(1) }}</div>
                {% endif %}
            </div>

            <div class="proikos-evaluation-item">
                <div class="proikos-eval-number">3</div>
                <div class="proikos-eval-name">{{ exam_info.exit.name }}</div>
                <div class="proikos-eval-weight">{{ exam_info.exit.weight }}</div>

                {% if exam_info.exit.is_pending %}
                <div class="proikos-eval-score score-pending">Pendiente</div>
                {% elseif exam_info.exit.is_passed %}
                <div class="proikos-eval-score score-approved">{{ exams.exit|number_format(1) }}</div>
                {% else %}
                <div class="proikos-eval-score score-failed">{{ exams.exit|number_format(1) }}</div>
                {% endif %}
            </div>
        </div>

        <div class="proikos-button-container">
            <button
                    id="proikos-certificate-btn"
                    class="proikos-certificate-button {% if status_id != 2 %}disabled{% endif %}"
                    data-status-id="{{ status_id }}"
                    data-user-id="{{ user_id }}"
                    data-course-code="{{ course_code }}"
                    data-session-id="{{ session_id }}"
                    data-state="{% if status_id == 2 %}{% if url_certificate %}view{% else %}generate{% endif %}{% else %}disabled{% endif %}"
                    {% if url_certificate %}data-certificate-url="{{ url_certificate }}"{% endif %}
                    {% if status_id != 2 %}disabled{% endif %}>

                {% if status_id == 2 %}
                {% if url_certificate %}
                <i class="fa fa-eye" aria-hidden="true"></i>
                Visualizar Certificado
                {% else %}
                <i class="fa fa-certificate" aria-hidden="true"></i>
                Generar Certificado
                {% endif %}
                {% elseif status_id == 1 %}
                <i class="fa fa-lock" aria-hidden="true"></i>
                Certificado disponible al aprobar
                {% else %}
                <i class="fa fa-ban" aria-hidden="true"></i>
                Certificado no disponible
                {% endif %}
            </button>
        </div>

    </div>
</div>

<script>
    (function() {
        'use strict';

        // ==============================================================
        // CONFIGURACIÓN
        // ==============================================================

        const AJAX_URL = '{{ _p.web }}plugin/proikos/ajax/generate_certificate.ajax.php';
        let isGenerating = false;

        // ==============================================================
        // INICIALIZACIÓN
        // ==============================================================

        function init() {
            // Animar las barras de progreso
            setTimeout(function() {
                var progressBars = document.querySelectorAll('.proikos-progress-fill');
                progressBars.forEach(function(bar) {
                    var progress = bar.getAttribute('data-progress');
                    if (progress) {
                        bar.style.width = progress + '%';
                    }
                });
            }, 300);

            // Configurar botón de certificado
            const certificateButton = document.getElementById('proikos-certificate-btn');
            if (!certificateButton) {
                console.warn('Botón de certificado no encontrado');
                return;
            }

            // Debug: Mostrar datos del botón
            console.log('=== PROIKOS CERTIFICATE DEBUG ===');
            console.log('Status ID:', certificateButton.dataset.statusId);
            console.log('User ID:', certificateButton.dataset.userId);
            console.log('Course Code:', certificateButton.dataset.courseCode);
            console.log('Session ID:', certificateButton.dataset.sessionId);
            console.log('State:', certificateButton.dataset.state);
            console.log('Certificate URL:', certificateButton.dataset.certificateUrl);
            console.log('================================');

            // Verificar que tenemos los datos necesarios
            if (!certificateButton.dataset.userId || !certificateButton.dataset.courseCode) {
                console.error('Faltan datos necesarios en el botón');
                updateButtonDisabled(certificateButton, 'Faltan datos del curso');
                return;
            }

            // Verificar estado inicial si está aprobado
            const statusId = certificateButton.dataset.statusId;
            if (statusId == '2') {
                checkCertificateStatus();
            }

            // Agregar event listener
            certificateButton.addEventListener('click', handleCertificateButtonClick);
        }

        // ==============================================================
        // VERIFICAR ESTADO DEL CERTIFICADO
        // ==============================================================

        function checkCertificateStatus() {
            const button = document.getElementById('proikos-certificate-btn');

            // Si ya tiene URL de certificado, actualizar a visualizar
            if (button.dataset.certificateUrl) {
                updateButtonToView(button, button.dataset.certificateUrl);
                return;
            }

            console.log('Verificando estado del certificado...');

            const data = new FormData();
            data.append('action', 'check_status');
            data.append('user_id', button.dataset.userId);
            data.append('course_code', button.dataset.courseCode);
            data.append('session_id', button.dataset.sessionId);

            fetch(AJAX_URL, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: data
            })
                .then(response => response.json())
                .then(result => {
                    console.log('Resultado check_status:', result);

                    if (result.exists) {
                        updateButtonToView(button, result.certificate_url);
                    } else if (result.can_generate) {
                        updateButtonToGenerate(button);
                    } else {
                        updateButtonDisabled(button, result.message);
                    }
                })
                .catch(error => {
                    console.error('Error al verificar estado:', error);
                    // En caso de error, dejarlo como generar si está aprobado
                    if (button.dataset.statusId == '2') {
                        updateButtonToGenerate(button);
                    }
                });
        }

        // ==============================================================
        // MANEJAR CLIC EN EL BOTÓN
        // ==============================================================

        function handleCertificateButtonClick(e) {
            e.preventDefault();

            const button = e.currentTarget;
            const buttonState = button.dataset.state;

            console.log('Clic en botón, estado:', buttonState);

            if (isGenerating) {
                console.log('Ya se está generando un certificado');
                return;
            }

            switch (buttonState) {
                case 'generate':
                    generateCertificate(button);
                    break;
                case 'view':
                    const url = button.dataset.certificateUrl;
                    if (url) {
                        console.log('Abriendo certificado:', url);
                        window.open(url, '_blank');
                    }
                    break;
                case 'disabled':
                    console.log('Botón deshabilitado');
                    break;
            }
        }

        // ==============================================================
        // GENERAR CERTIFICADO VIA AJAX
        // ==============================================================

        function generateCertificate(button) {
            if (isGenerating) {
                return;
            }

            console.log('Iniciando generación de certificado...');

            isGenerating = true;
            updateButtonLoading(button);

            const data = new FormData();
            data.append('action', 'generate');
            data.append('user_id', button.dataset.userId);
            data.append('course_code', button.dataset.courseCode);
            data.append('session_id', button.dataset.sessionId);

            console.log('Enviando datos:', {
                action: 'generate',
                user_id: button.dataset.userId,
                course_code: button.dataset.courseCode,
                session_id: button.dataset.sessionId
            });

            fetch(AJAX_URL, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: data
            })
                .then(response => response.json())
                .then(result => {
                    isGenerating = false;
                    console.log('Resultado generate:', result);

                    if (result.success) {
                        showSuccessMessage(result.message);
                        updateButtonToView(button, result.certificate_url);

                        // Auto-abrir certificado
                        setTimeout(() => {
                            window.open(result.certificate_url, '_blank');
                        }, 1000);
                    } else {
                        showErrorMessage(result.message);
                        updateButtonToGenerate(button);
                    }
                })
                .catch(error => {
                    isGenerating = false;
                    console.error('Error al generar certificado:', error);
                    showErrorMessage('Ocurrió un error al generar el certificado. Por favor, intenta nuevamente.');
                    updateButtonToGenerate(button);
                });
        }

        // ==============================================================
        // ACTUALIZAR ESTADOS DEL BOTÓN
        // ==============================================================

        function updateButtonToGenerate(button) {
            console.log('Actualizando botón a: GENERAR');
            button.dataset.state = 'generate';
            button.disabled = false;
            button.className = 'proikos-certificate-button';
            button.innerHTML = '<i class="fa fa-certificate" aria-hidden="true"></i> Generar Certificado';
        }

        function updateButtonToView(button, certificateUrl) {
            console.log('Actualizando botón a: VISUALIZAR');
            button.dataset.state = 'view';
            button.dataset.certificateUrl = certificateUrl;
            button.disabled = false;
            button.className = 'proikos-certificate-button';
            button.innerHTML = '<i class="fa fa-eye" aria-hidden="true"></i> Visualizar Certificado';
        }

        function updateButtonDisabled(button, message) {
            console.log('Actualizando botón a: DESHABILITADO -', message);
            button.dataset.state = 'disabled';
            button.disabled = true;
            button.className = 'proikos-certificate-button disabled';
            button.title = message || '';

            const statusId = button.dataset.statusId;
            if (statusId == '1') {
                button.innerHTML = '<i class="fa fa-lock" aria-hidden="true"></i> Certificado disponible al aprobar';
            } else {
                button.innerHTML = '<i class="fa fa-ban" aria-hidden="true"></i> Certificado no disponible';
            }
        }

        function updateButtonLoading(button) {
            console.log('Actualizando botón a: CARGANDO');
            button.disabled = true;
            button.className = 'proikos-certificate-button loading';
            button.innerHTML = '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> Generando certificado...';
        }

        // ==============================================================
        // SISTEMA DE NOTIFICACIONES
        // ==============================================================

        function showSuccessMessage(message) {
            showMessage(message, 'success');
        }

        function showErrorMessage(message) {
            showMessage(message, 'error');
        }

        function showMessage(message, type) {
            const notification = document.createElement('div');
            notification.className = 'proikos-notification proikos-notification-' + type;
            notification.innerHTML =
                '<div class="proikos-notification-content">' +
                '<i class="fa fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i>' +
                '<span>' + message + '</span>' +
                '</div>';

            document.body.appendChild(notification);

            setTimeout(function() {
                notification.classList.add('show');
            }, 10);

            setTimeout(function() {
                notification.classList.remove('show');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }, 5000);
        }

        // ==============================================================
        // EJECUTAR AL CARGAR
        // ==============================================================

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }

    })();
</script>