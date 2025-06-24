<?php

class PluginProikosContratingCompaniesQuotaCab
{
    private $table;
    private $contratingCompaniesQuotaDet;
    private $contratingCompaniesQuotaSession;
    private $contratingCompaniesQuotaSessionDet;
    private $sessionModes;

    public function __construct($table, $contratingCompaniesQuotaDet, $contratingCompaniesQuotaSession,
                                $contratingCompaniesQuotaSessionDet, $sessionModes)
    {
        $this->table = $table;
        $this->contratingCompaniesQuotaDet = $contratingCompaniesQuotaDet;
        $this->contratingCompaniesQuotaSession = $contratingCompaniesQuotaSession;
        $this->contratingCompaniesQuotaSessionDet = $contratingCompaniesQuotaSessionDet;
        $this->sessionModes = $sessionModes;
    }

    public function save($values)
    {
        if (!is_array($values)) {
            return false;
        }

        $table = \Database::get_main_table($this->table);
        $id = \Database::insert($table, $values);

        if ($id > 0) {
            return $id;
        }

        return false;
    }

    public function update($values)
    {
        if (!is_array($values)) {
            return false;
        }

        $table = Database::get_main_table($this->table);
        $params = [];

        if (isset($values['validity_date'])) {
            $params['validity_date'] = $values['validity_date'];
        }

        if (!empty($params)) {
            Database::update(
                $table,
                $params,
                [
                    'id = ?' => [
                        $values['id'],
                    ],
                ]
            );
        }

        return true;
    }

    public function getData($id)
    {
        if (empty($id)) {
            return false;
        }

        $table = Database::get_main_table($this->table);
        $sql = "SELECT
            a.id,
            a.contrating_company_id,
            a.validity_date,
            (SELECT SUM(user_quota) FROM " . $this->contratingCompaniesQuotaDet . " WHERE cab_id = a.id) AS total_user_quota,
            (SELECT CONCAT('S/ ', FORMAT(SUM(price_unit * user_quota), 2)) FROM " . $this->contratingCompaniesQuotaDet . " WHERE cab_id = a.id) AS total_price_unit_quota,
            DATE_FORMAT(a.validity_date, '%Y-%m-%d') AS formatted_input_validity_date,
            DATE_FORMAT(a.validity_date, '%d-%m-%Y') AS formatted_validity_date,
            DATE_FORMAT(a.created_at, '%d-%m-%Y %H:%i') AS formatted_created_at,
            CONCAT(b.lastname, ' ', b.firstname) AS user_name
            FROM $table a
            LEFT JOIN user b on a.created_user_id = b.user_id
            WHERE a.id = $id";
        $result = Database::query($sql);

        $data = [];
        $month = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre'
        ];

        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $fechaFormateada = '';

                if (!empty($row['formatted_input_validity_date'])) {
                    $fecha = strtotime($row['formatted_input_validity_date']);
                    $dia = date('j', $fecha);
                    $mes = $month[intval(date('n', $fecha))];
                    $anio = date('Y', $fecha);
                    $fechaFormateada = "$dia de $mes de $anio";
                }

                $row['vigency_date_es'] = $fechaFormateada;
                $data[] = $row;
            }
        }

        return $data[0] ?? [];
    }

    public function getDataByCompanyId($companyId, $quota_dispon = false)
    {
        if (empty($companyId)) {
            return false;
        }

        $table = Database::get_main_table($this->table);
        $sql = "SELECT
            a.id,
            a.contrating_company_id,
            (SELECT SUM(user_quota) FROM " . $this->contratingCompaniesQuotaDet . " WHERE cab_id = a.id) AS total_user_quota,
            (
                (SELECT SUM(user_quota) FROM " . $this->contratingCompaniesQuotaDet . " WHERE cab_id = a.id) -
                IFNULL((SELECT SUM(qs.user_quota) FROM plugin_proikos_contrating_companies_quota_session qs
                INNER JOIN " . $this->contratingCompaniesQuotaDet . " sqd ON sqd.id = qs.det_id
                WHERE sqd.cab_id = a.id), 0)
            ) AS quota_dispon,
            (SELECT CONCAT('S/ ', FORMAT(SUM(price_unit * user_quota), 2)) FROM " . $this->contratingCompaniesQuotaDet . " WHERE cab_id = a.id) AS total_price_unit_quota,
            (
                 SELECT GROUP_CONCAT(DISTINCT
                 CASE session_mode
                   WHEN 1 THEN 'Asincrónico'
                   WHEN 2 THEN 'Sincrónico'
                 END
                 ORDER BY session_mode SEPARATOR ', ') AS modalidades
                 FROM plugin_proikos_contrating_companies_quota_det where cab_id = a.id
            ) AS modalidades,
            (
                 SELECT GROUP_CONCAT(DISTINCT
                 sc.name
                 ORDER BY sqd.id SEPARATOR ', ') AS categorias_session
                 FROM plugin_proikos_contrating_companies_quota_det sqd
                 INNER JOIN session_category sc ON sc.id = sqd.session_category_id
                 where sqd.cab_id = a.id
            ) AS categorias_session,
            DATE_FORMAT(a.validity_date, '%d-%m-%Y') AS formatted_validity_date,
            DATE_FORMAT(a.created_at, '%d-%m-%Y %H:%i') AS formatted_created_at,
            CONCAT(b.lastname, ' ', b.firstname) AS user_name
            FROM $table a
            LEFT JOIN user b on a.created_user_id = b.user_id ";
        if($quota_dispon) {
            $sql .= " WHERE a.id = $companyId ";
        } else {
            $sql .= " WHERE a.contrating_company_id = $companyId ";
        }
        $sql .= " ORDER BY a.id DESC ";
        $result = Database::query($sql);
        $items = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                if(!$quota_dispon) {
                    // Assign quota to session
                    $action = Display::url(
                        Display::return_icon(
                            'session.png',
                            'Asignar cupos a sesiones',
                            [],
                            ICON_SIZE_SMALL),
                        api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies_assign_quota_to_session.php?company_id=' . $companyId . '&action=assign_quota_to_session&quota_cab_id=' . $row['id']
                    );

                    // edit action
                    $action .= Display::url(
                        Display::return_icon(
                            'visible.png',
                            'Ver detalle',
                            [],
                            ICON_SIZE_SMALL),
                        api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies_quota_det.php?company_id=' . $companyId . '&action=edit&quota_cab_id=' . $row['id']
                    );

                    if (api_is_platform_admin() || api_is_drh()) {
                        // delete action
                        $action .= Display::url(
                            Display::return_icon(
                                'delete.png',
                                get_lang('Delete'),
                                [],
                                ICON_SIZE_SMALL
                            ),
                            api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies_quota_cab.php?company_id=' . $companyId . '&action=delete&quota_cab_id=' . $row['id'],
                            [
                                'onclick' => 'javascript:if(!confirm(' . "'" .
                                    addslashes(api_htmlentities(get_lang("ConfirmYourChoice")))
                                    . "'" . ')) return false;',
                            ]
                        );
                    }

                    $row['actions'] = $action;
                    $items[] = $row;
                } else {
                    $items['quota_dispon'] = $row['quota_dispon'];
                }
            }
        }

        return $items;
    }

    public function delete($id)
    {
        $result = Database::delete(
            $this->table,
            ['id = ?' => $id]
        );

        if ($result) {
            // select $this->contratingCompaniesQuotaDet where cab_id = $id
            $sql = "SELECT id FROM " . $this->contratingCompaniesQuotaDet . " WHERE cab_id = $id";
            $result = Database::query($sql);
            $detIds = [];
            if (Database::num_rows($result) > 0) {
                while ($row = Database::fetch_array($result)) {
                    $detIds[] = $row['id'];
                }
            }

            if (!empty($detIds)) {
                $detIds = implode(',', $detIds);

                // select $this->contratingCompaniesQuotaSession where det_id in $ids
                $sql = "SELECT id FROM " . $this->contratingCompaniesQuotaSession . " WHERE det_id IN ($detIds)";
                $result = Database::query($sql);
                $sessionIds = [];
                if (Database::num_rows($result) > 0) {
                    while ($row = Database::fetch_array($result)) {
                        $sessionIds[] = $row['id'];
                    }
                }

                // delete from $this->contratingCompaniesQuotaSessionDet where quota_session_id in $sessionIds
                if (!empty($sessionIds)) {
                    $sessionIds = implode(',', $sessionIds);
                    $sql = "DELETE FROM " . $this->contratingCompaniesQuotaSessionDet . " WHERE quota_session_id IN ($sessionIds)";
                    Database::query($sql);
                }

                // delete from $this->contratingCompaniesQuotaSession where det_id in $ids
                $sql = "DELETE FROM " . $this->contratingCompaniesQuotaSession . " WHERE det_id IN ($detIds)";
                Database::query($sql);
            }

            // Delete det
            Database::delete(
                $this->contratingCompaniesQuotaDet,
                ['cab_id = ?' => $id]
            );

            return true;
        }

        return false;
    }

    public function getQuotaXSessionCompany($cab_id, $session_id)
    {
        $table = Database::get_main_table($this->contratingCompaniesQuotaDet);
        $sql = "SELECT pp.* FROM $table pp WHERE pp.cab_id = $cab_id AND pp.session_category_id = $session_id";
        $res = Database::query($sql);
        $name = null;
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $name = $row['user_quota'];
        }
        return $name;
    }
    public function getDetails($cabId)
    {
        if (empty($cabId)) {
            return false;
        }

        $quota_dispon = $this->getDataByCompanyId($cabId, true);
        $table = Database::get_main_table($this->contratingCompaniesQuotaDet);
        $sessionCategoryTable = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
        $sql = "SELECT
            a.id,
            a.session_category_id,
            a.user_quota,
            a.price_unit,
            a.session_mode,
            c.name AS category_name,
            DATE_FORMAT(a.created_at, '%d-%m-%Y %H:%i') AS formatted_created_at,
            CONCAT(b.lastname, ' ', b.firstname) AS user_name
            FROM $table a
            LEFT JOIN user b on a.created_user_id = b.user_id
            INNER JOIN $sessionCategoryTable c ON a.session_category_id = c.id
            WHERE a.cab_id = $cabId";
        $result = Database::query($sql);
        $items = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $quota_dis = $this->getQuotaXSessionCompany($cabId, $row['session_category_id']);
                $items[] = [
                    'id' => $row['id'],
                    'session_category_id' => $row['session_category_id'],
                    'category_name' => $row['category_name'],
                    'quota' => $row['user_quota'],
                    'qouta_dis' => $quota_dis,
                    'price_unit' => $row['price_unit'],
                    'session_mode' => $row['session_mode'],
                    'session_mode_name' => $this->sessionModes[$row['session_mode']] ?? '',
                ];
            }
        }

        return $items;
    }
}
