
<!-- EJEMPLO 1: APROBADO -->
<div class="proikos-results-container">
    <div class="proikos-header {{ css_status }}">

        {% if status_id == 1 %}

        <div class="proikos-success-icon"><img src="{{ image_icon }}" alt="Evaluaciones pendientes"/></div>
        <h1>EVALUACIONES PENDIENTES</h1>
        <p>Aún tienes evaluaciones por completar</p>

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
                <div class="proikos-score-value">{{ weighted_average }}</div>
                <div class="proikos-score-subtitle">/20</div>
                <div class="proikos-progress-bar">
                    <div class="proikos-progress-fill {{ css_status }}" data-progress="{{ weighted_average }}"></div>
                </div>
            </div>
            <div class="proikos-score-card">
                <div class="proikos-score-label">Puntaje Obtenido</div>
                <div class="proikos-score-value">{{ total_score }}</div>
                <div class="proikos-score-subtitle">/100 puntos</div>
                <div class="proikos-progress-bar">
                    <div class="proikos-progress-fill {{ css_status }}" data-progress="{{ total_score }}"></div>
                </div>
            </div>
        </div>

        <div class="proikos-evaluations-section">
            <h2 class="proikos-section-title">📋 Detalle de Evaluaciones</h2>

            <div class="proikos-evaluation-item">
                <div class="proikos-eval-number">1</div>
                <div class="proikos-eval-name">Examen de Entrada</div>
                <div class="proikos-eval-weight">10%</div>
                <div class="proikos-eval-score score-{{ css_status }}">
                    {{ exams.entrance }}
                </div>
            </div>

            <div class="proikos-evaluation-item">
                <div class="proikos-eval-number">2</div>
                <div class="proikos-eval-name">Taller</div>
                <div class="proikos-eval-weight">30%</div>
                <div class="proikos-eval-score score-{{ css_status }}">
                    {{ exams.workshop }}
                </div>
            </div>

            <div class="proikos-evaluation-item">
                <div class="proikos-eval-number">3</div>
                <div class="proikos-eval-name">Examen de Salida</div>
                <div class="proikos-eval-weight">60%</div>
                <div class="proikos-eval-score score-{{ css_status }}">
                    {{ exams.exit }}
                </div>
            </div>
        </div>

        {% if status_id == 2 %}
        <div class="proikos-button-container">
            <a href="{{ url_certificate }}" target="_blank" class="proikos-certificate-button">
                <i class="fa fa-eye" aria-hidden="true"></i>
                Visualizar Certificado
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