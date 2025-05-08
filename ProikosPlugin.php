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
    const EVENT_ADD_QUOTA = 'add_quota';
    const EVENT_USER_SUBSCRIPTION_TO_COURSE = 'user_subscription_to_course';

    protected function __construct()
    {
        parent::__construct(
            '1.0',
            'Alex Aragon <alex.aragon@tunqui.pe>',
            [
                'tool_enable' => 'boolean'
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
            status VARCHAR(1) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );";
        Database::query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS " . self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_CAB . " (
          id INT PRIMARY KEY AUTO_INCREMENT,
          contrating_company_id INT,
          created_user_id INT NOT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );";
        Database::query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS " . self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_DET . " (
          id INT PRIMARY KEY AUTO_INCREMENT,
          cab_id INT,
          type_course_id INT NOT NULL,
          course_id INT NOT NULL,
          user_quota INT NOT NULL,
          created_user_id INT NOT NULL,
          updated_user_id INT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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

    public function getInfoUserProikos($userId)
    {
        if (empty($userId)) {
            return false;
        }
        $table = Database::get_main_table(self::TABLE_PROIKOS_USERS);
        $sql = "SELECT * FROM $table up WHERE user_id = $userId";
        $result = Database::query($sql);
        $list = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $list = [
                    'user_id' => $row['user_id'],
                    'lastname' => $row['lastname'],
                    'firstname' => $row['firstname'],
                    'phone' => $row['phone'],
                    'type_document' => $row['type_document'],
                    'number_document' => $row['number_document'],
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
                    'code_reference' => $row['code_reference']
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

    public function saveInfoUserProikos($values){
        if (!is_array($values)) {
            return false;
        }
        $table = Database::get_main_table(self::TABLE_PROIKOS_USERS);
        $rucCompany =  $values['ruc_company'];
        $nameCompany =  $values['name_company'];
        $namePosition = self::getPositionName($values['position_company']);
        $nameArea = self::getAreaName($values['area']);
        //$nameManagement = self::getManagementName($values['department']);
        //$nameHeadquarters = self::getHeadquartersName($values['headquarters']);
        $nameManagement = '-';
        $nameHeadquarters = '-';
        $params = [
            'id' => $values['user_id'],
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
            'code_reference' => $values['code_reference']
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

    public function getPositions($idStakeholders): array
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
                $list[$row['id']] = $row['name_position'];
            }
        }
        $list['999'] = 'Otros';
        return $list;
    }

    public function getPetroArea(): array
    {
        $table = Database::get_main_table(self::TABLE_PROIKOS_AREA);
        $sql = "SELECT * FROM $table pa WHERE pa.status = 1";
        $result = Database::query($sql);
        $list = [];
        $list['-1'] = 'Selecciona una opción';
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $list[$row['id']] = $row['name_area'];
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
    public function browseSessions($date = null, $limit = [], $returnQueryBuilder = false, $getCount = false, $code_reference = null, int $categoryID)
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
                                $name = strtolower($item->get_name());
                                $defaultData[$name] = $score;
                            }
                        } else{
                            $name = strtolower($item->get_name());
                            $defaultData[$name] = $score;
                        }
                        break;
                    case 'ExerciseLink':
                        /** @var ExerciseLink $item */
                        $score = self::getScoreExercise($item->get_ref_id(),$session_id,$user_id);
                        if($showEmpty){
                            if($score===0){
                                $defaultData=[];
                            }else{
                                $name = strtolower($item->get_name());
                                $defaultData[$name] = $score;
                            }
                        } else {
                            $name = strtolower($item->get_name());
                            $defaultData[$name] = $score;
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
            while ($row = Database::fetch_array($result)) {
                $score = doubleval($row['exe_result']);
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
                    'weight' => (double)$row['weight'],
                    'session_id' => $row['session_id'],
                    'category_id' => $row['category_id'],
                    'score' => (double)$row['score'],
                    'user_id' => $row['user_id'],
                ];
            }
        }
        if($stop){
            return $list;
        } else {
            $sql = "SELECT * FROM $tbl_gradebook_certificate gc WHERE gc.user_id = ".$list['user_id']." AND gc.cat_id = ".$list['category_id'].";";
            $result = Database::query($sql);
            $total = boolval(Database::num_rows($result));
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
        $sql = "SELECT * FROM plugin_proikos_users";
        $result = Database::query($sql);
        $list = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $action = Display::url(
                    Display::return_icon(
                        'visible.png',
                        null,
                        [],
                        ICON_SIZE_SMALL),
                    api_get_path(WEB_PLUGIN_PATH)
                );
                $action .= Display::url(
                    Display::return_icon(
                        'lp.png',
                        '',
                        [],
                        ICON_SIZE_SMALL
                    ),
                    api_get_path(WEB_PLUGIN_PATH)
                );

                $list[] = [
                    'id' => $row['user_id'],
                    'name' => $row['firstname'] . ' ' . $row['lastname'],
                    'email' => $row['email'],
                    'phone' => $row['phone'],
                    'ruc' => $row['ruc_company'],
                    'company' => $row['name_company'],
                    'actions' => $action
                ];
            }
        }

        return $list;
    }

    public function contratingCompanyQuotaManager() {
        return (object) [
            'subscribe_user' => function($userId, $userAdmin, $userInfo, $courseInfo) {
                if (empty($userId)) {
                    return false;
                }
                // get ruc_company from self::TABLE_PROIKOS_USERS then search in self::TABLE_PROIKOS_CONTRATING_COMPANIES
                $table = Database::get_main_table(self::TABLE_PROIKOS_USERS);
                $sql = "SELECT ruc_company FROM $table WHERE user_id = $userId";
                $result = Database::query($sql);
                $rucCompany = '';
                if (Database::num_rows($result) > 0) {
                    while ($row = Database::fetch_array($result)) {
                        $rucCompany = $row['ruc_company'];
                    }
                }

                if (empty($rucCompany)) {
                    return false;
                }

                $table = Database::get_main_table(self::TABLE_PROIKOS_CONTRATING_COMPANIES);
                $sql = "SELECT id FROM $table WHERE ruc = '$rucCompany'";
                $result = Database::query($sql);
                $id = '';
                if (Database::num_rows($result) > 0) {
                    while ($row = Database::fetch_array($result)) {
                        $id = $row['id'];
                    }
                }

                if (empty($id)) {
                    return false;
                }

                $company = $this->contratingCompaniesModel()->getData($id);
                if (empty($company)) {
                    return false;
                }

                if ($company['total_user_quota'] <= 0) {
                    return -1;
                }

//                $this->addContratingCompanyDetail([
//                    'cab_id' => $id,
//                    'user_id' => $userAdmin,
//                    'user_quota' => -1,
//                    'event' => self::EVENT_USER_SUBSCRIPTION_TO_COURSE,
//                    'details' => 'El usuario ' . $userInfo['complete_name_with_username'] . ' ha sido registrado al curso ' . $courseInfo['name']
//                ]);

                return true;
            }
        ];
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
            self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_DET
        ));
    }

    public function contratingCompaniesQuotaDetModel()
    {
        require_once __DIR__ . '/src/model/PluginProikosContratingCompaniesQuotaDet.php';

        return (new PluginProikosContratingCompaniesQuotaDet(
            self::TABLE_PROIKOS_CONTRATING_COMPANIES_QUOTA_DET
        ));
    }
}
