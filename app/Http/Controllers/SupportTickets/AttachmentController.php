<?php

declare(strict_types=1);

namespace App\Http\Controllers\SupportTickets;

use App\Enums\FileVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\SupportTickets\StoreSupportTicketAttachmentRequest;
use App\Models\File;
use App\Models\SupportTicket;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AttachmentController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreSupportTicketAttachmentRequest $request, SupportTicket $ticket): Response
    {
        $this->authorize('update', $ticket);
        $this->authorize('create', File::class);

        /** @var UploadedFile $file */
        $file = $request->validated('attachment');
        $path = $file->store('support');

        if ($path === false) {
            throw ValidationException::withMessages([
                'attachment' => 'The attachment could not be uploaded. Please try again.',
            ]);
        }

        $ticket->files()->create([
            'name' => $file->getClientOriginalName(),
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'visibility' => FileVisibility::Private,
        ]);

        return back()->with('message', 'Your attachment was successfully added.');
    }

    public function destroy(SupportTicket $ticket, File $file): Response
    {
        $this->authorize('update', $ticket);
        $this->authorize('delete', $file);

        $file->delete();

        return back()->with('message', 'The attachment was successfully deleted.');
    }
}
