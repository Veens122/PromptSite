  <!-- Navigation -->
  <nav class="fixed top-0 w-full z-50 glass-card border-b border-white/10">
      <div class="container mx-auto px-6 py-4">
          <div class="flex items-center justify-between">
              <!-- Logo -->
              <div class="flex items-center space-x-2">
                  <div
                      class="w-10 h-10 bg-gradient-to-r from-cyan-400 to-green-400 rounded-lg flex items-center justify-center">
                      <span class="text-black font-bold text-xl"><a href="/">
            <img src="{{ asset('images/logo.png') }}" 
                 alt="PromptSite Logo" 
                 class="w-full h-full object-cover">
        </a>
</span>
                  </div>
                  <span
                      class="text-2xl font-bold bg-gradient-to-r from-cyan-400 to-green-400 bg-clip-text text-transparent"><a href="/">PromptSite</a>
                  </span>
              </div>

              <!-- Desktop Navigation -->
              <div class="hidden md:flex space-x-8">
                  <a href="#builder" class="hover:text-cyan-400 transition-colors text-gray-200">Build</a>
                  <a href="#features" class="hover:text-cyan-400 transition-colors text-gray-200">Features</a>
                  <a href="{{ route('contact') }}"
                      class="hover:text-cyan-400 transition-colors text-gray-200">Contact</a>
                  <a href="{{ route('about') }}" class="hover:text-cyan-400 transition-colors text-gray-200">About</a>
              </div>

              <!-- Theme Toggle & CTA -->
              <div class="flex items-center space-x-4">

                  @guest

                      <button
                          class="hidden sm:inline-flex bg-gradient-to-r from-cyan-400 to-green-400 text-black font-semibold px-6 py-2 rounded-lg hover:shadow-lg hover:shadow-cyan-400/25 transition-all"><a
                              href="{{ route('register') }}">Sign Up Free</a>

                      </button>

                      <button
                          class="hidden sm:inline-flex bg-gradient-to-r from-cyan-400 to-green-400 text-black font-semibold px-6 py-2 rounded-lg hover:shadow-lg hover:shadow-cyan-400/25 transition-all"><a
                              href="{{ route('login') }}">Login</a>

                      </button>
                  @endguest

                  {{-- Show the button below when loged in --}}
                  @auth
                      <div x-data="{ open: false }" class="relative">
                          <!-- Button -->
                          <button @click="open = !open"
                              class="hidden sm:inline-flex bg-gradient-to-r from-cyan-400 to-green-400 
               text-black font-semibold px-6 py-2 rounded-lg 
               hover:shadow-lg hover:shadow-cyan-400/25 transition-all">

                              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 ml-2" fill="none"
                                  viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 9l-7 7-7-7" />
                              </svg>
                          </button>

                          <!-- Dropdown Menu -->
                          <div x-show="open" @click.away="open = false" x-transition
                              class="absolute right-0 mt-2 w-30 bg-white shadow-lg rounded-md overflow-hidden z-50">

                              <a href="{{ route('dashboard') }}"
                                  class="block text-center px-4 py-2 text-gray-700 hover:bg-gray-100">
                                  Dashboard
                              </a>

                              <a href="" class="block text-center px-4 py-2 text-gray-700 hover:bg-gray-100">
                                  Pricing
                              </a>

                              {{-- logout form --}}
                              <form action="{{ route('logout') }}" method="POST">
                                  @csrf
                                  <button type="submit"
                                      class="text-red-500 text-center w-full px-4 py-2 hover:bg-red-500 hover:text-white">
                                      Logout
                                  </button>
                              </form>
                          </div>
                      </div>
                  @endauth




                  <!-- Mobile Menu Toggle -->
                  <button id="mobile-menu-toggle" class="md:hidden p-2">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16"></path>
                      </svg>
                  </button>
              </div>
          </div>

          <!-- Mobile Menu -->
          <div id="mobile-menu" class="md:hidden mt-4 p-4 glass-card rounded-lg border border-white/10 hidden">
              <div class="flex flex-col space-y-4">
                  <a href="#builder" class="text-left hover:text-cyan-400 transition-colors text-gray-200">Build</a>

                  <a href="#features" class="text-left hover:text-cyan-400 transition-colors text-gray-200">Features</a>
                  <a href="#pricing" class="text-left hover:text-cyan-400 transition-colors text-gray-200">About</a>
                  <button
                      class="bg-gradient-to-r from-cyan-400 to-green-400 text-black font-semibold px-6 py-2 rounded-lg"><a
                          href="{{ route('login') }}">Login</a></button>
                  <button
                      class="bg-gradient-to-r from-cyan-400 to-green-400 text-black font-semibold px-6 py-2 rounded-lg"><a
                          href="{{ route('register') }}">Sign
                          Up Free</a></button>
              </div>
          </div>
      </div>
  </nav>
