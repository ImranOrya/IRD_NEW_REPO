<?php

namespace App\Http\Controllers\api\app\pendingTask;

use App\Http\Controllers\Controller;
use App\Models\PendingTask;
use App\Models\PendingTaskDocument;
use Illuminate\Http\Request;

class PendingTaskController extends Controller
{
    //

    public function storeContent(Request $request){
    
        
     

        if($request->pending_id){

            $request->validate([
            'contents' =>'required|string'
        ]);

         $pendingTask =   PendingTask::find($request->pending_id);

         $pendingTask->content = $request->contents;
         $pendingTask->save();

                    return response()->json(
            [
                'message' => __('app_translation.success'),

            ],
           
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
        }
        else{
            
            $request->validate([
            'contents' =>'required|string',
            'task_type' =>'required',

        ]);



            return PendingTask::create([
            "task_type" => $request->task_type,
            "content" => $request->contents,
            "user_id" => $request->user()->id,
            "user_type" => $request->user()->role_id,
        ]);


          return response()->json(
            [
                'message' => __('app_translation.success'),

            ],
           
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
        }







    }


    public function content(Request $request){


        if($request->pending_id){

        }


    }

    protected function contentByPendingId($request){

        $user =$request->user();
        $pending_id =$request->pending_id;

        $data = PendingTask::where('id',$pending_id)
        ->where('user_type',$user->role_id)
        ->where('user_id',$user->id)
        ->select('content')->first();

        $Document  = PendingTaskDocument::where('pending_task_id',$pending_id)->get();

         return response()->json(
            [
                'message' => __('app_translation.success'),
                'contents' => $data->content,
               

            ],
           
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );

    }
}
