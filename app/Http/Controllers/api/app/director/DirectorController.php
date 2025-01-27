<?php

namespace App\Http\Controllers\api\app\director;

use App\Enums\LanguageEnum;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Director;
use App\Models\DirectorTran;
use App\Models\Email;
use Illuminate\Http\Request;

class DirectorController extends Controller
{
    //

    public function store(Request $request, $ngoId)
{
    // Validate the required fields for creating a Director
    $validated = $request->validate([
        'nid_no' => 'required|string|max:20',
        'gender_id' => 'required|integer',       
        'director_country_id' => 'required|integer|exists:coutries,id',    
        'director_email' => 'required|email|unique:emails,value',
        'director_contact' => 'required|unique:contacts,value',
        'director_district_id' => 'required|integer|exists:districts,id',
        'director_area' => 'required|string|max:255',
        'director_name_pastho' => 'required|string|max:255',
        'director_last_name_pastho' => 'required|string|max:255',
        'director_name_farsi' => 'required|string|max:255',
        'director_last_name_farsi' => 'required|string|max:255',
        'director_name_english' => 'required|string|max:255',
        'director_last_name_english' => 'required|string|max:255',
    ]);

      $email = Email::create(['value' => $validated['director_email']]);

        $contact = Contact::create(['value' => $validated['director_contact']]);

        // Create address
        $address = Address::create([
            'district_id' => $validated['director_district_id'],
            'area' => $validated['director_area'],
        ]);


    $profile =    $this->storeProfile($request,'director-profile');
    $nid_attach =$this->storeDocument($request,'private','document/director-Nid');

    // Create the Director record
    $director = Director::create([
        'ngo_id' => $ngoId,
        'nid_no' => $validated['nid_no'],
        'nid_attachment' => $nid_attach['path'], 
         'profile'   =>  $profile,
        'nid_type_id' => $validated['nid_type_id'],      
        'is_Active' => 1,                            
        'gender_id' => $validated['gender_id'],      
        'country_id' => $validated['director_country_id'],
        'address_id' => $address->id,
        'email_id' => $email->id,
        'contact_id' => $contact->id,
    ]);

    // Define available languages and create/update translations
    $languages = ['ps', 'fa', 'en'];

       DirectorTran::create(
        [
           'director_id' =>$director->id,
           'language_name' =>'en',
           'name' =>$validated['name_english'] ,
           'last_name' =>$validated['last_name_english']
        ]
        );
        DirectorTran::create(
        [
           'director_id' =>$director->id,
           'language_name' =>'ps',
           'name' =>$validated['name_pashto'] ,
           'last_name' =>$validated['last_name_pastho']
        ]
        );
         DirectorTran::create(
        [
           'director_id' =>$director->id,
           'language_name' =>'fa',
           'name' =>$validated['name_farsi'] ,
           'last_name' =>$validated['last_name_farsi']
        ]
        );
   

    return response()->json(['message' => 'Director created successfully', 'director' => $director], 201);
}

}
