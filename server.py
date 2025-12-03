from http.server import HTTPServer, BaseHTTPRequestHandler
import json
import os
import re
import time

PORT = 3001
DATA_FILE = 'urls.json'
HTML_FILE = 'index.html'

if not os.path.exists(DATA_FILE):
    with open(DATA_FILE, 'w') as f:
        json.dump([], f)

class Handler(BaseHTTPRequestHandler):
    def _headers(self, status=200, content_type='application/json'):
        self.send_response(status)
        self.send_header('Content-Type', f'{content_type}; charset=utf-8')
        self.send_header('X-Content-Type-Options', 'nosniff')
        self.send_header('X-Frame-Options', 'DENY')
        self.end_headers()

    def log_message(self, format, *args):
        print(f"[{time.strftime('%Y-%m-%d %H:%M:%S')}] {args[0]}")

    def do_GET(self):
        if self.path == '/':
            self._headers(200, 'text/html')
            with open(HTML_FILE, 'rb') as f:
                self.wfile.write(f.read())
            return

        if self.path == '/api/urls':
            self._headers(200)
            with open(DATA_FILE, 'rb') as f:
                self.wfile.write(f.read())
            return

        self._headers(404)
        self.wfile.write(b'{"error": "Not Found"}')

    def do_POST(self):
        if self.path == '/api/urls':
            try:
                length = int(self.headers.get('Content-Length', 0))
                body = json.loads(self.rfile.read(length))
                
                name = str(body.get('name', '')).strip()[:100]
                url = str(body.get('url', '')).strip()[:500]
                category = body.get('category', 'other')

                if not name or not url:
                    self._headers(400)
                    self.wfile.write('{"error": "name, url 필수"}'.encode())
                    return

                if not re.match(r'^https?://.+', url):
                    self._headers(400)
                    self.wfile.write('{"error": "올바른 URL 형식 아님"}'.encode())
                    return

                if category not in ['production', 'development', 'staging', 'other']:
                    category = 'other'

                with open(DATA_FILE, 'r') as f:
                    urls = json.load(f)

                new_url = {
                    'id': int(time.time() * 1000),
                    'name': name,
                    'url': url,
                    'category': category
                }
                urls.append(new_url)

                with open(DATA_FILE, 'w') as f:
                    json.dump(urls, f, ensure_ascii=False, indent=2)

                self._headers(201)
                self.wfile.write(json.dumps(new_url, ensure_ascii=False).encode())

            except Exception as e:
                self._headers(400)
                self.wfile.write(f'{{"error": "{str(e)}"}}'.encode())
            return

        self._headers(404)
        self.wfile.write(b'{"error": "Not Found"}')

if __name__ == '__main__':
    server = HTTPServer(('0.0.0.0', PORT), Handler)
    print(f'🚀 Server running at http://localhost:{PORT}')
    server.serve_forever()
