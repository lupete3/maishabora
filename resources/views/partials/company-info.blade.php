@if($company)
<span class="company-info" data-name="{{ $company->name }}" data-address="{{ $company->address }}" data-phone="{{ $company->phone }}" data-email="{{ $company->email }}" data-rccm="{{ $company->rccm }}" data-ifu="{{ $company->ifu }}">{{ $company->name }}</span>
@else
<span class="company-info">{{ config('app.name') }}</span>
@endif
