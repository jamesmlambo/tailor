<?php

namespace App\Mail;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class DocumentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Document $document,
        public string $pdfContents,
        public string $shareUrl,
    ) {}

    public function envelope(): Envelope
    {
        $business = $this->document->businessDetails();

        return new Envelope(
            subject: sprintf(
                '%s %s from %s',
                Document::TYPES[$this->document->type] ?? 'Document',
                $this->document->number,
                $business['name'] ?: 'us',
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.document',
            with: [
                'business' => $this->document->businessDetails(),
                'document' => $this->document,
                'shareUrl' => $this->shareUrl,
                'typeLabel' => Str::lower(Document::TYPES[$this->document->type] ?? 'document'),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContents, $this->document->number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
