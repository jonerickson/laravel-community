import { Link } from '@inertiajs/react';

export function ImpersonationBanner() {
    return (
        <div className="fixed inset-x-0 bottom-0 z-50 bg-sidebar px-4 py-2 text-center text-sm font-medium text-primary">
            You are currently impersonating a user.{' '}
            <Link href={route('impersonate.leave')} className="underline hover:no-underline">
                Stop impersonating
            </Link>
        </div>
    );
}
