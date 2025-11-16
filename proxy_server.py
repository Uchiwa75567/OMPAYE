#!/usr/bin/env python3
"""
Proxy simple pour rediriger le trafic du port 8081 vers 8888
"""

import http.server
import socketserver
import urllib.request
import urllib.parse

class ProxyHandler(http.server.SimpleHTTPRequestHandler):
    def do_GET(self):
        # Rediriger vers le serveur Laravel sur port 8888
        target_url = f"http://localhost:8083{self.path}"
        try:
            response = urllib.request.urlopen(target_url)
            self.send_response(response.getcode())
            
            # Copier les headers
            for header, value in response.headers.items():
                self.send_header(header, value)
            
            self.end_headers()
            
            # Copier le contenu
            content = response.read()
            self.wfile.write(content)
            
        except Exception as e:
            self.send_error(500, f"Proxy Error: {str(e)}")
    
    def do_POST(self):
        # Gérer les requêtes POST aussi
        target_url = f"http://localhost:8083{self.path}"
        content_length = int(self.headers.get('Content-Length', 0))
        post_data = self.rfile.read(content_length)
        
        try:
            req = urllib.request.Request(target_url, data=post_data, headers=self.headers, method='POST')
            response = urllib.request.urlopen(req)
            
            self.send_response(response.getcode())
            
            # Copier les headers
            for header, value in response.headers.items():
                self.send_header(header, value)
            
            self.end_headers()
            
            # Copier le contenu
            content = response.read()
            self.wfile.write(content)
            
        except Exception as e:
            self.send_error(500, f"Proxy Error: {str(e)}")

if __name__ == "__main__":
    PORT = 3000
    Handler = ProxyHandler
    
    with socketserver.TCPServer(("", PORT), Handler) as httpd:
        print(f"🌐 Proxy démarré sur http://localhost:{PORT}")
        print(f"🔄 Redirection vers http://localhost:8083")
        print("⏹️  Ctrl+C pour arrêter")
        httpd.serve_forever()