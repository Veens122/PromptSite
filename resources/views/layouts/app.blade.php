<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PromptSite - AI powered website builder | Create Websites with AI in Seconds</title>
    <meta name="description"
        content="AI PromptSite's technology generates complete, professional websites from simple prompts. No coding required - just describe what you want and get a ready-to-publish website.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])


    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap"
        rel="stylesheet">



    <!-- Particles.js -->
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

</head>

<body class="font-sans antialiased bg-gray-900 text-white transition-colors duration-300">


    <!-- Particles Background -->
    <div id="particles-js" class="fixed inset-0 w-full h-full -z-10"></div>

    @include('partials.navbar')

    @yield('content')

    @include('partials.footer')


    {{-- Javascript --}}
    <script src="{{ asset('assets/js/main.js') }}"></script>

    <script src="{{ asset('assets/js/image-handler.js') }}"></script>
    {{-- <script src="{{ asset('assets/js/website-builder.js') }}"></script> --}}



    <script src="//unpkg.com/alpinejs" defer></script>





    <!-- SWEET ALERT -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- SweetAlert2 Messages --}}
    @if (Session::has('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Successful!',
                html: "{{ Session::get('success') }}",
                timer: 5000,
                showConfirmButton: true
            });
        </script>
    @endif

    @if (Session::has('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: "{{ Session::get('error') }}",
                timer: 5000,
                showConfirmButton: false
            });
        </script>
    @endif

    @if (Session::has('info'))
        <script>
            Swal.fire({
                icon: 'info',
                title: 'Info',
                text: "{{ Session::get('info') }}",
                timer: 5000,
                showConfirmButton: false
            });
        </script>
    @endif

    @if ($errors->any())
        <script>
            Swal.fire({
                icon: 'warning',
                title: 'Validation Errors',
                html: `{!! implode('<br>', $errors->all()) !!}`,
                showConfirmButton: true
            });
        </script>
    @endif

</body>

</html>
