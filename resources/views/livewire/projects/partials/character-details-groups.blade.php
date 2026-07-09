            <div class="border-t border-brand-150 pt-6 flex flex-col gap-6">
                <h3 class="text-app-feature text-secondary-200 tracking-wide flex items-center gap-2">
                    DETAILS
                    <button type="button" @click="window.dispatchEvent(new CustomEvent('open-edit-characters'))" class="text-text-80 hover:text-secondary-200 transition-colors">
                        <x-icons.rename class="w-3 h-3" />
                    </button>
                </h3>

                @forelse($detailGroups as $group)
                    <div wire:key="group-{{ $group['id'] }}" class="flex flex-col gap-3 {{ $loop->first ? '' : 'border-t border-brand-150 pt-6' }}">
                        <h4 class="text-app-heading-2 text-secondary-80 mb-2">{{ $group['name'] }}</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            @forelse($group['fields'] as $field)
                                <div wire:key="field-{{ $field['id'] }}" class="flex flex-col gap-1 text-left">
                                    <label class="text-app-feature text-text-70 truncate">{{ $field['name'] }}</label>
                                    <input type="text" wire:model.live.debounce.500ms="detailValues.{{ $field['id'] }}" 
                                        @blur="$wire.$commit()" 
                                        @keydown.enter="$event.target.blur()" 
                                        @keydown.escape="$event.target.blur()" 
                                        value="{{ $detailValues[$field['id']] ?? '' }}" placeholder="Enter value"
                                        class="w-full px-4 py-2 bg-bg-main border-1 border-secondary-100 rounded-lg focus:border-secondary-250 focus:border-2 outline-none transition-all text-subtext-100 text-app-body-medium placeholder:text-subtext-80">
                                </div>
                                 @empty
                                    <p class="text-subtext-90 font-medium text-app-feature">No field in this group yet.</p>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <p class="text-subtext-90 font-medium text-app-feature">No detail groups yet.</p>
                @endforelse
            </div>
