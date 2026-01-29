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
        $fingerprintEnabled = (bool) config('services.fingerprint.api_key');
        $intercomEnabled = (bool) config('services.intercom.app_id');

        $connectSrc = explode(',', config('csp.additional_directives.'.Directive::CONNECT->value) ?? '');
        $imgSrc = explode(',', config('csp.additional_directives.'.Directive::IMG->value) ?? '');
        $scriptSrc = explode(',', config('csp.additional_directives.'.Directive::SCRIPT->value) ?? '');

        $policy
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::CONNECT, array_filter([Keyword::SELF, $s3Url, ...$connectSrc]))
            ->add(Directive::CHILD, Keyword::SELF)
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::FONT, array_filter([Keyword::SELF, $assetUrl, $s3Url]))
            ->add(Directive::FORM_ACTION, Keyword::SELF)
            ->add(Directive::FRAME, Keyword::SELF)
            ->add(Directive::IMG, array_filter([Keyword::SELF, 'ui-avatars.com', 'blob:', $assetUrl, $s3Url, ...$imgSrc]))
            ->add(Directive::MEDIA, Keyword::SELF)
            ->add(Directive::OBJECT, Keyword::NONE)
            ->add(Directive::SCRIPT, array_filter([Keyword::SELF, $s3Url, $assetUrl, ...$scriptSrc]))
            ->add(Directive::STYLE, array_filter([Keyword::SELF, Keyword::UNSAFE_INLINE, $s3Url, $assetUrl]));

        if ($fingerprintEnabled) {
            $this->configureFingerprint($policy);
        }

        if ($intercomEnabled) {
            $this->configureIntercom($policy);
        }

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

    protected function configureFingerprint(Policy $policy): void
    {
        $endpoint = Uri::of(config('services.fingerprint.endpoint') ?? '')->host();

        $policy
            ->add(Directive::SCRIPT, array_filter([
                'https://fpnpmcdn.net',
                $endpoint,
            ]))
            ->add(Directive::CONNECT, array_filter([
                'https://api.fpjs.io',
                $endpoint,
            ]));
    }

    protected function configureIntercom(Policy $policy): void
    {
        $policy
            ->add(Directive::SCRIPT, [
                'https://app.intercom.io',
                'https://widget.intercom.io',
                'https://js.intercomcdn.com',
            ])
            ->add(Directive::CONNECT, [
                'https://*.intercom.io',
                'wss://*.intercom.io',
                'https://*.intercom-messenger.com',
                'wss://*.intercom-messenger.com',
                'https://*.intercomcdn.com',
                'https://uploads.intercomusercontent.com',
            ])
            ->add(Directive::CHILD, [
                'https://intercom-sheets.com',
                'https://www.intercom-reporting.com',
                'https://www.youtube.com',
                'https://player.vimeo.com',
                'https://fast.wistia.net',
            ])
            ->add(Directive::FONT, [
                'https://js.intercomcdn.com',
                'https://fonts.intercomcdn.com',
            ])
            ->add(Directive::FORM_ACTION, [
                'https://intercom.help',
                'https://*.intercom.io',
            ])
            ->add(Directive::IMG, [
                'data:',
                'https://*.intercomcdn.com',
                'https://*.intercomassets.com',
                'https://*.intercomusercontent.com',
                'https://messenger-apps.intercom.io',
                'https://*.intercom-attachments-1.com',
                'https://*.intercom-attachments-2.com',
                'https://*.intercom-attachments-3.com',
                'https://*.intercom-attachments-4.com',
                'https://*.intercom-attachments-5.com',
                'https://*.intercom-attachments-6.com',
                'https://*.intercom-attachments-7.com',
                'https://*.intercom-attachments-8.com',
                'https://*.intercom-attachments-9.com',
            ])
            ->add(Directive::MEDIA, [
                'https://*.intercomcdn.com',
            ]);
    }
}
