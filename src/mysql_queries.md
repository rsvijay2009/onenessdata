### 1. CREATE TABLE FOR PROJECTS

```sql
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    status VARCHAR(100) NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 2. CREATE TABLE TO HOLD THE DATA OF DYNAMICALLY CREATED TABLES

```sql
CREATE TABLE tables_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    project_id INT DEFAULT NULL,
    status VARCHAR(100) NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 3. CREATE TABLE FOR DATATYPES

```sql
CREATE TABLE datatypes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description VARCHAR(255) NOT NULL,
    status VARCHAR(100) NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 4. INSERT DEFAULT DATATYPES

```sql
INSERT INTO datatypes (name, description)
VALUES 
    ('Text', 'Ex: ABCD ...'),
    ('Number', 'Ex: 0123 ...'),
    ('Date', 'Ex: DD:MM:YYYY ...'),
    ('Alphanumeric', 'Ex: AB12C5 ...'),
    ('Email', 'Ex: name@mail.com ...');
```

### 5. CREATE TABLE TO HOLD THE DATATYPES OF COLUMNS

```sql
CREATE TABLE table_datatypes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_id INT NOT NULL,
    table_name VARCHAR(255) NOT NULL,
    column_name  VARCHAR(255) NOT NULL,
    datatype_id INT NOT NULL,
    datatype VARCHAR(255) NOT NULL,
    data_quality INT NOT NULL DEFAULT 0,
    uniqueness INT NOT NULL DEFAULT 0,
    status VARCHAR(100) NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 6. CREATE TEMPORARY TABLE FOR STORED PROCEDURE

```sql
CREATE TABLE temp_table_ids (
    table_id INT
);
```

### 7. CREATE STORED PROCEDURE TO GET THE DATA FOR DASHBOARD

```sql
DELIMITER $$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetDashboardData`()
BEGIN
    DECLARE data_quality_correct_data INT DEFAULT 70;
    DECLARE data_quality_incorrect_data INT DEFAULT 30;
    DECLARE text_issue INT DEFAULT 10;
    DECLARE number_issue INT DEFAULT 20;
    DECLARE date_issue INT DEFAULT 30;
    DECLARE alphanumeric_issue INT DEFAULT 40;
    DECLARE email_issue INT DEFAULT 50;
    DECLARE duplicate_entries_issue INT DEFAULT 60;
    DECLARE others_issue INT DEFAULT 70;
    DECLARE overall_correct_data INT DEFAULT 10;
	DECLARE overall_incorrect_data INT DEFAULT 90;
    SELECT data_quality_correct_data AS 'data_quality_correct_data', 
    data_quality_incorrect_data AS 'data_quality_incorrect_data',
    text_issue AS 'text_issue',
    number_issue AS 'number_issue',
    date_issue AS 'date_issue',
    alphanumeric_issue AS 'alphanumeric_issue',
    email_issue AS 'email_issue',
    duplicate_entries_issue AS 'duplicate_entries_issue',
    others_issue AS 'others_issue',
    overall_correct_data AS 'overall_correct_data',
    overall_incorrect_data AS 'overall_incorrect_data';
    
END $$

DELIMITER ;
```

### 8. CREATE STORED PROCEDURE TO DELETE ALL THE PROJECT RELATED DATA

```sql
DELIMITER $$

CREATE PROCEDURE DeleteProjectData(IN projectId INT)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE _tableName VARCHAR(255);
    DECLARE _tableId INT;
    DECLARE cur CURSOR FOR SELECT id, name FROM tables_list WHERE project_id = projectId;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Cursor to fetch table names and IDs
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO _tableId, _tableName;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Check if table name is not null or empty
        IF _tableName IS NOT NULL AND _tableName <> '' THEN
            -- Dynamically drop table if it exists
            SET @s = CONCAT('DROP TABLE IF EXISTS ', _tableName);
            PREPARE stmt FROM @s;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            
            -- Collect table IDs for later use
            INSERT INTO temp_table_ids VALUES (_tableId);
        END IF;
    END LOOP;
    
    CLOSE cur;
    
    -- Delete from tables_list where project_id matches
    DELETE FROM tables_list WHERE project_id = projectId;
    
    -- Check if there are table IDs collected, then delete from table_datatypes
    IF (SELECT COUNT(*) FROM temp_table_ids) > 0 THEN
        DELETE FROM table_datatypes WHERE table_id IN (SELECT table_id FROM temp_table_ids);
    END IF;
    
    -- Delete from projects where id matches
    DELETE FROM projects WHERE id = projectId;

    -- Delete related data from data_verification table
    DELETE FROM data_verification WHERE project_id = projectId;
    
    -- Optionally, clean up temporary storage for IDs
    TRUNCATE TABLE temp_table_ids;
END$$

DELIMITER ;
```

### 9. CREATE STORED PROCEDURE TO DELETE ALL THE TABLE RELATED DATA

```sql
DELIMITER $$

CREATE PROCEDURE DropAndCleanUpTable(IN tableId INT)
BEGIN
    DECLARE _tableName VARCHAR(255);
    
    -- Select the table name from tables_list
    SELECT name INTO _tableName FROM tables_list WHERE id = tableId LIMIT 1;
    
    -- Check if the table name is not empty and drop the table if it exists
    IF _tableName IS NOT NULL AND _tableName <> '' THEN
        SET @dropQuery = CONCAT('DROP TABLE IF EXISTS ', _tableName);
        PREPARE stmt FROM @dropQuery;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
    
    -- Delete the entry from tables_list
    DELETE FROM tables_list WHERE id = tableId;
    
    -- Delete related data from table_datatypes
    DELETE FROM table_datatypes WHERE table_id = tableId;

    -- Delete related data from data_verification table
    DELETE FROM data_verification WHERE table_id = tableId;
END$$

DELIMITER ;
```

### 10. CREATE TABLE TO HOLD DATA CORRECTNESS

```sql
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
```

```sql
ALTER TABLE data_verification
CHANGE COLUMN ingore_flag ignore_flag  int NOT NULL DEFAULT '0';
```