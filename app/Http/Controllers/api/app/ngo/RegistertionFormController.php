<?php

namespace App\Http\Controllers\api\app\ngo;

use App\Enums\pdfFooter\PdfFooterEnum;
use App\Enums\StaffEnum;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Director;
use App\Models\Ngo;
use App\Models\Staff;
use Illuminate\Http\Request;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use App\Traits\Report\PdfGeneratorTrait;
use App\Traits\Address\AddressTrait;

class RegistertionFormController extends Controller
{
    //
    use PdfGeneratorTrait,AddressTrait;




    public function generateForm(Request $request,$id){



        $mpdf =  $this->generatePdf();
        

        $this->setWatermark($mpdf);

        $lang =$request->input('language_name');
        $this->setFooter($mpdf,PdfFooterEnum::REGISTER_FOOTER->value);

        // $lang='en';
        // $id =1;
        $data =$this->loadNgoData($lang,$id);

 
        $this->pdfFilePart($mpdf,"ngo.registeration.{$lang}.registeration",$data);
        // Write additional HTML content

        // $mpdf->AddPage();


        // Output the generated PDF to the browser
        return $mpdf->Output('document.pdf', 'I'); // Stream PDF to browser

    }

    protected function loadNgoData($lang,$id){

                    
                    $ngo = Ngo::with([
                            'ngoTrans' => function ($query) use ($lang){
                                $query->select('ngo_id','name','vision','mission','general_objective','objective')->where('language_name',$lang);

                            },
                            'email:id,value',
                            'contact:id,value',
                

                        ]   

                    )->select(
                        'id',
                        'email_id',
                        'contact_id',
                        'address_id',
                        'abbr',
                        'registration_no',
                        'date_of_establishment',
                        'place_of_establishment',
                        'moe_registration_no',

                    )->where('id',$id)->first();

                $director =Director::with([
                    'directorTrans' =>function ($query) use ($lang){
                        $query->select('name','last_name','director_id')->where('language_name',$lang);

                    }
                ])
                ->select('id','address_id')->where('ngo_id',$id)->first();

                $irdDirector = Staff::with([
                    'staffTran' =>function ($query) use ($lang){
                        $query->select('staff_id','name','last_name')->where('language_name',$lang);
                    } 
                ])->select('id')->where('staff_type_id',StaffEnum::director->value)->first();
                

                $ird_dir_name= $irdDirector->staffTran[0]->name.'  '.$irdDirector->staffTran[0]->last_name;

                $ngo_address =  $this->getCompleteAddress($ngo->address_id,$lang);
                $director_address =  $this->getCompleteAddress($director->address_id,$lang);
                $country_establishment = $this->getCountry($ngo->place_of_establishment,$lang);
                    // return $ngo->ngoTrans->name;

                    
                            $data =[
                            'register_number' =>$ngo->registration_no,
                            'date_of_sign' =>'................',
                            'ngo_name' =>  $ngo->ngoTrans[0]->name ?? null,
                            'abbr' => $ngo->abbr ?? null ,
                            'contact' =>$ngo->contact->value,
                            'address' => $ngo_address['complete_address'],
                            'director' =>$director->directorTrans[0]->name.'   '.$director->directorTrans[0]->last_name,
                            'director_address' => $director_address['complete_address'],
                            'email' => $ngo->email->value,
                            'establishment_date'=>$ngo->date_of_establishment,
                            'place_of_establishment' =>$country_establishment,
                            'ministry_economy_no' =>$ngo->moe_registration_no,
                            'general_objective' => $ngo->ngoTrans[0]->general_objective ?? null,
                            'afganistan_objective' => $ngo->ngoTrans[0]->objective ?? null,
                            'mission' => $ngo->ngoTrans[0]->mission ?? null,
                            'vission' => $ngo->ngoTrans[0]->vision ?? null,
                            'ird_director' =>$ird_dir_name,
                        


        ];
        return $data;
    }




}