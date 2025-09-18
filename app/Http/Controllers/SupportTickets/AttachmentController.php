<?php

declare(strict_types=1);

namespace App\Http\Controllers\SupportTickets;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AttachmentController extends Controller
{
    public function store(Request $request, SupportTicket $ticket): Response
    {
        abort_unless($ticket->isAuthoredBy(Auth::user()), 403);

        $validated = $request->validate([
            'attachment' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,txt,png,jpg,jpeg,gif,heif'],
        ]);

        /** @var UploadedFile $file */
        $file = $validated['attachment'];
        $path = $file->store('support-attachments');

        $ticket->files()->create([
            'name' => $file->getClientOriginalName(),
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
        ]);

        return to_route('support.show', $ticket)
            ->with('message', 'Your attachment was successfully added.');
    }

    public function destroy(SupportTicket $ticket, File $file): Response
    {
        abort_unless($ticket->isAuthoredBy(Auth::user()), 403);
        abort_unless($file->resource_id === $ticket->id && $file->resource_type === SupportTicket::class, 404);

        $file->delete();

        return to_route('support.show', $ticket)
            ->with('message', 'The attachment was successfully deleted.');
    }
}
