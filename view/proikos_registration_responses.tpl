<div class="pk-rf-admin">
    <div class="page-header">
        <h3>{{ form.title|e }} <small>{{ total }} inscrito(s)</small></h3>
    </div>

    <form method="get" action="{{ base_url }}" class="form-inline" style="margin-bottom: 15px;">
        <input type="hidden" name="action" value="responses">
        <input type="hidden" name="id" value="{{ form.id }}">
        <div class="input-group" style="max-width: 420px;">
            <input type="text" name="search" value="{{ search }}" class="form-control" placeholder="Buscar por nombre, correo o usuario">
            <span class="input-group-btn">
                <button type="submit" class="btn btn-primary"><em class="fa fa-search"></em></button>
                {% if search %}
                    <a href="{{ base_url }}?action=responses&id={{ form.id }}" class="btn btn-default"><em class="fa fa-times"></em></a>
                {% endif %}
            </span>
        </div>
    </form>

    {% if rows is empty %}
        <div class="alert alert-info">Aún no hay inscritos con este formulario.</div>
    {% else %}
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-condensed pk-rf-responses">
                <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Apellidos y nombres</th>
                    <th>Correo</th>
                    <th>Usuario</th>
                    {% for field in fields %}
                        <th>{{ field.label|e }}</th>
                    {% endfor %}
                </tr>
                </thead>
                <tbody>
                {% for row in rows %}
                    <tr>
                        <td class="text-nowrap">{{ row.date }}</td>
                        <td>
                            {% if row.submission.username %}
                                <a href="{{ row.user_url }}" target="_blank">{{ row.submission.lastname|e }}, {{ row.submission.firstname|e }}</a>
                            {% else %}
                                <span class="text-muted">Usuario eliminado</span>
                            {% endif %}
                        </td>
                        <td>{{ row.submission.email|e }}</td>
                        <td>{{ row.submission.username|e }}</td>
                        {% for cell in row.cells %}
                            <td>{{ cell }}</td>
                        {% endfor %}
                    </tr>
                {% endfor %}
                </tbody>
            </table>
        </div>

        {% if total_pages > 1 %}
            <nav>
                <ul class="pagination">
                    {% for p in 1..total_pages %}
                        <li {% if p == page %}class="active"{% endif %}>
                            <a href="{{ base_url }}?action=responses&id={{ form.id }}&page={{ p }}&search={{ search|url_encode }}">{{ p }}</a>
                        </li>
                    {% endfor %}
                </ul>
            </nav>
        {% endif %}
    {% endif %}
</div>
