<?php

class PluginProikosContratingCompaniesQuotaSession
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

    public function getDistributionByDetId($detId)
    {
        if (empty($detId)) {
            return [];
        }

        $table = \Database::get_main_table($this->table);
        $sql = "SELECT * FROM $table WHERE det_id = $detId";
        $result = Database::query($sql);

        $data = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $data[] = [
                    'id' => $row['id'],
                    'det_id' => $row['det_id'],
                    'session_id' => $row['session_id'],
                    'user_quota' => $row['user_quota'],
                ];
            }
        }

        return $data;
    }

    public function update($values)
    {
        if (!is_array($values)) {
            return false;
        }

        $table = \Database::get_main_table($this->table);
        $params = [];

        if (isset($values['session_id'])) {
            $params['session_id'] = $values['session_id'];
        }

        if (isset($values['user_quota'])) {
            $params['user_quota'] = $values['user_quota'];
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
