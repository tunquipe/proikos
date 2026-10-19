/**
 * Barra lateral de personalización de apariencia (plugin Proikos).
 * Solo se carga para administradores. Los cambios se previsualizan sobre la página
 * actual y se mantienen como borrador (sessionStorage) al navegar entre páginas
 * hasta que se publican con "Guardar".
 */
(function () {
    'use strict';

    var cfg = window.PK_APPEARANCE;
    if (!cfg) {
        return;
    }

    var STORAGE_DRAFT = 'pkAppearanceDraft';
    var STORAGE_OPEN = 'pkAppearanceOpen';
    var STORAGE_SECTION = 'pkAppearanceSection';

    var schema = null;
    var published = {};
    var draft = {};
    var root = null;
    var previewTimer = null;
    var originals = null;

    /* ------------------------------------------------------------------ */
    /* Utilidades                                                          */
    /* ------------------------------------------------------------------ */

    function el(tag, attrs, children) {
        var node = document.createElement(tag);
        Object.keys(attrs || {}).forEach(function (key) {
            if (key === 'text') {
                node.textContent = attrs[key];
            } else if (key === 'className') {
                node.className = attrs[key];
            } else if (key.indexOf('on') === 0) {
                node.addEventListener(key.substring(2), attrs[key]);
            } else if (attrs[key] !== null && attrs[key] !== undefined && attrs[key] !== false) {
                node.setAttribute(key, attrs[key]);
            }
        });
        (children || []).forEach(function (child) {
            if (child) {
                node.appendChild(typeof child === 'string' ? document.createTextNode(child) : child);
            }
        });
        return node;
    }

    function icon(name) {
        return el('em', {className: 'fa ' + name, 'aria-hidden': 'true'});
    }

    function storage(key, value) {
        try {
            if (value === undefined) {
                return window.sessionStorage.getItem(key);
            }
            if (value === null) {
                window.sessionStorage.removeItem(key);
            } else {
                window.sessionStorage.setItem(key, value);
            }
        } catch (e) {
            return null;
        }
        return null;
    }

    function request(action, data, isForm) {
        var body;
        if (isForm) {
            body = data;
        } else {
            body = new FormData();
            Object.keys(data || {}).forEach(function (key) {
                body.append(key, data[key]);
            });
        }
        if (action !== 'get') {
            body.append('pk_appearance_sec_token', cfg.token);
        }
        return fetch(cfg.ajax + '?a=' + action, {
            method: action === 'get' ? 'GET' : 'POST',
            body: action === 'get' ? undefined : body,
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json().then(function (json) {
                if (!response.ok) {
                    throw new Error(json.error || 'Error');
                }
                return json;
            });
        });
    }

    /** Garantiza un objeto plano: PHP puede devolver [] para una configuración vacía. */
    function asObject(value) {
        return value && typeof value === 'object' && !Array.isArray(value) ? value : {};
    }

    function isDirty() {
        return JSON.stringify(draft) !== JSON.stringify(published);
    }

    function setValue(key, value) {
        if (value === '' || value === null || value === undefined) {
            delete draft[key];
        } else {
            draft[key] = value;
        }
        storage(STORAGE_DRAFT, JSON.stringify(draft));
        updateStatus();
        schedulePreview();
    }

    function toast(message, type) {
        var node = el('div', {className: 'pk-cz-toast pk-cz-toast-' + (type || 'ok'), text: message});
        document.body.appendChild(node);
        setTimeout(function () {
            node.classList.add('pk-cz-toast-hide');
        }, 2500);
        setTimeout(function () {
            node.remove();
        }, 3000);
    }

    /* ------------------------------------------------------------------ */
    /* Vista previa en la página actual                                    */
    /* ------------------------------------------------------------------ */

    function rememberOriginals() {
        if (originals) {
            return;
        }
        originals = {logos: [], texts: {}, character: []};
        document.querySelectorAll('.pk-site-logo').forEach(function (img) {
            originals.logos.push([img, img.getAttribute('data-default-src') || img.getAttribute('src')]);
        });
        document.querySelectorAll('[data-pk-live]').forEach(function (node) {
            originals.texts[node.getAttribute('data-pk-live')] = originals.texts[node.getAttribute('data-pk-live')] || [];
            originals.texts[node.getAttribute('data-pk-live')].push([node, node.getAttribute('data-default') !== null ? node.getAttribute('data-default') : node.innerHTML]);
        });
        document.querySelectorAll('.pk-login-character').forEach(function (img) {
            originals.character.push([img, img.getAttribute('data-default-src') || img.getAttribute('src')]);
        });
    }

    function schedulePreview() {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(applyPreview, 250);
    }

    function applyPreview() {
        request('preview', {settings: JSON.stringify(draft)}).then(function (result) {
            var style = document.getElementById('pk-appearance-preview');
            if (!style) {
                style = el('style', {id: 'pk-appearance-preview'});
                document.head.appendChild(style);
            }
            style.textContent = result.css;
            // El CSS publicado se desactiva para que los valores quitados vuelvan al tema.
            var publishedCss = document.getElementById('pk-appearance-css');
            if (publishedCss) {
                publishedCss.disabled = true;
            }
            applyVars(asObject(result.vars));
        }).catch(function (error) {
            toast(error.message, 'error');
        });
    }

    function applyVars(vars) {
        rememberOriginals();
        originals.logos.forEach(function (pair) {
            pair[0].src = vars.logo || pair[1];
        });
        originals.character.forEach(function (pair) {
            pair[0].src = vars.login_character || pair[1];
        });
        Object.keys(originals.texts).forEach(function (key) {
            originals.texts[key].forEach(function (pair) {
                var value = vars[key];
                if (key === 'footer_html') {
                    pair[0].innerHTML = value || '';
                    pair[0].style.display = value ? '' : 'none';
                } else if (value) {
                    pair[0].innerHTML = escapeHtml(value).replace(/\n/g, '<br>');
                } else {
                    pair[0].innerHTML = pair[1];
                }
            });
        });
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function clearPreview() {
        var style = document.getElementById('pk-appearance-preview');
        if (style) {
            style.remove();
        }
        var publishedCss = document.getElementById('pk-appearance-css');
        if (publishedCss) {
            publishedCss.disabled = false;
        }
    }

    /* ------------------------------------------------------------------ */
    /* Controles                                                           */
    /* ------------------------------------------------------------------ */

    function resetButton(key, onReset) {
        return el('button', {
            type: 'button',
            className: 'pk-cz-reset',
            title: 'Usar el valor del tema',
            onclick: function () {
                setValue(key, null);
                onReset();
            }
        }, [icon('fa-undo')]);
    }

    function control(key, def) {
        var value = draft[key];
        var wrap = el('div', {className: 'pk-cz-control pk-cz-' + def.type});
        var label = el('label', {className: 'pk-cz-label', text: def.label});
        wrap.appendChild(label);

        switch (def.type) {
            case 'color':
                var picker = el('input', {type: 'color', value: /^#[0-9a-f]{6}$/i.test(value || '') ? value : '#ffffff'});
                var text = el('input', {type: 'text', className: 'pk-cz-input', placeholder: 'Tema', value: value || '', maxlength: 30});
                picker.classList.toggle('pk-cz-unset', !value);
                picker.addEventListener('input', function () {
                    text.value = picker.value;
                    picker.classList.remove('pk-cz-unset');
                    setValue(key, picker.value);
                });
                text.addEventListener('change', function () {
                    var v = text.value.trim();
                    if (v && !/^#([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i.test(v) && !/^rgba?\(/i.test(v)) {
                        toast('Color no válido. Use #rrggbb o rgba(...)', 'error');
                        return;
                    }
                    if (/^#[0-9a-f]{6}$/i.test(v)) {
                        picker.value = v;
                    }
                    picker.classList.toggle('pk-cz-unset', !v);
                    setValue(key, v);
                });
                wrap.appendChild(el('div', {className: 'pk-cz-row'}, [picker, text, resetButton(key, function () {
                    text.value = '';
                    picker.classList.add('pk-cz-unset');
                })]));
                break;

            case 'range':
                var output = el('span', {className: 'pk-cz-range-value', text: value ? value + def.unit : 'Tema'});
                var range = el('input', {type: 'range', min: def.min, max: def.max, step: def.step, value: value || def.min});
                range.classList.toggle('pk-cz-unset', !value);
                range.addEventListener('input', function () {
                    output.textContent = range.value + def.unit;
                    range.classList.remove('pk-cz-unset');
                    setValue(key, parseInt(range.value, 10));
                });
                label.appendChild(output);
                wrap.appendChild(el('div', {className: 'pk-cz-row'}, [range, resetButton(key, function () {
                    output.textContent = 'Tema';
                    range.classList.add('pk-cz-unset');
                })]));
                break;

            case 'toggle':
                var current = value === undefined ? !!def['default'] : !!value;
                var checkbox = el('input', {type: 'checkbox'});
                checkbox.checked = current;
                checkbox.addEventListener('change', function () {
                    // Igual al valor del tema = sin personalizar.
                    setValue(key, checkbox.checked === !!def['default'] ? null : checkbox.checked);
                });
                wrap.innerHTML = '';
                wrap.appendChild(el('label', {className: 'pk-cz-switch'}, [checkbox, el('span', {className: 'pk-cz-slider'}), el('span', {text: def.label})]));
                break;

            case 'select':
                var select = el('select', {className: 'pk-cz-input'});
                Object.keys(def.options).forEach(function (optionKey) {
                    var option = el('option', {value: optionKey, text: def.options[optionKey]});
                    if ((value || '') === optionKey) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
                select.addEventListener('change', function () {
                    setValue(key, select.value);
                });
                wrap.appendChild(select);
                break;

            case 'text':
                var input = el('input', {type: 'text', className: 'pk-cz-input', value: value || '', placeholder: def.placeholder || '', maxlength: 200});
                input.addEventListener('input', function () {
                    setValue(key, input.value);
                });
                wrap.appendChild(input);
                break;

            case 'textarea':
            case 'code':
                var area = el('textarea', {className: 'pk-cz-input', rows: def.type === 'code' ? 10 : 3, spellcheck: def.type === 'code' ? 'false' : null});
                area.value = value || '';
                area.addEventListener('input', function () {
                    setValue(key, area.value);
                });
                wrap.appendChild(area);
                break;

            case 'image':
                wrap.appendChild(imageControl(key, value));
                break;
        }

        if (def.help) {
            wrap.appendChild(el('div', {className: 'pk-cz-help', text: def.help}));
        }
        return wrap;
    }

    function imageControl(key, value) {
        var box = el('div', {className: 'pk-cz-image'});
        var thumb = el('div', {className: 'pk-cz-thumb'});
        var file = el('input', {type: 'file', accept: '.png,.jpg,.jpeg,.gif,.webp,.svg,.ico', style: 'display:none'});
        var remove = el('button', {type: 'button', className: 'pk-cz-btn pk-cz-btn-light', title: 'Quitar'}, [icon('fa-trash')]);

        function render(url) {
            thumb.innerHTML = '';
            if (url) {
                thumb.appendChild(el('img', {src: url, alt: ''}));
            } else {
                thumb.appendChild(el('span', {text: 'Imagen del tema'}));
            }
            remove.style.display = url ? '' : 'none';
        }

        file.addEventListener('change', function () {
            if (!file.files.length) {
                return;
            }
            var data = new FormData();
            data.append('image', file.files[0]);
            box.classList.add('pk-cz-loading');
            request('upload', data, true).then(function (result) {
                render(result.url);
                setValue(key, result.url);
            }).catch(function (error) {
                toast(error.message, 'error');
            }).then(function () {
                box.classList.remove('pk-cz-loading');
                file.value = '';
            });
        });
        remove.addEventListener('click', function () {
            render('');
            setValue(key, null);
        });

        render(value);
        box.appendChild(thumb);
        box.appendChild(el('div', {className: 'pk-cz-row'}, [
            el('button', {type: 'button', className: 'pk-cz-btn pk-cz-btn-light', onclick: function () {
                file.click();
            }}, [icon('fa-upload'), ' Subir imagen']),
            remove,
            file
        ]));
        return box;
    }

    /* ------------------------------------------------------------------ */
    /* Barra lateral                                                       */
    /* ------------------------------------------------------------------ */

    function updateStatus() {
        if (!root) {
            return;
        }
        var dirty = isDirty();
        root.querySelector('.pk-cz-status').textContent = dirty ? 'Cambios sin publicar' : 'Todo publicado';
        root.querySelector('.pk-cz-status').classList.toggle('pk-cz-dirty', dirty);
        root.querySelector('.pk-cz-save').disabled = !dirty;
        root.querySelector('.pk-cz-discard').disabled = !dirty;
    }

    function buildSidebar() {
        root = el('aside', {id: 'pk-customizer', 'aria-label': 'Personalizar apariencia'});

        var header = el('div', {className: 'pk-cz-header'}, [
            el('div', {className: 'pk-cz-title'}, [icon('fa-paint-brush'), ' Personalizar apariencia']),
            el('button', {type: 'button', className: 'pk-cz-close', title: 'Cerrar', onclick: close}, [icon('fa-times')])
        ]);
        root.appendChild(header);

        var nav = el('div', {className: 'pk-cz-pages'}, [el('span', {text: 'Ver:'})]);
        [['home', 'Inicio', 'fa-home'], ['login', 'Login', 'fa-sign-in'], ['register', 'Registro', 'fa-user-plus']].forEach(function (page) {
            nav.appendChild(el('a', {href: cfg.preview[page[0]], className: 'pk-cz-page-link'}, [icon(page[2]), ' ' + page[1]]));
        });
        root.appendChild(nav);

        var body = el('div', {className: 'pk-cz-body'});
        var openSection = storage(STORAGE_SECTION);
        Object.keys(schema).forEach(function (sectionKey) {
            var section = schema[sectionKey];
            var content = el('div', {className: 'pk-cz-section-body'});
            Object.keys(section.controls).forEach(function (key) {
                content.appendChild(control(key, section.controls[key]));
            });
            var sectionNode = el('div', {className: 'pk-cz-section' + (openSection === sectionKey ? ' pk-cz-open' : '')}, [
                el('button', {type: 'button', className: 'pk-cz-section-head', onclick: function () {
                    var wasOpen = sectionNode.classList.contains('pk-cz-open');
                    body.querySelectorAll('.pk-cz-section').forEach(function (node) {
                        node.classList.remove('pk-cz-open');
                    });
                    if (!wasOpen) {
                        sectionNode.classList.add('pk-cz-open');
                    }
                    storage(STORAGE_SECTION, wasOpen ? null : sectionKey);
                }}, [icon(section.icon), ' ' + section.title, icon('fa-chevron-down pk-cz-chevron')]),
                content
            ]);
            body.appendChild(sectionNode);
        });
        root.appendChild(body);

        var footer = el('div', {className: 'pk-cz-footer'}, [
            el('div', {className: 'pk-cz-status'}),
            el('div', {className: 'pk-cz-actions'}, [
                el('button', {type: 'button', className: 'pk-cz-btn pk-cz-btn-primary pk-cz-save', onclick: save}, [icon('fa-check'), ' Guardar y publicar']),
                el('button', {type: 'button', className: 'pk-cz-btn pk-cz-btn-light pk-cz-discard', onclick: discard}, ['Descartar']),
                el('button', {type: 'button', className: 'pk-cz-btn pk-cz-btn-link', onclick: resetAll}, ['Restaurar tema original'])
            ])
        ]);
        root.appendChild(footer);
        document.body.appendChild(root);
        updateStatus();
    }

    function rebuild() {
        if (root) {
            root.remove();
            root = null;
        }
        buildSidebar();
        document.documentElement.classList.add('pk-customizer-open');
    }

    function save() {
        request('save', {settings: JSON.stringify(draft)}).then(function (result) {
            published = asObject(result.settings);
            draft = JSON.parse(JSON.stringify(published));
            storage(STORAGE_DRAFT, null);
            updateStatus();
            toast(result.message || 'Guardado');
        }).catch(function (error) {
            toast(error.message, 'error');
        });
    }

    function discard() {
        if (!confirm('¿Descartar los cambios sin publicar?')) {
            return;
        }
        draft = JSON.parse(JSON.stringify(published));
        storage(STORAGE_DRAFT, null);
        rebuild();
        applyPreview();
    }

    function resetAll() {
        if (!confirm('Se quitarán todas las personalizaciones y el portal volverá a la apariencia original del tema. ¿Continuar?')) {
            return;
        }
        request('reset', {}).then(function (result) {
            published = {};
            draft = {};
            storage(STORAGE_DRAFT, null);
            rebuild();
            applyPreview();
            toast(result.message);
        }).catch(function (error) {
            toast(error.message, 'error');
        });
    }

    function open() {
        storage(STORAGE_OPEN, '1');
        if (root) {
            document.documentElement.classList.add('pk-customizer-open');
            return;
        }
        request('get').then(function (result) {
            schema = result.schema;
            published = asObject(result.settings);
            var saved = storage(STORAGE_DRAFT);
            draft = asObject(saved ? JSON.parse(saved) : JSON.parse(JSON.stringify(published)));
            rebuild();
            if (isDirty()) {
                applyPreview();
            }
        }).catch(function (error) {
            toast(error.message, 'error');
        });
    }

    function close() {
        if (isDirty() && !confirm('Hay cambios sin publicar. Se conservarán como borrador mientras navega. ¿Cerrar la barra?')) {
            return;
        }
        storage(STORAGE_OPEN, null);
        document.documentElement.classList.remove('pk-customizer-open');
        if (!isDirty()) {
            clearPreview();
            if (root) {
                root.remove();
                root = null;
            }
        }
    }

    function init() {
        var launcher = el('button', {
            type: 'button',
            id: 'pk-customizer-launcher',
            title: 'Personalizar apariencia del portal',
            onclick: function () {
                if (document.documentElement.classList.contains('pk-customizer-open')) {
                    close();
                } else {
                    open();
                }
            }
        }, [icon('fa-paint-brush'), el('span', {text: ' Personalizar'})]);
        document.body.appendChild(launcher);

        var params = new URLSearchParams(window.location.search);
        if (params.get('pk_customize') === '1' || storage(STORAGE_OPEN) === '1') {
            open();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
