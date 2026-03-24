<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $campaignSubject;
    public string $htmlContent;
    public ?string $plainText;
    public string $fromName;
    public string $fromEmail;
    public ?string $campaignReplyTo;

    public function __construct(
        string $campaignSubject,
        string $htmlContent,
        ?string $plainText,
        string $fromName,
        string $fromEmail,
        ?string $campaignReplyTo = null
    ) {
        $this->campaignSubject = $campaignSubject;
        $this->htmlContent = $htmlContent;
        $this->plainText = $plainText;
        $this->fromName = $fromName;
        $this->fromEmail = $fromEmail;
        $this->campaignReplyTo = $campaignReplyTo;
    }

    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            from: new Address($this->fromEmail, $this->fromName),
            subject: $this->campaignSubject,
        );

        if ($this->campaignReplyTo) {
            $envelope->replyTo[] = new Address($this->campaignReplyTo);
        }

        return $envelope;
    }

    public function build(): self
    {
        $this->html($this->htmlContent);

        if ($this->plainText) {
            $this->text('mail.plain', ['content' => $this->plainText]);
        }

        return $this;
    }
}
