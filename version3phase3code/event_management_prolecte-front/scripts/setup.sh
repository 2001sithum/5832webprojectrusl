#!/bin/bash
# ==========================================================
#  Event Ticket Management Pro - Database Setup Script
# ==========================================================
# Use this script to:
# 1. Verify prerequisites (PHP, PDO extensions).
# 2. Create the data directory if needed.
# 3. Initialize the database schema via initialize_db.php.
# 4. Optionally insert sample data via insert_sample_data.php.
#
# Run this script from the project's 'scripts/' directory.
# Example: cd /path/to/event_management_pro/scripts && ./setup.sh
# ==========================================================

# --- Configuration & Colors ---
SCRIPT_NAME=$(basename "$0")
COLOR_ERROR='\033[0;31m'
COLOR_SUCCESS='\033[0;32m'
COLOR_WARNING='\033[1;33m'
COLOR_INFO='\033[0;34m'
COLOR_BOLD='\033[1m'
COLOR_RESET='\033[0m'

echoc() {
    local message="$1"
    local color="$2"
    local bold=${3:-""}
    [[ "$bold" == "bold" ]] && message="${COLOR_BOLD}${message}"
    echo -e "${color}${message}${COLOR_RESET}"
}
info() { echoc "INFO: $1" "${COLOR_INFO}"; }
warn() { echoc "WARN: $1" "${COLOR_WARNING}"; }
error() { echoc "ERROR: $1" "${COLOR_ERROR}" >&2; } # Send errors to stderr
success() { echoc "SUCCESS: $1" "${COLOR_SUCCESS}"; }
step() { echoc "\n>>> Step: $1" "${COLOR_BLUE}" "bold"; }

# --- Path Setup ---
SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
PROJECT_ROOT_DIR=$(cd "${SCRIPT_DIR}/.." && pwd)
echoc "Detected Project Root: ${PROJECT_ROOT_DIR}" "blue"

# Define key paths relative to project root
DATA_DIR="${PROJECT_ROOT_DIR}/data"
INCLUDES_DIR="${PROJECT_ROOT_DIR}/includes"
CONFIG_FILE="${INCLUDES_DIR}/config.php"
INIT_DB_PHP_SCRIPT="${SCRIPT_DIR}/initialize_db.php" # Relative to script dir now
INSERT_DATA_PHP_SCRIPT="${SCRIPT_DIR}/insert_sample_data.php"
MYSQL_DUMP_FILE="${SCRIPT_DIR}/mysql_schema.sql" # Keep dump in scripts dir

# --- Prerequisite Check ---
step "Checking prerequisites..."
command -v php >/dev/null 2>&1 || { error "'php' command not found. PHP CLI is required."; exit 1; }
PHP_VERSION_ID=$(php -r 'echo PHP_VERSION_ID;')
PHP_VERSION_STR=$(php -r 'echo PHP_VERSION;')
info "PHP Version: ${PHP_VERSION_STR} (ID: ${PHP_VERSION_ID})"
if [ "$PHP_VERSION_ID" -lt 80000 ]; then # Recommend PHP 8.0+
    warn "PHP version is older than 8.0. Project might work, but 8.0+ is recommended."
fi
php -m | grep -qi "pdo" || { error "PHP PDO extension is required but not loaded."; exit 1; }


# --- Read Configuration ---
step "Reading configuration from ${CONFIG_FILE}..."
if [ ! -f "$CONFIG_FILE" ]; then
    error "Configuration file not found at '${CONFIG_FILE}'. Cannot proceed."
    exit 1
fi

# Use PHP itself to reliably get config values, avoids complex bash parsing
get_php_config() {
    local key=$1
    php -r "require('${CONFIG_FILE}'); echo defined('${key}') ? ${key} : '';" 2>/dev/null
}

DB_TYPE=$(get_php_config 'DB_TYPE')
DB_HOST=$(get_php_config 'DB_HOST')
DB_NAME=$(get_php_config 'DB_NAME')
DB_USER=$(get_php_config 'DB_USER')
# DB_PASS is intentionally NOT read/displayed here for security. PHP scripts will use it directly.
SQLITE_PATH_RAW=$(get_php_config 'SQLITE_PATH') # Used for messaging

if [ -z "$DB_TYPE" ]; then
     error "DB_TYPE is not defined or couldn't be read from '$CONFIG_FILE'."
     exit 1
fi
info "Database Type configured: ${COLOR_BOLD}${DB_TYPE}${COLOR_RESET}"

# Further prerequisite checks based on DB_TYPE
REQUIRED_PDO_EXT=""
if [ "$DB_TYPE" == "mysql" ]; then
    REQUIRED_PDO_EXT="pdo_mysql"
    if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ]; then
         error "DB_NAME or DB_USER is missing in config file for MySQL setup."
         exit 1
    fi
    info "MySQL Config: Host='${DB_HOST}', DB='${DB_NAME}', User='${DB_USER}'"
elif [ "$DB_TYPE" == "sqlite" ]; then
    REQUIRED_PDO_EXT="pdo_sqlite"
     if [[ -z "$SQLITE_PATH_RAW" ]]; then
        error "SQLITE_PATH is not defined in config file for SQLite setup."
        exit 1
     fi
     # Use PHP to resolve the full path including __DIR__
     SQLITE_PATH_FULL=$(php -r "require('${CONFIG_FILE}'); echo SQLITE_PATH;")
     SQLITE_DIR=$(dirname "$SQLITE_PATH_FULL")
     info "SQLite Path: ${SQLITE_PATH_FULL}"
else
     error "Unsupported DB_TYPE ('${DB_TYPE}') configured in '$CONFIG_FILE'."
     exit 1
fi

# Check for required PDO driver
php -m | grep -iq "$REQUIRED_PDO_EXT" || { error "PHP PDO extension for ${DB_TYPE} (${REQUIRED_PDO_EXT}) required but not loaded."; exit 1; }
info "Required PDO extension (${REQUIRED_PDO_EXT}) found."
success "Prerequisites appear OK."


# --- Ensure Data Directory ---
step "Ensuring data directory exists and is writable..."
info "Data Directory: ${DATA_DIR}"
if [ ! -d "$DATA_DIR" ]; then
    mkdir -p "$DATA_DIR"
    if [ $? -ne 0 ]; then error "Failed to create data directory at '${DATA_DIR}'. Check permissions."; exit 1; fi
    success "Data directory created."
    # Add security .htaccess for Apache
    if [[ "$(command -v httpd || command -v apache2 || command -v nginx)" ]]; then # Basic check if common web servers exist
         if [ ! -f "$DATA_DIR/.htaccess" ]; then
             echo "Deny from all" > "$DATA_DIR/.htaccess"
             info "Added .htaccess to deny web access to data directory."
         fi
          if [ ! -f "$DATA_DIR/.gitignore" ]; then
             echo -e "# Ignore everything in data except .htaccess and .gitignore\n*\n!/.htaccess\n!/.gitignore" > "$DATA_DIR/.gitignore"
             info "Added .gitignore to data directory."
         fi
    fi
     # Set permissions (Owner RWX, Group RWX, Others ---). May need adjustment based on server setup.
     chmod 770 "$DATA_DIR"
     info "Set permissions for data directory (770). Make sure web server user has write access (e.g., add www-data to your group)."
else
    info "Data directory already exists."
    # Check writability if directory already exists
    if [ ! -w "$DATA_DIR" ]; then
        warn "Data directory exists but may not be writable by the current user. Permissions problems might occur."
    fi
fi

# Specific writability check for SQLite directory
if [ "$DB_TYPE" == "sqlite" ]; then
    info "Checking SQLite directory writability: ${SQLITE_DIR}"
    # Need to check if the directory exists AND is writable
    if [ ! -d "$SQLITE_DIR" ]; then
        warn "SQLite directory '${SQLITE_DIR}' does not exist yet. PHP script will attempt to create it."
    elif [ ! -w "$SQLITE_DIR" ]; then
        error "SQLite directory '${SQLITE_DIR}' exists but is NOT writable. PHP script will likely fail."
        error "Please ensure the web server user (e.g., www-data, apache, nginx) has write permissions on this directory."
        exit 1
    else
        info "SQLite directory appears writable."
    fi
fi


# --- Database Initialization ---
step "Running PHP script to initialize database schema..."
if [ ! -f "$INIT_DB_PHP_SCRIPT" ]; then error "Initialization script not found: $INIT_DB_PHP_SCRIPT"; exit 1; fi

# Execute the PHP script
php "$INIT_DB_PHP_SCRIPT"
INIT_EXIT_CODE=$?

# Check the exit code from the PHP script
if [ $INIT_EXIT_CODE -ne 0 ]; then
    error "Database initialization script FAILED! (Exit Code: ${INIT_EXIT_CODE})"
    error "Check output above and PHP error logs (e.g., ${DATA_DIR}/php_init_errors.log)."
    error "Verify configuration in '${CONFIG_FILE}' and database connectivity/permissions."
    exit 1;
fi
success "Database schema initialization script completed successfully."


# --- Optional: Insert Sample Data ---
step "Checking for sample data script..."
if [ -f "$INSERT_DATA_PHP_SCRIPT" ]; then
    read -p "$(echoc 'Do you want to insert sample data (admin/user, sample events)? (y/N): ' "${COLOR_INFO}")" insert_data_confirm
    if [[ "$insert_data_confirm" =~ ^[Yy]$ ]]; then
        info "Running PHP script to insert sample data..."
        php "$INSERT_DATA_PHP_SCRIPT"
        SAMPLE_EXIT_CODE=$?
        if [ $SAMPLE_EXIT_CODE -ne 0 ]; then
             error "Sample data insertion script FAILED! (Exit Code: ${SAMPLE_EXIT_CODE})"
             error "Check output above and PHP error logs (e.g., ${DATA_DIR}/php_sample_data_errors.log)."
             # Don't necessarily exit, schema might be okay, but warn heavily
             warn "Continuing setup despite sample data failure."
        else
            success "Sample data inserted successfully."
        fi
    else
        info "Skipping sample data insertion."
    fi
else
     warn "Sample data script not found at '${INSERT_DATA_PHP_SCRIPT}'. Skipping insertion."
fi


# --- Final Instructions ---
success "\n--- Project Setup Script Completed! ---"
echo ""
echoc "Next Steps:" "${COLOR_BLUE}" "bold"
echo "1. Ensure your web server (Apache/Nginx) is configured correctly:"
echoc "   - Set the ${COLOR_BOLD}DocumentRoot${COLOR_RESET} to: ${PROJECT_ROOT_DIR}/public" "${COLOR_YELLOW}"
echoc "   - Ensure URL rewriting is enabled (e.g., Apache's mod_rewrite with AllowOverride All, or Nginx try_files directive) if you plan to implement clean URLs later." "${COLOR_YELLOW}"
echo "2. If using SQLite:"
echoc "   - Verify the web server user (e.g., www-data, apache, nginx) has ${COLOR_BOLD}WRITE${COLOR_RESET} permissions on the ${COLOR_BOLD}directory${COLOR_RESET}:" "${COLOR_YELLOW}"
echoc "     ${SQLITE_DIR}" "${COLOR_YELLOW}"
echoc "   - And potentially on the database file itself (though directory permissions are usually key):" "${COLOR_YELLOW}"
echoc "     ${SQLITE_PATH_FULL}" "${COLOR_YELLOW}"
echo "3. If using MySQL:"
echoc "   - Ensure the database '${DB_NAME}' exists and the user '${DB_USER}' has appropriate privileges (SELECT, INSERT, UPDATE, DELETE, CREATE (if using init script))." "${COLOR_YELLOW}"
echoc "   - ${COLOR_BOLD}Crucially: Change the default password${COLOR_RESET} in '${CONFIG_FILE}' if you haven't already!" "${COLOR_RED}"
echo "4. Access the application via the URL configured for your web server."
echo "   (e.g., http://localhost/ , http://localhost/event_management_pro/public/ , http://your-vhost.local/)"
echo ""
echoc "${COLOR_BOLD}Default Logins (if sample data inserted):${COLOR_RESET}" "${COLOR_INFO}"
echoc "  Admin:   username=${COLOR_BOLD}admin${COLOR_RESET}   password=${COLOR_BOLD}adminpass${COLOR_RESET}" "${COLOR_INFO}"
echoc "  Regular: username=${COLOR_BOLD}testuser${COLOR_RESET} password=${COLOR_BOLD}userpass${COLOR_RESET}" "${COLOR_INFO}"
echo ""
exit 0
