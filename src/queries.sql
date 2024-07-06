DROP TABLE IF EXISTS `projects`;
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    status VARCHAR(100) NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

DROP TABLE IF EXISTS `tables_list`;
CREATE TABLE tables_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    original_table_name VARCHAR(255) NOT NULL,
    project_id INT DEFAULT NULL,
    status VARCHAR(100) NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

DROP TABLE IF EXISTS `datatypes`;
CREATE TABLE datatypes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description VARCHAR(255) NOT NULL,
    status VARCHAR(100) NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
INSERT INTO datatypes (name, description)
VALUES 
    ('Text', 'Ex: ABCD ...'),
    ('Number', 'Ex: 0123 ...'),
    ('Date', 'Ex: DD:MM:YYYY ...'),
    ('Alphanumeric', 'Ex: AB12C5 ...'),
    ('Email', 'Ex: name@mail.com ...');

DROP TABLE IF EXISTS `temp_table_ids`;
CREATE TABLE temp_table_ids (
    table_id INT
);

DROP PROCEDURE IF EXISTS GetDashboardData;

DELIMITER $$
CREATE PROCEDURE `GetDashboardData`(IN table_name VARCHAR(64))
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
    DECLARE sql_query TEXT;

    -- Construct the dynamic SQL query
    SET @sql_query = CONCAT('SELECT data_quality_correct_data, data_quality_incorrect_data, text_issue, number_issue, date_issue, alphanumeric_issue, email_issue, duplicate_entries_issue, others_issue, null_issue, overall_correct_data, overall_incorrect_data ', 'INTO @data_quality_correct_data, @data_quality_incorrect_data, @text_issue, @number_issue, @date_issue, @alphanumeric_issue, @email_issue, @duplicate_entries_issue, @others_issue, @null_issue, @overall_correct_data, @overall_incorrect_data ',
    'FROM ', table_name, ' LIMIT 1');

    -- Prepare and execute the dynamic SQL
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
    SET others_issue = @others_issue;
    SET null_issue = @null_issue;
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



DROP PROCEDURE IF EXISTS DeleteProjectData;

DELIMITER $$

CREATE PROCEDURE DeleteProjectData(IN projectId INT)
BEGIN
    DECLARE projectName VARCHAR(255);
    DECLARE done INT DEFAULT 0;
    DECLARE tableName VARCHAR(255);
    DECLARE projectNameWithUnderscore VARCHAR(255);
    DECLARE cur CURSOR FOR
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name LIKE CONCAT(projectNameWithUnderscore, '%');
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    -- Retrieve the project name from the projects table
    SELECT name INTO projectName FROM projects WHERE id = projectId;

    SET projectNameWithUnderscore = CONCAT(projectName, '_%');
    -- Check if project name is found
    IF projectName IS NOT NULL THEN
        -- Open the cursor to find all tables starting with the project name
        OPEN cur;
        -- Loop through all the tables and drop them
        read_loop: LOOP
            FETCH cur INTO tableName;
            IF done THEN
                LEAVE read_loop;
            END IF;
            SET @dropStatement = CONCAT('DROP TABLE ', tableName);
            PREPARE stmt FROM @dropStatement;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END LOOP;

        CLOSE cur;
    END IF;

     -- Delete from projects where id matches
    DELETE FROM projects WHERE id = projectId;

      -- Delete from tables_list where name matches
    DELETE FROM tables_list WHERE project_id = projectId;

    -- Optionally, clean up temporary storage for IDs
    TRUNCATE TABLE temp_table_ids;
END $$

DELIMITER ;

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
        INSERT INTO error_log (error_time, error_message)
        VALUES (NOW(), CONCAT('SQLSTATE: ', @p1, ', Error: ', @p2));
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
END$$

DELIMITER ;

DROP TABLE IF EXISTS data_verification;
CREATE TABLE data_verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    table_id INT NOT NULL,
    table_name VARCHAR(255) NOT NULL,
    column_name  VARCHAR(255) NOT NULL,
    master_primary_key INT NOT NULL,
    ignore_flag INT NOT NULL DEFAULT 0,
    status VARCHAR(100) NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


DROP TABLE IF EXISTS `users`;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password  VARCHAR(255) NOT NULL,
    status VARCHAR(100) NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO users (username, password) VALUES('admin', 'admin');



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


DROP TABLE IF EXISTS `other_tables`;
CREATE TABLE other_tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    status VARCHAR(100) NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);