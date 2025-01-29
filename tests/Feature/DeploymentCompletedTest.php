<?php

namespace Tests\Feature;

use App\Models\Deployment;
use App\Models\Site;
use App\Notifications\DeploymentCompleted;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class DeploymentCompletedTest extends TestCase
{
    public function test_raw_text()
    {
        $deployment = new Deployment(['status' => 'finished']);
        $site = new Site(['domain' => 'example.com']);
        $notification = new DeploymentCompleted($deployment, $site);

        $expectedText = 'Deployment for site [example.com] has completed with status: finished';
        $this->assertEquals($expectedText, $notification->rawText());
    }

    public function test_to_email()
    {
        $deployment = new Deployment(['status' => 'finished']);
        $site = new Site(['domain' => 'example.com']);
        $notification = new DeploymentCompleted($deployment, $site);

        $mailMessage = $notification->toEmail(new \stdClass);
        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertEquals('Deployment Completed', $mailMessage->subject);
        $this->assertStringContainsString('Deployment for site [example.com] has completed with status: finished', $mailMessage->introLines[0]);
    }

    public function test_to_slack()
    {
        $deployment = new Deployment(['status' => 'finished']);
        $site = new Site(['domain' => 'example.com']);
        $notification = new DeploymentCompleted($deployment, $site);

        $expectedText = 'Deployment for site [example.com] has completed with status: finished';
        $this->assertEquals($expectedText, $notification->toSlack(new \stdClass));
    }

    public function test_to_discord()
    {
        $deployment = new Deployment(['status' => 'finished']);
        $site = new Site(['domain' => 'example.com']);
        $notification = new DeploymentCompleted($deployment, $site);

        $expectedText = 'Deployment for site [example.com] has completed with status: finished';
        $this->assertEquals($expectedText, $notification->toDiscord(new \stdClass));
    }

    public function test_to_telegram()
    {
        $deployment = new Deployment(['status' => 'finished']);
        $site = new Site(['domain' => 'example.com']);
        $notification = new DeploymentCompleted($deployment, $site);

        $expectedText = 'Deployment for site [example.com] has completed with status: finished';
        $this->assertEquals($expectedText, $notification->toTelegram(new \stdClass));
    }
}
