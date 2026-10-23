<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <title>Sign In &mdash; {{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <meta name="color-scheme" content="light">

    <!-- Theme Config Js -->
    <script src="{{ asset('assets/js/config.js') }}"></script>
    <!-- Vendor css -->
    <link href="{{ asset('assets/css/vendor.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App css -->
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" id="app-style" />
    <!-- Icons css -->
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Plugins css -->
    <link href="{{ asset('assets/plugins/jquery-confirm/jquery-confirm.min.css') }}" rel="stylesheet" />
</head>

<body>
    <div class="auth-bg d-flex min-vh-100 justify-content-center align-items-center">
        <div class="row g-0 justify-content-center w-100 m-xxl-5 px-xxl-4 m-3">
            <div class="col-xl-4 col-lg-5 col-md-6">
                <div class="card overflow-hidden text-center h-100 p-xxl-4 p-3 mb-0">

                    <h4 class="fw-semibold mb-2">
                        Halaman Masuk
                    </h4>

                    <p class="text-muted mb-4">
                        Sebelum melanjutkan, silahkan masuk ke akun Anda.
                    </p>

                    <form action="{{ route('auth.login') }}" method="POST" class="text-start mb-3">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="login">Username</label>
                            <input type="text" id="login" name="login" class="form-control"
                                placeholder="Masukkan username" autocomplete="off">
                        </div>

                        <div class="mb-3 position-relative">
                            <label class="form-label" for="password">Kata Sandi</label>
                            <div class="input-group">
                                <input type="password" id="password" name="password" class="form-control"
                                    placeholder="Masukkan kata sandi" autocomplete="off">
                                <span class="input-group-text bg-transparent" id="togglePassword"
                                    style="cursor: pointer;">
                                    <i class="ti ti-eye" id="passwordIcon"></i>
                                </span>
                            </div>
                        </div>

                        <div class="d-grid">
                            @if (config('app.env') === 'local')
                                <button type="submit" class="btn btn-primary">
                                    Masuk
                                </button>
                            @else
                                <button class="btn btn-primary g-recaptcha"
                                    data-sitekey="{{ config('recaptcha.site_key') }}" data-callback="onSubmit"
                                    data-action="submit">
                                    Masuk
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor js -->
    <script src="{{ asset('assets/js/vendor.min.js') }}"></script>
    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <!-- Plugins js -->
    <script src="{{ asset('assets/plugins/jquery-confirm/jquery-confirm.min.js') }}"></script>
    @if (config('app.env') !== 'local')
        <!-- Google reCAPTCHA -->
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
    <!-- Page js -->
    <script>
        @if (config('app.env') !== 'local')
            function onSubmit(token) {
                document.querySelector("form").submit();
            }
        @endif

        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const passwordIcon = document.getElementById('passwordIcon');

        togglePassword.addEventListener('click', () => {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            passwordIcon.className = isHidden ? 'ti ti-eye-off' : 'ti ti-eye';
        });
    </script>

    <script>
        $(document).ready(function() {
            @if (\Illuminate\Support\Facades\Session::get('failed_message'))
                $.alert({
                    title: 'Peringatan',
                    content: '{{ \Illuminate\Support\Facades\Session::get('failed_message') }}',
                    type: 'red',
                    theme: 'modern',
                    backgroundDismissAnimation: 'shake',
                    onOpenBefore: function() {
                        this.$title.css("color", "black");
                        this.$content.css("color", "black");
                    }
                });
            @endif

            @if (\Illuminate\Support\Facades\Session::get('success_message'))
                $.alert({
                    title: 'Informasi',
                    content: '{{ \Illuminate\Support\Facades\Session::get('success_message') }}',
                    type: 'green',
                    theme: 'modern',
                    backgroundDismissAnimation: 'shake',
                    onOpenBefore: function() {
                        this.$title.css("color", "black");
                        this.$content.css("color", "black");
                    }
                });
            @endif
        });
    </script>
</body>

</html>
