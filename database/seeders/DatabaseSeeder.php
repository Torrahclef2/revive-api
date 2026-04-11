<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupPost;
use App\Models\GroupPostReaction;
use App\Models\PrayerSession;
use App\Models\SessionMember;
use App\Models\Circle;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create 30 test users
        $users = User::factory(30)->create();

        // Create test admin user
        User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@test.com',
            'display_name' => 'Admin User',
            'level' => 'leader',
            'xp_points' => 50000,
            'streak_count' => 365,
        ]);

        // Create test user for authentication
        User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'display_name' => 'Test User',
            'password' => bcrypt('password'),
        ]);

        // Create circles (friendships/connections)
        foreach ($users->random(20) as $user) {
            $userCircles = User::where('id', '!=', $user->id)
                ->inRandomOrder()
                ->limit(random_int(3, 10))
                ->get();

            foreach ($userCircles as $circle_user) {
                Circle::create([
                    'requester_id' => $user->id,
                    'receiver_id' => $circle_user->id,
                    'status' => 'accepted',
                ]);
            }
        }

        // Create groups (10 groups)
        $groups = Group::factory(10)->create();

        // Add members to groups and create posts/reactions
        foreach ($groups as $group) {
            $members = $users->random(random_int(5, 15));
            
            // Add members to the group
            foreach ($members as $member) {
                if ($member->id !== $group->creator_id) {
                    GroupMember::create([
                        'group_id' => $group->id,
                        'user_id' => $member->id,
                        'role' => random_int(0, 100) > 80 ? 'admin' : 'member',
                        'joined_at' => now()->subDays(random_int(1, 30)),
                    ]);
                }
            }

            // Create posts in the group
            foreach ($members->random(random_int(3, 8)) as $member) {
                $posts = GroupPost::factory(random_int(2, 3))
                    ->for($group)
                    ->for($member, 'author')
                    ->create();

                // Add reactions to posts
                foreach ($posts as $post) {
                    $reactors = $members->random(random_int(1, 5));
                    foreach ($reactors as $reactor) {
                        if ($reactor->id !== $member->id) {
                            GroupPostReaction::create([
                                'post_id' => $post->id,
                                'user_id' => $reactor->id,
                                'reaction' => ['amen', 'heart', 'pray'][random_int(0, 2)],
                            ]);
                        }
                    }
                }
            }
        }

        // Create prayer sessions (15 sessions)
        for ($i = 0; $i < 15; $i++) {
            $host = $users->random(1)->first();
            $status = ['upcoming', 'admitting', 'live', 'ended'][random_int(0, 3)];
            
            $session = PrayerSession::factory()
                ->for($host, 'host')
                ->create([
                    'status' => $status,
                    'scheduled_at' => now()->addDays(random_int(1, 30)),
                ]);

            // Add session members (5-20 members per session)
            $members = $users->where('id', '!=', $host->id)
                ->random(random_int(5, 20));

            foreach ($members as $member) {
                SessionMember::create([
                    'session_id' => $session->id,
                    'user_id' => $member->id,
                    'status' => ['requested', 'admitted', 'rejected', 'kicked'][random_int(0, 3)],
                    'joined_at' => $status === 'live' ? now()->subMinutes(random_int(1, 60)) : null,
                ]);
            }
        }

        $this->command->info('Database seeded successfully! 🎉');
        $this->command->info(sprintf(
            'Created: %d Users | %d Groups | %d Prayer Sessions | %d Circles',
            User::count(),
            Group::count(),
            PrayerSession::count(),
            Circle::count()
        ));
    }
}
