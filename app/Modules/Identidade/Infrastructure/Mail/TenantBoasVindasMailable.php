<?php

namespace App\Modules\Identidade\Infrastructure\Mail;

use App\Modules\Identidade\Application\DTO\TenantBoasVindasMailPayload;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class TenantBoasVindasMailable extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly TenantBoasVindasMailPayload $payload,
    ) {}

    public function envelope(): Envelope
    {
        $fromAddress = (string) config('identidade.welcome_mail.from_address');
        $fromName = (string) config('identidade.welcome_mail.from_name');
        $replyTo = (string) config('identidade.welcome_mail.reply_to');

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            replyTo: $replyTo !== '' ? [new Address($replyTo, $fromName)] : [],
            subject: sprintf(
                '%s, seu portal %s está pronto',
                $this->payload->nomeAdmin,
                $this->payload->razaoSocial,
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.identidade.tenant-boas-vindas',
            with: [
                'nomeAdmin' => $this->payload->nomeAdmin,
                'razaoSocial' => $this->payload->razaoSocial,
                'emailAdmin' => $this->payload->emailAdmin,
                'portalUrl' => $this->payload->portalUrl,
                'loginUrl' => $this->payload->loginUrl,
                'trialEndsAt' => $this->payload->trialEndsAt,
            ],
        );
    }
}
