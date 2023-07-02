<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(){

        return response()->json( Auth::user()->unreadNotifications);
    }

    public function indexCount(){

        return response()->json( Auth::user()->unreadNotifications->count());
    }



    public function read(){
        Auth::user()->unreadNotifications->markAsRead();
        return response()->json( [
            'success' => 200
        ]);
    }
}
