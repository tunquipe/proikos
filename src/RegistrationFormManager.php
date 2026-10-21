<?php

namespace src;

use ChamiloSession as Session;
use Database;
use Display;
use Event;
use FormValidator;
use HTML_QuickForm_input;
use ProikosPlugin;
use Security;
use UserManager;

require_once __DIR__.'/UbigeoPeru.php';

/**
 * Formularios de inscripción dinámicos (estilo Google Forms).
 *
 * El administrador crea formularios con campos personalizados; los datos principales
 * de Chamilo (nombres, apellidos, correo y contraseña) siempre están presentes y no
 * se pueden eliminar, solo personalizar. Un formulario publicado puede elegirse como
 * formulario de registro de la plataforma.
 *
 * Tablas: plugin_proikos_reg_form, plugin_proikos_reg_form_field,
 * plugin_proikos_reg_form_submission y plugin_proikos_reg_form_answer.
 */
class RegistrationFormManager
{
    const TABLE_FORM = 'plugin_proikos_reg_form';
    const TABLE_FIELD = 'plugin_proikos_reg_form_field';
    const TABLE_SUBMISSION = 'plugin_proikos_reg_form_submission';
    const TABLE_ANSWER = 'plugin_proikos_reg_form_answer';

    const STATUS_DRAFT = 0;
    const STATUS_PUBLISHED = 1;

    const PASSWORD_MANUAL = 'manual';
    const PASSWORD_AUTO = 'auto';
    const PASSWORD_FIELD = 'field';

    const USERNAME_EMAIL = 'email';
    const USERNAME_FIELD = 'field';

    /** Campos principales de Chamilo: siempre presentes, no se pueden eliminar. */
    const CORE_FIELDS = ['firstname', 'lastname', 'email', 'password'];

    /** Tipos de campo que el administrador puede agregar. */
    const FIELD_TYPES = [
        'section' => 'Sección / título',
        'text' => 'Texto corto',
        'textarea' => 'Párrafo',
        'number' => 'Número',
        'email' => 'Correo electrónico',
        'tel' => 'Teléfono',
        'date' => 'Fecha',
        'select' => 'Lista desplegable',
        'radio' => 'Opción única',
        'checkbox' => 'Casillas (selección múltiple)',
        'file' => 'Archivo adjunto',
        'terms' => 'Aceptación de términos',
    ];

    const CORE_FIELD_LABELS = [
        'firstname' => 'Nombres',
        'lastname' => 'Apellidos',
        'email' => 'Correo electrónico',
        'password' => 'Contraseña',
    ];

    const TYPES_WITH_OPTIONS = ['select', 'radio', 'checkbox'];

    /** Validaciones permitidas por tipo (lista blanca usada al guardar). */
    const TYPE_VALIDATIONS = [
        'firstname' => ['min_length', 'max_length', 'pattern', 'regex', 'pattern_message'],
        'lastname' => ['min_length', 'max_length', 'pattern', 'regex', 'pattern_message'],
        'email' => ['allowed_domains', 'unique'],
        'password' => ['password_level', 'min_length', 'require_lower', 'require_upper', 'require_digit', 'require_special', 'hide_strength', 'no_confirm', 'requirements_display'],
        'text' => ['min_length', 'max_length', 'pattern', 'regex', 'pattern_message', 'unique'],
        'textarea' => ['min_length', 'max_length'],
        'number' => ['min', 'max', 'integer', 'unique'],
        'tel' => ['min_length', 'max_length', 'unique'],
        'date' => ['min_date', 'max_date', 'min_age', 'max_age'],
        'select' => [],
        'radio' => [],
        'checkbox' => ['min_selected', 'max_selected'],
        'file' => ['extensions', 'max_size_mb'],
        'terms' => [],
        'section' => [],
    ];

    /** Patrones predefinidos para campos de texto. */
    const PATTERNS = [
        'letters' => [
            'label' => 'Solo letras y espacios',
            'regex' => '/^[\p{L}\s\'\-\.]+$/u',
            'html' => "[A-Za-zÁÉÍÓÚáéíóúÑñÜü\\s'\\-\\.]+",
            'message' => 'Solo se permiten letras y espacios.',
        ],
        'alphanumeric' => [
            'label' => 'Letras y números',
            'regex' => '/^[\p{L}0-9\s]+$/u',
            'html' => '[A-Za-zÁÉÍÓÚáéíóúÑñÜü0-9\\s]+',
            'message' => 'Solo se permiten letras y números.',
        ],
        'digits' => [
            'label' => 'Solo dígitos',
            'regex' => '/^[0-9]+$/',
            'html' => '[0-9]+',
            'message' => 'Solo se permiten dígitos.',
        ],
        'dni' => [
            'label' => 'DNI (8 dígitos)',
            'regex' => '/^[0-9]{8}$/',
            'html' => '[0-9]{8}',
            'message' => 'El DNI debe tener 8 dígitos.',
        ],
        'ruc' => [
            'label' => 'RUC (11 dígitos)',
            'regex' => '/^(10|15|17|20)[0-9]{9}$/',
            'html' => '(10|15|17|20)[0-9]{9}',
            'message' => 'El RUC debe tener 11 dígitos y empezar con 10, 15, 17 o 20.',
        ],
        'custom' => [
            'label' => 'Expresión regular personalizada',
        ],
    ];

    /**
     * Niveles de contraseña: mínimo de caracteres de cada tipo (mismo formato que
     * password_requirements de Chamilo). "chamilo" toma la configuración de la
     * plataforma y "custom" los requisitos que marque el administrador.
     */
    const PASSWORD_LEVELS = [
        'chamilo' => ['label' => 'Reglas de la plataforma (Chamilo)', 'min' => null],
        'basic' => ['label' => 'Básico', 'min' => ['length' => 6, 'lowercase' => 0, 'uppercase' => 0, 'numeric' => 0, 'specials' => 0]],
        'medium' => ['label' => 'Medio', 'min' => ['length' => 8, 'lowercase' => 1, 'uppercase' => 0, 'numeric' => 1, 'specials' => 0]],
        'strong' => ['label' => 'Fuerte', 'min' => ['length' => 8, 'lowercase' => 1, 'uppercase' => 1, 'numeric' => 1, 'specials' => 1]],
        'custom' => ['label' => 'Personalizado', 'min' => null],
    ];

    /** Cómo se muestra la lista de requisitos de la contraseña ("inline" por defecto). */
    const PASSWORD_REQUIREMENTS_DISPLAY = [
        'inline' => 'Visibles debajo del campo',
        'popup' => 'En una ventana emergente al escribir la contraseña',
        'hidden' => 'Ocultos (solo se avisa al enviar el formulario)',
    ];

    /** Clases de caracteres de la contraseña (las mismas expresiones se usan en el JS). */
    const PASSWORD_CHAR_CLASSES = [
        'lowercase' => '/\p{Ll}/u',
        'uppercase' => '/\p{Lu}/u',
        'numeric' => '/[0-9]/',
        'specials' => '/[^\p{L}\p{N}\s]/u',
    ];

    /**
     * Columnas a las que se puede vincular un campo personalizado.
     * "user.*" se guarda en la tabla user de Chamilo, "pk.*" en plugin_proikos_users.
     */
    const MAP_TARGETS = [
        'user.username' => 'Chamilo: nombre de usuario (login)',
        'user.official_code' => 'Chamilo: código oficial',
        'user.phone' => 'Chamilo: teléfono',
        'user.language' => 'Chamilo: idioma de la plataforma (valor: spanish, english…)',
        'pk.type_document' => 'Proikos: tipo de documento (valor numérico)',
        'pk.number_document' => 'Proikos: número de documento',
        'pk.age' => 'Proikos: edad (valor numérico)',
        'pk.gender' => 'Proikos: género (valor M / F)',
        'pk.instruction' => 'Proikos: grado de instrucción',
        'pk.stakeholders' => 'Proikos: tipo de participante',
        'pk.position_company' => 'Proikos: puesto',
        'pk.name_company' => 'Proikos: empresa',
        'pk.ruc_company' => 'Proikos: RUC de empresa',
    ];

    const MAPPABLE_TYPES = ['text', 'number', 'tel', 'email', 'select', 'radio', 'date'];

    /**
     * Campos predefinidos: se agregan con un clic, con sus opciones y ya
     * vinculados a la columna de Proikos (los valores son los de inscription.php
     * para que los reportes los entiendan). Después se pueden editar como cualquier campo.
     */
    const FIELD_PRESETS = [
        'sexo' => [
            'button' => 'Sexo', 'icon' => 'fa-venus-mars',
            'field' => [
                'type' => 'select', 'label' => 'Sexo', 'required' => true,
                'options' => [['value' => 'M', 'label' => 'Masculino'], ['value' => 'F', 'label' => 'Femenino'], ['value' => 'X', 'label' => 'Sin definir']],
                'config' => ['width' => 6, 'map_to' => 'pk.gender'],
            ],
        ],
        'tipo_documento' => [
            'button' => 'Tipo de documento', 'icon' => 'fa-id-card-o',
            'field' => [
                'type' => 'select', 'label' => 'Tipo de documento', 'required' => true,
                'options' => [['value' => '1', 'label' => 'DNI'], ['value' => '2', 'label' => 'Carnet de Extranjería'], ['value' => '3', 'label' => 'Pasaporte'], ['value' => '5', 'label' => 'Otros']],
                'config' => ['width' => 6, 'map_to' => 'pk.type_document'],
            ],
        ],
        'numero_documento' => [
            'button' => 'N° de documento', 'icon' => 'fa-id-card',
            'field' => [
                'type' => 'text', 'label' => 'N° de documento', 'required' => true,
                'validations' => ['pattern' => 'alphanumeric', 'min_length' => 6, 'max_length' => 20, 'unique' => true],
                'config' => ['width' => 6, 'map_to' => 'pk.number_document'],
            ],
        ],
        'celular' => [
            'button' => 'Celular', 'icon' => 'fa-mobile',
            'field' => [
                'type' => 'tel', 'label' => 'Celular', 'required' => true,
                'validations' => ['min_length' => 9, 'max_length' => 15],
                'config' => ['width' => 6, 'map_to' => 'user.phone', 'placeholder' => '999 999 999'],
            ],
        ],
        'edad' => [
            'button' => 'Edad', 'icon' => 'fa-hashtag',
            'field' => [
                'type' => 'number', 'label' => 'Edad', 'required' => false,
                'validations' => ['min' => 14, 'max' => 100, 'integer' => true],
                'config' => ['width' => 4, 'map_to' => 'pk.age'],
            ],
        ],
        'fecha_nacimiento' => [
            'button' => 'Fecha de nacimiento', 'icon' => 'fa-birthday-cake',
            'field' => [
                'type' => 'date', 'label' => 'Fecha de nacimiento', 'required' => false,
                'config' => ['width' => 4],
            ],
        ],
        'grado_instruccion' => [
            'button' => 'Grado de instrucción', 'icon' => 'fa-graduation-cap',
            'field' => [
                'type' => 'select', 'label' => 'Grado de instrucción', 'required' => false,
                'options' => [['value' => '1', 'label' => 'Primaria'], ['value' => '2', 'label' => 'Secundaria'], ['value' => '3', 'label' => 'Técnica superior'], ['value' => '4', 'label' => 'Universitaria Bachiller'], ['value' => '5', 'label' => 'Universitaria Titulada']],
                'config' => ['width' => 6, 'map_to' => 'pk.instruction'],
            ],
        ],
        'empresa' => [
            'button' => 'Empresa', 'icon' => 'fa-building',
            'field' => [
                'type' => 'text', 'label' => 'Empresa', 'required' => false,
                'validations' => ['max_length' => 250],
                'config' => ['width' => 6, 'map_to' => 'pk.name_company'],
            ],
        ],
        'ruc_empresa' => [
            'button' => 'RUC de empresa', 'icon' => 'fa-barcode',
            'field' => [
                'type' => 'text', 'label' => 'RUC de empresa', 'required' => false,
                'validations' => ['pattern' => 'ruc'],
                'config' => ['width' => 6, 'map_to' => 'pk.ruc_company'],
            ],
        ],
        'puesto' => [
            'button' => 'Cargo / puesto', 'icon' => 'fa-briefcase',
            'field' => [
                'type' => 'text', 'label' => 'Cargo / puesto', 'required' => false,
                'validations' => ['max_length' => 250],
                'config' => ['width' => 6, 'map_to' => 'pk.position_company'],
            ],
        ],
        // Ubigeo en cascada: las opciones se leen del JSON (resources/ubigeo) y
        // cada lista se activa al elegir la anterior.
        'region' => [
            'button' => 'Región', 'icon' => 'fa-map',
            'field' => [
                'type' => 'select', 'label' => 'Región', 'required' => true,
                'config' => ['width' => 4, 'options_source' => 'ubigeo_departamentos'],
            ],
        ],
        'provincia' => [
            'button' => 'Provincia', 'icon' => 'fa-map-o',
            'field' => [
                'type' => 'select', 'label' => 'Provincia', 'required' => true,
                'config' => ['width' => 4, 'options_source' => 'ubigeo_provincias', 'parent' => 'region'],
            ],
        ],
        'distrito' => [
            'button' => 'Distrito', 'icon' => 'fa-map-marker',
            'field' => [
                'type' => 'select', 'label' => 'Distrito', 'required' => true,
                'config' => ['width' => 4, 'options_source' => 'ubigeo_distritos', 'parent' => 'provincia'],
            ],
        ],
    ];

    const ALLOWED_FILE_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip'];
    const MAX_FILE_SIZE_MB = 20;

    /** Plantilla vertical: etiqueta, campo, ayuda y error de validación. */
    const ELEMENT_TEMPLATE = '
        <div class="form-group pk-rf-group {error_class}">
            <label {label-for} class="control-label">{label}</label>
            {element}
            <!-- BEGIN label_2 --><div class="help-block pk-rf-help">{label_2}</div><!-- END label_2 -->
            <!-- BEGIN error --><span class="help-inline help-block pk-rf-error">{error}</span><!-- END error -->
        </div>';

    /** @var ProikosPlugin */
    private $plugin;

    /** @var array Etiqueta de cada campo por su clave, para el formulario que se construye. */
    private $labelsByKey = [];

    /**
     * Lista en cascada: el JS deja solo las opciones del grupo que corresponde
     * a lo elegido en el padre, y lo deshabilita mientras el padre esté vacío.
     */
    const CASCADE_JS = <<<'JS'
(function () {
    // Chamilo dibuja las listas con bootstrap-select: tras cambiar la lista
    // nativa (opciones o habilitada) hay que redibujar el componente visible.
    function redibujar(el) {
        var $ = window.jQuery;
        if ($ && $.fn.selectpicker && $(el).data('selectpicker')) {
            $(el).selectpicker('refresh');
        }
    }
    document.querySelectorAll('select[data-pk-parent]:not([data-ready])').forEach(function (sel) {
        sel.setAttribute('data-ready', '1');
        var padre = sel.form && sel.form.querySelector('[name="' + sel.dataset.pkParent + '"]');
        var grupos = JSON.parse(sel.dataset.pkGroups || '{}');
        var vacia = sel.options.length ? sel.options[0].text : 'Seleccione una opción';
        if (!padre) { return; }
        function pintar() {
            var actual = sel.value;
            var valor = padre.disabled ? '' : padre.value;
            var opciones = grupos[valor] || [];
            sel.disabled = !valor;
            sel.innerHTML = '';
            sel.appendChild(new Option(valor ? vacia : 'Elija antes ' + sel.dataset.pkParentLabel, ''));
            opciones.forEach(function (o) { sel.appendChild(new Option(o, o, false, o === actual)); });
            if (opciones.indexOf(actual) === -1) { sel.value = ''; }
            redibujar(sel);
            // Propaga a los niveles de abajo (provincia → distrito).
            sel.dispatchEvent(new Event('change'));
        }
        padre.addEventListener('change', pintar);
        pintar();
    });
})();
JS;

    public function __construct(ProikosPlugin $plugin = null)
    {
        $this->plugin = $plugin ?: ProikosPlugin::create();
    }

    /* ---------------------------------------------------------------------
     * Esquema
     * ------------------------------------------------------------------- */

    public static function createTables()
    {
        Database::query("CREATE TABLE IF NOT EXISTS ".self::TABLE_FORM." (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            settings LONGTEXT NULL,
            status TINYINT NOT NULL DEFAULT 0,
            is_registration TINYINT NOT NULL DEFAULT 0,
            created_by INT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_prf_registration (is_registration)
        ) DEFAULT CHARSET=utf8mb4");

        Database::query("CREATE TABLE IF NOT EXISTS ".self::TABLE_FIELD." (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            form_id INT UNSIGNED NOT NULL,
            field_key VARCHAR(64) NOT NULL,
            type VARCHAR(30) NOT NULL,
            label VARCHAR(255) NOT NULL,
            required TINYINT NOT NULL DEFAULT 0,
            options LONGTEXT NULL,
            validations LONGTEXT NULL,
            config LONGTEXT NULL,
            position INT NOT NULL DEFAULT 0,
            KEY idx_prff_form (form_id, position)
        ) DEFAULT CHARSET=utf8mb4");

        Database::query("CREATE TABLE IF NOT EXISTS ".self::TABLE_SUBMISSION." (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            form_id INT UNSIGNED NOT NULL,
            user_id INT NOT NULL,
            ip VARCHAR(45) NULL,
            created_at DATETIME NULL,
            KEY idx_prfs_form (form_id),
            KEY idx_prfs_user (user_id)
        ) DEFAULT CHARSET=utf8mb4");

        Database::query("CREATE TABLE IF NOT EXISTS ".self::TABLE_ANSWER." (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            submission_id INT UNSIGNED NOT NULL,
            form_id INT UNSIGNED NOT NULL,
            field_id INT UNSIGNED NOT NULL,
            value LONGTEXT NULL,
            KEY idx_prfa_submission (submission_id),
            KEY idx_prfa_field (field_id, value(100))
        ) DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Crea las tablas si aún no existen (instalaciones previas del plugin).
     */
    public static function ensureTables()
    {
        $schemaManager = Database::getManager()->getConnection()->getSchemaManager();
        if (!$schemaManager->tablesExist([self::TABLE_FORM, self::TABLE_FIELD, self::TABLE_SUBMISSION, self::TABLE_ANSWER])) {
            self::createTables();
        }
    }

    /* ---------------------------------------------------------------------
     * Formularios
     * ------------------------------------------------------------------- */

    public static function defaultSettings(): array
    {
        return [
            'password_mode' => self::PASSWORD_MANUAL,
            'password_field' => '',
            'username_mode' => self::USERNAME_EMAIL,
            'username_field' => '',
            'send_credentials_email' => true,
            'auto_login' => true,
            'redirect_url' => '',
            'success_message' => '',
            'submit_label' => 'Registrarme',
            'code_reference' => '',
            'wizard' => false,
        ];
    }

    public function getForms(): array
    {
        $tForm = self::TABLE_FORM;
        $tField = self::TABLE_FIELD;
        $tSubmission = self::TABLE_SUBMISSION;
        $sql = "SELECT f.*,
                    (SELECT COUNT(*) FROM $tField fi WHERE fi.form_id = f.id) AS total_fields,
                    (SELECT COUNT(*) FROM $tSubmission s WHERE s.form_id = f.id) AS total_submissions
                FROM $tForm f
                ORDER BY f.is_registration DESC, f.id DESC";
        $result = Database::query($sql);
        $forms = [];
        while ($row = Database::fetch_assoc($result)) {
            $forms[] = $this->hydrateForm($row);
        }

        return $forms;
    }

    public function getForm($formId)
    {
        $formId = (int) $formId;
        if ($formId <= 0) {
            return null;
        }
        $result = Database::query("SELECT * FROM ".self::TABLE_FORM." WHERE id = $formId");
        $row = Database::fetch_assoc($result);

        return $row ? $this->hydrateForm($row) : null;
    }

    /**
     * Formulario elegido como registro de la plataforma (debe estar publicado).
     */
    public function getRegistrationForm()
    {
        $sql = "SELECT * FROM ".self::TABLE_FORM."
                WHERE is_registration = 1 AND status = ".self::STATUS_PUBLISHED."
                ORDER BY id DESC LIMIT 1";
        $row = Database::fetch_assoc(Database::query($sql));

        return $row ? $this->hydrateForm($row) : null;
    }

    /**
     * Id del formulario de registro activo sin fallar si las tablas aún no existen.
     */
    public static function getActiveRegistrationFormId(): int
    {
        try {
            $sql = "SELECT id FROM ".self::TABLE_FORM."
                    WHERE is_registration = 1 AND status = ".self::STATUS_PUBLISHED."
                    ORDER BY id DESC LIMIT 1";
            $row = Database::fetch_assoc(Database::query($sql));

            return $row ? (int) $row['id'] : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function hydrateForm(array $row): array
    {
        $settings = json_decode($row['settings'] ?? '', true);
        $row['settings'] = array_merge(self::defaultSettings(), is_array($settings) ? $settings : []);
        $row['id'] = (int) $row['id'];
        $row['status'] = (int) $row['status'];
        $row['is_registration'] = (int) $row['is_registration'];

        return $row;
    }

    /**
     * Crea o actualiza la cabecera del formulario. Un formulario nuevo nace vacío:
     * los datos principales de Chamilo se agregan desde el constructor.
     */
    public function saveForm(array $values, $formId = 0): int
    {
        $settings = self::defaultSettings();
        foreach ($settings as $key => $default) {
            if (is_bool($default)) {
                $settings[$key] = !empty($values[$key]);
            } elseif (isset($values[$key])) {
                $settings[$key] = trim((string) $values[$key]);
            }
        }
        if (!in_array($settings['password_mode'], [self::PASSWORD_MANUAL, self::PASSWORD_AUTO, self::PASSWORD_FIELD], true)) {
            $settings['password_mode'] = self::PASSWORD_MANUAL;
        }
        if (!in_array($settings['username_mode'], [self::USERNAME_EMAIL, self::USERNAME_FIELD], true)) {
            $settings['username_mode'] = self::USERNAME_EMAIL;
        }
        if (self::PASSWORD_AUTO === $settings['password_mode']) {
            // El usuario no conoce su contraseña: hay que enviársela.
            $settings['send_credentials_email'] = true;
        }
        if ('' === $settings['submit_label']) {
            $settings['submit_label'] = 'Registrarme';
        }

        $params = [
            'title' => trim($values['title']),
            'description' => trim($values['description'] ?? ''),
            'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE),
            'status' => empty($values['status']) ? self::STATUS_DRAFT : self::STATUS_PUBLISHED,
            'updated_at' => api_get_utc_datetime(),
        ];

        $formId = (int) $formId;
        if ($formId > 0) {
            Database::update(self::TABLE_FORM, $params, ['id = ?' => $formId]);
            if (self::STATUS_DRAFT === $params['status']) {
                // Un borrador no puede seguir siendo el formulario de registro.
                Database::update(self::TABLE_FORM, ['is_registration' => 0], ['id = ?' => $formId]);
            }

            return $formId;
        }

        $params['created_by'] = api_get_user_id();
        $params['created_at'] = api_get_utc_datetime();
        $params['is_registration'] = 0;
        // Sin campos todavía no se puede publicar.
        $params['status'] = self::STATUS_DRAFT;

        return (int) Database::insert(self::TABLE_FORM, $params);
    }

    public function deleteForm($formId)
    {
        $formId = (int) $formId;
        Database::delete(self::TABLE_ANSWER, ['form_id = ?' => $formId]);
        Database::delete(self::TABLE_SUBMISSION, ['form_id = ?' => $formId]);
        Database::delete(self::TABLE_FIELD, ['form_id = ?' => $formId]);
        Database::delete(self::TABLE_FORM, ['id = ?' => $formId]);
    }

    public function duplicateForm($formId): int
    {
        $form = $this->getForm($formId);
        if (!$form) {
            return 0;
        }
        $newId = (int) Database::insert(self::TABLE_FORM, [
            'title' => $form['title'].' (copia)',
            'description' => $form['description'],
            'settings' => json_encode($form['settings'], JSON_UNESCAPED_UNICODE),
            'status' => self::STATUS_DRAFT,
            'is_registration' => 0,
            'created_by' => api_get_user_id(),
            'created_at' => api_get_utc_datetime(),
            'updated_at' => api_get_utc_datetime(),
        ]);
        $result = Database::query("SELECT * FROM ".self::TABLE_FIELD." WHERE form_id = ".(int) $formId);
        while ($row = Database::fetch_assoc($result)) {
            unset($row['id']);
            $row['form_id'] = $newId;
            Database::insert(self::TABLE_FIELD, $row);
        }

        return $newId;
    }

    /**
     * Campos del formulario predeterminado: datos personales, domicilio, salud,
     * documentos y acceso. Usa los campos predefinidos (FIELD_PRESETS) para que
     * queden vinculados a las columnas de Proikos; el administrador puede editarlo
     * como cualquier otro formulario.
     */
    public static function defaultTemplate(): array
    {
        $preset = function ($key, array $override = []) {
            return array_replace_recursive(['field_key' => $key], self::FIELD_PRESETS[$key]['field'], $override);
        };
        $yesNo = [['value' => 'si', 'label' => 'Sí'], ['value' => 'no', 'label' => 'No']];
        $documents = ['extensions' => 'pdf,jpg,jpeg,png', 'max_size_mb' => 5];

        // Idiomas activos de la plataforma, con el idioma por defecto primero.
        $languages = api_get_languages();
        $platformLanguage = api_get_setting('platformLanguage');
        $languageOptions = [];
        foreach ($languages['folder'] as $index => $folder) {
            // Chamilo guarda los nombres con entidades HTML (Espa&ntilde;ol).
            $option = ['value' => $folder, 'label' => html_entity_decode($languages['name'][$index], ENT_QUOTES, 'UTF-8')];
            if ($folder === $platformLanguage) {
                array_unshift($languageOptions, $option);
            } else {
                $languageOptions[] = $option;
            }
        }

        return [
            ['type' => 'section', 'label' => 'Datos personales'],
            ['type' => 'lastname', 'label' => 'Apellidos', 'validations' => ['pattern' => 'letters', 'max_length' => 60], 'config' => ['width' => 6]],
            ['type' => 'firstname', 'label' => 'Nombres', 'validations' => ['pattern' => 'letters', 'max_length' => 60], 'config' => ['width' => 6]],
            [
                'type' => 'select', 'field_key' => 'idioma', 'label' => 'Idioma', 'required' => true,
                'options' => $languageOptions,
                'config' => ['width' => 6, 'map_to' => 'user.language', 'help' => 'Idioma en el que verá la plataforma.'],
            ],
            ['type' => 'email', 'label' => 'Correo electrónico', 'config' => ['width' => 6]],
            $preset('celular'),
            $preset('tipo_documento'),
            $preset('numero_documento'),
            $preset('sexo'),
            $preset('grado_instruccion'),

            ['type' => 'section', 'label' => 'Domicilio'],
            [
                'type' => 'text', 'field_key' => 'direccion', 'label' => 'Dirección', 'required' => true,
                'validations' => ['max_length' => 250],
                'config' => ['width' => 12, 'placeholder' => 'Av. / Jr. / Calle, número, urbanización'],
            ],
            $preset('region'),
            $preset('provincia'),
            $preset('distrito'),
            [
                'type' => 'text', 'field_key' => 'referencia', 'label' => 'Referencia', 'required' => false,
                'validations' => ['max_length' => 250],
                'config' => ['width' => 12, 'placeholder' => 'Ej.: frente al parque, a media cuadra del mercado'],
            ],

            ['type' => 'section', 'label' => 'Datos de salud', 'config' => ['help' => 'Esta información solo se usa en caso de emergencia.']],
            [
                'type' => 'select', 'field_key' => 'tipo_sangre', 'label' => 'Tipo de sangre', 'required' => true,
                'options' => array_map(function ($type) {
                    return ['value' => $type, 'label' => $type];
                }, ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'No sé']),
                'config' => ['width' => 6],
            ],
            [
                'type' => 'radio', 'field_key' => 'seguro', 'label' => 'Seguro de salud', 'required' => true,
                'options' => [
                    ['value' => 'essalud', 'label' => 'EsSalud'],
                    ['value' => 'particular', 'label' => 'Particular (EPS / seguro privado)'],
                    ['value' => 'sis', 'label' => 'SIS'],
                    ['value' => 'ninguno', 'label' => 'No tengo seguro'],
                ],
                'config' => ['width' => 6],
            ],
            [
                'type' => 'radio', 'field_key' => 'tiene_alergias', 'label' => '¿Tiene alguna alergia?', 'required' => true,
                'options' => $yesNo,
                'config' => ['width' => 6],
            ],
            [
                'type' => 'textarea', 'field_key' => 'alergias', 'label' => 'Indique sus alergias', 'required' => true,
                'validations' => ['max_length' => 1000],
                'config' => ['width' => 12, 'placeholder' => 'Medicamentos, alimentos, otros', 'show_if' => ['field' => 'tiene_alergias', 'values' => ['si']]],
            ],
            [
                'type' => 'radio', 'field_key' => 'discapacidad', 'label' => '¿Tiene alguna discapacidad?', 'required' => true,
                'options' => $yesNo,
                'config' => ['width' => 6],
            ],
            [
                'type' => 'file', 'field_key' => 'carnet_conadis', 'label' => 'Carnet de CONADIS', 'required' => true,
                'validations' => $documents,
                'config' => ['width' => 12, 'help' => 'PDF o imagen, máximo 5 MB.', 'show_if' => ['field' => 'discapacidad', 'values' => ['si']]],
            ],

            ['type' => 'section', 'label' => 'Documentos'],
            [
                'type' => 'file', 'field_key' => 'dni_escaneado', 'label' => 'DNI escaneado (ambas caras)', 'required' => true,
                'validations' => $documents,
                'config' => ['width' => 12, 'help' => 'PDF o imagen, máximo 5 MB.'],
            ],

            ['type' => 'section', 'label' => 'Datos de acceso'],
            ['type' => 'password', 'label' => 'Contraseña', 'validations' => ['password_level' => 'medium'], 'config' => ['width' => 12]],

            [
                'type' => 'terms', 'field_key' => 'terminos',
                'label' => 'He leído y acepto los términos y condiciones, y autorizo el tratamiento de mis datos personales, incluidos mis datos de salud y discapacidad.',
                'config' => ['width' => 12, 'terms_mode' => 'modal', 'link_text' => 'Leer términos y condiciones', 'terms_text' => self::DEFAULT_TERMS_TEXT],
            ],
        ];
    }

    /** Texto de ejemplo de los términos del formulario predeterminado (los datos entre corchetes se reemplazan). */
    const DEFAULT_TERMS_TEXT = <<<'HTML'
<h4>1. Objeto</h4>
<p>Estos términos regulan el registro y el uso de la plataforma de capacitación virtual de <strong>[NOMBRE DE LA EMPRESA]</strong>, con RUC <strong>[RUC]</strong> y domicilio en <strong>[DIRECCIÓN]</strong> (en adelante, «la Empresa»). Al registrarse, usted acepta estos términos.</p>

<h4>2. Registro y cuenta de usuario</h4>
<ul>
    <li>Los datos que registra deben ser verdaderos, completos y actualizados. La Empresa puede anular la inscripción si comprueba que son falsos.</li>
    <li>Su usuario y contraseña son personales e intransferibles. Usted es responsable de su custodia y de toda actividad realizada con su cuenta.</li>
    <li>Si sospecha que alguien más usa su cuenta, cambie su contraseña y avísenos a <strong>[CORREO DE CONTACTO]</strong>.</li>
</ul>

<h4>3. Uso de la plataforma</h4>
<ul>
    <li>La plataforma se usa solo con fines de capacitación. No está permitido compartir la cuenta, rendir evaluaciones en nombre de otra persona ni intentar acceder a información de otros usuarios.</li>
    <li>Los cursos, videos, documentos y evaluaciones son propiedad de la Empresa o de sus autores. No pueden copiarse, distribuirse ni publicarse sin autorización.</li>
    <li>Los certificados se emiten solo a quienes cumplen los requisitos de aprobación de cada curso.</li>
</ul>

<h4>4. Protección de datos personales</h4>
<p>En cumplimiento de la Ley N.° 29733, Ley de Protección de Datos Personales, y su Reglamento, le informamos lo siguiente:</p>
<ul>
    <li><strong>Responsable:</strong> la Empresa, con los datos indicados en el punto 1. Sus datos se almacenan en el banco de datos <strong>[NOMBRE DEL BANCO DE DATOS]</strong>, inscrito ante la Autoridad Nacional de Protección de Datos Personales con código <strong>[CÓDIGO DE INSCRIPCIÓN]</strong>.</li>
    <li><strong>Datos que recogemos:</strong> nombres y apellidos, documento de identidad y su copia, correo, celular, sexo, grado de instrucción, idioma, domicilio y, de forma opcional según sus respuestas, datos de salud (tipo de sangre, seguro y alergias) y la condición de discapacidad con su carnet de CONADIS.</li>
    <li><strong>Finalidades:</strong> gestionar su inscripción y su cuenta, dictar los cursos, registrar su avance y sus notas, emitir y validar certificados, comunicarnos con usted, atenderlo en caso de emergencia y ofrecerle los ajustes razonables que su condición requiera.</li>
    <li><strong>Datos sensibles:</strong> sus datos de salud y de discapacidad son datos sensibles. Solo se tratan con su consentimiento expreso, que otorga al marcar la casilla de aceptación, y solo para atención en emergencias y ajustes razonables. No se usan para ninguna otra finalidad.</li>
    <li><strong>Conservación:</strong> conservamos sus datos mientras mantenga su cuenta y, después, durante los plazos que exijan las normas aplicables, por ejemplo para validar los certificados emitidos.</li>
    <li><strong>Transferencias:</strong> no vendemos ni cedemos sus datos. Solo los compartimos con los proveedores que nos prestan servicios (por ejemplo, el alojamiento de la plataforma), bajo obligación de confidencialidad, o cuando una autoridad lo requiera conforme a ley.</li>
    <li><strong>Sus derechos:</strong> puede ejercer en cualquier momento sus derechos de acceso, rectificación, cancelación y oposición, así como revocar su consentimiento, escribiendo a <strong>[CORREO DE PROTECCIÓN DE DATOS]</strong> con una copia de su documento de identidad. Si considera que no atendimos su solicitud, puede acudir a la Autoridad Nacional de Protección de Datos Personales.</li>
</ul>

<h4>5. Cambios en estos términos</h4>
<p>La Empresa puede actualizar estos términos. Los cambios se publicarán en la plataforma y, si son importantes, se le avisará por correo electrónico.</p>

<h4>6. Aceptación</h4>
<p>Al marcar la casilla de aceptación y completar su registro, usted declara haber leído estos términos, aceptarlos y autorizar el tratamiento de sus datos personales, incluidos los datos sensibles, para las finalidades descritas.</p>
HTML;

    /**
     * Crea un borrador con los campos de defaultTemplate().
     *
     * @return int Id del formulario creado (0 si la plantilla no es válida)
     */
    public function createDefaultForm(): int
    {
        [$normalized, $errors] = $this->normalizeFieldsDefinition(self::defaultTemplate(), []);
        if (!empty($errors)) {
            error_log('Proikos: plantilla de inscripción no válida: '.implode(' ', $errors));

            return 0;
        }
        $formId = $this->saveForm([
            'title' => 'Formulario de inscripción (predeterminado)',
            'description' => 'Complete sus datos para crear su cuenta.',
        ]);
        $this->saveFields($formId, $normalized);

        return $formId;
    }

    /**
     * Marca el formulario como registro de la plataforma (solo uno a la vez).
     */
    public function setRegistrationForm($formId): bool
    {
        $form = $this->getForm($formId);
        if (!$form || self::STATUS_PUBLISHED !== $form['status']
            || !empty($this->checkFormConsistency($form, $this->getFields($form['id'])))) {
            return false;
        }
        Database::query("UPDATE ".self::TABLE_FORM." SET is_registration = 0");
        Database::update(self::TABLE_FORM, ['is_registration' => 1], ['id = ?' => $form['id']]);

        return true;
    }

    public function unsetRegistrationForm()
    {
        Database::query("UPDATE ".self::TABLE_FORM." SET is_registration = 0");
    }

    public function setStatus($formId, $status)
    {
        $status = $status ? self::STATUS_PUBLISHED : self::STATUS_DRAFT;
        $params = ['status' => $status, 'updated_at' => api_get_utc_datetime()];
        if (self::STATUS_DRAFT === $status) {
            $params['is_registration'] = 0;
        }
        Database::update(self::TABLE_FORM, $params, ['id = ?' => (int) $formId]);
    }

    /* ---------------------------------------------------------------------
     * Campos
     * ------------------------------------------------------------------- */

    public function getFields($formId): array
    {
        $formId = (int) $formId;
        $result = Database::query("SELECT * FROM ".self::TABLE_FIELD." WHERE form_id = $formId ORDER BY position, id");
        $fields = [];
        while ($row = Database::fetch_assoc($result)) {
            $fields[] = $this->hydrateField($row);
        }

        return $fields;
    }

    private function hydrateField(array $row): array
    {
        foreach (['options', 'validations', 'config'] as $jsonColumn) {
            $decoded = json_decode($row[$jsonColumn] ?? '', true);
            $row[$jsonColumn] = is_array($decoded) ? $decoded : [];
        }
        $row['id'] = (int) $row['id'];
        $row['required'] = (bool) $row['required'];
        $row['position'] = (int) $row['position'];
        $row['is_core'] = in_array($row['type'], self::CORE_FIELDS, true);
        $row['config'] = array_merge(['width' => 12, 'help' => '', 'placeholder' => '', 'map_to' => '', 'options_source' => '', 'parent' => ''], $row['config']);
        if ('' !== $row['config']['options_source']) {
            // Lista del Ubigeo: las opciones vienen del JSON, no de la base.
            $row['options'] = array_map(function ($name) {
                return ['value' => $name, 'label' => $name];
            }, UbigeoPeru::flatOptions($row['config']['options_source']));
        }

        return $row;
    }

    public function countSubmissions($formId): int
    {
        $row = Database::fetch_assoc(Database::query(
            "SELECT COUNT(*) AS total FROM ".self::TABLE_SUBMISSION." WHERE form_id = ".(int) $formId
        ));

        return (int) $row['total'];
    }

    /**
     * Normaliza y valida la definición de campos enviada por el constructor.
     *
     * @return array [campos normalizados, errores]
     */
    public function normalizeFieldsDefinition(array $input, array $currentFields): array
    {
        $errors = [];
        $currentById = [];
        foreach ($currentFields as $field) {
            $currentById[$field['id']] = $field;
        }

        $normalized = [];
        $usedKeys = [];
        $usedMaps = [];
        $coreSeen = [];

        foreach (array_values($input) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $number = $index + 1;
            $id = (int) ($item['id'] ?? 0);
            if ($id > 0 && !isset($currentById[$id])) {
                $id = 0;
            }

            $type = (string) ($item['type'] ?? '');
            // El tipo de un campo principal no se puede cambiar.
            if ($id > 0 && $currentById[$id]['is_core']) {
                $type = $currentById[$id]['type'];
            }
            $isCore = in_array($type, self::CORE_FIELDS, true);
            if (!$isCore && !isset(self::FIELD_TYPES[$type])) {
                $errors[] = "Campo #$number: tipo no válido.";
                continue;
            }
            if ($isCore) {
                if (isset($coreSeen[$type])) {
                    continue;
                }
                $coreSeen[$type] = true;
            }

            $label = trim(strip_tags((string) ($item['label'] ?? '')));
            if ('' === $label) {
                $errors[] = "Campo #$number: la pregunta / etiqueta es obligatoria.";
            }
            $label = self::cut($label, 0, 255);

            if ($isCore) {
                $key = $type;
            } else {
                $key = self::slugify((string) ($item['field_key'] ?? ''));
                if ('' === $key) {
                    $key = self::slugify($label) ?: 'campo';
                }
                if (in_array($key, self::CORE_FIELDS, true) || 'password_confirm' === $key) {
                    $key .= '_extra';
                }
                $baseKey = $key;
                $suffix = 2;
                while (isset($usedKeys[$key])) {
                    $key = $baseKey.'_'.$suffix++;
                }
            }
            $usedKeys[$key] = true;

            // Opciones (una lista del Ubigeo no las guarda: se leen del JSON)
            $rawConfig = (array) ($item['config'] ?? []);
            $optionsSource = (string) ($rawConfig['options_source'] ?? '');
            if ('select' !== $type || !isset(UbigeoPeru::SOURCES[$optionsSource])) {
                $optionsSource = '';
            }
            $options = [];
            if (in_array($type, self::TYPES_WITH_OPTIONS, true) && '' === $optionsSource) {
                $seenValues = [];
                foreach ((array) ($item['options'] ?? []) as $option) {
                    $optLabel = trim(strip_tags((string) ($option['label'] ?? '')));
                    $optValue = trim(strip_tags((string) ($option['value'] ?? '')));
                    if ('' === $optLabel && '' === $optValue) {
                        continue;
                    }
                    if ('' === $optValue) {
                        $optValue = $optLabel;
                    }
                    if ('' === $optLabel) {
                        $optLabel = $optValue;
                    }
                    if (isset($seenValues[$optValue])) {
                        $errors[] = "Campo \"$label\": la opción con valor \"$optValue\" está repetida.";
                        continue;
                    }
                    $seenValues[$optValue] = true;
                    $options[] = ['value' => self::cut($optValue, 0, 250), 'label' => self::cut($optLabel, 0, 250)];
                }
                if (empty($options)) {
                    $errors[] = "Campo \"$label\": debe tener al menos una opción.";
                }
            }

            // Validaciones (lista blanca según el tipo)
            $validations = [];
            $rawValidations = (array) ($item['validations'] ?? []);
            foreach (self::TYPE_VALIDATIONS[$type] as $rule) {
                if (!array_key_exists($rule, $rawValidations)) {
                    continue;
                }
                $value = $rawValidations[$rule];
                if (in_array($rule, ['unique', 'integer', 'require_lower', 'require_upper', 'require_digit', 'require_special', 'hide_strength', 'no_confirm'], true)) {
                    if (!empty($value)) {
                        $validations[$rule] = true;
                    }
                    continue;
                }
                $value = trim((string) $value);
                if ('' === $value) {
                    continue;
                }
                switch ($rule) {
                    case 'min_length':
                    case 'max_length':
                    case 'min_selected':
                    case 'max_selected':
                    case 'min_age':
                    case 'max_age':
                        if (!ctype_digit($value)) {
                            $errors[] = "Campo \"$label\": la validación $rule debe ser un número entero.";
                            continue 2;
                        }
                        $validations[$rule] = (int) $value;
                        break;
                    case 'min':
                    case 'max':
                    case 'max_size_mb':
                        if (!is_numeric($value)) {
                            $errors[] = "Campo \"$label\": la validación $rule debe ser numérica.";
                            continue 2;
                        }
                        $validations[$rule] = $value + 0;
                        break;
                    case 'min_date':
                    case 'max_date':
                        if (!self::isValidDate($value)) {
                            $errors[] = "Campo \"$label\": fecha no válida en $rule.";
                            continue 2;
                        }
                        $validations[$rule] = $value;
                        break;
                    case 'pattern':
                        if (!isset(self::PATTERNS[$value])) {
                            continue 2;
                        }
                        $validations[$rule] = $value;
                        break;
                    case 'password_level':
                        if (!isset(self::PASSWORD_LEVELS[$value])) {
                            continue 2;
                        }
                        $validations[$rule] = $value;
                        break;
                    case 'requirements_display':
                        if (!isset(self::PASSWORD_REQUIREMENTS_DISPLAY[$value])) {
                            continue 2;
                        }
                        $validations[$rule] = $value;
                        break;
                    case 'regex':
                        $validations[$rule] = self::cut($value, 0, 500);
                        break;
                    case 'extensions':
                        $extensions = array_filter(array_map(function ($ext) {
                            return strtolower(trim($ext, " .\t\n\r"));
                        }, explode(',', $value)));
                        $extensions = array_values(array_intersect($extensions, self::ALLOWED_FILE_EXTENSIONS));
                        if (!empty($extensions)) {
                            $validations[$rule] = implode(',', $extensions);
                        }
                        break;
                    case 'allowed_domains':
                        $domains = array_filter(array_map(function ($domain) {
                            return strtolower(trim($domain, " @\t\n\r"));
                        }, explode(',', $value)));
                        if (!empty($domains)) {
                            $validations[$rule] = implode(',', $domains);
                        }
                        break;
                    default:
                        $validations[$rule] = self::cut(strip_tags($value), 0, 255);
                }
            }

            if (isset($validations['pattern']) && 'custom' === $validations['pattern']) {
                if (empty($validations['regex'])) {
                    $errors[] = "Campo \"$label\": indique la expresión regular personalizada.";
                } elseif (false === @preg_match(self::buildRegex($validations['regex']), '')) {
                    $errors[] = "Campo \"$label\": la expresión regular no es válida.";
                }
            } else {
                unset($validations['regex']);
            }
            foreach ([['min_length', 'max_length'], ['min', 'max'], ['min_selected', 'max_selected'], ['min_age', 'max_age'], ['min_date', 'max_date']] as [$min, $max]) {
                if (isset($validations[$min], $validations[$max]) && $validations[$min] > $validations[$max]) {
                    $errors[] = "Campo \"$label\": el mínimo ($min) no puede ser mayor que el máximo ($max).";
                }
            }
            if ('password' === $type) {
                // Los requisitos sueltos solo cuentan en el nivel personalizado;
                // los demás niveles los definen PASSWORD_LEVELS o Chamilo.
                if ('custom' === ($validations['password_level'] ?? '')) {
                    $validations['min_length'] = min(64, max(4, $validations['min_length'] ?? 8));
                } else {
                    unset($validations['min_length'], $validations['require_lower'], $validations['require_upper'],
                        $validations['require_digit'], $validations['require_special']);
                }
            }
            if ('file' === $type && isset($validations['max_size_mb']) && $validations['max_size_mb'] > self::MAX_FILE_SIZE_MB) {
                $validations['max_size_mb'] = self::MAX_FILE_SIZE_MB;
            }

            // Configuración visual y vínculo con columnas
            $width = (int) ($rawConfig['width'] ?? 12);
            $config = [
                'width' => in_array($width, [12, 6, 4], true) ? $width : 12,
                'help' => self::cut(trim(Security::remove_XSS((string) ($rawConfig['help'] ?? ''))), 0, 2000),
                'placeholder' => self::cut(trim(strip_tags((string) ($rawConfig['placeholder'] ?? ''))), 0, 255),
                'map_to' => '',
            ];
            if ('' !== $optionsSource) {
                $config['options_source'] = $optionsSource;
                // Cascada: clave del campo del que depende (se comprueba al final).
                $config['parent'] = self::slugify((string) ($rawConfig['parent'] ?? ''));
            }
            $mapTo = (string) ($rawConfig['map_to'] ?? '');
            if ('' !== $mapTo && isset(self::MAP_TARGETS[$mapTo]) && in_array($type, self::MAPPABLE_TYPES, true)) {
                if (isset($usedMaps[$mapTo])) {
                    $errors[] = "Campo \"$label\": la columna \"".self::MAP_TARGETS[$mapTo]."\" ya está vinculada a otro campo.";
                } else {
                    $usedMaps[$mapTo] = true;
                    $config['map_to'] = $mapTo;
                }
            }
            // Mostrar solo si otro campo tiene ciertas opciones marcadas (se comprueba al final).
            $showIf = (array) ($rawConfig['show_if'] ?? []);
            $showField = self::slugify((string) ($showIf['field'] ?? ''));
            if (!$isCore && 'section' !== $type && '' !== $showField) {
                $showValues = [];
                foreach ((array) ($showIf['values'] ?? []) as $showValue) {
                    $showValue = self::cut(trim(strip_tags((string) $showValue)), 0, 250);
                    if ('' !== $showValue && !in_array($showValue, $showValues, true)) {
                        $showValues[] = $showValue;
                    }
                }
                $config['show_if'] = ['field' => $showField, 'values' => $showValues];
            }
            if ('terms' === $type) {
                // El documento se abre en una URL o se lee en una ventana emergente.
                $mode = (string) ($rawConfig['terms_mode'] ?? '');
                $url = trim((string) ($rawConfig['url'] ?? ''));
                $termsText = trim(Security::remove_XSS((string) ($rawConfig['terms_text'] ?? '')));
                if (!in_array($mode, ['none', 'url', 'modal'], true)) {
                    $mode = '' !== $url ? 'url' : 'none';
                }
                $config['terms_mode'] = $mode;
                $config['url'] = 'url' === $mode && filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
                $config['terms_text'] = 'modal' === $mode ? self::cut($termsText, 0, 60000) : '';
                $config['link_text'] = self::cut(trim(strip_tags((string) ($rawConfig['link_text'] ?? ''))), 0, 255);
                if ('url' === $mode && '' === $config['url']) {
                    $errors[] = "Campo \"$label\": indique una URL válida (https://…) para el documento.";
                }
                if ('modal' === $mode && '' === trim(strip_tags($config['terms_text']))) {
                    $errors[] = "Campo \"$label\": escriba el texto de los términos que se mostrará en la ventana.";
                }
            }

            $required = 'section' !== $type && ($isCore || 'terms' === $type || !empty($item['required']));

            $normalized[] = [
                'id' => $id,
                'field_key' => $key,
                'type' => $type,
                'label' => $label,
                'required' => $required,
                'options' => $options,
                'validations' => $validations,
                'config' => $config,
                'is_core' => $isCore,
            ];
        }

        // Visibilidad condicional: el campo que decide tiene que ser una opción
        // única, lista o casillas de este formulario, con opciones existentes y sin ciclos.
        $byKey = array_column($normalized, null, 'field_key');
        foreach ($normalized as $field) {
            if (empty($field['config']['show_if'])) {
                continue;
            }
            $condition = $field['config']['show_if'];
            $parent = $byKey[$condition['field']] ?? null;
            if (!$parent || $parent['field_key'] === $field['field_key']
                || !in_array($parent['type'], ['radio', 'select', 'checkbox'], true) || empty($parent['options'])) {
                $errors[] = "Campo \"{$field['label']}\": se muestra según \"{$condition['field']}\", que no es una opción única, lista o casillas de este formulario.";
                continue;
            }
            if (empty($condition['values']) || array_diff($condition['values'], array_column($parent['options'], 'value'))) {
                $errors[] = "Campo \"{$field['label']}\": marque qué opciones de \"{$parent['label']}\" lo muestran.";
                continue;
            }
            for ($key = $condition['field'], $steps = 0; '' !== $key && isset($byKey[$key]) && $steps < 50; $steps++) {
                if ($key === $field['field_key']) {
                    $errors[] = "Campo \"{$field['label']}\": las condiciones forman un ciclo (un campo depende de otro que a su vez depende de él).";
                    break;
                }
                $key = $byKey[$key]['config']['show_if']['field'] ?? '';
            }
        }

        // Cascada del Ubigeo: el padre tiene que ser otra lista del formulario.
        $selectKeys = [];
        foreach ($normalized as $field) {
            if ('select' === $field['type']) {
                $selectKeys[$field['field_key']] = true;
            }
        }
        foreach ($normalized as $field) {
            $parent = $field['config']['parent'] ?? '';
            if ('' !== $parent && (!isset($selectKeys[$parent]) || $parent === $field['field_key'])) {
                $errors[] = "Campo \"{$field['label']}\": depende de \"$parent\", que no es otra lista desplegable de este formulario.";
            }
        }

        // Los datos principales que falten no impiden guardar (el formulario se
        // arma por partes); checkFormConsistency() impide publicarlo sin ellos.
        return [$normalized, $errors];
    }

    /**
     * Guarda la definición completa de campos: actualiza, inserta y elimina.
     */
    public function saveFields($formId, array $normalized)
    {
        $formId = (int) $formId;
        $keepIds = [];
        foreach ($normalized as $position => $field) {
            $params = [
                'form_id' => $formId,
                'field_key' => $field['field_key'],
                'type' => $field['type'],
                'label' => $field['label'],
                'required' => $field['required'] ? 1 : 0,
                'options' => json_encode($field['options'], JSON_UNESCAPED_UNICODE),
                'validations' => json_encode($field['validations'], JSON_UNESCAPED_UNICODE),
                'config' => json_encode($field['config'], JSON_UNESCAPED_UNICODE),
                'position' => $position,
            ];
            if ($field['id'] > 0) {
                Database::update(self::TABLE_FIELD, $params, ['id = ? AND form_id = ?' => [$field['id'], $formId]]);
                $keepIds[] = $field['id'];
            } else {
                $keepIds[] = (int) Database::insert(self::TABLE_FIELD, $params);
            }
        }

        $keepIds = array_filter(array_map('intval', $keepIds));
        $notIn = empty($keepIds) ? '' : ' AND id NOT IN ('.implode(',', $keepIds).')';
        $result = Database::query("SELECT id FROM ".self::TABLE_FIELD." WHERE form_id = $formId $notIn");
        while ($row = Database::fetch_assoc($result)) {
            Database::delete(self::TABLE_ANSWER, ['field_id = ?' => (int) $row['id']]);
            Database::delete(self::TABLE_FIELD, ['id = ?' => (int) $row['id']]);
        }

        Database::update(self::TABLE_FORM, ['updated_at' => api_get_utc_datetime()], ['id = ?' => $formId]);
    }

    /**
     * Comprueba que la configuración del formulario sea coherente con sus campos.
     *
     * @return string[] Advertencias
     */
    public function checkFormConsistency(array $form, array $fields): array
    {
        $warnings = [];
        $settings = $form['settings'];

        // Datos que Chamilo necesita para crear la cuenta (la contraseña solo si
        // la escribe el propio usuario).
        $present = array_column($fields, 'type');
        $missing = [];
        foreach (self::CORE_FIELDS as $coreKey) {
            if ('password' === $coreKey && self::PASSWORD_MANUAL !== $settings['password_mode']) {
                continue;
            }
            if (!in_array($coreKey, $present, true)) {
                $missing[] = self::CORE_FIELD_LABELS[$coreKey];
            }
        }
        if (!empty($missing)) {
            $warnings[] = 'Faltan datos principales para crear la cuenta: '.implode(', ', $missing)
                .'. Agréguelos desde "Datos principales" en el constructor.';
        }

        if (self::PASSWORD_FIELD === $settings['password_mode']) {
            $found = false;
            foreach ($fields as $field) {
                if ($field['field_key'] === $settings['password_field'] && !$field['is_core'] && 'section' !== $field['type']) {
                    $found = $field['required'];
                }
            }
            if (!$found) {
                $warnings[] = 'La contraseña se toma de un campo que no existe o no es obligatorio. Revise la configuración del formulario.';
            }
        }

        if (self::USERNAME_FIELD === $settings['username_mode']) {
            $found = false;
            foreach ($fields as $field) {
                if ($field['field_key'] === $settings['username_field'] && !$field['is_core'] && 'section' !== $field['type']) {
                    $found = $field['required'];
                }
            }
            if (!$found) {
                $warnings[] = 'El nombre de usuario se toma de un campo que no existe o no es obligatorio. Revise la configuración del formulario.';
            }
        }

        return $warnings;
    }

    /* ---------------------------------------------------------------------
     * Formulario público
     * ------------------------------------------------------------------- */

    public static function elementName(array $field): string
    {
        return 'f_'.$field['field_key'];
    }

    /**
     * Construye el FormValidator del formulario público con sus validaciones.
     */
    public function buildPublicForm(array $form, array $fields, string $action): FormValidator
    {
        $hasFile = false;
        foreach ($fields as $field) {
            if ('file' === $field['type']) {
                $hasFile = true;
            }
        }
        $attributes = [];
        if ($hasFile) {
            $attributes['enctype'] = 'multipart/form-data';
        }

        $formValidator = new FormValidator(
            'proikos_reg_form_'.$form['id'],
            'post',
            $action,
            '',
            $attributes,
            FormValidator::LAYOUT_INLINE
        );

        // El layout inline reemplaza la clase del <form>: se usa un contenedor propio.
        $wizard = !empty($form['settings']['wizard']);
        $formValidator->addHtml('<div class="proikos-reg-form"'.($wizard ? ' data-pk-wizard="1"' : '').'>');
        if (!empty($form['description'])) {
            $formValidator->addHtml('<div class="pk-rf-description">'.nl2br(Security::remove_XSS($form['description'])).'</div>');
        }

        $settings = $form['settings'];
        $renderer = $formValidator->defaultRenderer();
        // Etiquetas por clave: una lista en cascada nombra a su padre («Elija antes Región»).
        $this->labelsByKey = array_column($fields, 'label', 'field_key');
        $byKey = array_column($fields, null, 'field_key');
        $hasCascade = false;
        $hasConditions = false;
        // Cada sección es una tarjeta (pk-rf-block) con su título y una fila
        // flexible de campos. Se abre al llegar a la primera sección o campo, así
        // un formulario que empieza con una sección no deja una tarjeta vacía.
        $blockOpen = false;
        foreach ($fields as $field) {
            if (!empty($field['config']['parent'])) {
                $hasCascade = true;
            }
            if ('password' === $field['type'] && self::PASSWORD_MANUAL !== $settings['password_mode']) {
                continue;
            }
            if ('section' === $field['type']) {
                $formValidator->addHtml(($blockOpen ? '</div></div>' : '')
                    .'<div class="pk-rf-block" data-pk-step="'.htmlspecialchars(strip_tags($field['label']), ENT_QUOTES).'">'
                    .'<div class="pk-rf-section"><h4>'.Security::remove_XSS($field['label']).'</h4>');
                if (!empty($field['config']['help'])) {
                    $formValidator->addHtml('<p>'.nl2br($field['config']['help']).'</p>');
                }
                $formValidator->addHtml('</div><div class="pk-rf-row">');
                $blockOpen = true;
                continue;
            }
            if (!$blockOpen) {
                $formValidator->addHtml('<div class="pk-rf-block"><div class="pk-rf-row">');
                $blockOpen = true;
            }
            $condition = $this->conditionSpec($field, $byKey);
            if (null !== $condition) {
                $hasConditions = true;
            }
            $formValidator->addHtml('<div class="'.self::colClass($field).'"'
                .(null !== $condition ? ' data-pk-show="'.htmlspecialchars(json_encode($condition, JSON_UNESCAPED_UNICODE), ENT_QUOTES).'"' : '')
                .'>');
            $this->addFieldElement($formValidator, $field);
            $formValidator->addHtml('</div>');
            $renderer->setElementTemplate(self::ELEMENT_TEMPLATE, self::elementName($field));
            if ('password' === $field['type'] && self::asksPasswordConfirmation($field)) {
                $renderer->setElementTemplate(self::ELEMENT_TEMPLATE, self::elementName($field).'_confirm');
            }
        }
        if ($blockOpen) {
            $formValidator->addHtml('</div></div>');
        }
        if ($hasCascade) {
            $formValidator->addHtml('<script>'.self::CASCADE_JS.'</script>');
        }
        if ($hasConditions) {
            $formValidator->addHtml('<script>'.self::CONDITIONS_JS.'</script>');
        }

        // Trampa anti-bots: los humanos no ven este campo.
        $formValidator->addHtml(
            '<div class="pk-rf-hp" aria-hidden="true" style="position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden;"><label>Sitio web<input type="text" name="pk_website" tabindex="-1" autocomplete="off"></label></div>'
        );
        $formValidator->addHidden('pk_reg_form_sec_token', '');
        // Constante: siempre el token de la sesión, nunca el valor reenviado.
        $formValidator->setConstants(['pk_reg_form_sec_token' => Security::get_existing_token('pk_reg_form')]);
        $formValidator->addButton('pk_submit', $settings['submit_label'], 'check', 'primary', 'btn-block');
        $formValidator->addHtml('</div>');
        if ($wizard) {
            $formValidator->addHtml('<script>'.self::WIZARD_JS.'</script>');
        }
        $renderer->setElementTemplate(
            '<div class="form-group pk-rf-submit {error_class}">{element}<!-- BEGIN error --><span class="help-inline help-block">{error}</span><!-- END error --></div>',
            'pk_submit'
        );

        $formValidator->addFormRule(function ($values) use ($form, $fields) {
            $errors = $this->validateSubmission($form, $fields, $values, $_FILES);

            return empty($errors) ? true : $errors;
        });

        return $formValidator;
    }

    private function addFieldElement(FormValidator $formValidator, array $field)
    {
        $name = self::elementName($field);
        $config = $field['config'];
        $validations = $field['validations'];
        $requiredMark = $field['required'] ? '<span class="form_required">*</span> ' : '';
        $labelText = $requiredMark.Security::remove_XSS($field['label']);
        $label = !empty($config['help']) ? [$labelText, $config['help']] : $labelText;

        $attributes = [];
        if (!empty($config['placeholder'])) {
            $attributes['placeholder'] = $config['placeholder'];
        }
        if ($field['required']) {
            $attributes['required'] = 'required';
        }
        if (isset($validations['max_length'])) {
            $attributes['maxlength'] = $validations['max_length'];
        }
        if (isset($validations['min_length'])) {
            $attributes['minlength'] = $validations['min_length'];
        }

        switch ($field['type']) {
            case 'firstname':
            case 'lastname':
            case 'text':
                $pattern = $validations['pattern'] ?? '';
                if ($pattern && 'custom' !== $pattern) {
                    $attributes['pattern'] = self::PATTERNS[$pattern]['html'];
                    $attributes['title'] = $validations['pattern_message'] ?? self::PATTERNS[$pattern]['message'];
                }
                if (in_array($pattern, ['digits', 'dni', 'ruc'], true)) {
                    $attributes['inputmode'] = 'numeric';
                }
                $formValidator->addText($name, $label, false, $attributes);
                break;
            case 'email':
                $attributes['autocomplete'] = 'email';
                $element = $formValidator->addText($name, $label, false, $attributes);
                $this->setInputType($element, 'email');
                break;
            case 'password':
                $requirements = self::passwordRequirements($field);
                $attributes['autocomplete'] = 'new-password';
                unset($attributes['maxlength']);
                if ($requirements['length'] > 0) {
                    $attributes['minlength'] = $requirements['length'];
                }
                $formValidator->addElement('password', $name, $label, $attributes + ['class' => 'form-control']);
                $formValidator->addHtml($this->passwordMeterHtml(
                    $name,
                    $requirements,
                    empty($validations['hide_strength']),
                    $validations['requirements_display'] ?? 'inline'
                ));
                if (!self::asksPasswordConfirmation($field)) {
                    break;
                }
                // La confirmación va en su propia columna, al lado de la contraseña.
                $formValidator->addHtml('</div><div class="'.self::colClass($field).'">');
                $confirmLabel = $requiredMark.'Confirmar '.api_strtolower(Security::remove_XSS($field['label']));
                $formValidator->addElement('password', $name.'_confirm', $confirmLabel, ['required' => 'required', 'autocomplete' => 'new-password', 'class' => 'form-control']);
                break;
            case 'textarea':
                $attributes['rows'] = 4;
                $formValidator->addTextarea($name, $label, $attributes);
                break;
            case 'number':
                if (isset($validations['min'])) {
                    $attributes['min'] = $validations['min'];
                }
                if (isset($validations['max'])) {
                    $attributes['max'] = $validations['max'];
                }
                $attributes['step'] = !empty($validations['integer']) ? '1' : 'any';
                $element = $formValidator->addText($name, $label, false, $attributes);
                $this->setInputType($element, 'number');
                break;
            case 'tel':
                // Los límites se cuentan en dígitos en el servidor, no en caracteres.
                unset($attributes['maxlength'], $attributes['minlength']);
                $attributes['inputmode'] = 'tel';
                $attributes['pattern'] = '[0-9+\\s\\-()]+';
                $element = $formValidator->addText($name, $label, false, $attributes);
                $this->setInputType($element, 'tel');
                break;
            case 'date':
                if (isset($validations['min_date'])) {
                    $attributes['min'] = $validations['min_date'];
                }
                if (isset($validations['max_date'])) {
                    $attributes['max'] = $validations['max_date'];
                }
                $element = $formValidator->addText($name, $label, false, $attributes);
                $this->setInputType($element, 'date');
                break;
            case 'select':
                $options = ['' => $config['placeholder'] ?: 'Seleccione una opción'];
                foreach ($field['options'] as $option) {
                    $options[$option['value']] = $option['label'];
                }
                unset($attributes['placeholder'], $attributes['maxlength'], $attributes['minlength']);
                $groups = $this->optionGroups($field);
                if (null !== $groups) {
                    // Cascada: el JS deja solo las opciones del grupo elegido en el padre.
                    $parentKey = $config['parent'];
                    $attributes['data-pk-parent'] = 'f_'.$parentKey;
                    $attributes['data-pk-parent-label'] = $this->labelsByKey[$parentKey] ?? 'el campo anterior';
                    $attributes['data-pk-groups'] = json_encode($groups, JSON_UNESCAPED_UNICODE);
                }
                $formValidator->addSelect($name, $label, $options, $attributes);
                break;
            case 'radio':
                $options = [];
                foreach ($field['options'] as $option) {
                    $options[$option['value']] = Security::remove_XSS($option['label']);
                }
                $formValidator->addRadio($name, $label, $options);
                break;
            case 'checkbox':
                $group = [];
                foreach ($field['options'] as $index => $option) {
                    $group[] = $formValidator->createElement('checkbox', 'o'.$index, null, Security::remove_XSS($option['label']));
                }
                $formValidator->addGroup($group, $name, $label);
                break;
            case 'file':
                $fileAttributes = [];
                if (!empty($validations['extensions'])) {
                    $fileAttributes['accept'] = '.'.str_replace(',', ',.', $validations['extensions']);
                }
                if ($field['required']) {
                    $fileAttributes['required'] = 'required';
                }
                $formValidator->addFile($name, $label, $fileAttributes);
                break;
            case 'terms':
                $text = Security::remove_XSS($field['label']);
                $mode = $config['terms_mode'] ?? (!empty($config['url']) ? 'url' : 'none');
                $modalId = 'pk-terms-'.$field['field_key'];
                if ('url' === $mode && !empty($config['url'])) {
                    $linkText = Security::remove_XSS($config['link_text'] ?: 'Ver documento');
                    $text .= ' <a href="'.Security::remove_XSS($config['url']).'" target="_blank" rel="noopener">'.$linkText.'</a>';
                } elseif ('modal' === $mode && !empty($config['terms_text'])) {
                    $linkText = Security::remove_XSS($config['link_text'] ?: 'Leer términos y condiciones');
                    $text .= ' <a href="#'.$modalId.'" data-pk-modal="'.$modalId.'">'.$linkText.'</a>';
                }
                $formValidator->addCheckBox($name, !empty($config['help']) ? ['', $config['help']] : '', $requiredMark.$text, ['required' => 'required']);
                if ('modal' === $mode && !empty($config['terms_text'])) {
                    $formValidator->addHtml($this->termsModalHtml($modalId, $name, $field['label'], $config['terms_text']));
                }
                break;
        }
    }

    /**
     * Barra de seguridad y lista de requisitos que se marcan mientras se escribe.
     * El servidor valida siempre con passwordFailures(); esto solo adelanta el aviso.
     */
    private function passwordMeterHtml(string $name, array $requirements, bool $showBar, string $display): string
    {
        $items = '';
        if ('hidden' !== $display) {
            foreach (self::passwordRequirementTexts($requirements) as $key => $text) {
                $items .= '<li data-req="'.$key.'"><em class="fa fa-fw fa-circle-o"></em> '.Security::remove_XSS($text).'</li>';
            }
        }
        if (!$showBar && '' === $items) {
            return '';
        }
        $bar = $showBar
            ? '<div class="pk-rf-pass-bar"><span></span></div><div class="pk-rf-pass-label"></div>'
            : '';
        $class = 'popup' === $display ? 'pk-rf-pass-meter pk-rf-pass-popup' : 'pk-rf-pass-meter';

        return '<div class="'.$class.'" data-for="'.$name.'" data-req="'.htmlspecialchars(json_encode($requirements), ENT_QUOTES).'">'
            .$bar.($items ? '<ul class="pk-rf-pass-checks">'.$items.'</ul>' : '').'</div>'
            .'<script>'.self::PASSWORD_METER_JS.'</script>';
    }

    /** Script del medidor; las expresiones coinciden con PASSWORD_CHAR_CLASSES. */
    const PASSWORD_METER_JS = <<<'JS'
(function () {
    var CLASES = {lowercase: /\p{Ll}/gu, uppercase: /\p{Lu}/gu, numeric: /[0-9]/g, specials: /[^\p{L}\p{N}\s]/gu};
    var NIVELES = [
        ['', ''], ['Muy débil', '#d9534f'], ['Débil', '#f0ad4e'],
        ['Aceptable', '#e6c229'], ['Fuerte', '#5cb85c'], ['Muy fuerte', '#2e7d32']
    ];
    function contar(valor, clave) {
        var m = valor.match(CLASES[clave]);
        return m ? m.length : 0;
    }
    document.querySelectorAll('.pk-rf-pass-meter:not([data-ready])').forEach(function (meter) {
        meter.setAttribute('data-ready', '1');
        var input = meter.closest('form').querySelector('[name="' + meter.dataset.for + '"]');
        var req = JSON.parse(meter.dataset.req || '{}');
        if (!input) { return; }
        function pintar() {
            var valor = input.value || '';
            var cumpleTodo = true;
            meter.querySelectorAll('li[data-req]').forEach(function (li) {
                var clave = li.dataset.req;
                var ok = clave === 'length' ? valor.length >= req.length : contar(valor, clave) >= req[clave];
                cumpleTodo = cumpleTodo && ok;
                li.classList.toggle('pk-ok', ok);
                li.querySelector('em').className = 'fa fa-fw ' + (ok ? 'fa-check-circle' : 'fa-circle-o');
            });
            var bar = meter.querySelector('.pk-rf-pass-bar span');
            if (!bar) { return; }
            var clases = Object.keys(CLASES).filter(function (c) { return contar(valor, c) > 0; }).length;
            var puntos = 0;
            if (valor.length >= 6) { puntos++; }
            if (valor.length >= 10) { puntos++; }
            if (clases >= 2) { puntos++; }
            if (clases >= 3) { puntos++; }
            if (clases === 4 && valor.length >= 12) { puntos++; }
            if (valor && puntos === 0) { puntos = 1; }
            // Si no cumple los requisitos no puede verse "fuerte": se rechazaría igual.
            if (!cumpleTodo) { puntos = Math.min(puntos, 2); }
            bar.style.width = (puntos * 20) + '%';
            bar.style.backgroundColor = NIVELES[puntos][1];
            meter.querySelector('.pk-rf-pass-label').textContent = NIVELES[puntos][0];
        }
        input.addEventListener('input', pintar);
        if (meter.classList.contains('pk-rf-pass-popup')) {
            // Ventana emergente: la lista aparece solo mientras se escribe la contraseña.
            input.addEventListener('focus', function () { meter.classList.add('pk-open'); });
            input.addEventListener('blur', function () { meter.classList.remove('pk-open'); });
        }
        pintar();
    });
})();
JS;

    /** ¿Se pide escribir la contraseña dos veces? Activado salvo que el administrador lo quite. */
    public static function asksPasswordConfirmation(array $field): bool
    {
        return empty($field['validations']['no_confirm']);
    }

    /**
     * Mínimo de caracteres de cada tipo que exige el campo contraseña:
     * ['length', 'lowercase', 'uppercase', 'numeric', 'specials'].
     */
    public static function passwordRequirements(array $field): array
    {
        $validations = $field['validations'] ?? [];
        $level = $validations['password_level'] ?? 'chamilo';
        if ('custom' === $level) {
            return [
                'length' => (int) ($validations['min_length'] ?? 8),
                'lowercase' => empty($validations['require_lower']) ? 0 : 1,
                'uppercase' => empty($validations['require_upper']) ? 0 : 1,
                'numeric' => empty($validations['require_digit']) ? 0 : 1,
                'specials' => empty($validations['require_special']) ? 0 : 1,
            ];
        }
        if (!empty(self::PASSWORD_LEVELS[$level]['min'])) {
            return self::PASSWORD_LEVELS[$level]['min'];
        }
        $min = Security::getPasswordRequirements()['min'];

        return [
            'length' => (int) ($min['length'] ?? 0),
            'lowercase' => (int) ($min['lowercase'] ?? 0),
            'uppercase' => (int) ($min['uppercase'] ?? 0),
            'numeric' => (int) ($min['numeric'] ?? 0),
            'specials' => (int) ($min['specials'] ?? 0),
        ];
    }

    /** Texto de cada requisito exigido, por clave ("al menos 8 caracteres"…). */
    public static function passwordRequirementTexts(array $requirements): array
    {
        $names = [
            'length' => ['carácter', 'caracteres'],
            'lowercase' => ['letra minúscula', 'letras minúsculas'],
            'uppercase' => ['letra mayúscula', 'letras mayúsculas'],
            'numeric' => ['número', 'números'],
            'specials' => ['carácter especial (!@#$%…)', 'caracteres especiales (!@#$%…)'],
        ];
        $texts = [];
        foreach ($names as $key => [$singular, $plural]) {
            $count = (int) ($requirements[$key] ?? 0);
            if ($count > 0) {
                $texts[$key] = 'al menos '.$count.' '.(1 === $count ? $singular : $plural);
            }
        }

        return $texts;
    }

    /**
     * @return string[] Requisitos que la contraseña no cumple (vacío si es válida).
     */
    public static function passwordFailures(string $password, array $requirements): array
    {
        $texts = self::passwordRequirementTexts($requirements);
        $failed = [];
        if (isset($texts['length']) && api_strlen($password) < $requirements['length']) {
            $failed[] = $texts['length'];
        }
        foreach (self::PASSWORD_CHAR_CLASSES as $key => $regex) {
            if (isset($texts[$key]) && preg_match_all($regex, $password) < $requirements[$key]) {
                $failed[] = $texts[$key];
            }
        }

        return $failed;
    }

    /** Clases de la columna de un campo: ancho (12 / 6 / 4) y tipo, para el CSS. */
    private static function colClass(array $field): string
    {
        return 'pk-rf-col pk-rf-w'.(int) $field['config']['width'].' pk-rf-type-'.$field['type'];
    }

    /**
     * Ventana con el texto de los términos. "Acepto" marca la casilla del campo.
     * Propia (sin Bootstrap) para funcionar igual en las páginas personalizadas.
     */
    private function termsModalHtml(string $modalId, string $checkboxName, string $title, string $html): string
    {
        return '<div class="pk-rf-modal" id="'.$modalId.'" role="dialog" aria-modal="true" aria-hidden="true" hidden>'
            .'<div class="pk-rf-modal-box">'
            .'<div class="pk-rf-modal-head"><strong>'.Security::remove_XSS($title).'</strong>'
            .'<button type="button" class="pk-rf-modal-x" data-pk-close aria-label="Cerrar">&times;</button></div>'
            .'<div class="pk-rf-modal-body">'.Security::remove_XSS($html).'</div>'
            .'<div class="pk-rf-modal-foot">'
            .'<button type="button" class="btn btn-default" data-pk-close>Cerrar</button> '
            .'<button type="button" class="btn btn-primary" data-pk-accept="'.$checkboxName.'">Acepto</button>'
            .'</div></div></div>'
            .'<script>'.self::TERMS_MODAL_JS.'</script>';
    }

    const TERMS_MODAL_JS = <<<'JS'
(function () {
    if (window.pkTermsModal) { return; }
    window.pkTermsModal = true;
    function cerrar(modal) {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('pk-rf-modal-open');
    }
    document.addEventListener('click', function (e) {
        var abrir = e.target.closest('[data-pk-modal]');
        if (abrir) {
            // Evita además que el clic en el enlace marque la casilla de su etiqueta.
            e.preventDefault();
            var modal = document.getElementById(abrir.getAttribute('data-pk-modal'));
            if (modal) {
                modal.hidden = false;
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('pk-rf-modal-open');
            }
            return;
        }
        var abierto = e.target.closest('.pk-rf-modal');
        if (!abierto) { return; }
        var aceptar = e.target.closest('[data-pk-accept]');
        if (aceptar) {
            var casilla = abierto.closest('form').querySelector('input[type="checkbox"][name="' + aceptar.getAttribute('data-pk-accept') + '"]');
            if (casilla) {
                casilla.checked = true;
                casilla.dispatchEvent(new Event('change', {bubbles: true}));
            }
            cerrar(abierto);
        } else if (e.target.closest('[data-pk-close]') || e.target === abierto) {
            cerrar(abierto);
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.pk-rf-modal:not([hidden])').forEach(cerrar);
        }
    });
})();
JS;

    /**
     * ¿Se muestra el campo con lo enviado? Sin condición, siempre; con
     * condición, si el campo que decide está visible y tiene marcada alguna de
     * las opciones indicadas. Es la misma regla que aplica CONDITIONS_JS.
     */
    public function isFieldVisible(array $field, array $values, array $fieldsByKey, int $depth = 0): bool
    {
        $condition = $field['config']['show_if'] ?? null;
        if (empty($condition['field'])) {
            return true;
        }
        $parent = $fieldsByKey[$condition['field']] ?? null;
        if (!$parent || $depth > 20 || !$this->isFieldVisible($parent, $values, $fieldsByKey, $depth + 1)) {
            return false;
        }
        $value = $this->getSubmittedValue($parent, $values);
        $chosen = array_map('strval', is_array($value) ? $value : [$value]);

        return (bool) array_intersect($chosen, $condition['values']);
    }

    /**
     * Datos para que el navegador muestre u oculte el campo según su condición:
     * nombre del control que decide y los valores que lo muestran (en casillas,
     * los nombres de las casillas que lo muestran).
     */
    private function conditionSpec(array $field, array $fieldsByKey)
    {
        $condition = $field['config']['show_if'] ?? null;
        $parent = empty($condition['field']) ? null : ($fieldsByKey[$condition['field']] ?? null);
        if (!$parent) {
            return null;
        }
        $name = self::elementName($parent);
        if ('checkbox' === $parent['type']) {
            $names = [];
            foreach ($parent['options'] as $index => $option) {
                if (in_array((string) $option['value'], $condition['values'], true)) {
                    $names[] = $name.'[o'.$index.']';
                }
            }

            return ['type' => 'checkbox', 'names' => $names];
        }

        return ['type' => $parent['type'], 'name' => $name, 'values' => $condition['values']];
    }

    /**
     * Visibilidad condicional en el navegador: oculta el campo y deshabilita sus
     * controles (así su "required" no bloquea el envío y no se envían).
     */
    const CONDITIONS_JS = <<<'JS'
(function () {
    var cols = Array.prototype.slice.call(document.querySelectorAll('[data-pk-show]:not([data-ready])'));
    if (!cols.length) { return; }
    var form = cols[0].closest('form');
    cols.forEach(function (col) {
        col.setAttribute('data-ready', '1');
        col.pkSpec = JSON.parse(col.dataset.pkShow || '{}');
    });
    function oculto(el) {
        var col = el.closest('[data-pk-show]');
        return !!col && col.style.display === 'none';
    }
    function cumple(spec) {
        if (spec.type === 'checkbox') {
            return (spec.names || []).some(function (n) {
                var cb = form.querySelector('input[type="checkbox"][name="' + n + '"]');
                return !!cb && cb.checked && !oculto(cb);
            });
        }
        var el = spec.type === 'radio'
            ? form.querySelector('input[type="radio"][name="' + spec.name + '"]:checked')
            : form.querySelector('select[name="' + spec.name + '"]');
        return !!el && !oculto(el) && (spec.values || []).indexOf(el.value) !== -1;
    }
    function aplicar() {
        // Varias pasadas: un campo que se oculta puede ocultar a los que dependen de él.
        for (var pasada = 0; pasada < 3; pasada++) {
            cols.forEach(function (col) {
                var visible = cumple(col.pkSpec);
                col.style.display = visible ? '' : 'none';
                col.querySelectorAll('input, select, textarea').forEach(function (el) {
                    if (!visible && !el.disabled) {
                        el.disabled = true;
                        el.setAttribute('data-pk-off', '1');
                    } else if (visible && el.hasAttribute('data-pk-off')) {
                        el.disabled = false;
                        el.removeAttribute('data-pk-off');
                    } else {
                        return;
                    }
                    // Listas de Chamilo (bootstrap-select): redibujar el componente visible.
                    if (el.tagName === 'SELECT' && window.jQuery && jQuery.fn.selectpicker && jQuery(el).data('selectpicker')) {
                        jQuery(el).selectpicker('refresh');
                    }
                });
            });
        }
    }
    form.addEventListener('change', aplicar);
    aplicar();
})();
JS;

    /**
     * Paso a paso: cada tarjeta (pk-rf-block) es un paso. Antes de avanzar se
     * validan los campos visibles del paso; el envío y la validación del
     * servidor no cambian. Si el servidor devuelve errores, se abre el primer
     * paso que los tenga.
     */
    const WIZARD_JS = <<<'JS'
(function () {
    var root = document.querySelector('.proikos-reg-form[data-pk-wizard]:not([data-ready])');
    if (!root) { return; }
    root.setAttribute('data-ready', '1');
    var steps = Array.prototype.slice.call(root.querySelectorAll('.pk-rf-block'));
    var submit = root.querySelector('.pk-rf-submit');
    var form = root.closest('form');
    if (steps.length < 2 || !submit || !form) { return; }
    var last = steps.length - 1;
    var current = 0;

    function boton(html, clase) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn ' + clase;
        b.innerHTML = html;
        return b;
    }

    // Indicador de pasos
    var nav = document.createElement('ol');
    nav.className = 'pk-rf-steps';
    steps.forEach(function (step, i) {
        var li = document.createElement('li');
        li.innerHTML = '<span class="pk-rf-step-num"></span><span class="pk-rf-step-label"></span>';
        li.querySelector('.pk-rf-step-num').textContent = i + 1;
        li.querySelector('.pk-rf-step-label').textContent = step.getAttribute('data-pk-step') || ('Paso ' + (i + 1));
        li.addEventListener('click', function () { ir(i); });
        nav.appendChild(li);
    });
    steps[0].parentNode.insertBefore(nav, steps[0]);

    // Botones: Anterior / Siguiente, y el de envío solo en el último paso
    var prev = boton('<i class="fa fa-arrow-left" aria-hidden="true"></i> Anterior', 'btn-default pk-rf-prev');
    var next = boton('Siguiente <i class="fa fa-arrow-right" aria-hidden="true"></i>', 'btn-primary pk-rf-next');
    var bar = document.createElement('div');
    bar.className = 'pk-rf-wizard-nav';
    submit.parentNode.insertBefore(bar, submit);
    bar.appendChild(prev);
    bar.appendChild(next);
    bar.appendChild(submit);

    function campos(step) {
        return Array.prototype.slice.call(step.querySelectorAll('input, select, textarea')).filter(function (el) {
            return !el.disabled && el.willValidate;
        });
    }
    function limpiar(el) {
        var group = el.closest('.pk-rf-group');
        if (!group || !group.hasAttribute('data-pk-wz-error')) { return; }
        group.classList.remove('has-error');
        group.removeAttribute('data-pk-wz-error');
        var msg = group.querySelector('.pk-rf-wz-error');
        if (msg) { msg.remove(); }
    }
    function marcar(el) {
        var group = el.closest('.pk-rf-group');
        if (!group || group.querySelector('.pk-rf-error')) { return; }
        group.classList.add('has-error');
        group.setAttribute('data-pk-wz-error', '1');
        var msg = document.createElement('span');
        msg.className = 'help-block pk-rf-error pk-rf-wz-error';
        msg.textContent = el.validationMessage;
        group.appendChild(msg);
    }
    function enfocar(el) {
        var target = el;
        // Listas de Chamilo (bootstrap-select): la lista nativa está oculta.
        if (el.tagName === 'SELECT' && el.parentNode.querySelector('.dropdown-toggle')) {
            target = el.parentNode.querySelector('.dropdown-toggle');
        }
        (el.closest('.pk-rf-group') || el).scrollIntoView({block: 'center', behavior: 'smooth'});
        try { target.focus({preventScroll: true}); } catch (e) { target.focus(); }
    }
    function valido(i) {
        var primero = null;
        campos(steps[i]).forEach(function (el) {
            if (!el.checkValidity()) {
                marcar(el);
                primero = primero || el;
            }
        });
        if (primero) {
            mostrar(i);
            enfocar(primero);
        }
        return !primero;
    }
    function mostrar(i) {
        var cambia = i !== current;
        current = i;
        steps.forEach(function (step, n) { step.hidden = n !== i; });
        Array.prototype.forEach.call(nav.children, function (li, n) {
            li.classList.toggle('pk-active', n === i);
            li.classList.toggle('pk-done', n < i);
        });
        prev.style.display = i === 0 ? 'none' : '';
        next.style.display = i === last ? 'none' : '';
        submit.style.display = i === last ? '' : 'none';
        if (cambia && nav.getBoundingClientRect().top < 0) {
            nav.scrollIntoView({block: 'start', behavior: 'smooth'});
        }
    }
    // Hacia atrás siempre; hacia adelante solo si los pasos intermedios son válidos.
    function ir(i) {
        for (var n = current; n < i; n++) {
            if (!valido(n)) { return; }
        }
        mostrar(i);
    }

    prev.addEventListener('click', function () { ir(current - 1); });
    next.addEventListener('click', function () { ir(current + 1); });
    // Antes del envío se revisan todos los pasos (el navegador no puede
    // señalar un campo inválido que está en un paso oculto).
    submit.addEventListener('click', function (e) {
        for (var n = 0; n <= last; n++) {
            if (!valido(n)) {
                e.preventDefault();
                return;
            }
        }
    }, true);
    form.addEventListener('keydown', function (e) {
        var t = e.target;
        if (e.key === 'Enter' && current < last && t.tagName === 'INPUT' && t.type !== 'submit' && t.type !== 'button') {
            e.preventDefault();
            ir(current + 1);
        }
    });
    form.addEventListener('input', function (e) { limpiar(e.target); });
    form.addEventListener('change', function (e) { limpiar(e.target); });

    // Errores del servidor: se abre el primer paso que los tenga.
    var inicio = 0;
    if (!submit.classList.contains('has-error')) {
        for (var n = 0; n <= last; n++) {
            if (steps[n].querySelector('.has-error')) { inicio = n; break; }
        }
    } else {
        inicio = last;
    }
    current = -1;
    mostrar(inicio);
})();
JS;

    /**
     * Opciones agrupadas por la opción del padre de una lista del Ubigeo en
     * cascada, o null si el campo no depende de otro.
     */
    private function optionGroups(array $field)
    {
        $source = $field['config']['options_source'] ?? '';
        if ('' === $source || empty($field['config']['parent'])) {
            return null;
        }

        return UbigeoPeru::groups($source);
    }

    private function setInputType($element, string $type)
    {
        if ($element instanceof HTML_QuickForm_input) {
            $element->setType($type);
        }
    }

    /**
     * Obtiene el valor enviado de un campo, normalizado.
     *
     * @return mixed string | string[] (checkbox) | null
     */
    private function getSubmittedValue(array $field, array $values)
    {
        $name = self::elementName($field);
        switch ($field['type']) {
            case 'checkbox':
                $selected = [];
                $raw = $values[$name] ?? [];
                foreach ($field['options'] as $index => $option) {
                    if (!empty($raw['o'.$index])) {
                        $selected[] = $option['value'];
                    }
                }

                return $selected;
            case 'terms':
                return empty($values[$name]) ? '' : '1';
            case 'file':
                return null;
            default:
                $value = $values[$name] ?? '';

                return is_array($value) ? '' : trim((string) $value);
        }
    }

    /**
     * Valida el envío completo. Devuelve errores indexados por nombre de elemento.
     */
    public function validateSubmission(array $form, array $fields, array $values, array $files): array
    {
        $errors = [];
        $settings = $form['settings'];

        if (!empty($values['pk_website'])) {
            return ['pk_submit' => 'No se pudo procesar el registro.'];
        }
        if (!Security::check_token('post', null, 'pk_reg_form')) {
            return ['pk_submit' => 'La sesión del formulario expiró. Vuelva a enviar el formulario.'];
        }

        $byKey = array_column($fields, null, 'field_key');
        foreach ($fields as $field) {
            if ('section' === $field['type']) {
                continue;
            }
            if ('password' === $field['type'] && self::PASSWORD_MANUAL !== $settings['password_mode']) {
                continue;
            }
            // Oculto por su condición: no se exige ni se valida.
            if (!$this->isFieldVisible($field, $values, $byKey)) {
                continue;
            }
            $name = self::elementName($field);
            $error = 'file' === $field['type']
                ? $this->validateFile($field, $files[$name] ?? null)
                : $this->validateField($form, $field, $this->getSubmittedValue($field, $values), $values);
            if (null !== $error) {
                $errors[$name] = $error;
            }
        }

        // Nombre de usuario resultante
        $usernameElement = $this->usernameElement($settings, $fields);
        if (!isset($errors[$usernameElement])) {
            $username = $this->resolveUsername($settings, $fields, $values);
            if ('' === $username && self::USERNAME_FIELD === $settings['username_mode']) {
                $errors['pk_submit'] = 'El formulario no está configurado correctamente (nombre de usuario).';
            } elseif ('' !== $username) {
                if (api_strlen($username) > USERNAME_MAX_LENGTH) {
                    $errors[$usernameElement] = 'El nombre de usuario no puede superar '.USERNAME_MAX_LENGTH.' caracteres.';
                } elseif (preg_match(USERNAME_PURIFIER, $username)) {
                    $errors[$usernameElement] = 'El nombre de usuario solo puede contener letras sin tilde, números y los caracteres . _ - @';
                } elseif (!UserManager::is_username_available($username)) {
                    $errors[$usernameElement] = 'Ya existe un usuario registrado con este dato.';
                }
            }
        }

        if (self::PASSWORD_FIELD === $settings['password_mode']) {
            $password = $this->resolvePassword($form, $fields, $values);
            if ('' === $password) {
                $errors['pk_submit'] = 'El formulario no está configurado correctamente (contraseña).';
            }
        }

        return $errors;
    }

    /**
     * @return string|null Mensaje de error o null si es válido.
     */
    private function validateField(array $form, array $field, $value, array $values)
    {
        $validations = $field['validations'];
        $isEmpty = is_array($value) ? empty($value) : '' === $value;

        if ($isEmpty) {
            if ($field['required']) {
                switch ($field['type']) {
                    case 'terms':
                        return 'Debe aceptar para continuar.';
                    case 'select':
                    case 'radio':
                        return 'Seleccione una opción.';
                    case 'checkbox':
                        return 'Seleccione al menos una opción.';
                    default:
                        return 'Este campo es obligatorio.';
                }
            }

            return null;
        }

        switch ($field['type']) {
            case 'firstname':
            case 'lastname':
            case 'text':
            case 'textarea':
                $length = api_strlen($value);
                if (isset($validations['min_length']) && $length < $validations['min_length']) {
                    return 'Debe tener al menos '.$validations['min_length'].' caracteres.';
                }
                if (isset($validations['max_length']) && $length > $validations['max_length']) {
                    return 'No debe superar '.$validations['max_length'].' caracteres.';
                }
                if (!empty($validations['pattern'])) {
                    $pattern = $validations['pattern'];
                    $regex = 'custom' === $pattern ? self::buildRegex($validations['regex'] ?? '') : self::PATTERNS[$pattern]['regex'];
                    if (1 !== @preg_match($regex, $value)) {
                        return $validations['pattern_message'] ?? (self::PATTERNS[$pattern]['message'] ?? 'El formato no es válido.');
                    }
                }
                if ('firstname' === $field['type'] || 'lastname' === $field['type']) {
                    if ($length > 60) {
                        return 'No debe superar 60 caracteres.';
                    }
                }
                break;
            case 'email':
                if (false === api_valid_email($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return 'Ingrese un correo electrónico válido.';
                }
                if (!empty($validations['allowed_domains'])) {
                    $domain = strtolower(substr(strrchr($value, '@'), 1));
                    $allowed = explode(',', $validations['allowed_domains']);
                    if (!in_array($domain, $allowed, true)) {
                        return 'Solo se aceptan correos de: '.implode(', ', array_map(function ($d) {
                            return '@'.$d;
                        }, $allowed));
                    }
                }
                if ($field['is_core'] && !empty($validations['unique']) && $this->userEmailExists($value)) {
                    return 'El correo electrónico ingresado ya existe en el sistema.';
                }
                break;
            case 'password':
                $confirm = (string) ($values[self::elementName($field).'_confirm'] ?? '');
                if (self::asksPasswordConfirmation($field) && $value !== $confirm) {
                    return 'Las contraseñas no coinciden.';
                }
                $failed = self::passwordFailures($value, self::passwordRequirements($field));
                if (!empty($failed)) {
                    return 'La contraseña debe tener '.implode(', ', $failed).'.';
                }
                break;
            case 'number':
                if (!is_numeric($value)) {
                    return 'Ingrese un número válido.';
                }
                if (!empty($validations['integer']) && !preg_match('/^-?\d+$/', $value)) {
                    return 'Ingrese un número entero.';
                }
                if (isset($validations['min']) && $value < $validations['min']) {
                    return 'El valor mínimo es '.$validations['min'].'.';
                }
                if (isset($validations['max']) && $value > $validations['max']) {
                    return 'El valor máximo es '.$validations['max'].'.';
                }
                break;
            case 'tel':
                if (!preg_match('/^[0-9+\s\-()]+$/', $value)) {
                    return 'Ingrese un número de teléfono válido.';
                }
                $digits = strlen(preg_replace('/\D/', '', $value));
                if (isset($validations['min_length']) && $digits < $validations['min_length']) {
                    return 'Debe tener al menos '.$validations['min_length'].' dígitos.';
                }
                if (isset($validations['max_length']) && $digits > $validations['max_length']) {
                    return 'No debe superar '.$validations['max_length'].' dígitos.';
                }
                break;
            case 'date':
                if (!self::isValidDate($value)) {
                    return 'Ingrese una fecha válida.';
                }
                if (isset($validations['min_date']) && $value < $validations['min_date']) {
                    return 'La fecha no puede ser anterior a '.$validations['min_date'].'.';
                }
                if (isset($validations['max_date']) && $value > $validations['max_date']) {
                    return 'La fecha no puede ser posterior a '.$validations['max_date'].'.';
                }
                if (isset($validations['min_age']) || isset($validations['max_age'])) {
                    $age = (new \DateTime($value))->diff(new \DateTime('today'))->y;
                    if (isset($validations['min_age']) && $age < $validations['min_age']) {
                        return 'Debe tener al menos '.$validations['min_age'].' años.';
                    }
                    if (isset($validations['max_age']) && $age > $validations['max_age']) {
                        return 'La edad máxima permitida es '.$validations['max_age'].' años.';
                    }
                }
                break;
            case 'select':
            case 'radio':
                if (!in_array($value, array_column($field['options'], 'value'), true)) {
                    return 'Seleccione una opción válida.';
                }
                // Cascada: la opción tiene que ser del grupo elegido en el padre.
                $groups = $this->optionGroups($field);
                if (null !== $groups) {
                    $parentValue = (string) ($values['f_'.$field['config']['parent']] ?? '');
                    if (!in_array($value, $groups[$parentValue] ?? [], true)) {
                        return 'La opción elegida no corresponde a lo seleccionado en el campo anterior.';
                    }
                }
                break;
            case 'checkbox':
                $count = count($value);
                if (isset($validations['min_selected']) && $count < $validations['min_selected']) {
                    return 'Seleccione al menos '.$validations['min_selected'].' opción(es).';
                }
                if (isset($validations['max_selected']) && $count > $validations['max_selected']) {
                    return 'Seleccione como máximo '.$validations['max_selected'].' opción(es).';
                }
                break;
        }

        if (!$field['is_core'] && !empty($validations['unique']) && $this->answerExists($field['id'], $value)) {
            return 'Este dato ya fue registrado por otro usuario.';
        }
        if ('pk.number_document' === $field['config']['map_to']) {
            $check = $this->plugin->validUserDNI(Database::escape_string($value));
            if (true !== $check) {
                return $check;
            }
        }

        return null;
    }

    /**
     * @return string|null
     */
    private function validateFile(array $field, $file)
    {
        $hasFile = is_array($file) && isset($file['error']) && UPLOAD_ERR_NO_FILE !== $file['error'];
        if (!$hasFile) {
            return $field['required'] ? 'Adjunte un archivo.' : null;
        }
        if (UPLOAD_ERR_OK !== $file['error'] || !is_uploaded_file($file['tmp_name'])) {
            return 'No se pudo subir el archivo. Intente nuevamente.';
        }
        $validations = $field['validations'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = !empty($validations['extensions']) ? explode(',', $validations['extensions']) : self::ALLOWED_FILE_EXTENSIONS;
        if (!in_array($extension, $allowed, true)) {
            return 'Tipo de archivo no permitido. Formatos aceptados: '.implode(', ', $allowed).'.';
        }
        $maxMb = $validations['max_size_mb'] ?? self::MAX_FILE_SIZE_MB;
        if ($file['size'] > $maxMb * 1024 * 1024) {
            return 'El archivo no debe superar '.$maxMb.' MB.';
        }

        return null;
    }

    private function userEmailExists(string $email): bool
    {
        $email = Database::escape_string($email);
        $sql = "SELECT id FROM ".Database::get_main_table(TABLE_MAIN_USER)." WHERE email = '$email' LIMIT 1";

        return Database::num_rows(Database::query($sql)) > 0;
    }

    private function answerExists($fieldId, $value): bool
    {
        $value = Database::escape_string(is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value);
        $sql = "SELECT id FROM ".self::TABLE_ANSWER." WHERE field_id = ".(int) $fieldId." AND value = '$value' LIMIT 1";

        return Database::num_rows(Database::query($sql)) > 0;
    }

    /**
     * Elemento del formulario que da el nombre de usuario: el campo elegido en
     * la configuración, el vinculado a «Chamilo: nombre de usuario» o el correo.
     */
    private function usernameElement(array $settings, array $fields): string
    {
        foreach ($fields as $field) {
            $matches = self::USERNAME_FIELD === $settings['username_mode']
                ? $field['field_key'] === $settings['username_field'] && !$field['is_core']
                : 'user.username' === $field['config']['map_to'];
            if ($matches) {
                return self::elementName($field);
            }
        }

        return 'f_email';
    }

    private function resolveUsername(array $settings, array $fields, array $values): string
    {
        $element = $this->usernameElement($settings, $fields);
        foreach ($fields as $field) {
            if ('f_email' !== $element && self::elementName($field) === $element) {
                $value = trim((string) $this->getSubmittedValue($field, $values));
                // Con un campo elegido no hay respaldo: vacío es un error de configuración.
                if ('' !== $value || self::USERNAME_FIELD === $settings['username_mode']) {
                    return $value;
                }
            }
        }

        return trim((string) ($values['f_email'] ?? ''));
    }

    private function resolvePassword(array $form, array $fields, array $values): string
    {
        $settings = $form['settings'];
        switch ($settings['password_mode']) {
            case self::PASSWORD_MANUAL:
                return (string) ($values['f_password'] ?? '');
            case self::PASSWORD_FIELD:
                foreach ($fields as $field) {
                    if ($field['field_key'] === $settings['password_field'] && !$field['is_core']) {
                        $value = $this->getSubmittedValue($field, $values);

                        return is_string($value) ? $value : '';
                    }
                }

                return '';
            default:
                return api_generate_password();
        }
    }

    /**
     * Crea el usuario en Chamilo y guarda las respuestas.
     *
     * @return int Id del usuario creado (0 si falla)
     */
    public function register(array $form, array $fields, array $values, array $files): int
    {
        $settings = $form['settings'];
        $byKey = array_column($fields, null, 'field_key');
        // Los campos ocultos por su condición no se guardan.
        $fields = array_values(array_filter($fields, function ($field) use ($values, $byKey) {
            return $this->isFieldVisible($field, $values, $byKey);
        }));
        $mapped = [];
        foreach ($fields as $field) {
            if (!empty($field['config']['map_to'])) {
                $mapped[$field['config']['map_to']] = (string) $this->getSubmittedValue($field, $values);
            }
        }

        $firstname = trim((string) $values['f_firstname']);
        $lastname = trim((string) $values['f_lastname']);
        $email = trim((string) $values['f_email']);
        $username = $this->resolveUsername($settings, $fields, $values);
        $password = $this->resolvePassword($form, $fields, $values);
        $language = $values['language'] ?? api_get_interface_language();
        if (!empty($mapped['user.language']) && in_array($mapped['user.language'], api_get_languages()['folder'], true)) {
            $language = $mapped['user.language'];
        }
        $codeReference = $settings['code_reference'] ?: null;
        $sendMail = !empty($settings['send_credentials_email']) || self::PASSWORD_AUTO === $settings['password_mode'];

        $userId = UserManager::create_user(
            $firstname,
            $lastname,
            STUDENT,
            $email,
            $username,
            $password,
            $mapped['user.official_code'] ?? '',
            $language,
            $mapped['user.phone'] ?? '',
            null,
            PLATFORM_AUTH_SOURCE,
            null,
            1,
            0,
            [],
            null,
            $sendMail,
            false,
            '',
            false,
            null,
            0,
            [],
            '',
            $codeReference
        );

        if (empty($userId)) {
            return 0;
        }

        $submissionId = (int) Database::insert(self::TABLE_SUBMISSION, [
            'form_id' => $form['id'],
            'user_id' => $userId,
            'ip' => api_get_real_ip(),
            'created_at' => api_get_utc_datetime(),
        ]);

        $hasTerms = false;
        foreach ($fields as $field) {
            if ('section' === $field['type'] || $field['is_core']) {
                continue;
            }
            if ('terms' === $field['type']) {
                $hasTerms = true;
            }
            if ('file' === $field['type']) {
                $value = $this->storeFile($form['id'], $submissionId, $files[self::elementName($field)] ?? null);
            } else {
                $value = $this->getSubmittedValue($field, $values);
            }
            if (null === $value || '' === $value || [] === $value) {
                continue;
            }
            Database::insert(self::TABLE_ANSWER, [
                'submission_id' => $submissionId,
                'form_id' => $form['id'],
                'field_id' => $field['id'],
                'value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value,
            ]);
        }

        $this->saveProikosUser($userId, $firstname, $lastname, $mapped, $codeReference, $hasTerms, $form['id'], $submissionId);

        return (int) $userId;
    }

    /**
     * Registra al usuario en plugin_proikos_users para que aparezca en los reportes.
     */
    private function saveProikosUser($userId, $firstname, $lastname, array $mapped, $codeReference, $hasTerms, $formId, $submissionId)
    {
        $params = [
            'user_id' => $userId,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'phone' => $mapped['user.phone'] ?? null,
            'code_reference' => $codeReference,
            'terms_conditions' => $hasTerms ? '1' : '0',
            'metadata' => json_encode(['registration_form_id' => $formId, 'submission_id' => $submissionId]),
        ];
        foreach (['type_document', 'age'] as $intColumn) {
            if (isset($mapped['pk.'.$intColumn]) && is_numeric($mapped['pk.'.$intColumn])) {
                $params[$intColumn] = (int) $mapped['pk.'.$intColumn];
            }
        }
        if (!empty($mapped['pk.gender'])) {
            $params['gender'] = self::cut(strtoupper($mapped['pk.gender']), 0, 1);
        }
        if (!empty($mapped['pk.ruc_company'])) {
            $params['ruc_company'] = self::cut($mapped['pk.ruc_company'], 0, 11);
        }
        foreach (['number_document', 'instruction', 'stakeholders', 'position_company', 'name_company'] as $column) {
            if (isset($mapped['pk.'.$column]) && '' !== $mapped['pk.'.$column]) {
                $params[$column] = $mapped['pk.'.$column];
            }
        }

        Database::insert(ProikosPlugin::TABLE_PROIKOS_USERS, $params);
    }

    /**
     * Inicia la sesión del usuario recién registrado.
     */
    public function loginUser($userId)
    {
        $userInfo = api_get_user_info($userId);
        $_user = [
            'firstName' => $userInfo['firstname'],
            'lastName' => $userInfo['lastname'],
            'mail' => $userInfo['email'],
            'language' => $userInfo['language'],
            'user_id' => $userId,
            'status' => STUDENT,
        ];
        Session::write('_user', $_user);
        Session::write('is_allowedCreateCourse', false);
        Event::eventLogin($userId);
        Session::write('user_last_login_datetime', 0);
    }

    /* ---------------------------------------------------------------------
     * Archivos
     * ------------------------------------------------------------------- */

    public static function getUploadDir(): string
    {
        return api_get_path(SYS_UPLOAD_PATH).'plugins/proikos/registration_forms/';
    }

    /**
     * @return string|null JSON con los datos del archivo guardado
     */
    private function storeFile($formId, $submissionId, $file)
    {
        if (!is_array($file) || UPLOAD_ERR_OK !== ($file['error'] ?? UPLOAD_ERR_NO_FILE)) {
            return null;
        }
        $relativeDir = (int) $formId.'/'.(int) $submissionId.'/';
        $dir = self::getUploadDir().$relativeDir;
        if (!is_dir($dir) && !mkdir($dir, api_get_permissions_for_new_directories(), true)) {
            return null;
        }
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $storedName = bin2hex(random_bytes(12)).'.'.$extension;
        if (!move_uploaded_file($file['tmp_name'], $dir.$storedName)) {
            return null;
        }

        return json_encode([
            'name' => api_replace_dangerous_char($file['name']),
            'path' => $relativeDir.$storedName,
            'size' => (int) $file['size'],
        ], JSON_UNESCAPED_UNICODE);
    }

    /* ---------------------------------------------------------------------
     * Respuestas
     * ------------------------------------------------------------------- */

    public function getSubmissions($formId, $search = '', $offset = 0, $limit = 50): array
    {
        $formId = (int) $formId;
        $tSubmission = self::TABLE_SUBMISSION;
        $tUser = Database::get_main_table(TABLE_MAIN_USER);
        $where = "s.form_id = $formId";
        if ('' !== $search) {
            $like = Database::escape_string('%'.$search.'%');
            $where .= " AND (u.firstname LIKE '$like' OR u.lastname LIKE '$like' OR u.email LIKE '$like' OR u.username LIKE '$like')";
        }
        $limitSql = $limit > 0 ? ' LIMIT '.(int) $offset.', '.(int) $limit : '';
        $sql = "SELECT s.id, s.user_id, s.ip, s.created_at, u.firstname, u.lastname, u.email, u.username
                FROM $tSubmission s
                LEFT JOIN $tUser u ON u.id = s.user_id
                WHERE $where
                ORDER BY s.id DESC $limitSql";
        $result = Database::query($sql);
        $submissions = [];
        while ($row = Database::fetch_assoc($result)) {
            $row['answers'] = [];
            $submissions[(int) $row['id']] = $row;
        }

        if (!empty($submissions)) {
            $ids = implode(',', array_keys($submissions));
            $answers = Database::query("SELECT submission_id, field_id, value FROM ".self::TABLE_ANSWER." WHERE submission_id IN ($ids)");
            while ($answer = Database::fetch_assoc($answers)) {
                $submissions[(int) $answer['submission_id']]['answers'][(int) $answer['field_id']] = $answer['value'];
            }
        }

        $countSql = "SELECT COUNT(*) AS total FROM $tSubmission s LEFT JOIN $tUser u ON u.id = s.user_id WHERE $where";
        $total = (int) Database::fetch_assoc(Database::query($countSql))['total'];

        return [array_values($submissions), $total];
    }

    public function getAnswer($submissionId, $fieldId)
    {
        $sql = "SELECT a.value, a.form_id FROM ".self::TABLE_ANSWER." a
                WHERE a.submission_id = ".(int) $submissionId." AND a.field_id = ".(int) $fieldId;

        return Database::fetch_assoc(Database::query($sql)) ?: null;
    }

    /**
     * Texto legible de una respuesta (para tablas y Excel).
     */
    public static function formatAnswer(array $field, $rawValue): string
    {
        if (null === $rawValue || '' === $rawValue) {
            return '';
        }
        $labels = array_column($field['options'], 'label', 'value');
        switch ($field['type']) {
            case 'checkbox':
                $values = json_decode($rawValue, true) ?: [];

                return implode(', ', array_map(function ($value) use ($labels) {
                    return $labels[$value] ?? $value;
                }, $values));
            case 'select':
            case 'radio':
                return $labels[$rawValue] ?? $rawValue;
            case 'terms':
                return 'Aceptado';
            case 'file':
                $file = json_decode($rawValue, true);

                return $file['name'] ?? '';
            default:
                return (string) $rawValue;
        }
    }

    /* ---------------------------------------------------------------------
     * Utilidades
     * ------------------------------------------------------------------- */

    public static function slugify(string $text): string
    {
        $text = api_strtolower(trim($text));
        $text = strtr($text, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        $text = preg_replace('/[^a-z0-9]+/', '_', $text);
        $text = trim($text, '_');

        return substr($text, 0, 50);
    }

    /**
     * Recorta un texto (api_substr() devuelve false con cadenas vacías).
     */
    public static function cut($text, int $start, int $length): string
    {
        return (string) mb_substr((string) $text, $start, $length, 'UTF-8');
    }

    public static function isValidDate($value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', (string) $value);

        return $date && $date->format('Y-m-d') === $value;
    }

    /**
     * Envuelve una expresión regular del administrador con delimitadores seguros.
     */
    public static function buildRegex(string $regex): string
    {
        return '~^(?:'.str_replace('~', '\~', $regex).')$~u';
    }

    /**
     * Datos para el constructor JS.
     */
    public static function builderCatalog(): array
    {
        $patterns = [];
        foreach (self::PATTERNS as $key => $pattern) {
            $patterns[$key] = $pattern['label'];
        }

        $passwordLevels = [];
        foreach (self::PASSWORD_LEVELS as $key => $level) {
            $requirements = 'custom' === $key ? null : self::passwordRequirements(['validations' => ['password_level' => $key]]);
            $passwordLevels[$key] = [
                'label' => $level['label'],
                'summary' => $requirements ? implode(', ', self::passwordRequirementTexts($requirements)) : '',
            ];
        }

        return [
            'passwordLevels' => $passwordLevels,
            'presets' => self::FIELD_PRESETS,
            'optionSources' => UbigeoPeru::SOURCES,
            'passwordRequirementsDisplay' => self::PASSWORD_REQUIREMENTS_DISPLAY,
            'types' => self::FIELD_TYPES,
            'coreLabels' => self::CORE_FIELD_LABELS,
            'typesWithOptions' => self::TYPES_WITH_OPTIONS,
            'validations' => self::TYPE_VALIDATIONS,
            'patterns' => $patterns,
            'mapTargets' => self::MAP_TARGETS,
            'mappableTypes' => self::MAPPABLE_TYPES,
            'fileExtensions' => self::ALLOWED_FILE_EXTENSIONS,
            'maxFileSizeMb' => self::MAX_FILE_SIZE_MB,
        ];
    }
}
