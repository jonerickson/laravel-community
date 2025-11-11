<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Action;
use App\Mail\Auth\MagicLinkMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendMagicLinkAction extends Action
{
    public function __construct(
        protected User $user
    ) {
        //
    }

    public function __invoke(): true
    {
        $url = URL::temporarySignedRoute('magic-link.login', now()->addMinutes(15), [
            'user' => $this->user->reference_id,
        ]);

        Mail::to($this->user->email)->send(new MagicLinkMail($this->user, $url));

        return true;
    }
}
