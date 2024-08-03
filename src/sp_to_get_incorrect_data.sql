CREATE TABLE `debug_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

DELIMITER $$

CREATE PROCEDURE `FindIncorrectData`(IN tbl_name VARCHAR(255), IN datatype_tbl_name VARCHAR(255))
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE col_name VARCHAR(255);
    DECLARE dt_id INT;
    DECLARE incorrect_count INT DEFAULT 0;
    DECLARE table_id INT;
    DECLARE project_id INT;

    DECLARE cur CURSOR FOR SELECT column_name, datatype_id FROM temp_datatype;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    -- Temporary table to store datatype information
    DROP TEMPORARY TABLE IF EXISTS temp_datatype;
    CREATE TEMPORARY TABLE temp_datatype (
        column_name VARCHAR(255),
        datatype_id INT
    );

    SET @verification_table = CONCAT(tbl_name, '_data_verification');

    -- Populate the temporary table with column names and datatype ids dynamically
    SET @fetch_columns_query = CONCAT('INSERT INTO temp_datatype (column_name, datatype_id) SELECT column_name, datatype_id FROM ', datatype_tbl_name);
    PREPARE stmt FROM @fetch_columns_query;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Get the table_id and project_id from the tables_list
    SET @table_id_query = CONCAT('SELECT table_id INTO @table_id FROM ', tbl_name, ' LIMIT 1');
    PREPARE stmt FROM @table_id_query;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    INSERT INTO debug_log (message) VALUES (CONCAT('table_id: ', @table_id)); -- Debugging log
    
    SET @project_id_query = CONCAT('SELECT project_id INTO @project_id FROM tables_list WHERE id = ', @table_id);
    PREPARE stmt FROM @project_id_query;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    
    INSERT INTO debug_log (message) VALUES (CONCAT('project_id: ', @project_id)); -- Debugging log
    
    SELECT project_id; -- Debugging statement

    -- Open the cursor
    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO col_name, dt_id;
        IF done THEN
            LEAVE read_loop;
        END IF;

        INSERT INTO debug_log (message) VALUES (CONCAT('Processing column: ', col_name, ' with datatype: ', dt_id)); -- Debugging log

        -- Check for incorrect data based on datatype
        CASE dt_id
            WHEN 1 THEN -- Text
                SET @query = CONCAT('SELECT COUNT(*) INTO @incorrect_count FROM ', tbl_name, ' WHERE ', col_name, ' NOT REGEXP "^[a-zA-Z ]+$"');
            WHEN 2 THEN -- Number
                SET @query = CONCAT('SELECT COUNT(*) INTO @incorrect_count FROM ', tbl_name, ' WHERE ', col_name, ' NOT REGEXP "^[0-9]{1,3}(?:,[0-9]{3})*(?:\.[0-9]+)?$"');
            WHEN 3 THEN -- Date
                SET @query = CONCAT('SELECT COUNT(*) INTO @incorrect_count FROM ', tbl_name, ' WHERE (', col_name, ' NOT REGEXP "^[0-9]{2}/[0-9]{2}/[0-9]{4}$" OR STR_TO_DATE(', col_name, ', "%d/%m/%Y") IS NULL OR STR_TO_DATE(', col_name, ', "%d-%m-%Y") IS NULL OR STR_TO_DATE(', col_name, ', "%Y/%m/%d") IS NULL OR STR_TO_DATE(', col_name, ', "%Y-%m-%d") IS NULL)');
            WHEN 4 THEN -- Alphanumeric
                SET @query = CONCAT('SELECT COUNT(*) INTO @incorrect_count FROM ', tbl_name, ' WHERE ', col_name, ' NOT REGEXP "^[a-zA-Z0-9]+$"');
            WHEN 5 THEN -- Email
                SET @query = CONCAT('SELECT COUNT(*) INTO @incorrect_count FROM ', tbl_name, ' WHERE ', col_name, ' NOT REGEXP "^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,24}$"');
            ELSE
                SET @incorrect_count = 0;
        END CASE;

        -- Execute the query
        PREPARE stmt FROM @query;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;

        INSERT INTO debug_log (message) VALUES (CONCAT('Incorrect count for column: ', col_name, ' = ', @incorrect_count)); -- Debugging log

        -- Insert incorrect data into the verification table
        IF @incorrect_count > 0 THEN
            CASE dt_id
                WHEN 1 THEN -- Text
                    SET @insert_query = CONCAT(
                        'INSERT INTO ', @verification_table, ' (table_id, table_name, original_table_name, master_primary_key, column_name, project_id) ',
                        'SELECT table_id, "', tbl_name, '", "', tbl_name, '", primary_key, "', col_name, '", ', @project_id, ' FROM ', tbl_name, 
                        ' WHERE ', col_name, ' NOT REGEXP "^[a-zA-Z ]+$"'
                    );
                WHEN 2 THEN -- Number
                    SET @insert_query = CONCAT(
                        'INSERT INTO ', @verification_table, ' (table_id, table_name, original_table_name, master_primary_key, column_name, project_id) ',
                        'SELECT table_id, "', tbl_name, '", "', tbl_name, '", primary_key, "', col_name, '", ', @project_id, ' FROM ', tbl_name, 
                        ' WHERE ', col_name, ' NOT REGEXP "^[0-9]+$"'
                    );
                WHEN 3 THEN -- Date
                    SET @insert_query = CONCAT(
                        'INSERT INTO ', @verification_table, ' (table_id, table_name, original_table_name, master_primary_key, column_name, project_id) ',
                        'SELECT table_id, "', tbl_name, '", "', tbl_name, '", primary_key, "', col_name, '", ', @project_id, ' FROM ', tbl_name, 
                        ' WHERE (', col_name, ' NOT REGEXP "^[0-9]{2}/[0-9]{2}/[0-9]{4}$" OR STR_TO_DATE(', col_name, ', "%d/%m/%Y") IS NULL OR STR_TO_DATE(', col_name, ', "%d-%m-%Y") IS NULL OR STR_TO_DATE(', col_name, ', "%Y/%m/%d") IS NULL OR STR_TO_DATE(', col_name, ', "%Y-%m-%d") IS NULL)'
                    );
                WHEN 4 THEN -- Alphanumeric
                    SET @insert_query = CONCAT(
                        'INSERT INTO ', @verification_table, ' (table_id, table_name, original_table_name, master_primary_key, column_name, project_id) ',
                        'SELECT table_id, "', tbl_name, '", "', tbl_name, '", primary_key, "', col_name, '", ', @project_id, ' FROM ', tbl_name, 
                        ' WHERE ', col_name, ' NOT REGEXP "^[a-zA-Z0-9]+$"'
                    );
                WHEN 5 THEN -- Email
                    SET @insert_query = CONCAT(
                        'INSERT INTO ', @verification_table, ' (table_id, table_name, original_table_name, master_primary_key, column_name, project_id) ',
                        'SELECT table_id, "', tbl_name, '", "', tbl_name, '", primary_key, "', col_name, '", ', @project_id, ' FROM ', tbl_name, 
                        ' WHERE ', col_name, ' NOT REGEXP "^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,24}$"'
                    );
            END CASE;

            -- Execute the insert query
            PREPARE insert_stmt FROM @insert_query;
            EXECUTE insert_stmt;
            DEALLOCATE PREPARE insert_stmt;
            
            INSERT INTO debug_log (message) VALUES (CONCAT('Inserted incorrect data for column: ', col_name)); -- Debugging log
        END IF;
    END LOOP;

    CLOSE cur;
END $$

DELIMITER ;