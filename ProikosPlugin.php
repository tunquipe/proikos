<?php

class ProikosPlugin extends Plugin
{
    const TABLE_PROIKOS_USERS = 'plugin_proikos_users';
    const TABLE_PROIKOS_ENTITY = 'plugin_proikos_entity';

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
        $sql = "CREATE TABLE IF NOT EXISTS ".self::TABLE_PROIKOS_USERS." (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            user_id VARCHAR(250) NULL,
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
            employment_category VARCHAR(250) NULL,
            experience_time INT NULL,
            area VARCHAR(250) NULL,
            department VARCHAR(250) NULL,
            headquarters VARCHAR(250) NULL
        )";
        Database::query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS ".self::TABLE_PROIKOS_ENTITY." (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            name_entity VARCHAR(250) NULL,
            picture VARCHAR(250) NULL,
            code_reference VARCHAR(250) NULL,
            status INT NULL
        )";
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
        $sql = "SELECT * FROM $table up WHERE $userId";
        $result = Database::query($sql);
        $list = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $list[] = [
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
                    'employment_category' => $row['employment_category'],
                    'experience_time' => $row['experience_time'],
                    'area' => $row['area'],
                    'department' => $row['department'],
                    'headquarters' => $row['headquarters']
                ];
            }
        }
        return $list;
    }


    public function getListEntity(): array
    {
        $table = Database::get_main_table(self::TABLE_PROIKOS_ENTITY);
        $sql = "SELECT * FROM $table pe";
        $result = Database::query($sql);
        $list = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $list[] = [
                    'id' => $row['id'],
                    'name_entity' => $row['name_entity'],
                    'picture' => $row['picture'],
                    'code_reference' => $row['code_reference'],
                    'status' => $row['status']
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
            'id' => $values['id'],
            'name_entity' => $values['name_entity'],
            'code_reference' => $values['code_reference'],
            'status' => $values['status']
        ];
        $id = Database::insert($table, $params);
        if ($id > 0) {
            return $id;
        }
    }

    public function getEntity($idEntity){
        if (empty($entity)) {
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
                    'picture' => $row['picture'],
                    'code_reference' => $row['code_reference'],
                    'status' => $row['status']
                ];
            }
            return $item;
        } else {
            return false;
        }
    }

    public function saveImage($idEntity, $fileData){
        $entity = self::getEntity($idEntity);
        if (empty($entity)) {
            return false;
        }

        if (!empty($fileData['error'])) {
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
            'name_company' => $values['name_company'],
            'contact_manager' => $values['contact_manager'],
            'position_company' => $values['position_company'],
            'stakeholders' => $values['stakeholders'],
            'employment_category' => $values['employment_category'],
            'experience_time' => $values['experience_time'],
            'area' => $values['area'],
            'department' => $values['department'],
            'headquarters' => $values['headquarters']
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
            'employment_category' => $values['employment_category'],
            'experience_time' => $values['experience_time'],
            'area' => $values['area'],
            'department' => $values['department'],
            'headquarters' => $values['headquarters']
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
}
