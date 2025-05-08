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
        $sql = "SELECT
                a.*,
                SUM(c.user_quota) AS total_user_quota
            FROM
                $table a
            LEFT JOIN
                " . $this->contratingCompaniesQuotaCab ." b ON b.contrating_company_id = a.id
            LEFT JOIN
                " . $this->contratingCompaniesQuotaDet ." c ON c.cab_id = b.id
            " . ($id !== null ? "WHERE a.id = $id" : "") . "
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
                $action .= Display::url(
                    Display::return_icon(
                        'visible.png',
                        null,
                        [],
                        ICON_SIZE_SMALL),
                    api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies_quota_cab.php?company_id=' . $row['id']
                );

                $list[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'ruc' => $row['ruc'],
                    'admin_name' => $row['admin_name'],
                    'total_user_quota' => $row['total_user_quota'],
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
