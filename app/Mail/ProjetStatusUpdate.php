<?php

namespace App\Mail;

use App\Models\Projet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjetStatusUpdate extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Le projet qui a été mis à jour.
     */
    public $projet;

    /**
     * Create a new message instance.
     */
    public function __construct(Projet $projet)
    {
        $this->projet = $projet;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $status = $this->projet->statut === 'Validé' ? 'a été validé' : 'a été rejeté';
        
        return new Envelope(
            subject: "Votre projet {$status}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.projet-status-update',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
} 