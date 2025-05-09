<?php

class PluginProikosContratingCompaniesQuotaDet
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

        $table = \Database::get_main_table($this->table);
        $params = [];

        if (isset($values['session_category_id'])) {
            $params['session_category_id'] = $values['session_category_id'];
        }

        if (isset($values['user_quota'])) {
            $params['user_quota'] = $values['user_quota'];
        }

        if (isset($values['updated_user_id'])) {
            $params['updated_user_id'] = $values['updated_user_id'];
        }

        if (isset($values['price_unit'])) {
            $params['price_unit'] = $values['price_unit'];
        }

        if (!empty($params)) {
            \Database::update(
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

    public function delete($items)
    {
        if (empty($items) || !is_array($items)) {
            return false;
        }

        foreach ($items as $item) {
            $table = \Database::get_main_table($this->table);
            \Database::delete(
                $table,
                ['id = ?' => $item]
            );
        }

        return true;
    }
}
