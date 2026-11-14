<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProjectInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Project $project, protected string $token) {}

    public function build(): static
    {
        return $this
            ->markdown('mail.project-invitation', [
                'acceptUrl' => app_url(route('projects.invitations.show', ['project' => $this->project, 'token' => $this->token], false)),
            ])
            ->subject(__('Project Invitation'));
    }
}
