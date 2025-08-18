<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\User;

/**
 * @mixin User
 */
trait HasEmailAuthentication
{
    public function hasEmailAuthentication(): bool
    {
        return $this->has_email_authentication;
    }

    public function toggleEmailAuthentication(bool $condition): void
    {
        $this->has_email_authentication = $condition;
        $this->save();
    }

    protected function initializeHasEmailAuthentication(): void
    {
        $this->setHidden(array_merge($this->getHidden(), [
            'has_email_authentication',
        ]));

        $this->mergeCasts([
            'has_email_authentication' => 'boolean',
        ]);
    }
}
