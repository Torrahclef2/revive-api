# Users Table Schema

## Overview

The users table is the core of the Revive application, storing all user profile and engagement data. It uses UUID as the primary key to provide better security and privacy.

## Column Definitions

### Authentication (Required)
| Column | Type | Details |
|--------|------|---------|
| `id` | UUID | Primary key, auto-generated UUID |
| `email` | string(255) | Unique, required email for login |
| `password` | string(255) | Hashed password |
| `remember_token` | string(100) | Optional token for "remember me" |

### Profile Information
| Column | Type | Details |
|--------|------|---------|
| `username` | string(255) | Unique username for profile URL |
| `display_name` | string(255) | User's display name, nullable |
| `avatar_url` | string(255) | URL to avatar image, nullable |
| `headline` | string(255) | Short bio/tagline (e.g., "Intercessor \| Teacher"), nullable |

### Spiritual Information
| Column | Type | Enum Values | Details |
|--------|------|-------------|---------|
| `denomination` | string(255) | - | Religious denomination, nullable |
| `gender` | enum | male, female, prefer_not_to_say | Default: prefer_not_to_say |
| `level` | enum | seeker, rising_disciple, follower, faithful, leader | User's spiritual growth level, default: seeker |

### Location
| Column | Type | Details |
|--------|------|---------|
| `location_city` | string(255) | City of residence, nullable |
| `location_country` | string(255) | Country code/name, nullable, **indexed** |

### Engagement Metrics
| Column | Type | Details |
|--------|------|---------|
| `xp_points` | unsignedInteger | Experience points, default: 0 |
| `streak_count` | unsignedInteger | Consecutive activity streak, default: 0 |
| `last_active_date` | date | Last activity date, nullable |

### Status & Timestamps
| Column | Type | Details |
|--------|------|---------|
| `is_active` | boolean | Account active status, default: true |
| `email_verified_at` | timestamp | Email verification timestamp, nullable |
| `created_at` | timestamp | Account creation time |
| `updated_at` | timestamp | Last update time |

## Indexes

The following columns are indexed for performance:
- `email` - For fast login lookups
- `username` - For profile URL lookups
- `location_country` - For geographic filtering
- `level` - For user level filtering and statistics

Foreign key in `sessions` table references `users.id` with cascade delete.

## Enums

### Gender
```php
'male', 'female', 'prefer_not_to_say'
Default: 'prefer_not_to_say'
```

### User Level (Spiritual Growth)
```php
'seeker'            // New to faith/Bible study
'rising_disciple'   // Growing in faith
'follower'          // Consistent in practice
'faithful'          // Deeply committed
'leader'            // Mentoring others
Default: 'seeker'
```

## User Model

The User model is configured with:
- UUID primary key support via `HasUuids` trait
- UUID cast for sessions' user_id foreign key
- All profile fields in `$fillable`
- Password hashing via `hashed` cast
- Hidden sensitive fields: password, remember_token

### Factory

The UserFactory generates realistic test data:
- Random but unique email and username
- Hashed test password
- Random profile information
- Random spiritual level and denominations
- Random location data
- Random engagement metrics

#### Factory Methods

```php
// Create unverified user
User::factory()->unverified()->create()

// Create inactive user
User::factory()->inactive()->create()

// Create multiple users
User::factory()->count(100)->create()
```

## Migration

Run the migration:
```bash
php artisan migrate
```

Rollback:
```bash
php artisan migrate:rollback
```

## Usage Examples

### Create User (in code)
```php
use App\Models\User;

$user = User::create([
    'email' => 'user@example.com',
    'username' => 'johndoe',
    'password' => Hash::make('password123'),
    'display_name' => 'John Doe',
    'level' => 'seeker',
    'gender' => 'male',
    'location_country' => 'US',
]);
```

### Query Examples
```php
// Find user by email
$user = User::where('email', 'user@example.com')->first();

// Get all active users
$activeUsers = User::where('is_active', true)->get();

// Get users by level
$leaders = User::where('level', 'leader')->get();

// Order by XP points (leaderboard)
$topUsers = User::orderByDesc('xp_points')->limit(10)->get();

// Find users by country
$usUsers = User::where('location_country', 'US')->get();

// Get verified users
$verifiedUsers = User::whereNotNull('email_verified_at')->get();
```

### Update Examples
```php
// Update user profile
$user->update([
    'display_name' => 'New Name',
    'headline' => 'Updated bio',
    'avatar_url' => 'https://example.com/avatar.jpg',
]);

// Increment XP (after activity)
$user->increment('xp_points', 10);

// Update streak
$user->update([
    'streak_count' => $user->streak_count + 1,
    'last_active_date' => today(),
]);

// Verify email
$user->update(['email_verified_at' => now()]);

// Deactivate account
$user->update(['is_active' => false]);
```

## Database Relations (Future)

When other tables are created, add these relations to User model:

```php
// Prayers
public function prayers() {
    return $this->hasMany(Prayer::class);
}

// Prayer groups
public function groupMemberships() {
    return $this->belongsToMany(Group::class, 'group_members');
}

// Messages
public function messages() {
    return $this->hasMany(Message::class);
}

// Followers
public function followers() {
    return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id');
}

public function following() {
    return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id');
}

// Notifications
public function notifications() {
    return $this->hasMany(Notification::class);
}
```

## Seeders

Create test data with:
```bash
php artisan db:seed --class=UserSeeder
```

Example seeder for reference:
```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'email' => 'admin@revive.local',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'display_name' => 'Administrator',
            'level' => 'leader',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create 50 test users
        User::factory()->count(50)->create();
    }
}
```

## Notes

- UUIDs provide better privacy than auto-increment IDs
- Enums are database-level constraints for data integrity
- Indexes improve query performance for common lookups
- The `is_active` flag allows soft disabling of accounts
- Streak and XP are tracked for gamification features
- Email verification is optional but recommended
