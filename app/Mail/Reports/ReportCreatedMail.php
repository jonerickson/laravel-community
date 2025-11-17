<?php

declare(strict_types=1);

namespace App\Mail\Reports;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReportCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Report $report)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Report Submitted',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reports.report-created',
        );
    }

    /**
     * @return array{}
     */
    public function attachments(): array
    {
        return [];
    }
}
