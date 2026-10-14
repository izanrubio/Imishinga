<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function show(Request $request, Attachment $attachment): StreamedResponse
    {
        abort_unless($attachment->card->list->board->user_id === $request->user()->id, 404);

        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        $disposition = $attachment->isImage() && ! $request->boolean('download') ? 'inline' : 'attachment';

        $filename = str_replace(['/', '\\', '"'], '_', $attachment->filename);

        return Storage::disk('local')->response($attachment->path, $filename, [
            'X-Content-Type-Options' => 'nosniff',
        ], $disposition);
    }
}
