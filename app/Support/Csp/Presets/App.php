<?php

declare(strict_types=1);

namespace App\Support\Csp\Presets;

use Illuminate\Support\Uri;
use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;

class App implements Preset
{
    public function configure(Policy $policy): void
    {
        $assetUrl = Uri::of(config('app.asset_url'))->host();
        $fingerprintEndpoint = Uri::of(config('services.fingerprint'))->host();

        $policy
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::CONNECT, array_filter([Keyword::SELF, 'api.fpjs.io', $fingerprintEndpoint]))
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::FONT, Keyword::SELF)
            ->add(Directive::FORM_ACTION, Keyword::SELF)
            ->add(Directive::FRAME, Keyword::SELF)
            ->add(Directive::IMG, [Keyword::SELF, 'ui-avatars.com', 'blob:'])
            ->add(Directive::MEDIA, Keyword::SELF)
            ->add(Directive::OBJECT, Keyword::NONE)
            ->add(Directive::SCRIPT, array_filter([Keyword::SELF, 'fpnpmcdn.net', $fingerprintEndpoint, $assetUrl]))
            ->add(Directive::STYLE, array_filter([Keyword::SELF, Keyword::UNSAFE_INLINE, $assetUrl]));

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
