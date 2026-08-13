<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LiquidationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ownerName;

    public $subjectStr;

    public $pdfPath;

    /**
     * Create a new message instance.
     */
    public function __construct($ownerName, $subjectStr, $pdfPath)
    {
        $this->ownerName = $ownerName;
        $this->subjectStr = $subjectStr;
        $this->pdfPath = $pdfPath;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectStr,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.liquidation',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as('Liquidacion.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
