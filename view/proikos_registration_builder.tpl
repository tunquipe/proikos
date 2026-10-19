<div class="pk-rf-admin pk-rf-builder-page">
    <div class="pk-rf-builder-header">
        <h3>{{ form.title|e }}
            {% if form.status == 1 %}
                <span class="label label-primary">Publicado</span>
            {% else %}
                <span class="label label-default">Borrador</span>
            {% endif %}
            {% if form.is_registration %}
                <span class="label label-success"><em class="fa fa-star"></em> Formulario de registro</span>
            {% endif %}
        </h3>
        <p class="text-muted">
            Agregue los <strong>datos principales</strong> (Nombres, Apellidos, Correo electrónico y Contraseña)
            desde la barra inferior: Chamilo los necesita para crear la cuenta, así que el formulario no se puede
            publicar sin ellos. Arrastre las tarjetas para ordenarlas.
        </p>
    </div>

    {% if errors is not empty %}
        <div class="alert alert-danger">
            <strong>No se guardaron los cambios:</strong>
            <ul>
                {% for error in errors %}
                    <li>{{ error|e }}</li>
                {% endfor %}
            </ul>
        </div>
    {% endif %}

    {% if total_submissions > 0 %}
        <div class="alert alert-warning">
            <em class="fa fa-exclamation-triangle"></em>
            Este formulario ya tiene {{ total_submissions }} inscrito(s). Si elimina un campo también se eliminarán sus respuestas.
        </div>
    {% endif %}

    <form method="post" action="{{ action_url }}" id="pk-rf-builder-form">
        <input type="hidden" name="pk_reg_admin_sec_token" value="{{ token }}">
        <input type="hidden" name="fields_json" id="pk-rf-fields-json">

        <div id="pk-rf-fields" class="pk-rf-fields"></div>

        <div class="pk-rf-add-bar">
            <span class="pk-rf-add-title"><em class="fa fa-id-card-o"></em> Datos principales (necesarios para crear la cuenta):</span>
            <div id="pk-rf-add-core" class="pk-rf-add-buttons"></div>
            <span class="pk-rf-add-title"><em class="fa fa-magic"></em> Campos predefinidos (listos para usar, con sus opciones):</span>
            <div id="pk-rf-add-presets" class="pk-rf-add-buttons"></div>
            <span class="pk-rf-add-title"><em class="fa fa-plus-circle"></em> Agregar campo:</span>
            <div id="pk-rf-add-buttons" class="pk-rf-add-buttons"></div>
        </div>

        <div class="pk-rf-save-bar">
            <button type="submit" class="btn btn-primary btn-lg"><em class="fa fa-save"></em> Guardar campos</button>
            <span id="pk-rf-dirty" class="text-warning" style="display:none;"><em class="fa fa-circle"></em> Cambios sin guardar</span>
        </div>
    </form>
</div>

<script>
    window.PK_RF_BUILDER = {{ builder_data|raw }};
</script>
<script src="{{ builder_js }}"></script>
