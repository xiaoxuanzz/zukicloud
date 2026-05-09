API endpoints
- action=upload (POST)
  - form-data: file, dest (optional)
- action=list (GET)
  - query: path (optional), recursive (optional, default false)
- action=file (GET)
  - query: path (required)

Security
- If api/keys.txt exists, requests must include header X-API-KEY or Authorization: Bearer <key>.
- For development, if keys.txt is absent, API allow requests (no auth).

Examples
- Upload a file:
  curl -F 'file=@path/to/file' -F 'dest=sub/dir' "http://yourhost/api/index.php?action=upload"
- List root:
  curl "http://yourhost/api/index.php?action=list&path=&recursive=false"
- List recursively:
  curl "http://yourhost/api/index.php?action=list&path=&recursive=true"
- Get a file:
  curl -L "http://yourhost/api/index.php?action=file&path=path/to/file.ext" -o file.ext
