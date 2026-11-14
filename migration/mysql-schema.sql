-- Run once on an empty MySQL/MariaDB database using phpMyAdmin.
-- Do not import over existing production tables.
CREATE TABLE q_categories (name VARCHAR(80) COLLATE utf8mb4_bin PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE q_products (
 id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL UNIQUE,
 category VARCHAR(80) COLLATE utf8mb4_bin NOT NULL,
 description TEXT NOT NULL,
 specifications TEXT NOT NULL,
 origin VARCHAR(100) NOT NULL DEFAULT 'India',
 packaging VARCHAR(300) NOT NULL DEFAULT 'On enquiry',
 moq VARCHAR(100) NOT NULL DEFAULT 'On enquiry',
 lead_time VARCHAR(100) NOT NULL DEFAULT 'On enquiry',
 price VARCHAR(100) NOT NULL DEFAULT 'On quotation',
 availability VARCHAR(150) NOT NULL DEFAULT 'Subject to confirmation',
 published TINYINT(1) NOT NULL DEFAULT 1,
 featured TINYINT(1) NOT NULL DEFAULT 0,
 local VARCHAR(1024) NOT NULL DEFAULT '',
 url VARCHAR(2048) NOT NULL DEFAULT '',
 source VARCHAR(2048) NOT NULL DEFAULT '',
 author VARCHAR(255) NOT NULL DEFAULT '',
 license VARCHAR(255) NOT NULL DEFAULT '',
 image_data LONGTEXT NULL,
 image_mime VARCHAR(100) NULL,
 CONSTRAINT q_products_category FOREIGN KEY (category) REFERENCES q_categories(name)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE q_enquiries (
 reference VARCHAR(40) PRIMARY KEY,created BIGINT NOT NULL,iphash CHAR(64) NOT NULL,payload LONGTEXT NOT NULL,status VARCHAR(30) NOT NULL DEFAULT 'New',
 INDEX q_enquiry_ip(iphash,created)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE q_login_attempts (iphash CHAR(64) NOT NULL,created BIGINT NOT NULL,INDEX q_login_ip(iphash,created)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
