# 系上課程預約系統

## Project Overview
一個提供線上課程預約服務的平台。
## Features
教師端可以自由訂定課程人數、網址及開課時間，而學生端可以自由註冊心儀的課程並提供取消、等待清單和課前寄信通知服務。同時可以處理多人同時搶課情境。
## Tech Stack
Laravel、PHP、MySQL與Redis。
## System Architecture
系統架構圖、模組關係、設計模式。(TODO)
## Database Design
ER Diagram、資料表關係。(TODO)
## Demo
Demo 網址(TODO)
## Installation
本專案使用 Laravel Sail 作為 Docker 開發環境，執行環境為 Laravel 13、PHP 8.5。

### Prerequisites
Docker
Docker Compose

### Setup

```bash
# 1. 複製環境設定檔
cp .env.example .env

# 2. 啟動 Docker containers
docker compose up -d

# 3. 安裝 Composer dependencies
docker compose exec laravel.test composer install

# 4. 產生 Laravel application key
docker compose exec laravel.test php artisan key:generate

# 5. 執行資料庫 migration
docker compose exec laravel.test php artisan migrate

# 6. 清除並快取 Laravel 設定
docker compose exec laravel.test php artisan optimize
```
## Technical Highlights
1. Database Read/Write Splitting：透過資料庫讀寫分離，將讀取與寫入請求分流，降低主資料庫負載。
2. Concurrency Control：結合 Database Transaction、Redis Distributed Lock，避免多人同時預約造成超額報名及 Race Condition。
3. Asynchronous Processing：使用 Laravel Queue 與 Job 將寄信通知、候補遞補等耗時操作非同步化，降低 HTTP Request Response Time。
4. Event-Driven Architecture：透過 Laravel Event、Listener 與 Observer 分離核心商業邏輯及附加行為，降低模組間耦合。
## Testing
預計使用phpunit來寫unit test和feature test，還有利用playwright來進行e2e test、Grafana k6進行concurrency test(TODO)