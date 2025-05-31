<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RegisterMemberController extends Controller
{
    public function index()
    {
        return view("member.register-member");
    }
}
