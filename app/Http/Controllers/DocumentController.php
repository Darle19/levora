<?php

namespace App\Http\Controllers;

use App\Models\BookingDocument;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function download(BookingDocument $document)
    {
        $order = $document->booking->order;

        $this->authorize('view', $order);

        // Validate file path to prevent path traversal
        $expectedPrefix = "documents/{$order->id}/";
        if (!str_starts_with($document->file_path, $expectedPrefix)) {
            abort(403);
        }

        if (!$order->isFullyPaid()) {
            abort(403, __('messages.doc_locked_message'));
        }

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404);
        }

        $filename = $this->buildFilename($document);

        return Storage::disk('local')->download($document->file_path, $filename);
    }

    private function buildFilename(BookingDocument $document): string
    {
        $type = $document->getTypeLabel();
        $desc = $document->getDescription();
        $suffix = $desc ? "_{$desc}" : '';

        return "{$type}{$suffix}.pdf";
    }
}
