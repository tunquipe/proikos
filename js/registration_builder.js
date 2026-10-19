/**
 * Constructor de formularios de inscripción dinámicos (plugin Proikos).
 * Mantiene la definición de campos en memoria y la envía como JSON al guardar.
 */
(function ($) {
    'use strict';

    var data = window.PK_RF_BUILDER || {fields: [], catalog: {}};
    var catalog = data.catalog;
    var CORE = Object.keys(catalog.coreLabels);
    var TYPE_ICONS = {
        firstname: 'fa-user', lastname: 'fa-user', email: 'fa-envelope', password: 'fa-lock',
        section: 'fa-header', text: 'fa-font', textarea: 'fa-align-left', number: 'fa-hashtag',
        tel: 'fa-phone', date: 'fa-calendar', select: 'fa-caret-square-o-down', radio: 'fa-dot-circle-o',
        checkbox: 'fa-check-square-o', file: 'fa-paperclip', terms: 'fa-file-text-o'
    };
    var TEXT_LIKE = ['firstname', 'lastname', 'email', 'text', 'textarea', 'number', 'tel', 'select'];

    var uid = 0;
    var dirty = false;
    var openUid = null;

    var fields = (data.fields || []).map(normalize);

    function normalize(field) {
        var item = $.extend(true, {
            id: 0, field_key: '', type: 'text', label: '', required: false,
            options: [], validations: {}, config: {}
        }, field);
        item.config = $.extend({width: 12, help: '', placeholder: '', map_to: '', options_source: '', parent: ''}, item.config || {});
        ['help', 'placeholder', 'map_to', 'url', 'link_text', 'options_source', 'parent'].forEach(function (key) {
            if (item.config[key] === false || item.config[key] === null) {
                item.config[key] = '';
            }
        });
        if (item.config.show_if && !Array.isArray(item.config.show_if.values)) {
            item.config.show_if.values = [];
        }
        if (Array.isArray(item.validations)) {
            item.validations = {};
        }
        if (Array.isArray(item.config)) {
            item.config = {width: 12, help: '', placeholder: '', map_to: ''};
        }
        item.is_core = CORE.indexOf(item.type) !== -1;
        item._uid = ++uid;
        return item;
    }

    function isCore(field) {
        return CORE.indexOf(field.type) !== -1;
    }

    function typeLabel(type) {
        return catalog.types[type] || catalog.coreLabels[type] || type;
    }

    function markDirty() {
        dirty = true;
        $('#pk-rf-dirty').show();
    }

    function findField(fieldUid) {
        for (var i = 0; i < fields.length; i++) {
            if (fields[i]._uid === fieldUid) {
                return i;
            }
        }
        return -1;
    }

    /* ------------------------------------------------------------------ */
    /* Helpers de UI                                                       */
    /* ------------------------------------------------------------------ */

    function formGroup(labelText, $control, help) {
        var $group = $('<div class="form-group"></div>');
        $group.append($('<label class="control-label"></label>').text(labelText));
        $group.append($control);
        if (help) {
            $group.append($('<p class="help-block"></p>').text(help));
        }
        return $group;
    }

    function input(type, value, onChange, attrs) {
        var $input = $('<input class="form-control input-sm">').attr('type', type).attr(attrs || {});
        $input.val(value === undefined || value === null ? '' : value);
        $input.on('input change', function () {
            onChange($(this).val());
            markDirty();
        });
        return $input;
    }

    function checkbox(text, checked, onChange, disabled) {
        var $label = $('<label></label>');
        var $check = $('<input type="checkbox">').prop('checked', !!checked).prop('disabled', !!disabled);
        $check.on('change', function () {
            onChange(this.checked);
            markDirty();
        });
        $label.append($check).append(document.createTextNode(' ' + text));
        return $('<div class="checkbox"></div>').append($label);
    }

    function select(options, value, onChange) {
        var $select = $('<select class="form-control input-sm"></select>');
        $.each(options, function (key, text) {
            $select.append($('<option></option>').attr('value', key).text(text));
        });
        $select.val(value === undefined || value === null ? '' : String(value));
        $select.on('change', function () {
            onChange($(this).val());
            markDirty();
        });
        return $select;
    }

    function col(size, $content) {
        return $('<div></div>').addClass('col-sm-' + size).append($content);
    }

    function setValidation(field, rule, value) {
        if (value === '' || value === false || value === null) {
            delete field.validations[rule];
        } else {
            field.validations[rule] = value;
        }
    }

    function allows(field, rule) {
        return (catalog.validations[field.type] || []).indexOf(rule) !== -1;
    }

    /* ------------------------------------------------------------------ */
    /* Render                                                              */
    /* ------------------------------------------------------------------ */

    function render() {
        renderCoreButtons();
        renderPresetButtons();
        var $container = $('#pk-rf-fields').empty();
        fields.forEach(function (field, index) {
            $container.append(renderCard(field, index));
        });
        if ($.fn.sortable) {
            $container.sortable({
                handle: '.pk-rf-handle',
                items: '> .pk-rf-card',
                placeholder: 'pk-rf-card-placeholder',
                forcePlaceholderSize: true,
                update: function () {
                    var order = $container.children('.pk-rf-card').map(function () {
                        return $(this).data('uid');
                    }).get();
                    fields.sort(function (a, b) {
                        return order.indexOf(a._uid) - order.indexOf(b._uid);
                    });
                    markDirty();
                    render();
                }
            });
        }
    }

    function renderCard(field, index) {
        var core = isCore(field);
        var open = openUid === field._uid;
        var $card = $('<div class="pk-rf-card"></div>')
            .attr('data-uid', field._uid)
            .toggleClass('pk-rf-open', open)
            .toggleClass('pk-rf-core', core)
            .toggleClass('pk-rf-section-card', field.type === 'section');

        // Cabecera
        var $head = $('<div class="pk-rf-card-head"></div>');
        $head.append('<span class="pk-rf-handle" title="Arrastrar para ordenar"><em class="fa fa-arrows"></em></span>');
        var $title = $('<div class="pk-rf-card-title"></div>');
        $title.append($('<em class="fa fa-fw"></em>').addClass(TYPE_ICONS[field.type] || 'fa-question'));
        $title.append($('<strong></strong>').text(field.label || '(sin etiqueta)'));
        $title.append($('<small class="text-muted"></small>').text(' · ' + typeLabel(field.type)));
        if (core) {
            $title.append(' <span class="label label-info">Dato principal</span>');
        }
        if (field.required) {
            $title.append(' <span class="label label-danger">Obligatorio</span>');
        }
        if (field.config.map_to) {
            $title.append(' <span class="label label-warning"><em class="fa fa-link"></em></span>');
        }
        if (field.config.show_if && field.config.show_if.field) {
            var decide = fields.filter(function (other) {
                return other.field_key === field.config.show_if.field;
            })[0];
            $title.append($('<span class="label label-primary"></span>')
                .attr('title', 'Se muestra solo según «' + (decide ? decide.label : field.config.show_if.field) + '»')
                .append('<em class="fa fa-eye"></em> Condicional'));
        }
        $head.append($title);

        var $tools = $('<div class="pk-rf-card-tools btn-group btn-group-xs"></div>');
        $tools.append(toolButton('fa-arrow-up', 'Subir', index === 0, function () {
            move(index, -1);
        }));
        $tools.append(toolButton('fa-arrow-down', 'Bajar', index === fields.length - 1, function () {
            move(index, 1);
        }));
        if (!core) {
            $tools.append(toolButton('fa-copy', 'Duplicar', false, function () {
                duplicate(index);
            }));
        }
        $tools.append(toolButton('fa-trash text-danger', 'Eliminar', false, function () {
            var aviso = core ? '\n\nEs un dato principal: el formulario no se podrá publicar sin él.' : '';
            if (confirm('¿Eliminar el campo "' + (field.label || typeLabel(field.type)) + '"?' + aviso)) {
                fields.splice(index, 1);
                // Los que se mostraban según este campo pasan a mostrarse siempre.
                fields.forEach(function (other) {
                    if (field.field_key && other.config.show_if && other.config.show_if.field === field.field_key) {
                        delete other.config.show_if;
                    }
                });
                markDirty();
                render();
            }
        }));
        $tools.append(toolButton(open ? 'fa-chevron-up' : 'fa-chevron-down', open ? 'Cerrar' : 'Editar', false, function () {
            openUid = open ? null : field._uid;
            render();
        }));
        $head.append($tools);
        $head.on('click', function (e) {
            if ($(e.target).closest('.pk-rf-card-tools, .pk-rf-handle').length) {
                return;
            }
            openUid = open ? null : field._uid;
            render();
        });
        $card.append($head);

        if (open) {
            $card.append(renderBody(field));
        }

        return $card;
    }

    function toolButton(icon, title, disabled, onClick) {
        var $btn = $('<button type="button" class="btn btn-default"></button>')
            .attr('title', title)
            .prop('disabled', disabled)
            .append($('<em class="fa"></em>').addClass(icon));
        $btn.on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            onClick();
        });
        return $btn;
    }

    function renderBody(field) {
        var core = isCore(field);
        var $body = $('<div class="pk-rf-card-body"></div>');
        var refreshTitle = function () {
            $body.closest('.pk-rf-card').find('.pk-rf-card-title strong').text(field.label || '(sin etiqueta)');
        };

        // Etiqueta y tipo
        var $row = $('<div class="row"></div>');
        var labelText = field.type === 'section' ? 'Título de la sección' :
            (field.type === 'terms' ? 'Texto de aceptación' : 'Pregunta / etiqueta');
        $row.append(col(8, formGroup(labelText, input('text', field.label, function (v) {
            field.label = v;
            refreshTitle();
        }, {maxlength: 255}))));

        var $type;
        if (core) {
            $type = $('<p class="form-control-static"></p>').text(typeLabel(field.type) + ' (dato principal)');
        } else {
            $type = select(catalog.types, field.type, function (v) {
                changeType(field, v);
            });
        }
        $row.append(col(4, formGroup('Tipo de campo', $type)));
        $body.append($row);

        // Ayuda, placeholder y ancho
        $row = $('<div class="row"></div>');
        $row.append(col(field.type === 'section' ? 12 : 6, formGroup(
            field.type === 'section' ? 'Descripción de la sección' : 'Texto de ayuda',
            $('<textarea class="form-control input-sm" rows="2"></textarea>').val(field.config.help || '').on('input', function () {
                field.config.help = $(this).val();
                markDirty();
            })
        )));
        if (field.type !== 'section') {
            if (TEXT_LIKE.indexOf(field.type) !== -1) {
                $row.append(col(3, formGroup(field.type === 'select' ? 'Texto de la opción vacía' : 'Placeholder', input('text', field.config.placeholder, function (v) {
                    field.config.placeholder = v;
                }, {maxlength: 255}))));
            }
            $row.append(col(3, formGroup('Ancho', select({12: 'Completo', 6: 'Mitad', 4: 'Un tercio'}, field.config.width, function (v) {
                field.config.width = parseInt(v, 10);
            }))));
        }
        $body.append($row);

        if (field.type === 'section') {
            return $body;
        }

        // Opciones
        if (catalog.typesWithOptions.indexOf(field.type) !== -1) {
            $body.append(renderOptions(field));
        }

        // Términos
        if (field.type === 'terms') {
            var mode = field.config.terms_mode || (field.config.url ? 'url' : 'none');
            $row = $('<div class="row"></div>');
            $row.append(col(5, formGroup('Documento de los términos', select({
                none: 'Sin enlace (solo la casilla)',
                url: 'Enlace a una URL (se abre en otra pestaña)',
                modal: 'Texto en una ventana emergente'
            }, mode, function (v) {
                field.config.terms_mode = v;
                render();
            }))));
            if (mode !== 'none') {
                $row.append(col(4, formGroup('Texto del enlace', input('text', field.config.link_text, function (v) {
                    field.config.link_text = v;
                }, {placeholder: mode === 'url' ? 'Ver documento' : 'Leer términos y condiciones'}))));
            }
            $body.append($row);
            if (mode === 'url') {
                $body.append(formGroup('URL del documento', input('url', field.config.url, function (v) {
                    field.config.url = v;
                }, {placeholder: 'https://...'})));
            } else if (mode === 'modal') {
                $body.append(formGroup(
                    'Texto de los términos',
                    $('<textarea class="form-control input-sm" rows="10"></textarea>')
                        .attr('placeholder', 'Escriba o pegue aquí los términos y condiciones…')
                        .val(field.config.terms_text || '')
                        .on('input', function () {
                            field.config.terms_text = $(this).val();
                            markDirty();
                        }),
                    'Se muestra en una ventana al pulsar el enlace; el botón "Acepto" marca la casilla. Admite HTML básico (<p>, <b>, <ul>, <a>…).'
                ));
            }
        }

        // Validaciones
        var $validations = renderValidations(field);
        if ($validations) {
            $body.append($validations);
        }

        // Visibilidad condicional
        if (!core) {
            var $condition = renderCondition(field);
            if ($condition) {
                $body.append($condition);
            }
        }

        // Pie: obligatorio, clave y vínculo
        var $foot = $('<div class="row pk-rf-card-foot"></div>');
        var lockRequired = core || field.type === 'terms';
        $foot.append(col(3, checkbox('Obligatorio', field.required || lockRequired, function (v) {
            field.required = v;
            render();
        }, lockRequired)));
        if (!core) {
            $foot.append(col(4, formGroup('Clave interna', input('text', field.field_key, function (v) {
                // Las condiciones que dependen de este campo siguen apuntándole.
                fields.forEach(function (other) {
                    if (field.field_key && other.config.show_if && other.config.show_if.field === field.field_key) {
                        other.config.show_if.field = v;
                    }
                });
                field.field_key = v;
            }, {maxlength: 50, placeholder: 'Se genera automáticamente'}), 'Letras minúsculas, números y _. Se usa en exportaciones.')));
        }
        if (catalog.mappableTypes.indexOf(field.type) !== -1) {
            var targets = $.extend({'': 'No vincular'}, catalog.mapTargets);
            $foot.append(col(5, formGroup('Guardar también en', select(targets, field.config.map_to, function (v) {
                field.config.map_to = v;
                render();
            }), 'Copia el valor a una columna del usuario (reportes Proikos).')));
        }
        $body.append($foot);

        return $body;
    }

    function renderOptions(field) {
        var $wrap = $('<div class="pk-rf-options"></div>');
        if (field.type === 'select' && renderOptionsSource(field, $wrap)) {
            return $wrap;
        }
        $wrap.append('<label class="control-label">Opciones</label>');
        var $list = $('<div></div>');
        var icon = field.type === 'radio' ? 'fa-circle-o' : (field.type === 'checkbox' ? 'fa-square-o' : 'fa-angle-right');

        field.options.forEach(function (option, i) {
            var $line = $('<div class="pk-rf-option"></div>');
            $line.append($('<em class="fa fa-fw text-muted"></em>').addClass(icon));
            $line.append(input('text', option.label, function (v) {
                option.label = v;
            }, {placeholder: 'Opción ' + (i + 1), 'class': 'form-control input-sm pk-rf-option-label'}));
            $line.append(input('text', option.value, function (v) {
                option.value = v;
            }, {placeholder: 'Valor (opcional)', 'class': 'form-control input-sm pk-rf-option-value', title: 'Valor que se guarda. Si se deja vacío se usa el texto de la opción.'}));
            $line.append(toolButton('fa-times', 'Quitar opción', false, function () {
                field.options.splice(i, 1);
                markDirty();
                render();
            }).removeClass('btn-default').addClass('btn-link btn-xs'));
            $list.append($line);
        });
        $wrap.append($list);

        var $add = $('<button type="button" class="btn btn-link btn-sm"><em class="fa fa-plus"></em> Agregar opción</button>');
        $add.on('click', function () {
            field.options.push({value: '', label: ''});
            markDirty();
            render();
            $('.pk-rf-open .pk-rf-option-label').last().focus();
        });
        var $bulk = $('<button type="button" class="btn btn-link btn-sm"><em class="fa fa-list"></em> Pegar varias</button>');
        $bulk.on('click', function () {
            var text = prompt('Escriba una opción por línea. Para usar un valor distinto al texto: valor|texto');
            if (!text) {
                return;
            }
            text.split(/\r?\n/).forEach(function (line) {
                line = line.trim();
                if (!line) {
                    return;
                }
                var parts = line.split('|');
                field.options.push(parts.length > 1 ? {value: parts[0].trim(), label: parts.slice(1).join('|').trim()} : {value: '', label: line});
            });
            markDirty();
            render();
        });
        $wrap.append($add).append($bulk);
        if (field.config.map_to === 'pk.gender') {
            $wrap.append('<p class="help-block">Para género use los valores <code>M</code>, <code>F</code> y <code>X</code> (sin definir): se guarda una sola letra.</p>');
        }
        return $wrap;
    }

    /** Misma regla que RegistrationFormManager::slugify(). */
    function slugify(text) {
        return String(text || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '').slice(0, 50);
    }

    /** Asigna una clave interna al campo si aún no la tiene (para poder referirse a él). */
    function ensureKey(field) {
        if (!field.field_key) {
            var base = slugify(field.label) || 'campo';
            if (CORE.indexOf(base) !== -1 || base === 'password_confirm') {
                base += '_extra';
            }
            var key = base, n = 2;
            while (fields.some(function (other) { return other !== field && other.field_key === key; })) {
                key = base + '_' + n++;
            }
            field.field_key = key;
        }
        return field.field_key;
    }

    /** Valor que guarda una opción: el escrito o, si está vacío, su texto (como el servidor). */
    function optionValue(option) {
        return String(option.value || option.label || '').trim();
    }

    /**
     * "Mostrar solo si": el campo aparece solo cuando en una opción única, lista
     * o casillas se marca alguna de las opciones elegidas. Sin condición, siempre.
     */
    function renderCondition(field) {
        var candidates = fields.filter(function (other) {
            return other !== field && ['radio', 'select', 'checkbox'].indexOf(other.type) !== -1
                && !other.config.options_source && other.options.length;
        });
        if (!candidates.length) {
            return null;
        }
        var condition = field.config.show_if || null;
        var parent = condition ? candidates.filter(function (other) {
            return other.field_key === condition.field;
        })[0] : null;

        var $panel = $('<div class="pk-rf-validations"></div>');
        $panel.append('<div class="pk-rf-validations-title"><em class="fa fa-eye"></em> Mostrar solo si</div>');
        var choices = {'': 'Siempre visible (sin condición)'};
        candidates.forEach(function (other) {
            choices[other._uid] = (other.label || '(sin etiqueta)') + ' · ' + typeLabel(other.type);
        });
        var $row = $('<div class="row"></div>');
        $row.append(col(6, formGroup('Según el campo', select(choices, parent ? parent._uid : '', function (val) {
            var chosen = candidates.filter(function (other) {
                return String(other._uid) === val;
            })[0];
            if (chosen) {
                field.config.show_if = {field: ensureKey(chosen), values: []};
            } else {
                delete field.config.show_if;
            }
            render();
        }))));
        $panel.append($row);

        if (parent) {
            var $values = $('<div class="pk-rf-condition-values"></div>');
            $values.append($('<label class="control-label"></label>').text('Se muestra cuando se marca:'));
            parent.options.forEach(function (option) {
                var value = optionValue(option);
                if (!value) {
                    return;
                }
                $values.append(checkbox(option.label || value, condition.values.indexOf(value) !== -1, function (checked) {
                    condition.values = condition.values.filter(function (v) { return v !== value; });
                    if (checked) {
                        condition.values.push(value);
                    }
                }));
            });
            $panel.append($values);
            $panel.append('<p class="help-block">Si no se cumple, el campo no se muestra, no se exige aunque sea obligatorio y no se guarda.</p>');
        }
        return $panel;
    }

    /**
     * Origen de las opciones de una lista: escritas a mano o del Ubigeo de Perú
     * (se leen del archivo; no se guardan). Devuelve true si el origen es el
     * Ubigeo, y entonces no hay opciones que editar.
     */
    function renderOptionsSource(field, $wrap) {
        var source = field.config.options_source || '';
        var $row = $('<div class="row"></div>');
        $row.append(col(6, formGroup('Origen de las opciones', select($.extend({'': 'Escritas a mano'}, catalog.optionSources || {}), source, function (val) {
            field.config.options_source = val;
            if (!val) {
                field.config.parent = '';
                // Las del Ubigeo no se copian como opciones escritas a mano.
                field.options = [{value: '', label: 'Opción 1'}, {value: '', label: 'Opción 2'}];
            }
            render();
        }))));
        if (source && source !== 'ubigeo_departamentos') {
            var parents = {'': 'No depende (muestra todas)'};
            fields.forEach(function (other) {
                if (other !== field && other.type === 'select' && other.field_key) {
                    parents[other.field_key] = other.label || other.field_key;
                }
            });
            $row.append(col(6, formGroup('Depende de', select(parents, field.config.parent, function (val) {
                field.config.parent = val;
            }), source === 'ubigeo_provincias' ? 'Elija la lista de Región.' : 'Elija la lista de Provincia.')));
        }
        $wrap.append($row);
        if (source) {
            $wrap.append('<p class="help-block"><em class="fa fa-map"></em> Las opciones se leen del Ubigeo de Perú (INEI): no hay nada que escribir. Si depende de otra lista, se activa al elegir en ella y solo muestra las opciones que corresponden.</p>');
            return true;
        }
        return false;
    }

    function renderValidations(field) {
        var rules = catalog.validations[field.type] || [];
        if (!rules.length && field.type !== 'password') {
            return null;
        }
        var v = field.validations;
        var $panel = $('<div class="pk-rf-validations"></div>');
        $panel.append('<div class="pk-rf-validations-title"><em class="fa fa-shield"></em> Validaciones</div>');
        var $row = $('<div class="row"></div>');
        var unit = field.type === 'tel' ? 'dígitos' : 'caracteres';

        if (field.type === 'password') {
            return renderPasswordValidations(field, $panel);
        }
        if (field.type === 'email' && isCore(field)) {
            $panel.append('<p class="help-block">Siempre se valida el formato del correo.</p>');
        }

        function number(rule, text, attrs) {
            $row.append(col(3, formGroup(text, input('number', v[rule], function (val) {
                setValidation(field, rule, val);
            }, attrs || {min: 0}))));
        }

        if (allows(field, 'min_length')) {
            number('min_length', 'Mín. ' + unit);
        }
        if (allows(field, 'max_length')) {
            number('max_length', 'Máx. ' + unit);
        }
        if (allows(field, 'min')) {
            number('min', 'Valor mínimo', {step: 'any'});
        }
        if (allows(field, 'max')) {
            number('max', 'Valor máximo', {step: 'any'});
        }
        if (allows(field, 'min_date')) {
            $row.append(col(3, formGroup('Fecha mínima', input('date', v.min_date, function (val) {
                setValidation(field, 'min_date', val);
            }))));
        }
        if (allows(field, 'max_date')) {
            $row.append(col(3, formGroup('Fecha máxima', input('date', v.max_date, function (val) {
                setValidation(field, 'max_date', val);
            }))));
        }
        if (allows(field, 'min_age')) {
            number('min_age', 'Edad mínima (años)');
        }
        if (allows(field, 'max_age')) {
            number('max_age', 'Edad máxima (años)');
        }
        if (allows(field, 'min_selected')) {
            number('min_selected', 'Mín. seleccionadas');
        }
        if (allows(field, 'max_selected')) {
            number('max_selected', 'Máx. seleccionadas');
        }
        if (allows(field, 'extensions')) {
            $row.append(col(6, formGroup('Extensiones permitidas', input('text', v.extensions, function (val) {
                setValidation(field, 'extensions', val);
            }, {placeholder: 'pdf,jpg,png'}), 'Permitidas: ' + catalog.fileExtensions.join(', '))));
        }
        if (allows(field, 'max_size_mb')) {
            number('max_size_mb', 'Tamaño máx. (MB)', {min: 1, max: catalog.maxFileSizeMb, step: 'any'});
        }
        if (allows(field, 'allowed_domains')) {
            $row.append(col(6, formGroup('Dominios permitidos', input('text', v.allowed_domains, function (val) {
                setValidation(field, 'allowed_domains', val);
            }, {placeholder: 'empresa.com, empresa.pe'}), 'Opcional. Separe con comas.')));
        }
        if (allows(field, 'pattern')) {
            var patterns = $.extend({'': 'Sin formato específico'}, catalog.patterns);
            $row.append(col(4, formGroup('Formato', select(patterns, v.pattern, function (val) {
                setValidation(field, 'pattern', val);
                render();
            }))));
            if (v.pattern === 'custom') {
                $row.append(col(5, formGroup('Expresión regular', input('text', v.regex, function (val) {
                    setValidation(field, 'regex', val);
                }, {placeholder: '[A-Z]{3}-[0-9]{4}'}), 'Se valida el valor completo (no use ^ ni $).')));
            }
            if (v.pattern) {
                $row.append(col(v.pattern === 'custom' ? 12 : 5, formGroup('Mensaje de error', input('text', v.pattern_message, function (val) {
                    setValidation(field, 'pattern_message', val);
                }, {placeholder: 'Mensaje que verá el usuario si el formato no es válido'}))));
            }
        }
        $panel.append($row);

        if (allows(field, 'integer')) {
            $panel.append(checkbox('Solo números enteros', v.integer, function (val) {
                setValidation(field, 'integer', val);
            }));
        }
        if (allows(field, 'unique')) {
            var uniqueText = isCore(field) ? 'No permitir correos ya registrados en la plataforma' : 'No permitir valores repetidos entre inscritos';
            $panel.append(checkbox(uniqueText, v.unique, function (val) {
                setValidation(field, 'unique', val);
            }));
        }
        return $panel;
    }

    function renderPasswordValidations(field, $panel) {
        var v = field.validations;
        var levels = catalog.passwordLevels || {};
        var level = levels[v.password_level] ? v.password_level : 'chamilo';
        var options = {};
        $.each(levels, function (key, item) {
            options[key] = item.label;
        });

        var $row = $('<div class="row"></div>');
        $row.append(col(5, formGroup('Nivel de seguridad', select(options, level, function (val) {
            setValidation(field, 'password_level', val === 'chamilo' ? '' : val);
            render();
        }))));
        if (level === 'custom') {
            $row.append(col(3, formGroup('Mín. caracteres', input('number', v.min_length || 8, function (val) {
                setValidation(field, 'min_length', val);
            }, {min: 4, max: 64}))));
        } else {
            $row.append(col(7, $('<p class="help-block pk-rf-pass-summary"></p>').text('Exige ' + levels[level].summary + '.')));
        }
        $panel.append($row);

        if (level === 'custom') {
            var $checks = $('<div class="pk-rf-pass-requires"></div>');
            [['require_lower', 'Una letra minúscula'], ['require_upper', 'Una letra mayúscula'],
                ['require_digit', 'Un número'], ['require_special', 'Un carácter especial (!@#$%…)']].forEach(function (item) {
                $checks.append(checkbox(item[1], v[item[0]], function (val) {
                    setValidation(field, item[0], val);
                }));
            });
            $panel.append($checks);
        }

        var $display = $('<div class="row"></div>');
        $display.append(col(5, formGroup('Requisitos de la contraseña', select(catalog.passwordRequirementsDisplay, v.requirements_display || 'inline', function (val) {
            setValidation(field, 'requirements_display', val === 'inline' ? '' : val);
        }), 'Cómo ve el usuario lo que le falta (✓ / ○). El servidor valida siempre.')));
        $panel.append($display);

        $panel.append(checkbox('Pedir confirmación (escribir la contraseña dos veces)', !v.no_confirm, function (val) {
            setValidation(field, 'no_confirm', !val);
        }));
        $panel.append(checkbox('Ocultar la barra de seguridad', v.hide_strength, function (val) {
            setValidation(field, 'hide_strength', val);
        }));
        $panel.append('<p class="help-block">Si en la configuración del formulario la contraseña es generada o asignada desde otro campo, este campo no se muestra.</p>');
        return $panel;
    }

    /* ------------------------------------------------------------------ */
    /* Acciones                                                            */
    /* ------------------------------------------------------------------ */

    function changeType(field, newType) {
        field.type = newType;
        // Conservar solo las validaciones que admite el nuevo tipo.
        var allowed = catalog.validations[newType] || [];
        Object.keys(field.validations).forEach(function (rule) {
            if (allowed.indexOf(rule) === -1) {
                delete field.validations[rule];
            }
        });
        if (catalog.typesWithOptions.indexOf(newType) !== -1 && !field.options.length) {
            field.options = [{value: '', label: 'Opción 1'}];
        }
        if (catalog.mappableTypes.indexOf(newType) === -1) {
            field.config.map_to = '';
        }
        if (newType !== 'select' && field.config.options_source) {
            field.config.options_source = '';
            field.config.parent = '';
            field.options = [{value: '', label: 'Opción 1'}, {value: '', label: 'Opción 2'}];
        }
        if (newType === 'terms') {
            field.required = true;
        }
        if (newType === 'section') {
            field.required = false;
        }
        render();
    }

    function addField(type) {
        var core = CORE.indexOf(type) !== -1;
        var field = normalize(core ? {
            // Dato principal: listo para usar, con su etiqueta y siempre obligatorio.
            type: type,
            field_key: type,
            label: catalog.coreLabels[type],
            required: true,
            validations: type === 'email' ? {unique: true} : {},
            config: {width: 6}
        } : {
            type: type,
            label: type === 'section' ? 'Nueva sección' : (type === 'terms' ? 'Acepto los términos y condiciones' : ''),
            required: type === 'terms',
            options: catalog.typesWithOptions.indexOf(type) !== -1 ? [{value: '', label: 'Opción 1'}, {value: '', label: 'Opción 2'}] : []
        });
        insertField(field);
    }

    /** Agrega el campo al final, lo deja abierto y lo muestra. */
    function insertField(field) {
        fields.push(field);
        openUid = field._uid;
        markDirty();
        render();
        var $card = $('.pk-rf-card[data-uid="' + field._uid + '"]');
        if ($card.length) {
            $('html, body').animate({scrollTop: $card.offset().top - 80}, 200);
            $card.find('input[type=text]').first().focus();
        }
    }

    function move(index, delta) {
        var target = index + delta;
        if (target < 0 || target >= fields.length) {
            return;
        }
        var tmp = fields[index];
        fields[index] = fields[target];
        fields[target] = tmp;
        markDirty();
        render();
    }

    function duplicate(index) {
        var copy = normalize(JSON.parse(JSON.stringify(fields[index])));
        copy.id = 0;
        copy.field_key = '';
        copy.label = copy.label + ' (copia)';
        fields.splice(index + 1, 0, copy);
        openUid = copy._uid;
        markDirty();
        render();
    }

    /** Botones de los datos principales: cada uno se agrega una sola vez. */
    function renderCoreButtons() {
        var $buttons = $('#pk-rf-add-core').empty();
        CORE.forEach(function (type) {
            var added = fields.some(function (field) {
                return field.type === type;
            });
            var $btn = $('<button type="button" class="btn btn-sm"></button>')
                .addClass(added ? 'btn-default' : 'btn-primary')
                .prop('disabled', added)
                .attr('title', added ? 'Ya está en el formulario' : 'Agregar al formulario');
            $btn.append($('<em class="fa fa-fw"></em>').addClass(added ? 'fa-check' : (TYPE_ICONS[type] || 'fa-question')));
            $btn.append(document.createTextNode(' ' + catalog.coreLabels[type]));
            $btn.on('click', function () {
                addField(type);
            });
            $buttons.append($btn);
        });
    }

    /**
     * Campos predefinidos (Sexo, Tipo de documento…). Un predefinido vinculado a
     * una columna se ofrece una vez: dos campos no pueden guardar en la misma columna.
     */
    function renderPresetButtons() {
        var $buttons = $('#pk-rf-add-presets').empty();
        $.each(catalog.presets || {}, function (key, preset) {
            var mapTo = (preset.field.config || {}).map_to || '';
            var added = fields.some(function (field) {
                return field.field_key === key || (mapTo && field.config.map_to === mapTo);
            });
            var $btn = $('<button type="button" class="btn btn-default btn-sm"></button>')
                .prop('disabled', added)
                .attr('title', added ? 'Ya está en el formulario' : 'Agregar con sus opciones y validaciones');
            $btn.append($('<em class="fa fa-fw"></em>').addClass(added ? 'fa-check' : preset.icon));
            $btn.append(document.createTextNode(' ' + preset.button));
            $btn.on('click', function () {
                var field = normalize($.extend(true, {field_key: key}, preset.field));
                insertField(field);
            });
            $buttons.append($btn);
        });
    }

    function renderAddButtons() {
        var $buttons = $('#pk-rf-add-buttons');
        $.each(catalog.types, function (type, text) {
            var $btn = $('<button type="button" class="btn btn-default btn-sm"></button>');
            $btn.append($('<em class="fa fa-fw"></em>').addClass(TYPE_ICONS[type] || 'fa-question'));
            $btn.append(document.createTextNode(' ' + text));
            $btn.on('click', function () {
                addField(type);
            });
            $buttons.append($btn);
        });
    }

    /**
     * Con la plantilla compilada antigua en caché (Twig sin auto_reload) faltan
     * los bloques de datos principales y predefinidos: se crean aquí.
     */
    function ensureAddSections() {
        if ($('#pk-rf-add-core').length) {
            return;
        }
        var $bar = $('#pk-rf-add-buttons').closest('.pk-rf-add-bar');
        $bar.prepend(
            '<span class="pk-rf-add-title"><em class="fa fa-id-card-o"></em> Datos principales (necesarios para crear la cuenta):</span>' +
            '<div id="pk-rf-add-core" class="pk-rf-add-buttons"></div>' +
            '<span class="pk-rf-add-title"><em class="fa fa-magic"></em> Campos predefinidos (listos para usar, con sus opciones):</span>' +
            '<div id="pk-rf-add-presets" class="pk-rf-add-buttons"></div>'
        );
    }

    $(function () {
        ensureAddSections();
        renderAddButtons();
        render();

        $('#pk-rf-builder-form').on('submit', function (e) {
            var missing = fields.filter(function (field) {
                return !String(field.label || '').trim();
            });
            if (missing.length) {
                e.preventDefault();
                openUid = missing[0]._uid;
                render();
                alert('Todos los campos deben tener una pregunta / etiqueta.');
                return;
            }
            var payload = fields.map(function (field) {
                var copy = $.extend(true, {}, field);
                delete copy._uid;
                return copy;
            });
            $('#pk-rf-fields-json').val(JSON.stringify(payload));
            dirty = false;
        });

        window.addEventListener('beforeunload', function (e) {
            if (dirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    });
})(jQuery);
