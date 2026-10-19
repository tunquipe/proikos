<div class="pk-rf-admin">
    <div class="alert {% if has_registration %}alert-success{% else %}alert-info{% endif %}">
        {% if has_registration %}
            <em class="fa fa-check-circle"></em>
            La plataforma usa un formulario dinámico para el registro de usuarios:
            <a href="{{ registration_url }}" target="_blank">{{ registration_url }}</a>
            <a href="{{ base_url }}?action=unset_registration&pk_reg_admin_sec_token={{ token }}"
               class="btn btn-default btn-xs"
               onclick="return confirm('¿Volver al formulario de registro original de la plataforma?');">
                Volver al registro original
            </a>
        {% else %}
            <em class="fa fa-info-circle"></em>
            Actualmente se usa el formulario de registro original. Publique un formulario y márquelo como
            <strong>formulario de registro</strong> para reemplazarlo.
        {% endif %}
    </div>

    {% if forms is empty %}
        <div class="pk-rf-empty text-center">
            <em class="fa fa-wpforms fa-4x text-muted"></em>
            <h4>Aún no hay formularios de inscripción</h4>
            <p class="text-muted">Cree un formulario con los datos que necesita recoger además de los datos principales del usuario.</p>
            <a href="{{ base_url }}?action=add" class="btn btn-primary"><em class="fa fa-plus"></em> Nuevo formulario</a>
        </div>
    {% else %}
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                <tr>
                    <th>Formulario</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Campos</th>
                    <th class="text-center">Inscritos</th>
                    <th>Última modificación</th>
                    <th class="text-center">Acciones</th>
                </tr>
                </thead>
                <tbody>
                {% for item in forms %}
                    <tr {% if item.is_registration %}class="success"{% endif %}>
                        <td>
                            <strong>{{ item.title|e }}</strong>
                            {% if item.is_registration %}
                                <span class="label label-success"><em class="fa fa-star"></em> Formulario de registro</span>
                            {% endif %}
                            {% if item.status == 1 %}
                                <div class="pk-rf-link">
                                    <input type="text" class="form-control input-sm" readonly value="{{ item.public_url }}" onclick="this.select();">
                                </div>
                            {% endif %}
                        </td>
                        <td class="text-center">
                            {% if item.status == 1 %}
                                <span class="label label-primary">Publicado</span>
                            {% else %}
                                <span class="label label-default">Borrador</span>
                            {% endif %}
                        </td>
                        <td class="text-center">{{ item.total_fields }}</td>
                        <td class="text-center">
                            <a href="{{ base_url }}?action=responses&id={{ item.id }}">{{ item.total_submissions }}</a>
                        </td>
                        <td>{{ item.updated_local }}</td>
                        <td class="text-center pk-rf-actions">
                            <a href="{{ base_url }}?action=builder&id={{ item.id }}" class="btn btn-primary btn-sm" title="Diseñar campos">
                                <em class="fa fa-pencil-square-o"></em> Campos
                            </a>
                            <a href="{{ base_url }}?action=edit&id={{ item.id }}" class="btn btn-default btn-sm" title="Configuración">
                                <em class="fa fa-cog"></em>
                            </a>
                            <a href="{{ item.preview_url }}" target="_blank" class="btn btn-default btn-sm" title="Vista previa">
                                <em class="fa fa-eye"></em>
                            </a>
                            <a href="{{ base_url }}?action=responses&id={{ item.id }}" class="btn btn-default btn-sm" title="Respuestas">
                                <em class="fa fa-table"></em>
                            </a>
                            <div class="btn-group">
                                <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                    <em class="fa fa-ellipsis-v"></em>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    {% if item.status == 1 %}
                                        {% if not item.is_registration %}
                                            <li>
                                                <a href="{{ base_url }}?action=set_registration&id={{ item.id }}&pk_reg_admin_sec_token={{ token }}"
                                                   onclick="return confirm('Este formulario reemplazará el registro de usuarios de la plataforma. ¿Continuar?');">
                                                    <em class="fa fa-star"></em> Usar como formulario de registro
                                                </a>
                                            </li>
                                        {% endif %}
                                        <li>
                                            <a href="{{ base_url }}?action=unpublish&id={{ item.id }}&pk_reg_admin_sec_token={{ token }}"
                                               {% if item.is_registration %}onclick="return confirm('Al pasar a borrador dejará de ser el formulario de registro. ¿Continuar?');"{% endif %}>
                                                <em class="fa fa-eye-slash"></em> Pasar a borrador
                                            </a>
                                        </li>
                                    {% else %}
                                        <li>
                                            <a href="{{ base_url }}?action=publish&id={{ item.id }}&pk_reg_admin_sec_token={{ token }}">
                                                <em class="fa fa-globe"></em> Publicar
                                            </a>
                                        </li>
                                    {% endif %}
                                    <li>
                                        <a href="{{ base_url }}?action=duplicate&id={{ item.id }}&pk_reg_admin_sec_token={{ token }}">
                                            <em class="fa fa-copy"></em> Duplicar
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ base_url }}?action=export&id={{ item.id }}">
                                            <em class="fa fa-file-excel-o"></em> Exportar respuestas
                                        </a>
                                    </li>
                                    <li class="divider"></li>
                                    <li>
                                        <a href="{{ base_url }}?action=delete&id={{ item.id }}&pk_reg_admin_sec_token={{ token }}" class="text-danger"
                                           onclick="return confirm('Se eliminará el formulario y sus {{ item.total_submissions }} respuesta(s). Los usuarios ya registrados no se eliminan. ¿Continuar?');">
                                            <em class="fa fa-trash"></em> Eliminar
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                {% endfor %}
                </tbody>
            </table>
        </div>
    {% endif %}
</div>
