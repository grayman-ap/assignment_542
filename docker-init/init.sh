#!/bin/bash
# Runs once when the MySQL data volume is empty (first `docker compose up`).
# Creates both databases, applies the schema, and seeds the vulnerable DB
# (which stores plaintext passwords, as is typical of the starter app).
set -euo pipefail

echo "[init] creating databases ..."
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" <<SQL
CREATE DATABASE IF NOT EXISTS student_reg CHARACTER SET utf8mb4;
CREATE DATABASE IF NOT EXISTS student_reg_vuln CHARACTER SET utf8mb4;
GRANT ALL PRIVILEGES ON student_reg.* TO '${MYSQL_USER:-app}'@'%';
GRANT ALL PRIVILEGES ON student_reg_vuln.* TO '${MYSQL_USER:-app}'@'%';
FLUSH PRIVILEGES;
SQL

echo "[init] applying schema to hardened DB ..."
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" student_reg < /db/schema.sql

echo "[init] applying schema to starter DB ..."
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" student_reg_vuln < /db/schema.sql

echo "[init] seeding starter DB (plaintext passwords for demonstration) ..."
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" student_reg_vuln < /db/seed_vuln.sql

echo "[init] databases ready."
