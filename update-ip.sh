#!/usr/bin/env bash

# Exit on error
set -e

# Change directory to the script's directory (project root)
cd "$(dirname "$0")"

ENV_FILE=".env"
ENV_EXAMPLE=".env.example"
PORT="8000"
SERVE=false
PROTOCOL="http"

# Parse arguments
while [[ $# -gt 0 ]]; do
    case "$1" in
        --port)
            PORT="$2"
            shift 2
            ;;
        --serve)
            SERVE=true
            shift
            ;;
        --help|-h)
            echo "Usage: ./update-ip.sh [--port <port>] [--serve]"
            echo "  --port <number>   Specify backend port (default: 8000)"
            echo "  --serve           Start Laravel dev server bound to 0.0.0.0 after updating .env"
            exit 0
            ;;
        [0-9]*)
            PORT="$1"
            shift
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Ensure .env exists
if [ ! -f "$ENV_FILE" ]; then
    if [ -f "$ENV_EXAMPLE" ]; then
        echo "Creating .env from .env.example..."
        cp "$ENV_EXAMPLE" "$ENV_FILE"
    else
        echo "Error: Neither .env nor .env.example was found."
        exit 1
    fi
fi

# Detect local IP address across different platforms (Windows Git Bash, WSL, Linux, macOS)
get_local_ip() {
    local ip=""

    # 1. Windows Git Bash / Cygwin: Active route via default gateway (most reliable on Windows)
    if command -v route.exe >/dev/null 2>&1; then
        ip=$(route.exe print 0.0.0.0 2>/dev/null | grep -E '\s+0\.0\.0\.0\s+' | awk '{print $4}' | head -n 1 | tr -d '\r')
    fi

    # 2. Windows Git Bash: Fallback to ipconfig.exe
    if [ -z "$ip" ] && command -v ipconfig.exe >/dev/null 2>&1; then
        ip=$(ipconfig.exe | grep -i "IPv4" | grep -oE '[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+' | grep -v '^127\.' | head -n 1 | tr -d '\r')
    fi

    # 3. Linux (ip route to default gateway)
    if [ -z "$ip" ] && command -v ip >/dev/null 2>&1; then
        ip=$(ip route get 1.1.1.1 2>/dev/null | awk '{for(i=1;i<=NF;i++) if($i=="src") print $(i+1)}')
    fi

    # 4. Linux / WSL (hostname -I)
    if [ -z "$ip" ] && command -v hostname >/dev/null 2>&1; then
        ip=$(hostname -I 2>/dev/null | awk '{print $1}')
    fi

    # 5. macOS
    if [ -z "$ip" ] && command -v ipconfig >/dev/null 2>&1; then
        ip=$(ipconfig getifaddr en0 2>/dev/null || ipconfig getifaddr en1 2>/dev/null)
    fi

    echo "$ip"
}

IP=$(get_local_ip)

if [ -z "$IP" ]; then
    echo "Error: Could not automatically detect local IP address."
    echo "Please check your network connection."
    exit 1
fi

NEW_URL="${PROTOCOL}://${IP}:${PORT}"

echo "Detected Local IP : $IP"
echo "Target APP_URL    : $NEW_URL"

# Update APP_URL in .env
if grep -q "^APP_URL=" "$ENV_FILE"; then
    # Use temporary file to avoid in-place sed differences between Linux/macOS/Git Bash
    sed "s|^APP_URL=.*|APP_URL=${NEW_URL}|" "$ENV_FILE" > "${ENV_FILE}.tmp" && mv "${ENV_FILE}.tmp" "$ENV_FILE"
else
    echo "APP_URL=${NEW_URL}" >> "$ENV_FILE"
fi

echo "Successfully updated APP_URL in $ENV_FILE to $NEW_URL"

if [ "$SERVE" = true ]; then
    echo "Starting Laravel server on $IP:$PORT..."
    php artisan serve --host="$IP" --port="$PORT"
fi
