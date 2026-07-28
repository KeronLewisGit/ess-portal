<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Brand -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="font-semibold text-gray-800">
                        {{ \App\Models\Setting::get('company_name', config('app.name')) }}
                        <span class="text-gray-400 font-normal hidden lg:inline">— ESS Portal</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    <x-nav-link :href="route('letter-requests.index')" :active="request()->routeIs('letter-requests.*')">
                        {{ __('My Requests') }}
                    </x-nav-link>

                    <x-nav-link :href="route('payslips.index')" :active="request()->routeIs('payslips.*')">
                        {{ __('My Payslips') }}
                    </x-nav-link>

                    @can('access-hr-area')
                        <div class="hidden sm:flex sm:items-center">
                            <x-dropdown align="left" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('hr.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700' }} text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out h-16">
                                        {{ __('HR') }}
                                        <svg class="fill-current h-4 w-4 ms-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link :href="route('hr.approvals.index')">
                                        {{ __('Approval Queue') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('hr.employees.index')">
                                        {{ __('Employees') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('hr.letter-types.index')">
                                        {{ __('Letter Templates') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('hr.payslips.index')">
                                        {{ __('Payslips') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('hr.reports.index')">
                                        {{ __('Reports') }}
                                    </x-dropdown-link>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endcan
                </div>
            </div>

            <!-- Account Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <span class="me-3 inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                    {{ Auth::user()->role->label() }}
                </span>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        @can('manage-settings')
                            <x-dropdown-link :href="route('admin.settings.edit')">
                                {{ __('Portal Settings') }}
                            </x-dropdown-link>
                        @endcan

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('letter-requests.index')" :active="request()->routeIs('letter-requests.*')">
                {{ __('My Requests') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('payslips.index')" :active="request()->routeIs('payslips.*')">
                {{ __('My Payslips') }}
            </x-responsive-nav-link>

            @can('access-hr-area')
                <div class="pt-2 border-t border-gray-200">
                    <div class="px-4 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">HR</div>

                    <x-responsive-nav-link :href="route('hr.approvals.index')" :active="request()->routeIs('hr.approvals.*')">
                        {{ __('Approval Queue') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('hr.employees.index')" :active="request()->routeIs('hr.employees.*')">
                        {{ __('Employees') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('hr.letter-types.index')" :active="request()->routeIs('hr.letter-types.*')">
                        {{ __('Letter Templates') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('hr.payslips.index')" :active="request()->routeIs('hr.payslips.*')">
                        {{ __('Payslips') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('hr.reports.index')" :active="request()->routeIs('hr.reports.*')">
                        {{ __('Reports') }}
                    </x-responsive-nav-link>
                </div>
            @endcan
        </div>

        <!-- Responsive Account Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                <div class="mt-1 text-xs text-gray-400">{{ Auth::user()->role->label() }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                @can('manage-settings')
                    <x-responsive-nav-link :href="route('admin.settings.edit')">
                        {{ __('Portal Settings') }}
                    </x-responsive-nav-link>
                @endcan

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
