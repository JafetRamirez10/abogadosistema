<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Evento;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlazoProximoMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Evento $evento) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Plazo próximo: '.$this->evento->titulo,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.plazo-proximo',
            with: [
                'titulo' => $this->evento->titulo,
                'tipo' => $this->evento->tipo->label(),
                'inicio' => $this->evento->inicio->timezone('America/Costa_Rica')->format('d/m/Y H:i'),
                'lugar' => $this->evento->lugar,
                'expediente' => $this->evento->expediente?->numero_interno,
                'notas' => $this->evento->notas,
            ],
        );
    }
}
