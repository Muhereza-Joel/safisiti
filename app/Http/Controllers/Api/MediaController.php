<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    /**
     * Serve media files
     */
    public function show($model, $id, $collectionName, $fileName): StreamedResponse
    {
        $media = Media::find($id);

        if (!$media) {
            abort(404);
        }

        return response()->streamDownload(function () use ($media) {
            echo $media->stream();
        }, $media->file_name, [
            'Content-Type' => $media->mime_type,
        ]);
    }
}
