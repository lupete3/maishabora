<!-- Modal -->
<style>
    .nav-tabs .nav-link.active {
        background-color: #f6a61cda;
        /* bleu Bootstrap */
        color: #fff;
        border-color: #f6a61cda #f6a61cda #fff;
        border-radius: 10px;
        margin: 2px;
    }

    .nav-tabs .nav-link {
        color: #55a518e9;
    }
</style>
<div class="modal fade" id="modalMembre" tabindex="-1" aria-labelledby="modalMembreLabel" aria-hidden="true"
    data-focus="false" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form wire:submit.prevent="{{ $editModal ? 'update' : 'submitForm' }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMembreLabel">{{ __("Information du client") }}
                    </h5>
                    <button type="button" class="btn-close" aria-label="Close" wire:click='closeModal'></button>
                </div>
                <div class="modal-body">

                    @if (!$editModal)
                    {{-- Create Form (Multi-step) --}}
                    <div class="tab-content mt-3">
                        <!-- Step 1: Infos de base -->
                        <div class="@if ($currentStep != 1) d-none @endif">
                            <div class="row g-3">
                                <!-- Nom -->
                                <div class="col-md-4 mb-1">
                                    <label for="name" class="form-label">Nom</label>
                                    <input type="text" wire:model.defer="name" id="name" class="form-control"
                                        placeholder="Nom" required autofocus />
                                    @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <!-- Postnom -->
                                <div class="col-md-4 mb-1">
                                    <label for="postnom" class="form-label">Postnom</label>
                                    <input type="text" wire:model.defer="postnom" id="postnom" class="form-control"
                                        placeholder="Postnom" required />
                                    @error('postnom') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <!-- Prenom -->
                                <div class="col-md-4 mb-1">
                                    <label for="prenom" class="form-label">Prénom (optionnel)</label>
                                    <input type="text" wire:model.defer="prenom" id="prenom" class="form-control"
                                        placeholder="Prénom" />
                                    @error('prenom') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <!-- Sexe -->
                                <div class="col-md-4 mb-1">
                                    <label class="form-label">Sexe</label>
                                    <select wire:model.defer="sexe" class="form-select">
                                        <option value="">Choisir un sexe </option>
                                        <option value="Masculin">Masculin</option>
                                        <option value="Féminin">Féminin</option>
                                    </select>
                                    @error('sexe') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <!-- Date de naissance -->
                                <div class="col-md-4 mb-1">
                                    <label for="date_naissance" class="form-label">Date de naissance</label>
                                    <input type="date" wire:model.defer="date_naissance" id="date_naissance"
                                        class="form-control" required />
                                    @error('date_naissance') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <!-- Téléphone -->
                                <div class="col-md-4 mb-1">
                                    <label for="telephone" class="form-label">Téléphone</label>
                                    <input type="text" wire:model.defer="telephone" id="telephone"
                                        class="form-control" placeholder="+243..." required />
                                    @error('telephone') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <!-- Profession -->
                                <div class="col-md-4 mb-1">
                                    <label for="profession" class="form-label">Profession</label>
                                    <input type="text" wire:model.defer="profession" id="profession"
                                        class="form-control" placeholder="Ex: Agriculteur" />
                                    @error('profession') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <!-- Email -->
                                <div class="col-md-8 mb-1">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" wire:model.defer="email" id="email" class="form-control"
                                        placeholder="exemple@domaine.com" required />
                                    @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <!-- Status physique -->
                                <div class="col-md-4 mb-1">
                                    <label for="adresse_physique" class="form-label">Status</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="status"
                                            wire:model.defer="status">
                                        <label class="form-check-label" for="status">Actif</label>
                                    </div>
                                    @error('status') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <!-- Adresse physique -->
                                <div class="col-md-8 mb-1">
                                    <label for="adresse_physique" class="form-label">Adresse physique</label>
                                    <input type="text" wire:model.defer="adresse_physique" id="adresse_physique"
                                        class="form-control">
                                    @error('adresse_physique') <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Identité officielle et état civil -->
                        <div class="@if ($currentStep != 2) d-none @endif">
                            <div class="row g-3">
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Type de pièce</label>
                                    <input type="text" wire:model.defer="type_piece" class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Numéro de pièce</label>
                                    <input type="text" wire:model.defer="numero_piece" class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Expiration pièce</label>
                                    <input type="date" wire:model.defer="date_expiration_piece"
                                        class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">État civil</label>
                                    <select wire:model.defer="etat_civil" class="form-select">
                                        <option value="">Choisir...</option>
                                        <option value="célibataire">Célibataire</option>
                                        <option value="marié">Marié</option>
                                        <option value="divorcé">Divorcé</option>
                                        <option value="veuf">Veuf</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Nombre de dépendants</label>
                                    <input type="number" wire:model.defer="nombre_dependants" class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Lieu de naissance</label>
                                    <input type="text" wire:model.defer="lieu_naissance" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- Step 3 : Données économiques et professionnelles -->
                        <div class="@if ($currentStep != 3) d-none @endif">
                            <div class="row g-3">
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Revenu mensuel</label>
                                    <input type="number" wire:model.defer="revenu_mensuel" class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Source de revenu</label>
                                    <input type="text" wire:model.defer="source_revenu" class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Employeur</label>
                                    <input type="text" wire:model.defer="nom_employeur" class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Nom conjoint</label>
                                    <input type="text" wire:model.defer="nom_conjoint" class="form-control">
                                </div>
                                <div class="col-md-12 mb-1">
                                    <label class="form-label">Téléphone conjoint</label>
                                    <input type="text" wire:model.defer="telephone_conjoint"
                                        class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- Step 4 : Références et Localisation -->
                        <div class="@if ($currentStep != 4) d-none @endif">
                            <div class="row g-3">
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Nom référence</label>
                                    <input type="text" wire:model.defer="nom_reference" class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Téléphone référence</label>
                                    <input type="text" wire:model.defer="telephone_reference"
                                        class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Lien avec référence</label>
                                    <input type="text" wire:model.defer="lien_reference" class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Province</label>
                                    <select name="province" wire:model.defer="province" class="form-control">
                                        <option value="">Choisir province</option>
                                        <option value="Sud-Kivu">Sud-Kivu</option>
                                        <option value="Nord-Kivu">Nord-Kivu</option>
                                        <option value="Katanga">Katanga</option>
                                        <option value="Autre">Autre</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Ville</label>
                                    <select name="ville" wire:model.defer="ville" class="form-control">
                                        <option value="">Choisir ville</option>
                                        <option value="Bukavu">Bukavu</option>
                                        <option value="Uvira">Uvira</option>
                                        <option value="Kamituga">Kamituga</option>
                                        <option value="Autre">Autre</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Commune</label>
                                    <select name="commune" wire:model.defer="commune" class="form-control">
                                        <option value="">Choisir commune</option>
                                        <option value="Ibanda">Ibanda</option>
                                        <option value="Kadutu">Kadutu</option>
                                        <option value="Bagira">Bagira</option>
                                        <option value="Autre">Autre</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Quartier</label>
                                    <input type="text" wire:model.defer="quartier" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- Step 5 : Média et Documents -->
                        <div class="@if ($currentStep != 5) d-none @endif">
                            <div class="row g-3">
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Photo profil</label>
                                    <input type="file" wire:model="photo_profil" class="form-control"
                                        accept="image/*">
                                    @error('photo_profil')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    @if ($photo_profil)
                                    <div class="mt-2">
                                        <img src="{{ $photo_profil->temporaryUrl() }}" class="img-thumbnail"
                                            style="max-width: 200px;">
                                    </div>
                                    @endif
                                </div>

                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Scan pièce</label>
                                    <input type="file" wire:model="scan_piece" class="form-control"
                                        accept="image/*,.pdf">
                                    @error('scan_piece')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Date d’adhésion</label>
                                    <input type="date" wire:model.defer="date_adhesion" class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Nationalité</label>
                                    <input type="text" wire:model.defer="nationalite" class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Niveau d’étude</label>
                                    <select name="niveau_etude" wire:model.defer='niveau_etude'
                                        class="form-control">
                                        <option value="">Choisir niveau</option>
                                        <option value="Primaire">Primaire</option>
                                        <option value="Sécondaire">Sécondaire</option>
                                        <option value="Grade">Grade</option>
                                        <option value="Licence">Licence</option>
                                        <option value="Master">Master</option>
                                        <option value="Doctorat">Doctorat</option>
                                        <option value="N/A">N/A</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-1">
                                    <label class="form-label">Remarque</label>
                                    <textarea wire:model.defer="remarque" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    {{-- Edit Form (Tabs) --}}
                    <ul class="nav nav-tabs" id="membreTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="infos-tab" data-bs-toggle="tab"
                                data-bs-target="#infos" type="button" role="tab">Infos de base</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="identite-tab" data-bs-toggle="tab"
                                data-bs-target="#identite" type="button" role="tab">Identité & État civil</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="eco-tab" data-bs-toggle="tab" data-bs-target="#eco"
                                type="button" role="tab">Économie & Pro</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="reference-tab" data-bs-toggle="tab"
                                data-bs-target="#reference" type="button" role="tab">Références &
                                Localisation</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="media-tab" data-bs-toggle="tab" data-bs-target="#media"
                                type="button" role="tab">Média / Docs</button>
                        </li>
                    </ul>

                    <div class="tab-content mt-3">
                        <!-- Onglet 1 : Infos de base -->
                        <div class="tab-pane fade show active" id="infos" role="tabpanel">
                            <div class="row g-3">

                                <!-- Nom -->
                                <div class="col-md-4 mb-1">
                                    <label for="name" class="form-label">Nom</label>
                                    <input type="text" wire:model.defer="name" id="name" class="form-control"
                                        placeholder="Nom" required autofocus />
                                    @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <!-- Postnom -->
                                <div class="col-md-4 mb-1">
                                    <label for="postnom" class="form-label">Postnom</label>
                                    <input type="text" wire:model.defer="postnom" id="postnom"
                                        class="form-control" placeholder="Postnom" required />
                                    @error('postnom') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <!-- Prenom -->
                                <div class="col-md-4 mb-1">
                                    <label for="prenom" class="form-label">Prénom (optionnel)</label>
                                    <input type="text" wire:model.defer="prenom" id="prenom"
                                        class="form-control" placeholder="Prénom" />
                                    @error('prenom') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <!-- Prenom -->
                                <div class="col-md-4 mb-1">
                                    <label class="form-label">Sexe</label>
                                    <select wire:model.defer="sexe" class="form-select">
                                        <option value="">Choisir un sexe </option>
                                        <option value="Masculin">Masculin</option>
                                        <option value="Féminin">Féminin</option>
                                    </select>
                                    @error('sexe') <span class="text-danger">{{ $message }}</span> @enderror

                                </div>

                                <!-- Date de naissance -->
                                <div class="col-md-4 mb-1">
                                    <label for="date_naissance" class="form-label">Date de naissance</label>
                                    <input type="date" wire:model.defer="date_naissance" id="date_naissance"
                                        class="form-control" required />
                                    @error('date_naissance') <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Téléphone -->
                                <div class="col-md-4 mb-1">
                                    <label for="telephone" class="form-label">Téléphone</label>
                                    <input type="text" wire:model.defer="telephone" id="telephone"
                                        class="form-control" placeholder="+243..." required />
                                    @error('telephone') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <!-- Profession -->
                                <div class="col-md-4 mb-1">
                                    <label for="profession" class="form-label">Profession</label>
                                    <input type="text" wire:model.defer="profession" id="profession"
                                        class="form-control" placeholder="Ex: Agriculteur" />
                                    @error('profession') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <!-- Email -->
                                <div class="col-md-8 mb-1">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" wire:model.defer="email" id="email"
                                        class="form-control" placeholder="exemple@domaine.com" required />
                                    @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <!-- Status physique -->
                                <div class="col-md-4 mb-1">
                                    <label for="adresse_physique" class="form-label">Status</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="status"
                                            wire:model.defer="status">
                                        <label class="form-check-label" for="status">Actif</label>
                                    </div>
                                    @error('status') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <!-- Adresse physique -->
                                <div class="col-md-8 mb-1">
                                    <label for="adresse_physique" class="form-label">Adresse physique</label>
                                    <input type="text" wire:model.defer="adresse_physique"
                                        id="adresse_physique" class="form-control">
                                    @error('adresse_physique') <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                            </div>
                        </div>

                        <!-- Onglet 2 : Identité officielle et état civil -->
                        <div class="tab-pane fade" id="identite" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Type de pièce</label>
                                    <input type="text" wire:model.defer="type_piece" class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Numéro de pièce</label>
                                    <input type="text" wire:model.defer="numero_piece" class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Expiration pièce</label>
                                    <input type="date" wire:model.defer="date_expiration_piece"
                                        class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">État civil</label>
                                    <select wire:model.defer="etat_civil" class="form-select">
                                        <option value="">Choisir...</option>
                                        <option value="célibataire">Célibataire</option>
                                        <option value="marié">Marié</option>
                                        <option value="divorcé">Divorcé</option>
                                        <option value="veuf">Veuf</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Nombre de dépendants</label>
                                    <input type="number" wire:model.defer="nombre_dependants"
                                        class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Lieu de naissance</label>
                                    <input type="text" wire:model.defer="lieu_naissance" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- Onglet 3 : Données économiques et professionnelles -->
                        <div class="tab-pane fade" id="eco" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Revenu mensuel</label>
                                    <input type="number" wire:model.defer="revenu_mensuel"
                                        class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Source de revenu</label>
                                    <input type="text" wire:model.defer="source_revenu" class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Employeur</label>
                                    <input type="text" wire:model.defer="nom_employeur" class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Nom conjoint</label>
                                    <input type="text" wire:model.defer="nom_conjoint" class="form-control">
                                </div>
                                <div class="col-md-12 mb-1">
                                    <label class="form-label">Téléphone conjoint</label>
                                    <input type="text" wire:model.defer="telephone_conjoint"
                                        class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- Onglet 4 : Références et Localisation -->
                        <div class="tab-pane fade" id="reference" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Nom référence</label>
                                    <input type="text" wire:model.defer="nom_reference" class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Téléphone référence</label>
                                    <input type="text" wire:model.defer="telephone_reference"
                                        class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Lien avec référence</label>
                                    <input type="text" wire:model.defer="lien_reference" class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Province</label>
                                    <select name="niveau_etude" wire:model.defer="province"
                                        class="form-control">
                                        <option value="">Choisir province</option>
                                        <option value="Sud-Kivu">Sud-Kivu</option>
                                        <option value="Nord-Kivu">Nord-Kivu</option>
                                        <option value="Katanga">Katanga</option>
                                        <option value="Autre">Autre</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Ville</label>
                                    <select name="niveau_etude" wire:model.defer="ville" class="form-control">
                                        <option value="">Choisir ville</option>
                                        <option value="Bukavu">Bukavu</option>
                                        <option value="Uvira">Uvira</option>
                                        <option value="Kamituga">Kamituga</option>
                                        <option value="Autre">Autre</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Commune</label>
                                    <select name="niveau_etude" wire:model.defer="commune"
                                        class="form-control">
                                        <option value="">Choisir commune</option>
                                        <option value="Ibanda">Ibanda</option>
                                        <option value="Kadutu">Kadutu</option>
                                        <option value="Bagira">Bagira</option>
                                        <option value="Autre">Autre</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Quartier</label>
                                    <input type="text" wire:model.defer="quartier" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- Onglet 5 : Média et Documents -->
                        <div class="tab-pane fade" id="media" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Photo profil</label>
                                    <input type="file" wire:model="photo_profil" class="form-control"
                                        accept="image/*">

                                    @error('photo_profil')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror

                                    <!-- Aperçu en direct -->
                                    @if ($photo_profil)
                                    <div class="mt-2">
                                        <img src="{{ $photo_profil->temporaryUrl() }}" class="img-thumbnail"
                                            style="max-width: 200px;">
                                    </div>
                                    @elseif ($editModal && $photo_profil_url)
                                    <div class="mt-2">
                                        <img src="{{ asset('storage/' . $photo_profil_url) }}"
                                            class="img-thumbnail" style="max-width: 200px;">
                                    </div>
                                    @endif
                                </div>

                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Scan pièce</label>
                                    <input type="file" wire:model="scan_piece" class="form-control"
                                        accept="image/*,.pdf">

                                    @error('scan_piece')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror

                                    @if ($scan_piece)
                                    <div class="mt-2">
                                        @if(Str::startsWith($scan_piece->getMimeType(), 'image/'))
                                        <img src="{{ $scan_piece->temporaryUrl() }}" class="img-thumbnail"
                                            style="max-width: 200px;">
                                        @endif
                                    </div>

                                    @elseif ($editModal && $scan_piece_url)
                                    <div class="mt-2">
                                        @if(Str::endsWith($scan_piece_url, ['.jpg', '.jpeg', '.png']))
                                        <img src="{{ asset('storage/' . $scan_piece_url) }}"
                                            class="img-thumbnail" style="max-width: 200px;">
                                        @else
                                        <p><a href="{{ asset('storage/' . $scan_piece_url) }}"
                                                target="_blank"><i class="menu-icon tf-icons bx bx-file"
                                                    style="font-size: 25px"></i>
                                                Voir le document</a></p>
                                        @endif
                                    </div>
                                    @endif

                                </div>

                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Date d’adhésion</label>
                                    <input type="date" wire:model.defer="date_adhesion" class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Nationalité</label>
                                    <input type="text" wire:model.defer="nationalite" class="form-control">
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">Niveau d’étude</label>
                                    <select name="niveau_etude" wire:model.defer='niveau_etude'
                                        class="form-control">
                                        <option value="">Choisir niveau</option>
                                        <option value="Primaire">Primaire</option>
                                        <option value="Sécondaire">Sécondaire</option>
                                        <option value="Grade">Grade</option>
                                        <option value="Licence">Licence</option>
                                        <option value="Master">Master</option>
                                        <option value="Doctorat">Doctorat</option>
                                        <option value="N/A">N/A</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-1">
                                    <label class="form-label">Remarque</label>
                                    <textarea wire:model.defer="remarque" class="form-control"
                                        rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>


                <div class="modal-footer">
                    @if (!$editModal)
                        @if ($currentStep > 1)
                            <button type="button" class="btn btn-secondary" wire:click="previousStep">Précédent</button>
                        @endif
                        @if ($currentStep < $totalSteps)
                            <button type="button" class="btn btn-primary" wire:click="nextStep">Suivant</button>
                        @endif
                        @if ($currentStep == $totalSteps)
                            <button type="submit" class="btn btn-success" wire:loading.attr="disabled">
                                <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                                Enregistrer
                            </button>
                        @endif
                    @else
                        <button type="button" class="btn btn-secondary" wire:click='closeModal'>{{ __('Fermer') }}</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                            {{ __('Mettre à jour') }}
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Table des adhésions (inchangée) -->