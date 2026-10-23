<header class="app-topbar">
    <div class="page-container topbar-menu">
        <div class="d-flex align-items-center gap-2">

            <!-- Brand Logo -->
            <a href="index.html" class="logo">
                <span class="logo-light">
                    <span class="logo-lg">
                        <img src="" alt="logo">
                    </span>
                    <span class="logo-sm">
                        <img src="" alt="small logo">
                    </span>
                </span>

                <span class="logo-dark">
                    <span class="logo-lg">
                        <img src="" alt="dark logo">
                    </span>
                    <span class="logo-sm">
                        <img src="" alt="small logo">
                    </span>
                </span>
            </a>

            <!-- Sidebar Menu Toggle Button -->
            <button class="sidenav-toggle-button btn btn-secondary btn-icon">
                <i class="ti ti-menu-deep fs-24"></i>
            </button>

            <!-- Button Timestamp -->
            <div class="topbar-item d-none d-sm-flex">
                <button class="topbar-link btn btn-outline-primary" type="button">
                    <div id="tanggal"></div>
                    &nbsp;
                    <div id="jam"></div>
                </button>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <!-- Light/Dark Mode Button -->
            {{-- <div class="topbar-item d-none d-sm-flex">
                <button class="topbar-link btn btn-outline-primary btn-icon" id="light-dark-mode" type="button">
                    <i class="ti ti-moon fs-22"></i>
                </button>
            </div> --}}


            <!-- User Dropdown -->
            <div class="topbar-item">
                <div class="dropdown">
                    <a class="topbar-link btn btn-outline-primary dropdown-toggle drop-arrow-none"
                        data-bs-toggle="dropdown" data-bs-offset="0,22" type="button" aria-haspopup="false"
                        aria-expanded="false">
                        {{-- <img src="{{ asset('uploads/avatar/' . auth()->user()->avatar) }}" width="25"
                            height="25" class="rounded-circle me-lg-2 d-flex" alt="user-image">
                        <span class="d-lg-flex flex-column gap-1 d-none">
                            {{ auth()->user()->name }}
                        </span> --}}
                        <i class="ti ti-chevron-down d-none d-lg-block align-middle ms-2"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- item-->
                        <div class="dropdown-header noti-title">
                            <h6 class="text-overflow m-0">
                                {{ __('dashboard.topbar.usermenu.title') }}
                            </h6>
                        </div>

                        <div class="dropdown-divider"></div>

                        <!-- item-->
                        <a href="#" class="dropdown-item active fw-semibold text-danger"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="ti ti-logout me-1
                            fs-17 align-middle"></i>
                            <span class="align-middle">
                                {{ __('dashboard.topbar.usermenu.section.logout') }}
                            </span>
                        </a>

                        <form action="" method="POST" id="logout-form"
                            style="display: none;">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
