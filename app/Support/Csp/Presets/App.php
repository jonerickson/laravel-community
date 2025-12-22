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
        $assetUrl = Uri::of(config('app.asset_url') ?? '')->host();
        $s3Url = Uri::of(config('filesystems.disks.s3.url') ?? '')->host();
        $fingerprintEndpoint = Uri::of(config('services.fingerprint.endpoint') ?? '')->host();

        $connectSrc = explode(',', config('csp.additional_directives.'.Directive::CONNECT->value) ?? '');
        $imgSrc = explode(',', config('csp.additional_directives.'.Directive::IMG->value) ?? '');

        $policy
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::CONNECT, array_filter([Keyword::SELF, 'api.fpjs.io', $s3Url, $fingerprintEndpoint, ...$connectSrc]))
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::FONT, array_filter([Keyword::SELF, $assetUrl, $s3Url]))
            ->add(Directive::FORM_ACTION, Keyword::SELF)
            ->add(Directive::FRAME, Keyword::SELF)
            ->add(Directive::IMG, array_filter([Keyword::SELF, 'ui-avatars.com', 'blob:', $assetUrl, $s3Url, ...$imgSrc]))
            ->add(Directive::MEDIA, Keyword::SELF)
            ->add(Directive::OBJECT, Keyword::NONE)
            ->add(Directive::SCRIPT, array_filter([Keyword::SELF, 'fpnpmcdn.net', $fingerprintEndpoint, $s3Url, $assetUrl]))
            ->add(Directive::STYLE, array_filter([Keyword::SELF, Keyword::UNSAFE_INLINE, $s3Url, $assetUrl]));

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
            'marketplace',
            'marketplace/*',
            'telescope',
            'telescope/*',
        ];

        return array_any($overridePaths, fn ($pattern) => request()->is($pattern));
    }
}
