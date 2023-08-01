<?php

namespace App\Http\Controllers;

use App\Models\CycleVersion;
use Illuminate\Http\Request;

class CycleVersionController extends Controller
{

    public function show(request $request, CycleVersion $cycleVersion ){
        try {

            return response()->json([
                'cycleVersion' => $cycleVersion,
                'ads'=> $cycleVersion->ads
            ]);
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
