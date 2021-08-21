<?php

namespace Modules\Auth\Http\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The Verification Token
     * 
     * @param Token
     */
    public $token;

    /**
     * Create a new message instance.
     *
     * @param integer $token
     * @return void
     */
    public function __construct(int $token)
    {
        $this->token = $token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.showtoken');
    }
}
