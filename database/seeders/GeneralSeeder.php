<?php

namespace Database\Seeders;

use App\Models\Gender;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GeneralSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $this->gender();
    }

    protected function gender()  {

    Gender::create([
        'name_en' =>'Male',
        'name_fa' =>'مرد',
        'name_ps' =>'نارینه'
    ]);   
    
      Gender::create([
        'name_en' =>'Famale',
        'name_fa' =>'زن',
        'name_ps' =>'ښځینه'
    ]); 
        
    }
}
