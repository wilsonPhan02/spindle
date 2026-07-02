<aside
    @mouseleave="isHovered = false"
    class="fixed inset-y-0 left-0 z-50 w-72 bg-brand-100 border-r border-brand-150 transition-transform duration-300 ease-in-out flex flex-col shadow-sm"
    :class="(isPinned || isHovered) ? 'translate-x-0' : '-translate-x-full'"
>
    <button
        @click="isPinned = false; isHovered = false"
        class="absolute right-0 top-1/2 translate-x-1/2 -translate-y-1/2 w-8 h-16 bg-brand-100 border border-brand-150 rounded-full flex items-center justify-start pl-1.5 text-text-80 hover:bg-brand-150 transition-colors z-50 shadow-sm focus:outline-none"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    </button>

    <div class="p-6">
        <div class="flex items-center space-x-3">
            {{-- Bagian Avatar ini UTUH sesuai kodingan asli lu, tidak disentuh --}}
            <div>
                <x-avatar
                    size="w-12 h-12"
                    :imageUrl="auth()->user()->profile?->avatar_url ? Storage::url(auth()->user()->profile->avatar_url) : null"                />
            </div>

            <div class="flex flex-col truncate">
                <span class="text-app-subheading-2 text-text-80 truncate" :title="currentUsername" x-text="currentUsername">
                {{ Auth::user()->profile?->username ?? explode('@', Auth::user()->email)[0] }}
                </span>

                <span class="text-app-body-small text-subtext-90 truncate" title="{{ Auth::user()->email }}">
                {{ Auth::user()->email }}
                </span>
            </div>
        </div>
    </div>

    <div class="px-6 mb-10">
        <div class="relative">
            <svg class="absolute left-3 top-2.5 w-4 h-4 text-subtext-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" placeholder="Search The Yarn" class="w-full pl-9 pr-4 py-2 bg-brand-10 border-none rounded-full text-app-body-medium text-text-80 placeholder-subtext-70 focus:ring-1 focus:ring-secondary-150 outline-none transition-shadow">
        </div>
    </div>

    <div class="flex-1 overflow-y-auto px-6 space-y-6 pb-6 custom-scrollbar">

        <div x-data="{ open: true }">
            <button @click="open = !open" class="flex items-center justify-between w-full text-app-feature text-text-70 mb-2 focus:outline-none hover:text-text-80 transition-colors">
                <span>Pinned</span>
                <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-collapse>
                <div class="flex flex-col items-center justify-center py-4 text-center opacity-60">
                    <x-icons.sidebar-pen class="w-8 h-8 text-secondary-150 mb-1" />
                    <p class="text-app-desc-feature text-secondary-200">You Didn't Have Any Project!</p>
                </div>
            </div>
        </div>

        <div x-data="{ open: true }">
            <button @click="open = !open" class="flex items-center justify-between w-full text-app-feature text-text-70 mb-2 focus:outline-none hover:text-text-80 transition-colors">
                <span>Recent</span>
                <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-collapse>
                <div class="flex flex-col items-center justify-center py-4 text-center opacity-60">
                    <x-icons.sidebar-pen class="w-8 h-8 text-secondary-150 mb-1" />
                    <p class="text-app-desc-feature text-secondary-200">You Didn't Have Any Project!</p>
                </div>
            </div>
        </div>

        <div>
            <div class="text-app-feature text-text-70 mb-2">Others</div>
            <div class="space-y-1">
                <a href="{{ route('archive') }}" wire:navigate class="flex items-center px-3 py-2 -mx-3 rounded-lg text-app-body-medium text-text-80 hover:bg-brand-150 hover:text-text-100 transition-colors group {{ request()->routeIs('archive') ? 'bg-brand-150 text-text-100' : '' }}">
                    <x-icons.archive class="w-5 h-5 mr-3 text-text-80 group-hover:text-text-100 transition-colors" />
                    Archive
                </a>

                <a href="{{ route('settings') }}" class="flex items-center px-3 py-2 -mx-3 rounded-lg text-app-body-medium text-text-80 hover:bg-brand-150 hover:text-text-100 transition-colors group">
                    <x-icons.setting class="w-5 h-5 mr-3 text-text-80 group-hover:text-text-100 transition-colors" />
                    Settings
                </a>
            </div>
        </div>
    </div>
</aside>
