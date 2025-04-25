<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompanyInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $inviteUrl;
    public $companyName;

    /**
     * Create a new message instance.
     */
    public function __construct($inviteUrl, $companyName)
    {
        $this->inviteUrl = $inviteUrl;
        $this->companyName = $companyName;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Convite para se juntar à empresa ' . $this->companyName)
                    ->view('emails.company_invitation');
    }
}
