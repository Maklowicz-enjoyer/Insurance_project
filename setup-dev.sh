#!/bin/bash

# ============================================
# SkanPolis - Development Setup Script
# ============================================
# This script automates the initial setup
# of the SkanPolis development environment
# ============================================

set -e  # Exit on error

echo "=========================================="
echo "SkanPolis - Development Setup"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# ============================================
# 1. Check prerequisites
# ============================================
echo -e "${YELLOW}[1/7] Checking prerequisites...${NC}"

if ! command -v docker &> /dev/null; then
    echo -e "${RED}ERROR: Docker is not installed!${NC}"
    echo "Please install Docker: https://docs.docker.com/get-docker/"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}ERROR: Docker Compose is not installed!${NC}"
    echo "Please install Docker Compose: https://docs.docker.com/compose/install/"
    exit 1
fi

echo -e "${GREEN}✓ Docker and Docker Compose are installed${NC}"

# ============================================
# 2. Create .env file from template
# ============================================
echo -e "${YELLOW}[2/7] Creating .env file...${NC}"

if [ ! -f .env ]; then
    cp .env.example .env
    echo -e "${GREEN}✓ Created .env file from template${NC}"
    echo -e "${YELLOW}⚠ IMPORTANT: Please edit .env and update the passwords!${NC}"
else
    echo -e "${YELLOW}⚠ .env file already exists, skipping...${NC}"
fi

# ============================================
# 3. Generate secrets
# ============================================
echo -e "${YELLOW}[3/7] Generating secrets...${NC}"

mkdir -p secrets

# Generate DB password if not exists
if [ ! -f secrets/db_password.txt ]; then
    openssl rand -base64 32 > secrets/db_password.txt
    echo -e "${GREEN}✓ Generated database password${NC}"

    # Update .env with generated password
    DB_PASS=$(cat secrets/db_password.txt)
    if [ -f .env ]; then
        sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" .env
        sed -i "s/MYSQL_ROOT_PASSWORD=.*/MYSQL_ROOT_PASSWORD=$DB_PASS/" .env
    fi
else
    echo -e "${YELLOW}⚠ DB password already exists${NC}"
fi

# Generate session secret if not exists
if [ ! -f secrets/session_secret.txt ]; then
    openssl rand -base64 64 > secrets/session_secret.txt
    echo -e "${GREEN}✓ Generated session secret${NC}"

    # Update .env with generated secret
    SESSION_SECRET=$(cat secrets/session_secret.txt)
    if [ -f .env ]; then
        sed -i "s/SESSION_SECRET=.*/SESSION_SECRET=$SESSION_SECRET/" .env
    fi
else
    echo -e "${YELLOW}⚠ Session secret already exists${NC}"
fi

# Generate Redis password if not exists
if [ ! -f secrets/redis_password.txt ]; then
    openssl rand -base64 32 > secrets/redis_password.txt
    echo -e "${GREEN}✓ Generated Redis password${NC}"

    # Update .env with generated password
    REDIS_PASS=$(cat secrets/redis_password.txt)
    if [ -f .env ]; then
        sed -i "s/REDIS_PASSWORD=.*/REDIS_PASSWORD=$REDIS_PASS/" .env
    fi
else
    echo -e "${YELLOW}⚠ Redis password already exists${NC}"
fi

echo -e "${GREEN}✓ All secrets generated${NC}"

# ============================================
# 4. Stop existing containers (if any)
# ============================================
echo -e "${YELLOW}[4/7] Stopping existing containers...${NC}"

docker-compose down -v 2>/dev/null || true
echo -e "${GREEN}✓ Stopped existing containers${NC}"

# ============================================
# 5. Build Docker images
# ============================================
echo -e "${YELLOW}[5/7] Building Docker images...${NC}"

docker-compose build --no-cache
echo -e "${GREEN}✓ Docker images built successfully${NC}"

# ============================================
# 6. Start containers
# ============================================
echo -e "${YELLOW}[6/7] Starting containers...${NC}"

docker-compose up -d
echo -e "${GREEN}✓ Containers started${NC}"

# ============================================
# 7. Wait for services to be ready
# ============================================
echo -e "${YELLOW}[7/7] Waiting for services to be ready...${NC}"

# Wait for MySQL
echo -n "Waiting for MySQL..."
for i in {1..30}; do
    if docker-compose exec -T mysql mysqladmin ping -h localhost --silent 2>/dev/null; then
        echo -e " ${GREEN}✓${NC}"
        break
    fi
    echo -n "."
    sleep 2
done

# Wait for Redis
echo -n "Waiting for Redis..."
for i in {1..30}; do
    if docker-compose exec -T redis redis-cli ping 2>/dev/null | grep -q PONG; then
        echo -e " ${GREEN}✓${NC}"
        break
    fi
    echo -n "."
    sleep 2
done

# Wait for PHP-FPM
echo -n "Waiting for PHP-FPM..."
sleep 5
echo -e " ${GREEN}✓${NC}"

# ============================================
# 8. Display summary
# ============================================
echo ""
echo "=========================================="
echo -e "${GREEN}✓ Setup completed successfully!${NC}"
echo "=========================================="
echo ""
echo "Services are running at:"
echo -e "  • Application:    ${GREEN}http://localhost:8080${NC}"
echo -e "  • PhpMyAdmin:     ${GREEN}http://localhost:8081${NC} (use --profile dev)"
echo -e "  • MySQL:          ${GREEN}localhost:3306${NC}"
echo -e "  • Redis:          ${GREEN}localhost:6379${NC}"
echo ""
echo "Default credentials:"
echo -e "  • Admin:   ${GREEN}admin@skanpolis.pl${NC} / ${GREEN}Admin123!@#${NC}"
echo -e "  • User:    ${GREEN}user@example.pl${NC} / ${GREEN}User123!@#${NC}"
echo ""
echo "Useful commands:"
echo "  • View logs:      docker-compose logs -f"
echo "  • Stop:           docker-compose down"
echo "  • Restart:        docker-compose restart"
echo "  • Shell (PHP):    docker-compose exec php bash"
echo "  • Shell (MySQL):  docker-compose exec mysql mysql -u root -p"
echo ""
echo -e "${YELLOW}⚠ Remember to change default passwords in production!${NC}"
echo "=========================================="
