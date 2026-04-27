# How to Run This Project

## One-time setup (first time only)

### 1. Install PHP MySQL extension
```bash
sudo dnf install -y php-pdo php-mysqlnd
```

### 2. Create the MySQL database
```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS socialmedia;"
```

### 3. Run migrations + seed
```bash
php artisan migrate --seed
```

---

## Every time you run the project (3 terminals)

**Terminal 1 — Laravel server:**
```bash
php artisan serve
```

**Terminal 2 — Vite (hot reload):**
```bash
npm run dev
```

**Terminal 3 — Reverb WebSocket server:**
```bash
php artisan reverb:start
```

Then open: http://localhost:8000

---

## Test accounts (after seeding)
| Email | Password | Username |
|-------|----------|----------|
| alice@example.com | password | alice |
| bob@example.com | password | bob |
| carol@example.com | password | carol |

---

## Features
- Register / Login
- Create posts (text + optional image)
- Like & comment on posts
- Follow / Unfollow users
- User profiles with bio, avatar, cover photo
- Real-time notifications (Laravel Reverb)
- Real-time direct messages (Laravel Reverb)
- News feed showing posts from people you follow
