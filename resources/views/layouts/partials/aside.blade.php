<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ route('dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo demo ">
                <img src="{{ asset('assets/img/logo.jpg') }}" width="50px" alt="" class="mr-2">
            </span>
            {{ config( 'app.name', 'Laravel') }}

        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <!-- Dashboard -->
        <li class="menu-item @if (request()->routeIs('dashboard')) active @endif">
            <a href="{{ route('dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Tableau de bord</div>
            </a>
        </li>

        <!-- Liens Compable-->
        @can('isCaissier', App\Models\User::class)
            <li class="menu-item @if (request()->routeIs('cash.register')) active @endif">
                <a href="{{ route('cash.register') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-wallet"></i>
                    <div data-i18n="Analytics">Caisse Centrale</div>
                </a>
            </li>

            {{-- <li class="menu-item @if (request()->routeIs('transfer.to.central')) active @endif">
                <a href="{{ route('transfer.to.central') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-wallet"></i>
                    <div data-i18n="Analytics">Virement Caisse Centrale</div>
                </a>
            </li> --}}
            <li class="menu-item @if (request()->routeIs('agent.dashboard')) active @endif">
                <a href="{{ route('agent.dashboard') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-wallet"></i>
                    <div data-i18n="Analytics">Caisse Agents</div>
                </a>
            </li>




        @endcan

        @can('isRecouvreur', App\Models\User::class)
            <li class="menu-item @if (request()->routeIs('member.register','member.details','receipt.generate')) active @endif">
                <a href="{{ route('member.register') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-user"></i>
                    <div data-i18n="Analytics">Gestion des membres</div>
                </a>
            </li>
            {{-- <li class="menu-item @if (request()->routeIs('deposit.member')) active @endif">
                <a href="{{ route('deposit.member') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-wallet"></i>
                    <div data-i18n="Analytics">Dépôt pour membre</div>
                </a>
            </li> --}}
        @endif





        {{-- @can('isRecouvreur', App\Models\User::class)

            <li class="menu-item @if (request()->routeIs('recouvreur.member.register')) active @endif">
                <a href="{{ route('recouvreur.member.register') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-home-circle"></i>
                    <div data-i18n="Analytics">Enregistrement Membres</div>
                </a>
            </li>
            <li class="menu-item @if (request()->routeIs('members.sell-card')) active @endif">
                <a href="{{ route('members.sell-card') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-home-circle"></i>
                    <div data-i18n="Analytics">Adhesion Membres</div>
                </a>
            </li>
            <li class="menu-item @if (request()->routeIs('members.subscribe')) active @endif">
                <a href="{{ route('members.subscribe') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-home-circle"></i>
                    <div data-i18n="Analytics">Souscription</div>
                </a>
            </li>
            <li class="menu-item @if (request()->routeIs('members.books')) active @endif">
                <a href="{{ route('members.books') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-home-circle"></i>
                    <div data-i18n="Analytics">Dépôt Membres</div>
                </a>
            </li>
        @endcan --}}



        {{-- <li class="menu-item @if (request()->routeIs('member.dashboard')) active @endif">
            <a href="{{ route('member.dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Tableau de bord Membre</div>
            </a>
        </li> --}}
        {{-- <li class="menu-item @if (request()->routeIs('member.history')) active @endif">
            <a href="{{ route('member.history') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Relévé de compte</div>
            </a>
        </li> --}}

        {{-- @can('isAdmin', App\Models\User::class)
            <li class="menu-item @if (request()->routeIs('admin.dashboard')) active @endif">
                <a href="{{ route('admin.dashboard') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-home-circle"></i>
                    <div data-i18n="Analytics">Tableau de bord Admin</div>
                </a>
            </li>
        @endcan --}}


    </ul>
</aside>
