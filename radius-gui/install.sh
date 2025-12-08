#!/bin/bash
#
# RADIUS Reporting GUI - Installation Script
#

set -e

echo "========================================="
echo "RADIUS Reporting GUI - Installation"
echo "========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "Please run as root or with sudo"
    exit 1
fi

# Check for required commands
command -v php >/dev/null 2>&1 || { echo "PHP is not installed. Aborting." >&2; exit 1; }
command -v mysql >/dev/null 2>&1 || { echo "MySQL client is not installed. Aborting." >&2; exit 1; }
command -v composer >/dev/null 2>&1 || { echo "Composer is not installed. Aborting." >&2; exit 1; }

echo "✓ All required commands found"
echo ""

# Get installation directory
read -p "Enter installation directory [/var/www/html/radius-gui]: " INSTALL_DIR
INSTALL_DIR=${INSTALL_DIR:-/var/www/html/radius-gui}

# Create directory if it doesn't exist
if [ ! -d "$INSTALL_DIR" ]; then
    mkdir -p "$INSTALL_DIR"
    echo "✓ Created directory: $INSTALL_DIR"
fi

# Copy files
echo "Copying files..."
cp -r * "$INSTALL_DIR/"
echo "✓ Files copied"

# Install dependencies
echo "Installing Composer dependencies..."
cd "$INSTALL_DIR"
composer install --no-dev --optimize-autoloader
echo "✓ Dependencies installed"

# Create .env file
if [ ! -f "$INSTALL_DIR/.env" ]; then
    cp "$INSTALL_DIR/.env.example" "$INSTALL_DIR/.env"
    echo "✓ Created .env file"
    echo "⚠ Please edit .env file with your database credentials"
fi

# Set permissions
echo "Setting permissions..."
chown -R www-data:www-data "$INSTALL_DIR"
chmod -R 755 "$INSTALL_DIR"
chmod -R 775 "$INSTALL_DIR/logs"
echo "✓ Permissions set"

# Database setup
read -p "Do you want to apply database migrations now? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    read -p "Enter MySQL host [localhost]: " DB_HOST
    DB_HOST=${DB_HOST:-localhost}

    read -p "Enter MySQL user [radius]: " DB_USER
    DB_USER=${DB_USER:-radius}

    read -s -p "Enter MySQL password: " DB_PASS
    echo

    read -p "Enter MySQL database [radius]: " DB_NAME
    DB_NAME=${DB_NAME:-radius}

    echo "Applying migrations..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$INSTALL_DIR/../sql/01-add-error-tracking-columns.sql"
    echo "✓ Migrations applied"
fi

# Create initial admin account
read -p "Do you want to create an initial admin account? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    read -p "Enter admin username [admin]: " ADMIN_USER
    ADMIN_USER=${ADMIN_USER:-admin}

    read -s -p "Enter admin password: " ADMIN_PASS
    echo

    # Hash password with PHP
    HASHED_PASS=$(php -r "echo password_hash('$ADMIN_PASS', PASSWORD_DEFAULT);")

    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" <<EOF
INSERT INTO operators (username, password, firstname, lastname, email, createusers)
VALUES ('$ADMIN_USER', '$HASHED_PASS', 'System', 'Administrator', 'admin@example.com', 1)
ON DUPLICATE KEY UPDATE password='$HASHED_PASS';
EOF

    echo "✓ Admin account created/updated"
fi

echo ""
echo "========================================="
echo "Installation Complete!"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Edit $INSTALL_DIR/.env with your database credentials"
echo "2. Configure your web server to point to: $INSTALL_DIR/public"
echo "3. Access the application at your configured URL"
echo ""
echo "Default login: $ADMIN_USER / [your password]"
echo ""
