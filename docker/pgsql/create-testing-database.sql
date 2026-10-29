-- Creates the database used by the automated test suite (see phpunit.xml).
-- Runs once, when the PostgreSQL volume is initialised.
SELECT 'CREATE DATABASE taskflow_test'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'taskflow_test')\gexec
