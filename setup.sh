#!/bin/bash
# Setup script for the Liberu Social Network project.
#
# Provides installation options for Standalone, Docker, or Kubernetes deployments.
# Handles composer/npm installations with fallback logic and error checking.
#
# K8s deployments are compatible with the Liberu Control Panel:
#   https://github.com/liberu-control-panel/control-panel-laravel

set -e

# ---------------------------------------------------------------------------
# Colour helpers
# ---------------------------------------------------------------------------

RED='\e[91m'
GREEN='\e[92m'
YELLOW='\e[93m'
BLUE='\e[94m'
RESET='\e[39m'

print_message() { echo -e "${1}${2}${RESET}"; }
print_header()  { echo -e "\n==================================\n$1\n==================================\n"; }
print_error()   { print_message "$RED"    "ERROR: $1"; }
print_success() { print_message "$GREEN"  "OK: $1"; }
print_info()    { print_message "$BLUE"   "INFO: $1"; }
print_warning() { print_message "$YELLOW" "WARN: $1"; }

command_exists() { command -v "$1" >/dev/null 2>&1; }

# ---------------------------------------------------------------------------
# PHP version check (requires PHP 8.5+)
# ---------------------------------------------------------------------------

check_php_version() {
    command_exists php || { print_error "PHP is not installed."; return 1; }

    php_version="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
    required="8.5"

    if php -r "exit(version_compare(PHP_VERSION, '${required}', '>=') ? 0 : 1);"; then
        print_success "PHP ${php_version} (>= ${required} required)"
        return 0
    fi

    print_error "PHP ${php_version} detected — PHP ${required}+ is required."
    return 1
}

# ---------------------------------------------------------------------------
# Composer helpers
# ---------------------------------------------------------------------------

ensure_composer() {
    if command_exists composer; then
        print_success "Composer found"
        COMPOSER_CMD="composer"
        return 0
    fi

    print_warning "composer not found — downloading composer.phar..."
    command_exists curl || { print_error "curl required to download composer"; return 1; }
    command_exists php  || { print_error "php required"; return 1; }

    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --quiet
    php -r "unlink('composer-setup.php');"

    if [ -f "composer.phar" ]; then
        print_success "composer.phar downloaded"
        COMPOSER_CMD="php composer.phar"
        return 0
    fi

    print_error "Failed to download composer.phar"
    return 1
}

install_composer_dependencies() {
    print_header "COMPOSER INSTALL"

    if [ -d "vendor" ] && [ -f "vendor/autoload.php" ]; then
        print_info "Vendor directory already exists."
        read -r -p "Reinstall composer dependencies? (y/n) " reply
        echo
        if [[ ! "$reply" =~ ^[Yy]$ ]]; then
            print_success "Skipping composer install"
            return 0
        fi
    fi

    ensure_composer || { print_error "Cannot proceed without Composer"; return 1; }

    print_info "Running: $COMPOSER_CMD install"
    if $COMPOSER_CMD install --no-interaction --prefer-dist; then
        print_success "Composer dependencies installed"
        return 0
    fi

    print_error "Composer install failed"
    return 1
}

# ---------------------------------------------------------------------------
# NPM helpers
# ---------------------------------------------------------------------------

install_npm_dependencies() {
    print_header "NPM INSTALL"

    if [ -d "node_modules" ]; then
        print_info "node_modules already exists."
        read -r -p "Reinstall npm dependencies? (y/n) " reply
        echo
        if [[ ! "$reply" =~ ^[Yy]$ ]]; then
            print_success "Skipping npm install"
            return 0
        fi
    fi

    command_exists npm || { print_error "npm not installed — visit https://nodejs.org/"; return 1; }

    if npm install; then
        print_success "NPM dependencies installed"
        return 0
    fi

    print_error "NPM install failed"
    return 1
}

build_frontend_assets() {
    print_header "NPM BUILD"
    command_exists npm || { print_error "npm not installed — cannot build assets"; return 1; }

    if npm run build; then
        print_success "Frontend assets built"
        return 0
    fi

    print_error "NPM build failed"
    return 1
}

# ---------------------------------------------------------------------------
# Environment setup
# ---------------------------------------------------------------------------

setup_env() {
    if [ ! -f ".env" ]; then
        print_info "Copying .env.example to .env"
        cp .env.example .env
        print_warning "Please edit .env with your database and OAuth credentials before continuing."
        read -r -p "Press Enter to continue..."
        return 0
    fi
    print_info ".env already exists — skipping copy"
}

# ---------------------------------------------------------------------------
# Standalone installation
# ---------------------------------------------------------------------------

install_standalone() {
    print_header "STANDALONE INSTALLATION"

    check_php_version || { print_error "PHP version check failed"; exit 1; }

    echo "User : $(whoami)"
    echo "PHP  : $(php -r 'echo phpversion();')"
    echo ""

    read -r -p "Copy .env.example to .env? (y/n) " copy_env
    echo
    if [[ "$copy_env" =~ ^[Yy]$ ]]; then
        cp .env.example .env
        print_success ".env copied"
        read -r -p "Have you configured database credentials in .env? (y/n) " configured
        echo
        if [[ ! "$configured" =~ ^[Yy]$ ]]; then
            print_warning "Please configure .env then re-run this script."
            exit 0
        fi
    fi

    install_composer_dependencies || { print_error "Composer step failed"; exit 1; }
    install_npm_dependencies      || print_warning "NPM install failed — skipping"
    build_frontend_assets         || print_warning "NPM build failed — skipping"

    print_header "KEY GENERATE"
    php artisan key:generate
    print_success "Application key generated"

    print_header "DATABASE MIGRATE"
    if ! php artisan migrate:fresh; then
        print_error "Database migration failed"
        exit 1
    fi
    print_success "Database migrated"

    print_header "DATABASE SEED"
    if php artisan db:seed; then
        print_success "Database seeded"
    else
        print_warning "Database seeding failed — check seeders"
    fi

    print_header "MODULE AUTOLOAD"
    if php artisan module:dump-autoload 2>/dev/null; then
        print_success "Module autoload refreshed"
    else
        print_info "module:dump-autoload not available — skipping"
    fi

    print_header "RUNNING TESTS"
    if [ -f "vendor/bin/phpunit" ]; then
        ./vendor/bin/phpunit --no-coverage || print_warning "Some tests failed — review output"
    else
        print_warning "PHPUnit not found — skipping tests"
    fi

    print_header "OPTIMIZE"
    php artisan optimize:clear
    php artisan route:clear

    echo ""
    print_success "Installation complete!"
    echo ""

    read -r -p "Start the development server now? (y/n) " start_server
    echo
    if [[ "$start_server" =~ ^[Yy]$ ]]; then
        php artisan serve
    else
        print_info "Start later with: php artisan serve"
    fi
}

# ---------------------------------------------------------------------------
# Docker installation
# ---------------------------------------------------------------------------

install_docker() {
    print_header "DOCKER INSTALLATION"

    command_exists docker || {
        print_error "Docker not installed — visit https://docs.docker.com/get-docker/"
        exit 1
    }
    print_success "Docker found"

    if ! command_exists docker-compose && ! docker compose version >/dev/null 2>&1; then
        print_error "Docker Compose not available — visit https://docs.docker.com/compose/install/"
        exit 1
    fi
    print_success "Docker Compose available"

    setup_env

    print_info "Building and starting Docker containers..."
    if command_exists docker-compose; then
        docker-compose up -d --build
    else
        docker compose up -d --build
    fi

    print_success "Docker containers started — app available at http://localhost:8000"
}

# ---------------------------------------------------------------------------
# Kubernetes installation
# ---------------------------------------------------------------------------

install_kubernetes() {
    print_header "KUBERNETES INSTALLATION"

    command_exists kubectl || {
        print_error "kubectl not installed — visit https://kubernetes.io/docs/tasks/tools/"
        exit 1
    }
    print_success "kubectl found"

    K8S_DIR=""
    [ -d "k8s" ]        && K8S_DIR="k8s"
    [ -d "kubernetes" ] && K8S_DIR="${K8S_DIR:-kubernetes}"

    if [ -z "$K8S_DIR" ]; then
        print_error "No k8s/ or kubernetes/ directory found."
        exit 1
    fi

    print_info "Using Kubernetes configs from: $K8S_DIR/"

    # Optionally validate manifests before applying
    if [ -f "$K8S_DIR/validate.sh" ]; then
        read -r -p "Validate manifests before deploying? (y/n) " do_validate
        echo
        if [[ "$do_validate" =~ ^[Yy]$ ]]; then
            print_info "Running manifest validation..."
            if bash "$K8S_DIR/validate.sh"; then
                print_success "Manifests validated"
            else
                print_error "Manifest validation failed — fix errors before deploying"
                exit 1
            fi
        fi
    fi

    setup_env

    echo ""
    echo "Select deployment environment:"
    echo "  1) production (default)"
    echo "  2) development"
    read -r -p "Choice (1-2, default 1): " env_choice
    case "${env_choice:-1}" in
        2) K8S_ENV="development" ;;
        *) K8S_ENV="production" ;;
    esac

    print_info "Deploying to: $K8S_ENV"

    if [ -f "$K8S_DIR/deploy.sh" ]; then
        print_info "Using $K8S_DIR/deploy.sh"
        ENVIRONMENT="$K8S_ENV" bash "$K8S_DIR/deploy.sh"
    elif [ -d "$K8S_DIR/overlays/$K8S_ENV" ]; then
        print_info "Applying kustomize overlay: $K8S_DIR/overlays/$K8S_ENV"
        kubectl apply -k "$K8S_DIR/overlays/$K8S_ENV"
        print_success "Kubernetes resources applied"
        print_info "Check status: kubectl get pods -n social-network-laravel"
    else
        print_info "Applying all manifests in $K8S_DIR/"
        kubectl apply -f "$K8S_DIR/"
        print_success "Kubernetes resources applied"
    fi
}

# ---------------------------------------------------------------------------
# Main menu
# ---------------------------------------------------------------------------

main() {
    clear
    print_header "LIBERU SOCIAL NETWORK - INSTALLER"

    echo "Select installation type:"
    echo ""
    echo "  1) Standalone (local development/production)"
    echo "  2) Docker"
    echo "  3) Kubernetes"
    echo "  4) Validate K8s manifests only"
    echo "  5) Exit"
    echo ""

    while true; do
        read -r -p "Choice (1-5): " choice
        case "$choice" in
            1) install_standalone; break ;;
            2) install_docker;     break ;;
            3) install_kubernetes; break ;;
            4)
                K8S_DIR="k8s"
                [ -f "$K8S_DIR/validate.sh" ] || K8S_DIR="kubernetes"
                if [ -f "$K8S_DIR/validate.sh" ]; then
                    bash "$K8S_DIR/validate.sh"
                else
                    print_error "No k8s/validate.sh found"
                fi
                break
                ;;
            5) print_info "Cancelled"; exit 0 ;;
            *) print_warning "Enter 1, 2, 3, 4, or 5." ;;
        esac
    done
}

main
