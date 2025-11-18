#!/bin/bash
# Rebuild and test script for SkanPolis
# This script rebuilds containers and reinitializes the database

set -e

echo "========================================="
echo "SkanPolis - Rebuild and Test"
echo "========================================="

echo ""
echo "Step 1: Stopping existing containers..."
docker-compose down

echo ""
echo "Step 2: Removing old database volume (to reinitialize with new passwords)..."
docker volume rm skanpolis_mysql_data 2>/dev/null || echo "Volume already removed or doesn't exist"

echo ""
echo "Step 3: Rebuilding PHP container (with new session config)..."
docker-compose build php

echo ""
echo "Step 4: Starting all containers..."
docker-compose up -d

echo ""
echo "Step 5: Waiting for services to be ready..."
sleep 10

echo ""
echo "Step 6: Checking container status..."
docker-compose ps

echo ""
echo "Step 7: Testing database connection..."
docker exec skanpolis_mysql mysql -u insurance_user -p"$(cat secrets/db_password.txt)" insurance_db -e "SELECT COUNT(*) as user_count FROM User;" 2>/dev/null || echo "Waiting for MySQL to fully initialize..."

echo ""
echo "========================================="
echo "Rebuild complete!"
echo "========================================="
echo ""
echo "Test credentials:"
echo "  Admin: admin@skanpolis.pl / Admin123!@#"
echo "  User:  user@example.pl / User123!@#"
echo ""
echo "Application URL: http://localhost:8080"
echo ""
