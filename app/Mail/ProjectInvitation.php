<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProjectInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public Project $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function build(): static
    {
        return $this
            ->markdown('emails.project-invitation', [
                'acceptUrl' => route('projects.invitations.accept', ['project' => $this->project]),
            ])
            ->subject(__('Project Invitation'));
    }
}
