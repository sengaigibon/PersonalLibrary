#!/bin/bash
set -e

# Fix permissions (runs as root)
chown -R postgres:postgres /var/lib/postgresql/data /var/run/postgresql
chmod 0750 /var/lib/postgresql/data

# If the data directory is empty, initialize the database
if [ -z "$(ls -A /var/lib/postgresql/data)" ]; then
    echo "Initializing database..."
    su -s /bin/bash postgres -c "initdb -D /var/lib/postgresql/data"
fi

# Allow PostgreSQL to listen on all interfaces
grep -q "^listen_addresses" /var/lib/postgresql/data/postgresql.conf \
    && sed -i "s/^listen_addresses.*/listen_addresses = '*'/" /var/lib/postgresql/data/postgresql.conf \
    || echo "listen_addresses = '*'" >> /var/lib/postgresql/data/postgresql.conf

# Allow connections from any host (Docker network) using md5
grep -q "^host all all 0.0.0.0/0" /var/lib/postgresql/data/pg_hba.conf \
    || echo "host all all 0.0.0.0/0 md5" >> /var/lib/postgresql/data/pg_hba.conf

# Remove stale postmaster.pid if it exists (left over from unclean shutdown)
if [ -f /var/lib/postgresql/data/postmaster.pid ]; then
    echo "Removing stale postmaster.pid..."
    rm -f /var/lib/postgresql/data/postmaster.pid
fi

# Start PostgreSQL in the background
su -s /bin/bash postgres -c "pg_ctl -D /var/lib/postgresql/data -l /var/lib/postgresql/data/logfile start" || {
    echo "pg_ctl failed to start. Log output:"
    cat /var/lib/postgresql/data/logfile
    exit 1
}

# Wait for PostgreSQL to start
until su -s /bin/bash postgres -c "pg_isready"; do
  echo "Waiting for PostgreSQL to start..."
  sleep 1
done

# Create the user if it doesn't exist
su -s /bin/bash postgres -c "psql -U postgres -tc \"SELECT 1 FROM pg_roles WHERE rolname = '${POSTGRES_USER}'\" | grep -q 1 || psql -U postgres -c \"CREATE USER ${POSTGRES_USER} WITH PASSWORD '${POSTGRES_PASSWORD}' SUPERUSER\""

# Create the database if it doesn't exist and assign ownership
su -s /bin/bash postgres -c "psql -U postgres -tc \"SELECT 1 FROM pg_database WHERE datname = '${POSTGRES_DB}'\" | grep -q 1 || psql -U postgres -c \"CREATE DATABASE ${POSTGRES_DB} OWNER ${POSTGRES_USER}\""

# Bring PostgreSQL to the foreground
su -s /bin/bash postgres -c "pg_ctl -D /var/lib/postgresql/data stop"
exec su -s /bin/bash postgres -c "postgres -D /var/lib/postgresql/data"
