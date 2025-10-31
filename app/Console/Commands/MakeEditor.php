<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeEditor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:make-editor {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set a user as editor by email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email '{$email}' not found!");
            return 1;
        }
        
        if ($user->isEditor()) {
            $this->info("User '{$user->name}' ({$email}) is already an editor!");
            return 0;
        }
        
        $user->update(['role' => 'editor']);
        
        $this->info("User '{$user->name}' ({$email}) has been set as editor successfully!");
        return 0;
    }
}
