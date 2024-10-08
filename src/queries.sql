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
    table_type VARCHAR(255) DEFAULT NULL,
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


CREATE TABLE `debug_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

DROP TABLE IF EXISTS `temp_table_ids`;
CREATE TABLE `temp_table_ids` (
  `table_id` int DEFAULT NULL
);
