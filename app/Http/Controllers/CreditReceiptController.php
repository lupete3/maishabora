<?php

// app/Http/Controllers/CreditReceiptController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Credit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CreditReceiptController extends Controller
{
    public function generate($id)
    {
        $credit = Credit::with(['user', 'repayments'])->findOrFail($id);
        $member = $credit->user;
        $agent = User::find(Auth::user()->id);
        $firstRepayment = $credit->repayments->sortBy('due_date')->first();

        $data = compact('credit', 'member', 'agent', 'firstRepayment');

        return view('receipts.credit-receipt', compact('credit', 'member', 'agent', 'firstRepayment'));

    }
}
