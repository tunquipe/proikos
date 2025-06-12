<?php

class PluginProikosContratingCompanies
{
    private $table;
    private $contratingCompaniesQuotaCab;
    private $contratingCompaniesQuotaDet;

    public function __construct($table, $contratingCompaniesQuotaCab, $contratingCompaniesQuotaDet)
    {
        $this->table = $table;
        $this->contratingCompaniesQuotaCab = $contratingCompaniesQuotaCab;
        $this->contratingCompaniesQuotaDet = $contratingCompaniesQuotaDet;
    }

    public function save($values)
    {
        if (!is_array($values)) {
            return false;
        }

        $table = Database::get_main_table($this->table);
        $params = [
            'name' => $values['name'],
            'ruc' => $values['ruc'],
            'admin_name' => $values['admin_name'] ?? '',
            'status' => $values['status'] ?? 1,
            'admin_email' => $values['admin_email'] ?? '',
            'company_code' => $values['company_code'] ?? '',
        ];
        $id = Database::insert($table, $params);

        if ($id > 0) {
            return $id;
        }

        return false;
    }

    public function getData($id = null, $asSelect = false)
    {
        $table = Database::get_main_table($this->table);
        $where = "";

        if ($id !== null) {
            $where = "WHERE a.id = $id";
            if (api_is_contractor_admin()) {
                $rucCompany = ProikosPlugin::getUserRucCompany();
                $where .= " AND a.ruc = '$rucCompany'";
            }
        } else {
            if (api_is_contractor_admin()) {
                $rucCompany = ProikosPlugin::getUserRucCompany();
                $where = "WHERE a.ruc = '$rucCompany'";
            }
        }

        $sql = "SELECT
                a.*,
                SUM(c.user_quota) AS total_user_quota,
                CONCAT('S/ ', FORMAT(SUM(c.price_unit * c.user_quota), 2)) AS total_price_unit_quota
            FROM
                $table a
            LEFT JOIN
                " . $this->contratingCompaniesQuotaCab ." b ON b.contrating_company_id = a.id
            LEFT JOIN
                " . $this->contratingCompaniesQuotaDet ." c ON c.cab_id = b.id
            " . ($where) . "
            GROUP BY
                a.id;";

        $result = Database::query($sql);
        $list = [];

        if ($asSelect) {
            $list[0] = 'Seleccione una opción';
        }

        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {

                if ($asSelect) {
                    $list[$row['id']] = $row['ruc'] . ' - ' . $row['name'];
                    continue;
                }

                $action = Display::url(
                    Display::return_icon(
                        'tickets.png',
                        'Gestionar Cupos',
                        [],
                        ICON_SIZE_SMALL),
                    api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies_quota_cab.php?company_id=' . $row['id']
                );

                if (api_is_platform_admin() || api_is_drh()) {
                    $action .= Display::url(
                        Display::return_icon(
                            'edit.png',
                            null,
                            [],
                            ICON_SIZE_SMALL),
                        api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies.php?action=edit&id=' . $row['id']
                    );
                    $action .= Display::url(
                        Display::return_icon(
                            'delete.png',
                            get_lang('Delete'),
                            [],
                            ICON_SIZE_SMALL
                        ),
                        api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies.php?action=delete&id=' . $row['id'],
                        [
                            'onclick' => 'javascript:if(!confirm(' . "'" .
                                addslashes(api_htmlentities(get_lang("ConfirmYourChoice")))
                                . "'" . ')) return false;',
                        ]
                    );
                }

                $list[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'ruc' => $row['ruc'],
                    'admin_name' => $row['admin_name'],
                    'admin_email' => $row['admin_email'],
                    'company_code' => $row['company_code'],
                    'total_user_quota' => $row['total_user_quota'],
                    'total_price_unit_quota' => $row['total_price_unit_quota'],
                    'status' => $row['status'],
                    'actions' => $action
                ];
            }
        }

        if ($id !== null) {
            return $list[0];
        }

        return $list;
    }

    public function getDataByRUC($ruc)
    {
        if (empty($ruc)) {
            return '';
        }

        $table = Database::get_main_table($this->table);

        $sql = "SELECT name FROM $table WHERE ruc = '$ruc' order by id desc LIMIT 1;";
        $result = Database::query($sql);
        $nameCompany = '';
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $nameCompany = $row['name'];
            }
        }

        return $nameCompany;
    }

    public function update($values)
    {
        if (!is_array($values)) {
            return false;
        }

        $table = Database::get_main_table($this->table);
        $params = [];

        if (isset($values['name'])) {
            $params['name'] = $values['name'];
        }

        if (isset($values['ruc'])) {
            $params['ruc'] = $values['ruc'];
        }

        if (isset($values['admin_name'])) {
            $params['admin_name'] = $values['admin_name'];
        }

        if (isset($values['admin_email'])) {
            $params['admin_email'] = $values['admin_email'];
        }

        if (isset($values['status'])) {
            $params['status'] = $values['status'];
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

    public function delete($id)
    {
        $result = Database::delete(
            $this->table,
            ['id = ?' => $id]
        );

        if ($result) {
            return true;
        }

        return false;
    }
}
