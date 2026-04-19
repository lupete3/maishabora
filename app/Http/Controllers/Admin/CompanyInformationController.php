<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyInformationController extends Controller
{
    public function index()
    {
        $company = CompanyInformation::getActive();
        return view('admin.company.index', compact('company'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'rccm' => 'nullable|string|max:100',
            'ifu' => 'nullable|string|max:100',
            'currency' => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:5',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $company = CompanyInformation::getActive();

        $data = $request->only([
            'name', 'address', 'phone', 'email', 'rccm', 'ifu', 'currency', 'currency_symbol'
        ]);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = 'logo.jpg';
            $file->move(public_path('assets/img'), $filename);
            $data['logo_path'] = 'assets/img/' . $filename;
        }

        if ($company) {
            $company->update($data);
        } else {
            $data['is_active'] = true;
            CompanyInformation::create($data);
        }

        notyf()->success('Informations de l\'entreprise mises à jour avec succès.');

        return redirect()->back();
    }
}
