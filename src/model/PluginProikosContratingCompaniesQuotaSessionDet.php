<?php

class PluginProikosContratingCompaniesQuotaSessionDet
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

    public function getQuotaBySessionId($sessionId)
    {
        $table = \Database::get_main_table($this->table);
        $sql = "SELECT * FROM $table WHERE session_id = $sessionId AND user_id IS NULL LIMIT 1";
        $result = \Database::query($sql);

        $item = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $item = $row;
            }
        }

        if (empty($item)) {
            return [
                'success' => false,
                'message' => 'No se encontraron cupos disponibles para la sesión'
            ];
        }

        return [
            'success' => true,
            'message' => 'Cupo disponible',
            'data' => $item
        ];
    }

    public function useQuota($id, $userId)
    {
        $sql = "UPDATE " . $this->table . " SET user_id = $userId WHERE id = $id";
        Database::query($sql);
    }
}
