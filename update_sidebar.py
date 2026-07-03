import sys

with open('resources/views/livewire/layout/sidebar.blade.php', 'r') as f:
    content = f.read()

# 1. Update the input container to be relative and insert the dropdown wrapper
input_container = '''    <div class="px-6 mb-8 relative z-50">
        <div class="relative">
            <svg class="absolute left-3 top-2.5 w-4 h-4 text-subtext-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input wire:model.live.debounce.300ms="searchQuery" type="text" placeholder="Search The Yarn" class="w-full pl-9 pr-8 py-2 bg-brand-10 border-none rounded-full text-app-body-medium text-text-80 placeholder-subtext-70 focus:ring-1 focus:ring-secondary-150 outline-none transition-shadow">
            <button x-show="$wire.searchQuery !== ''" @click="$wire.set('searchQuery', '')" class="absolute right-3 top-2.5 text-subtext-90 hover:text-text-80 transition-colors" x-cloak>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div wire:ignore.self 
             x-show="$wire.searchQuery !== '' || recentSearches.length > 0" 
             x-transition.opacity.duration.300ms 
             x-cloak 
             class="absolute top-[calc(100%+8px)] left-6 right-6 bg-[#FAF8F5] border border-brand-200 rounded-xl shadow-md p-4 z-50 max-h-[50vh] overflow-y-auto custom-scrollbar">
            
            <!-- SEARCH_RESULTS_PLACEHOLDER -->
            
            <!-- RECENT_SEARCHES_PLACEHOLDER -->
        </div>
    </div>'''

content = content.replace('''    <div class="px-6 mb-8">
        <div class="relative">
            <svg class="absolute left-3 top-2.5 w-4 h-4 text-subtext-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input wire:model.live.debounce.300ms="searchQuery" type="text" placeholder="Search The Yarn" class="w-full pl-9 pr-8 py-2 bg-brand-10 border-none rounded-full text-app-body-medium text-text-80 placeholder-subtext-70 focus:ring-1 focus:ring-secondary-150 outline-none transition-shadow">
            <button x-show="$wire.searchQuery !== ''" @click="$wire.set('searchQuery', '')" class="absolute right-3 top-2.5 text-subtext-90 hover:text-text-80 transition-colors" x-cloak>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>''', input_container)

# 2. Extract the Search Results Block and replace the placeholder
start_str = '''        <div wire:ignore.self x-show="$wire.searchQuery !== ''" x-transition.opacity.duration.300ms x-cloak>'''
end_str = '''            <div class="border-b border-brand-200 w-full my-6 shrink-0"></div>
        </div>'''
start_idx = content.find(start_str)
end_idx = content.find(end_str) + len(end_str)

search_results_block = content[start_idx:end_idx]
# Remove the border-b line since it's a dropdown now
search_results_block = search_results_block.replace('''            <div class="border-b border-brand-200 w-full my-6 shrink-0"></div>\n        </div>''', '''        </div>''')
# Adjust indentation inside the dropdown block for neatness (optional, but good)
search_results_block = search_results_block.replace('        <div wire:ignore.self', '<div wire:ignore.self').replace('            <div class="space-y-4', '    <div class="space-y-4')

# 3. Extract the Recent Searches Block
recent_start_str = '''        <div wire:ignore.self x-show="recentSearches.length > 0 && $wire.searchQuery === ''" x-transition.opacity.duration.300ms x-cloak>'''
recent_end_str = '''            </div>
        </div>

        <div class="space-y-6 shrink-0">'''
recent_start_idx = content.find(recent_start_str)
recent_end_idx = content.find(recent_end_str)

recent_searches_block = content[recent_start_idx:recent_end_idx + len('            </div>\n        </div>')]

# 4. Remove both from original location
content = content[:start_idx] + '''        <div class="space-y-6 shrink-0">''' + content[recent_end_idx + len('            </div>\n        </div>\n\n        <div class="space-y-6 shrink-0">'):]

# 5. Insert them into the placeholders
content = content.replace('            <!-- SEARCH_RESULTS_PLACEHOLDER -->', search_results_block)
content = content.replace('            <!-- RECENT_SEARCHES_PLACEHOLDER -->', recent_searches_block)

with open('resources/views/livewire/layout/sidebar.blade.php', 'w') as f:
    f.write(content)

print('Done')
