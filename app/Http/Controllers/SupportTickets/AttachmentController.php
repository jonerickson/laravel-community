<?php

declare(strict_types=1);

namespace App\Http\Controllers\SupportTickets;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\SupportTicket;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class AttachmentController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, SupportTicket $ticket): Response
    {
        $this->authorize('create', [File::class, $ticket]);

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
        $this->authorize('delete', [$file, $ticket]);

        $file->delete();

        return to_route('support.show', $ticket)
            ->with('message', 'The attachment was successfully deleted.');
    }
}
