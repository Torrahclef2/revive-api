# Revive API Setup Guide

## Project Overview
Revive is a Laravel 12 REST API backend for a Flutter mobile app focused on Christian prayer and Bible study community.

## Environment Setup

### Requirements
- PHP 8.2+ (currently PHP 8.2)
- MySQL 8.0+
- Redis 6.0+
- Composer 2.0+

### Installation Steps

1. **Install Dependencies**
   ```bash
   composer install
   ```

2. **Environment Configuration**
   Update `.env` file with:
   - `DB_DATABASE=revive`
   - `DB_USERNAME=root`
   - `DB_PASSWORD=your_password`
   - `REDIS_HOST=127.0.0.1`
   - `REDIS_PORT=6379`
   - `FRONTEND_URL=http://localhost:3000` (Flutter app URL)

3. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

4. **Run Migrations**
   ```bash
   php artisan migrate
   ```

5. **Start Development Server**
   ```bash
   php artisan serve
   ```

## Installed Packages

- **laravel/sanctum** (v4.3): API token authentication for mobile clients
- **spatie/laravel-permission** (v6.25): Role and permission management
- **intervention/image** (v3.11): Image processing for avatars
- **laravel/reverb** (v1.10): WebSocket server for real-time features
- **predis/predis** (v3.4): Redis client for queues and caching

## Architecture

### Folder Structure
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/           # Authentication controllers
│   │   └── Api/            # API resource controllers
│   ├── Requests/           # Form request validation
│   └── Resources/          # JSON transformation resources
├── Services/               # Business logic layer
├── Events/                 # Application events
├── Listeners/              # Event handlers
├── Jobs/                   # Queued jobs
└── Notifications/          # Notification classes

routes/
├── api.php                 # API routes (v1 prefix)
└── web.php                 # Web routes
```

### Configuration Files
- `config/cors.php`: CORS settings (allows all origins in development)
- `config/sanctum.php`: API authentication configuration
- `config/permission.php`: Spatie permissions configuration

## API Routes

Base URL: `http://localhost:8000/api/v1`

### Public Routes
- `POST /auth/register` - Register new user
- `POST /auth/login` - Login user
- `POST /auth/forgot-password` - Request password reset
- `POST /auth/reset-password` - Reset password

### Protected Routes (require Authorization header)
- `POST /auth/logout` - Logout user
- `GET /me` - Get current user info
- `PUT /me` - Update current user info

**Authorization Header Format:**
```
Authorization: Bearer {access_token}
```

## Development Notes

### For Mobile App Integration
- Mobile clients authenticate using Bearer tokens (no sessions/cookies)
- CORS is configured to allow requests from Flutter app
- All API responses are JSON

### Next Steps
1. Create data models (User, Prayer, Study, etc.)
2. Create migrations for database tables
3. Implement authentication controllers
4. Set up WebSocket handlers for real-time features
5. Configure queues and notifications

## Commands Reference

```bash
# Create a new model with migration
php artisan make:model ModelName -m

# Create a controller
php artisan make:controller Api/YourController

# Create a form request
php artisan make:request StoreYourRequest

# Create an API resource
php artisan make:resource YourResource

# Run migrations
php artisan migrate

# Queue jobs
php artisan queue:work

# Start WebSocket server
php artisan reverb:start
```

## Production Checklist
- [ ] Update `APP_ENV=production` in `.env`
- [ ] Update `FRONTEND_URL` to production Flutter app URL
- [ ] Set proper database credentials
- [ ] Configure Redis for production
- [ ] Set `APP_DEBUG=false`
- [ ] Generate secure `APP_KEY`
- [ ] Configure CORS for production domains only
- [ ] Set up SSL/HTTPS
- [ ] Configure email service for notifications

## Useful Links
- [Laravel Documentation](https://laravel.com/docs)
- [Sanctum Documentation](https://laravel.com/docs/sanctum)
- [Spatie Permissions](https://spatie.be/docs/laravel-permission)
- [Reverb WebSockets](https://laravel.com/docs/reverb)
