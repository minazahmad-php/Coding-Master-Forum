#!/bin/bash

# Setup cron job for auto-update
# Run this script as root or with sudo

echo "Setting up auto-update cron job..."

# Add cron job to run auto-update every day at 2 AM
(crontab -l 2>/dev/null; echo "0 2 * * * cd /var/www/forum && php scripts/auto-update.php >> storage/logs/auto-update.log 2>&1") | crontab -

echo "Cron job added successfully!"
echo "Auto-update will run every day at 2 AM"
echo "Logs will be saved to storage/logs/auto-update.log"

# Make the script executable
chmod +x scripts/auto-update.php

echo "Setup complete!"