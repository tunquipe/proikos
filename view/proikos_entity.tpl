{{ form }}
<table class="table table-bordered">
    <thead>
    <tr>
        <th>#</th>
        <th>Nombre</th>
        <th>Imagen</th>
        <th>Código de referencia</th>
        <th>Estado</th>
        <th>Acciones</th>
    </tr>
    </thead>
    <tbody>
    {% for entity in entities %}
        <tr>
            <th scope="row">{{ entity.id }}</th>
            <td>{{ entity.name_entity }}</td>
            <td>{{ entity.picture }}</td>
            <td>{{ entity.code_reference }}</td>
            <td>{{ entity.status }}</td>
            <td>{{ entity.actions }}</td>
        </tr>
    {% endfor %}
    </tbody>
</table>