<?php

namespace App\Http\Controllers\api\app\projects;

use Mpdf\Mpdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use App\Traits\Address\AddressTrait;
use App\Enums\pdfFooter\PdfFooterEnum;
use App\Traits\Report\PdfGeneratorTrait;

class ProjectPdfController extends Controller
{
    //
    use PdfGeneratorTrait, AddressTrait;



    public function generateForm(Request $request, $id)
    {
        $mpdf = $this->generatePdf();
        $this->setWatermark($mpdf);

        $lang = 'en';
        $data = $this->loadProjectData($lang, $id);

        // return $data;
        // Set footer
        // $this->setFooter($mpdf, PdfFooterEnum::MOU_FIRST_FOOTER_en->value);

        // STEP 1: Configure TOC layout
        $mpdf->TOC([
            'toc-preHTML' => '<h1 style="text-align:center;">Table of Contents</h1><div class="toc">',
            'toc-postHTML' => '</div>',
            'toc-bookmarkText' => 'Table of Contents',
            'paging' => true,
            'links' => true,
        ]);

        // ✅ STEP 2: Insert TOC page here


        // STEP 3: Render Blade view (with <tocentry> tags inside)
        $this->pdfFilePart($mpdf, "project.mou.pdf.{$lang}.mouFirstPart", $data);

        $mpdf->WriteHTML('<tocpagebreak />');
        $mpdf->WriteHTML('
        <style>
            .toc a {
                cursor: pointer;
                color: blue;
                text-decoration: underline;
            }
        </style>
    ');


        // Part 2: Content before the table (still portrait)
        $this->pdfFilePart($mpdf, "project.mou.pdf.{$lang}.mouSecondPart_PortraitBeforeTable", $data);

        // LANDSCAPE PART (Health Facilities Table)
        $mpdf->AddPage('L'); // 🔄 switch to landscape
        $this->pdfFilePart($mpdf, "project.mou.pdf.{$lang}.mouSecondPart_LandscapeTableOnly", $data);
        $mpdf->AddPage('P'); // 🔙 back to portrait

        // Rest of the content
        $this->pdfFilePart($mpdf, "project.mou.pdf.{$lang}.mouSecondPart_PortraitAfterTable", $data);


        // // Part 2: Content before the table (still portrait)
        // $this->pdfFilePart($mpdf, "project.mou.pdf.{$lang}.mouSecondPart_PortraitBeforeTable", $data);

        // // LANDSCAPE PART (Health Facilities Table)
        // $mpdf->AddPage('L'); // 🔄 switch to landscape
        // $this->pdfFilePart($mpdf, "project.mou.pdf.{$lang}.mouSecondPart_LandscapeTableOnly", $data);
        // $mpdf->AddPage('P'); // 🔙 back to portrait

        // // Rest of the content
        // $this->pdfFilePart($mpdf, "project.mou.pdf.{$lang}.mouSecondPart_PortraitAfterTable", $data);

        // // Done
        return $mpdf->Output('document.pdf', 'I');
    }








    protected function loadProjectData($lang, $id)
    {





        $locale = App::getLocale(); // e.g., 'en', 'fa', 'ps'

        // 1. Main project info with joined donor/currency
        $project = DB::table('projects as p')
            ->leftJoin('donor_trans as d', 'd.donor_id', '=', 'p.donor_id')
            ->leftJoin('currency_trans as c', 'c.id', '=', 'p.currency_id')
            ->leftJoin('ngo_trans as nt', function ($join) use ($lang) {
                $join->on('p.ngo_id', 'nt.ngo_id')
                    ->where('nt.language_name', $lang);
            })
            ->leftJoin('project_trans as prot', function ($join) use ($lang) {
                $join->on('p.ngo_id', 'nt.ngo_id')
                    ->where('nt.language_name', $lang);
            })
            ->where('p.id', $id)
            ->select(
                'p.*',
                'prot.*',
                'nt.name as ngo_name',
                'nt.vision as ngo_vision',
                'nt.mission as ngo_mission',
                'nt.introduction as ngo_introduction',
                'd.name as donor_name',
                'c.name as currency_name',

            )
            ->first();

        // return $project;

        $focal_point = DB::table('project_managers as pro')
            ->where('pro.id', $project->project_manager_id)
            ->join('emails as e', 'e.id', '=', 'pro.email_id')
            ->join('contacts as c', 'c.id', '=', 'pro.contact_id')
            ->select(
                'c.value as contact',
                'e.value as email'
            )->first();
        $focal_point = 'Number:' . $focal_point->contact . ',' . 'Email:' . $focal_point->email;


        $ngo_director = DB::table('directors as dir')
            ->where('ngo_id', $project->ngo_id)
            ->where('is_active', true)
            ->join('emails as e', 'e.id', '=', 'dir.email_id')
            ->join('contacts as c', 'c.id', '=', 'dir.contact_id')
            ->select(
                'c.value as contact',
                'e.value as email'
            )->first();
        $ngo_director = 'Number:' . $ngo_director->contact . ',' . 'Email:' . $ngo_director->email;

        // 2. Project translation in selected locale
        $tran = DB::table('project_trans')
            ->where('project_id', $id)
            ->where('language_name', $locale)
            ->first();

        // 3. Project details + translations
        $detailTrans = DB::table('project_details as pd')
            ->leftJoin('project_detail_trans as pdt', 'pdt.project_detail_id', '=', 'pd.id')
            ->leftJoin('province_trans as prot', function ($join) use ($lang) {
                $join->on('prot.province_id', '=', 'pd.province_id')
                    ->where('prot.language_name', $lang);
            })
            ->where('pd.project_id', $id)
            ->where('pdt.language_name', $locale)
            ->select(
                'pd.province_id',
                'prot.value as province_name',
                'pd.budget',
                'pd.direct_beneficiaries',
                'pd.in_direct_beneficiaries',
                'pdt.health_center',
                'pdt.address',
                'pdt.health_worker',
                'pdt.managment_worker'
            )
            ->get();

        $direct_beneficiaries = $detailTrans->sum('direct_beneficiaries');
        $in_direct_beneficiaries = $detailTrans->sum('in_direct_beneficiaries');

        $provinceList = $detailTrans
            ->pluck('province_name')
            ->filter()         // remove null values
            ->unique()         // remove duplicates
            ->implode(',');    // join into comma-separated string



        $data['project_provinces'] = $provinceList;

        $health_worker = $detailTrans
            ->pluck('health_worker')
            ->filter()         // remove null values
            ->unique()         // remove duplicates
            ->implode(',');    // join into comma-separated string



        $data['health_worker'] = $health_worker;

        $managment_worker = $detailTrans
            ->pluck('managment_worker')
            ->filter()         // remove null values
            ->unique()         // remove duplicates
            ->implode(',');    // join into comma-separated string



        $data['managment_worker'] = $managment_worker;







        // 4. Convert health centers (json) to readable list
        $healthFacilities = $detailTrans->map(function ($row) {
            $facilities = json_decode($row->health_center ?? '[]', true);

            // Ensure it's an array before implode
            if (!is_array($facilities)) {
                $facilities = [$facilities]; // or: $facilities = explode(',', $facilities);
            }

            return [
                'province_id' => $row->province_id,
                'facilities' => implode(', ', $facilities),
            ];
        });




        $data = [
            'preamble' => $project->preamble,
            'ngo_name' => $project->name,
            'introduction_ngo' => $project->ngo_introduction,
            'abbr' => $project->terminologies,
            'org_vision' => $project->ngo_vision,
            'org_mission' =>  $project->ngo_mission,
            'org_management_working_area' => $project->organization_senior_manangement,
            'project_structure' => $project->project_structure,
            'backgroud_experince' => $project->health_experience,
            'provision_health_service' => $project->prev_proj_activi,
            'introduction_current_project' => $project->introduction,
            'health_facilities' => [
                ['province' => 'kabul', 'facilities' => 'arzan_qimat'],
                ['province' => 'logor', 'facilities' => 'pole alam']
            ],
            'goals' => $project->goals,
            'objectives' => $project->objectives,
            'expected_outcomes' => $project->expected_outcome,
            'expected_impact' => $project->expected_impact,
            'subject' => $project->subject,
            'activities' => $project->main_activities,
            'implementing_org' => $project->ngo_name,
            'funder' => $project->donor_name,
            'budget' => $project->total_budget,
            'start_date' => $project->start_date,
            'end_date' => $project->end_date,
            'mou_date' => $project->created_at,
            'location' => '',
            'provinces' => $provinceList,
            'areas' => 'areas',
            'direct_beneficiaries' => $direct_beneficiaries,
            'indirect_beneficiaries' => $in_direct_beneficiaries,
            'org_structure' => $project->project_structure,
            'health_staff' => $health_worker,
            'admin_staff' => $managment_worker,
            'action_plan' => $project->operational_plan,
            'ngo_director_contact' => $ngo_director,
            'project_focal_point_contact' => $focal_point,
            'project_provinces' => $provinceList,



            'director' => '................',


            'ird_director' => '................',



        ];
        return $data;
    }
}
