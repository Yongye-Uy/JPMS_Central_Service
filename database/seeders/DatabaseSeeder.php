<?php

namespace Database\Seeders;

use App\Models\Journal;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeds the 5 JPMS roles, one demo journal, and one demo user per role
     * (matching the React prototype's seed pattern: admin/editor/author/
     * reviewer/reader@jpms.com, password "password") so the full lifecycle
     * can be smoke-tested end-to-end.
     */
    public function run(): void
    {
        $roles = collect(['Reader', 'Author', 'Reviewer', 'Editor', 'Admin'])
            ->mapWithKeys(fn ($name) => [$name => Role::firstOrCreate(['role_name' => $name])]);

        $editor = User::firstOrCreate(
            ['email' => 'editor@jpms.com'],
            [
                'password_hash' => Hash::make('password'),
                'full_name' => 'Editor Demo',
                'affiliation' => 'JPMS',
                'is_active' => true,
            ]
        );

        $journal = Journal::firstOrCreate(
            ['issn' => '1234-5678'],
            [
                'title' => 'Journal of Applied Demo Science',
                'scope_description' => 'A demo journal seeded for end-to-end verification.',
                'editor_in_chief_id' => $editor->id,
            ]
        );

        $demoUsers = [
            'admin@jpms.com' => ['Admin Demo', ['Admin']],
            'editor@jpms.com' => ['Editor Demo', ['Editor']],
            'author@jpms.com' => ['Author Demo', ['Author']],
            'reviewer@jpms.com' => ['Reviewer Demo', ['Reviewer']],
            'reader@jpms.com' => ['Reader Demo', ['Reader']],
        ];

        foreach ($demoUsers as $email => [$name, $userRoles]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'password_hash' => Hash::make('password'),
                    'full_name' => $name,
                    'affiliation' => 'JPMS',
                    'is_active' => true,
                ]
            );

            foreach ($userRoles as $roleName) {
                // Author/Reader are global (granted at self-registration,
                // before any journal is chosen); Editor/Reviewer are
                // admin-granted per-journal; Admin is global.
                $journalScoped = in_array($roleName, ['Editor', 'Reviewer'], true);
                UserRole::firstOrCreate([
                    'user_id' => $user->id,
                    'role_id' => $roles[$roleName]->id,
                    'journal_id' => $journalScoped ? $journal->id : null,
                ]);
            }
        }
    }
}
