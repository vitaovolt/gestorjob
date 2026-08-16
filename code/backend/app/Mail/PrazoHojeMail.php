<?php

namespace App\Mail;

use App\Models\Tarefa;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class PrazoHojeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Tarefa $tarefa,
        public string $url,
        public Carbon $dia,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Prazo hoje — '.$this->tarefa->titulo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.prazo-hoje',
        );
    }
}
