<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin {--name= : The name of the administrator} {--email= : The email of the administrator} {--password= : The password} {--super : Whether the administrator is a superadmin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new System Administrator for the backend dashboard';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->option('name') ?? $this->ask('Enter administrator name');
        $email = $this->option('email') ?? $this->ask('Enter administrator email');
        
        $password = $this->option('password');
        if (!$password) {
            $password = $this->secret('Enter administrator password');
            $confirmPassword = $this->secret('Confirm administrator password');
            
            if ($password !== $confirmPassword) {
                $this->error('Passwords do not match.');
                return 1;
            }
        }

        $isSuper = $this->option('super') || $this->confirm('Is this user a Super Administrator?', true);

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        $admin = Admin::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_active' => true,
            'is_superadmin' => $isSuper,
        ]);

        $this->info("System Administrator '{$admin->name}' (ID: {$admin->id}) created successfully.");
        return 0;
    }
}
