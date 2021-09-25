<?php
namespace App\Events;

class Notifications {
    static function send($user) {
        $email = \Config\Services::email();

        $email->setFrom("your@example.com", "Your Name");
        $email->setTo("your@example.com");

        $email->setSubject("Email Test");
        $email->setMessage("Testing the email class.");

        $email->send();
    }
}