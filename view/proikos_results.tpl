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

        {% if status_id == 2 %}
        <div class="proikos-button-container">
            <a href="{{ url_certificate }}" target="_blank" class="proikos-certificate-button">
                <i class="fa fa-eye" aria-hidden="true"></i>
                Visualizar Certificado
            </a>
        </div>
        {% elseif status_id == 1 %}
        <div class="proikos-button-container">
            <a href="#" class="proikos-certificate-button disabled" onclick="return false;">
                <i class="fa fa-lock" aria-hidden="true"></i>
                Certificado disponible al aprobar
            </a>
        </div>
        {% else %}
        <div class="proikos-button-container">
            <a href="#" class="proikos-certificate-button disabled" onclick="return false;">
                <i class="fa fa-ban" aria-hidden="true"></i>
                Certificado no disponible
            </a>
        </div>
        {% endif %}

    </div>
</div>

<script>
    (function() {
        // Animar las barras de progreso cuando se cargue la página
        window.addEventListener('load', function() {
            setTimeout(function() {
                var progressBars = document.querySelectorAll('.proikos-progress-fill');
                progressBars.forEach(function(bar) {
                    var progress = bar.getAttribute('data-progress');
                    if (progress) {
                        bar.style.width = progress + '%';
                    }
                });
            }, 300);
        });
    })();
</script>