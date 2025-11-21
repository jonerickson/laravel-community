<?php

declare(strict_types=1);

namespace App\Support\Csp\Presets;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;

class App implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::CONNECT, [Keyword::SELF, 'api.fpjs.io', 'metrics.reactstudios.com'])
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::FONT, Keyword::SELF)
            ->add(Directive::FORM_ACTION, Keyword::SELF)
            ->add(Directive::FRAME, Keyword::SELF)
            ->add(Directive::IMG, [Keyword::SELF, 'ui-avatars.com', 'blob:'])
            ->add(Directive::MEDIA, Keyword::SELF)
            ->add(Directive::OBJECT, Keyword::NONE)
            ->add(Directive::SCRIPT, [Keyword::SELF, 'fpnpmcdn.net'])
            ->add(Directive::STYLE, [Keyword::SELF, Keyword::UNSAFE_INLINE]);

        if ($this->isProtectedRoute()) {
            $policy->add(Directive::SCRIPT, [Keyword::UNSAFE_INLINE, Keyword::UNSAFE_EVAL]);
            $policy->add(Directive::FONT, 'data:');
            $policy->add(Directive::WORKER, 'blob:');
        } else {
            $policy->addNonce(Directive::SCRIPT);
        }
    }

    protected function isProtectedRoute(): bool
    {
        $overridePaths = [
            'admin',
            'admin/*',
            'horizon',
            'horizon/*',
            'telescope',
            'telescope/*',
        ];

        return array_any($overridePaths, fn ($pattern) => request()->is($pattern));
    }
}
