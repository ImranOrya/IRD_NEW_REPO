<?php

namespace App\Http\Controllers\api\app\project;

use App\Enums\pdfFooter\PdfFooterEnum;
use App\Http\Controllers\Controller;
use App\Traits\Address\AddressTrait;
use App\Traits\Report\PdfGeneratorTrait;
use Illuminate\Http\Request;

class ProjectPdfController extends Controller
{
    //
    use PdfGeneratorTrait, AddressTrait;


    public function generateForm(Request $request, $id)
    {
        $mpdf = $this->generatePdf();
        $this->setWatermark($mpdf);

        $lang = 'en'; // Assuming English
        $data = $this->loadProjectData($lang, $id);

        // **SECTION 1** - Apply First Footer
        $this->setFooter($mpdf, PdfFooterEnum::MOU_FIRST_FOOTER_en->value);
        $this->pdfFilePart($mpdf, "project.mou.pdf.{$lang}.mou", $data);

        // Add a new page to start Section 2
        $mpdf->AddPage();

        // **SECTION 2** - Apply Second Footer
        $this->setFooter($mpdf, PdfFooterEnum::MOU_FOOTER_en->value);
        $this->pdfFilePart($mpdf, "project.mou.pdf.{$lang}.mou_section2", $data);

        // Output the generated PDF
        return $mpdf->Output('document.pdf', 'I'); // Stream PDF to browser
    }



    protected function loadProjectData($lang, $id)
    {


        $data = [
            'register_number' => '$ngo->registration_no',
            'date_of_sign' => '................',
            'ngo_name' => '................',
            'abbr' => '................',
            'contact' => '................',
            'address' => '................',
            'director' => '................',
            'director_address' => '................',
            'email' => '................',
            'establishment_date' => '................',
            'place_of_establishment' => '................',
            'ministry_economy_no' => '................',
            'general_objective' => '................',
            'afganistan_objective' => '................',
            'mission' => '................',
            'vission' => '................',
            'ird_director' => '................',



        ];
        return $data;
    }
}
