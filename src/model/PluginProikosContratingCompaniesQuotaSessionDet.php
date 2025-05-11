<?php

class PluginProikosContratingCompaniesQuotaSessionDet
{
    private $table;
    private $userTable;

    public function __construct($table, $userTable)
    {
        $this->table = $table;
        $this->userTable = $userTable;
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

    public function getQuotaBySessionId($sessionId, $userId)
    {
        $userTable = Database::get_main_table($this->userTable);
        $sql = "SELECT ruc_company FROM $userTable WHERE user_id = $userId";
        $result = Database::query($sql);
        $rucCompany = '';
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $rucCompany = $row['ruc_company'];
            }
        }

        if (empty($rucCompany)) {
            return [
                'success' => false,
                'message' => 'No se encontró el RUC de la empresa'
            ];
        }

        $currentDate = date('Y-m-d');
        $sql = "SELECT a.* FROM plugin_proikos_contrating_companies_quota_session_det a
                INNER JOIN plugin_proikos_contrating_companies_quota_session b ON b.id = a.quota_session_id
                INNER JOIN plugin_proikos_contrating_companies_quota_det c ON c.id = b.det_id
                INNER JOIN plugin_proikos_contrating_companies_quota_cab d ON d.id = c.cab_id
                INNER JOIN plugin_proikos_contrating_companies e ON e.id = d.contrating_company_id
                WHERE a.session_id = $sessionId
                AND e.ruc = '$rucCompany'
                AND e.status = 1
                AND d.validity_date > '$currentDate'
                AND a.user_id IS NULL
                ORDER BY a.id ASC
                LIMIT 1;";
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
