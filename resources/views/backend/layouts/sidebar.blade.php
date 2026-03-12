<!-- ========== App Menu Start ========== -->
<div class="main-nav">
   <div class="logo-box">
      <a href="{{route('dashboard')}}" class="logo-dark">
         <img src="{{asset('backend/assets/fav-icon.png')}}" class="logo-sm" alt="logo sm">
         <img src="{{asset('backend/assets/fav-icon.png')}}" class="logo-lg" alt="logo dark">
      </a>
      <a href="{{route('dashboard')}}" class="logo-light" style="text-align: center;">
         <img src="{{asset('backend/assets/fav-icon.png')}}" class="logo-sm" alt="logo sm">
         <img src="{{asset('backend/assets/logo.png')}}" style="width: 121px; height: 45px;" class="logo-lg"
            alt="logo light">
      </a>
   </div>
   <button type="button" class="button-sm-hover text-info" aria-label="Show Full Sidebar">
      <iconify-icon icon="solar:double-alt-arrow-right-bold-duotone" class="button-sm-hover-icon"></iconify-icon>
   </button>
   <div class="scrollbar" data-simplebar>
      <ul class="navbar-nav" id="navbar-nav">
         <!-- <li class="nav-item">
            <a class="nav-link menu-arrow" href="#sidebarProducts_user" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarProducts_user">
               <span class="nav-icon">
                  <iconify-icon icon=""></iconify-icon>
               </span>
               <span class="nav-text"> Manage User </span>
            </a>
            <div class="collapse" id="sidebarProducts_user">
               <ul class="nav sub-navbar-nav">
                 
                     <li class="sub-nav-item">
                        <a class="sub-nav-link" href="User">User</a>
                     </li>
                     <li class="sub-nav-item">
                        <a class="sub-nav-link" href="User">Role</a>
                     </li>
                     <li class="sub-nav-item">
                        <a class="sub-nav-link" href="User">Permissions</a>
                     </li>                 
               </ul>
            </div>
         </li> -->
         <li class="nav-item">
            <a class="nav-link" href="{{route('dashboard')}}">
               <span class="nav-icon">
                  <iconify-icon icon="solar:widget-5-bold-duotone"></iconify-icon>
               </span>
               <span class="nav-text"> Dashboard </span>
            </a>
         </li>
         @php
            $currentRoute = Route::currentRouteName();
            $shouldBeOpen = in_array($currentRoute, ['label', 'category', 'product.index', 'attributes', 'manage-storage']);
         @endphp
         <li class="nav-item">
            <a class="nav-link menu-arrow {{ !$shouldBeOpen ? 'collapsed' : '' }}" 
               href="#sidebarProducts_2" data-bs-toggle="collapse" role="button"
               aria-expanded="{{ $shouldBeOpen ? 'true' : 'false' }}" 
               aria-controls="sidebarProducts_2">
               <span class="nav-icon">
                     <iconify-icon icon="solar:t-shirt-bold-duotone"></iconify-icon>
               </span>
               <span class="nav-text"> Manage Products </span>
            </a>
            <div class="collapse {{ $shouldBeOpen ? 'show' : '' }}" id="sidebarProducts_2">
               <ul class="nav sub-navbar-nav">                 
                  <li class="sub-nav-item">
                     <a class="sub-nav-link" href="{{route('label')}}">Label</a>
                  </li>
                  <li class="sub-nav-item">
                     <a class="sub-nav-link" href="{{route('category')}}">Main Category</a>
                  </li>
                  <!-- <li class="sub-nav-item">
                     <a class="sub-nav-link" href="{{route('subcategory')}}">Sub Category</a>
                  </li> -->
                  <li class="sub-nav-item">
                     <a class="sub-nav-link" href="{{route('product.index')}}">Product</a>
                  </li>
                  <li class="sub-nav-item">
                     <a class="sub-nav-link" href="{{route('attributes')}}">Attributes</a>
                  </li>
                  <li class="sub-nav-item">
                     <a class="sub-nav-link" href="{{route('manage-storage')}}">Storage</a>
                  </li>
               </ul>
            </div>
         </li>
         <li class="nav-item">
            <a class="nav-link menu-arrow" href="#sidebar_banner" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebar_banner">
               <span class="nav-icon">
                  <iconify-icon icon="solar:checklist-bold-duotone"></iconify-icon>
               </span>
               <span class="nav-text">Manage Home Section </span>
            </a>
            <div class="collapse" id="sidebar_banner">
               <ul class="nav sub-navbar-nav">
                  <li class="sub-nav-item">
                     <a class="sub-nav-link" href="{{ route('manage-banner.index') }}">Banner</a>
                  </li>                     
               </ul>
            </div>
         </li>
      </ul>
   </div>
</div>
<!-- ========== App Menu End ========== -->