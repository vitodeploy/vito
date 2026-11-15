<?php

use Illuminate\Support\Facades\Mail;

test('smtp transport enforces tls when configured', function (bool $requireTls) {
    config([
        'mail.mailers.smtp.host' => 'smtp.example.com',
        'mail.mailers.smtp.port' => 587,
        'mail.mailers.smtp.require_tls' => $requireTls,
    ]);

    expect(Mail::mailer('smtp')->getSymfonyTransport()->isTlsRequired())->toBe($requireTls);
})->with([true, false]);
