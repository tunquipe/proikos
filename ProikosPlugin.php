<?php
use ExtraField as ExtraFieldModel;
use Chamilo\CoreBundle\Entity\Repository\SequenceRepository;
use Chamilo\CoreBundle\Entity\Repository\SequenceResourceRepository;
use Chamilo\CoreBundle\Entity\Sequence;
use Chamilo\CoreBundle\Entity\SequenceResource;
use ChamiloSession as Session;

class ProikosPlugin extends Plugin
{
    const TABLE_PROIKOS_USERS = 'plugin_proikos_users';
    const TABLE_PROIKOS_ENTITY = 'plugin_proikos_entity';
    const TABLE_PROIKOS_SECTOR = 'plugin_proikos_sector';
    const TABLE_PROIKOS_POSITION = 'plugin_proikos_position';

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
            sector VARCHAR(250) NULL,
            position_company VARCHAR(250) NULL,
            stakeholders VARCHAR(250) NULL,
            employment_category VARCHAR(250) NULL,
            experience_time INT NULL,
            area VARCHAR(250) NULL,
            department VARCHAR(250) NULL,
            headquarters VARCHAR(250) NULL,
            code_reference VARCHAR(250) NULL
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
            id_sector INT NULL,
            name_position VARCHAR(250) NULL,
            status INT NULL
        )";

        Database::query($sql);

        //add sectors

        $sql = "INSERT INTO ".self::TABLE_PROIKOS_SECTOR."  (`id`, `name_sector`, `status`)
        VALUES ('1', 'Hidrocarburos', '1'),
        ('2', 'Minería', '1'),
        ('3','Construcción', '1'),
        ('4', 'Industria', '1'),
        ('5', 'Energía', '1'),
        ('6', 'Servicios', '1'),
        ('7', 'Banca', '1');";

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
                    'sector' => $row['sector'],
                    'position_company' => $row['position_company'],
                    'stakeholders' => $row['stakeholders'],
                    'employment_category' => $row['employment_category'],
                    'experience_time' => $row['experience_time'],
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
            'name_company' => $values['name_company'],
            'contact_manager' => $values['contact_manager'],
            'sector' => $values['sector'],
            'position_company' => $values['position_company'],
            'stakeholders' => $values['stakeholders'],
            'employment_category' => $values['employment_category'],
            'experience_time' => $values['experience_time'],
            'area' => $values['area'],
            'department' => $values['department'],
            'headquarters' => $values['headquarters'],
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
            'sector' => $values['sector'],
            'position_company' => $values['position_company'],
            'stakeholders' => $values['stakeholders'],
            'employment_category' => $values['employment_category'],
            'experience_time' => $values['experience_time'],
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

    public function getPositions($idSector): array
    {
        $table = Database::get_main_table(self::TABLE_PROIKOS_POSITION);
        $sql = "SELECT * FROM $table pp WHERE pp.id_sector = $idSector";
        $result = Database::query($sql);
        $list = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $list[$row['id']] = $row['name_position'];
            }
        }
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
     * @param string $date
     * @param array  $limit
     * @param bool   $returnQueryBuilder
     * @param bool   $getCount
     *
     * @return array|\Doctrine\ORM\Query The session list
     */
    public function browseSessions($date = null, $limit = [], $returnQueryBuilder = false, $getCount = false, $code_reference = null)
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
                            $qb->expr()->eq('url.accessUrlId ', $urlId))
                        ->getDQL()
                )
            )
            ->andWhere($qb->expr()->gt('s.nbrCourses', 0));

        if($code_reference != 'ALL'){
            $qb->andWhere($qb->expr()->eq('s.codeReference', ':codeReference'))
                ->setParameter('codeReference', $code_reference);
        }

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

    public function getRequirementsSessionUser($sequences, $type, $userId, $sessionId): bool
    {
        $em = Database::getManager();
        /** @var SequenceRepository $sequenceRepository */
        $sequenceRepository = $em->getRepository(Sequence::class);
        /** @var SequenceResourceRepository $sequenceResourceRepository */
        $sequenceResourceRepository = $em->getRepository(SequenceResource::class);
        $sequences = $sequenceResourceRepository->getRequirements($sessionId, $type);
        $sequenceList = $sequenceResourceRepository->checkRequirementsForUser($sequences, $type, $userId, $sessionId);
        $allowSubscription = $sequenceResourceRepository->checkSequenceAreCompleted($sequenceList);
        return $allowSubscription;
    }
}
