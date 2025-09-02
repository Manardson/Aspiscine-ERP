<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @include('parts.head')
<body>
    <!-- Loader starts-->
    <div class="loader-wrapper">
        <div class="loader-index"><span></span></div>
        <svg>
        <defs></defs>
        <filter id="goo">
          <fegaussianblur in="SourceGraphic" stddeviation="11" result="blur"></fegaussianblur>
          <fecolormatrix in="blur" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 19 -9" result="goo">    </fecolormatrix>
        </filter>
      </svg>
    </div>
    <!-- Loader ends-->
    <!-- page-wrapper Start-->
    <div class="page-wrapper compact-wrapper" id="pageWrapper">
        <!-- Page Header Start-->
        <div class="page-main-header">
            <div class="main-header-right row m-0">
                <div class="main-header-left">
                    <div class="logo-wrapper">
                        <a href="/home"><img class="img-fluid" src="https://aspiscine.ro/apps/phastpress/phast.php/c2VydmljZT1pbWFnZXMmc3JjPWh0dHBzJTNBJTJGJTJGYXNwaXNjaW5lLnJvJTJGd3AtY29udGVudCUyRnVwbG9hZHMlMkYyMDIxJTJGMDQlMkZuZXctbG9nby1hcy1waXNjaW5lLnBuZyZjYWNoZU1hcmtlcj0xNjE4OTM1MDE1LTU4NzImdG9rZW49NTgzYzRiNWIzZTViZTc2MA.q.png" alt="aspiscine logo"></a> 
                    </div>
                </div>
                <div class="toggle-sidebar"><i class="status_toggle middle" data-feather="grid" id="sidebar-toggle"></i></div>
                <div class="left-menu-header col">

                </div>
                <div class="nav-right col pull-right right-menu">

                </div>
                <div class="d-lg-none mobile-toggle pull-right"><i data-feather="more-horizontal"></i></div>
            </div>
        </div>
        <!-- Page Header Ends -->
        <!-- Page Body Start-->
<div class="page-body-wrapper horizontal-menu">
    @include('parts.menu')