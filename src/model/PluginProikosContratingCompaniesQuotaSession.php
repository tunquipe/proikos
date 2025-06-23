<?php

class PluginProikosContratingCompaniesQuotaSession
{
    private $table;
    private $sessionModes;

    public function __construct($table, $sessionModes)
    {
        $this->table = $table;
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

    public function getDistributionByDetId($detId, $detSessionCategoryId, $sessionMode)
    {
        if (empty($detId)) {
            return [];
        }

        $table = \Database::get_main_table($this->table);
        $sessionCategoryTable = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
        $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);
        $sql = "SELECT a.*, b.name as category_name,
        ( SELECT COUNT(*) FROM plugin_proikos_contrating_companies_quota_session_det WHERE session_id = a.session_id AND quota_session_id = a.id and user_id is not null) AS used_user_quota,
        c.name as session_name, c.time_in_session, DATE_FORMAT(a.created_at, '%d-%m-%Y %H:%i') AS formatted_created_at,
        CONCAT(d.lastname, ' ', d.firstname) AS user_name
        FROM $table a
        INNER JOIN $sessionCategoryTable b ON b.id = '$detSessionCategoryId'
        INNER JOIN $sessionTable c ON c.id = a.session_id
        LEFT JOIN user d on a.created_user_id = d.user_id
        WHERE a.det_id = $detId";
        $result = Database::query($sql);

        $data = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $data[] = [
                    'id' => $row['id'],
                    'det_id' => $row['det_id'],
                    'session_id' => $row['session_id'],
                    'user_quota' => $row['user_quota'],
                    'used_user_quota' => $row['user_quota'] - $row['used_user_quota'],
                    'category_name' => $row['category_name'],
                    'session_name' => $row['session_name'],
                    'formatted_created_at' => $row['formatted_created_at'],
                    'user_name' => $row['user_name'],
                    'session_mode_name' => $this->sessionModes[$sessionMode] ?? '',
                    'time_in_session' => $row['time_in_session']
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
