
# TenStor — Tenable.sc Publishing Receiver

---

**EN (English)**

**TenStor** is a tiny PHP webservice to receive **Publish Report** from **Tenable.sc** via **HTTP POST multipart/form-data**.
The incoming PDF will be:
1) Stored into a persistent volume,
2) Parsed on page 1 to find `(Scan: ...)` pattern,
3) Renamed to `YY-MM-DD-<extracted-label>.pdf`.

> By default it runs as a Docker container on **port 8085**.

## Features
- Basic Auth (username/password) via environment variables
- Auto rename based on PDF content (regex `(Scan:\s*(...))`)
- Logging to file (volume) and container stdout
- Upload limits & tuning via `php.ini`
- Simple healthcheck endpoint (`GET /?health=1`)

## Architecture
- Base image: `php:8.3-apache` + `smalot/pdfparser`
- Endpoint: `POST /` with field **reportContent** (multipart)
- Volumes:
  - `/tenable/hasil` → uploaded/renamed files
  - `/var/log` → `report-tenable.log`

## Quick Start

### 1) Prerequisites
- Docker + Docker Compose
- Port 8085 open and available from server that will be used

### 2) Clone & Configure
```bash
git clone https://github.com/tint-us/tenstor.git
cd tenstor
cp .env.example .env
# edit .env → TENSTOR_USER / TENSTOR_PASSWORD
```

### 3) Run
```bash
docker compose up -d --build
```

### 4) Manual Test
```bash
curl -u "$TENSTOR_USER:$TENSTOR_PASSWORD"       -F "reportContent=@/path/to/report.pdf"       http://YOUR_HOST:8085/
```

### 5) Configure on Tenable.sc
1. **System → Publishing Sites → New**
2. **Type**: HTTP Post
3. **URI**: `http://YOUR_HOST:8085/`
4. **Authentication**: Password (use the same creds as `.env`)
5. **Publish** from **Reporting → Report Results → Publish**

## Configuration

`.env`:
```env
TENSTOR_USER=changeme
TENSTOR_PASSWORD=changeme
TENSTOR_UPLOAD_DIR=/tenable/hasil
TENSTOR_LOG_FILE=/var/log/report-tenable.log
PHP_TZ=Asia/Jakarta
```

`docker/php.ini` (default):
```ini
file_uploads = On
upload_max_filesize = 100M
post_max_size = 110M
memory_limit = 256M
max_execution_time = 120
date.timezone = Asia/Jakarta
```

## File Naming Behavior
- Regex: `\(Scan:\s*(.*?)\)` from the first PDF page.
- Final name: `YY-MM-DD-<label>.pdf`.
- If pattern not found → file still stored (logged), no rename.

## Security & Best Practices
- Restrict port 8085 to Tenable.sc IPs (firewall / security group).
- Use HTTPS via reverse proxy (optional).
- Rotate logs under `data/log/` volume.

## Troubleshooting
- **401 Unauthorized**: No creds sent → ensure Authentication: Password is used.
- **403 Forbidden**: Bad username/password → match `.env` values.
- **400 Invalid upload**: Form field must be named `reportContent`.
- **Rename failed**: Check volume permissions or filename collisions.
- **Pattern not found**: Ensure first PDF page contains `(Scan: ...)` text.

## License

No-Resale Community License (NRL-1.0)

Permissions:
- Use (personal & commercial), modify, and distribute the Software and Derivatives for no charge.
- Hosting/SaaS using the Software is allowed.
Conditions:
- No Resale: You may not Sell the Software or Derivatives (i.e., provide copies, access to copies, or substantially similar software, in source or executable form, for a fee) unless you obtain a Commercial License from the Licensor.
- Attribution: On distribution, keep copyright notices, this license text, and add a notice of changes. Include a URL to the original source repository.
- Trademarks: No rights to Licensor’s names or logos.
Disclaimers:
- The Software is provided “AS IS”, without warranties or conditions of any kind.
- No liability: Licensor is not liable for any damages arising from use of the Software.
(Optional) Patent grant: Licensor grants a non-exclusive, royalty-free patent license to make, use, and distribute the Software; contributors grant the same for their contributions.

©2025 tnt.my.id. Commercial licensing available: tintus.ardi@gmail.com.
