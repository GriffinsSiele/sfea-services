<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

use Illuminate\Support\Facades\Validator;

use App\Models\Access;
use App\Models\AccessForm\RequestForm;
use App\Models\AccessForm\HiddenField;
use App\Models\AccessForm\RequestFormFieldRelation;

use Itstructure\GridView\DataProviders\EloquentDataProvider;

class AccessFormController extends Controller
{

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Access  $access
     * @return \Illuminate\Http\Response
     */
    public function forms(Access $access)
    {
        Gate::authorize('update-access', $access);

        $forms = RequestForm::orderByRaw('FIELD(Code, "PhoneForm", "OrgForm", "PersonForm") DESC')->get();

        $fieldsMap = $access->fromHiddenFieldsMap();

        return view('private.access.forms')
            ->with('pageTitle', 'Управление полями форм/запросов')
            ->with('access', $access)
            ->with('fieldsMap', $fieldsMap)
            ->with('forms', $forms);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Access  $access
     * @return \Illuminate\Http\Response
     */
    public function hideField(Request $request, Access $access)
    {
        Gate::authorize('update-access', $access);


        if(
            $request->input('objectCode', false)
            && $request->input('fieldCode', false)
            && $request->input('hide', false) !== false
        ) {
            $rffr = RequestFormFieldRelation::where('ObjectCode', '=', $request->input('objectCode', false))
                ->where('FieldCode', '=', $request->input('fieldCode', false))->first();

            if($rffr) {
                if($request->input('hide')) {
                    $hf = new HiddenField();

                    $hf->RFFRelationId = $rffr->Id;
                    $hf->AccessId = $access->Level;

                    $hf->save();
                }
                else
                    HiddenField::where('RFFRelationId', '=', $rffr->Id)
                        ->where('AccessId', '=', $access->Level)->delete();
            }
        }
        else
            return response()->json(['status' => 'error']);

        return response()->json(['status' => 'ok']);
    }

}
