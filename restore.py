import sys

with open('resources/views/livewire/layout/sidebar.blade.php', 'r') as f:
    content = f.read()

# Fix stray quote
content = content.replace('        "\n        class="flex flex-col flex-1', '        class="flex flex-col flex-1')

# 1. Add opacity transition to Search Results
content = content.replace(
    '<div wire:key="search-results-block" wire:transition>',
    '<div wire:key="search-results-block" x-transition.opacity.duration.300ms>'
)

# 2. Add opacity transition to Recent Searches
content = content.replace(
    '<div wire:key="recent-searches-block" x-show="recentSearches.length > 0 && $wire.searchQuery === \'\'" x-cloak>',
    '<div wire:key="recent-searches-block" x-show="recentSearches.length > 0 && $wire.searchQuery === \'\'" x-transition.opacity.duration.300ms x-cloak>'
)

# 3. Change search result icons to always be book (the user asked for this earlier: "hasil searchnya ada yang gaada ikon, pastikan semua ada ikon", "cukup project an section aja").
icon_logic_old = '''                                    @if($item['type'] === 'project' || $item['type'] === 'category')
                                        <x-icons.sidebar-book class="w-4 h-4 text-secondary-150 shrink-0" />
                                    @elseif($item['type'] === 'section')
                                        <x-icons.list class="w-4 h-4 text-[#8C7558] shrink-0" />
                                    @endif'''
icon_logic_new = '''                                    <x-icons.sidebar-book class="w-4 h-4 text-secondary-150 shrink-0" />'''
content = content.replace(icon_logic_old, icon_logic_new)

# 4. Fix Others section to match Pinned/Recent perfectly and remove extra </div>
others_old = '''        <div class="pt-6 shrink-0">
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
        </div>
    </div>
</aside>'''

others_new = '''        <div class="pt-6 shrink-0">
            <div class="text-app-feature text-text-70 mb-2">Others</div>
            <div class="flex flex-col gap-1">
                <a href="{{ route('archive') }}" wire:navigate class="flex items-center justify-between px-2 py-1.5 -mx-2 rounded-lg hover:bg-brand-150 transition-colors group cursor-pointer {{ request()->routeIs('archive') ? 'bg-brand-150' : '' }}">
                    <div class="flex items-center gap-2 min-w-0 flex-1">
                        <x-icons.archive class="w-4 h-4 text-secondary-150 shrink-0 group-hover:text-text-100 transition-colors {{ request()->routeIs('archive') ? 'text-text-100' : '' }}" />
                        <span class="text-[13px] font-medium text-text-80 truncate group-hover:text-text-100 transition-colors {{ request()->routeIs('archive') ? 'text-text-100' : '' }}">Archive</span>
                    </div>
                </a>

                <a href="{{ route('settings') }}" class="flex items-center justify-between px-2 py-1.5 -mx-2 rounded-lg hover:bg-brand-150 transition-colors group cursor-pointer {{ request()->routeIs('settings') ? 'bg-brand-150' : '' }}">
                    <div class="flex items-center gap-2 min-w-0 flex-1">
                        <x-icons.setting class="w-4 h-4 text-secondary-150 shrink-0 group-hover:text-text-100 transition-colors {{ request()->routeIs('settings') ? 'text-text-100' : '' }}" />
                        <span class="text-[13px] font-medium text-text-80 truncate group-hover:text-text-100 transition-colors {{ request()->routeIs('settings') ? 'text-text-100' : '' }}">Settings</span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</aside>'''

content = content.replace(others_old, others_new)

with open('resources/views/livewire/layout/sidebar.blade.php', 'w') as f:
    f.write(content)

print('Done')
