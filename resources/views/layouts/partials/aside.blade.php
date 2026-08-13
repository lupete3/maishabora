<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ route('dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo demo ">
                <img src="{{ asset('assets/img/logo.jpg') }}" width="50px" alt="" class="mr-2">
            </span>
            {{ $company?->name ?? config('app.name', 'Maisha Bora') }}
        </a>

        <a class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1" style="margin-bottom:20px">
        <!-- Dashboard -->
        <li class="menu-item @if (request()->routeIs('dashboard')) active @endif">
            <a href="{{ route('dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-grid-alt"></i> <!-- Tableau de bord -->
                <div data-i18n="Analytics">Tableau de bord</div>
            </a>
        </li>

        @if (auth()->user()->hasAnyRole(['Admin', 'Caissier', 'SUPER IT', 'Comptable', 'Receptionniste', 'Recouvreur']))
            <li class="menu-item @if (request()->routeIs('agent.clients-non-collectes')) active @endif">
                <a href="{{ route('agent.clients-non-collectes') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-user"></i> <!-- Tableau de bord -->
                    <div data-i18n="Analytics">Clients à visiter</div>
                </a>
            </li>
        @endif

        @can('afficher-caisse-centrale')
            <li class="menu-item @if (request()->routeIs('decision.dashboard')) active @endif">
                <a href="{{ route('decision.dashboard') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-pulse"></i>
                    <div data-i18n="Analytics">Cockpit décisionnel</div>
                </a>
            </li>

            <li class="menu-item @if (request()->routeIs('cash.register')) active @endif">
                <a href="{{ route('cash.register') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-wallet"></i> <!-- Caisse centrale -->
                    <div data-i18n="Analytics">Caisse Centrale</div>
                </a>
            </li>
        @endcan

        @can('depot-compte-membre')
            <li class="menu-item @if (request()->routeIs('agent.cloture')) active @endif">
                <a href="{{ route('agent.cloture') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-money"></i> <!-- Meilleure icône pour la caisse -->
                    <div data-i18n="Analytics">Clôture Caisse Agent</div>
                </a>
            </li>
        @endcan

        @can('afficher-caisse-agent')
            <li class="menu-item @if (request()->routeIs('ecarts.caisse')) active @endif">
                <a href="{{ route('ecarts.caisse') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-error-circle"></i>
                    <div data-i18n="Analytics">Écarts de Caisse</div>
                </a>
            </li>
        @endcan

        @can('effectuer-virement')
            <li class="menu-item @if (request()->routeIs('transfert.ajouter')) active @endif">
                <a href="{{ route('transfert.ajouter') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-transfer"></i> <!-- Icône spécifique pour transfert -->
                    <div data-i18n="Analytics">Transfert Compte</div>
                </a>
            </li>
        @endcan

        @can('afficher-caisse-agent')
            <li class="menu-item @if (request()->routeIs('agent.dashboard')) active @endif">
                <a href="{{ route('agent.dashboard') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-briefcase-alt-2"></i> <!-- Caisse agents -->
                    <div data-i18n="Analytics">Caisse Agents</div>
                </a>
            </li>
        @endcan

        @can('decaissement')
            <li class="menu-item @if (request()->routeIs('disbursement.index')) active @endif">
                <a href="{{ route('disbursement.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-wallet-alt"></i> <!-- Décaissements -->
                    <div data-i18n="Analytics">Décaissements</div>
                </a>
            </li>
        @endcan

        @can('approuver-decaissement')
            <li class="menu-item @if (request()->routeIs('disbursement.approval')) active @endif">
                <a href="{{ route('disbursement.approval') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-check-shield"></i> <!-- Icône d'approbation -->
                    <div data-i18n="Analytics">Approbation Décaissements</div>
                </a>
            </li>
        @endcan

        @can('afficher-demandes-credit')
            <li class="menu-item @if (request()->routeIs('credit.applications.*')) active @endif">
                <a href="{{ route('credit.applications.list') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-list-ul"></i>
                    <div data-i18n="Analytics">Demandes Crédit & Analyse</div>
                </a>
            </li>
        @endcan

        @can('afficher-rapport-credit')
            <li class="menu-item @if (request()->routeIs('credit.grant', 'repayments.manage')) active @endif" wire:ignore.self>
                <a class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-credit-card"></i>
                    <div data-i18n="Misc">Crédits</div>
                </a>
                <ul class="menu-sub">
                    @can('ajouter-credit', App\Models\User::class)
                        <li class="menu-item">
                            <a href="{{ route('credit.grant') }}" class="menu-link">
                                <i class="menu-icon tf-icons bx bx-plus-circle"></i> <!-- Plus pour ajouter -->
                                <div data-i18n="Analytics">Octroyer un Crédit</div>
                            </a>
                        </li>
                    @endcan

                    @can('afficher-credit')
                        <li class="menu-item @if (request()->routeIs('repayments.manage')) active @endif">
                            <a href="{{ route('repayments.manage') }}" class="menu-link">
                                <i class="menu-icon tf-icons bx bx-refresh"></i> <!-- Remboursements -->
                                <div data-i18n="Analytics">Gérer les Remboursements</div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endcan

        @can('ajouter-transfert-caisse', App\Models\User::class)
            <li class="menu-item @if (request()->routeIs('transfer.to.central')) active @endif">
                <a href="{{ route('transfer.to.central') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-transfer"></i> <!-- Virement -->
                    <div data-i18n="Analytics">Virement Caisse Centrale</div>
                </a>
            </li>
        @endcan

        @can('afficher-client', App\Models\User::class)
            <li class="menu-item @if (request()->routeIs('member.register', 'member.details', 'receipt.generate')) active @endif">
                <a href="{{ route('member.register') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-group"></i> <!-- Membres -->
                    <div data-i18n="Analytics">Gestion des membres</div>
                </a>
            </li>
        @endcan

        @can('afficher-carnet', App\Models\User::class)
            <!-- Vente de cartes membres -->
            <li class="menu-item @if (request()->routeIs('members.sell-card')) active @endif">
                <a href="{{ route('members.sell-card') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-id-card"></i> <!-- Icône de carte membre -->
                    <div data-i18n="Analytics">Vente Cartes Membres</div>
                </a>
            </li>
        @endcan

        @can('afficher-paye', App\Models\User::class)
            <li class="menu-item @if (request()->routeIs('payroll.index')) active @endif">
                <a href="{{ route('payroll.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-money"></i> <!-- Icône de paie -->
                    <div data-i18n="Analytics">Gestion de la Paie</div>
                </a>
            </li>
        @endcan

        @can('afficher-rapport-comptable', App\Models\User::class)
            <li class="menu-item @if (request()->routeIs(
                    'comptabilite.comptes',
                    'comptabilite.type_journal',
                    'comptabilite.journals',
                    'comptabilite.grand_livre',
                    'comptabilite.balance',
                    'comptabilite.compte_resultat',
                    'comptabilite.bilan',
                    'comptabilite.provisions',
                    'comptabilite.resultats',
                    'comptabilite.ratios',
                    'reports.agent-performance',
                    'comptabilite.collector.indicators')) active @endif" wire:ignore.self>
                <a class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-calculator"></i> <!-- Icône générale pour rapports -->
                    <div data-i18n="Misc">Comptabilité</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item @if (request()->routeIs('comptabilite.comptes')) active @endif">
                        <a href="{{ route('comptabilite.comptes') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-wallet"></i> <!-- Icône de calculateur -->
                            <div data-i18n="Analytics">Gestion Comptes</div>
                        </a>
                    </li>
                    <!-- Simulation de crédit -->
                    <li class="menu-item @if (request()->routeIs('comptabilite.type_journal')) active @endif">
                        <a href="{{ route('comptabilite.type_journal') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-category"></i> <!-- Icône de calculateur -->
                            <div data-i18n="Analytics">Type de Journal</div>
                        </a>
                    </li>
                    <!-- Simulation de crédit -->
                    <li class="menu-item @if (request()->routeIs('comptabilite.journals')) active @endif">
                        <a href="{{ route('comptabilite.journals') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-edit"></i> <!-- Icône de calculateur -->
                            <div data-i18n="Analytics">Ecritures Comptables</div>
                        </a>
                    </li>

                    <!-- Grand Livre -->
                    <li class="menu-item @if (request()->routeIs('comptabilite.grand_livre')) active @endif">
                        <a href="{{ route('comptabilite.grand_livre') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-book-open"></i>
                            <div data-i18n="Analytics">Grand Livre</div>
                        </a>
                    </li>

                    <li class="menu-item @if (request()->routeIs('comptabilite.balance')) active @endif">
                        <a href="{{ route('comptabilite.balance') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-calculator"></i> <!-- Icône de calculateur -->
                            <div data-i18n="Analytics">Balance Générale</div>
                        </a>
                    </li>

                    <!-- États Financiers -->
                    <li class="menu-item @if (request()->routeIs('comptabilite.compte_resultat')) active @endif">
                        <a href="{{ route('comptabilite.compte_resultat') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-chart"></i>
                            <div data-i18n="Analytics">Compte de Résultat</div>
                        </a>
                    </li>

                    <li class="menu-item @if (request()->routeIs('comptabilite.bilan')) active @endif">
                        <a href="{{ route('comptabilite.bilan') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                            <div data-i18n="Analytics">Bilan</div>
                        </a>
                    </li>

                    <!-- Provisions -->
                    <li class="menu-item @if (request()->routeIs('comptabilite.provisions')) active @endif">
                        <a href="{{ route('comptabilite.provisions') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-shield-alt-2"></i>
                            <div data-i18n="Analytics">Provisions & Risques</div>
                        </a>
                    </li>
                    <!-- Simulation de crédit -->
                    <li class="menu-item @if (request()->routeIs('comptabilite.resultats')) active @endif">
                        <a href="{{ route('comptabilite.resultats') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-line-chart"></i> <!-- Icône de calculateur -->
                            <div data-i18n="Analytics">Résultat Général</div>
                        </a>
                    </li>
                    <li class="menu-item @if (request()->routeIs('comptabilite.ratios')) active @endif">
                        <a href="{{ route('comptabilite.ratios') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-bar-chart-alt-2"></i>
                            <div data-i18n="Analytics">Ratios de Gestion</div>
                        </a>
                    </li>
                    <li class="menu-item @if (request()->routeIs('reports.agent-performance')) active @endif">
                        <a href="{{ route('reports.agent-performance') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-user-check"></i>
                            <div data-i18n="Analytics">Performance Agents</div>
                        </a>
                    </li>
                    <li class="menu-item @if (request()->routeIs('comptabilite.collector.indicators')) active @endif">
                        <a href="{{ route('comptabilite.collector.indicators') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-pie-chart-alt-2"></i>
                            <div data-i18n="Analytics">Indicateurs Collecteurs</div>
                        </a>
                    </li>
                </ul>
            </li>
        @endcan

        @can('afficher-rapport-credit')
            <li class="menu-item @if (request()->routeIs(
                    'rapports.clients',
                    'rapports.carnets',
                    'rapports.transactions',
                    'report.credit.overview',
                    'report.credit.followup',
                    'report.repayments',
                    'rapports.depot_retrait',
                    'member.accounts',
                    'members.carnet-overview')) active @endif" wire:ignore.self>
                <a class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-file"></i> <!-- Icône générale pour rapports -->
                    <div data-i18n="Misc">Rapports</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item @if (request()->routeIs('rapports.clients')) active @endif">
                        <a href="{{ route('rapports.clients') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-user"></i> <!-- Utilisateurs -->
                            <div data-i18n="Analytics">Rapports Clients</div>
                        </a>
                    </li>

                    <li class="menu-item @if (request()->routeIs('member.accounts')) active @endif">
                        <a href="{{ route('member.accounts') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-id-card"></i> <!-- Utilisateurs -->
                            <div data-i18n="Analytics">Comptes Clients</div>
                        </a>
                    </li>

                    <li class="menu-item @if (request()->routeIs('rapports.carnets')) active @endif">
                        <a href="{{ route('rapports.carnets') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-book-content"></i> <!-- Livre/carnet -->
                            <div data-i18n="Analytics">Rapports Carnets</div>
                        </a>
                    </li>

                    <!-- Overview des carnets -->
                    <li class="menu-item @if (request()->routeIs('members.carnet-overview')) active @endif">
                        <a href="{{ route('members.carnet-overview') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-show-alt"></i>
                            <div data-i18n="Analytics">Carnets Douteux</div>
                        </a>
                    </li>

                    <li class="menu-item @if (request()->routeIs('report.credit.overview')) active @endif">
                        <a href="{{ route('report.credit.overview') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-time-five"></i> <!-- Horloge pour "en cours" -->
                            <div data-i18n="Analytics">Rapport Crédits En Cours</div>
                        </a>
                    </li>
                    <li class="menu-item @if (request()->routeIs('report.credit.followup')) active @endif">
                        <a href="{{ route('report.credit.followup') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-bar-chart-square"></i> <!-- Graphique pour rapports -->
                            <div data-i18n="Analytics">Rapport Total Crédits</div>
                        </a>
                    </li>
                    <li class="menu-item @if (request()->routeIs('rapports.transactions')) active @endif">
                        <a href="{{ route('rapports.transactions') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-line-chart"></i> <!-- Graphique pour rapports -->
                            <div data-i18n="Analytics">Rapport Transactions</div>
                        </a>
                    </li>
                    <li class="menu-item @if (request()->routeIs('report.repayments')) active @endif">
                        <a href="{{ route('report.repayments') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-rotate-left"></i></i> <!-- Graphique pour rapports -->
                            <div data-i18n="Analytics">Rapport Remboursement</div>
                        </a>
                    </li>
                    <li class="menu-item @if (request()->routeIs('rapports.depot_retrait')) active @endif">
                        <a href="{{ route('rapports.depot_retrait') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-line-chart"></i> <!-- Graphique pour rapports -->
                            <div data-i18n="Analytics">Rapport Dépôt-Retrait</div>
                        </a>
                    </li>
                </ul>
            </li>
        @endcan

        @can('afficher-role')
            <li class="menu-item @if (request()->routeIs('role.management', 'user.management')) active @endif" wire:ignore.self>
                <a class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-group"></i>
                    <div data-i18n="Misc">Rôles et Utilisateurs</div>
                </a>
                <ul class="menu-sub">
                    @can('afficher-role')
                        <!-- Gestion Utilisateurs -->
                        <li class="menu-item @if (request()->routeIs('role.management')) active @endif">
                            <a href="{{ route('role.management') }}" class="menu-link">
                                <i class="menu-icon tf-icons bx bx-group"></i> <!-- Users -->
                                <div data-i18n="Analytics">Gestion Rôles</div>
                            </a>
                        </li>
                    @endcan
                    @can('afficher-utilisateur')
                        <!-- Gestion Utilisateurs -->
                        <li class="menu-item @if (request()->routeIs('user.management')) active @endif">
                            <a href="{{ route('user.management') }}" class="menu-link">
                                <i class="menu-icon tf-icons bx bx-group"></i> <!-- Users -->
                                <div data-i18n="Analytics">Gestion Utilisateurs</div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endcan

        @can('afficher-informations-entreprise')
            <li class="menu-item @if (request()->routeIs('company.information')) active @endif" wire:ignore.self>
                <a href="{{ route('company.information') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-building"></i>
                    <div data-i18n="Analytics">Info Entreprise</div>
                </a>
            </li>
        @endcan

        @can('afficher-logs', App\Models\User::class)
            <li class="menu-item @if (request()->is('ai/reports/*')) active open @endif" wire:ignore.self>
                <a class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bxs-magic-wand"></i>
                    <div data-i18n="Misc">Hub d'Analyse IA</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item @if (request()->routeIs('ai.reports')) active @endif">
                        <a href="{{ route('ai.reports') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-sun"></i>
                            <div data-i18n="Analytics">Résumé Quotidien</div>
                        </a>
                    </li>
                    <li class="menu-item @if (request()->routeIs('ai.reports.credit')) active @endif">
                        <a href="{{ route('ai.reports.credit') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-credit-card"></i>
                            <div data-i18n="Analytics">Santé des Crédits</div>
                        </a>
                    </li>
                    <li class="menu-item @if (request()->routeIs('ai.reports.clients')) active @endif">
                        <a href="{{ route('ai.reports.clients') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-user-voice"></i>
                            <div data-i18n="Analytics">Fidélité Clients</div>
                        </a>
                    </li>
                    <li class="menu-item @if (request()->routeIs('ai.reports.sales')) active @endif">
                        <a href="{{ route('ai.reports.sales') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-trending-up"></i>
                            <div data-i18n="Analytics">Ventes & Adhésions</div>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="menu-item @if (request()->routeIs('rapports.logs')) active @endif">
                <a href="{{ route('rapports.logs') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-history"></i> <!-- Icône de calculateur -->
                    <div data-i18n="Analytics">Logs du système</div>
                </a>
            </li>

            <li class="menu-item @if (request()->routeIs('admin.user-sessions')) active @endif">
                <a href="{{ route('admin.user-sessions') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-shield-quarter"></i>
                    <div data-i18n="Analytics">Sessions utilisateurs</div>
                </a>
            </li>

            <li class="menu-item">
                <a href="{{ route('system.run-cron') }}" class="menu-link"
                    onclick="return confirm('Voulez-vous forcer l\'exécution de la vérification des retards maintenant ?')">
                    <i class="menu-icon tf-icons bx bx-cog"></i>
                    <div data-i18n="Analytics">Forcer l'exécution (Cron)</div>
                </a>
            </li>
        @endcan

        @can('afficher-simulation-credit', App\Models\User::class)
            <li class="menu-item @if (request()->routeIs('repayments.simulation')) active @endif">
                <a href="{{ route('repayments.simulation') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-calculator"></i> <!-- Icône de calculateur -->
                    <div data-i18n="Analytics">Simulation Crédit</div>
                </a>
            </li>
        @endcan
    </ul>
</aside>
