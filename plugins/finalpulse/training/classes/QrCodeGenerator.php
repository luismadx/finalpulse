<?php namespace FinalPulse\Training\Classes;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel;
use Illuminate\Http\Response;

class QrCodeGenerator
{
    public function __invoke(string $texto): Response
    {
        $logo = storage_path('app/media/finalpulse.png');

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data(urldecode($texto))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(600)
            ->margin(10)
            ->logoPath($logo)
            ->logoResizeToWidth(120)
            ->logoPunchoutBackground(true)
            ->build();

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType()
        ]);
    }
}
