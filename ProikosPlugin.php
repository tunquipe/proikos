<?php

use ExtraField as ExtraFieldModel;
use Chamilo\CoreBundle\Entity\Repository\SequenceRepository;
use Chamilo\CoreBundle\Entity\Repository\SequenceResourceRepository;
use Chamilo\CoreBundle\Entity\Sequence;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CoreBundle\Entity\Session;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ProikosPlugin extends Plugin
{
    const TABLE_PROIKOS_CHECK_DOCS = 'plugin_proikos_check_docs';
    const TABLE_PROIKOS_DATA_LOG = 'plugin_proikos_data_log';
    const TABLE_PROIKOS_USERS = 'plugin_proikos_users';
    const TABLE_PROIKOS_ENTITY = 'plugin_proikos_entity';
    const TABLE_PROIKOS_SECTOR = 'plugin_proikos_sector';
    const TABLE_PROIKOS_POSITION = 'plugin_proikos_position';
    const TABLE_PROIKOS_AREA = 'plugin_proikos_area';
    const TABLE_PROIKOS_MANAGEMENT = 'plugin_proikos_management';
    const TABLE_PROIKOS_HEADQUARTERS = 'plugin_proikos_headquarters';
    const TABLE_PROIKOS_COMPANIES = 'plugin_proikos_companies';
    const TABLE_PROIKOS_MANAGERS = 'plugin_proikos_managers';
    const TABLE_PROIKOS_AREA_REF_MANAGEMENT = 'plugin_proikos_area_ref_management';
    const TABLE_PROIKOS_CONTRATING_COMPANIES = 'plugin_proikos_contrating_companies';
    const TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_CAB = 'plugin_proikos_contrating_companies_quota_cab';
    const TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_DET = 'plugin_proikos_contrating_companies_quota_det';
    const TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_SESSION = 'plugin_proikos_contrating_companies_quota_session';
    const TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_SESSION_DET = 'plugin_proikos_contrating_companies_quota_session_det';
    const TABLE_PLUGIN_EASY_CERTIFICATE_SEND = 'plugin_easycertificate_send';
    const CATEGORY_ASINCRONO = 1;
    const CATEGORY_SINCRONO = 2;
    const CATEGORY_DESC = [
        self::CATEGORY_ASINCRONO => 'Asincrónico',
        self::CATEGORY_SINCRONO => 'Sincrónico'
    ];
    const ATTACH_CERTIFICATES = [
        1 => 'Certificado Inducción',
        2 => 'Certificado IPERC'
    ];
    const ATTACH_CERTIFICATES_FILE_MODE = [
        1 => 'certificado-induccion',
        2 => 'certificado-iperc'
    ];

    const ATTACH_CERTIFICATES_ALTO_RIESGO = [
        1 => 'Trabajos en Caliente',
        2 => 'Trabajos en Altura',
        3 => 'Trabajos con Energías Peligrosas',
        4 => 'Trabajo en Espacio Confinado',
        5 => 'Trabajos en Excavaciones',
        6 => 'Trabajos en Gammagrafía',
        7 => 'Trabajos de Inmmersión'
    ];

    const ATTACH_CERTIFICATES_ALTO_RIESGO_FILE_MODE = [
        1 => 'trabajos-en-caliente',
        2 => 'trabajos-en-altura',
        3 => 'trabajos-con-energias-peligrosas',
        4 => 'trabajo-en-espacio-confinado',
        5 => 'trabajos-en-excavaciones',
        6 => 'trabajos-en-gammagrafia',
        7 => 'trabajos-de-inmmersion'
    ];

    const EVENT_ADD_QUOTA = 'add_quota';
    const EVENT_USER_SUBSCRIPTION_TO_COURSE = 'user_subscription_to_course';

    protected function __construct()
    {
        parent::__construct(
            '1.0',
            'Alex Aragon <alex.aragon@tunqui.pe>',
            [
                'tool_enable' => 'boolean',
                'enable_limit_user_quotas' => 'boolean',
                'enable_link_smowl_exercise' => 'boolean',
                'highest_score_exercise'  => 'boolean',
            ]
        );
        $this->isAdminPlugin = true;
    }

    /**
     * @return string
     */
    public function getToolTitle(): string
    {
        $title = $this->get_lang('tool_title');

        if (!empty($title)) {
            return $title;
        }

        return $this->get_title();
    }

    /**
     * @return ProikosPlugin
     */
    public static function create(): ProikosPlugin
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    public function install()
    {
        $schemaManager = Database::getManager()->getConnection()->getSchemaManager();

        $tablesExists = $schemaManager->tablesExist(
            [
                'plugin_proikos_area',
                'plugin_proikos_area_ref_management	',
                'plugin_proikos_companies',
                'plugin_proikos_entity',
                'plugin_proikos_headquarters',
                'plugin_proikos_management',
                'plugin_proikos_managers',
                'plugin_proikos_position',
                'plugin_proikos_sector',
                'plugin_proikos_users',
                self::TABLE_PROIKOS_CONTRATING_COMPANIES,
                self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_CAB,
                self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_DET
            ]
        );

        if ($tablesExists) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS ".self::TABLE_PROIKOS_USERS." (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            user_id INT NULL,
            lastname VARCHAR(250) NULL,
            firstname VARCHAR(250) NULL,
            phone VARCHAR(250) NULL,
            type_document INT NULL,
            number_document INT NULL,
            age INT NULL,
            gender VARCHAR(1) NULL,
            instruction VARCHAR(250) NULL,
            name_company VARCHAR(250) NULL,
            contact_manager VARCHAR(250) NULL,
            position_company VARCHAR(250) NULL,
            stakeholders VARCHAR(250) NULL,
            area VARCHAR(250) NULL,
            department VARCHAR(250) NULL,
            headquarters VARCHAR(250) NULL,
            code_reference VARCHAR(250) NULL
        )";
        Database::query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS ".self::TABLE_PROIKOS_ENTITY." (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            name_entity VARCHAR(250) NULL,
            business_name VARCHAR(250) NULL,
            ruc VARCHAR(20) NULL,
            tax_residence VARCHAR(250) NULL,
            economic_activity VARCHAR(250) NULL,
            number_of_workers INT NULL,
            picture VARCHAR(250) NULL,
            code_reference VARCHAR(250) NULL,
            status INT NULL
        )";
        Database::query($sql);

        //sectoss
        $sql = "CREATE TABLE IF NOT EXISTS ".self::TABLE_PROIKOS_SECTOR." (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            name_sector VARCHAR(250) NULL,
            status INT NULL
        )";

        Database::query($sql);

        //positions
        $sql = "CREATE TABLE IF NOT EXISTS ".self::TABLE_PROIKOS_POSITION." (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            name_position VARCHAR(250) NULL,
            status INT NULL
        )";
        Database::query($sql);

        //area
        $sql = "CREATE TABLE IF NOT EXISTS ".self::TABLE_PROIKOS_AREA."  (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            name_area VARCHAR(250) NULL,
            status INT NULL
        )";
        Database::query($sql);

        //gerencia
        $sql = "CREATE TABLE IF NOT EXISTS ".self::TABLE_PROIKOS_MANAGEMENT."  (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            name_management VARCHAR(250) NULL,
            status INT NULL
        )";
        Database::query($sql);

        //gerencia
        $sql = "CREATE TABLE IF NOT EXISTS plugin_proikos_area_ref_management (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            id_area INT NULL,
            id_management INT NULL
        )";
        Database::query($sql);


        //headquarters
        $sql = "CREATE TABLE IF NOT EXISTS ".self::TABLE_PROIKOS_HEADQUARTERS." (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            name_headquarters VARCHAR(250) NULL,
            status INT NULL
        )";
        Database::query($sql);

        // companies
        $sql = "CREATE TABLE IF NOT EXISTS  ".self::TABLE_PROIKOS_COMPANIES."  (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            name_companies VARCHAR(250) NULL,
            status INT NULL
        )";
        Database::query($sql);

        // managers
        $sql = "CREATE TABLE IF NOT EXISTS  ".self::TABLE_PROIKOS_MANAGERS." (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            name_managers VARCHAR(250) NULL,
            status INT NULL
        )";
        Database::query($sql);

        //add sectors

        /*$sql = "INSERT INTO ".self::TABLE_PROIKOS_SECTOR."  (`id`, `name_sector`, `status`)
        VALUES ('1', 'Hidrocarburos', '1'),
        ('2', 'Minería', '1'),
        ('3','Construcción', '1'),
        ('4', 'Industria', '1'),
        ('5', 'Energía', '1'),
        ('6', 'Servicios', '1'),
        ('7', 'Banca', '1');";

        Database::query($sql);*/

        $sql = "CREATE TABLE IF NOT EXISTS  " . self::TABLE_PROIKOS_CONTRATING_COMPANIES . " (
            id INT PRIMARY KEY AUTO_INCREMENT,
            ruc VARCHAR(20) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            admin_name VARCHAR(50) NOT NULL,
            admin_email VARCHAR(100) NULL,
            status VARCHAR(1) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );";
        Database::query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS " . self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_CAB . " (
          id INT PRIMARY KEY AUTO_INCREMENT,
          contrating_company_id INT,
          created_user_id INT NOT NULL,
          validity_date DATE NOT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );";
        Database::query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS " . self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_DET . " (
          id INT PRIMARY KEY AUTO_INCREMENT,
          cab_id INT,
          session_category_id INT NOT NULL,
          user_quota INT NOT NULL,
          price_unit DECIMAL(10,2) NULL,
          session_mode INT,
          created_user_id INT NOT NULL,
          updated_user_id INT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );";
        Database::query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS " . self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_SESSION . " (
            id INT PRIMARY KEY AUTO_INCREMENT,
            det_id INT,
            session_id INT,
            user_quota INT NOT NULL,
            created_user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );";
        Database::query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS " . self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_SESSION_DET . " (
            id INT PRIMARY KEY AUTO_INCREMENT,
            quota_session_id INT NOT NULL,
            session_id INT,
            user_id INT,
            expiration_date DATE,
            created_user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
        );";
        Database::query($sql);

    }

    public function uninstall()
    {
       /* $tablesToBeDeleted = [
            self::TABLE_PROIKOS_USERS
        ];
        foreach ($tablesToBeDeleted as $tableToBeDeleted) {
            $table = Database::get_main_table($tableToBeDeleted);
            $sql = "DROP TABLE IF EXISTS $table";
            Database::query($sql);
        }*/
    }
    public function getExistsUserProikos($userId)
    {
        if (empty($userId)) {
            return false;
        }
        $tableUserProikos = Database::get_main_table(self::TABLE_PROIKOS_USERS);
        $sql = "SELECT ppu.* FROM $tableUserProikos ppu WHERE ppu.user_id = $userId";
        $result = Database::query($sql);
        $item = 0;
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $item = $row['user_id'];
            }
        }
        return $item;
    }
    public function getInfoUserProikos($userId)
    {
        if (empty($userId)) {
            return false;
        }
        $tableUser = Database::get_main_table(TABLE_MAIN_USER);
        $tableUserProikos = Database::get_main_table(self::TABLE_PROIKOS_USERS);
        $sql = "SELECT u.id as u_id, u.firstname as u_firstname, u.lastname as u_lastname, u.username, u.email, ppu.* FROM $tableUser u
                LEFT JOIN $tableUserProikos ppu ON ppu.user_id = u.id WHERE u.id = $userId";

        $result = Database::query($sql);
        $list = [];

        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {

                $list = [
                    'user_id' => $row['u_id'],
                    'user_id_p' => $row['user_id'],
                    'lastname' => $row['u_lastname'],
                    'firstname' => $row['u_firstname'],
                    'email' => $row['email'],
                    'username' => $row['username'],
                    'phone' => $row['phone'],
                    'type_document' => empty($row['type_document']) ? '1' : $row['type_document'],
                    'number_document' => empty($row['number_document']) ? $row['username'] : $row['number_document'],
                    'age' => $row['age'],
                    'gender' => $row['gender'],
                    'instruction' => $row['instruction'],
                    'name_company' => $row['name_company'],
                    'contact_manager' => $row['contact_manager'],
                    'position_company' => $row['position_company'],
                    'stakeholders' => $row['stakeholders'],
                    'record_number' => $row['record_number'],
                    'area' => $row['area'],
                    'department' => $row['department'],
                    'headquarters' => $row['headquarters'],
                    'code_reference' => $row['code_reference'],
                    'ruc_company' => $row['ruc_company'],
                    'metadata' => $row['metadata']
                ];
            }
        }
        return $list;
    }


    public function getListEntity($all = false): array
    {
        $table = Database::get_main_table(self::TABLE_PROIKOS_ENTITY);
        $sql = "SELECT * FROM $table pe WHERE pe.status = 1";
        if($all){
            $sql = "SELECT * FROM $table pe";
        }
        $result = Database::query($sql);
        $url = api_get_path(WEB_UPLOAD_PATH);
        $list = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {

                $action = Display::url(
                    Display::return_icon(
                        'edit.png',
                        null,
                        [],
                        ICON_SIZE_SMALL),
                    api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/entity_management.php?action=edit&id=' . $row['id']
                );
                $action .= Display::url(
                    Display::return_icon(
                        'delete.png',
                        get_lang('Delete'),
                        [],
                        ICON_SIZE_SMALL
                    ),
                    api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/entity_management.php?action=delete&id=' . $row['id'],
                    [
                        'onclick' => 'javascript:if(!confirm(' . "'" .
                            addslashes(api_htmlentities(get_lang("ConfirmYourChoice")))
                            . "'" . ')) return false;',
                    ]
                );

                $list[] = [
                    'id' => $row['id'],
                    'name_entity' => $row['name_entity'],
                    'business_name' => $row['business_name'],
                    'ruc' => $row['ruc'],
                    'tax_residence' => $row['tax_residence'],
                    'economic_activity' => $row['economic_activity'],
                    'number_of_workers' => $row['number_of_workers'],
                    'picture' => $url.$row['picture'],
                    'code_reference' => $row['code_reference'],
                    'status' => $row['status'],
                    'actions' => $action
                ];
            }
        }
        return $list;
    }

    public function deleteReportLogRow($idRow): bool
    {
        if (empty($idRow)) {
            return false;
        }
        $tableReport = Database::get_main_table(self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_SESSION_DET);
        $sql = "DELETE FROM $tableReport WHERE id = $idRow";
        $result = Database::query($sql);

        if (Database::affected_rows($result) != 1) {
            return false;
        }

        return true;
    }

    public function deleteEntity($idEntity): bool
    {
        if (empty($idEntity)) {
            return false;
        }
        $idMeet = (int) $idEntity;
        $tableEntity = Database::get_main_table(self::TABLE_PROIKOS_ENTITY);
        $sql = "DELETE FROM $tableEntity WHERE id = $idEntity";
        $result = Database::query($sql);

        if (Database::affected_rows($result) != 1) {
            return false;
        }

        return true;
    }
    public function createEntity($values){
        if (!is_array($values)) {
            return false;
        }
        $table = Database::get_main_table(self::TABLE_PROIKOS_ENTITY);
        $params = [
            'name_entity' => $values['name_entity'],
            'picture' => null,
            'business_name' => $values['business_name'],
            'ruc' => $values['ruc'],
            'tax_residence' => $values['tax_residence'],
            'economic_activity' => $values['economic_activity'],
            'number_of_workers' => $values['number_of_workers'],
            'code_reference' => $values['code_reference'],
            'status' => $values['status']
        ];
        $id = Database::insert($table, $params);
        if ($id > 0) {
            return $id;
        }
    }

    public function updateEntity($values): bool
    {
        if (!is_array($values)) {
            return false;
        }
        $table = Database::get_main_table(self::TABLE_PROIKOS_ENTITY);
        $params = [
            'name_entity' => $values['name_entity'],
            'business_name' => $values['business_name'],
            'ruc' => $values['ruc'],
            'tax_residence' => $values['tax_residence'],
            'economic_activity' => $values['economic_activity'],
            'number_of_workers' => $values['number_of_workers'],
            'picture' => $values['picture'],
            'code_reference' => $values['code_reference'],
            'status' => $values['status']
        ];

        Database::update(
            $table,
            $params,
            [
                'id = ?' => [
                    $values['id'],
                ],
            ]
        );

        return true;

    }
    public function getRUC($name)
    {
        $tableCompanies = Database::get_main_table(self::TABLE_PROIKOS_CONTRATING_COMPANIES);

        // Escapar el nombre correctamente
        $name = Database::escape_string($name); // o mysqli_real_escape_string()
        $sql = "SELECT * FROM $tableCompanies c WHERE c.name = '".$name."'";
        $result = Database::query($sql);

        $item = null;
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $item = $row['ruc'];
            }
        } else {
            $plugin = ProikosPlugin::create();
            $entity = $plugin->getEntity(1);
            $item = $entity['ruc'];
        }

        return $item;
    }
    public function getEntity($idEntity){
        if (empty($idEntity)) {
            return false;
        }
        $table = Database::get_main_table(self::TABLE_PROIKOS_ENTITY);
        $sql = "SELECT * FROM $table pe WHERE id = $idEntity";
        $result = Database::query($sql);
        $item = null;
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $item = [
                    'id' => $row['id'],
                    'name_entity' => $row['name_entity'],
                    'business_name' => $row['business_name'],
                    'ruc' => $row['ruc'],
                    'tax_residence' => $row['tax_residence'],
                    'economic_activity' => $row['economic_activity'],
                    'number_of_workers' => $row['number_of_workers'],
                    'picture' => $row['picture'],
                    'code_reference' => $row['code_reference'],
                    'status' => $row['status']
                ];
            }
        }
        return $item;
    }

    public function getIDSessionQuota($idReport)
    {
        if (empty($idReport)) {
            return false;
        }

        $table = Database::get_main_table(self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_SESSION_DET);

        $sql = "SELECT * FROM $table WHERE id = $idReport";
        $result = Database::query($sql);
        $item = null;

        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $item = $row['quota_session_id'];
            }
        }
        return $item;

    }

    public function updateMinusSessionQuota($id_det)
    {
        if (empty($id_det)) {
            return false;
        }

        $table = Database::get_main_table(self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_SESSION);

        $sql = "SELECT user_quota FROM $table WHERE id = $id_det";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        if ($row) {
            $newQuota = $row['user_quota'] - 1; // Restar 1 al valor actual
        } else {
            return false; // No se encontró el registro
        }

        if ($newQuota == 0) {
            $sql = "DELETE FROM $table WHERE id = $id_det";
            $result = Database::query($sql);
            if (Database::affected_rows($result) != 1) {
                return false;
            }
        } else {
            $params = [
                'user_quota' => $newQuota
            ];

            $conditions = [
                'id = ?' => [$id_det]
            ];
            return Database::update(
                $table,
                $params,
                $conditions
            );
        }
    }

    public function updateDeleteRemoveUserQuota($user_id, $session_id)
    {
        if (empty($user_id)) {
            return false;
        }
        if (empty($session_id)) {
            return false;
        }
        $table = Database::get_main_table(self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_SESSION_DET);
        $params = [
            'user_id' => null,
            'updated_at' => null
        ];
        $conditions = [
            'user_id = ? AND session_id = ?' => [$user_id, $session_id]
        ];
        return Database::update(
            $table,
            $params,
            $conditions
        );
    }

    public function saveImage($idEntity, $fileData){
        $entity = self::getEntity($idEntity);
        if (empty($entity)) {
            return false;
        }
        if (!empty($fileData['error'])) {
            return false;
        }
        if(empty($fileData)){
            return false;
        }

        $extension = getextension($fileData['name']);
        $dirName = 'proikos_upload/';
        $fileDir = api_get_path(SYS_UPLOAD_PATH).$dirName;
        $fileName = "proikos_$idEntity.{$extension[0]}";
        if (!file_exists($fileDir)) {
            mkdir($fileDir, api_get_permissions_for_new_directories(), true);
        }

        $image = new Image($fileData['tmp_name']);
        $image->send_image($fileDir.$fileName);

        $table = Database::get_main_table(self::TABLE_PROIKOS_ENTITY);
        Database::update(
            $table,
            ['picture' => $dirName.$fileName],
            ['id = ?' => $idEntity]
        );
    }

    public function insertProikosUser($values)
    {
        if (!is_array($values)) {
            return false;
        }

        $table = Database::get_main_table(self::TABLE_PROIKOS_USERS);
        $params = [
            'user_id' => $values['user_id'],
            'lastname' => $values['lastname'],
            'firstname' => $values['firstname'],
            'phone' => $values['phone'],
            'type_document' => $values['type_document'],
            'number_document' => $values['number_document'],
            'age' => $values['age'],
            'gender' => $values['gender'],
            'instruction' => $values['instruction'],
            'ruc_company' => $values['ruc'],
            'name_company' => $values['name_company'],
            'contact_manager' => '-',
            'position_company' => $values['position_company'],
            'stakeholders' => $values['stakeholders'],
            'record_number' => '-',
            'area' => $values['area'],
            'department' => '-',
            'headquarters' => '-',
            'code_reference' => $values['code_reference'],
            'terms_conditions' => 1
        ];

        $id = Database::insert($table, $params);
        return ($id > 0) ? $id : 0;

    }
    public function updateProikosUser($values)
    {
        if (!is_array($values)) {
            return false;
        }

        $table = Database::get_main_table(self::TABLE_PROIKOS_USERS);
        $params = [
            'user_id' => $values['user_id'],
            'lastname' => $values['lastname'],
            'firstname' => $values['firstname'],
            'phone' => $values['phone'],
            'type_document' => $values['type_document'],
            'number_document' => $values['number_document'],
            'age' => $values['age'],
            'gender' => $values['gender'],
            'instruction' => $values['instruction'],
            'ruc_company' => $values['ruc'],
            'name_company' => $values['name_company'],
            'contact_manager' => '-',
            'position_company' => $values['position_company'],
            'stakeholders' => $values['stakeholders'],
            'record_number' => '-',
            'area' => $values['area'],
            'department' => '-',
            'headquarters' => '-',
            'code_reference' => $values['code_reference'],
            'terms_conditions' => 1
        ];

        return Database::update(
            $table,
            $params,
            [
                'user_id = ?' => [
                    $values['user_id'],
                ],
            ]
        );

    }

    public function saveInfoUserProikos($values){
        if (!is_array($values)) {
            return false;
        }

        if (empty($values['ruc_company']) && empty($values['name_company'])) {
            $entity = $this->getEntity(1);
            $values['ruc_company'] = $entity['ruc'];
            $values['name_company'] = $entity['business_name'];
        }
        if(empty($values['contact_manager'])){
            $values['contact_manager'] = '-';
        }

        $table = Database::get_main_table(self::TABLE_PROIKOS_USERS);
        $rucCompany =  $values['ruc_company'];
        $nameCompany =  $values['name_company'];
        $namePosition = self::getPositionName($values['position_company']);
        $nameArea = self::getAreaName($values['area']);
        $nameManagement = '-';
        $nameHeadquarters = '-';
        $params = [
            'user_id' => $values['user_id'],
            'lastname' => $values['lastname'],
            'firstname' => $values['firstname'],
            'phone' => $values['phone'],
            'type_document' => $values['type_document'],
            'number_document' => $values['number_document'],
            'age' => $values['age'],
            'gender' => $values['gender'],
            'instruction' => $values['instruction'],
            'ruc_company' => $rucCompany,
            'name_company' => $nameCompany,
            'contact_manager' => $values['contact_manager'],
            'position_company' => $namePosition,
            'stakeholders' => $values['stakeholders'],
            'record_number' => $values['record_number'],
            'area' => $nameArea,
            'department' => $nameManagement,
            'headquarters' => $nameHeadquarters,
            'code_reference' => $values['code_reference'],
            'terms_conditions' => 1
        ];

        $id = Database::insert($table, $params);
        if ($id > 0) {
            return $id;
        }
    }

    public function updateInfoUserProikos($values){
        if (!is_array($values)) {
            return false;
        }
        $table = Database::get_main_table(self::TABLE_PROIKOS_USERS);
        $params = [
            'lastname' => $values['lastname'],
            'firstname' => $values['firstname'],
            'phone' => $values['phone'],
            'type_document' => $values['type_document'],
            'number_document' => $values['number_document'],
            'age' => $values['age'],
            'gender' => $values['gender'],
            'instruction' => $values['instruction'],
            'name_company' => $values['name_company'],
            'contact_manager' => $values['contact_manager'],
            'position_company' => $values['position_company'],
            'stakeholders' => $values['stakeholders'],
            'record_number' => $values['record_number'],
            'area' => $values['area'],
            'department' => '-',
            'headquarters' => '-',
            'code_reference' => $values['code_reference']
        ];

        return Database::update(
            $table,
            $params,
            [
                'user_id = ?' => [
                    $values['user_id'],
                ],
            ]
        );
    }

    /**
     * Helper function to generates a form elements group.
     *
     * @param object $form   The form where the elements group has to be added
     * @param array  $values Values to browse through
     *
     * @return array
     */
    function formGenerateElementsGroup($form, $values, $elementName, $required = false): array
    {
        $group = [];
        $count = 0;
        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $count++;
                $attrib =  [
                    'label-class'=>'label_'.strtolower($value['value'])
                ];
                if($required){
                    if($count>=1){
                        $attrib =  [
                            'label-class'=>'label_'.strtolower($value['value']),
                            'required' => 'required'
                        ];
                    }
                }
                $element = &$form->createElement(
                    'radio',
                    $elementName,
                    '',
                    $value['display_img'],
                    $value['value'],
                    $attrib
                );
                $group[] = $element;
            }
        }

        return $group;
    }

    //get sector table;

    public function getSectors(): array
    {
        $table = Database::get_main_table(self::TABLE_PROIKOS_SECTOR);
        $sql = "SELECT * FROM $table ps";
        $result = Database::query($sql);
        $list = [];
        $list['-1'] = 'Selecciona una opción';
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $list[$row['id']] = $row['name_sector'];
            }
        }
        $list['999'] = 'Otros';
        return $list;
    }

    //get position table;

    public function getPositions($idStakeholders, $name = false): array
    {
        if($idStakeholders!=1){
            $idStakeholders = 2;
        }
        $table = Database::get_main_table(self::TABLE_PROIKOS_POSITION);
        $sql = "SELECT * FROM $table pp WHERE pp.id_stakeholder = $idStakeholders";
        $result = Database::query($sql);
        $list = [];
        $list['0'] = 'Selecciona una opción';
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                if($name){
                    $list[$row['name_position']] = $row['name_position'];
                } else {
                    $list[$row['id']] = $row['name_position'];
                }
            }
        }
        if($name){
            $list['Otros'] = 'Otros';
        } else {
            $list['999'] = 'Otros';
        }
        return $list;
    }

    public function getPetroArea($name=false): array
    {
        $table = Database::get_main_table(self::TABLE_PROIKOS_AREA);
        $sql = "SELECT * FROM $table pa WHERE pa.status = 1";
        $result = Database::query($sql);
        $list = [];
        $list['-1'] = 'Selecciona una opción';
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                if($name){
                    $list[$row['name_area']] = $row['name_area'];
                } else {
                    $list[$row['id']] = $row['name_area'];
                }

            }
        }
        //$list['999'] = 'Otros';
        return $list;
    }

    public function getPetroManagement(): array
    {
        $table = Database::get_main_table(self::TABLE_PROIKOS_MANAGEMENT);
        $sql = "SELECT * FROM $table pm ";
        $result = Database::query($sql);
        $list = [];
        $list['-1'] = 'Selecciona una opción';
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $list[$row['id']] = $row['name_management'];
            }
        }
        $list['999'] = 'Otros';
        return $list;
    }

    public function getHeadquarters($all, $idManagement = 0): array
    {
        $table = Database::get_main_table(self::TABLE_PROIKOS_HEADQUARTERS);
        if($all){
            $sql = "SELECT * FROM $table ph";
        } else {
            $sql = "SELECT * FROM $table ph WHERE ph.id_management = $idManagement";
        }

        $result = Database::query($sql);
        $list = [];
        $list['0'] = 'Selecciona una opción';
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $list[$row['id']] = $row['name_headquarters'];
            }
        }
        $list['999'] = 'Otros';
        return $list;
    }
    public function getCompanyName($id){
        if($id == '-'){
            return '-';
        }
        $table = Database::get_main_table(self::TABLE_PROIKOS_COMPANIES);
        $sql = "SELECT pc.name_companies FROM $table pc WHERE pc.id = $id";
        $result = Database::query($sql);
        $name = '-';
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $name = $row['name_companies'];
            }
        }
        return $name;
    }

    public function getPositionName($id){
        if($id == 999){
            return 'Otros';
        } else {
            $table = Database::get_main_table(self::TABLE_PROIKOS_POSITION);
            $sql = "SELECT pp.name_position FROM $table pp WHERE pp.id = $id";
            $result = Database::query($sql);
            $name = '-';
            if (Database::num_rows($result) > 0) {
                while ($row = Database::fetch_array($result)) {
                    $name = $row['name_position'];
                }
            }
            return $name;
        }

    }

    public function getAreaName($id){
        if($id == 999){
            return 'Otros';
        } else {
            $table = Database::get_main_table(self::TABLE_PROIKOS_AREA);
            $sql = "SELECT pa.name_area FROM $table pa WHERE pa.id = $id";
            $result = Database::query($sql);
            $name = '-';
            if (Database::num_rows($result) > 0) {
                while ($row = Database::fetch_array($result)) {
                    $name = $row['name_area'];
                }
            }
            return $name;
        }
    }

    public function getManagementName($id){
        if($id == 999){
            return 'Otros';
        } else {
            $table = Database::get_main_table(self::TABLE_PROIKOS_MANAGEMENT);
            $sql = "SELECT pm.name_management FROM $table pm WHERE pm.id = $id";
            $result = Database::query($sql);
            $name = '-';
            if (Database::num_rows($result) > 0) {
                while ($row = Database::fetch_array($result)) {
                    $name = $row['name_management'];
                }
            }
            return $name;
        }
    }

    public function getHeadquartersName($id){
        if($id == 999){
            return 'Otros';
        } else {
            $table = Database::get_main_table(self::TABLE_PROIKOS_HEADQUARTERS);
            $sql = "SELECT ph.name_headquarters FROM $table ph WHERE ph.id = $id";
            $result = Database::query($sql);
            $name = '-';
            if (Database::num_rows($result) > 0) {
                while ($row = Database::fetch_array($result)) {
                    $name = $row['name_headquarters'];
                }
            }
            return $name;
        }
    }

    public function getCompanies(): array
    {
        $table = Database::get_main_table(self::TABLE_PROIKOS_COMPANIES);
        $sql = "SELECT * FROM $table pc";
        $result = Database::query($sql);
        $list = [];
        $list['-'] = 'Selecciona una opción';
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $list[$row['id']] = $row['name_companies'];
            }
        }
        $list['999'] = 'Otros';
        return $list;
    }

    public function getCompaniesAdministrator($idCompany)
    {
        if(empty($idCompany) || $idCompany=='999'){
            return '-';
        }

        $table = Database::get_main_table(self::TABLE_PROIKOS_COMPANIES);
        $sql = "SELECT * FROM $table pc WHERE id = $idCompany ";
        $result = Database::query($sql);
        $nameAdministrator = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $nameAdministrator = $row['administrator'];
            }
        }
        return $nameAdministrator;
    }

    public function getManagers(): array
    {
        $table = Database::get_main_table(self::TABLE_PROIKOS_MANAGERS);
        $sql = "SELECT * FROM $table pm";
        $result = Database::query($sql);
        $list = [];
        $list['-1'] = 'Selecciona una opción';
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $list[$row['id']] = $row['name_managers'];
            }
        }
        $list['999'] = 'Otros';
        return $list;
    }

    public function getManagementArea($idArea): array
    {
        $tableManagement = Database::get_main_table(self::TABLE_PROIKOS_MANAGEMENT);
        $tableArea = Database::get_main_table(self::TABLE_PROIKOS_AREA);
        $tableAreaRedManagement = Database::get_main_table(self::TABLE_PROIKOS_AREA_REF_MANAGEMENT);

        $sql = "SELECT pm.id, pm.name_management FROM $tableManagement pm
                INNER JOIN $tableAreaRedManagement pam ON pam.id_management = pm.id
                INNER JOIN $tableArea pa ON pam.id_area = pa.id WHERE pa.id = $idArea";
        $result = Database::query($sql);
        $list = [];
        $list['0'] = 'Selecciona una opción';
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $list[$row['id']] = $row['name_management'];
            }
        }
        $list['999'] = 'Otros';
        return $list;
    }
    public function getCompanyArea($idUser){
        if (empty($idUser)) {
            return false;
        }
        $table = Database::get_main_table(self::TABLE_PROIKOS_USERS);
        $sql = "SELECT pu.name_company, pu.area  FROM $table pu WHERE pu.user_id = '$idUser'";
        $result = Database::query($sql);
        $item = null;
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $item = [
                    '0' => $row['name_company'],
                    '1' => $row['area']
                ];
            }
        }
        return $item;
    }

    public function getCodeReferenceByUser($idUser){
        if (empty($idUser)) {
            return false;
        }
        $table = Database::get_main_table(self::TABLE_PROIKOS_USERS);
        $sql = "SELECT pu.code_reference FROM $table pu WHERE pu.user_id = '$idUser'";
        $result = Database::query($sql);
        $item = null;
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $item = $row['code_reference'];
            }
        }
        if(api_is_platform_admin() || api_is_teacher()){
            $item = 'ALL';
        }
        if(is_null($item)){
            $tableUser = Database::get_main_table(TABLE_MAIN_USER);
            $sql = "SELECT u.code_reference FROM $tableUser u WHERE u.user_id = '$idUser'";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                while ($row = Database::fetch_array($result)) {
                    $item = $row['code_reference'];
                }
            }
        }
        //var_dump($item);
        return $item;
    }

    //get picture entity
    public function getPictureEntity($code){
        if (empty($code)) {
            return false;
        }
        $table = Database::get_main_table(self::TABLE_PROIKOS_ENTITY);
        $sql = "SELECT * FROM $table pe WHERE pe.code_reference = '$code'";
        $result = Database::query($sql);
        $item = null;
        $fileDir = api_get_path(WEB_UPLOAD_PATH);
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $item = [
                    'picture' => $fileDir.$row['picture']
                ];
            }
        }
        return $item['picture'];
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     *
     * @return mixed
     */
    public static function hideFromSessionCatalogCondition($qb)
    {
        $em = Database::getManager();
        $qb3 = $em->createQueryBuilder();

        $extraField = new ExtraFieldModel('session');
        $extraFieldInfo = $extraField->get_handler_field_info_by_field_variable('hide_from_catalog');
        if (!empty($extraFieldInfo)) {
            $qb->andWhere(
                $qb->expr()->notIn(
                    's',
                    $qb3
                        ->select('s3')
                        ->from('ChamiloCoreBundle:ExtraFieldValues', 'fv')
                        ->innerJoin('ChamiloCoreBundle:Session', 's3', Join::WITH, 'fv.itemId = s3.id')
                        ->where(
                            $qb->expr()->eq('fv.field', $extraFieldInfo['id'])
                        )->andWhere(
                            $qb->expr()->eq('fv.value ', 1)
                        )
                        ->getDQL()
                )
            );
        }

        return $qb;
    }

    /**
     * List the sessions.
     *
     * @param null $date
     * @param array $limit
     * @param bool $returnQueryBuilder
     * @param bool $getCount
     * @param null $code_reference
     * @param int $categoryID
     * @return array|\Doctrine\ORM\Query The session list
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function browseSessions($date = null, $limit = [], $returnQueryBuilder = false, $getCount = false, $code_reference = null, $categoryID)
    {
        $urlId = api_get_current_access_url_id();
        $em = Database::getManager();
        $qb = $em->createQueryBuilder();
        $qb2 = $em->createQueryBuilder();

        $qb = $qb
            ->select('s')
            ->from('ChamiloCoreBundle:Session', 's')
            ->where(
                $qb->expr()->in(
                    's',
                    $qb2
                        ->select('s2')
                        ->from('ChamiloCoreBundle:AccessUrlRelSession', 'url')
                        ->join('ChamiloCoreBundle:Session', 's2')
                        ->where(
                            $qb->expr()->eq('url.sessionId ', 's2.id')
                        )->andWhere(
                            $qb->expr()->eq('url.accessUrlId ', $urlId)
                        )->getDQL()
                )
            )
            ->andWhere($qb->expr()->gt('s.nbrCourses', 0));

        if($code_reference != 'ALL'){
           $qb->andWhere($qb->expr()->eq('s.codeReference', ':codeReference'))
                ->setParameter('codeReference', $code_reference);
        }

        $qb->andWhere($qb->expr()->eq('s.category', ':categoryID'))
            ->setParameter('categoryID', $categoryID);

        if (!empty($date)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('s.accessEndDate'),
                    $qb->expr()->andX(
                        $qb->expr()->isNotNull('s.accessStartDate'),
                        $qb->expr()->isNotNull('s.accessEndDate'),
                        $qb->expr()->lte('s.accessStartDate', $date),
                        $qb->expr()->gte('s.accessEndDate', $date)
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->isNull('s.accessStartDate'),
                        $qb->expr()->isNotNull('s.accessEndDate'),
                        $qb->expr()->gte('s.accessEndDate', $date)
                    )
                )
            );
        }

        // only asincronico / sincronico
        $qb->andWhere(
            $qb->expr()->in(
                's.sessionMode',
                [
                    self::CATEGORY_ASINCRONO,
                    self::CATEGORY_SINCRONO
                ]
            )
        );

        if ($getCount) {
            $qb->select('count(s)');
        }

        $qb = self::hideFromSessionCatalogCondition($qb);

        if (!empty($limit)) {
            $qb
                ->setFirstResult($limit['start'])
                ->setMaxResults($limit['length'])
            ;
        }

        $query = $qb->getQuery();


        if ($returnQueryBuilder) {
            return $query;
        }

        if ($getCount) {
            return $query->getSingleScalarResult();
        }

        return $query->getResult();
    }

    public function getRequirementsSessionUser($type, $userId, $sessionId): bool
    {
        $em = Database::getManager();
        /** @var SequenceRepository $sequenceRepository */
        $sequenceRepository = $em->getRepository(Sequence::class);
        /** @var SequenceResourceRepository $sequenceResourceRepository */
        $sequenceResourceRepository = $em->getRepository(SequenceResource::class);
        $sequences = $sequenceResourceRepository->getRequirements($sessionId, $type);
        $sequenceList = $sequenceResourceRepository->checkRequirementsForUser($sequences, $type, $userId, $sessionId);
        return self::checkSequenceAreCompleted($sequenceList);
    }

    /**
     * Check if at least one sequence are completed.
     */
    public function checkSequenceAreCompleted(array $sequences, $itemType = SequenceResourceRepository::VERTICES_TYPE_REQ): bool
    {
        if(empty($sequences)){
            return true;
        }
        foreach ($sequences as $sequence) {
            $status = false;

            foreach ($sequence[$itemType] as $item) {
                $status = $status || $item['status'];
            }

            if ($status) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $idStudent
     * @return array
     */
    public function getExtraInfo($idStudent): array
    {
        $tbl_proikos_info = Database::get_main_table(self::TABLE_PROIKOS_USERS);
        $sql = "SELECT * FROM $tbl_proikos_info pe WHERE pe.user_id = '$idStudent'";
        $result = Database::query($sql);
        $list = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $list = [
                    'phone' => $row['phone'],
                    'name_company' => $row['name_company'],
                    'stakeholders' => $row['stakeholders'],
                    'area' => $row['area'],
                    'department' => $row['department'],
                    'headquarters' => $row['headquarters'],
                    'code_reference' => $row['code_reference'],
                ];
            }
        }
        return $list;
    }
    public function getStudentsSessionForFora($idSession): array
    {
        $users = SessionManager::get_users_by_session($idSession);
        $list = [];
        $count = 1;
        foreach ($users as $row) {
            $extras = self::getExtraInfo($row['user_id']);
            $list[] = [
                'number' => $count,
                'user_id' => $row['user_id'],
                'firstname' => $row['firstname'],
                'lastname' => $row['lastname'],
                'email' => $row['username'],
                'extras' => $extras
            ];
            $count++;
        }
        return $list;
    }

    public static function getUserRucCompany(): string
    {
        $userId = api_get_user_id();
        $table = Database::get_main_table(self::TABLE_PROIKOS_USERS);
        $sql = "SELECT ruc_company FROM $table WHERE user_id = '$userId'";
        $result = Database::query($sql);
        $rucCompany = '';
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $rucCompany = $row['ruc_company'];
            }
        }
        return $rucCompany;
    }

    public function getGradebookEvaluation($courseId, $session_id = 0){
        $course_code = self::getCourseCode($courseId);
        $cats = Category::load(
            null,
            null,
            $course_code,
            null,
            null,
            $session_id,
            false
        );
        foreach ($cats as $cat) {
            $cats = $cat->get_subcategories(null, $course_code, $session_id);
            $evals = $cat->get_evaluations(null, false, $course_code, $session_id);
            $links = $cat->get_links(null, true, $course_code, $session_id);
            $evals_links = array_merge($evals, $links);
            $all_items = new GradebookDataGenerator($cats, $evals, $links);
            usort($all_items->items, ['GradebookDataGenerator', 'sort_by_name']);
            $visibleItems = array_merge($all_items->items, $evals_links);
            $defaultData = [];
            /** @var GradebookItem $item */
            foreach ($visibleItems as $item) {
                $name = strtolower($item->get_name());
                $defaultData[$name] = 0;
            }
            return $defaultData;
        }
    }
    public function getCoursesSessionID($idSession, $idUser, $evaluations_empty = []): array
    {
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT src.c_id, src.session_id, c.title, c.code FROM $tbl_session_course src INNER JOIN $tbl_course c ON src.c_id = c.id WHERE src.session_id = $idSession;";
        $result = Database::query($sql);
        $courses = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $evaluations = self::getResultExerciseStudent(
                    $idUser,
                    $row['c_id'],
                    $idSession
                );
                if(is_array($evaluations)){
                    foreach ($evaluations as $key => $value) {
                        if ($value > 0) {
                            $evaluations_empty[$key] = $value;
                        }
                    }
                }
                $courses[] = [
                    'c_id' => $row['c_id'],
                    'session_id' => $row['session_id'],
                    'title' => $row['title'],
                    'code' => $row['code'],
                    'evaluations' => $evaluations_empty,
                ];
            }
        }
        return $courses;
    }
    public function getCertificateDates($user_id, $session_id)
    {
        $tableCertificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $tableCategory = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $sql = "SELECT cert.* FROM $tableCertificate cert INNER JOIN $tableCategory cat ON cat.id = cert.cat_id
              WHERE cert.user_id = $user_id AND cat.session_id = $session_id";
        $result = Database::query($sql);
        $list = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {
                $list = $row;
            }
        }

        return $list;
    }
    public function getCourseCode($courseId)
    {
        $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT code FROM $tableCourse WHERE id = $courseId";
        $rs = Database::query($sql);
        $aux = Database::fetch_assoc($rs);
        return $aux['code'];
    }

    public function getResultExerciseStudent($user_id, $courseId, $session_id = 0, $showEmpty = false)
    {
        $course_code = self::getCourseCode($courseId);
        $cats = Category::load(
            null,
            null,
            $course_code,
            null,
            null,
            $session_id,
            false
        );
        foreach ($cats as $cat){
            $cats = $cat->get_subcategories($user_id, $course_code, $session_id);
            $evals = $cat->get_evaluations($user_id, false, $course_code, $session_id);
            $links = $cat->get_links($user_id, true, $course_code, $session_id);

            $evals_links = array_merge($evals, $links);
            $all_items = new GradebookDataGenerator($cats, $evals, $links);
            usort($all_items->items, ['GradebookDataGenerator', 'sort_by_name']);
            $visibleItems = array_merge($all_items->items, $evals_links);
            $defaultData = [];
            /** @var GradebookItem $item */
            foreach ($visibleItems as $item) {
                $itemType = get_class($item);
                switch ($itemType) {
                    case 'Evaluation':
                        $score = self::getFormatScore($item,$user_id);
                        if($showEmpty){
                            if($score==0){
                                $defaultData=[];
                            } else {
                                $name = str_replace(' ', '_', strtolower($item->get_name()));
                                $defaultData[$name] = round($score,1);
                            }
                        } else{
                            $name = str_replace(' ', '_', strtolower($item->get_name()));
                            $defaultData[$name] = round($score,1);
                        }
                        break;
                    case 'ExerciseLink':
                        /** @var ExerciseLink $item */
                        $score = self::getScoreExercise($item->get_ref_id(),$session_id,$user_id);
                        if($showEmpty){
                            if($score==0){
                                $defaultData=[];
                            }else{
                                $name = str_replace(' ', '_', strtolower($item->get_name()));
                                $defaultData[$name] = round($score,1);
                            }
                        } else {
                            $name = str_replace(' ', '_', strtolower($item->get_name()));
                            $defaultData[$name] = round($score,1);
                        }
                        break;
                }
            }

            return $defaultData;
        }
    }

    public function getScoreExercise($id_exercise, $session_id, $user_id){
        $table_exercise_log = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $sql = "SELECT * FROM $table_exercise_log tee WHERE tee.exe_exo_id = $id_exercise AND tee.session_id = $session_id AND tee.exe_user_id = $user_id;";
        $result = Database::query($sql);
        $score = 0;
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {
                $scoreMax = ($this->get('highest_score_exercise') == 'true');
                if($row['status'] == 'incomplete'){
                    continue;
                }
                if ($scoreMax) {
                        $score = max($score, doubleval($row['exe_result']));
                } else {
                    $score = doubleval($row['exe_result']);
                }

            }
        }
        return $score;
    }

    public function getFormatScore(GradebookItem $item, $user_id): float
    {

        $score = $item->calc_score($user_id);

        if(is_null($score)){
            return floatval(0);
        } else {
            return floatval($score[0]);
        }

    }

    public function getNameExercise($id_exercise){
        $tbl_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
        $sql = "SELECT cq.title FROM $tbl_quiz cq WHERE cq.id = $id_exercise";
        $res = Database::query($sql);
        $name = null;
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $name = $row['title'];
        }
        return $name;
    }

    public function getScoreEvaluationStudent($codeCourse, $idStudent): array
    {
        $tbl_gradebook_evaluation = Database::get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
        $tbl_gradebook_result = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
        $sql = "SELECT ge.id as id_evaluation, gr.user_id, ge.name, ge.course_code, gr.score FROM $tbl_gradebook_evaluation ge
                INNER JOIN $tbl_gradebook_result gr ON gr.evaluation_id = ge.id WHERE ge.course_code = '$codeCourse' AND gr.user_id = $idStudent;";
        $result = Database::query($sql);
        $list = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $list[] = [
                    'id_evaluation' => $row['id_evaluation'],
                    'id_user' => $row['user_id'],
                    'name' => $row['name'],
                    'code_course' => $row['course_code'],
                    'score' => $row['score']
                ];
            }
        }
        return $list;
    }
    public function getListCourses(): array
    {
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT c.id, c.title, c.code FROM $tbl_course c";
        $result = Database::query($sql);
        $courses = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $courses[] = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'code' => $row['code']
                ];
            }
        }
        return $courses;
    }

    public function getTotalStudentsPlatform(){
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT count(*) as total FROM $tbl_user u WHERE u.status = 5;";
        $result = Database::query($sql);
        $total = 0;
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $total =  $row['total'];
            }
        }
        return $total;
    }

    public function getCourseName($course_code){
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT c.title FROM $tbl_course c WHERE c.code='$course_code' ";
        $result = Database::query($sql);
        $name = '';
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $name =  $row['title'];
            }
        }
        return $name;
    }
    public function getParticipatingUsersCertificate($starDate, $endDate): array
    {
        $d_start = (string)$starDate;
        $d_end = (string)$endDate;
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $sql = "SELECT srcu.user_id, srcu.session_id, srcu.c_id FROM $tbl_session_course srcu INNER JOIN $tbl_session s ON s.id = srcu.session_id
                WHERE s.display_start_date BETWEEN '".$d_start."' AND '".$d_end."'";
        $result = Database::query($sql);
        $lists = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $code = self::getCourseCode($row['c_id']);
                $certificate = $this->getScoreCertificate($row['user_id'],$code,$row['session_id'],true);
                $has_certificate = 0;
                if(empty($certificate)){
                    $has_certificate = 0;
                } else {
                    $score_min = $certificate['certif_min_score'];
                    $score = $certificate['score'];
                    if(intval(round($score,0)) >= $score_min){
                        $has_certificate = 1;
                    }
                }
                $lists[] = [
                    'user_id' => $row['user_id'],
                    'course_code' => self::getCourseCode($row['c_id']),
                    'certificate' => $has_certificate
                ];
            }
        }
        $newArray = []; // El nuevo array donde almacenaremos los resultados
        $newList = [];
        foreach ($lists as $item) {
            $courseCode = $item['course_code'];
            $certificate = $item['certificate'];

            // Si el course_code ya existe en el nuevo array, suma las evaluaciones.
            if (isset($newArray[$courseCode])) {
                $newArray[$courseCode]['certificate'] += $certificate;
            } else {
                // Si no existe, crea un nuevo elemento en el nuevo array.
                $newArray[$courseCode] = array(
                    'course_code' => $courseCode,
                    'course_name' => self::getCourseName($courseCode),
                    'certificate' => $certificate
                );
            }
        }
        foreach ($newArray as $row){
            $newList[] = [
                'course_code' => $row['course_code'],
                'course_name' =>  $row['course_name'],
                'certificate' => $row['certificate']
            ];
        }
        return  $newList;
    }

    public function getExamsSession($starDate, $endDate): array
    {
        $d_start = (string)$starDate;
        $d_end = (string)$endDate;
        $sql = "SELECT src.session_id, src.c_id, cq.title,cq.iid FROM session s
        INNER JOIN session_rel_course src ON s.id = src.session_id
        INNER JOIN c_quiz cq ON cq.c_id=src.c_id
        WHERE cq.active = 1 AND s.display_start_date BETWEEN '".$d_start."' AND '".$d_end."'";
        $result = Database::query($sql);
        $lists = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $lists[] = [
                    'session_id' => $row['session_id'],
                    'course_code' => self::getCourseCode($row['c_id']),
                    'title' => strtolower($row['title']),
                    'exercises_id' => $row['iid']
                ];
            }
        }
        return $lists;
    }

    public function processStudentList($filter_score, $exercise, $courseCode, $sessionId, $title): array
    {
        $exerciseStatsTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $courseId = api_get_course_int_id($courseCode);
        $students = CourseManager::get_student_list_from_course_code(
            $courseCode,
            true,
            $sessionId,
            null,
            null,
            false
        );
        $totalStudents = count($students);
        $total_with_parameter_score = 0;
        $taken = 0;

        $globalRow = [
            'title' => $title,
            'exam_taken' => 0,
            'exam_not_taken' => 0,
            'exam_pass' => 0,
            'exam_fail' => 0,
            'total_students' => $totalStudents,
        ];

        foreach ($students as $student) {
            $studentId = isset($student['user_id']) ? $student['user_id'] : $student['id_user'];
            $sql = "SELECT COUNT(ex.exe_id) as count
                FROM $exerciseStatsTable AS ex
                WHERE
                    ex.c_id = $courseId AND
                    ex.exe_exo_id = ".$exercise." AND
                    exe_user_id= $studentId AND
                    session_id = $sessionId
                ";
            $result = Database::query($sql);
            $attempts = Database::fetch_array($result);

            $sql = "SELECT exe_id, exe_result, exe_weighting
                FROM $exerciseStatsTable
                WHERE
                    exe_user_id = $studentId AND
                    c_id = $courseId AND
                    exe_exo_id = ".$exercise." AND
                    session_id = $sessionId
                ORDER BY exe_result DESC
                LIMIT 1";
            $result = Database::query($sql);
            $score = 0;
            $weighting = 0;
            while ($scoreInfo = Database::fetch_array($result)) {
                $score = $score + $scoreInfo['exe_result'];
                $weighting = $weighting + $scoreInfo['exe_weighting'];
            }

            $percentageScore = 0;

            if ($weighting != 0) {
                $percentageScore = round(($score * 100) / $weighting);
            }

            if ($attempts['count'] > 0) {
                $taken++;
            }

            if ($percentageScore >= $filter_score) {
                $total_with_parameter_score++;
            }

            $globalRow = [
                'title' => $title,
                'exam_taken' => $taken,
                'exam_not_taken' => $totalStudents - $taken,
                'exam_pass' => $total_with_parameter_score,
                'exam_fail' => $taken - $total_with_parameter_score,
                'total_students' => $totalStudents,
            ];

        }
        return $globalRow;
    }


    public function getUserParticipatesExam($starDate, $endDate, $stakeholders = '0', $gender = '0'): array
    {
        $d_start = (string)$starDate;
        $d_end = (string)$endDate;
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $sql = "SELECT srcu.user_id, srcu.session_id, srcu.c_id FROM $tbl_session_course srcu INNER JOIN $tbl_session s ON s.id = srcu.session_id
                WHERE s.display_start_date BETWEEN '".$d_start."' AND '".$d_end."'";
        print_r($sql);
        exit;
        $result = Database::query($sql);
        $lists = [];
        $number = 0;
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $evaluations = self::getResultExerciseStudent(
                    $row['user_id'],
                    $row['c_id'],
                    $row['session_id'],
                    true
                );
                if(is_null($evaluations) OR empty($evaluations)){
                    $number = 0;
                } else {
                    if(count($evaluations) >= 1){
                        $number = 1;
                    }
                }
                $lists[] = [
                    'user_id' => $row['user_id'],
                    'course_code' => self::getCourseCode($row['c_id']),
                    'evaluations' => $number
                ];
            }
        }
        return $lists;
    }

    public function getParticipatingUsers($starDate, $endDate, $stakeholders='0', $gender='0'): array
    {
        $lists = self::getUserParticipatesExam($starDate, $endDate, $stakeholders, $gender);
        $newArray = []; // El nuevo array donde almacenaremos los resultados
        $newList = [];
        foreach ($lists as $item) {
            $courseCode = $item['course_code'];
            $evaluations = $item['evaluations'];

            // Si el course_code ya existe en el nuevo array, suma las evaluaciones.
            if (isset($newArray[$courseCode])) {
                $newArray[$courseCode]['evaluations'] += $evaluations;
            } else {
                // Si no existe, crea un nuevo elemento en el nuevo array.
                $newArray[$courseCode] = array(
                    'course_code' => $courseCode,
                    'course_name' => self::getCourseName($courseCode),
                    'evaluations' => $evaluations
                );
            }
        }
        foreach ($newArray as $row){
            $newList[] = [
                'course_code' => $row['course_code'],
                'course_name' =>  $row['course_name'],
                'participants' => $row['evaluations']
            ];
        }
        return $newList;
    }

    public function getStudentsApprovedDisapproved($start_date, $end_date, $stakeholders='0', $gender = '0'): array
    {

        $participants = self::getParticipatingUsers($start_date, $end_date, $stakeholders, $gender);
        $certificates = self::getParticipatingUsersCertificate($start_date, $end_date);

        // Creamos un nuevo array para almacenar los datos combinados
        $combinedData = [];
        $list = [];
        foreach ($participants as $participant) {
            $courseCode = $participant['course_code'];
            if (!isset($combinedData[$courseCode])) {
                $combinedData[$courseCode] = [
                    'course_code' => $participant['course_code'],
                    'course_name' => $participant['course_name'],
                    'approved' => 0,
                    'participants' => 0,
                ];
            }
            $combinedData[$courseCode]['participants'] += $participant['participants'];
        }

        foreach ($certificates as $certificate) {
            $courseCode = $certificate['course_code'];
            if (isset($combinedData[$courseCode])) {
                $combinedData[$courseCode]['approved'] = $certificate['certificate'];
            }
        }

        foreach ($combinedData as $data){
            $approved = $data['approved'];
            $participants = $data['participants'];
            $disapproved = max($participants - $approved, 0);
            $list[] = [
                'course_code' => $data['course_code'],
                'course_name' => $data['course_name'],
                'approved' => $data['approved'],
                'disapproved' => $disapproved
            ];
        }
        return $list;
    }

    public function getSessionRelCourseUsers($starDate, $endDate): array
    {
        $d_start = (string)$starDate;
        $d_end = (string)$endDate;
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT s.nbr_users, s.name, c.code FROM $tbl_session s INNER JOIN $tbl_session_course src ON s.id = src.session_id
        INNER JOIN $tbl_course c ON src.c_id = c.id WHERE s.display_start_date BETWEEN '".$d_start."' AND '".$d_end."'";
        $result = Database::query($sql);
        $data = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $data[] =  [
                    'nbr_users' => intval($row['nbr_users']),
                    'name' => $row['name'],
                    'course_code' => $row['code']
                ];
            }
        }

        $aggregatedData = array();
        foreach ($data as $item) {
            $courseCode = $item['course_code'];
            if (!isset($aggregatedData[$courseCode])) {
                // Si el curso aún no se ha encontrado, crear una entrada en el array agregado
                $aggregatedData[$courseCode] = $item;
            } else {
                // Si el curso ya se encontró, sumar el valor de nbr_users
                $aggregatedData[$courseCode]['nbr_users'] += $item['nbr_users'];
            }
        }

        return array_values($aggregatedData);
    }

    public function getSessionForDate($starDate, $endDate): array
    {
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $d_start = (string)$starDate;
        $d_end = (string)$endDate;
        $sql = "SELECT * FROM $tbl_session s WHERE s.display_start_date BETWEEN '".$d_start."' AND '".$d_end."'";
        $result = Database::query($sql);
        $list = [];
        $em = Database::getManager();
        /** @var \Chamilo\CoreBundle\Entity\Repository\SessionRepository $sessionRepository */
        $sessionRepository = $em->getRepository('ChamiloCoreBundle:Session');
        /** @var Session $session */
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $sessionInfo = api_get_session_info($row['id']);
                $session = $sessionRepository->find($row['id']);
                $sessionCategory = $session->getCategory();

                if(is_null($sessionCategory)){
                    $categoryName = 'Ninguno';
                } else {
                    $categoryName = $sessionCategory->getName();
                }

                $list[] = [
                    'id' => $sessionInfo['id'],
                    'id_coach' => $sessionInfo['id_coach'],
                    'session_category_id' => $sessionInfo['session_category_id'],
                    'session_category_name' => $categoryName,
                    'name' => $sessionInfo['name'],
                    'description' => $sessionInfo['description'],
                    'nbr_courses' => $sessionInfo['nbr_courses'],
                    'nbr_users' => $sessionInfo['nbr_users'],
                    'session_admin_id' => $sessionInfo['session_admin_id'],
                    'code_reference' => $sessionInfo['code_reference'],
                    'display_start_date' => $sessionInfo['display_start_date'],
                    'display_end_date' => $sessionInfo['display_end_date'],
                    'access_start_date' => $sessionInfo['access_start_date'],
                    'access_end_date' => $sessionInfo['access_end_date']
                ];
            }
        }

        return $list;
    }
    public function  getStudentForSessionData($session = [], $data = []): array
    {
        if (empty($session['id'])) {
            return [];
        }
        $id = (int) $session['id'];
        $tbl_proikos_user = Database::get_main_table(self::TABLE_PROIKOS_USERS);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $table_access_url_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

        $sql = "SELECT u.id as user_id, u.lastname, u.firstname, u.username, u.email, u.official_code, u.status, u.active, su.relation_type, au.access_url_id,
                su.moved_to, su.moved_status, su.moved_at, su.registered_at, ppu.*
                FROM $tbl_user u
                INNER JOIN $tbl_session_rel_user su
                ON u.user_id = su.user_id AND
                su.session_id = $id
                LEFT OUTER JOIN $table_access_url_user au
                ON (au.user_id = u.user_id)
                INNER JOIN $tbl_proikos_user ppu
                ON u.user_id = ppu.user_id
                WHERE (au.access_url_id = 1 OR au.access_url_id is null ) ";

        if($data['gender'] != '0'){
            $sql.= " AND ppu.gender = '".$data['gender']."' ";
        }

        if($data['stakeholders'] != '0'){
            $sql.= " AND ppu.stakeholders = '".$data['stakeholders']."' ";
        }

        if($data['position_company'] != '0'){
            $sql.= " AND ppu.position_company = '".$data['position_company']."' ";
        }

        if($data['department'] != '-1'){
            $sql.= " AND ppu.department = '".$data['department']."' ";
        }

        if($data['name_company'] != '-'){
            $sql.= " AND ppu.name_company = '".$data['name_company']."' ";
        }

        $sql.= " ORDER BY su.relation_type,   u.lastname, u.firstname ";

        $users = [];
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result, 'ASSOC')) {

            $hasCertificates = Certificate::getCertificateByUser($row['user_id']);
            $row['has_certificates'] = 0;
            if (!empty($hasCertificates)) {
                $row['has_certificates'] = 1;
            }
            $courses = self::getCoursesSessionID($session['id'], $row['user_id']);
            $users[] = [
                'id' => $row['user_id'],
                'firstname' => $row['firstname'],
                'lastname' => $row['lastname'],
                'username' => $row['username'],
                'email' => $row['email'],
                'official_code' => $row['official_code'],
                'has_certificates' => $row['has_certificates'],
                'status' => $row['status'],
                'active' => $row['active'],
                'phone' => $row['phone'] ?? '-',
                'type_document' => $row['type_document'] ?? '-',
                'number_document' => $row['number_document'] ?? '-',
                'age' => $row['age'] ?? '-',
                'gender' => $row['gender'] ?? '-',
                'instruction' => $row['instruction'] ?? '-',
                'name_company' => $row['name_company'] ?? '-',
                'contact_manager' => $row['contact_manager'] ?? '-',
                'position_company' => $row['position_company'] ?? '-',
                'stakeholders' => $row['stakeholders'] ?? '-',
                'record_number' => $row['record_number'] ?? '-',
                'area' => $row['area'] ?? '-',
                'department' => $row['department'] ?? '-',
                'headquarters' => $row['headquarters'] ?? '-',
                'session_id' => $session['id'],
                'session_name' => $session['name'],
                'display_start_date' => $session['display_start_date'],
                'display_end_date' => $session['display_end_date'],
                'courses' => $courses
            ];
        }

        return $users;
    }

    public function getStudentForSession($session = [], $tmpEvals = [])
    {
        $userList = SessionManager::get_users_by_session($session['id']);
        $users = [];
        if (!empty($userList)) {
            foreach ($userList as $user) {
                $userId = $user['user_id'];
                $infoProikos = self::getInfoUserProikos($userId);
                $userInfo = api_get_user_info($userId);
                $courses = self::getCoursesSessionID($session['id'], $userId, $tmpEvals);
                $users[] = [
                    'id' => $userId,
                    'firstname' => $userInfo['firstname'],
                    'lastname' => $userInfo['lastname'],
                    'username' => $userInfo['username'],
                    'email' => $userInfo['email'],
                    'official_code' => $userInfo['official_code'],
                    'has_certificates' => $userInfo['has_certificates'],
                    'status' => $userInfo['status'],
                    'active' => $userInfo['active'],
                    'phone' => $infoProikos['phone'] ?? '-',
                    'type_document' => $infoProikos['type_document'] ?? '-',
                    'number_document' => $infoProikos['number_document'] ?? '-',
                    'age' => $infoProikos['age'] ?? '-',
                    'gender' => $infoProikos['gender'] ?? '-',
                    'instruction' => $infoProikos['instruction'] ?? '-',
                    'name_company' => $infoProikos['name_company'] ?? '-',
                    'contact_manager' => $infoProikos['contact_manager'] ?? '-',
                    'position_company' => $infoProikos['position_company'] ?? '-',
                    'stakeholders' => $infoProikos['stakeholders'] ?? '-',
                    'record_number' => $infoProikos['record_number'] ?? '-',
                    'area' => $infoProikos['area'] ?? '-',
                    'department' => $infoProikos['department'] ?? '-',
                    'headquarters' => $infoProikos['headquarters'] ?? '-',
                    'session_id' => $session['id'],
                    'session_name' => $session['name'],
                    'display_start_date' => $session['display_start_date'],
                    'display_end_date' => $session['display_end_date'],
                    'courses' => $courses
                ];
            }
            return $users;
        }
    }

    function  getInstructionTypeText($valor): string
    {
        $instructions = [
            '1' => 'Primaria',
            '2' => 'Secundaria',
            '3' => 'Técnica superior',
            '4' => 'Universitaria Bachiller',
            '5' => 'Universitaria Titulada',
        ];
        if (array_key_exists($valor, $instructions)) {
            return $instructions[$valor];
        } else {
            return '-';
        }
    }

    public function getStakeholderForUserId($user_id){
        $sql = "SELECT ppu.stakeholders FROM plugin_proikos_users ppu WHERE ppu.user_id = $user_id";
        $result = Database::query($sql);
        $name = '1';
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $name =  $row['stakeholders'];
            }
        }
        return $name;
    }

    function  getStakeholderTypeText($valor): string
    {
        $stakeholders = [
            '1' => 'Petroperu',
            '2' => 'Contratista',
            '3' => 'Cliente',
            '99' => 'Otros',
        ];
        if (array_key_exists($valor, $stakeholders)) {
            return $stakeholders[$valor];
        } else {
            return '-';
        }
    }

    function getDocumentTypeText($valor): string
    {
        $typesDocuments = [
            '1' => 'DNI',
            '2' => 'Carnet de Extranjeria',
            '3' => 'Pasaporte',
            '4' => 'RUC',
            '5' => 'Otros',
        ];
        if (array_key_exists($valor, $typesDocuments)) {
            return $typesDocuments[$valor];
        } else {
            return '-';
        }
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportReportXLS($students, $logo, $extraColumns, $extraHeaders){
        $date = date('d-m-Y H:i:s', time());
        $date_format = api_convert_and_format_date($date, "%d-%m-%Y %H:%M");
        $nameFile = 'participants_report_';
        $newHeaders = [];
        if(!empty($extraHeaders)){
            foreach ($extraHeaders as $key => $value) {
                $newHeaders[] = $key;
            }
        }

        $headers = [
            '#',
            get_lang('LastName'),
            get_lang('FirstName'),
            get_plugin_lang('TypeDocument', 'ProikosPlugin'),
            get_plugin_lang('NumberDocument', 'ProikosPlugin'),
            get_plugin_lang('AgeYear', 'ProikosPlugin'),
            get_plugin_lang('Gender', 'ProikosPlugin'),
            get_plugin_lang('GradeInstructions', 'ProikosPlugin'),
            get_plugin_lang('Email', 'ProikosPlugin'),
            get_plugin_lang('CompanyName', 'ProikosPlugin'),
            get_plugin_lang('ContactManager', 'ProikosPlugin'),
            get_plugin_lang('Position', 'ProikosPlugin'),
            get_plugin_lang('Stakeholder', 'ProikosPlugin'),
            get_plugin_lang('RecordNumber', 'ProikosPlugin'),
            get_plugin_lang('Area', 'ProikosPlugin'),
            get_plugin_lang('Department', 'ProikosPlugin'),
            get_plugin_lang('Headquarters', 'ProikosPlugin'),
            get_plugin_lang('CodeReference', 'ProikosPlugin'),
            get_plugin_lang('SessionId', 'ProikosPlugin'),
            get_plugin_lang('SessionName', 'ProikosPlugin'),
            get_plugin_lang('SessionStarDate', 'ProikosPlugin'),
            get_lang('Code'),
            get_lang('CourseName')
        ];

        $headers = array_merge($headers, $newHeaders);
        $resultHeaders =[
            get_plugin_lang('MinCertScore', 'ProikosPlugin'),
            get_plugin_lang('Score', 'ProikosPlugin'),
            get_plugin_lang('Certificate', 'ProikosPlugin'),
            get_plugin_lang('Status', 'ProikosPlugin')
        ];
        $headers = array_merge($headers, $resultHeaders);
        //$totalColumns = count($headers);

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $row = 5;
        $rowDimension = $worksheet->getRowDimension($row);
        $rowDimension->setRowHeight(30);

        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Logo');
        $drawing->setPath($logo);
        $drawing->setWidth(200); // Altura de la imagen en puntos
        $drawing->setCoordinates('A2'); // Coordenadas de la celda donde se insertará la imagen
        $drawing->setOffsetX(0);
        $drawing->setOffsetY(-30);
        $drawing->setWorksheet($worksheet);

        $worksheet->getRowDimension(1)->setRowHeight(20);
        $worksheet->getRowDimension(2)->setRowHeight(30); // Ajustar la altura de la fila si es necesario
        $worksheet->getRowDimension(3)->setRowHeight(30);
        $worksheet->getColumnDimension('A')->setWidth(15); // Ajustar el ancho de la columna si es necesario
        $worksheet->getCell('A2')->setValue('');
        //activar autofiltro a las cabeceras
        $range = 'A5:T5';
        $worksheet->setAutoFilter($range);
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '00000000'],
                ],
            ],
        ];

        for ($col = 'A', $i = 0; $i < count($headers); $col++, $i++) {
            $worksheet->setCellValue($col . $row, ucfirst($headers[$i]));

            // Aplicar formato a la celda
            $style = $worksheet->getStyle($col . $row);
            // Alinear el texto al centro
            $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            // Aplicar negrita al texto
            $font = $style->getFont();
            $font->setBold(true);
            $font->getColor()->setRGB('FFFFFF');
            // Ajustar el ancho de la columna para adaptarse al contenido
            $worksheet->getColumnDimension($col)->setAutoSize(true);
            // Aplicar borde a las celdas

            $style->applyFromArray($borderStyle);
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('00b26e');
        }
        $line = 6;
        $count = 1;
        $initialColumn = 24;
        $untilWhichColumn = $initialColumn + $extraColumns;
        $continueColumn = $untilWhichColumn;
        $rowCell = 6;

        $greenStyle = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '7EF796'],
            ],
        ];

        $redStyle = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FF6B61'],
            ],
        ];
        $score_min = 0;
        foreach ($students as $student){
            //var_dump($student);
            $worksheet->setCellValueByColumnAndRow(0, $line, '');
            $worksheet->setCellValueByColumnAndRow(1, $line, $count);
            $worksheet->setCellValueByColumnAndRow(2, $line, strtoupper($student['firstname']));
            $worksheet->setCellValueByColumnAndRow(3, $line, strtoupper($student['lastname']));

            $documentTypeText = self::getDocumentTypeText($student['type_document']);
            $worksheet->setCellValueByColumnAndRow(4, $line, $documentTypeText);
            $worksheet->setCellValueByColumnAndRow(5, $line, $student['number_document']);
            $worksheet->getStyleByColumnAndRow(5, $line)->getNumberFormat()->setFormatCode("@");
            $worksheet->setCellValueByColumnAndRow(6, $line, $student['age']);
            $worksheet->setCellValueByColumnAndRow(7, $line, $student['gender']);

            $instructionTypeText = self::getInstructionTypeText($student['instruction']);
            $worksheet->setCellValueByColumnAndRow(8, $line, $instructionTypeText);
            $worksheet->setCellValueByColumnAndRow(9, $line, strtolower($student['email']));
            $worksheet->setCellValueByColumnAndRow(10, $line, $student['name_company']);
            $worksheet->setCellValueByColumnAndRow(11, $line, $student['contact_manager']);
            $worksheet->setCellValueByColumnAndRow(12, $line, $student['position_company']);

            $stakeholdersTypeText = self::getStakeholderTypeText($student['stakeholders']);
            $worksheet->setCellValueByColumnAndRow(13, $line, $stakeholdersTypeText);
            $worksheet->setCellValueByColumnAndRow(14, $line, $student['record_number']);
            $worksheet->setCellValueByColumnAndRow(15, $line, $student['area']);
            $worksheet->setCellValueByColumnAndRow(16, $line, $student['department']);
            $worksheet->setCellValueByColumnAndRow(17, $line, $student['headquarters']);
            //$worksheet->getColumnDimensionByColumn(17)->setAutoSize(true);
            $worksheet->setCellValueByColumnAndRow(18, $line, 'PETROPERU');
            $worksheet->setCellValueByColumnAndRow(19, $line, $student['session_id']);
            $worksheet->setCellValueByColumnAndRow(20, $line, $student['session_name']);
            $worksheet->setCellValueByColumnAndRow(21, $line, $student['display_start_date']);
            foreach ($student['courses'] as $course) {
                $worksheet->setCellValueByColumnAndRow(22, $line, $course['code']);
                $worksheet->setCellValueByColumnAndRow(23, $line, $course['title']);

                foreach ($course['evaluations'] as $key => $value) {
                    $worksheet->setCellValueByColumnAndRow($initialColumn, $rowCell, round($value, 0));
                    $worksheet->getColumnDimensionByColumn($initialColumn)->setAutoSize(true);
                    $initialColumn++;
                    if ($initialColumn >= $untilWhichColumn) {
                        $initialColumn = 24;
                    };
                }
                $rowCell++;
                //columns finales certificado, puntaje y estado del alumno
                $certificate = $this->getScoreCertificate($student['id'],$course['code'],$student['session_id'],true);
                $status = 'Desaprobado';
                $has_certificate = 'No';
                if(empty($certificate)){
                    $score = 0;
                } else {
                    $score_min = $certificate['certif_min_score'];
                    $score = $certificate['score'];
                    if(intval(round($score,0)) >= $score_min){
                        $status = 'Aprobado';
                        $has_certificate = 'Si';
                    }
                }

                $worksheet->setCellValueByColumnAndRow($continueColumn, $line, $score_min);
                $worksheet->getColumnDimensionByColumn($continueColumn)->setAutoSize(true);
                $continueColumn++;

                $worksheet->setCellValueByColumnAndRow($continueColumn, $line, round($score,0));
                $cellScore = $worksheet->getCellByColumnAndRow($continueColumn, $line)->getValue();
                $cellScoreStyle = $worksheet->getCellByColumnAndRow($continueColumn, $line)->getStyle();
                if ($cellScore >= $score_min) {
                    $cellScoreStyle->applyFromArray($greenStyle);
                } else {
                    $cellScoreStyle->applyFromArray($redStyle);
                }

                $worksheet->getColumnDimensionByColumn($continueColumn)->setAutoSize(true);
                $continueColumn++;

                $worksheet->setCellValueByColumnAndRow($continueColumn, $line, $has_certificate);
                $worksheet->getColumnDimensionByColumn($continueColumn)->setAutoSize(true);
                $continueColumn++;

                $worksheet->setCellValueByColumnAndRow($continueColumn, $line, $status);
                $worksheet->getColumnDimensionByColumn($continueColumn)->setAutoSize(true);
                $cellStatus = $worksheet->getCellByColumnAndRow($continueColumn, $line)->getValue();
                $cellStatusStyle = $worksheet->getCellByColumnAndRow($continueColumn, $line)->getStyle();
                if ($cellStatus == 'Aprobado') {
                    $cellStatusStyle->applyFromArray($greenStyle);
                } else {
                    $cellStatusStyle->applyFromArray($redStyle);
                }

                if ($continueColumn >= $untilWhichColumn) {
                    $continueColumn = $untilWhichColumn;
                };
            }

            $worksheet->getStyle("A$line:AD$line")->applyFromArray($borderStyle);
            $line++;
            $count++;
        }

        $fileName = $nameFile . $date_format . '.xlsx';
        $file = api_get_path(SYS_ARCHIVE_PATH) . api_replace_dangerous_char($fileName);
        $writer = new Xlsx($spreadsheet);
        $writer->save($file);
        DocumentManager::file_send_for_download($file, true, $fileName);
        exit;

    }

    public function getScoreCertificate($idUser, $codeCourse, $idSession, $stop = false): array
    {

        $tbl_gradebook_category = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $tbl_gradebook_score_log = Database::get_main_table(TABLE_MAIN_GRADEBOOK_SCORE_LOG);
        $tbl_gradebook_certificate= Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $sql = "SELECT
                    gsl.id,
                    gc.course_code,
                    gc.certif_min_score,
                    gc.require_all_quizzes,
                    gc.weight,
                    gc.session_id,
                    gsl.category_id,
                    gsl.score,
                    gsl.user_id,
                    CASE WHEN gcf.user_id IS NOT NULL THEN 1 ELSE 0 END AS has_certificate
                FROM $tbl_gradebook_category gc
                INNER JOIN $tbl_gradebook_score_log gsl ON gsl.category_id = gc.id
                LEFT JOIN $tbl_gradebook_certificate gcf ON gcf.user_id = gsl.user_id
                WHERE gc.course_code = '$codeCourse' AND gc.session_id = $idSession AND gsl.user_id = $idUser
                ORDER BY gsl.id DESC LIMIT 1;";

        $result = Database::query($sql);
        $list = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {

                $list = [
                    'id' => $row['id'],
                    'course_code' => $row['course_code'],
                    'certif_min_score' => (int)$row['certif_min_score'],
                    'require_all_quizzes' => (int)$row['require_all_quizzes'],
                    'weight' => (double)$row['weight'],
                    'session_id' => $row['session_id'],
                    'category_id' => $row['category_id'],
                    'score' => (double)$row['score'],
                    'user_id' => $row['user_id'],
                ];
            }
        }

        if (empty($list)) {
            return [];
        }

        if($stop){
            return $list;
        } else {
            $sql = "SELECT * FROM $tbl_gradebook_certificate gc WHERE gc.user_id = ".$list['user_id']." AND gc.cat_id = ".$list['category_id'].";";
            $result = Database::query($sql);
            /*$total = boolval(Database::num_rows($result));
            var_dump($total);*/
            $has_certificate = false;
            $icon = api_get_path(WEB_PLUGIN_PATH) . 'proikos/images/sad.png';
            if (Database::num_rows($result) > 0) {
                $has_certificate = true;
                $icon = api_get_path(WEB_PLUGIN_PATH) . 'proikos/images/happy.png';
            }

            return [
                'icon' => $icon,
                'has_certificate' =>  $has_certificate
            ];
        }
    }

    public function getStakeholdersUser($idUser):int
    {
        $table = Database::get_main_table(self::TABLE_PROIKOS_USERS);
        $sql = "SELECT ppu.stakeholders FROM $table ppu WHERE ppu.user_id = $idUser;";
        $result = Database::query($sql);
        $idStakeholders = 0;
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                if(!is_null($row['stakeholders'])){
                    $idStakeholders = $row['stakeholders'];
                } else {
                    $idStakeholders = 0;
                }
            }
        }
        return $idStakeholders;
    }
    public function getCountUser($idSession){
        $table = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $sql = "SELECT count(*) as total FROM $table src WHERE src.session_id=$idSession;";
        $result = Database::query($sql);
        $courses = null;
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $courses = $row['total'];
            }
        }
        return $courses;
    }

    public function getIdCourseSession($idSession)
    {
        $table = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $sql = "SELECT count(*) as total FROM $table src WHERE src.session_id=$idSession;";
        $result = Database::query($sql);
        $courses = null;
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $courses = $row['total'];
            }
        }
        return $courses;
    }

    public function getTotalCourseSession($idSession, $total){
        $table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $sql = "SELECT src.c_id FROM $table src WHERE src.session_id=$idSession;";
        $result = Database::query($sql);
        $courses = null;
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                if($total>=1){
                    $courses = $row['c_id'];
                }
            }
        }
        return $courses;
    }

    public function getRequirementsSessionCourseUser($idSession){
        $table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $sql = "SELECT src.c_id FROM $table src WHERE src.session_id=$idSession;";
        $result = Database::query($sql);
        $courses = [];
        $checks = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $courses[]= $row['c_id'];
            }
        }
        $userId = api_get_user_id();
        $em = Database::getManager();
        /** @var SequenceResourceRepository $sequenceResourceRepository */
        $sequenceResourceRepository = $em->getRepository(SequenceResource::class);

        foreach ($courses as $course){
            $sequences = $sequenceResourceRepository->getRequirements($course, 1);
            $sequenceList = $sequenceResourceRepository->checkRequirementsForUser($sequences, 1, $userId, $course);
            $checks = self::checkSequenceAreCompleted($sequenceList);
        }
        return $checks;
    }
    public function getSessionDatesNext($categoryID=0): array
    {
        $tableSession = Database::get_main_table(TABLE_MAIN_SESSION);
        $sql = "SELECT s.name, s.display_start_date FROM $tableSession s JOIN session_category sc
        ON sc.id = s.session_category_id WHERE display_start_date >= CURDATE() AND s.session_category_id = $categoryID";
        $result = Database::query($sql);
        $list = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $list[]= $row['name'];
            }
        }
        return $list;
    }

    public function getUsers() {
        $tableUser = Database::get_main_table(TABLE_MAIN_USER);
        $tableUserProikos = Database::get_main_table(self::TABLE_PROIKOS_USERS);
        $sql = "SELECT u.id, ppu.user_id, u.firstname, u.lastname, u.username, u.email, ppu.phone, ppu.number_document,
                ppu.ruc_company, ppu.name_company, ppu.code_reference, ppu.stakeholders
                FROM $tableUser u LEFT JOIN $tableUserProikos ppu ON ppu.user_id = u.id;";
        $result = Database::query($sql);
        $list = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $action = Display::url(
                    Display::return_icon(
                        'edit.png',
                        null,
                        [],
                        ICON_SIZE_SMALL),
                    api_get_path(WEB_PLUGIN_PATH).'proikos/src/users_management.php?action=edit&user_id='.$row['id']
                );
                /*$action .= Display::url(
                    Display::return_icon(
                        'delete.png',
                        '',
                        [],
                        ICON_SIZE_SMALL
                    ),
                    api_get_path(WEB_PLUGIN_PATH)
                );*/

                $list[] = [
                    'id' => $row['id'],
                    'name' => $row['firstname'] . ' ' . $row['lastname'],
                    'email' => $row['email'],
                    'username' => $row['username'],
                    'number_document' => $row['number_document'],
                    'phone' => !empty($row['phone']) ? $row['phone'] : '-',
                    'ruc' => !empty($row['ruc_company']) ? $row['ruc_company'] : $this->get_lang('Unregistered'),
                    'company' => !empty($row['name_company']) ? $row['name_company'] : $this->get_lang('Unregistered'),
                    'actions' => $action
                ];
            }
        }

        return $list;
    }

    public function getSpecificCourseFeature() {
        $coursesTarget = ['PTRA'];
        $courseInTarget = (isset($_GET['c']) && in_array($_GET['c'], $coursesTarget)) ||
            (isset($_GET['cidReq']) && in_array($_GET['cidReq'], $coursesTarget));
        $requireUploadMapFiles = !empty($_POST) && (
                empty($_FILES['user_attachment_cert_ext']['tmp_name']) ||
                empty($_FILES['user_attachment_dj']['tmp_name'])
            );
        $documentMap = [
            'user_attachment_cert_ext' => 'certificado_externo',
            'user_attachment_dj'       => 'declaracion_jurada'
        ];

        return (object)[
            'courses_target' => $coursesTarget,
            'course_in_target' => $courseInTarget,
            'require_upload_map_files' => $requireUploadMapFiles,
            'document_map' => $documentMap,
            'validate_upload' => function () use ($courseInTarget, $requireUploadMapFiles) {
                if ($courseInTarget && $requireUploadMapFiles) {
                    return (
                        '<div class="form-group alert alert-danger" role="alert" style="grid-column: span 2;">'.
                        'Adjutar los documentos requeridos para la inscripción'
                        .'</div>'
                    );
                }

                return '';
            },
            'upload_buttons_ui' => function() use ($courseInTarget) {
                if (!$courseInTarget) {
                    return '';
                }

                return (
                <<<EOT
                    <br>
                    <div class="form-group">
                        <label for="user_attachment_cert_ext">Adjuntar certificado externo</label>
                        <input type="file" name="user_attachment_cert_ext" id="user_attachment_cert_ext" class="form-control input_user_attachment" style="display: none;" />
                        <button class="btn btn-default form-control user_attachment_doc" data-input="user_attachment_cert_ext" type="button">
                            <em class="fa fa-paperclip"></em> Cargar archivo
                        </button>
                    </div>

                    <div class="form-group">
                        <label for="user_attachment_dj">Adjuntar declaración jurada</label>
                        <input type="file" name="user_attachment_dj" id="user_attachment_dj" class="form-control input_user_attachment" style="display: none;" />
                        <button class="btn btn-default form-control user_attachment_doc" data-input="user_attachment_dj" type="button">
                            <em class="fa fa-paperclip"></em> Cargar archivo
                        </button>
                    </div>

                    <script>
                        $(function () {
                            const \$btnAdd = $('.user_attachment_doc');
                            const \$input = $('.input_user_attachment');

                            \$btnAdd.on('click', function (e) {
                                e.preventDefault();
                                const inputId = $(this).data('input');
                                const \$inputRef = $('#' + inputId);
                                \$inputRef.click();
                            });

                            \$input.on('change', function (e) {
                                const name = $(this).attr('name');
                                const fileName = e.target?.files[0]?.name;
                                const \$btnAddRef = $('.user_attachment_doc[data-input="' + name + '"]');
                                if (fileName) {
                                    \$btnAddRef.html('<em class="fa fa-paperclip"></em> ' + fileName);
                                } else {
                                    \$btnAddRef.html('<em class="fa fa-paperclip"></em> Cargar archivo');
                                }
                            });
                        });
                    </script>
EOT
                );
            },
            'save_files' => function($user_id) use ($documentMap, $courseInTarget) {
                if (!$courseInTarget) {
                    return false;
                }

                $courseCode = $_GET['c'] ?? $_GET['cidReq'];
                $baseUploadDir = api_get_path(SYS_APP_PATH) . 'upload/proikos_user_documents/';
                $userCourseDir = $baseUploadDir . $user_id . '/' . $courseCode . '/';

                if (!file_exists($userCourseDir)) {
                    mkdir($userCourseDir, 0775, true);
                }

                foreach ($documentMap as $inputName => $documentName) {
                    if (!empty($_FILES[$inputName]['tmp_name']) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
                        $originalName = basename($_FILES[$inputName]['name']);
                        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                        $fileName = $documentName . '.' . $extension;
                        $destination = $userCourseDir . $fileName;
                        move_uploaded_file($_FILES[$inputName]['tmp_name'], $destination);
                    }
                }

                return true;
            },
            'get_actions' => function($user_id) use ($courseInTarget) {
                if (!$courseInTarget) {
                    return '';
                }

                $courseCode = $_GET['c'] ?? $_GET['cidReq'];
                $downloadCertificadoExternoButton = '';
                $downloadDJButton = '';
                $baseUploadDir = api_get_path(SYS_APP_PATH) . 'upload/proikos_user_documents/';
                $userCourseDir = $baseUploadDir . $user_id . '/' . $courseCode;
                $userCertificadoExterno = $userCourseDir . '/certificado_externo.*';
                $userDeclaracionJurada = $userCourseDir . '/declaracion_jurada.*';

                if (!empty(glob($userCertificadoExterno))) {
                    $downloadLink = glob($userCertificadoExterno)[0];
                    $filename = basename($downloadLink);
                    $downloadUrl = api_get_path(WEB_PATH) . 'plugin/proikos/src/ajax.php?action=download_user_uploaded_documents&user_id=' . $user_id
                        . '&course_code=' . urlencode($courseCode)
                        . '&filename=' . urlencode($filename);
                    $downloadCertificadoExternoButton = '<a class="btn btn-small btn-default" style="margin-left: 8px;" href="' . $downloadUrl . '">'.
                        get_lang("Certificado Externo").'</a>';
                }

                if (!empty(glob($userDeclaracionJurada))) {
                    $downloadLink = glob($userDeclaracionJurada)[0];
                    $filename = basename($downloadLink);
                    $downloadUrl = api_get_path(WEB_PATH) . 'plugin/proikos/src/ajax.php?action=download_user_uploaded_documents&user_id=' . $user_id
                        . '&course_code=' . urlencode($courseCode)
                        . '&filename=' . urlencode($filename);
                    $downloadDJButton = '<a class="btn btn-small btn-default" style="margin-left: 8px;" href="' . $downloadUrl . '">'.
                        get_lang("Declaración Jurada").'</a>';
                }

                return $downloadCertificadoExternoButton . $downloadDJButton;
            }
        ];
    }

    public function get_icon($iconName): string
    {
        $iconPathWeb = '';
        $icon_path = __DIR__ . '/images/' . $iconName . '.png';
        if (file_exists($icon_path)) {
            $iconPathWeb = api_get_path(WEB_PLUGIN_PATH).'proikos/images/' . $iconName . '.png';
        }
        return $iconPathWeb;
    }

    public function generateDownloadLinkAttachCertificates($userId, $userFullName, $sessionId): string
    {
        $baseUploadDir = api_get_path(SYS_APP_PATH) . 'upload/proikos_user_documents/';
        $userSessionDir = $baseUploadDir . $userId . '/' . $sessionId;
        $icon = $this->get_icon('attach');
        $icon_na = $this->get_icon('attach_na');
        // if directory $userSessionDir exists and is not empty
        if (is_dir($userSessionDir) && count(scandir($userSessionDir)) > 2) {
            $downloadUrl = api_get_path(WEB_PATH) . 'plugin/proikos/src/ajax.php?action=download_user_uploaded_documents&user_id=' . $userId
                . '&session_id=' . $sessionId . '&user_full_name=' . urlencode($userFullName);
            $downloadCertUploadedLink = Display::url(
                Display::img($icon, $this->get_lang('DownloadAttachedCertificates'),['width' => '32px']),
                $downloadUrl
            );
        } else {
            $downloadCertUploadedLink = Display::img($icon_na, $this->get_lang('AttachedDocumentsNotAvailable'),['width' => '32px']);
        }

        return $downloadCertUploadedLink;
    }

    public function validEmail($email) {
        if (empty($email)) {
            return false;
        }

        $table = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT email FROM $table WHERE email = '$email'";
        $result = Database::query($sql);
        $emailFound = '';
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $emailFound = $row['email'];
            }
        }

        if (empty($emailFound)) {
            return true;
        }

        return 'El correo electrónico ingresado ya existe en el sistema';
    }

    public function validUserDNI($NumDoc)
    {
        if (empty($NumDoc)) {
            return false;
        }

        $table = Database::get_main_table(self::TABLE_PROIKOS_USERS);
        $sql = "SELECT id FROM $table WHERE number_document = '$NumDoc'";
        $result = Database::query($sql);
        $userFound = '';
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $userFound = $row['id'];
            }
        }

        if (empty($userFound)) {
            return true;
        }

        return 'El número de documento ingresado ya existe en el sistema';
    }

    public function contratingCompaniesModel()
    {
        require_once __DIR__ . '/src/model/PluginProikosContratingCompanies.php';

        return (new PluginProikosContratingCompanies(
            self::TABLE_PROIKOS_CONTRATING_COMPANIES,
            self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_CAB,
            self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_DET
        ));
    }

    public function contratingCompaniesQuotaCabModel()
    {
        require_once __DIR__ . '/src/model/PluginProikosContratingCompaniesQuotaCab.php';

        return (new PluginProikosContratingCompaniesQuotaCab(
            self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_CAB,
            self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_DET,
            self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_SESSION,
            self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_SESSION_DET,
            self::CATEGORY_DESC
        ));
    }

    public function contratingCompaniesQuotaDetModel()
    {
        require_once __DIR__ . '/src/model/PluginProikosContratingCompaniesQuotaDet.php';

        return (new PluginProikosContratingCompaniesQuotaDet(
            self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_DET
        ));
    }

    public function contratingCompaniesQuotaSessionModel()
    {
        require_once __DIR__ . '/src/model/PluginProikosContratingCompaniesQuotaSession.php';

        return (new PluginProikosContratingCompaniesQuotaSession(
            self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_SESSION,
            self::CATEGORY_DESC
        ));
    }

    public function contratingCompaniesQuotaSessionDetModel()
    {
        require_once __DIR__ . '/src/model/PluginProikosContratingCompaniesQuotaSessionDet.php';

        return (new PluginProikosContratingCompaniesQuotaSessionDet(
            self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_SESSION_DET,
            self::TABLE_PROIKOS_USERS
        ));
    }

    function generateRandomCode(): int
    {
        // Generate a random 5-digit number
        return rand(10000, 99999);
    }

    public function deleteRowQuotaCompany($cadID, $idQuota)
    {
        $table = Database::get_main_table(self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_DET);
        $tableTwo = Database::get_main_table(self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_CAB);
        $sql = "SELECT count(*) as total FROM $table pcq WHERE pcq.cab_id = '$cadID';";
        Database::query($sql);
        $result = Database::query($sql);
        $total = 0;
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $total = intval($row['total']);
        }

        $sql = "DELETE FROM ".$table." pcq WHERE pcq.cab_id = '$cadID' AND pcq.id = '$idQuota'; ";
        Database::query($sql);

        if($total == 1){
            $sql = "DELETE FROM ".$tableTwo." tcq WHERE tcq.id = '$cadID'; ";
            Database::query($sql);
            return true;
        }
        return false;
    }

    public function getCRUDQuotaDet(FormValidator $form, $defaultCourseDetail = [], $disableActions = false)
    {
        $disableActions = $disableActions ? 1 : 0;

        $sessionModes = [
            '0' => $this->get_lang('Mode'),
            '1' => self::CATEGORY_DESC[self::CATEGORY_ASINCRONO],
            '2' => self::CATEGORY_DESC[self::CATEGORY_SINCRONO]
        ];
        $sessionModes = json_encode($sessionModes);

        $sessionCategories = [
            '0' => $this->get_lang('SelectSessionCategory'),
        ];
        $allSessionCategory = SessionManager::get_all_session_category();
        if (!empty($allSessionCategory)) {
            foreach ($allSessionCategory as $category) {
                $sessionCategories[$category['id']] = $category['name'];
            }
        }
        $sessionCategories = json_encode($sessionCategories);

        $deleteIcon = Display::return_icon(
            'delete.png',
            get_lang('Delete'),
            [],
            ICON_SIZE_SMALL
        );

        $defaultIndex = 0;
        $courseDetailHasError = false;
        $courseDetailHasErrorClass = '';
        $courseDetailErrorMessage = '';

        if ($form->isSubmitted()) {
            $formValues = $form->getSubmitValues();
            $defaultCourseDetail = $formValues['course_detail'] ?? [];

            if (empty($defaultCourseDetail)) {
                $courseDetailHasError = true;
                $courseDetailHasErrorClass = 'has-error';
                $courseDetailErrorMessage = $this->get_lang('CoursesConfigurationRequired');
            } else {
                foreach ($defaultCourseDetail as $key => $value) {
                    if (empty($value['session_mode']) || empty($value['session_category_id']) || (empty($value['quota']) && $value['quota'] != 0) || $value['quota'] < 0) {
                        $courseDetailHasError = true;
                    }

                    if ($value['price_unit'] == -1) {
                        $courseDetailHasError = true;
                    }
                }

                if ($courseDetailHasError) {
                    $courseDetailHasErrorClass = 'has-error';
                    $courseDetailErrorMessage = $this->get_lang('CoursesConfigurationPleaseCompleteAllFields');
                }
            }
        }

        if (!empty($defaultCourseDetail)) {
            foreach ($defaultCourseDetail as $key => $value) {
                if ($key > $defaultIndex) {
                    $defaultIndex = $key;
                }
            }

            if ($defaultIndex >= 0) {
                $defaultIndex += 1;
            }
        }
        $actionForm = $form->getAttributes();
        $urlPlatform = substr(api_get_path('WEB_PATH'),0,-1);
        $urlCurrent = $urlPlatform.$actionForm['action'];
        $isContractor = api_is_contractor_admin();
        $cssDelete = 'display: inline-block;';
        if($isContractor){
            $cssDelete = 'display: none;';
        }
        $defaultCourseDetail = json_encode($defaultCourseDetail);

        $form->addHtml(
            <<<EOT
    <div class="form-group {$courseDetailHasErrorClass}">
        <label for="configure_courses" class="col-sm-2 control-label">
            <span class="form_required">*</span>
            Configurar Cupos
        </label>
        <div class="col-sm-8">
            <div class="card">
                <table class="table table-striped" style="margin-bottom: 0px;">
                    <thead>
                        <tr>
                            <th>{$this->get_lang('Mode')}</th>
                            <th>{$this->get_lang('SessionCategory')}</th>
                            <th class="text-center">{$this->get_lang('PriceUnitAbr')}</th>
                            <th class="text-center">{$this->get_lang('ContratingCompanyUserQuota')}</th>
                            <th style="text-align: center;">
                                <button type="button" class="btn btn-primary" id="add_course_session">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="course-detail-container">
                    </tbody>
                    <tfoot style="display: none;">
                        <tr>
                            <td colspan="3" style="text-align: right;">
                                <label for="total_quota" class="control-label">
                                    Total Nº Cupos
                                </label>
                            </td>
                            <td>
                                <input type="number" name="total_quota" id="total_quota" readonly class="form-control text-right">
                            </td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="3" style="text-align: right;">
                                <label for="total_quota" class="control-label">
                                    {$this->get_lang('ContratingCompanyUserQuotaTotalPrice')}
                                </label>
                            </td>
                            <td>
                                 <input type="text" name="total_price" id="total_price" readonly class="form-control text-right">
                            </td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <span class="help-inline help-block">{$courseDetailErrorMessage}</span>
        </div>
        <div class="col-sm-2"></div>
    </div>
    <script>
        let disableActions = {$disableActions};
        let index = {$defaultIndex};
        const sessionCategories = JSON.parse('{$sessionCategories}');
        const sessionModes = JSON.parse('{$sessionModes}');
        const plusButton = document.getElementById('add_course_session');
        if (1 == disableActions) {
            plusButton.style.display = 'none';
        }

        function addNewRow(itemIndex = null, itemSessionMode = null, itemType = null, itemQuota = null, id = null, itemPriceUnitQuota = null) {
            const tableBody = document.getElementById('course-detail-container');
            const newRow = document.createElement('tr');
            itemIndex = itemIndex === null ? index : itemIndex;

            // ----- Create the select element for session mode -----
            const sessionModeSelect = document.createElement('select');
            sessionModeSelect.name = 'course_detail[' + itemIndex + '][session_mode]';
            sessionModeSelect.className = 'form-control';
            sessionModeSelect.dataset.index = itemIndex;
            for (const [value, text] of Object.entries(sessionModes)) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = text;
                sessionModeSelect.appendChild(option);
            }

            // ----- Create the select element for session Category -----
            const sessionCategoriesSelect = document.createElement('select');
            sessionCategoriesSelect.name = 'course_detail[' + itemIndex + '][session_category_id]';
            sessionCategoriesSelect.className = 'form-control';
            sessionCategoriesSelect.dataset.index = itemIndex;
            for (const [value, text] of Object.entries(sessionCategories)) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = text;
                sessionCategoriesSelect.appendChild(option);
            }

            const inputIdHidden = id !== null ? (`<input type="hidden" name="course_detail[` + itemIndex + `][id]" value="` + id + `">`) : ``;

            newRow.innerHTML = `
                <td>
                    ` + sessionModeSelect.outerHTML + `
                </td>
                <td>
                    ` + sessionCategoriesSelect.outerHTML + `
                </td>
                <td>
                    ` + inputIdHidden  + `
                    <select name="course_detail[` + itemIndex + `][price_unit]" id="price_unit" class="form-control text-right">
                        <option value="-1" selected>Seleccionar</option>
                        <option value="35.40">35.40</option>
                        <option value="51.92">51.92</option>
                        <option value="0">0</option>
                    </select>
                </td>
                <td>
                    <input type="number" name="course_detail[` + itemIndex + `][quota]" id="quota" class="form-control text-right">
                </td>
                <td style="text-align: center;">
                    <a href="javascript:void(0);" class="btn btn-default" id="remove_item_` + itemIndex + `">
                        {$deleteIcon}
                    </a>
                    <a href="{$urlCurrent}&set=remove&idDet=` + id + `" style="{$cssDelete}" class="btn btn-default" id="delete_item_` + itemIndex + `">
                        {$deleteIcon}
                    </a>
                </td>`;
            tableBody.appendChild(newRow);

            // N. Quota
            const quotaInput = newRow.querySelector('input[name="course_detail[' + itemIndex + '][quota]"]');
            quotaInput.addEventListener('input', function() {
                updateTotalQuota();
                updateTotalPriceUnitQuota();
            });

            if (itemQuota != null) {
                quotaInput.value = itemQuota;
                quotaInput.dispatchEvent(new Event('input'));
            }

            // Price Quota
            const priceUnitQuotaInput = newRow.querySelector('select[name="course_detail[' + itemIndex + '][price_unit]"]');
            priceUnitQuotaInput.addEventListener('change', function() {
                updateTotalPriceUnitQuota();
            });

            if (itemPriceUnitQuota != null) {
                priceUnitQuotaInput.value = itemPriceUnitQuota;
                priceUnitQuotaInput.dispatchEvent(new Event('change'));
            }

            const deleteButton = newRow.querySelector('a[id="remove_item_' + itemIndex + '"]');
            const removeButton = newRow.querySelector('a[id="delete_item_' + itemIndex + '"]');
            deleteButton.addEventListener('click', function() {
                tableBody.removeChild(newRow);
                updateTotalQuota();
                updateTotalPriceUnitQuota();
            });

            const sessionModeSelectElement = newRow.querySelector('select[name="course_detail[' + itemIndex + '][session_mode]"]');
            $(sessionModeSelectElement).selectpicker({
                width: '120px',
                liveSearch: true
            });

            if (itemSessionMode != null) {
                sessionModeSelectElement.value = itemSessionMode;
                sessionModeSelectElement.dispatchEvent(new Event('change'));
            }

            const sessionCategorySelectElement = newRow.querySelector('select[name="course_detail[' + itemIndex + '][session_category_id]"]');
            $(sessionCategorySelectElement).selectpicker({
                width: '300px',
                liveSearch: true
            });

            if (itemType != null) {
                sessionCategorySelectElement.value = itemType;
                sessionCategorySelectElement.dispatchEvent(new Event('change'));
            }

            if (1 == disableActions) {
                deleteButton.style.display = 'none';
                priceUnitQuotaInput.disabled = true;
                quotaInput.disabled = true;
                $(sessionCategorySelectElement).prop("disabled", true);
                $(sessionCategorySelectElement).selectpicker('refresh');
                $(sessionModeSelectElement).prop("disabled", true);
                $(sessionModeSelectElement).selectpicker('refresh');
            } else {
                removeButton.style.display = 'none';
            }
        }

        function updateTotalQuota() {
            const quotaInputs = document.querySelectorAll('input[name^="course_detail["][name$="[quota]"]');
            let totalQuota = 0;
            quotaInputs.forEach(input => {
                const quotaValue = parseInt(input.value) || 0;
                totalQuota += quotaValue;
            });
            document.getElementById('total_quota').value = totalQuota;

            if (totalQuota > 0) {
                document.querySelector('tfoot').style.display = 'table-row-group';
            } else {
                document.querySelector('tfoot').style.display = 'none';
            }
        }

        function updateTotalPriceUnitQuota() {
            const inputs = document.querySelectorAll('input[name^="course_detail"], select[name^="course_detail"]');
            const courseData = {};

            inputs.forEach(input => {
                const match = input.name.match(/course_detail\[(\d+)]\[(\w+)]/);
                if (match) {
                    const index = match[1];
                    const key = match[2];

                    if (!courseData[index]) {
                        courseData[index] = {};
                    }

                    courseData[index][key] = parseFloat(input.value) || 0;
                }
            });

            let total = 0;
            for (const key in courseData) {
                const price_unit = courseData[key].price_unit || 0;
                const quota = courseData[key].quota || 0;
                total += price_unit * quota;
            }

            const totalFormatted = new Intl.NumberFormat('es-PE', {
                style: 'currency',
                currency: 'PEN'
              }).format(total);

            document.getElementById('total_price').value = totalFormatted;
        }

        if (plusButton) {
            plusButton.addEventListener('click', function() {
                addNewRow();
                index++;
            });
        }

        // Add initial row if needed
        let defaultCourseDetail = JSON.parse('{$defaultCourseDetail}');
        if (defaultCourseDetail && Object.keys(defaultCourseDetail)?.length > 0) {
            for (const [key, value] of Object.entries(defaultCourseDetail)) {
                addNewRow(parseInt(key), value.session_mode, value.session_category_id, value.quota, value.id, value.price_unit);
            }
        }

        if (Object.keys(defaultCourseDetail)?.length === 0) {
            // attach event
            if (plusButton) {
                plusButton.dispatchEvent(new Event('click'));
            }
        }
    </script>
EOT
        );

        return $courseDetailHasError;
    }

    public static function checkUserQuizCompletion($userId, $categoryId)
    {
        // Validate input parameters
        $userId = intval($userId);
        $categoryId = intval($categoryId);

        if (empty($userId) || empty($categoryId)) {
            return [
                'passed' => false,
                'reason' => 'Invalid parameters',
                'total_quizzes' => 0,
                'completed_count' => 0,
                'incomplete_count' => 0,
                'incomplete_quizzes' => [],
                'error' => true
            ];
        }

        // Load the gradebook category
        $category = Category::load($categoryId);

        if (empty($category) || !isset($category[0])) {
            return [
                'passed' => false,
                'reason' => 'Category not found',
                'total_quizzes' => 0,
                'completed_count' => 0,
                'incomplete_count' => 0,
                'incomplete_quizzes' => [],
                'error' => true
            ];
        }

        $category = $category[0];

        // Check if require_all_quizzes is enabled for this category
        if (!$category->get_require_all_quizzes()) {
            return [
                'passed' => true,
                'reason' => 'Quiz requirement not enabled for this category',
                'total_quizzes' => 0,
                'completed_count' => 0,
                'incomplete_count' => 0,
                'incomplete_quizzes' => [],
                'require_all_quizzes' => false
            ];
        }

        // Get course information
        $courseCode = $category->get_course_code();
        $sessionId = $category->get_session_id();
        $courseInfo = api_get_course_info($courseCode);

        if (empty($courseInfo)) {
            return [
                'passed' => false,
                'reason' => 'Course not found',
                'total_quizzes' => 0,
                'completed_count' => 0,
                'incomplete_count' => 0,
                'incomplete_quizzes' => [],
                'error' => true
            ];
        }

        $courseId = $courseInfo['real_id'];

        // Get all active quizzes in the course
        $tbl_quiz = Database::get_course_table(TABLE_QUIZ_TEST);

        // Build SQL query to get all active quizzes
        $sql = "SELECT DISTINCT iid as id, title, description
                FROM $tbl_quiz
                WHERE c_id = $courseId
                AND active = 1";

        // Add session filter if we're in a session context
        if (!empty($sessionId)) {
            $sql .= " AND (session_id = $sessionId OR session_id = 0 OR session_id IS NULL)";
        } else {
            // If not in a session, only get quizzes not tied to any session
            $sql .= " AND (session_id = 0 OR session_id IS NULL)";
        }

        $sql .= " ORDER BY title";

        $result = Database::query($sql);
        $allQuizzes = [];

        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $allQuizzes[$row['id']] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description']
            ];
        }

        $totalQuizzes = count($allQuizzes);

        // If no quizzes exist, consider it as passed
        if ($totalQuizzes == 0) {
            return [
                'passed' => true,
                'reason' => 'No quizzes in course',
                'total_quizzes' => 0,
                'completed_count' => 0,
                'incomplete_count' => 0,
                'incomplete_quizzes' => []
            ];
        }

        // Get completed quizzes for the user
        $tbl_track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

        $sql = "SELECT DISTINCT exe_exo_id, MAX(exe_result) as best_score, MAX(exe_date) as last_attempt
                FROM $tbl_track_exercises
                WHERE exe_user_id = $userId
                AND c_id = $courseId
                AND status != 'incomplete'";

        // Add session filter if applicable
        if (!empty($sessionId)) {
            $sql .= " AND session_id = $sessionId";
        } else {
            $sql .= " AND (session_id = 0 OR session_id IS NULL)";
        }

        $sql .= " GROUP BY exe_exo_id";

        $result = Database::query($sql);
        $completedQuizzes = [];
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $completedQuizzes[$row['exe_exo_id']] = [
                'best_score' => $row['best_score'],
                'last_attempt' => $row['last_attempt']
            ];
        }

        $completedCount = count($completedQuizzes);

        // Find incomplete quizzes
        $incompleteQuizzes = [];
        foreach ($allQuizzes as $quizId => $quizInfo) {
            if (!isset($completedQuizzes[$quizId])) {
                $incompleteQuizzes[$quizId] = $quizInfo;
            }
        }

        $incompleteCount = $totalQuizzes - $completedCount;

        // Determine pass/fail status
        $passed = ($incompleteCount == 0);

        // Build the result array
        $result = [
            'passed' => $passed,
            'reason' => $passed ? 'All quizzes completed' : 'Incomplete quizzes',
            'total_quizzes' => $totalQuizzes,
            'completed_count' => $completedCount,
            'incomplete_count' => $incompleteCount,
            'incomplete_quizzes' => $incompleteQuizzes,
            'completed_quizzes' => $completedQuizzes,
            'percentage' => $totalQuizzes > 0 ? round(($completedCount / $totalQuizzes) * 100, 2) : 100,
            'course_code' => $courseCode,
            'course_id' => $courseId,
            'session_id' => $sessionId,
            'category_id' => $categoryId,
            'user_id' => $userId,
            'require_all_quizzes' => true
        ];

        // Add detailed message
        if (!$passed) {
            $quizList = [];
            foreach ($incompleteQuizzes as $quiz) {
                $quizList[] = $quiz['title'];
            }
            $result['message'] = sprintf(
                    get_lang('YouMustCompleteAllQuizzesFirst'),
                    $incompleteCount,
                    $totalQuizzes
                ) . ': ' . implode(', ', $quizList);
        } else {
            $result['message'] = get_lang('AllQuizzesCompleted');
        }

        return $result;
    }


    public function renderModal()
    {
        if (!empty($_SESSION['proikos_modal_message'])) {
            $message = addslashes($_SESSION['proikos_modal_message']);
            $img = api_get_path(WEB_PLUGIN_PATH).'proikos/images/company-without-quotas.png';
            echo <<<HTML
                <style>
                    .modal-backdrop-custom {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background-color: rgba(0, 0, 0, 0.6);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        z-index: 1000;
                    }

                    #imageModal img {
                        width: 500px;
                        max-width: 100%;
                        max-height: 100%;
                        display: block;
                    }
                </style>
                <div id="imageModal" class="modal-backdrop-custom">
                    <img src="$img" alt="Imagen" />
                </div>
                <script>
                    document.getElementById('imageModal').addEventListener('click', function () {
                        this.style.display = 'none';
                    });
                </script>
HTML;

            unset($_SESSION['proikos_modal_message']);
        }
    }

    public function setModalMessage($message)
    {
        $_SESSION['proikos_modal_message'] = $message;
    }

    public function updateUserMetadata($userId, $metadata)
    {
        // convert metadata to JSON
        $metadataJson = json_encode($metadata);

        // get the old metadata and merge with the new one
        $sql = "SELECT metadata FROM ".Database::get_main_table(self::TABLE_PROIKOS_USERS)." WHERE user_id = $userId";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            $row = Database::fetch_array($result);

            $mergedMetadata = $metadata;
            if (!empty($row['metadata']) && $row['metadata'] != 'null') {
                $oldMetadata = json_decode($row['metadata'], true);
                $mergedMetadata = array_merge($oldMetadata, $metadata);
            }

            $metadataJson = json_encode($mergedMetadata, JSON_UNESCAPED_UNICODE);
        }

        // update the metadata in the database
        $sql = "UPDATE ".Database::get_main_table(self::TABLE_PROIKOS_USERS)." SET metadata = '$metadataJson' WHERE user_id = $userId";
        Database::query($sql);
    }

    public function getUserMetadata($userId)
    {
        $sql = "SELECT metadata FROM ".Database::get_main_table(self::TABLE_PROIKOS_USERS)." WHERE user_id = $userId";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            $row = Database::fetch_array($result);
            return json_decode($row['metadata'], true);
        }

        return [];
    }

    public function smowlFormLink(
        string $endpoint,
        array $jwtParams,
        array $getParams = []
    ): string {
        global $_configuration;
        $jwtParams['activityType'] = $_configuration['smowltech']['activityType'];
        $jwtParams['entityKey'] = $_configuration['smowltech']['entityKey'];
        $payload = [
            "iss" => 'smowl_custom_integration',
            "aud" => 'proikos',
            "iat" => time(),
            "exp" => time() + 3600 * 12, // 12 hours expiration
            "data" => $jwtParams
        ];

        $getParams['entityName'] = $_configuration['smowltech']['entityName'];
        $getParams['token'] = \Firebase\JWT\JWT::encode($payload, $_configuration['smowltech']['jwtSecret'], 'HS256');

        // If there is "token" and "entityName",they should be the first parameters
        if (isset($getParams['token']) && isset($getParams['entityName'])) {
            $token = $getParams['token'];
            $entityName = $getParams['entityName'];
            unset($getParams['token'], $getParams['entityName']);
            $getParams = ['token' => $token, 'entityName' => $entityName] + $getParams;
        }

        // If there is activityUrl or Course_link, it should be the last parameter
        if (isset($getParams['activityUrl'])) {
            $activityUrl = $getParams['activityUrl'];
            unset($getParams['activityUrl']);
            $getParams['activityUrl'] = $activityUrl;
        }

        if (isset($getParams['Course_link'])) {
            $activityUrl = $getParams['Course_link'];
            unset($getParams['Course_link']);
            $getParams['Course_link'] = $activityUrl;
        }

        return $endpoint . '?' . http_build_query($getParams);
    }

    public function smowlRegistrationEndpoint(
        $userId, $userName, $userEmail, $lang, $activityUrl, $sessionId, $exerciseId
    ): string
    {
        $registrationEndpoint = 'https://swl.smowltech.net/register/';
        $jwtParams = [
            'userId' => $userId,
            'activityId' => $exerciseId,
            'activityContainerId' => $sessionId
        ];
        $getParams = [
            'userName' => $userName,
            'userEmail' => $userEmail,
            'lang' => $lang,
            'type' => 0,
            'activityUrl' => $activityUrl
        ];

        return $this->smowlFormLink($registrationEndpoint, $jwtParams, $getParams);
    }

    public function smowlRegistrationPanel(
        $userId, $userName, $userEmail, $lang, $activityUrl, $sessionId, $exerciseId
    ): string
    {
        $registrationEndpoint = 'https://swl.smowltech.net/register/';
        $jwtParams = [
            'userId' => $userId,
            'activityId' => $exerciseId,
            'activityContainerId' => $sessionId
        ];
        $getParams = [
            'userName' => $userName,
            'userEmail' => $userEmail,
            'lang' => $lang,
            'type' => 0,
            'activityUrl' => $activityUrl
        ];

        $urlSmowlLink = $this->smowlFormLink($registrationEndpoint, $jwtParams, $getParams);

        $tpl = new Template('smowl', true, true, false, false, true, false);
        $tpl->assign('src_plugin', api_get_path(WEB_PLUGIN_PATH) . 'proikos/');
        $tpl->assign('url_smowl', $urlSmowlLink);
        $content = $tpl->fetch('proikos/view/proikos_register_smowl.tpl');

        return $content;
    }

    public function smowlMonitoringEndpoint(
        $userId, $userName, $userEmail, $lang, $sessionId, $exerciseId
    ): string
    {
        $monitoringEndpoint = 'https://swl.smowltech.net/monitor/';
        $jwtParams = [
            'userId' => $userId,
            'activityId' => $exerciseId,
            'activityContainerId' => $sessionId,
            'isMonitoring' => 1
        ];
        $getParams = [
            'userName' => $userName,
            'userEmail' => $userEmail,
            'lang' => $lang,
            'type' => 0
        ];

        return $this->smowlFormLink($monitoringEndpoint, $jwtParams, $getParams);
    }

    public function getData($from, $number_of_items, $column, $direction, $courseId, $sessionId, $keyword = null, $onlyQuantity = false)
    {
        $table_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $table_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $table_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $table_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $table_plugin_proikos_users = self::TABLE_PROIKOS_USERS;
        $table_plugin_easycertificate_send = self::TABLE_PLUGIN_EASY_CERTIFICATE_SEND;
        $table_track_e_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $table_c_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
        $table_track_e_course_access = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $table_gradebook_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);

        $sortable_columns = [
            's.display_end_date',
            'sru.id',
            'c.title',
            'u.lastname',
            'ppu.number_document',
        ];

        $order_by = isset($sortable_columns[$column]) ? $sortable_columns[$column] : 's.display_end_date';
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "
            SELECT
                u.id AS user_id,
                DATE(s.display_end_date) AS fecha_ex,
                SEC_TO_TIME((
                    SELECT SUM(UNIX_TIMESTAMP(logout_course_date) - UNIX_TIMESTAMP(login_course_date))
                    FROM $table_track_e_course_access
                    WHERE
                        UNIX_TIMESTAMP(logout_course_date) > UNIX_TIMESTAMP(login_course_date) AND
                        c_id = c.id AND
                        session_id = sru.session_id AND
                        user_id = u.id
                )) AS nro_horz,
                c.title AS nombre_curso,
                c.visual_code AS visual_code_curso,
                s.name AS session_name,
                CONCAT(u.firstname, ' ', u.lastname) AS nombre_apellido,
                COALESCE(
                    CASE
                        WHEN ppu.type_document = 1 THEN ppu.number_document
                        ELSE NULL
                    END,
                    'N/A'
                ) AS dni,
                COALESCE(ppu.ruc_company, '-') AS ruc_empresa,
                COALESCE(ppu.name_company, 'EMPRESA NO ASIGNADA') AS empresa,
                COALESCE(ppu.area, 'N/A') AS sede,
                COALESCE((
                  SELECT (
                    CASE WHEN pecs.certificate_id IS NOT NULL
                            AND CURRENT_DATE >= DATE(pecs.created_at)
                            AND (pecs.reminder_15_sent_at IS NULL OR CURRENT_DATE <= pecs.reminder_15_sent_at)
                        THEN 'VIGENTE'
                        ELSE 'CADUCADO'
                    END
                  ) FROM {$table_plugin_easycertificate_send} pecs
                  WHERE pecs.user_id = u.id
                    AND pecs.session_id = s.id
                    AND pecs.course_id = src.c_id
                    ORDER BY pecs.id DESC
                    LIMIT 1
                ), 'CERTIFICADO NO GENERADO') AS observacion,
            c.code AS course_code,
            c.id AS course_id,
            s.id AS session_id,
            src.c_id AS cat_id

            FROM {$table_session_rel_user} sru
            INNER JOIN {$table_session} s ON s.id = sru.session_id
            INNER JOIN {$table_user} u ON u.id = sru.user_id
            INNER JOIN {$table_session_rel_course} src ON src.session_id = s.id
            INNER JOIN {$table_course} c ON c.id = src.c_id
            INNER JOIN {$table_plugin_proikos_users} ppu ON ppu.user_id = u.id
            LEFT JOIN (
                SELECT te.exe_user_id, te.session_id,
                    MAX(te.exe_result / te.exe_weighting * 100) AS score
                FROM {$table_track_e_exercises} te
                INNER JOIN {$table_c_quiz} q ON q.id = te.exe_exo_id
                WHERE q.title LIKE '%Entrada%' OR q.title LIKE '%Entrance%'
                GROUP BY te.exe_user_id, te.session_id
            ) entrance_quiz ON entrance_quiz.exe_user_id = u.id AND entrance_quiz.session_id = s.id

            WHERE sru.relation_type IN (0, 2)
        ";

        if ($courseId === '%') {
            $sql .= " AND c.id LIKE '%'";
        } else {
            $sql .= " AND c.id = $courseId ";
        }

        if ($sessionId === '%') {
            $sql .= " AND s.id LIKE '%'";
        } else {
            $sql .= " AND s.id = $sessionId ";
        }

        if (!empty($keyword)) {
            $keyword = Database::escape_string($keyword);
            $sql .= " AND ppu.number_document = $keyword ";
        }

        if (api_is_contractor_admin()) {
            $rucCompany = self::getUserRucCompany();
            $sql .= " AND ppu.ruc_company = '$rucCompany' ";
        }

        $sql .= " ORDER BY 2 desc, 4 asc, 6 asc, 1 desc";

        if (isset($from) && isset($number_of_items) && !$onlyQuantity) {
            $sql .= " LIMIT $from, $number_of_items";
        }

        $result = Database::query($sql);
        $numRows = Database::num_rows($result);

        if ($onlyQuantity) {
            return $numRows;
        }

        $dataColumns = $this->getDATAcolumns(false);

        $list = [];
        $index = 1;
        while ($row = Database::fetch_array($result)) {
            $item = [
                $index,
                $row['fecha_ex'],
                $row['nro_horz'],
                $row['nombre_curso'] . (!empty($row['visual_code_curso']) ? ' (' . $row['visual_code_curso'] . ')' : ''),
                $row['session_name'],
                $row['nombre_apellido'],
                $row['dni'],
                $row['ruc_empresa'],
                $row['empresa'],
                $row['sede'],
            ];

            $rowIndex = 10;
            $cats = Category::load(
                null,
                null,
                $row['course_code'],
                null,
                null,
                $row['session_id'],
                'ORDER By id'
            );

            if (!empty($cats[0])) {
                $userScore = $this->getResultExerciseStudent($row['user_id'], $row['course_id'], $row['session_id'], false);

                $scoreCertificate = $this->getScoreCertificate($row['user_id'], $row['course_code'], $row['session_id'], false);
                $userScores = [];
                $userLinks = $cats[0]->get_links($row['user_id'], false, $row['course_code'], $row['session_id']);
                $examScore = [];
                foreach ($dataColumns as $columnKey => $columnName) {

                    foreach ($userLinks as $link) {

                        /*if (!$this->examInMap($columnKey, $link->get_name()) || !empty($examScore)) {
                            continue;
                        }*/

                        $exerKey = strtolower($link->get_name());

                        $score = round($userScore[$exerKey] ?? 0, 1);


                        $exeResult = $link->get_weight() > 0 ? round($score * ($link->get_weight() / 100), 2) : 0;
                        $examScore = [
                            'score' => round($score, 1),
                            'exeResult' => $exeResult
                        ];

                    }

                    /*if (empty($examScore)) {
                        $examScore = [
                            'score' => 'xxx',
                            'exeResult' => 'yyyy'
                        ];
                    }*/

                    $userScores[] = $examScore;
                }

                // Calc final score
                $finalScore = 0;
                foreach ($userScores as $userScore) {
                    $item[$rowIndex] = $userScore['score'];
                    $rowIndex++;
                    if ($userScore['score'] !== '-') {
                        $finalScore += $userScore['exeResult'];
                    }
                }

                $finalScore = round($finalScore, 2);
                $quizCheck = ProikosPlugin::checkUserQuizCompletion($row['user_id'], $cats[0]->get_id());
                if (isset($scoreCertificate['has_certificate'])) {
                    $approved = $scoreCertificate['has_certificate'] && $quizCheck['passed'];
                } else {
                    $approved = false;
                }
                $status = true === $approved
                    ? '<span class="label label-success">'.$this->get_lang('Approved').'</span>'
                    : '<span class="label label-danger">'.$this->get_lang('Failed').'</span>';

                $observation = $row['observacion'] === 'VIGENTE'
                    ? '<span class="label label-success">' . $row['observacion'] . '</span>'
                    : '<span class="label label-danger">' . $row['observacion'] . '</span>';

                $item[$rowIndex++] = $finalScore;
                $item[$rowIndex++] = $status;
                $item[$rowIndex++] = $observation;
                $downloadCertUploadedLink = $this->generateDownloadLinkAttachCertificates($row['user_id'], $row['nombre_apellido'], $sessionId);
                $item[$rowIndex] = $downloadCertUploadedLink;
            } else {
                foreach ($dataColumns as $column) {
                    $item[$rowIndex] = '-';
                    $rowIndex++;
                }

                $item[$rowIndex++] = 'ss-';
                $item[$rowIndex++] = '-sss';
                $item[$rowIndex] = '-';
            }

            $list[] = $item;

            $index++;
        }

        return $list;
    }

    public function getDATAcolumns($showFinalScoreColumn = true)
    {
        $columns = [
            'ex1' => 'Examen de entrada',
            'ex2' => 'Taller',
            'ex3' => 'Examen de salida'
        ];

        if ($showFinalScoreColumn) {
            $columns['final_score'] = 'Puntaje Final';
        }

        return $columns;
    }

    private function examInMap($key, $exam): bool
    {
        $examMap = [
            'ex1' => ['entrada', 'entrance'],
            'ex2' => ['prueba', 'test', 'taller', 'práctica', 'practical', 'practica'],
            'ex3' => ['salida', 'exit', 'final']
        ];

        if (empty($exam)) {
            return false;
        }

        if (!isset($examMap[$key]) || !is_array($examMap[$key])) {
            return false;
        }

        $exam = mb_strtolower($exam, 'UTF-8');
        $exam = trim($exam);

        foreach ($examMap[$key] as $examName) {
            if (mb_strpos($exam, $examName) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the last subscribed session by the user has restrictions
     */
    public function hasTimeInSessionRestriction($userId, $sessionIdEval)
    {
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $sql = "SELECT b.time_in_session FROM $tbl_session_rel_user a
            INNER JOIN session b ON a.session_id = b.id
            WHERE a.user_id = $userId
            ORDER BY a.registered_at DESC
            LIMIT 1";

        $result = Database::query($sql);
        $session = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $session = $row;
            }
        }

        if (empty($session) || empty($session['time_in_session'])) {
            return false;
        }

        $sql = "SELECT time_in_session FROM session WHERE id = $sessionIdEval";
        $result = Database::query($sql);
        $sessionEval = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $sessionEval = $row;
            }
        }

        // if last session time_in_session is minor than the session to be evaluated return true
        if (empty($sessionEval)) {
            return false;
        }

        if ($session['time_in_session'] < $sessionEval['time_in_session']) {
            return [
                'time_in_session' => $session['time_in_session']
            ];
        }

        return false;
    }

    public static function enableTranslate($elementdsId)
    {
        global $htmlHeadXtra;
        $elementdsId = json_encode($elementdsId);
        $userInfo = api_get_user_info();
        $allowedLanguages = [
            'english' => 'en',
            'spanish' => 'es'
        ];
        $selectedLanguage = $allowedLanguages[$userInfo['language']] ?? '';
        if (!empty($selectedLanguage) && $selectedLanguage != 'es') {
            setcookie('googtrans', '/' . $selectedLanguage);
            $htmlHeadXtra[] = <<<EOT
            <style>
                body {
                  top: 0 !important;
                }

                body>.skiptranslate, .goog-logo-link, .gskiptranslate, .goog-te-gadget span, .goog-te-banner-frame, #goog-gt-tt, .goog-te-balloon-frame, div#goog-gt-tt {
                  display: none !important;
                }

                .goog-te-gadget {
                  color: transparent !important;
                  font-size: 0px;
                }

                .goog-text-highlight {
                  background: transparent !important;
                  box-shadow: transparent !important;
                }

                #google_translate_element select {
                  background: #60C7E6;
                  color: #fff4e4;
                  border: none;
                  font-weight: bold;
                  border-radius: 3px;
                  padding: 8px 12px
                }
            </style>

            <script>
                 const elementdsId = JSON.parse('{$elementdsId}');
                 function googleTranslateElementInit() {
                    const options = {
                        autoDisplay: true,
                        includedLanguages: 'en,es',
                        layout: google.translate.TranslateElement.InlineLayout.HORIZONTAL
                    };

                    new google.translate.TranslateElement(options, 'google_translate_element');
                 }

                document.addEventListener('DOMContentLoaded', function() {
                    document.body.classList.add('notranslate');

                    elementdsId.forEach((elementId) => {
                        const element = document.getElementById(elementId);
                        if (element) {
                            element.classList.add('translate');
                        }
                    });
                });
            </script>
            <script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
EOT;
        }
    }

    public static function deleteUser($userId)
    {
        $sql = "DELETE FROM ".Database::get_main_table(self::TABLE_PROIKOS_USERS)." WHERE user_id = '$userId'";
        Database::query($sql);
    }

    public function checkRegisterLogData($userID, $courseID, $sessionID)
    {
        $codeRegister = $this->registerCodeSessionRelUser($userID, $sessionID);
        $tableLog = Database::get_main_table(self::TABLE_PROIKOS_DATA_LOG);
        $sql = "SELECT count(*) as total FROM $tableLog ppl WHERE ppl.user_id = $userID AND ppl.course_id = $courseID AND ppl.session_id = $sessionID AND ppl.registration_session_user = $codeRegister; ";
        $result = Database::query($sql);
        $total = 0;
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {
                $total = $row['total'];
            }
        }
        return intval($total);
    }

    public function getValuesRegisterData($userID, $courseID, $sessionID): array
    {
        $course_code = api_get_course_id();
        $session = api_get_session_info($sessionID);
        $em = Database::getManager();
        /** @var \Chamilo\CoreBundle\Entity\Repository\SessionRepository $sessionRepository */
        $sessionRepository = $em->getRepository('ChamiloCoreBundle:Session');
        /** @var \Chamilo\CoreBundle\Entity\Session $session */
        $sessionEM = $sessionRepository->find($sessionID);
        $sessionCategory = $sessionEM->getCategory();

        if(is_null($sessionCategory)){
            $categoryName = 'Ninguno';
        } else {
            $categoryName = $sessionCategory->getName();
        }

        $userScoreExams = $this->getResultExerciseStudent($userID, $courseID, $sessionID);
        $ponderacion_entrada = 0.10;  // 10%
        $ponderacion_salida = 0.30;   // 30%
        $ponderacion_taller = 0.60;   // 60%

        // Calcular el puntaje total ponderado
        $puntaje_total = (($userScoreExams['examen_de_entrada'] * $ponderacion_entrada) +
                ($userScoreExams['examen_de_salida'] * $ponderacion_salida) +
                ($userScoreExams['taller'] * $ponderacion_taller)) / 20 * 100;

        if ($puntaje_total == 0) {
            $status = $this->get_lang('Registered');
            $status_id = 1;
        } else if ($userScoreExams['examen_de_entrada'] == 0 || $userScoreExams['examen_de_salida'] == 0 || $userScoreExams['taller'] == 0) {
            $status = $this->get_lang('Failed');
            $status_id = 0;
        } else if ($puntaje_total >= 70.5) {
            $status = $this->get_lang('Approved');
            $status_id = 2;
        } else {
            $status = $this->get_lang('Failed');
            $status_id = 0;
        }

        $userInfoProikos = $this->getInfoUserProikos($userID);

        $timeSpent = api_time_to_hms(
            Tracking::get_time_spent_on_the_course(
                $userID,
                $courseID,
                $sessionID
            )
        );
        $registerSessionCodeUser = $this->registerCodeSessionRelUser($userID, $sessionID);
        return [
            'username' => $userInfoProikos['username'],
            'registration_session_user' => $registerSessionCodeUser,
            'user_id' => $userID,
            'course_id' => $courseID,
            'session_id' => $sessionID,
            'course_code' => $course_code,
            'session_name' => $session['name'],
            'session_category_id' => $session['session_category_id'],
            'session_category_name' => $categoryName,
            'email' => $userInfoProikos['email'],
            'last_name' => $userInfoProikos['lastname'],
            'first_name' => $userInfoProikos['firstname'],
            'dni' => $userInfoProikos['number_document'],
            'company_ruc' => $userInfoProikos['ruc_company'] ?? '-',
            'company_name' => $userInfoProikos['name_company'],
            'stakeholders' => $userInfoProikos['stakeholders'],
            'area' => $userInfoProikos['area'],
            'metadata_exists' => (bool)$userInfoProikos['metadata'],
            'entrance_exam' => $userScoreExams['examen_de_entrada'] ?? 0,
            'workshop' => $userScoreExams['taller'] ?? 0,
            'exit_exam' => $userScoreExams['examen_de_salida'] ?? 0,
            'score' => $puntaje_total,
            'certificate_status' => $status == 'Aprobado' ? 1 : 0,
            'status' => $status,
            'status_id' => $status_id,
            'time_course' => $timeSpent,
            'observations' => $values['observations'] ?? '-',
            //'certificate_issue_date' => $values['certificate_issue_date'],
            //'certificate_expiration_date' => $values['certificate_expiration_date'],
        ];
    }

    public function registerCodeSessionRelUser($userID, $sessionID)
    {
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $sql = " SELECT su.id FROM $tbl_session_rel_user su WHERE su.user_id = $userID AND su.session_id = $sessionID; ";
        $result = Database::query($sql);
        $codeID = 0;
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {
                $codeID = $row['id'];
            }
        }
        return $codeID;
    }

    public function registerData($values = [], $format = false)
    {
        if(empty($values)){
            return 0;
        }

        $table = Database::get_main_table(self::TABLE_PROIKOS_DATA_LOG);

        if($format){
            $id = Database::insert($table, $values);
        } else {
            $params = [
                'username' => $values['username'],
                'user_id' => $values['user_id'],
                'registration_session_user' => $values['registration_session_user'],
                'course_id' => $values['c_id'],
                'session_id' => $values['session_id'],
                'course_code' => $values['code'],
                'session_name' => $values['session_name'],
                'session_category_id' => $values['session_category_id'],
                'session_category_name' => $values['session_category_name'],
                'email' => $values['email'],
                'last_name' => $values['lastname'],
                'first_name' => $values['firstname'],
                'dni' => $values['DNI'],
                'company_ruc' => $values['ruc_company'] ?? '-',
                'company_name' => $values['name_company'],
                'stakeholders' => $values['stakeholders'],
                'area' => $values['area'],
                'metadata_exists' => $values['metadata_exists'],
                'entrance_exam' => $values['exams']['examen_de_entrada'] ?? 0,
                'workshop' => $values['exams']['taller'] ?? 0,
                'exit_exam' => $values['exams']['examen_de_salida'] ?? 0,
                'score' => $values['score'],
                'certificate_status' => $values['certificate_status'],
                'status' => strip_tags($values['status']),
                'status_id' => $values['status_id'],
                'time_course' => $values['time_course'],
                'observations' => $values['observations'] ?? '-',
                //'certificate_issue_date' => $values['certificate_issue_date'],
                //'certificate_expiration_date' => $values['certificate_expiration_date'],
            ];
            $id = Database::insert($table, $params);
        }


        if ($id > 0) {
            return $id;
        }

        return 0;
    }

    public function getExercisesSessionAndCourse($sessionID): array
    {
        $tbl_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
        $sql = "
            SELECT cq.iid as exercise_id FROM $tbl_quiz cq
            INNER JOIN session_rel_course src ON src.c_id = cq.c_id
            WHERE src.session_id = $sessionID;
        ";
        $result = Database::query($sql);
        $exercise = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {
                $exercise[] = $row['exercise_id'];
            }
        }
        return $exercise;
    }

    public function deleteTrackExercise($exerciseID, $userID, $sessionID): bool
    {
        $tbl_track_exercise = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

        $sql = "SELECT tte.exe_id FROM $tbl_track_exercise tte WHERE exe_user_id = $userID AND exe_exo_id = $exerciseID AND session_id = $sessionID;";
        $result = Database::query($sql);
        $exeId = 0;
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {
                $exeId = $row['exe_id'];
            }
        }

        $sql = "DELETE FROM $tbl_track_exercise WHERE exe_user_id = $userID AND exe_exo_id = $exerciseID AND session_id = $sessionID; ";
        $result = Database::query($sql);

        if (Database::affected_rows($result) != 1) {
            return false;
        }

        Event::addEvent(
            LOG_EXERCISE_ATTEMPT_DELETE,
            LOG_EXERCISE_ATTEMPT,
            $exeId,
            api_get_utc_datetime()
        );
        Event::addEvent(
            LOG_EXERCISE_ATTEMPT_DELETE,
            LOG_EXERCISE_AND_USER_ID,
            $exeId.'-'.$userID,
            api_get_utc_datetime()
        );

        return true;

    }

    public function getLPSession($sessionID): array
    {
        $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $sql = "SELECT lp.c_id as course_id, lp.iid as lp_id, src.session_id FROM $tbl_lp lp
                INNER JOIN $tbl_session_course src ON src.c_id = lp.c_id
                WHERE src.session_id = $sessionID;
        ";
        $result = Database::query($sql);
        $lps = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {
                $lps[] = $row;
            }
        }
        return $lps;
    }

    public function getDataUsersReportProikos($dni = null, $courseId = 0, $session_id = 0, $ruc = 0, $page = 1, $perPage = 10, $isExport = false): array
    {
        $table_data = Database::get_main_table(self::TABLE_PROIKOS_DATA_LOG);
        // Calcular el offset para la paginación
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT
                ppd.id,
                ppd.registration_session_user,
                ppd.user_id,
                ppd.username,
                ppd.email,
                ppd.DNI,
                CONCAT(ppd.first_name, ' ', ppd.last_name) as student,
                ppd.session_category_id,
                ppd.session_category_name,
                ppd.session_id,
                ppd.course_id as c_id,
                ppd.course_code as code,
                ppd.session_name,
                ppd.company_ruc as ruc_company,
                ppd.company_name as name_company,
                ppd.area,
                ppd.entrance_exam,
                ppd.workshop,
                ppd.exit_exam,
                ppd.score,
                ppd.status,
                ppd.certificate_status
                FROM $table_data ppd ";

        $sql.= " WHERE ppd.status = 0 ";

        if(!empty($dni)){
            $sql.= " AND ppd.username = $dni ";
        }

        if($courseId != 0){
            $sql.= " AND ppd.course_id = $courseId ";
        }

        if($session_id != 0){
            $sql.= " AND ppd.session_id = $session_id ";
        }
        if (api_is_contractor_admin()) {
            $rucCompany = self::getUserRucCompany();
            $sql.= " AND ppd.company_ruc = $rucCompany ";
        } else {
            if($ruc != 0){
                $sql.= " AND ppd.company_ruc = $ruc ";
            }
        }

        if ($isExport) {
            $sql.= " ORDER BY ppd.id DESC; ";
        } else {
            $sql.= " ORDER BY ppd.id DESC LIMIT $offset, $perPage;";
        }

        $result = Database::query($sql);
        $users = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {
                $action = Display::url(
                    Display::return_icon(
                        'delete.png',
                        get_lang('Delete'),
                        [],
                        ICON_SIZE_SMALL
                    ),
                    api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/logs.php?action=delete&id=' . $row['id'],
                    [
                        'class' => 'btn btn-default',
                        'onclick' => 'javascript:if(!confirm(' . "'" .
                            addslashes(api_htmlentities(get_lang("ConfirmYourChoice")))
                            . "'" . ')) return false;',
                    ]
                );
                $row['actions'] = $action;
                $users[] = $row;
            }
        }

        $sqlTotal = "SELECT COUNT(DISTINCT ppd.id) as total_users FROM $table_data ppd ";
        $sqlTotal.= " WHERE ppd.status = 0 ";

        if (!empty($dni)) {
            $sqlTotal .= " AND ppd.username = '$dni' ";
        }

        if ($courseId != 0) {
            $sqlTotal .= " AND ppd.course_id = $courseId ";
        }

        if ($session_id != 0) {
            $sqlTotal .= " AND ppd.session_id = $session_id ";
        }

        if (api_is_contractor_admin()) {
            $rucCompany = self::getUserRucCompany();
            $sqlTotal .= " AND ppd.company_ruc = $rucCompany ";
        } else {
            if ($ruc != 0) {
                $sqlTotal .= " ppd ppu.company_ruc = $ruc ";
            }
        }

        // Ejecutar la consulta de total de registros
        $resultTotal = Database::query($sqlTotal);
        $rowTotal = Database::fetch_assoc($resultTotal);
        $totalUsers = $rowTotal['total_users'];
        $totalPages = ceil($totalUsers / $perPage);

        return [
            'users' => $users,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalUsers' => $totalUsers,
            ]
        ];

    }

    public function getDataReport($dni = null, $courseId = 0, $session_id = 0, $ruc = 0, $page = 1, $perPage = 10, $isExport = false): array
    {
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
        $tbl_proikos_user = Database::get_main_table(self::TABLE_PROIKOS_USERS);
        $table_plugin_easycertificate_send = Database::get_main_table(self::TABLE_PLUGIN_EASY_CERTIFICATE_SEND);

        // Calcular el offset para la paginación
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT DISTINCT
                u.id,
                u.username,
                u.registration_date,
                u.email,
                u.username as DNI,
                CONCAT(u.firstname, ' ', u.lastname) as student,
                u.firstname,
                u.lastname,
                sc.id as session_category_id,
                sc.name as session_category_name,
                srcu.session_id,
                srcu.c_id,
                c.code,
                s.name as session_name,
                s.display_start_date,
                s.display_end_date,
                 ppu.name_company,
                ppu.ruc_company,
                ppu.stakeholders,
                IF(ppu.area = -1, 'No registrado', ppu.area) as area,
                IF(ppu.metadata IS NOT NULL AND ppu.metadata != '', 'true', 'false') as metadata_exists,
                COALESCE((
                        SELECT
                            CASE
                                WHEN pecs.certificate_id IS NOT NULL
                                    AND CURRENT_DATE >= DATE(pecs.created_at)
                                    AND (pecs.reminder_15_sent_at IS NULL OR CURRENT_DATE <= pecs.reminder_15_sent_at)
                                THEN '1'
                                ELSE '2'
                            END
                        FROM {$table_plugin_easycertificate_send} pecs
                        WHERE pecs.user_id = u.id
                            AND pecs.session_id = s.id
                            AND pecs.course_id = srcu.c_id
                        ORDER BY pecs.id DESC
                        LIMIT 1
                    ), '3') AS certificate_status
            FROM
                $tbl_session_course_user srcu
            INNER JOIN
                $tbl_course c ON c.id = srcu.c_id
            INNER JOIN
                $tbl_session s ON s.id = srcu.session_id
            INNER JOIN
                $tbl_session_category sc ON sc.id = s.session_category_id
            INNER JOIN
                $tbl_user u ON u.user_id = srcu.user_id
            INNER JOIN
                $tbl_proikos_user ppu ON ppu.user_id = u.id
            WHERE
                srcu.status = 0 ";

        if(!empty($dni)){
            $sql.= " AND u.username = $dni ";
        }

        if($courseId != 0){
            $sql.= " AND srcu.c_id = $courseId ";
        }

        if($session_id != 0){
            $sql.= " AND srcu.session_id = $session_id ";
        }

        if (api_is_contractor_admin()) {
            $rucCompany = self::getUserRucCompany();
            $sql.= " AND ppu.ruc_company = $rucCompany ";
        } else {
            if($ruc != 0){
                $sql.= " AND ppu.ruc_company = $ruc ";
            }
        }
        if (!$isExport) {
            $sql.= " ORDER BY u.id DESC LIMIT $offset, $perPage;";
        } else {
            $sql.= " ORDER BY u.id DESC; ";
        }

        $result = Database::query($sql);
        $users = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {

                $cats = Category::load(
                    null,
                    null,
                    $row['code'],
                    null,
                    null,
                    $row['session_id'],
                    'ORDER By id'
                );
                $date = date("Y/m/d", strtotime($row['registration_date']));
                $row['registration_date_normal'] = $date;
                $registrationDate = api_format_date($row['registration_date'], DATE_FORMAT_LONG_NO_DAY);
                $row['registration_date'] = $registrationDate;
                if (!empty($cats)) {
                    // Solo si $cats tiene elementos, procedemos a obtener los enlaces
                    $userLinks = $cats[0]->get_links($row['id'], false, $row['code'], $row['session_id']);
                } else {
                    $userLinks = null; // Si no hay categorías, asignamos null
                }

                $userScore = $this->getResultExerciseStudent($row['id'], $row['c_id'], $row['session_id']);
                $row['exams'] = $userScore;

                // Validar si los valores existen o son vacíos, y si lo son, asignar 0
                $examen_de_entrada = isset($row['exams']['examen_de_entrada']) && $row['exams']['examen_de_entrada'] !== '' ? $row['exams']['examen_de_entrada'] : 0;
                $examen_de_salida = isset($row['exams']['examen_de_salida']) && $row['exams']['examen_de_salida'] !== '' ? $row['exams']['examen_de_salida'] : 0;
                $taller = isset($row['exams']['taller']) && $row['exams']['taller'] !== '' ? $row['exams']['taller'] : 0;

                // Definir los porcentajes para cada examen
                $ponderacion_entrada = 0.10;  // 10%
                $ponderacion_salida = 0.30;   // 30%
                $ponderacion_taller = 0.60;   // 60%

                // Calcular el puntaje total ponderado
                $puntaje_total = (($examen_de_entrada * $ponderacion_entrada) +
                        ($examen_de_salida * $ponderacion_salida) +
                        ($taller * $ponderacion_taller)) / 20 * 100;

                // Verificar el estado basado en los puntajes
                if ($puntaje_total == 0) {
                    $status = '<span class="label label-warning">' . $this->get_lang('Registered') . '</span>';
                    $status_id = 1;
                } else if ($examen_de_entrada == 0 || $examen_de_salida == 0 || $taller == 0) {
                    $status = '<span class="label label-danger">' . $this->get_lang('Failed') . '</span>';
                    $status_id = 0;
                } else if ($puntaje_total >= 70.4) {
                    $status = '<span class="label label-success">' . $this->get_lang('Approved') . '</span>';
                    $status_id = 2;
                } else {
                    $status = '<span class="label label-danger">' . $this->get_lang('Failed') . '</span>';
                    $status_id = 0;
                }
                $registerCodeSession = $this->registerCodeSessionRelUser($row['id'],$row['session_id']);
                $row['registration_session_user'] = $registerCodeSession;
                $row['status'] = $status;
                $row['status_id'] = $status_id;
                $row['score'] = $puntaje_total;
                $row['user_id'] = $row['id'];
                $row['links'] = empty($userLinks);
                $downloadCertUploadedLink = $this->generateDownloadLinkAttachCertificates($row['id'], $row['student'], $row['session_id']);
                $row['cert'] = $downloadCertUploadedLink;
                $iconCertificate = $this->get_icon('certificate');
                $iconCertificate_na = $this->get_icon('certificate_na');
                $row['download'] = Display::img($iconCertificate_na, $this->get_lang('CertificateNotGenerated'),['width' => '32px']);
                if($status_id == 2){
                    $urlCertificate = $this->getUserCertificateSession($row['id'], $row['session_id']);
                    $row['download'] = '<a href="'.$urlCertificate.'" target="_blank">'.
                        Display::img($iconCertificate, $this->get_lang('DownloadCertificate'),['width' => '32px']).
                        '</a>';
                }
                $checkDocument = $this->checkDocuments($row['id'],$row['session_id']);
                $certificateDates = $this->getCertificateDates($row['id'],$row['session_id']);
                $row['certificate_date'] = [
                    'created_at' => !empty($certificateDates['created_at'])
                        ? date('d-m-Y', strtotime($certificateDates['created_at']))
                        : '-',
                    'expiration_date' => !empty($certificateDates['expiration_date'])
                        ? date('d-m-Y', strtotime($certificateDates['expiration_date']))
                        : '-',
                ];
                $row['check_document'] = $checkDocument;
                $row['sustenance'] = $this->getSustenanceIconFA($row['id'],$row['c_id'],$row['session_id'], true);

                $timeSpent = api_time_to_hms(
                    Tracking::get_time_spent_on_the_course(
                        $row['id'],
                        $row['c_id'],
                        $row['session_id']
                    )
                );

                $row['time_course'] = $timeSpent;
                $users[] = $row;
            }
        }
        ///var_dump($users); exit;
        // Contar el total de registros sin LIMIT
        $sqlTotal = "
        SELECT COUNT(DISTINCT u.id) as total_users
        FROM $tbl_session_course_user srcu
        INNER JOIN $tbl_course c ON c.id = srcu.c_id
        INNER JOIN $tbl_session s ON s.id = srcu.session_id
        INNER JOIN $tbl_session_category sc ON sc.id = s.session_category_id
        INNER JOIN $tbl_user u ON u.user_id = srcu.user_id
        INNER JOIN $tbl_proikos_user ppu ON ppu.user_id = u.id
        WHERE srcu.status = 0 ";

        // Agregar las mismas condiciones de búsqueda
        if (!empty($dni)) {
            $sqlTotal .= " AND u.username = '$dni' ";
        }

        if ($courseId != 0) {
            $sqlTotal .= " AND srcu.c_id = $courseId ";
        }

        if ($session_id != 0) {
            $sqlTotal .= " AND srcu.session_id = $session_id ";
        }

        if (api_is_contractor_admin()) {
            $rucCompany = self::getUserRucCompany();
            $sqlTotal .= " AND ppu.ruc_company = $rucCompany ";
        } else {
            if ($ruc != 0) {
                $sqlTotal .= " AND ppu.ruc_company = $ruc ";
            }
        }

        // Ejecutar la consulta de total de registros
        $resultTotal = Database::query($sqlTotal);
        $rowTotal = Database::fetch_assoc($resultTotal);
        $totalUsers = $rowTotal['total_users'];
        $totalPages = ceil($totalUsers / $perPage);

        return [
            'users' => $users,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalUsers' => $totalUsers,
            ]
        ];

    }
    public function checkDocuments($userId, $sessionId)
    {
        $tableCheck = Database::get_main_table(self::TABLE_PROIKOS_CHECK_DOCS);

        $sql = "SELECT check_document FROM $tableCheck WHERE user_id = $userId AND session_id = $sessionId";
        $result = Database::query($sql);
        $documentCheck = 0;
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $documentCheck = intval($row['check_document']);
            }
        }
        if($documentCheck == 1){
            $checkImg = Display::img(api_get_path(WEB_IMG_PATH)."icons/22/check.png",$this->get_lang('VerifiedDocuments'));
        } else {
            $checkImg = Display::img(api_get_path(WEB_IMG_PATH)."icons/22/check_na.png",$this->get_lang('UnverifiedDocuments'));
        }

        return $checkImg;

    }

    /**
     * Opciones de códigos de sustentación
     */
    function getSustenanceOptions() {
        return array(
            0 => 'Sin observaciones',
            1 => 'Falta examen entrada',
            2 => 'Falta examen salida',
            3 => 'Falta taller',
            4 => 'No ingreso al curso',
            5 => 'No alcanzo nota minima',
            6 => 'Copio',
            7 => 'Conducta inapropiada',
            8 => 'No respondio al llamado',
            9 => 'Realizo otra actividad',
            10 => 'Suplantación',
            11 => 'Otros'
        );
    }

    /**
     * Convierte los códigos de incidencia a sus descripciones
     * @param string $codes Ej: "1,2,3"
     * @return array Array de descripciones
     */
    function getCodesDescriptions($codes) {
        if (empty($codes)) {
            return [];
        }

        $options = $this->getSustenanceOptions();
        $codesArray = explode(',', $codes);
        $descriptions = [];

        foreach ($codesArray as $code) {
            $code = trim($code);
            if (isset($options[$code])) {
                $descriptions[] = $options[$code];
            }
        }

        return $descriptions;
    }

    /**
     * Obtiene las incidencias por usuario y sesión
     */
    function getSustenanceByUserAndSession($user_id, $session_id) {
        try {
            $tableSustenance = Database::get_main_table('plugin_proikos_sustenance');
            $tableUsers = Database::get_main_table('user');

            $sql = "SELECT
                    ps.id,
                    ps.user_id,
                    ps.course_id,
                    ps.session_id,
                    ps.sustenance_codes,
                    ps.comment,
                    ps.created_at,
                    ps.updated_at,
                    u.firstname,
                    u.lastname
                FROM $tableSustenance ps
                LEFT JOIN $tableUsers u ON ps.user_id = u.id
                WHERE ps.user_id = " . intval($user_id) . "
                AND ps.session_id = " . intval($session_id);

            $result = Database::query($sql);

            // Si no hay incidencias registradas
            if (Database::num_rows($result) === 0) {
                return 'Sin incidencia';
            }

            // Obtener todas las incidencias
            $incidencias = [];
            while ($row = Database::fetch_assoc($result)) {
                $sessionName = api_get_session_name($row['session_id']);

                // Obtener las descripciones de los códigos
                $codesDescriptions = $this->getCodesDescriptions($row['sustenance_codes']);

                $incidencias[] = [
                    'id' => $row['id'],
                    'user_id' => $row['user_id'],
                    'user_name' => $row['firstname'] . ' ' . $row['lastname'],
                    'course_id' => $row['course_id'],
                    'session_id' => $row['session_id'],
                    'session_name' => $sessionName,
                    'sustenance_codes' => $row['sustenance_codes'],
                    'sustenance_descriptions' => $codesDescriptions,
                    'comment' => $row['comment'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                ];
            }

            // Generar mensaje con todas las descripciones
            $allDescriptions = [];
            foreach ($incidencias as $inc) {
                $allDescriptions = array_merge($allDescriptions, $inc['sustenance_descriptions']);
            }
            $allDescriptions = array_unique($allDescriptions);
            return implode(', ', $allDescriptions);

            /*return [
                'message' => $message,
                'has_incidence' => true,
                'data' => $incidencias,
                'count' => count($incidencias)
            ];*/

        } catch (Exception $e) {
            error_log('Error en getSustenanceByUserAndSession: ' . $e->getMessage());
            return false;
        }
    }


    public function getSessionExercises($sessionId, $courseId = null) {
        $exercises = [];

        // Obtener cursos de la sesión
        $courses = self::getSessionCourses($sessionId);

        foreach ($courses as $course) {
            // Si se especifica un curso específico, filtrar
            if ($courseId && $course['id'] != $courseId) {
                continue;
            }

            // Obtener ejercicios del curso
            $courseExercises = self::getCourseExercises($course['id'], $sessionId);

            foreach ($courseExercises as $exercise) {
                $exercises[] = [
                    'id' => $exercise['id'],
                    'title' => $exercise['title'],
                    'course_id' => $course['id'],
                    'course_title' => $course['title'],
                    'course_code' => $course['code'],
                    'description' => $exercise['description'],
                    'active' => $exercise['active']
                ];
            }
        }

        return $exercises;
    }

    function getUserCertificateSession($userId, $sessionId): string
    {
        $sessionCourses = SessionManager::get_course_list_by_session_id($sessionId);
        foreach ($sessionCourses as $course) {
            $category = Category::load(
                null,
                null,
                $course['code'],
                null,
                null,
                $sessionId
            );

            if (empty($category)) {
                continue;
            }

            if (!isset($category[0])) {
                continue;
            }

            /** @var Category $category */
            $category = $category[0];

            // Don't allow generate of certifications
            if (empty($category->getGenerateCertificates())) {
                continue;
            }

            $categoryId = $category->get_id();
            $certificateInfo = self::get_certificate_by_user_id(
                $categoryId,
                $userId
            );

            if (empty($certificateInfo)) {
                continue;
            }

            return api_get_path(WEB_PATH)."certificates/index.php?id={$certificateInfo['id']}&user_id={$userId}&action=export";
        }
        return '';
    }

    public static function get_certificate_by_user_id($cat_id, $user_id)
    {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $cat_id = (int) $cat_id;
        $user_id = (int) $user_id;

        $sql = "SELECT * FROM $table
                WHERE cat_id = $cat_id AND user_id = $user_id ";

        $result = Database::query($sql);
        return Database::fetch_array($result, 'ASSOC');

    }

    function getCourseExercises($courseId, $sessionId = null) {
        $courseId = (int)$courseId;
        $sessionId = $sessionId ? (int)$sessionId : null;

        // Obtener información del curso
        $courseInfo = api_get_course_info_by_id($courseId);
        if (!$courseInfo) {
            return [];
        }

        $courseTablePrefix = Database::get_course_table(TABLE_QUIZ_TEST);

        $sql = "SELECT
                q.iid,
                q.title,
                q.description,
                q.active,
                q.start_time,
                q.end_time
            FROM {$courseTablePrefix} q
            WHERE q.c_id = $courseId
            AND q.active IN (0, 1)";

        // Si hay sesión, filtrar por ejercicios disponibles en la sesión
        if ($sessionId) {
            $sql .= " AND (q.session_id = $sessionId OR q.session_id IS NULL OR q.session_id = 0)";
        }

        $sql .= " ORDER BY q.title";

        $result = Database::query($sql);
        $exercises = [];

        while ($row = Database::fetch_assoc($result)) {
            $exercises[] = [
                'id' => $row['iid'],
                'title' => $row['title'],
                'description' => $row['description'],
                'active' => $row['active'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time']
            ];
        }

        return $exercises;
    }
    function getSessionCourses($sessionId) {
        $sessionId = (int)$sessionId;

        $sql = "SELECT
                c.id,
                c.title,
                c.code,
                c.directory
            FROM " . Database::get_main_table(TABLE_MAIN_COURSE) . " c
            INNER JOIN " . Database::get_main_table(TABLE_MAIN_SESSION_COURSE) . " sc
                ON c.id = sc.c_id
            WHERE sc.session_id = $sessionId
            ORDER BY c.title";

        $result = Database::query($sql);
        $courses = [];

        while ($row = Database::fetch_assoc($result)) {
            $courses[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'code' => $row['code'],
                'directory' => $row['directory']
            ];
        }

        return $courses;
    }

    public function getSustenanceIconFA($user_id, $course_id, $session_id = null, $typeIconImg = false): string
    {
        $tableSustenance = Database::get_main_table('plugin_proikos_sustenance');

        $sql = "SELECT id FROM $tableSustenance
                WHERE user_id = $user_id
                AND course_id = $course_id ";

        if ($session_id !== null) {
            $sql .= " AND session_id = $session_id ";
        }

        $result = Database::query($sql);
        $hasRecord = Database::num_rows($result) > 0;
        $idSustenance = 0;
        while ($row = Database::fetch_assoc($result)) {
            $idSustenance = $row['id'];
        }

        if($typeIconImg){
            $iconRed = Display::url(Display::return_icon('bookmark_red.png',$this->get_lang('WithReportedIncidence')),'#',['data-sustenance-id' => $idSustenance, 'class'=>'viewModalSustenance']);
            $iconGreen = Display::url(Display::return_icon('bookmark_green.png',$this->get_lang('NoIncidents')),'#', ['data-sustenance-id' => 0, 'class'=>'viewModalSustenance']);
        } else {
            $iconRed = '<i class="fa fa-bookmark" style="color: #dc3545; "
                       title="Sustento registrado"></i> ';
            $iconGreen =  '<i class="fa fa-bookmark" style="color: #28a745; "
                   title="Sin sustento registrado"></i> ';
        }


        // Si existe registro - Ícono verde con checkmark
        if ($hasRecord) {
            return $iconRed;
        }

        // Si no existe registro - Ícono rojo con X
        return $iconGreen;
    }
}
