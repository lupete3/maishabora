@extends('layouts.backend')

@section('title', 'Informations de l\'Entreprise')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Paramètres /</span> Informations de l'Entreprise</h4>

    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card mb-4">
                <h5 class="card-header bg-primary text-white">
                    <i class="bx bx-building me-2"></i> Configuration de l'Entreprise
                </h5>
                <div class="card-body mt-3">
                    <form action="{{ route('company.information.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('POST')

                        <div class="row">
                            <!-- Logo Section -->
                            <div class="col-12 mb-4">
                                <label class="form-label d-block">Logo de l'entreprise</label>
                                <div class="d-flex align-items-start align-items-sm-center gap-4">
                                    @if($company && $company->logo_path)
                                        <img src="{{ asset($company->logo_path) }}" alt="logo" class="d-block rounded" height="100" width="100" id="uploadedAvatar" />
                                    @else
                                        <img src="{{ asset('assets/img/logo.jpg') }}" alt="logo default" class="d-block rounded" height="100" width="100" id="uploadedAvatar" />
                                    @endif
                                    <div class="button-wrapper">
                                        <label for="upload" class="btn btn-outline-primary me-2 mb-4" tabindex="0">
                                            <span class="d-none d-sm-block">Envoyer un nouveau logo</span>
                                            <i class="bx bx-upload d-block d-sm-none"></i>
                                            <input type="file" id="upload" name="logo" class="account-file-input" hidden accept="image/png, image/jpeg" />
                                        </label>
                                        <p class="text-muted mb-0">Autorisé : JPG, GIF ou PNG. Taille max : 2Mo</p>
                                        @error('logo') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3">

                            <!-- Basic Info -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nom de l'entreprise <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $company->name ?? '') }}" required placeholder="Ex: Maisha Bora">
                                    @error('name') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Téléphone</label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i class="bx bx-phone"></i></span>
                                        <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $company->phone ?? '') }}" placeholder="+243 ...">
                                    </div>
                                    @error('phone') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="address" class="form-label">Adresse Physique</label>
                                    <textarea class="form-control" id="address" name="address" rows="2" placeholder="Numéro, Avenue, Commune, Ville...">{{ old('address', $company->address ?? '') }}</textarea>
                                    @error('address') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email de contact</label>
                                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $company->email ?? '') }}" placeholder="contact@maishabora.com">
                                    @error('email') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="rccm" class="form-label">Numéro RCCM</label>
                                    <input type="text" class="form-control" id="rccm" name="rccm" value="{{ old('rccm', $company->rccm ?? '') }}">
                                    @error('rccm') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ifu" class="form-label">Numéro IFU / ID Nat</label>
                                    <input type="text" class="form-control" id="ifu" name="ifu" value="{{ old('ifu', $company->ifu ?? '') }}">
                                    @error('ifu') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="currency" class="form-label">Devise par défaut</label>
                                    <select class="form-select" id="currency" name="currency">
                                        <option value="USD" {{ old('currency', $company->currency ?? 'USD') == 'USD' ? 'selected' : '' }}>USD</option>
                                        <option value="CDF" {{ old('currency', $company->currency ?? 'USD') == 'CDF' ? 'selected' : '' }}>CDF</option>
                                        <option value="EUR" {{ old('currency', $company->currency ?? 'USD') == 'EUR' ? 'selected' : '' }}>EUR</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="currency_symbol" class="form-label">Symbole devise</label>
                                    <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" value="{{ old('currency_symbol', $company->currency_symbol ?? '$') }}">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Preview image on upload
    const uploadInput = document.getElementById('upload');
    const avatarImg = document.getElementById('uploadedAvatar');

    if (uploadInput) {
        uploadInput.onchange = () => {
            if (uploadInput.files[0]) {
                avatarImg.src = window.URL.createObjectURL(uploadInput.files[0]);
            }
        };
    }
</script>
@endpush
@endsection
