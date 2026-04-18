<?php

namespace App\Http\Controllers;

use App\Models\BookingDocument;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function download(BookingDocument $document)
    {
        $order = $document->booking->order;

        // Admins can always download; agents need authorization
        $user = auth()->user();
        $isAdmin = $user && $user->hasAnyRole(['administrator', 'manager']);

        if (! $isAdmin) {
            $this->authorize('view', $order);
        }

        // Validate file path to prevent path traversal
        $expectedPrefix = "documents/{$order->id}/";
        if (!str_starts_with($document->file_path, $expectedPrefix)) {
            abort(403);
        }

        if (! $isAdmin && $order->status !== 'paid') {
            abort(403, __('messages.doc_locked_message'));
        }

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404);
        }

        $filename = $this->buildFilename($document);

        // Prevent stale PDFs: Storage::download doesn't set any Cache-Control
        // by default, so browsers happily serve yesterday's file back from the
        // disk cache when ops regenerate documents.
        return Storage::disk('local')->download($document->file_path, $filename, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function buildFilename(BookingDocument $document): string
    {
        $type = $document->getTypeLabel();
        $desc = $document->getDescription();
        $suffix = $desc ? "_{$desc}" : '';

        return "{$type}{$suffix}.pdf";
    }
}
