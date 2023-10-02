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
                'plugin_proikos_users'
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
        $nameCompany =  self::getCompanyName($values['name_company']);
        $namePosition = self::getPositionName($values['position_company']);
        $nameArea = self::getAreaName($values['area']);
        $nameManagement = self::getManagementName($values['department']);
        $nameHeadquarters = self::getHeadquartersName($values['headquarters']);
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
            'department' => $values['department'],
            'headquarters' => $values['headquarters'],
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
    function formGenerateElementsGroup($form, $values, $elementName): array
    {
        $group = [];
        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $element = &$form->createElement('radio', $elementName, '', $value['display_img'], $value['value'], ['label-class'=>'label_'.strtolower($value['value'])]);
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
        $sql = "SELECT * FROM $table pa ";
        $result = Database::query($sql);
        $list = [];
        $list['-1'] = 'Selecciona una opción';
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $list[$row['id']] = $row['name_area'];
            }
        }
        $list['999'] = 'Otros';
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

    public function getStudentsSessionForFora($idSession): array
    {
        $users = SessionManager::get_users_by_session($idSession);
        $list = [];
        $count = 1;
        foreach ($users as $row) {
            $list[] = [
                'number' => $count,
                'user_id' => $row['user_id'],
                'firstname' => $row['firstname'],
                'lastname' => $row['lastname'],
                'email' => $row['username']
            ];
            $count++;
        }
        return $list;
    }

    public function getCoursesSessionID($idSession, $idUser): array
    {
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT src.c_id, src.session_id, c.title, c.code FROM $tbl_session_course src INNER JOIN $tbl_course c ON src.c_id = c.id WHERE src.session_id = $idSession;";
        $result = Database::query($sql);
        $courses = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $evaluations =  self::getScoreEvaluationStudent($row['code'],$idUser);
                $courses[] = [
                    'c_id' => $row['c_id'],
                    'session_id' => $row['session_id'],
                    'title' => $row['title'],
                    'code' => $row['code'],
                    'evaluations' => $evaluations
                ];
            }
        }
        return $courses;
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
                $categoryName = $sessionCategory->getName();
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

    public function getStudentForSession($session = [])
    {
        $userList = SessionManager::get_users_by_session($session['id']);
        $users = [];
        if (!empty($userList)) {
            foreach ($userList as $user) {
                $userId = $user['user_id'];
                $infoProikos = self::getInfoUserProikos($userId);
                $userInfo = api_get_user_info($userId);
                $courses = self::getCoursesSessionID($session['id'], $userId);
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
    public function exportReportXLS($students, $logo){
        $date = date('d-m-Y H:i:s', time());
        $date_format = api_convert_and_format_date($date, "%d-%m-%Y %H:%M");
        $nameFile = 'participants_report_';
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
            $worksheet->setCellValue($col . $row, $headers[$i]);

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
                foreach ($course['evaluations'] as $evaluation) {
                    $worksheet->setCellValueByColumnAndRow(24, $line, $evaluation['name']);
                    $worksheet->setCellValueByColumnAndRow(25, $line, $evaluation['score']);
                }
            }


            //$worksheet->setCellValueByColumnAndRow(22, $line, $student['courses'][0]['code']);
            //$worksheet->setCellValueByColumnAndRow(23, $line, $student['courses'][0]['title']);

            $worksheet->getStyle("A$line:W$line")->applyFromArray($borderStyle);
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

    public function getScoreCertificate($idUser, $codeCourse, $idSession): array
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

    public function getIdCourseSession($idSession){
        $table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $sql = "SELECT src.c_id FROM $table src WHERE src.session_id=$idSession;";
        $result = Database::query($sql);
        $courses = null;
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $courses = $row['c_id'];
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
    public function getSessionDatesNext(): array
    {
        $table = Database::get_main_table(TABLE_MAIN_SESSION);
        $sql = "SELECT name, display_start_date FROM $table WHERE display_start_date >= CURDATE();";
        $result = Database::query($sql);
        $list = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $list[]= $row['name'];
            }
        }
        return $list;
    }
}
