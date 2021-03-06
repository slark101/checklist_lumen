<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use App\Models\Item;
use App\Models\Checklist;
use App\Http\Transformers\ItemTransformer;

class ItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index($checklistid)
    {
        $data = Item::where('checklist_id',$checklistid)->get();
        return $this->collection($data,new ItemTransformer());
    }

    public function store($checklistid, Request $request)
    {
        $user = Auth::user();
        $checklist = Checklist::find($checklistid);
        if(!$checklist) {
            abort(404,'Not Found');
        }
        $data = $request->json()->get('data')['attributes'];
        $validator = Validator::make(
            $data,
            [
                'description'=>'string|required',
                'urgency'=>'nullable|integer',
                'due'=>'nullable|date',
                'assignee_id'=>'nullable|integer',
                ]
        );
        if($validator->fails()) {
            return $this->respondWithErrorMessage($validator);
        }
        $item = new Item();
        $item->description = $data['description'];
        $item->urgency = $data['urgency'];
        $item->due = $data['due'];
        $item->assignee_id = $data['assignee_id'];
        $item->checklist_id = $checklist->id;
        $item->task_id = $checklist->task_id;
        $item->created_by = $user->id;
        $item->updated_by = $user->id;
        $item->save();

        return $this->item($item,new ItemTransformer(),201);
    }

    public function show($checklistid,$itemid)
    {
        $data = Item::where(['checklist_id'=>$checklistid,'id'=>$itemid])->first();
        if(!$data)
        {
            abort(404,'Not Found.');
        }
        return $this->item($data,new ItemTransformer());
    }

    public function edit($checklistid,$itemid, Request $request)
    {
        $user = Auth::user();
        $item = Item::where(['checklist_id'=>$checklistid,'id'=>$itemid])->first();
        if(!$item)
        {
            abort(404,'Not Found.');
        }
        $data = $request->json()->get('data')['attributes'];
        $validator = Validator::make(
            $data,
            [
                'description'=>'string',
                'urgency'=>'nullable',
                'due'=>'nullable|date',
                'assignee_id'=>'nullable|integer',
                ]
        );
        if($validator->fails()) {
            return $this->respondWithErrorMessage($validator);
        }

        $fields = ['description','urgency','due','assignee_id'];
        foreach($data as $key=>$value) {
            if(in_array($key,$fields)) {
                $item->$key = $value;
            }
        }
        $item->updated_by = $user->id;
        $item->update();
        return $this->item($item,new ItemTransformer());
    }

    public function remove($checklistid,$itemid) {
        $item = Item::where(['checklist_id'=>$checklistid,'id'=>$itemid])->first();
        if(!$item)
        {
            abort(404,'Not Found.');
        }
        $item->delete();
        return $this->get_response(['result'=>'Item has been deleted'],204);
    }

    public function get_complete() {
        $items = Item::where('is_complete',true)->get();
        $result = array();
        foreach($items as $item) {
            $result[] = ['item_id'=>$item->id];
        }
        return $this->get_response($result);
    }

    public function get_incomplete() {
        $items = Item::where('is_complete',false)->get();
        $result = array();
        foreach($items as $item) {
            $result[] = ['item_id'=>$item->id];
        }
        return $this->get_response($result);
    }
}