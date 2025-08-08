

git clone git@github.com:tohwoan-choi/egcharge.git

- Nginx 설정 파일 수정
        sudo nano /etc/nginx/sites-available/default
        # 설정 테스트
            sudo nginx -t

            # Nginx 재시작
            sudo systemctl restart nginx



            # HTTP 설정 (egcharge.com 추가)
            server {
                listen 80;
                server_name egcharge.com www.egcharge.com;

                location = /robots.txt {
                    root /var/www/egcharge/public;  # 실제 robots.txt가 있는 디렉터리
                    default_type "text/plain; charset=UTF-8";
                }

                location / {
                    return 301 https://$server_name$request_uri;
                }
            }
            # HTTPS 설정
            server {
                listen 443 ssl http2;
                server_name egcharge.com www.egcharge.com;

                # SSL 인증서 설정
                ssl_certificate /etc/ssl/egcharge.com_202508070E841.all.crt.pem;
                ssl_certificate_key /etc/ssl/egcharge.com_202508070E841.key.pem;

                # SSL 프로토콜 및 암호화 설정
                ssl_protocols TLSv1.2 TLSv1.3;
                ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-CHACHA20-POLY1305;
                ssl_prefer_server_ciphers off;

                # SSL 세션 설정
                ssl_session_cache shared:SSL:1m;
                ssl_session_timeout 1d;
                ssl_session_tickets off;

                # 보안 헤더
                add_header Strict-Transport-Security "max-age=63072000" always;

                # 웹사이트 루트 디렉토리
                root /var/www/egcharge/public;
                index index.html index.php;
                access_log /var/log/nginx/egcharge_access.log;
                error_log /var/log/nginx/egcharge_error.log;

                location = /robots.txt {
                    root /var/www/egcharge/public;
                    default_type "text/plain; charset=UTF-8";
                }
                location / {
                    try_files $uri $uri/ =404;
                }
                location ~ \.php$ {
                    include snippets/fastcgi-php.conf;
                    fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
                    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
                    include fastcgi_params;
                }

            }

            # 충전기 정보 kepco 오픈 API
            # 공공 데이터 포털
            # https://www.data.go.kr/index.do

            curl --include --request GET 'http://openapi.kepco.co.kr/service/EvInfoServiceV2/getEvSearchList?serviceKey=YD4F7fM0S3trjigSjUMpEtEU1kD6deSc3zED0kM4zEvOGjw6QhxIX6%2FmAJLY2esmuQOrf%2BPf2GmGaLLcBy5BvQ%3D%3D&pageNo=1&numOfRows=10'
            curl --include --request GET 'http://openapi.kepco.co.kr/service/EvInfoServiceV2/getEvSearchList?serviceKey=YD4F7fM0S3trjigSjUMpEtEU1kD6deSc3zED0kM4zEvOGjw6QhxIX6/mAJLY2esmuQOrf+Pf2GmGaLLcBy5BvQ==&pageNo=1&numOfRows=10'

            YD4F7fM0S3trjigSjUMpEtEU1kD6deSc3zED0kM4zEvOGjw6QhxIX6/mAJLY2esmuQOrf+Pf2GmGaLLcBy5BvQ==