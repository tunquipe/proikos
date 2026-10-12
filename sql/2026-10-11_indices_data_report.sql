-- =====================================================================
-- Proikos - Índices para acelerar el reporte data_report.php
-- Fecha: 2026-10-11
--
-- Uso (en el VPS):
--   mysql -u USUARIO -p NOMBRE_BD < 2026-10-11_indices_data_report.sql
--
-- - Es idempotente: si un índice ya existe lo omite, se puede correr varias veces.
-- - No modifica datos, solo agrega índices (InnoDB los crea en línea, sin bloquear
--   lecturas/escrituras). Compatible con MySQL 5.7/8.x y MariaDB.
-- =====================================================================

DROP PROCEDURE IF EXISTS proikos_add_index;

DELIMITER $$
CREATE PROCEDURE proikos_add_index(IN p_table VARCHAR(64), IN p_index VARCHAR(64), IN p_columns VARCHAR(255))
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = p_table
    ) THEN
        SELECT CONCAT('OMITIDO (no existe la tabla): ', p_table) AS resultado;
    ELSEIF EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = p_table AND index_name = p_index
    ) THEN
        SELECT CONCAT('YA EXISTE: ', p_table, '.', p_index) AS resultado;
    ELSE
        SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_columns, ')');
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        SELECT CONCAT('CREADO: ', p_table, '.', p_index, ' (', p_columns, ')') AS resultado;
    END IF;
END$$
DELIMITER ;

-- Estado del certificado por usuario/sesión/curso (el más crítico del reporte)
CALL proikos_add_index('plugin_easycertificate_send', 'idx_pecs_user_session_course', 'user_id, session_id, course_id');

-- Datos del usuario Proikos y filtro por empresa (gestores de cupo)
CALL proikos_add_index('plugin_proikos_users', 'idx_ppu_user_id', 'user_id');
CALL proikos_add_index('plugin_proikos_users', 'idx_ppu_ruc_company', 'ruc_company');

-- Incidencias (sustento) y verificación de documentos, consultadas por cada fila
CALL proikos_add_index('plugin_proikos_sustenance', 'idx_pps_user_session', 'user_id, session_id');
CALL proikos_add_index('plugin_proikos_check_docs', 'idx_ppcd_user_session', 'user_id, session_id');

-- Notas de exámenes por alumno (getScoreExercise)
CALL proikos_add_index('track_e_exercises', 'idx_tee_user_exo_session', 'exe_user_id, exe_exo_id, session_id');

DROP PROCEDURE IF EXISTS proikos_add_index;

-- Actualizar estadísticas para que el optimizador use los nuevos índices
ANALYZE TABLE plugin_easycertificate_send, plugin_proikos_users, plugin_proikos_sustenance,
              plugin_proikos_check_docs, track_e_exercises;

-- ---------------------------------------------------------------------
-- Reversión (solo si fuera necesario):
-- ALTER TABLE plugin_easycertificate_send DROP INDEX idx_pecs_user_session_course;
-- ALTER TABLE plugin_proikos_users        DROP INDEX idx_ppu_user_id, DROP INDEX idx_ppu_ruc_company;
-- ALTER TABLE plugin_proikos_sustenance   DROP INDEX idx_pps_user_session;
-- ALTER TABLE plugin_proikos_check_docs   DROP INDEX idx_ppcd_user_session;
-- ALTER TABLE track_e_exercises           DROP INDEX idx_tee_user_exo_session;
-- ---------------------------------------------------------------------
