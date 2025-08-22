<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a wire:navigate href="{{ route('dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo demo ">
                <img src="{{ asset('assets/img/logo.jpg') }}" width="50px" alt="" class="mr-2">
            </span>
            {{ config( 'app.name', 'Laravel') }}

        </a>

        <a wire:navigate href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <!-- Dashboard -->
        <li class="menu-item @if (request()->routeIs('dashboard')) active @endif">
            <a wire:navigate href="{{ route('dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-grid-alt"></i> <!-- Tableau de bord -->
                <div data-i18n="Analytics">Tableau de bord</div>
            </a>
        </li>

        <!-- Liens Comptable -->
        @can('afficher-caisse-centrale')
        <li class="menu-item @if (request()->routeIs('cash.register')) active @endif">
            <a wire:navigate href="{{ route('cash.register') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-wallet"></i> <!-- Caisse centrale -->
                <div data-i18n="Analytics">Caisse Centrale</div>
            </a>
        </li>
        @endcan

        <!-- Liens Comptable -->
        @can('depot-compte-membre')
            <li class="menu-item @if (request()->routeIs('agent.cloture')) active @endif">
                <a wire:navigate href="{{ route('agent.cloture') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-money"></i> <!-- Meilleure icône pour la caisse -->
                    <div data-i18n="Analytics">Clôture Caisse Agent</div>
                </a>
            </li>
        @endcan

        @can('effectuer-virement')
            <li class="menu-item @if (request()->routeIs('transfert.ajouter')) active @endif">
                <a wire:navigate href="{{ route('transfert.ajouter') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-transfer"></i> <!-- Icône spécifique pour transfert -->
                    <div data-i18n="Analytics">Transfert Compte</div>
                </a>
            </li>
        @endcan

        @can('afficher-caisse-agent')
        <li class="menu-item @if (request()->routeIs('agent.dashboard')) active @endif">
            <a wire:navigate href="{{ route('agent.dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-briefcase-alt-2"></i> <!-- Caisse agents -->
                <div data-i18n="Analytics">Caisse Agents</div>
            </a>
        </li>
        @endcan

        @can('afficher-rapport-credit')
            <li class="menu-item @if (request()->routeIs('report.credit.overview','report.credit.followup','credit.grant')) 
                active @endif" wire:ignore.self>
                <a class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-credit-card"></i>
                    <div data-i18n="Misc">Crédits</div>
                </a>
                <ul class="menu-sub">
                    
                    @can('ajouter-credit', App\Models\User::class)
                        <li class="menu-item">
                            <a wire:navigate href="{{ route('credit.grant') }}" class="menu-link">
                                <i class="menu-icon tf-icons bx bx-plus-circle"></i> <!-- Plus pour ajouter -->
                                <div data-i18n="Analytics">Octroyer un Crédit</div>
                            </a>
                        </li>
                    @endcan
                    @can('afficher-credit')
                    <li class="menu-item @if (request()->routeIs('repayments.manage')) active @endif">
                        <a wire:navigate href="{{ route('repayments.manage') }}" class="menu-link">
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
            <a wire:navigate href="{{ route('transfer.to.central') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-transfer"></i> <!-- Virement -->
                <div data-i18n="Analytics">Virement Caisse Centrale</div>
            </a>
        </li>
        @endcan

        @can('afficher-client', App\Models\User::class)
        <li
            class="menu-item @if (request()->routeIs('member.register','member.details','receipt.generate')) active @endif">
            <a wire:navigate href="{{ route('member.register') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-group"></i> <!-- Membres -->
                <div data-i18n="Analytics">Gestion des membres</div>
            </a>
        </li>
        @endcan

        @can('afficher-carnet', App\Models\User::class)
        <!-- Vente de cartes membres -->
        <li class="menu-item @if (request()->routeIs('members.sell-card')) active @endif">
            <a wire:navigate href="{{ route('members.sell-card') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-id-card"></i> <!-- Icône de carte membre -->
                <div data-i18n="Analytics">Vente Cartes Membres</div>
            </a>
        </li>
        @endcan

        @can('afficher-simulation-credit', App\Models\User::class)
        <!-- Simulation de crédit -->
        <li class="menu-item @if (request()->routeIs('repayments.simulation')) active @endif">
            <a wire:navigate href="{{ route('repayments.simulation') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-calculator"></i> <!-- Icône de calculateur -->
                <div data-i18n="Analytics">Simulation Crédit</div>
            </a>
        </li>
        @endcan


        @can('afficher-rapport-credit')
        <li class="menu-item @if (request()->routeIs('rapports.clients','rapports.carnets')) 
            active @endif" wire:ignore.self>
            <a class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-file"></i> <!-- Icône générale pour rapports -->
                <div data-i18n="Misc">Rapports</div>
            </a>
            <ul class="menu-sub">
                
                <li class="menu-item @if (request()->routeIs('rapports.clients')) active @endif">
                    <a wire:navigate href="{{ route('rapports.clients') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-user"></i> <!-- Utilisateurs -->
                        <div data-i18n="Analytics">Rapports Clients</div>
                    </a>
                </li>
                
                <li class="menu-item @if (request()->routeIs('member.accounts')) active @endif">
                    <a wire:navigate href="{{ route('member.accounts') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-user"></i> <!-- Utilisateurs -->
                        <div data-i18n="Analytics">Comptes Clients</div>
                    </a>
                </li>
                
                <li class="menu-item @if (request()->routeIs('rapports.carnets')) active @endif">
                    <a wire:navigate href="{{ route('rapports.carnets') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-book"></i> <!-- Livre/carnet -->
                        <div data-i18n="Analytics">Rapports Carnets</div>
                    </a>
                </li>
                
                <li class="menu-item">
                    <a wire:navigate href="{{ route('report.credit.overview') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-time"></i> <!-- Horloge pour "en cours" -->
                        <div data-i18n="Analytics">Rapport Crédits En Cours</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a wire:navigate href="{{ route('report.credit.followup') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-bar-chart"></i> <!-- Graphique pour rapports -->
                        <div data-i18n="Analytics">Rapport Total Crédits</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a wire:navigate href="{{ route('rapports.transactions') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-bar-chart"></i> <!-- Graphique pour rapports -->
                        <div data-i18n="Analytics">Rapport Transactions</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a wire:navigate href="{{ route('report.repayments') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-bar-chart"></i> <!-- Graphique pour rapports -->
                        <div data-i18n="Analytics">Rapport Remboursement</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a wire:navigate href="{{ route('rapports.depot_retrait') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-bar-chart"></i> <!-- Graphique pour rapports -->
                        <div data-i18n="Analytics">Rapport Dépôt-Retrait</div>
                    </a>
                </li>
                
            </ul>
        </li>
        @endcan

        @can('afficher-role')
        <li class="menu-item @if (request()->routeIs('role.management','user.management')) 
            active @endif" wire:ignore.self>
            <a class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-group"></i>
            <div data-i18n="Misc">Rôles et Utilisateurs</div>
            </a>
            <ul class="menu-sub">
                @can('afficher-role')
                    <!-- Gestion Utilisateurs -->
                    <li class="menu-item @if (request()->routeIs('role.management')) active @endif">
                        <a wire:navigate href="{{ route('role.management') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-group"></i> <!-- Users -->
                            <div data-i18n="Analytics">Gestion Rôles</div>
                        </a>
                    </li>
                @endcan
                @can('afficher-utilisateur')
                    <!-- Gestion Utilisateurs -->
                    <li class="menu-item @if (request()->routeIs('user.management')) active @endif">
                        <a wire:navigate href="{{ route('user.management') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-group"></i> <!-- Users -->
                            <div data-i18n="Analytics">Gestion Utilisateurs</div>
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
        @endcan

    </ul>
</aside>

