<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;
    public string $username;
    public string $password;
    public string $role;

    public function __construct(string $name, string $username, string $password, string $role)
    {
        $this->name = $name;
        $this->username = $username;
        $this->password = $password;
        $this->role = $role;
    }

    public function build()
    {
        return $this->subject('Your Account Credentials')
            ->view('emails.user_credentials');
    }
}