<?php

namespace src;

use Database;
use Security;

/**
 * Personalización de la apariencia del portal (colores, tipografía, cabecera, menú,
 * pie de página, login, registro y logo).
 *
 * La configuración se guarda en plugin_proikos_appearance. Al guardar se genera un
 * CSS estático y un archivo state.json en app/upload/plugins/proikos/appearance/,
 * que es lo único que se lee en cada página (sin consultas a la base de datos).
 */
class AppearanceManager
{
    const TABLE = 'plugin_proikos_appearance';
    const MAX_IMAGE_MB = 3;
    const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico'];

    /** Fuentes disponibles: clave => [nombre CSS, familia de Google Fonts o null]. */
    const FONTS = [
        '' => ['Predeterminada del tema', null],
        'oxygen' => ["'Oxygen', sans-serif", 'Oxygen:wght@300;400;700'],
        'roboto' => ["'Roboto', sans-serif", 'Roboto:wght@300;400;500;700'],
        'open_sans' => ["'Open Sans', sans-serif", 'Open+Sans:wght@300;400;600;700'],
        'lato' => ["'Lato', sans-serif", 'Lato:wght@300;400;700'],
        'montserrat' => ["'Montserrat', sans-serif", 'Montserrat:wght@300;400;600;700'],
        'poppins' => ["'Poppins', sans-serif", 'Poppins:wght@300;400;600;700'],
        'nunito' => ["'Nunito', sans-serif", 'Nunito:wght@300;400;600;700'],
        'inter' => ["'Inter', sans-serif", 'Inter:wght@300;400;600;700'],
        'source_sans' => ["'Source Sans 3', sans-serif", 'Source+Sans+3:wght@300;400;600;700'],
        'raleway' => ["'Raleway', sans-serif", 'Raleway:wght@300;400;600;700'],
        'ubuntu' => ["'Ubuntu', sans-serif", 'Ubuntu:wght@300;400;500;700'],
        'merriweather' => ["'Merriweather', serif", 'Merriweather:wght@300;400;700'],
        'playfair' => ["'Playfair Display', serif", 'Playfair+Display:wght@400;600;700'],
        'system' => ["-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif", null],
    ];

    /** @var array|null Estado publicado (cache por petición) */
    private static $state = null;

    /**
     * Esquema de controles de la barra lateral, agrupados por sección.
     * Tipos: color, image, select, range, toggle, text, textarea, code.
     */
    /**
     * Común a los estilos modernos del menú: opciones centradas en la
     * cabecera, submenús redondeados con sombra y avatar con aro.
     */
    const MENU_STYLE_BASE = <<<'CSS'
/* Menú moderno — base */
.navbar-default .navbar-nav > li > a {
    position: relative;
    margin: 17px 2px;
    padding: 8px 14px;
    line-height: 20px;
    font-weight: 600;
    letter-spacing: .02em;
    border-radius: 999px;
    transition: background-color .15s ease, color .15s ease;
}
.navbar-default .navbar-nav > li.avatar-user > a.dropdown-toggle {
    margin: 13px 0 13px 8px;
    padding: 4px 8px 4px 4px;
    background-color: transparent;
}
.avatar-user > a.dropdown-toggle img.img-circle {
    width: 36px;
    height: 36px;
    border: 2px solid #fff;
    box-shadow: 0 0 0 1px rgba(15, 35, 60, .12), 0 2px 6px rgba(15, 35, 60, .12);
}
.navbar-nav > li > .dropdown-menu {
    margin-top: 6px;
    padding: 6px;
    border: 1px solid rgba(15, 35, 60, .08);
    border-radius: 12px;
    box-shadow: 0 12px 32px rgba(15, 35, 60, .16);
}
.navbar-nav > li > .dropdown-menu > li > a {
    padding: 8px 12px;
    border-radius: 8px;
}
.navbar-nav > li > .dropdown-menu > li > a:hover,
.navbar-nav > li > .dropdown-menu > li > a:focus {
    background-color: rgba(0, 138, 209, .08);
}
@media (max-width: 767px) {
    .navbar-default .navbar-nav > li > a {
        margin: 2px 0;
        padding: 10px 14px;
        border-radius: 8px;
    }
}
CSS;

    /** Forma y colores por defecto de cada estilo moderno del menú. */
    const MENU_STYLES = [
        'pill' => <<<'CSS'
/* Menú moderno — pastillas */
.navbar-default .navbar-nav > li > a {
    color: #33475b;
}
.navbar-default .navbar-nav > li > a:hover,
.navbar-default .navbar-nav > li > a:focus {
    color: #006fa8;
    background-color: rgba(0, 138, 209, .12);
}
.navbar-default .navbar-nav > .active > a,
.navbar-default .navbar-nav > .active > a:hover,
.navbar-default .navbar-nav > .active > a:focus,
.navbar-default .navbar-nav > .open > a,
.navbar-default .navbar-nav > .open > a:hover,
.navbar-default .navbar-nav > .open > a:focus {
    color: #fff;
    background-color: #008ad1;
    box-shadow: 0 4px 12px rgba(0, 138, 209, .3);
}
CSS,
        'underline' => <<<'CSS'
/* Menú moderno — línea inferior */
.navbar-default .navbar-nav > li > a {
    color: #33475b;
    border-radius: 0;
}
.navbar-default .navbar-nav > li > a::after {
    content: '';
    position: absolute;
    left: 14px;
    right: 14px;
    bottom: -2px;
    height: 3px;
    border-radius: 3px;
    background-color: currentColor;
    transform: scaleX(0);
    transition: transform .2s ease;
}
.navbar-default .navbar-nav > li > a:hover,
.navbar-default .navbar-nav > li > a:focus,
.navbar-default .navbar-nav > .active > a,
.navbar-default .navbar-nav > .active > a:hover,
.navbar-default .navbar-nav > .active > a:focus,
.navbar-default .navbar-nav > .open > a,
.navbar-default .navbar-nav > .open > a:hover,
.navbar-default .navbar-nav > .open > a:focus {
    color: #006fa8;
    background-color: transparent;
}
.navbar-default .navbar-nav > li > a:hover::after,
.navbar-default .navbar-nav > .active > a::after {
    transform: scaleX(1);
}
.navbar-default .navbar-nav > li.avatar-user > a::after {
    display: none;
}
@media (max-width: 767px) {
    .navbar-default .navbar-nav > li > a::after {
        left: 0;
        right: auto;
        top: 8px;
        bottom: 8px;
        width: 3px;
        height: auto;
        transform: scaleY(0);
    }
    .navbar-default .navbar-nav > li > a:hover::after,
    .navbar-default .navbar-nav > .active > a::after {
        transform: scaleY(1);
    }
}
CSS,
    ];

    public static function schema(): array
    {
        $fonts = [];
        foreach (self::FONTS as $key => $font) {
            $fonts[$key] = '' === $key ? $font[0] : trim(explode(',', $font[0])[0], "'");
        }

        return [
            'identity' => [
                'title' => 'Logo e identidad',
                'icon' => 'fa-picture-o',
                'controls' => [
                    'logo' => ['type' => 'image', 'label' => 'Logo del portal', 'help' => 'Medida recomendada: 300 × 170 px. PNG, SVG, JPG o WEBP. Se usa en el login, el registro y la recuperación de contraseña.'],
                    'header_logo' => ['type' => 'image', 'label' => 'Logo de la cabecera (menú)', 'help' => 'Medida recomendada: 300 × 100 px (horizontal, más ancho que alto). PNG, SVG, JPG o WEBP. Se usa en la barra del menú; si no se sube, se usa el logo del portal.'],
                    'logo_width' => ['type' => 'range', 'label' => 'Ancho del logo en la cabecera', 'min' => 80, 'max' => 400, 'step' => 5, 'unit' => 'px'],
                    'favicon' => ['type' => 'image', 'label' => 'Ícono de la pestaña (favicon)', 'help' => 'PNG o ICO cuadrado, 32×32 o 64×64.'],
                ],
            ],
            'typography' => [
                'title' => 'Tipografía',
                'icon' => 'fa-font',
                'controls' => [
                    'font_body' => ['type' => 'select', 'label' => 'Fuente del texto', 'options' => $fonts],
                    'font_headings' => ['type' => 'select', 'label' => 'Fuente de los títulos', 'options' => $fonts],
                    'font_size' => ['type' => 'range', 'label' => 'Tamaño base del texto', 'min' => 12, 'max' => 18, 'step' => 1, 'unit' => 'px'],
                ],
            ],
            'colors' => [
                'title' => 'Colores generales',
                'icon' => 'fa-tint',
                'controls' => [
                    'color_primary' => ['type' => 'color', 'label' => 'Color principal (botones)'],
                    'color_primary_text' => ['type' => 'color', 'label' => 'Texto de botones principales'],
                    'color_link' => ['type' => 'color', 'label' => 'Enlaces'],
                    'color_link_hover' => ['type' => 'color', 'label' => 'Enlaces al pasar el mouse'],
                    'color_text' => ['type' => 'color', 'label' => 'Texto'],
                    'color_body_bg' => ['type' => 'color', 'label' => 'Fondo de las páginas'],
                ],
            ],
            'content' => [
                'title' => 'Contenido de las páginas',
                'icon' => 'fa-columns',
                'controls' => [
                    'breadcrumb_bg' => ['type' => 'color', 'label' => 'Barra de ruta / breadcrumb (ej. "Mis cursos")'],
                    'breadcrumb_text' => ['type' => 'color', 'label' => 'Texto del breadcrumb'],
                    'panel_heading_bg' => ['type' => 'color', 'label' => 'Encabezados del menú lateral (ej. "Perfil")'],
                    'panel_heading_text' => ['type' => 'color', 'label' => 'Texto de los encabezados laterales'],
                    'panel_border' => ['type' => 'color', 'label' => 'Borde de los paneles'],
                    'course_card_bg' => ['type' => 'color', 'label' => 'Fondo de las tarjetas de curso'],
                    'course_card_hover_bg' => ['type' => 'color', 'label' => 'Fondo de las tarjetas al pasar el mouse', 'help' => 'Solo en las tarjetas de cursos de una sesión.'],
                    'course_card_border' => ['type' => 'color', 'label' => 'Borde de las tarjetas de curso'],
                    'course_card_hover_border' => ['type' => 'color', 'label' => 'Borde de las tarjetas al pasar el mouse'],
                    'course_card_title' => ['type' => 'color', 'label' => 'Título de las tarjetas de curso'],
                    'course_card_image' => ['type' => 'image', 'label' => 'Imagen decorativa de las tarjetas', 'help' => 'Se muestra a la derecha de la tarjeta, en lugar de la trama del tema.'],
                    'course_card_hide_image' => ['type' => 'toggle', 'label' => 'Quitar imagen decorativa', 'default' => false],
                ],
            ],
            'header' => [
                'title' => 'Cabecera',
                'icon' => 'fa-window-maximize',
                'controls' => [
                    'header_bg' => ['type' => 'color', 'label' => 'Fondo de la cabecera'],
                    'header_border' => ['type' => 'color', 'label' => 'Línea inferior'],
                    'header_shadow' => ['type' => 'toggle', 'label' => 'Sombra', 'default' => true],
                ],
            ],
            'menu' => [
                'title' => 'Menú',
                'icon' => 'fa-bars',
                'controls' => [
                    'menu_style' => ['type' => 'select', 'label' => 'Estilo del menú', 'options' => [
                        '' => 'Clásico del tema (bloques)',
                        'pill' => 'Moderno: pastillas redondeadas',
                        'underline' => 'Moderno: línea inferior',
                    ]],
                    'menu_text' => ['type' => 'color', 'label' => 'Texto del menú'],
                    'menu_hover_bg' => ['type' => 'color', 'label' => 'Fondo al pasar el mouse'],
                    'menu_hover_text' => ['type' => 'color', 'label' => 'Texto al pasar el mouse'],
                    'menu_active_bg' => ['type' => 'color', 'label' => 'Fondo de la opción activa'],
                    'menu_active_text' => ['type' => 'color', 'label' => 'Texto de la opción activa'],
                    'menu_font_size' => ['type' => 'range', 'label' => 'Tamaño del texto', 'min' => 11, 'max' => 18, 'step' => 1, 'unit' => 'px'],
                    'menu_uppercase' => ['type' => 'toggle', 'label' => 'Texto en mayúsculas', 'default' => true],
                ],
            ],
            'footer' => [
                'title' => 'Pie de página',
                'icon' => 'fa-window-minimize',
                'controls' => [
                    'footer_bg' => ['type' => 'color', 'label' => 'Fondo'],
                    'footer_inner_bg' => ['type' => 'color', 'label' => 'Fondo del contenido'],
                    'footer_text' => ['type' => 'color', 'label' => 'Texto'],
                    'footer_link' => ['type' => 'color', 'label' => 'Enlaces'],
                    'footer_link_hover' => ['type' => 'color', 'label' => 'Enlaces al pasar el mouse'],
                    'footer_html' => ['type' => 'textarea', 'label' => 'Texto adicional', 'help' => 'Dirección, teléfonos, redes… Se permite HTML básico (enlaces, negrita).', 'live' => 'footer_html'],
                ],
            ],
            'login' => [
                'title' => 'Página de login',
                'icon' => 'fa-sign-in',
                'preview' => 'login',
                'controls' => [
                    'login_bg_color' => ['type' => 'color', 'label' => 'Color de fondo'],
                    'login_bg_image' => ['type' => 'image', 'label' => 'Imagen de fondo'],
                    'login_panel_bg' => ['type' => 'color', 'label' => 'Fondo del panel izquierdo'],
                    'login_form_bg' => ['type' => 'color', 'label' => 'Fondo del formulario'],
                    'login_title_color' => ['type' => 'color', 'label' => 'Color del título'],
                    'login_desc_color' => ['type' => 'color', 'label' => 'Color de la descripción'],
                    'login_label_color' => ['type' => 'color', 'label' => 'Color de etiquetas'],
                    'login_btn_bg' => ['type' => 'color', 'label' => 'Botón "Iniciar sesión"'],
                    'login_btn_text' => ['type' => 'color', 'label' => 'Texto del botón'],
                    'login_register_color' => ['type' => 'color', 'label' => 'Botón "Registro de usuario"'],
                    'login_title' => ['type' => 'text', 'label' => 'Título', 'placeholder' => 'Bienvenidos a …', 'live' => 'login_title'],
                    'login_description' => ['type' => 'textarea', 'label' => 'Descripción', 'live' => 'login_description'],
                    'login_hide_description' => ['type' => 'toggle', 'label' => 'Ocultar descripción', 'default' => false],
                    'login_message' => ['type' => 'textarea', 'label' => 'Mensaje inferior', 'live' => 'login_message'],
                    'login_character' => ['type' => 'image', 'label' => 'Imagen del panel izquierdo'],
                    'login_hide_character' => ['type' => 'toggle', 'label' => 'Ocultar imagen del panel', 'default' => false],
                ],
            ],
            'register' => [
                'title' => 'Formulario de registro',
                'icon' => 'fa-user-plus',
                'preview' => 'register',
                'controls' => [
                    'register_bg_color' => ['type' => 'color', 'label' => 'Color de fondo'],
                    'register_bg_image' => ['type' => 'image', 'label' => 'Imagen de fondo'],
                    'register_card_bg' => ['type' => 'color', 'label' => 'Fondo del formulario'],
                    'register_title_color' => ['type' => 'color', 'label' => 'Color del título'],
                    'register_title_size' => ['type' => 'range', 'label' => 'Tamaño del título', 'min' => 16, 'max' => 40, 'step' => 1, 'unit' => 'px'],
                    'register_label_color' => ['type' => 'color', 'label' => 'Color de etiquetas'],
                    'register_input_border' => ['type' => 'color', 'label' => 'Borde de los campos'],
                    'register_section_color' => ['type' => 'color', 'label' => 'Títulos de sección'],
                    'register_section_bg' => ['type' => 'color', 'label' => 'Fondo del encabezado de sección', 'help' => 'Si se deja vacío, se usa un tono suave del color de los títulos de sección.'],
                    'register_steps_color' => ['type' => 'color', 'label' => 'Pasos (formulario paso a paso)', 'help' => 'Círculos y línea de avance. Si se deja vacío, se usa el color del botón de envío.'],
                    'register_btn_bg' => ['type' => 'color', 'label' => 'Botón de envío'],
                    'register_btn_text' => ['type' => 'color', 'label' => 'Texto del botón'],
                ],
            ],
            'advanced' => [
                'title' => 'CSS personalizado',
                'icon' => 'fa-code',
                'controls' => [
                    'custom_css' => ['type' => 'code', 'label' => 'Reglas CSS adicionales', 'help' => 'Se aplican al final, sobre todo lo demás.'],
                ],
            ],
        ];
    }

    /** Lista plana clave => definición de control. */
    public static function controls(): array
    {
        $controls = [];
        foreach (self::schema() as $section) {
            foreach ($section['controls'] as $key => $control) {
                $controls[$key] = $control;
            }
        }

        return $controls;
    }

    /* ---------------------------------------------------------------------
     * Persistencia
     * ------------------------------------------------------------------- */

    public static function ensureTable()
    {
        Database::query("CREATE TABLE IF NOT EXISTS ".self::TABLE." (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            settings LONGTEXT NULL,
            version INT UNSIGNED NOT NULL DEFAULT 0,
            updated_by INT NULL,
            updated_at DATETIME NULL
        ) DEFAULT CHARSET=utf8mb4");
    }

    public static function getSettings(): array
    {
        $row = Database::fetch_assoc(Database::query("SELECT settings FROM ".self::TABLE." ORDER BY id LIMIT 1"));
        $settings = $row ? json_decode($row['settings'], true) : [];

        return is_array($settings) ? $settings : [];
    }

    /**
     * Guarda la configuración, regenera el CSS publicado y el estado.
     */
    public static function save(array $input): array
    {
        $settings = self::sanitize($input);
        $row = Database::fetch_assoc(Database::query("SELECT id, version FROM ".self::TABLE." ORDER BY id LIMIT 1"));
        $version = $row ? ((int) $row['version'] + 1) : 1;
        $params = [
            'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'version' => $version,
            'updated_by' => api_get_user_id(),
            'updated_at' => api_get_utc_datetime(),
        ];
        if ($row) {
            Database::update(self::TABLE, $params, ['id = ?' => (int) $row['id']]);
        } else {
            Database::insert(self::TABLE, $params);
        }

        self::publish($settings, $version);

        return $settings;
    }

    /**
     * Escribe el CSS y el state.json que leen las páginas, y borra archivos sin uso.
     */
    private static function publish(array $settings, int $version)
    {
        $dir = self::getDir();
        $css = self::buildCss($settings);
        $cssName = '';
        if ('' !== trim($css)) {
            $cssName = 'portal-'.$version.'.css';
            file_put_contents($dir.$cssName, $css);
        }

        $state = [
            'version' => $version,
            'css' => $cssName,
            'favicon' => $settings['favicon'] ?? '',
            'vars' => self::templateVars($settings),
        ];
        file_put_contents($dir.'state.json', json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // Limpiar CSS antiguos e imágenes que ya no se usan.
        $used = array_filter([$cssName, 'state.json', 'index.html']);
        foreach (self::controls() as $key => $control) {
            if ('image' === $control['type'] && !empty($settings[$key])) {
                $used[] = basename($settings[$key]);
            }
        }
        foreach (scandir($dir) as $file) {
            if ('.' === $file[0] || in_array($file, $used, true)) {
                continue;
            }
            // Conservar subidas recientes (pueden ser parte de un borrador sin guardar).
            if (0 === strpos($file, 'img-') && filemtime($dir.$file) > time() - 86400) {
                continue;
            }
            @unlink($dir.$file);
        }
        self::$state = null;
    }

    public static function getDir(): string
    {
        $dir = api_get_path(SYS_UPLOAD_PATH).'plugins/proikos/appearance/';
        if (!is_dir($dir)) {
            mkdir($dir, api_get_permissions_for_new_directories(), true);
            file_put_contents($dir.'index.html', '');
        }

        return $dir;
    }

    public static function getWebDir(): string
    {
        return api_get_path(WEB_UPLOAD_PATH).'plugins/proikos/appearance/';
    }

    /* ---------------------------------------------------------------------
     * Saneamiento
     * ------------------------------------------------------------------- */

    public static function sanitize(array $input): array
    {
        $clean = [];
        $webDir = self::getWebDir();
        foreach (self::controls() as $key => $control) {
            if (!array_key_exists($key, $input)) {
                continue;
            }
            $value = $input[$key];
            switch ($control['type']) {
                case 'color':
                    $value = self::sanitizeColor($value);
                    break;
                case 'range':
                    $value = '' === $value || null === $value ? '' : (int) $value;
                    if ('' !== $value) {
                        $value = max($control['min'], min($control['max'], $value));
                    }
                    break;
                case 'toggle':
                    // Solo se guarda si difiere del valor por defecto del tema.
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    if ($value !== ($control['default'] ?? false)) {
                        $clean[$key] = $value;
                    }
                    continue 2;
                case 'select':
                    $value = (string) $value;
                    if (!isset($control['options'][$value])) {
                        $value = '';
                    }
                    break;
                case 'image':
                    // Solo imágenes subidas a la carpeta de apariencia.
                    $value = (string) $value;
                    if (0 !== strpos($value, $webDir) || !preg_match('/^img-[a-f0-9]{16}\.[a-z]{3,4}$/', basename($value))) {
                        $value = '';
                    }
                    break;
                case 'text':
                    $value = mb_substr(trim(strip_tags((string) $value)), 0, 200);
                    break;
                case 'textarea':
                    $value = trim(Security::remove_XSS((string) $value));
                    $value = mb_substr($value, 0, 2000);
                    break;
                case 'code':
                    $value = self::sanitizeCss((string) $value);
                    break;
            }
            if ('' !== $value && null !== $value) {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    public static function sanitizeColor($value): string
    {
        $value = trim((string) $value);
        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value)) {
            return strtolower($value);
        }
        if (preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+)\s*)?\)$/', $value)) {
            return $value;
        }

        return '';
    }

    /**
     * El CSS personalizado se publica como archivo .css: se impide cerrar una
     * etiqueta <style> (vista previa) y cargar recursos con @import / expression().
     */
    public static function sanitizeCss(string $css): string
    {
        $css = str_ireplace(['</style', '<style', '<script', '</script'], '', $css);
        $css = preg_replace('/@import[^;]*;?/i', '', $css);
        $css = preg_replace('/expression\s*\(/i', '(', $css);
        $css = preg_replace('/javascript\s*:/i', '', $css);

        return mb_substr(trim($css), 0, 20000);
    }

    /* ---------------------------------------------------------------------
     * Generación de CSS
     * ------------------------------------------------------------------- */

    public static function buildCss(array $s): string
    {
        $css = [];
        $imports = [];
        $add = function (string $selector, array $declarations) use (&$css) {
            $lines = [];
            foreach ($declarations as $property => $value) {
                if (null !== $value && '' !== $value) {
                    $lines[] = '    '.$property.': '.$value.';';
                }
            }
            if ($lines) {
                $css[] = $selector." {\n".implode("\n", $lines)."\n}";
            }
        };
        $get = function ($key) use ($s) {
            return $s[$key] ?? null;
        };
        $px = function ($key) use ($s) {
            return isset($s[$key]) && '' !== $s[$key] ? ((int) $s[$key]).'px' : null;
        };
        $url = function ($key) use ($s) {
            return !empty($s[$key]) ? 'url("'.str_replace(['"', '\\'], '', $s[$key]).'")' : null;
        };

        // Tipografía
        $font = function ($key) use ($s, &$imports) {
            if (empty($s[$key]) || !isset(self::FONTS[$s[$key]])) {
                return null;
            }
            [$family, $googleFamily] = self::FONTS[$s[$key]];
            if ($googleFamily) {
                $imports[$googleFamily] = true;
            }

            return $family;
        };
        $fontBody = $font('font_body');
        $fontHeadings = $font('font_headings');
        $add('body, .custompage, button, input, select, textarea, .btn, .form-control, .navbar-default .navbar-nav > li > a', [
            'font-family' => $fontBody,
        ]);
        $add('body', [
            'font-size' => $px('font_size'),
            'color' => $get('color_text'),
            'background' => $get('color_body_bg'),
        ]);
        $add('h1, h2, h3, h4, h5, h6, .custompage h3.title, .page-header h1, .page-header h2', [
            'font-family' => $fontHeadings,
        ]);

        // Colores generales
        $add('a', ['color' => $get('color_link')]);
        $add('a:hover, a:focus', ['color' => $get('color_link_hover')]);
        $add('.btn-primary, .btn-primary:focus, .custompage .btn-primary', [
            'background-color' => $get('color_primary'),
            'border-color' => $get('color_primary'),
            'color' => $get('color_primary_text'),
        ]);
        if ($get('color_primary')) {
            $add('.btn-primary:hover, .btn-primary:active, .custompage .btn-primary:hover', [
                'background-color' => $get('color_primary'),
                'border-color' => $get('color_primary'),
                'color' => $get('color_primary_text'),
                'filter' => 'brightness(0.92)',
            ]);
        }

        // Contenido
        $add('.breadcrumb', [
            'background-color' => $get('breadcrumb_bg'),
            'color' => $get('breadcrumb_text'),
        ]);
        $add('.breadcrumb a, .breadcrumb > .active, .breadcrumb > li + li::before', ['color' => $get('breadcrumb_text')]);
        $add('.sidebar .panel-default .panel-heading', ['background' => $get('panel_heading_bg')]);
        $add('.sidebar .panel-default .panel-heading a, .sidebar .panel-default .panel-heading .panel-title', ['color' => $get('panel_heading_text')]);
        $add('.panel-default, .panel-default > .panel-heading', ['border-color' => $get('panel_border')]);

        // Tarjetas de curso (lista clásica y cursos dentro de una sesión)
        $cardImage = !empty($s['course_card_hide_image']) ? 'none' : $url('course_card_image');
        $add('.classic-courses .panel-default, .session.panel-body .sessions-items .courses', [
            'background-color' => $get('course_card_bg'),
            'background-image' => $cardImage,
            'border-color' => $get('course_card_border'),
        ]);
        // El tema redefine el fondo completo al pasar el mouse; se repiten imagen y color.
        $add('.session.panel-body .sessions-items .courses:hover', [
            'background-color' => $get('course_card_hover_bg') ?: $get('course_card_bg'),
            'background-image' => $cardImage,
        ]);
        $add('.classic-courses .panel-default:hover, .session.panel-body .sessions-items .courses:hover', [
            'border-color' => $get('course_card_hover_border'),
        ]);
        $add('.classic-courses .course-items-title, .classic-courses .course-items-title a, .session.panel-body .sessions-items .courses h4 a', [
            'color' => $get('course_card_title'),
        ]);

        // Cabecera
        $add('#cm-header', [
            'background' => $get('header_bg'),
            'border-bottom-color' => $get('header_border'),
            'box-shadow' => isset($s['header_shadow']) && false === $s['header_shadow'] ? 'none' : null,
        ]);
        $add('#cm-header .logo_campus img, #cm-header .pk-header-logo', [
            'width' => $px('logo_width'),
            'height' => $px('logo_width') ? 'auto' : null,
        ]);

        // Menú: primero la forma del estilo elegido; los colores del
        // administrador van después y, con la misma especificidad, mandan.
        if (isset(self::MENU_STYLES[$s['menu_style'] ?? ''])) {
            $css[] = self::MENU_STYLE_BASE."\n".self::MENU_STYLES[$s['menu_style']];
        }
        $add('.navbar-default .navbar-nav > li > a', [
            'color' => $get('menu_text'),
            'font-size' => $px('menu_font_size'),
            'text-transform' => isset($s['menu_uppercase']) && false === $s['menu_uppercase'] ? 'none' : null,
        ]);
        $add('.navbar-default .navbar-nav > li > a:hover, .navbar-default .navbar-nav > li > a:focus', [
            'background-color' => $get('menu_hover_bg'),
            'color' => $get('menu_hover_text'),
        ]);
        $add('.navbar-default .navbar-nav > .active > a, .navbar-default .navbar-nav > .active > a:hover, .navbar-default .navbar-nav > .active > a:focus, .navbar-default .navbar-nav > .open > a, .navbar-default .navbar-nav > .open > a:hover, .navbar-default .navbar-nav > .open > a:focus', [
            'background-color' => $get('menu_active_bg'),
            'color' => $get('menu_active_text'),
        ]);

        // Pie de página
        $add('.footer', [
            'background-color' => $get('footer_bg'),
            'color' => $get('footer_text'),
        ]);
        $add('.footer > .container', ['background' => $get('footer_inner_bg')]);
        $add('.footer a', ['color' => $get('footer_link')]);
        $add('.footer a:hover', ['color' => $get('footer_link_hover')]);

        // Login
        $add('.custompage .container-login:has(.width-login), .custompage .container-login:has(.width-lost-password)', [
            'background-color' => $get('login_bg_color'),
            'background-image' => $url('login_bg_image') ?: ($get('login_bg_color') ? 'none' : null),
            'background-size' => $url('login_bg_image') ? 'cover' : null,
            'background-position' => $url('login_bg_image') ? 'center' : null,
        ]);
        $add('.custompage #vegas', ['background' => $get('login_panel_bg')]);
        $add('.custompage .width-login, .custompage .width-login .padding-login, .custompage .width-lost-password .padding-login', [
            'background' => $get('login_form_bg'),
        ]);
        $add('.custompage .width-login h3.title, .custompage .title-lost-password', ['color' => $get('login_title_color')]);
        $add('.custompage #vegas .description', ['color' => $get('login_desc_color')]);
        $add('.custompage .width-login label, .custompage .width-lost-password label', ['color' => $get('login_label_color')]);
        $add('.custompage .width-login .btn-primary, .custompage .width-lost-password .btn-primary', [
            'background-color' => $get('login_btn_bg'),
            'border-color' => $get('login_btn_bg'),
            'color' => $get('login_btn_text'),
        ]);
        $add('.custompage .width-login .last-password a', ['color' => $get('login_btn_bg')]);
        if ($get('login_register_color')) {
            $add('.custompage .width-login .btn-success', [
                'color' => $get('login_register_color'),
                'border-color' => $get('login_register_color'),
                'background-color' => 'transparent',
            ]);
            $add('.custompage .width-login .btn-success:hover', [
                'background-color' => $get('login_register_color'),
                'color' => '#fff',
            ]);
        }
        // El tema fija la altura de la tarjeta; con otro logo, fuente o textos el contenido desborda.
        foreach (['logo', 'font_body', 'font_headings', 'login_title', 'login_description', 'login_message', 'login_character'] as $key) {
            if (!empty($s[$key])) {
                $add('.custompage .width-login', ['height' => 'auto', 'min-height' => '590px']);
                $add('.custompage .width-login > form', ['margin-bottom' => '0']);
                // La fila ocupa toda la altura mínima de la tarjeta; si no, queda una franja blanca abajo.
                $add('.custompage .width-login > form > .row', ['display' => 'flex', 'flex-wrap' => 'wrap', 'min-height' => '590px']);
                $add('.custompage .width-login #vegas', ['height' => '100%', 'border-bottom-left-radius' => '10px']);
                break;
            }
        }
        $add('.custompage #vegas .character', [
            'display' => !empty($s['login_hide_character']) ? 'none' : null,
        ]);
        $add('.custompage .width-login #vegas .description', [
            'display' => !empty($s['login_hide_description']) ? 'none' : null,
        ]);
        // Imagen propia del panel: ocupa todo el ancho y queda pegada al borde inferior.
        if (!empty($s['login_character']) && empty($s['login_hide_character'])) {
            $add('.custompage .width-login #vegas', [
                'flex-direction' => 'column',
                'flex-wrap' => 'nowrap',
                'justify-content' => 'flex-start',
                'align-items' => 'stretch',
                'overflow' => 'hidden',
            ]);
            $add('.custompage .width-login #vegas .character', [
                'width' => '100%',
                'margin-top' => 'auto',
                'font-size' => '0',
                'line-height' => '0',
            ]);
            $add('.custompage .width-login #vegas .character img', [
                'display' => 'block !important',
                'width' => '100%',
                'max-width' => '100%',
                'height' => 'auto',
            ]);
            // Sin descripción, el logo se centra en el espacio libre sobre la imagen.
            if (!empty($s['login_hide_description'])) {
                $add('.custompage .width-login #vegas .logo', [
                    'flex' => '1 1 auto',
                    'display' => 'flex',
                    'align-items' => 'center',
                    'justify-content' => 'center',
                ]);
            }
        }

        // Registro
        $add('.custompage .container-login:has(.width-register)', [
            'background-color' => $get('register_bg_color'),
            'background-image' => $url('register_bg_image') ?: ($get('register_bg_color') ? 'none' : null),
            'background-size' => $url('register_bg_image') ? 'cover' : null,
            'background-position' => $url('register_bg_image') ? 'center' : null,
        ]);
        $add('.custompage .width-register', ['background' => $get('register_card_bg')]);
        $add('.custompage .width-register h3.title', [
            'color' => $get('register_title_color'),
            'font-size' => $px('register_title_size'),
        ]);
        // Indicador del formulario paso a paso (usa la variable --pk-accent del formulario).
        $stepsColor = $get('register_steps_color') ?: $get('register_btn_bg');
        $add('.proikos-reg-form .pk-rf-steps', ['--pk-accent' => $stepsColor]);
        $add('.proikos-reg-form .pk-rf-steps li.pk-active .pk-rf-step-num', [
            'box-shadow' => $stepsColor ? '0 0 0 4px color-mix(in srgb, '.$stepsColor.' 18%, transparent)' : null,
        ]);
        // El sufijo «(vista previa)» acompaña al tamaño elegido.
        $add('.custompage .width-register h3.title small', ['font-size' => $px('register_title_size') ? '55%' : null]);
        $add('.custompage .width-register label, .custompage .width-register .control-label', ['color' => $get('register_label_color')]);
        $add('.custompage .width-register .form-control, .custompage .width-register .form-control.bootstrap-select .dropdown-toggle.btn-default', [
            'border-color' => $get('register_input_border'),
        ]);
        $add('.custompage .width-register .pk-rf-section h4', ['color' => $get('register_section_color')]);
        // La banda, su borde y la barra lateral de cada sección salen de este color.
        $add('.custompage .width-register .proikos-reg-form', ['--pk-section' => $get('register_section_color')]);
        $add('.custompage .width-register .proikos-reg-form .pk-rf-section', ['background' => $get('register_section_bg')]);
        $add('.width-register .panel-user', ['background-color' => $get('register_section_color') ? $get('register_section_color').' !important' : null]);
        $add('.custompage .width-register .btn-primary', [
            'background-color' => $get('register_btn_bg'),
            'border-color' => $get('register_btn_bg'),
            'color' => $get('register_btn_text'),
        ]);

        // Pie de página adicional
        if (!empty($s['footer_html'])) {
            $css[] = ".pk-footer-text {\n    padding: 8px 0;\n    font-size: 13px;\n}";
        }

        if (!empty($s['custom_css'])) {
            $css[] = "/* CSS personalizado */\n".$s['custom_css'];
        }

        if (empty($css)) {
            return '';
        }

        $header = "/* Apariencia personalizada — plugin Proikos (generado automáticamente) */\n";
        foreach (array_keys($imports) as $family) {
            $header .= "@import url('https://fonts.googleapis.com/css2?family=".$family."&display=swap');\n";
        }

        return $header."\n".implode("\n\n", $css)."\n";
    }

    /**
     * Variables para las plantillas (logo, textos e imágenes de login, pie de página).
     */
    public static function templateVars(array $s): array
    {
        return array_filter([
            'logo' => $s['logo'] ?? '',
            'header_logo' => $s['header_logo'] ?? '',
            'login_title' => $s['login_title'] ?? '',
            'login_description' => $s['login_description'] ?? '',
            'login_message' => $s['login_message'] ?? '',
            'login_character' => $s['login_character'] ?? '',
            'footer_html' => $s['footer_html'] ?? '',
        ]);
    }

    /* ---------------------------------------------------------------------
     * Integración con las páginas
     * ------------------------------------------------------------------- */

    /**
     * Estado publicado, leído de state.json (sin consultas a la base de datos).
     */
    public static function getState(): array
    {
        if (null === self::$state) {
            self::$state = [];
            $file = api_get_path(SYS_UPLOAD_PATH).'plugins/proikos/appearance/state.json';
            if (is_file($file)) {
                $state = json_decode((string) file_get_contents($file), true);
                self::$state = is_array($state) ? $state : [];
            }
        }

        return self::$state;
    }

    /**
     * HTML para el final del <head>: CSS publicado, favicon y, para administradores,
     * el botón flotante de personalización.
     */
    public static function getHeadHtml(bool $withCustomizer): string
    {
        $state = self::getState();
        $html = '';
        if (!empty($state['css'])) {
            $html .= '<link rel="stylesheet" id="pk-appearance-css" href="'.self::getWebDir().$state['css'].'">'."\n";
        }
        if (!empty($state['favicon'])) {
            $html .= '<link rel="icon" href="'.htmlspecialchars($state['favicon']).'">'."\n";
        }
        if ($withCustomizer) {
            $pluginWeb = api_get_path(WEB_PLUGIN_PATH).'proikos/';
            $version = @filemtime(__DIR__.'/../js/appearance_customizer.js');
            $config = [
                'ajax' => $pluginWeb.'src/appearance_ajax.php',
                'preview' => [
                    'home' => api_get_path(WEB_PATH).'user_portal.php',
                    'login' => $pluginWeb.'appearance_preview.php?page=login',
                    'register' => $pluginWeb.'appearance_preview.php?page=register',
                ],
                'token' => Security::get_existing_token('pk_appearance'),
            ];
            $html .= '<link rel="stylesheet" href="'.$pluginWeb.'css/appearance_customizer.css?v='.$version.'">'."\n";
            $html .= '<script>window.PK_APPEARANCE = '.json_encode($config, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG).';</script>'."\n";
            $html .= '<script src="'.$pluginWeb.'js/appearance_customizer.js?v='.$version.'" defer></script>'."\n";
        }

        return $html;
    }

    /**
     * ¿Debe mostrarse la barra de personalización en esta página?
     */
    public static function customizerAllowed(): bool
    {
        return api_is_platform_admin()
            && 'true' === api_get_plugin_setting('proikos', 'enable_appearance_customizer');
    }

    /* ---------------------------------------------------------------------
     * Imágenes
     * ------------------------------------------------------------------- */

    /**
     * @return array ['url' => ...] o ['error' => ...]
     */
    public static function uploadImage(array $file): array
    {
        if (UPLOAD_ERR_OK !== ($file['error'] ?? UPLOAD_ERR_NO_FILE) || !is_uploaded_file($file['tmp_name'])) {
            return ['error' => 'No se pudo subir la imagen.'];
        }
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $error = self::checkImage((string) file_get_contents($file['tmp_name']), $extension);
        if ('' !== $error) {
            return ['error' => $error];
        }

        $name = 'img-'.bin2hex(random_bytes(8)).'.'.$extension;
        if (!move_uploaded_file($file['tmp_name'], self::getDir().$name)) {
            return ['error' => 'No se pudo guardar la imagen.'];
        }

        return ['url' => self::getWebDir().$name];
    }

    /**
     * Valida una imagen (subida o importada).
     *
     * @return string Mensaje de error, vacío si la imagen es válida
     */
    private static function checkImage(string $content, string $extension): string
    {
        if (!in_array($extension, self::IMAGE_EXTENSIONS, true)) {
            return 'Formato no permitido. Use: '.implode(', ', self::IMAGE_EXTENSIONS).'.';
        }
        if (strlen($content) > self::MAX_IMAGE_MB * 1024 * 1024) {
            return 'La imagen no debe superar '.self::MAX_IMAGE_MB.' MB.';
        }
        if ('svg' === $extension) {
            if (preg_match('/<script|on[a-z]+\s*=|javascript:|<foreignObject/i', $content)) {
                return 'El SVG contiene código no permitido.';
            }
        } elseif ('ico' !== $extension && false === @getimagesizefromstring($content)) {
            return 'El archivo no es una imagen válida.';
        }

        return '';
    }

    /* ---------------------------------------------------------------------
     * Exportar / importar
     * ------------------------------------------------------------------- */

    const EXPORT_FORMAT = 'proikos-appearance';
    const EXPORT_VERSION = 1;
    const EXPORT_JSON = 'appearance.json';
    const IMPORT_MAX_MB = 30;

    /**
     * Crea un ZIP con la configuración publicada y sus imágenes. Las imágenes se
     * guardan como rutas relativas (images/...) para poder usarlas en otro campus.
     *
     * @return string Ruta del ZIP temporal (el llamador debe borrarlo)
     */
    public static function exportPackage(): string
    {
        $settings = self::getSettings();
        $dir = self::getDir();
        $webDir = self::getWebDir();
        $images = [];

        foreach (self::controls() as $key => $control) {
            if ('image' !== $control['type'] || empty($settings[$key])) {
                continue;
            }
            $name = basename($settings[$key]);
            if (0 === strpos($settings[$key], $webDir) && is_file($dir.$name)) {
                $images[$name] = $dir.$name;
                $settings[$key] = 'images/'.$name;
            } else {
                unset($settings[$key]);
            }
        }

        $manifest = [
            'format' => self::EXPORT_FORMAT,
            'format_version' => self::EXPORT_VERSION,
            'exported_at' => gmdate('c'),
            'source' => api_get_path(WEB_PATH),
            'settings' => (object) $settings,
        ];

        $path = tempnam(sys_get_temp_dir(), 'pk-appearance-');
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString(self::EXPORT_JSON, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        foreach ($images as $name => $file) {
            $zip->addFile($file, 'images/'.$name);
        }
        $zip->close();

        return $path;
    }

    /**
     * Lee un ZIP exportado por exportPackage(). Las imágenes se validan y se copian
     * con un nombre nuevo; la configuración se devuelve saneada, sin guardarla, para
     * que el administrador la revise en la vista previa antes de publicar.
     *
     * @return array ['settings' => [...], 'ignored' => n] o ['error' => ...]
     */
    public static function importPackage(array $file): array
    {
        if (UPLOAD_ERR_OK !== ($file['error'] ?? UPLOAD_ERR_NO_FILE) || !is_uploaded_file($file['tmp_name'])) {
            return ['error' => 'No se pudo subir el archivo.'];
        }
        if ($file['size'] > self::IMPORT_MAX_MB * 1024 * 1024) {
            return ['error' => 'El archivo no debe superar '.self::IMPORT_MAX_MB.' MB.'];
        }

        $zip = new \ZipArchive();
        if (true !== $zip->open($file['tmp_name'])) {
            return ['error' => 'El archivo no es un ZIP válido.'];
        }

        // Los archivos se leen por nombre desde el ZIP, nunca se extraen a disco.
        $stat = $zip->statName(self::EXPORT_JSON);
        $manifest = $stat && $stat['size'] < 1024 * 1024 ? json_decode((string) $zip->getFromName(self::EXPORT_JSON), true) : null;
        if (!is_array($manifest) || self::EXPORT_FORMAT !== ($manifest['format'] ?? '') || !is_array($manifest['settings'] ?? null)) {
            $zip->close();

            return ['error' => 'El ZIP no contiene una configuración de apariencia exportada desde este plugin.'];
        }
        if ((int) ($manifest['format_version'] ?? 0) > self::EXPORT_VERSION) {
            $zip->close();

            return ['error' => 'La configuración se exportó con una versión más nueva del plugin. Actualice el plugin e intente de nuevo.'];
        }

        $settings = $manifest['settings'];
        $errors = [];
        foreach (self::controls() as $key => $control) {
            if ('image' !== $control['type'] || !isset($settings[$key])) {
                continue;
            }
            $entry = (string) $settings[$key];
            unset($settings[$key]);
            if (!preg_match('#^images/img-[a-f0-9]{16}\.([a-z]{3,4})$#', $entry, $match)) {
                continue;
            }
            $stat = $zip->statName($entry);
            if (!$stat || $stat['size'] > self::MAX_IMAGE_MB * 1024 * 1024) {
                $errors[] = $control['label'].': imagen no encontrada o demasiado grande.';
                continue;
            }
            $content = (string) $zip->getFromName($entry);
            $error = self::checkImage($content, $match[1]);
            if ('' !== $error) {
                $errors[] = $control['label'].': '.$error;
                continue;
            }
            $name = 'img-'.bin2hex(random_bytes(8)).'.'.$match[1];
            if (false === file_put_contents(self::getDir().$name, $content)) {
                $errors[] = $control['label'].': no se pudo guardar la imagen.';
                continue;
            }
            $settings[$key] = self::getWebDir().$name;
        }
        $zip->close();

        $clean = self::sanitize($settings);

        return [
            'settings' => $clean,
            'ignored' => max(0, count($manifest['settings']) - count($clean) - count($errors)),
            'errors' => $errors,
        ];
    }
}
