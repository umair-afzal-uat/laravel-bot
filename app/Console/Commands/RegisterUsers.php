<?php

namespace App\Console\Commands;

use App\Jobs\RegisterUserJob;
use Illuminate\Console\Command;

class RegisterUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'register:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register users on the challenge.blackscale.media website';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = [
            ['email' => 'test1@gmail.com', 'fullName' => 'John Doe', 'password' => 'password1'],
            ['email' => 'test2@gmail.com', 'fullName' => 'Jane Smith', 'password' => 'password2'],
            // Add more users as needed
        ];

        foreach ($users as $user) {
            RegisterUserJob::dispatch($user['email'], $user['fullName'], $user['password']);
        }

        $this->info('Registration automation started.');
        return 0;
    }
}
