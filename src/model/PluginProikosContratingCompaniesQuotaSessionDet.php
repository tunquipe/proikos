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

    public function getData()
    {
        $where = "";

        if (api_is_contractor_admin()) {
            $rucCompany = ProikosPlugin::getUserRucCompany();
            $where = "WHERE e.ruc = '$rucCompany'";
        }

        $sql = "SELECT a.id, a.quota_session_id, e.ruc, e.name as company_name, f.name as session_name,
                DATE_FORMAT(a.created_at, '%d-%m-%Y %H:%i') AS quota_created_at,
                CONCAT(h.lastname, ' ', h.firstname) AS quota_created_by,
                DATE_FORMAT(a.expiration_date, '%d-%m-%Y') AS quota_vigency_date,
                CONCAT(g.lastname, ' ', g.firstname) AS student_name,
                g.user_id,
                DATE_FORMAT(a.updated_at, '%d-%m-%Y %H:%i') AS student_subscription_date
                FROM plugin_proikos_contrating_companies_quota_session_det a
                INNER JOIN plugin_proikos_contrating_companies_quota_session b ON b.id = a.quota_session_id
                INNER JOIN plugin_proikos_contrating_companies_quota_det c ON c.id = b.det_id
                INNER JOIN plugin_proikos_contrating_companies_quota_cab d ON d.id = c.cab_id
                INNER JOIN plugin_proikos_contrating_companies e ON e.id = d.contrating_company_id
                INNER JOIN session f ON f.id = a.session_id
                LEFT JOIN user g ON g.user_id = a.user_id
                LEFT JOIN user h ON h.user_id = a.created_user_id

                $where

                ORDER BY a.id DESC;";

        $result = \Database::query($sql);
        $data = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                if (api_is_platform_admin()) {
                    $btnDelete = Display::url(
                        Display::return_icon(
                            'delete.png',
                            get_lang('Delete'),
                            [],
                            ICON_SIZE_SMALL
                        ),
                        api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/reporting_quota_session_det.php?action=delete&quota_id_s='.$row['quota_session_id'].'&id=' . $row['id'],
                        [
                            'onclick' => 'javascript:if(!confirm(' . "'" .
                                addslashes(api_htmlentities(get_lang("ConfirmYourChoice")))
                                . "'" . ')) return false;',
                        ]
                    );
                    $row['actions'] = $btnDelete;
                }
                $row['status'] = !empty($row['user_id']);
                $row['class'] = !empty($row['user_id']) ? 'row-success' : 'row-warning';
                $data[] = $row;
            }
        }

        return $data;
    }


    public function getPaginatedData($page = 1, $perPage = 30, $searchParams = []): array
    {
        $where = [];

        if (api_is_contractor_admin()) {
            $rucCompany = Database::escape_string(ProikosPlugin::getUserRucCompany());
            $where[] = "e.ruc = '$rucCompany'";
        }

        // Agregar filtro de búsqueda según el criterio seleccionado
        if (!empty($searchParams['search_by']) && !empty($searchParams['search_term'])) {
            $term = Database::escape_string($searchParams['search_term']);

            switch ($searchParams['search_by']) {
                case 'ruc':
                    $where[] = "e.ruc LIKE '%$term%'";
                    break;
                case 'company':
                    $where[] = "e.name LIKE '%$term%'";
                    break;
                case 'student':
                    $where[] = "(g.firstname LIKE '%$term%' OR g.lastname LIKE '%$term%' OR CONCAT(g.lastname, ' ', g.firstname) LIKE '%$term%')";
                    break;
            }
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Calcular el offset
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT a.id, a.quota_session_id, e.ruc, e.name as company_name, f.name as session_name,
            DATE_FORMAT(a.created_at, '%d-%m-%Y %H:%i') AS quota_created_at,
            CONCAT(h.lastname, ' ', h.firstname) AS quota_created_by,
            DATE_FORMAT(a.expiration_date, '%d-%m-%Y') AS quota_vigency_date,
            CONCAT(g.lastname, ' ', g.firstname) AS student_name,
            g.user_id,
            DATE_FORMAT(a.updated_at, '%d-%m-%Y %H:%i') AS student_subscription_date
            FROM plugin_proikos_contrating_companies_quota_session_det a
            INNER JOIN plugin_proikos_contrating_companies_quota_session b ON b.id = a.quota_session_id
            INNER JOIN plugin_proikos_contrating_companies_quota_det c ON c.id = b.det_id
            INNER JOIN plugin_proikos_contrating_companies_quota_cab d ON d.id = c.cab_id
            INNER JOIN plugin_proikos_contrating_companies e ON e.id = d.contrating_company_id
            INNER JOIN session f ON f.id = a.session_id
            LEFT JOIN user g ON g.user_id = a.user_id
            LEFT JOIN user h ON h.user_id = a.created_user_id
            $whereClause
            ORDER BY a.id DESC
            LIMIT $perPage OFFSET $offset;";

        $result = \Database::query($sql);
        $data = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                if (api_is_platform_admin()) {
                    $btnDelete = Display::url(
                        Display::return_icon(
                            'delete.png',
                            get_lang('Delete'),
                            [],
                            ICON_SIZE_SMALL
                        ),
                        api_get_path(WEB_PLUGIN_PATH) . 'proikos/src/reporting_quota_session_det.php?action=delete&quota_id_s='.$row['quota_session_id'].'&id=' . $row['id'],
                        [
                            'onclick' => 'javascript:if(!confirm(' . "'" .
                                addslashes(api_htmlentities(get_lang("ConfirmYourChoice")))
                                . "'" . ')) return false;',
                        ]
                    );
                    $row['actions'] = $btnDelete;
                }
                $row['status'] = !empty($row['user_id']);
                $row['class'] = !empty($row['user_id']) ? 'row-success' : 'row-warning';
                $data[] = $row;
            }
        }

        return $data;
    }

    public function getTotalRecords($searchParams = []): int
    {
        $where = [];

        if (api_is_contractor_admin()) {
            $rucCompany = Database::escape_string(ProikosPlugin::getUserRucCompany());
            $where[] = "e.ruc = '$rucCompany'";
        }

        // Agregar filtro de búsqueda según el criterio seleccionado
        if (!empty($searchParams['search_by']) && !empty($searchParams['search_term'])) {
            $term = Database::escape_string($searchParams['search_term']);

            switch ($searchParams['search_by']) {
                case 'ruc':
                    $where[] = "e.ruc LIKE '%$term%'";
                    break;
                case 'company':
                    $where[] = "e.name LIKE '%$term%'";
                    break;
                case 'student':
                    $where[] = "(g.firstname LIKE '%$term%' OR g.lastname LIKE '%$term%' OR CONCAT(g.lastname, ' ', g.firstname) LIKE '%$term%')";
                    break;
            }
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "SELECT COUNT(a.id) as total
            FROM plugin_proikos_contrating_companies_quota_session_det a
            INNER JOIN plugin_proikos_contrating_companies_quota_session b ON b.id = a.quota_session_id
            INNER JOIN plugin_proikos_contrating_companies_quota_det c ON c.id = b.det_id
            INNER JOIN plugin_proikos_contrating_companies_quota_cab d ON d.id = c.cab_id
            INNER JOIN plugin_proikos_contrating_companies e ON e.id = d.contrating_company_id
            INNER JOIN session f ON f.id = a.session_id
            LEFT JOIN user g ON g.user_id = a.user_id
            $whereClause;";

        $result = \Database::query($sql);
        $row = Database::fetch_array($result);

        return (int)$row['total'];
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

    public function companySessionsWithQuota($userId)
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
        // Sesiones asincronicas
        $sql = "SELECT COUNT(*) as count FROM plugin_proikos_contrating_companies_quota_det c
                INNER JOIN plugin_proikos_contrating_companies_quota_cab d ON d.id = c.cab_id
                INNER JOIN plugin_proikos_contrating_companies e ON e.id = d.contrating_company_id
                WHERE e.ruc = '$rucCompany'
                AND e.status = 1
                AND c.session_mode = 1
                AND d.validity_date > '$currentDate';";
        $result = \Database::query($sql);
        $countAsincrono = 0;
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $countAsincrono += $row['count'];
            }
        }

        // Sesiones sincrónicas
        $sql = "SELECT COUNT(*) as count FROM plugin_proikos_contrating_companies_quota_det c
                INNER JOIN plugin_proikos_contrating_companies_quota_cab d ON d.id = c.cab_id
                INNER JOIN plugin_proikos_contrating_companies e ON e.id = d.contrating_company_id
                WHERE e.ruc = '$rucCompany'
                AND e.status = 1
                AND c.session_mode = 2
                AND d.validity_date > '$currentDate';";
        $result = \Database::query($sql);
        $countSincrono = 0;
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $countSincrono += $row['count'];
            }
        }

        return [
            'success' => true,
            'message' => 'Cupos disponibles',
            'data' => [
                'asincrono' => $countAsincrono,
                'sincrono' => $countSincrono
            ]
        ];
    }
}
