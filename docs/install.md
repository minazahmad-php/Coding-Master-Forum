# Installation Guide

## Requirements

- PHP 8.0 or higher
- SQLite extension enabled
- Web server (Apache/Nginx)
- mod_rewrite enabled (for Apache)

## Step-by-Step Installation

### 1. Download and Extract

Download the forum package and extract it to your web server directory.

### 2. Set Permissions

Make sure the following directories are writable by the web server:

```bash
chmod 755 storage
chmod 755 public/uploads
chmod 755 storage/logs
chmod 755 storage/cache