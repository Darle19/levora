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
