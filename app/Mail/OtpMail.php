<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $phone;
    public $address;
    public $otp;

    public function __construct($name, $phone, $address, $otp)
    {
        $this->name = $name;
        $this->phone = $phone;
        $this->address = $address;
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject('Xác thực người nhận hàng')
                    ->view('emails.otp'); // File view blade
    }
}

