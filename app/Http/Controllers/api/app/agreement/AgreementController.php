<?php

namespace App\Http\Controllers\api\app\agreement;

use App\Models\Agreement;
use App\Models\CheckList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use App\Repositories\ngo\NgoRepositoryInterface;

class AgreementController extends Controller
{
    protected $ngoRepository;

    public function __construct(
        NgoRepositoryInterface $ngoRepository
    ) {
        $this->ngoRepository = $ngoRepository;
    }
    public function agreementDocuments(Request $request)
    {
        $ngo_id = $request->input('ngo_id');
        $agreement_id = $request->input('agreement_id');

        $locale = App::getLocale();
        $query = $this->ngoRepository->ngo($ngo_id);
        $documents = $this->ngoRepository->agreementDocuments($query, $agreement_id, $locale);

        return response()->json([
            'agreement_documents' => $documents,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function agreement(Request $request, $id)
    {
        $data = Agreement::select('id', 'start_date', 'end_date')->where('ngo_id', $id)->get();
        return response()->json([
            'message' => __('app_translation.success'),
            'agreement' => $data,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function agreementDocument(Request $request, $id)
    {

        $locale = App::getLocale();
        $tr = CheckList::join('check_list_trans as ct', 'ct.check_list_id', '=', 'check_lists.id')
            ->join('documents as doc', 'doc.check_list_id', 'check_lists.id')
            ->join('agreement_documents as agr', 'agr.document_id', 'doc.id')
            ->where('ct.language_name', $locale)
            ->where('agr.agreement_id', $id)
            ->select('check_lists.id as check_list_id', 'ct.value as check_list_name',  'doc.id as document_id', 'doc.type', 'check_lists.description', 'doc.path', 'doc.actual_name')
            ->orderBy('check_lists.id')
            ->get();

        return response()->json([
            'document' => $tr
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
