import sys

with open('resources/views/livewire/layout/sidebar.blade.php', 'r') as f:
    content = f.read()

# 1. Extract the Search Results from the dropdown.
# The dropdown starts with <div wire:ignore.self x-show="$wire.searchQuery !== '' || recentSearches.length > 0"
# inside it is: <div x-show="$wire.searchQuery !== ''">...</div>
# We need to extract that inner search results block and move it to the scrollable container.

dropdown_start_str = '''        <div wire:ignore.self 
             x-show="$wire.searchQuery !== '' || recentSearches.length > 0" 
             x-transition.opacity.duration.300ms 
             x-cloak 
             class="absolute top-[calc(100%+8px)] left-6 right-6 bg-[#FAF8F5] border border-brand-200 rounded-xl shadow-md p-4 z-50 max-h-[50vh] overflow-y-auto custom-scrollbar">'''

search_results_start = content.find('''<div wire:ignore.self x-show="$wire.searchQuery !== ''" x-transition.opacity.duration.300ms x-cloak>''')
search_results_end = content.find('''        <div wire:ignore.self x-show="recentSearches.length > 0 && $wire.searchQuery === ''" x-transition.opacity.duration.300ms x-cloak>''')

if search_results_start == -1 or search_results_end == -1:
    print("Could not find search results block")
    sys.exit(1)

search_results_block = content[search_results_start:search_results_end]
# Add the border-b back to the end of search results since it will be inline again!
search_results_block = search_results_block.replace('''                </div>
            @endif
            </div>
            </div>
        </div>''', '''                </div>
            @endif
            </div>
            </div>
            <div class="border-b border-brand-200 w-full my-6 shrink-0"></div>
        </div>''')

# Remove the search results block from the dropdown
content = content[:search_results_start] + content[search_results_end:]

# 2. Insert the search results block into the scrollable container.
scroll_container_str = '''    <div class="flex flex-col flex-1 overflow-y-auto [scrollbar-gutter:stable] px-6 pb-6 custom-scrollbar">
        <div class="space-y-6 shrink-0">'''

if scroll_container_str not in content:
    print("Could not find scroll container")
    sys.exit(1)

# We want to insert it right inside the scroll container, before the space-y-6 block
replacement_scroll_container = '''    <div class="flex flex-col flex-1 overflow-y-auto [scrollbar-gutter:stable] px-6 pb-6 custom-scrollbar">\n''' + search_results_block + '''\n        <div class="space-y-6 shrink-0">'''

content = content.replace(scroll_container_str, replacement_scroll_container)

with open('resources/views/livewire/layout/sidebar.blade.php', 'w') as f:
    f.write(content)

print('Done')
