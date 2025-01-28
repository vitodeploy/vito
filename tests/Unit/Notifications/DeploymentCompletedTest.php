<?php

namespace Tests\Unit\Notifications;

use App\Models\Deployment;
use App\Models\Site;
use App\Notifications\DeploymentCompleted;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class DeploymentCompletedTest extends TestCase
{
    public function testRawText()
    {
        $deployment = new Deployment(['status' => 'finished']);
        $site = new Site(['domain' => 'example.com']);
        $notification = new DeploymentCompleted($deployment, $site);

        $expectedText = "Deployment for site [example.com] has completed with status: finished";
        $this->assertEquals($expectedText, $notification->rawText());
    }

    public function testToEmail()
    {
        $deployment = new Deployment(['status' => 'finished']);
        $site = new Site(['domain' => 'example.com']);
        $notification = new DeploymentCompleted($deployment, $site);

        $mailMessage = $notification->toEmail(new \stdClass());
        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertEquals('Deployment Completed', $mailMessage->subject);
        $this->assertStringContainsString('Deployment for site [example.com] has completed with status: finished', $mailMessage->introLines[0]);
    }

    public function testToSlack()
    {
        $deployment = new Deployment(['status' => 'finished']);
        $site = new Site(['domain' => 'example.com']);
        $notification = new DeploymentCompleted($deployment, $site);

        $expectedText = "Deployment for site [example.com] has completed with status: finished";
        $this->assertEquals($expectedText, $notification->toSlack(new \stdClass()));
    }

    public function testToDiscord()
    {
        $deployment = new Deployment(['status' => 'finished']);
        $site = new Site(['domain' => 'example.com']);
        $notification = new DeploymentCompleted($deployment, $site);

        $expectedText = "Deployment for site [example.com] has completed with status: finished";
        $this->assertEquals($expectedText, $notification->toDiscord(new \stdClass()));
    }

    public function testToTelegram()
    {
        $deployment = new Deployment(['status' => 'finished']);
        $site = new Site(['domain' => 'example.com']);
        $notification = new DeploymentCompleted($deployment, $site);

        $expectedText = "Deployment for site [example.com] has completed with status: finished";
        $this->assertEquals($expectedText, $notification->toTelegram(new \stdClass()));
    }
}
