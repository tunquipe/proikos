<?php

class PluginProikosContratingCompaniesQuotaCab
{
    private $table;

    public function __construct($table)
    {
        $this->table = $table;
    }

    public function save($values)
    {
        if (!is_array($values)) {
            return false;
        }

        $table = \Database::get_main_table($this->table);
        $params = [
            'contrating_company_id' => $values['cab_id'],
            'created_user_id' => $values['user_id']
        ];
        $id = \Database::insert($table, $params);

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
            DATE_FORMAT(a.created_at, '%d-%m-%Y %H:%i') AS formatted_created_at,
            CONCAT(b.lastname, ' ', b.firstname) AS user_name
            FROM $table a
            LEFT JOIN user b on a.created_user_id = b.user_id
            WHERE a.contrating_company_id = $id";
        $result = Database::query($sql);
        $items = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                // delete action
                $action = Display::url(
                    Display::return_icon(
                        'delete.png',
                        get_lang('Delete'),
                        [],
                        ICON_SIZE_SMALL
                    ),
                    api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/contrating_company_detail_management.php?action=delete&id=' . $row['cab_id'] . '&item_id=' . $row['id'],
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
            return true;
        }

        return false;
    }
}
