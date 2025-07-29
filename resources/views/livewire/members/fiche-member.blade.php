@extends('layouts.backend')

@section('title', 'Octroit Crédit')

@section('content')

<div class="container-xxl flex-grow-1 container-p-y">

    <div class="mt-4">

        <style>
            .client-sheet {
                max-width: 800px;
                margin: 10px auto;
                padding: 20px;
                font-family: "Segoe UI", Arial, sans-serif;
                border: 1px solid #ddd;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
                background-color: #fff;
            }

            .client-header {
                text-align: center;
                border-bottom: 3px solid #b87710;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }

            .client-header h2 {
                margin: 0;
                font-size: 26px;
                color: #b87710;
                font-weight: 700;
            }

            .client-header img {
                max-height: 70px;
                margin-bottom: 10px;
            }

            .section-title {
                background-color: #b87710;
                color: #fff;
                padding: 8px 12px;
                margin-top: 10px;
                font-size: 16px;
                font-weight: 600;
                border-radius: 4px;
            }

            .info-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 12px;
                font-size: 14px;
            }

            .info-table th,
            .info-table td {
                border: 1px solid #ddd;
                padding: 5px;
                text-align: left;
            }

            .info-table th {
                background-color: #f8f5f2;
                color: #333;
                font-weight: 600;
            }

            .info-table tr:nth-child(even) {
                background-color: #fcf8f5;
            }

            .photo {
                border: 1px solid #ccc;
                border-radius: 4px;
            }

            .text-muted {
                color: #6c757d;
            }

            .print-button-container {
                text-align: right;
                margin-bottom: 20px;
            }

            .btn-print {
                background-color: #b87710;
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 4px;
                cursor: pointer;
                transition: background-color 0.3s;
            }

            .btn-print:hover {
                background-color: #a4670d;
            }

            @media print {

                button,
                .btn,
                .text-end,
                .flex-grow,
                footer,
                nav {
                    display: none !important;
                }
            }
        </style>

        <div class="client-sheet">
            <div class="text-end mb-3">
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="bx bx-printer"></i> Imprimer la fiche
                </button>
            </div>

            <div class="client-header">
                @if($member->photo_profil)
                <img src="{{ Storage::url($member->photo_profil) }}" width="100" class="photo"
                    style="margin-bottom:-40px">
                @else
                <img src="{{ asset('assets/img/logo.jpg') }}" alt="Logo" width="100" style="margin-bottom:-40px">
                @endif
                <h2>FICHE CLIENT - {{ env('APP_NAME') }}</h2>
                <small class="text-muted">Date : {{ now()->format('d/m/Y') }}</small>
            </div>

            <div class="section-title">Infos de Base</div>
            <table class="info-table">
                <tr>
                    <th>Code Système</th>
                    <td>{{ $member->code ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Nom</th>
                    <td>{{ $member->name }} {{ $member->postnom }} {{ $member->prenom }}</td>
                </tr>
                <tr>
                    <th>Sexe</th>
                    <td>{{ $member->sexe }}</td>
                </tr>
                <tr>
                    <th>Date de naissance</th>
                    <td>{{ $member->date_naissance }}</td>
                </tr>
                <tr>
                    <th>Lieu de naissance</th>
                    <td>{{ $member->lieu_naissance }}</td>
                </tr>
                <tr>
                    <th>Téléphone</th>
                    <td>{{ $member->telephone }}</td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td>{{ $member->email }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>{{ $member->status ? 'Actif' : 'Inactif' }}</td>
                </tr>
            </table>

            <div class="section-title">Identité & État Civil</div>
            <table class="info-table">
                <tr>
                    <th>Type de pièce</th>
                    <td>{{ $member->type_piece }}</td>
                </tr>
                <tr>
                    <th>Numéro de pièce</th>
                    <td>{{ $member->numero_piece }}</td>
                </tr>
                <tr>
                    <th>Date d'expiration</th>
                    <td>{{ $member->date_expiration_piece }}</td>
                </tr>
                <tr>
                    <th>État civil</th>
                    <td>{{ $member->etat_civil }}</td>
                </tr>
                <tr>
                    <th>Nombre de dépendants</th>
                    <td>{{ $member->nombre_dependants }}</td>
                </tr>
            </table>

            <div class="section-title">Infos Économiques et Professionnelles</div>
            <table class="info-table">
                <tr>
                    <th>Profession</th>
                    <td>{{ $member->profession }}</td>
                </tr>
                <tr>
                    <th>Revenu mensuel</th>
                    <td>{{ number_format($member->revenu_mensuel, 2, ',', ' ') }} FC</td>
                </tr>
                <tr>
                    <th>Source de revenu</th>
                    <td>{{ $member->source_revenu }}</td>
                </tr>
                <tr>
                    <th>Nom de l'employeur</th>
                    <td>{{ $member->nom_employeur }}</td>
                </tr>
                <tr>
                    <th>Nom du conjoint</th>
                    <td>{{ $member->nom_conjoint }}</td>
                </tr>
                <tr>
                    <th>Téléphone conjoint</th>
                    <td>{{ $member->telephone_conjoint }}</td>
                </tr>
            </table>

            <div class="section-title">Références</div>
            <table class="info-table">
                <tr>
                    <th>Nom référence</th>
                    <td>{{ $member->nom_reference }}</td>
                </tr>
                <tr>
                    <th>Téléphone référence</th>
                    <td>{{ $member->telephone_reference }}</td>
                </tr>
                <tr>
                    <th>Lien avec le membre</th>
                    <td>{{ $member->lien_reference }}</td>
                </tr>
            </table>

            <div class="section-title">Localisation</div>
            <table class="info-table">
                <tr>
                    <th>Adresse physique</th>
                    <td>{{ $member->adresse_physique }}</td>
                </tr>
                <tr>
                    <th>Province</th>
                    <td>{{ $member->province }}</td>
                </tr>
                <tr>
                    <th>Ville</th>
                    <td>{{ $member->ville }}</td>
                </tr>
                <tr>
                    <th>Commune</th>
                    <td>{{ $member->commune }}</td>
                </tr>
                <tr>
                    <th>Quartier</th>
                    <td>{{ $member->quartier }}</td>
                </tr>
            </table>

            <div class="section-title">Média</div>
            <table class="info-table">
                <tr>
                    <th>Scan pièce</th>
                    <td>
                        @if ($member->scan_piece)
                        <div class="mt-2">
                            @if(Str::endsWith($member->scan_piece, ['.jpg', '.jpeg', '.png']))
                            <img src="{{ Storage::url($member->scan_piece) }}" class="photo">
                            @else
                            <p><a href="{{ asset('storage/' . $member->scan_piece) }}" target="_blank"><i
                                        class="menu-icon tf-icons bx bx-file" style="font-size: 25px"></i>
                                    Voir le document</a></p>
                            @endif
                        </div>
                        @else
                        N/A
                        @endif
                    </td>
                </tr>
            </table>

            <div class="section-title">Infos Institutionnelles</div>
            <table class="info-table">
                <tr>
                    <th>Date d'adhésion</th>
                    <td>{{ $member->date_adhesion }}</td>
                </tr>
                <tr>
                    <th>Nationalité</th>
                    <td>{{ $member->nationalite }}</td>
                </tr>
                <tr>
                    <th>Niveau d'étude</th>
                    <td>{{ $member->niveau_etude }}</td>
                </tr>
            </table>

            <div class="section-title">Remarques</div>
            <p>{{ $member->remarque }}</p>
        </div>

        {{-- <div class="client-sheet">
            <div class="text-end mb-3">
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="bx bx-printer"></i> Imprimer la fiche
                </button>
            </div>

            <div class="client-header">
                <img src="{{ asset('assets/img/logo.jpg') }}" alt="Logo" width="100" style="margin-bottom:-40px">
                <h2>FICHE CLIENT - MAISHA BORA</h2>
                <small class="text-muted">Date : ____ / ____ / ______</small>
            </div>

            <div class="section-title">Infos de Base</div>
            <table class="info-table">
                <tr><th>Code Système</th></tr>
                <tr><th>Nom</th></tr>
                <tr><th>Sexe</th></tr>
                <tr><th>Date de naissance</th></tr>
                <tr><th>Lieu de naissance</th></tr>
                <tr><th>Téléphone</th></tr>
                <tr><th>Email</th></tr>
                <tr><th>Status</th></tr>
            </table>

            <div class="section-title">Identité & État Civil</div>
            <table class="info-table">
                <tr><th>Type de pièce</th></tr>
                <tr><th>Numéro de pièce</th></tr>
                <tr><th>Date d'expiration</th></tr>
                <tr><th>État civil</th></tr>
                <tr><th>Nombre de dépendants</th></tr>
            </table>

            <div class="section-title">Infos Économiques et Professionnelles</div>
            <table class="info-table">
                <tr><th>Profession</th></tr>
                <tr><th>Revenu mensuel</th></tr>
                <tr><th>Source de revenu</th></tr>
                <tr><th>Nom de l'employeur</th></tr>
                <tr><th>Nom du conjoint</th></tr>
                <tr><th>Téléphone conjoint</th></tr>
            </table>

            <div class="section-title">Références</div>
            <table class="info-table">
                <tr><th>Nom référence</th></tr>
                <tr><th>Téléphone référence</th></tr>
                <tr><th>Lien avec le membre</th></tr>
            </table>

            <div class="section-title">Localisation</div>
            <table class="info-table">
                <tr><th>Adresse physique</th></tr>
                <tr><th>Province</th></tr>
                <tr><th>Ville</th></tr>
                <tr><th>Commune</th></tr>
                <tr><th>Quartier</th></tr>
            </table>

            <div class="section-title">Média</div>
            <table class="info-table">
                <tr><th>Scan pièce (joindre copie papier)</th></tr>
            </table>

            <div class="section-title">Infos Institutionnelles</div>
            <table class="info-table">
                <tr><th>Date d'adhésion</th></tr>
                <tr><th>Nationalité</th></tr>
                <tr><th>Niveau d'étude</th></tr>
            </table>

            <div class="section-title">Remarques</div>
            <p>__</p>
            <p>__</p>
            <p>__</p>
        </div> --}}

    </div>

</div>

@endsection
