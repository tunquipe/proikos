<?php

class PluginProikosContratingCompaniesQuotaCab
{
    private $table;
    private $contratingCompaniesQuotaDet;

    public function __construct($table, $contratingCompaniesQuotaDet)
    {
        $this->table = $table;
        $this->contratingCompaniesQuotaDet = $contratingCompaniesQuotaDet;
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

    public function getData($id)
    {
        if (empty($id)) {
            return false;
        }

        $table = Database::get_main_table($this->table);
        $sql = "SELECT
            a.id,
            a.contrating_company_id,
            (SELECT SUM(user_quota) FROM " . $this->contratingCompaniesQuotaDet . " WHERE cab_id = a.id) AS total_user_quota,
            DATE_FORMAT(a.created_at, '%d-%m-%Y %H:%i') AS formatted_created_at,
            CONCAT(b.lastname, ' ', b.firstname) AS user_name
            FROM $table a
            LEFT JOIN user b on a.created_user_id = b.user_id
            WHERE a.contrating_company_id = $id";
        $result = Database::query($sql);
        $items = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {

                // edit action
                $action = Display::url(
                    Display::return_icon(
                        'edit.png',
                        null,
                        [],
                        ICON_SIZE_SMALL),
                    api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies_quota_cab.php?company_id=' . $id . '&action=edit&quota_cab_id=' . $row['id']
                );

                // delete action
                $action .= Display::url(
                    Display::return_icon(
                        'delete.png',
                        get_lang('Delete'),
                        [],
                        ICON_SIZE_SMALL
                    ),
                    api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_companies_quota_cab.php?company_id=' . $id . '&action=delete&quota_cab_id=' . $row['id'],
                    [
                        'onclick' => 'javascript:if(!confirm(' . "'" .
                            addslashes(api_htmlentities(get_lang("ConfirmYourChoice")))
                            . "'" . ')) return false;',
                    ]
                );

                $row['actions'] = $action;
                $items[] = $row;
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
            Database::delete(
                $this->contratingCompaniesQuotaDet,
                ['cab_id = ?' => $id]
            );

            return true;
        }

        return false;
    }
}
