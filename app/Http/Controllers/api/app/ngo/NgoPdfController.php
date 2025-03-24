<?php

namespace App\Http\Controllers\api\app\ngo;

use ZipArchive;
use App\Models\Ngo;
use App\Models\Staff;
use App\Enums\StaffEnum;
use App\Models\Director;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use App\Traits\Address\AddressTrait;
use App\Enums\pdfFooter\PdfFooterEnum;
use App\Traits\Report\PdfGeneratorTrait;

class NgoPdfController extends Controller
{
    //
    use PdfGeneratorTrait, AddressTrait;

    // public function generateForm(Request $request, $id)
    // {
    //     // return $request;
    //     $mpdf =  $this->generatePdf();
    //     $this->setWatermark($mpdf);
    //     $lang = $request->input('language_name');
    //     $lang = ['en','ps','fa'];

    //     // $this->setFooter($mpdf, PdfFooterEnum::REGISTER_FOOTER->value);
    //     // $this->setFooter($mpdf, PdfFooterEnum::MOU_FOOTER_en->value);

    //     foreach($lang as $key){
    //         $data = $this->loadNgoData($key, $id);

    //     }



    //     // return view('project.mou.pdf.');
    //     // $this->pdfFilePart($mpdf, "project.mou.pdf.{$lang}.mou", $data);
    //     $this->pdfFilePart($mpdf, "ngo.registeration.{$lang}.registeration", $data);
    //     // Write additional HTML content

    //     // $mpdf->AddPage();


    //     $mpdf->SetProtection(
    //         ['print'],  // Permissions (Disallow Copy & Print)

    //     );

    //     // Output the generated PDF to the browser
    //     return $mpdf->Output('document.pdf', 'D'); // Stream PDF to browser

    // }



    public function generateForm(Request $request, $id)
    {
        $languages = ['en', 'ps', 'fa'];
        $pdfFiles = [];


        foreach ($languages as $lang) {
            $mpdf = $this->generatePdf();

            $this->setWatermark($mpdf);
            $data = $this->loadNgoData($lang, $id);
            // return "ngo.registeration.{$lang}.registeration";
            // Generate PDF content
            $this->pdfFilePart($mpdf, "ngo.registeration.{$lang}.registeration", $data);
            // $this->pdfFilePart($mpdf, "ngo.registeration.{$lang}.registeration", $data);
            $mpdf->SetProtection(['print']);

            // Store the PDF temporarily

            $fileName = "{$data['ngo_name']}_registration_{$lang}.pdf";
            $outputPath = storage_path("app/private/temp/");
            if (!is_dir($outputPath)) {
                mkdir($outputPath, 0755, true);
            }
            $filePath = $outputPath . $fileName;

            // return $filePath;
            $mpdf->Output($filePath, 'F'); // Save to file

            $pdfFiles[] = $filePath;
        }

        // Create ZIP file
        $zipFile = storage_path('app/private/documents.zip');
        $zip = new ZipArchive();

        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($pdfFiles as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
        }

        // Delete individual PDFs after zipping
        foreach ($pdfFiles as $file) {
            unlink($file);
        }

        return response()->download($zipFile)->deleteFileAfterSend(true);
    }
    protected function loadNgoData($locale = 'en', $id)
    {
        $id = 1;
        $locale = 'en';
        $ngo = DB::table('ngos as n')
            ->where('n.id', $id)
            ->join('ngo_trans as nt', function ($join) use ($locale) {
                $join->on('nt.ngo_id', '=', 'n.id')
                    ->where('nt.language_name', $locale);
            })
            ->join('contacts as c', 'c.id', '=', 'n.contact_id')
            ->join('emails as e', 'e.id', '=', 'n.email_id')
            ->join('addresses as a', 'a.id', '=', 'n.address_id')
            ->join('address_trans as at', function ($join) use ($locale) {
                $join->on('at.address_id', '=', 'a.id')
                    ->where('at.language_name', $locale);
            })
            ->join('district_trans as dt', function ($join) use ($locale) {
                $join->on('dt.district_id', '=', 'a.district_id')
                    ->where('dt.language_name', $locale);
            })
            ->join('province_trans as pt', function ($join) use ($locale) {
                $join->on('pt.province_id', '=', 'a.province_id')
                    ->where('pt.language_name', $locale);
            })
            ->join('country_trans as ct', function ($join) use ($locale) {
                $join->on('ct.country_id', '=', 'n.place_of_establishment')
                    ->where('ct.language_name', $locale);
            })
            ->select(
                'n.id',
                'n.registration_no',
                'n.moe_registration_no',
                'n.abbr',
                'n.date_of_establishment',
                'nt.name',
                'nt.vision',
                'nt.mission',
                'nt.general_objective',
                'nt.objective',
                'c.value as contact',
                'e.value as email',
                'dt.value as district',
                'dt.district_id',
                'at.area',
                'pt.value as province',
                'pt.province_id',
                'ct.value as country',
            )
            ->first();

        $director =  DB::table('directors as d')
            ->where('d.ngo_id', $id)
            ->where('d.is_active', true)
            ->join('director_trans as dirt', function ($join) use ($locale) {
                $join->on('dirt.director_id', '=', 'd.id')
                    ->where("dirt.language_name", $locale);
            })
            ->join('addresses as a', 'a.id', '=', 'd.address_id')
            ->join('address_trans as at', function ($join) use ($locale) {
                $join->on('at.address_id', '=', 'a.id')
                    ->where('at.language_name', $locale);
            })
            ->join('district_trans as dt', function ($join) use ($locale) {
                $join->on('dt.district_id', '=', 'a.district_id')
                    ->where('dt.language_name', $locale);
            })
            ->join('province_trans as pt', function ($join) use ($locale) {
                $join->on('pt.province_id', '=', 'a.province_id')
                    ->where('pt.language_name', $locale);
            })
            ->join('country_trans as ct', function ($join) use ($locale) {
                $join->on('ct.country_id', '=', 'd.country_id')
                    ->where('ct.language_name', $locale);
            })
            ->select(
                'dirt.name',
                'dirt.last_name',
                'dt.value as district',
                'dt.district_id',
                'pt.value as province',
                'pt.province_id',
                'ct.value as country',
                'at.area',
            )
            ->first();
        if (!$director) {
            return "Director not found";
        }
        $irdDirector = DB::table('staff as s')
            ->where('s.staff_type_id', StaffEnum::director->value)
            ->join('staff_trans as st', function ($join) use ($locale) {
                $join->on('st.staff_id', '=', 's.id')
                    ->where("st.language_name", $locale);
            })
            ->select(
                'st.name',
            )
            ->first();
        if (!$irdDirector) {
            return "IRD Director not found";
        }
        $data = [
            'register_number' => $ngo->registration_no,
            'date_of_sign' => '................',
            'ngo_name' =>  $ngo->name,
            'abbr' => $ngo->abbr,
            'contact' => $ngo->contact,
            'address' => $ngo->area . ',' . $ngo->district . ',' . $ngo->province . ',' . $ngo->country,
            'director' => $director->name . " " . $director->last_name,
            'director_address' =>  $director->area . ',' . $director->district . ',' . $director->province . ',' . $director->country,
            'email' => $ngo->email,
            'establishment_date' => $ngo->date_of_establishment,
            'place_of_establishment' => $ngo->country,
            'ministry_economy_no' => $ngo->moe_registration_no,
            'general_objective' => $ngo->general_objective,
            'afganistan_objective' => $ngo->objective,
            'mission' => $ngo->mission,
            'vission' => $ngo->vision,
            'ird_director' => $irdDirector->name,
        ];


        return $data;
    }
}
