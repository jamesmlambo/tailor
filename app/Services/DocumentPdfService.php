<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\Document;
use Barryvdh\DomPDF\PDF as DomPdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class DocumentPdfService
{
    /**
     * @return array<string, mixed>
     */
    public function data(Document $document): array
    {
        $document->loadMissing('items');

        $business = $document->businessDetails();
        $theme = BusinessProfile::THEMES[$document->theme] ?? BusinessProfile::THEMES['modern'];
        $logo = $this->logoDataUri($business['logo_path'] ?? null);
        $business['logo_url'] = $logo ?: ($business['logo_url'] ?? null);

        return [
            'document' => $document,
            'business' => $business,
            'theme' => $document->theme,
            'accent' => $theme['accent'],
            'items' => $document->items,
            'hasTax' => $document->items->contains(fn ($item) => (float) $item->tax_rate > 0),
            'balance' => max(0, (float) $document->total - (float) $document->amount_paid),
        ];
    }

    public function make(Document $document): DomPdf
    {
        return Pdf::loadView('documents.pdf', $this->data($document))
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true);
    }

    public function output(Document $document): string
    {
        return $this->make($document)->output();
    }

    public function filename(Document $document): string
    {
        return strtolower($document->type).'-'.preg_replace('/[^A-Za-z0-9\-_]/', '-', $document->number).'.pdf';
    }

    /**
     * Convert the uploaded logo into a PNG data URI that Dompdf can render.
     */
    private function logoDataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $raw = Storage::disk('public')->get($path);

        if (! $raw) {
            return null;
        }

        if (function_exists('imagecreatefromstring')) {
            $source = @imagecreatefromstring($raw);

            if ($source !== false) {
                $width = imagesx($source);
                $height = imagesy($source);
                $maxWidth = 500;

                if ($width > $maxWidth) {
                    $newWidth = $maxWidth;
                    $newHeight = (int) max(1, round($height * ($maxWidth / $width)));
                    $resized = imagecreatetruecolor($newWidth, $newHeight);
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                    imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagedestroy($source);
                    $source = $resized;
                }

                ob_start();
                imagepng($source);
                $png = ob_get_clean();
                imagedestroy($source);

                if (is_string($png) && $png !== '') {
                    return 'data:image/png;base64,'.base64_encode($png);
                }
            }
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($raw);
    }
}
