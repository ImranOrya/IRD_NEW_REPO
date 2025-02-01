<?php

namespace App\Traits\Report;

use App\Models\User;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

trait PdfGeneratorTrait
{
    public function generatePdf()
    {

        $configVariables = new ConfigVariables();
        $fontDirs = $configVariables->getDefaults()['fontDir'];
        $fontVariables = new FontVariables();
        $fontData = $fontVariables->getDefaults()['fontdata'];


        $mpdf = new Mpdf([
            'fontDir' => array_merge($fontDirs, [public_path('fonts/amiri')]),
            'fontdata' => $fontData + [
                'amiri' => [
                    'R' => 'Amiri-Regular.ttf',
                    'B' => 'Amiri-Bold.ttf',
                    'I' => 'Amiri-Italic.ttf',
                    'BI' => 'Amiri-BoldItalic.ttf'
                ]
            ],
            'default_font' => 'amiri',
            'mode' => 'utf-8',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'margin_bottom' => 50,  // Increase bottom margin to make space for the footer
        ]);

        return $mpdf;
    }
    public function mergeExistingPdf(Mpdf $mpdf, $pdfPath, $footerHtml)
    {
        $pageCount = $mpdf->setSourceFile($pdfPath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $mpdf->AddPage();
            $templateId = $mpdf->ImportPage($i);
            $mpdf->UseTemplate($templateId);
            $mpdf->SetFooter($footerHtml); // Ensure the footer is set for each page
        }
    }

    public function setWatermark($mpdf, $path = 'app/public/images/emart.png')
    {
        $watermarkImagePath = storage_path('app/public/images/emart.png');
        $mpdf->SetWatermarkImage($watermarkImagePath, 0.2); // Set watermark and opacity
        $mpdf->showWatermarkImage = true; // Enable watermark

    }

    public function setFooter($mpdf, $footerHtml)
    {
        // Set the footer with RTL support

        $mpdf->SetFooter($footerHtml); // Apply footer to all pages
        $mpdf->defaultfooterline = 0; // Remove default footer line

    }

    public function pdfFilePart($mpdf, $view, $data = [])
    {
        // Directly pass the associative $data array to the view method
        $part = view($view, $data)->render();
        $mpdf->WriteHTML($part);
    }
}
