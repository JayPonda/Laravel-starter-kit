-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `boilerplate`;
-- Create user and grant all privileges
CREATE USER IF NOT EXISTS 'boilerplate'@'%' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON `boilerplate`.* TO 'boilerplate'@'%';
FLUSH PRIVILEGES;
