<?php

namespace Database\Seeders;

use App\Enums\CheckListTypeEnum;
use App\Models\CheckList;
use App\Models\CheckListTrans;
use App\Models\CheckListType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CheckListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $this->CheckListType();
        $this->registerationCheckList();
    }

    protected function CheckListType()
    {
        CheckListType::create(
            [
                'id' => CheckListTypeEnum::internal,
                'name' => 'Internal'
            ]
        );

        CheckListType::create(
            [
                'id' => CheckListTypeEnum::externel,
                'name' => 'External'
            ]
        );
    }

    protected function registerationCheckList()
    {
        $checklists = [

            [
                'type' => CheckListTypeEnum::internal,
                'description' => 'ngo director nid',
                'is_optional' => false,
                'file_size' => 2048,
                'translations' => [
                    ['language_name' => 'en', 'value' => 'Ngo Director Nid'],
                    ['language_name' => 'ps', 'value' => 'د نجو د ریسی تذکره'],
                    ['language_name' => 'fa', 'value' => 'تذکره ریس انجو'],
                ],
            ],
            [
                'type' => CheckListTypeEnum::internal,
                'description' => '',
                'is_optional' => false,
                'file_size' => 2048,
                'translations' => [
                    ['language_name' => 'en', 'value' => 'Article of Association'],
                    ['language_name' => 'ps', 'value' => 'د اساسنامی کاپی'],
                    ['language_name' => 'fa', 'value' => 'کاپی اساسنامه'],
                ],
            ],
            [
                'type' => CheckListTypeEnum::internal,
                'description' => '',
                'is_optional' => false,
                'file_size' => 2048,
                'translations' => [
                    ['language_name' => 'en', 'value' => 'Copy of NID or Password of GD'],
                    ['language_name' => 'ps', 'value' => 'د سکن کاپی'],
                    ['language_name' => 'fa', 'value' => 'کاپی سکن'],
                ],

            ],
        ];

        foreach ($checklists as $checklistData) {
            $checklist = CheckList::create([
                'check_list_type_id' => $checklistData['type'],
                'acceptable_extensions' => "pdf,png",
                'acceptable_mimes' => ".pdf,.jpeg,.jpg,.png",
                'description' => $checklistData['description'],
                'file_size' => $checklistData['file_size'],
            ]);

            foreach ($checklistData['translations'] as $translation) {
                $translation['check_list_id'] = $checklist->id;
                CheckListTrans::create($translation);
            }
        }
    }
}
