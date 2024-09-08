CREATE DEFINER=`root`@`localhost` PROCEDURE `GetDashboardData`(IN dashboard_table_name VARCHAR(64), IN table_name VARCHAR(64))
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
END