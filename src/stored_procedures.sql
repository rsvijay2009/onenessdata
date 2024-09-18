DROP PROCEDURE IF EXISTS CompareTables;

DELIMITER $$
CREATE PROCEDURE CompareTables(
    IN tableA VARCHAR(255),
    IN tableB VARCHAR(255),
    IN tableA_Relationship VARCHAR(255),
    IN tableB_Relationship VARCHAR(255),
    IN tableA_Column_To_Compare VARCHAR(255),
    IN tableB_Column_To_Compare VARCHAR(255)
)
BEGIN
    DECLARE timestamp_str VARCHAR(20);
    DECLARE compare_table_name VARCHAR(255);
    DECLARE create_table_sql TEXT;
    DECLARE insert_sql TEXT;
    DECLARE select_sql TEXT;

    -- Generate unique table name using timestamp
    SET timestamp_str = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s');
    SET compare_table_name = CONCAT('compare_data_', timestamp_str);

    -- Create the comparison table
    SET @create_table_sql = CONCAT(
        'CREATE TABLE ', compare_table_name, ' (',
        'relationship_key VARCHAR(255), ',
        'tableA_', tableA_Relationship, ' VARCHAR(255), ',
        'tableB_', tableB_Relationship, ' VARCHAR(255), ',
        'difference VARCHAR(255))'
    );

    -- Execute the create table SQL
    PREPARE stmt FROM @create_table_sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Construct the insert query
    SET @insert_sql = CONCAT(
        'INSERT INTO ', compare_table_name, ' (relationship_key) ',
        'SELECT DISTINCT ', tableA_Relationship, ' FROM ', tableA, ' ',
        'UNION ',
        'SELECT DISTINCT ', tableB_Relationship, ' FROM ', tableB
    );

    -- Execute the insert SQL
    PREPARE stmt FROM @insert_sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    
    -- Construct the select query
    SET select_sql = CONCAT('SELECT * FROM ', compare_table_name);

    -- Execute the select SQL and return the results
    SET @select_sql = select_sql;
    PREPARE stmt FROM @select_sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END $$
DELIMITER ;


DROP PROCEDURE IF EXISTS DeleteProjectData;

DELIMITER $$
CREATE PROCEDURE DeleteProjectData (IN projectId INT)
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE base_table_name VARCHAR(255);
    DECLARE table1_name VARCHAR(255);
    DECLARE table2_name VARCHAR(255);
    DECLARE table3_name VARCHAR(255);
    

    DECLARE cur CURSOR FOR 
        SELECT `name` 
        FROM tables_list 
        WHERE project_id = projectId;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO base_table_name;
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Construct the names of the related tables
        SET table1_name = CONCAT(base_table_name, '_datatype');
        SET table2_name = CONCAT(base_table_name, '_data_verification');
        SET table3_name = CONCAT(base_table_name, '_dashboard');

        -- Prepare the drop table statements
        SET @drop_base_table = CONCAT('DROP TABLE IF EXISTS ', base_table_name);
        SET @drop_table1 = CONCAT('DROP TABLE IF EXISTS ', table1_name);
        SET @drop_table2 = CONCAT('DROP TABLE IF EXISTS ', table2_name);
        SET @drop_table3 = CONCAT('DROP TABLE IF EXISTS ', table3_name);

        -- Execute the drop table statements
        PREPARE stmt1 FROM @drop_base_table;
        EXECUTE stmt1;
        DEALLOCATE PREPARE stmt1;

        PREPARE stmt2 FROM @drop_table1;
        EXECUTE stmt2;
        DEALLOCATE PREPARE stmt2;

        PREPARE stmt3 FROM @drop_table2;
        EXECUTE stmt3;
        DEALLOCATE PREPARE stmt3;

        PREPARE stmt4 FROM @drop_table3;
        EXECUTE stmt4;
        DEALLOCATE PREPARE stmt4;

        -- Delete the record from tables_list
        DELETE FROM tables_list WHERE name = base_table_name;
    END LOOP;

    CLOSE cur;
    -- Delete the record from projects table
    DELETE FROM projects WHERE id = projectId;
END $$
DELIMITER ;


DROP PROCEDURE IF EXISTS DropAndCleanUpTable;

DELIMITER $$
CREATE PROCEDURE DropAndCleanUpTable(IN tableId INT)
BEGIN
    DECLARE _tableName VARCHAR(255);
    DECLARE _dataTypeTableName VARCHAR(255);
    DECLARE _dataVerificationTableName VARCHAR(255);
    DECLARE _dashboardTableName VARCHAR(255);
    DECLARE _dropQuery VARCHAR(500);

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
    BEGIN
        -- Capture error and log it
        GET DIAGNOSTICS CONDITION 1
            @p1 = RETURNED_SQLSTATE,
            @p2 = MESSAGE_TEXT;
        INSERT INTO debug_log (message, created_at)
        VALUES (CONCAT('SQLSTATE: ', @p1, ', Error: ', @p2), NOW());
    END;
    
    -- Select the table name from tables_list
    SELECT name INTO _tableName FROM tables_list WHERE id = tableId LIMIT 1;
    
    -- Set the dynamic table names
    SET _dataTypeTableName = CONCAT(_tableName, '_datatype');
    SET _dataVerificationTableName = CONCAT(_tableName, '_data_verification');
    SET _dashboardTableName = CONCAT(_tableName, '_dashboard');

    -- Check if the table name is not empty and drop the table if it exists
    IF _tableName IS NOT NULL AND _tableName <> '' THEN
        SET @dropQuery = CONCAT('DROP TABLE IF EXISTS ', _tableName);
        PREPARE stmt FROM @dropQuery;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
    
    -- Drop _dataTypeTableName if it exists
    IF _dataTypeTableName IS NOT NULL AND _dataTypeTableName <> '' THEN
        SET @dropQuery = CONCAT('DROP TABLE IF EXISTS ', _dataTypeTableName);
        PREPARE stmt FROM @dropQuery;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;

    -- Drop _dataVerificationTableName if it exists
    IF _dataVerificationTableName IS NOT NULL AND _dataVerificationTableName <> '' THEN
        SET @dropQuery = CONCAT('DROP TABLE IF EXISTS ', _dataVerificationTableName);
        PREPARE stmt FROM @dropQuery;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;

    -- Drop _dashboardTableName if it exists
    IF _dashboardTableName IS NOT NULL AND _dashboardTableName <> '' THEN
        SET @dropQuery = CONCAT('DROP TABLE IF EXISTS ', _dashboardTableName);
        PREPARE stmt FROM @dropQuery;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;

    -- Delete the entry from tables_list
    DELETE FROM tables_list WHERE id = tableId;
END $$
DELIMITER ;


DROP PROCEDURE IF EXISTS FindIncorrectData;

DELIMITER $$
CREATE PROCEDURE FindIncorrectData (IN tbl_name VARCHAR(255), IN datatype_tbl_name VARCHAR(255))
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

    SET @project_id_query = CONCAT('SELECT project_id INTO @project_id FROM tables_list WHERE id = ', @table_id);
    PREPARE stmt FROM @project_id_query;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Open the cursor
    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO col_name, dt_id;
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Insert data that doesn't match any of the specified regular expressions with "Others" remark
        SET @query = CONCAT(
            'INSERT INTO ', @verification_table, 
            ' (table_id, table_name, original_table_name, master_primary_key, column_name, project_id, remarks) ',
            'SELECT table_id, "', tbl_name, '", "', tbl_name, '", primary_key, "', col_name, '", ', @project_id, ', "Others" ',
            'FROM ', tbl_name, ' WHERE ', col_name, ' NOT REGEXP "^[a-zA-Z ]+$" ',
            'AND ', col_name, ' NOT REGEXP "^[0-9]+$" ',
            'AND ', col_name, ' NOT REGEXP "^[0-9]{2}/[0-9]{2}/[0-9]{4}$" ',
            'AND ', col_name, ' NOT REGEXP "^[0-9]{2}-[0-9]{2}-[0-9]{4}$" ',
            'AND ', col_name, ' NOT REGEXP "^[a-zA-Z0-9]+$" ',
            'AND ', col_name, ' NOT REGEXP "^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,24}$" ',
            'AND NOT EXISTS (SELECT 1 FROM ', @verification_table, ' WHERE master_primary_key = ', tbl_name, '.primary_key AND column_name = "', col_name, '")'
        );
        
        -- Execute the query
        PREPARE stmt FROM @query;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;

        -- Additional check for NULL, empty string, and 'NULL'
        SET @null_check_query = CONCAT(
            'INSERT INTO ', @verification_table,
            ' (table_id, table_name, original_table_name, master_primary_key, column_name, project_id, remarks) ',
            'SELECT table_id, "', tbl_name, '", "', tbl_name, '", primary_key, "', col_name, '", ', @project_id, ', "NULL" ',
            'FROM ', tbl_name, ' WHERE (', col_name, ' IS NULL OR ', col_name, ' = "" OR ', col_name, ' = "NULL") ',
            'AND NOT EXISTS (SELECT 1 FROM ', @verification_table, ' WHERE master_primary_key = ', tbl_name, '.primary_key AND column_name = "', col_name, '")'
        );

        -- Execute the query for NULL, empty string, and 'NULL'
        PREPARE stmt FROM @null_check_query;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;

        -- Now process the remaining data based on the specific regular expressions
        CASE dt_id
            WHEN 1 THEN -- Text
                SET @query = CONCAT('SELECT COUNT(*) INTO @incorrect_count FROM ', tbl_name, ' WHERE ', col_name, ' NOT REGEXP "^[a-zA-Z ]+$"');
            WHEN 2 THEN -- Number
                SET @query = CONCAT('SELECT COUNT(*) INTO @incorrect_count FROM ', tbl_name, ' WHERE ', col_name, ' NOT REGEXP "^[0-9]+$"');
            WHEN 3 THEN -- Date
                SET @query = CONCAT('SELECT COUNT(*) INTO @incorrect_count FROM ', tbl_name, ' WHERE NOT (', col_name, ' REGEXP "^[0-9]{2}/[0-9]{2}/[0-9]{4}$" AND STR_TO_DATE(', col_name, ', "%d/%m/%Y") IS NOT NULL OR ', col_name, ' REGEXP "^[0-9]{2}-[0-9]{2}-[0-9]{4}$" AND STR_TO_DATE(', col_name, ', "%d-%m-%Y") IS NOT NULL)');
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

        -- Insert incorrect data into the verification table if it doesn't already exist
        IF @incorrect_count > 0 THEN
            CASE dt_id
                WHEN 1 THEN -- Text
                    SET @insert_query = CONCAT(
                        'INSERT INTO ', @verification_table,
                        ' (table_id, table_name, original_table_name, master_primary_key, column_name, project_id, remarks) ',
                        'SELECT table_id, "', tbl_name, '", "', tbl_name, '", primary_key, "', col_name, '", ', @project_id, ', "Text" ',
                        'FROM ', tbl_name, ' WHERE ', col_name, ' NOT REGEXP "^[a-zA-Z ]+$" ',
                        'AND NOT EXISTS (SELECT 1 FROM ', @verification_table, ' WHERE master_primary_key = ', tbl_name, '.primary_key AND column_name = "', col_name, '")'
                    );
                WHEN 2 THEN -- Number
                    SET @insert_query = CONCAT(
                        'INSERT INTO ', @verification_table,
                        ' (table_id, table_name, original_table_name, master_primary_key, column_name, project_id, remarks) ',
                        'SELECT table_id, "', tbl_name, '", "', tbl_name, '", primary_key, "', col_name, '", ', @project_id, ', "Number" ',
                        'FROM ', tbl_name, ' WHERE ', col_name, ' NOT REGEXP "^[0-9]+$" ',
                        'AND NOT EXISTS (SELECT 1 FROM ', @verification_table, ' WHERE master_primary_key = ', tbl_name, '.primary_key AND column_name = "', col_name, '")'
                    );
                WHEN 3 THEN -- Date
                    SET @insert_query = CONCAT(
                        'INSERT INTO ', @verification_table,
                        ' (table_id, table_name, original_table_name, master_primary_key, column_name, project_id, remarks) ',
                        'SELECT table_id, "', tbl_name, '", "', tbl_name, '", primary_key, "', col_name, '", ', @project_id, ', "Date" ',
                        'FROM ', tbl_name, ' WHERE NOT (', col_name, ' REGEXP "^[0-9]{2}/[0-9]{2}/[0-9]{4}$" AND STR_TO_DATE(', col_name, ', "%d/%m/%Y") IS NOT NULL OR ', col_name, ' REGEXP "^[0-9]{2}-[0-9]{2}-[0-9]{4}$" AND STR_TO_DATE(', col_name, ', "%d-%m-%Y") IS NOT NULL) ',
                        'AND NOT EXISTS (SELECT 1 FROM ', @verification_table, ' WHERE master_primary_key = ', tbl_name, '.primary_key AND column_name = "', col_name, '")'
                    );
                WHEN 4 THEN -- Alphanumeric
                    SET @insert_query = CONCAT(
                        'INSERT INTO ', @verification_table,
                        ' (table_id, table_name, original_table_name, master_primary_key, column_name, project_id, remarks) ',
                        'SELECT table_id, "', tbl_name, '", "', tbl_name, '", primary_key, "', col_name, '", ', @project_id, ', "Alphanumeric" ',
                        'FROM ', tbl_name, ' WHERE ', col_name, ' NOT REGEXP "^[a-zA-Z0-9]+$" ',
                        'AND NOT EXISTS (SELECT 1 FROM ', @verification_table, ' WHERE master_primary_key = ', tbl_name, '.primary_key AND column_name = "', col_name, '")'
                    );
                WHEN 5 THEN -- Email
                    SET @insert_query = CONCAT(
                        'INSERT INTO ', @verification_table,
                        ' (table_id, table_name, original_table_name, master_primary_key, column_name, project_id, remarks) ',
                        'SELECT table_id, "', tbl_name, '", "', tbl_name, '", primary_key, "', col_name, '", ', @project_id, ', "Email" ',
                        'FROM ', tbl_name, ' WHERE ', col_name, ' NOT REGEXP "^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,24}$" ',
                        'AND NOT EXISTS (SELECT 1 FROM ', @verification_table, ' WHERE master_primary_key = ', tbl_name, '.primary_key AND column_name = "', col_name, '")'
                    );
            END CASE;

            -- Execute the insert query
            PREPARE stmt FROM @insert_query;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
    END LOOP;

    -- Close the cursor
    CLOSE cur;
END $$
DELIMITER ;


DROP PROCEDURE IF EXISTS GetDashboardData;

DELIMITER $$
CREATE PROCEDURE GetDashboardData (IN dashboard_table_name VARCHAR(64), IN table_name VARCHAR(64))
BEGIN
    DECLARE data_quality_correct_data INT;
    DECLARE data_quality_incorrect_data INT;
    DECLARE text_issue INT;
    DECLARE number_issue INT;
    DECLARE date_issue INT;
    DECLARE alphanumeric_issue INT;
    DECLARE email_issue INT;
    DECLARE duplicate_entries_issue INT;
    DECLARE others_issue INT;
    DECLARE null_issue INT;
    DECLARE overall_correct_data INT;
    DECLARE overall_incorrect_data INT;
    DECLARE verification_table_name VARCHAR(64);
    DECLARE sql_query TEXT;

    -- Concatenate '_data_verification' to the table name
    SET verification_table_name = CONCAT(table_name, '_data_verification');

    -- Construct the dynamic SQL query to get 'others_issue' count
    SET @sql_query_others = CONCAT('SELECT COUNT(*) INTO @others_issue FROM ', verification_table_name, ' WHERE  ignore_flag = 0 AND remarks = ''Others''');

    -- Prepare and execute the dynamic SQL for 'Others' count
    PREPARE stmt_others FROM @sql_query_others;
    EXECUTE stmt_others;
    DEALLOCATE PREPARE stmt_others;

    -- Assign the others issue count
    SET others_issue = @others_issue;

    -- Construct the dynamic SQL query to get 'null_issue' count
    SET @sql_query_null = CONCAT('SELECT COUNT(*) INTO @null_issue FROM ', verification_table_name, ' WHERE ignore_flag = 0 AND (remarks = ''NULL'' OR remarks IS NULL)');

    -- Prepare and execute the dynamic SQL for 'Null' count
    PREPARE stmt_null FROM @sql_query_null;
    EXECUTE stmt_null;
    DEALLOCATE PREPARE stmt_null;

    -- Assign the null issue count
    SET null_issue = @null_issue;

    -- Construct the dynamic SQL query for the other metrics
    SET @sql_query = CONCAT('SELECT data_quality_correct_data, data_quality_incorrect_data, text_issue, number_issue, date_issue, alphanumeric_issue, email_issue, duplicate_entries_issue, overall_correct_data, overall_incorrect_data ',
                            'INTO @data_quality_correct_data, @data_quality_incorrect_data, @text_issue, @number_issue, @date_issue, @alphanumeric_issue, @email_issue, @duplicate_entries_issue, @overall_correct_data, @overall_incorrect_data ',
                            'FROM ', dashboard_table_name, ' LIMIT 1');

    -- Prepare and execute the dynamic SQL for the main metrics
    PREPARE stmt FROM @sql_query;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Assign the variables from the prepared statement execution
    SET data_quality_correct_data = @data_quality_correct_data;
    SET data_quality_incorrect_data = @data_quality_incorrect_data;
    SET text_issue = @text_issue;
    SET number_issue = @number_issue;
    SET date_issue = @date_issue;
    SET alphanumeric_issue = @alphanumeric_issue;
    SET email_issue = @email_issue;
    SET duplicate_entries_issue = @duplicate_entries_issue;
    SET overall_correct_data = @overall_correct_data;
    SET overall_incorrect_data = @overall_incorrect_data;

    -- Return the values
    SELECT data_quality_correct_data AS 'data_quality_correct_data',
           data_quality_incorrect_data AS 'data_quality_incorrect_data',
           text_issue AS 'text_issue',
           number_issue AS 'number_issue',
           date_issue AS 'date_issue',
           alphanumeric_issue AS 'alphanumeric_issue',
           email_issue AS 'email_issue',
           duplicate_entries_issue AS 'duplicate_entries_issue',
           others_issue AS 'others_issue',
           null_issue AS 'null_issue',
           overall_correct_data AS 'overall_correct_data',
           overall_incorrect_data AS 'overall_incorrect_data';
END $$
DELIMITER ;