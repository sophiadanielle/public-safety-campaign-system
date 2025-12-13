-- Sample DB setup script (MySQL 8+)
-- Creates database and loads the initial schema.

CREATE DATABASE IF NOT EXISTS campaign_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campaign_db;

-- Core tables
SOURCE 001_initial_schema.sql;


