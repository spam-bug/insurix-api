<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Documents\DownloadDocument;
use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    public function show(Request $request, Document $document, DownloadDocument $downloadDocument): StreamedResponse
    {
        return $downloadDocument->handle($document, $request->boolean('inline'));
    }
}
