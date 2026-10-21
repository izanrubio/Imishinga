@php
    $deleting = $confirmingDeleteId ? $attachments->firstWhere('id', $confirmingDeleteId) : null;
@endphp

<div class="flex flex-col gap-2"
    x-data="{
        dragging: false,
        uploading: false,
        progress: 0,
        pending: [],
        clientErrors: [],
        maxBytes: 10 * 1024 * 1024,
        names() { return this.pending.map(file => file.name).join(', '); },
    }"
    x-on:livewire-upload-start="uploading = true; progress = 0; clientErrors = []"
    x-on:livewire-upload-progress="progress = $event.detail.progress"
    x-on:livewire-upload-finish="uploading = false"
    x-on:livewire-upload-error="uploading = false; clientErrors = pending.map(file => ({ name: file.name, message: file.size > maxBytes ? 'File too large, max 10 MB' : 'Upload failed, try again' }))">
    @if ($attachments->isNotEmpty())
        <ul class="flex flex-col gap-2">
            @foreach ($attachments as $attachment)
                <li wire:key="attachment-{{ $attachment->id }}" class="flex items-center gap-3 rounded-lg border border-border p-2">
                    @if ($attachment->isImage())
                        <a href="{{ route('attachments.show', $attachment) }}" target="_blank" rel="noopener" class="shrink-0 rounded-md">
                            <img src="{{ route('attachments.show', $attachment) }}" alt="{{ $attachment->filename }}" loading="lazy"
                                class="size-14 rounded-md bg-surface-muted object-cover">
                        </a>
                    @else
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-md bg-surface-muted text-ink-muted" aria-hidden="true">
                            <x-icon name="file" />
                        </span>
                    @endif

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">{{ $attachment->filename }}</p>
                        <p class="text-xs text-ink-subtle">{{ $attachment->humanSize() }} · added {{ $attachment->created_at->format('j M') }}</p>
                    </div>

                    <a href="{{ route('attachments.show', ['attachment' => $attachment, 'download' => 1]) }}" aria-label="Download {{ $attachment->filename }}"
                        class="inline-flex size-10 shrink-0 items-center justify-center rounded-lg text-ink-muted transition-colors hover:bg-surface-muted hover:text-ink">
                        <x-icon name="download" />
                    </a>

                    <x-icon-button variant="danger" class="shrink-0" aria-label="Delete attachment {{ $attachment->filename }}" wire:click="confirmDelete({{ $attachment->id }})">
                        <x-icon name="trash" />
                    </x-icon-button>
                </li>
            @endforeach
        </ul>
    @endif

    @error('newFiles')
        <div role="alert" class="rounded-xl border-2 border-dashed border-danger bg-danger-soft px-4 py-4 text-center">
            <p class="flex items-center justify-center gap-2 text-sm font-medium text-danger-ink"><x-icon name="alert-circle" />{{ $message }}</p>
        </div>
    @enderror

    @foreach ($newFiles as $index => $file)
        @if ($errors->has("newFiles.$index"))
            <div wire:key="upload-error-{{ $index }}" role="alert" class="rounded-xl border-2 border-dashed border-danger bg-danger-soft px-4 py-4 text-center">
                <p class="flex items-center justify-center gap-2 text-sm font-medium text-danger-ink"><x-icon name="alert-circle" />{{ $errors->first("newFiles.$index") }}</p>
                <p class="mt-1 text-xs text-ink-muted">
                    {{ $file->getClientOriginalName() }} ·
                    <button type="button" x-on:click="$refs.input.click()" class="font-medium underline underline-offset-2">Try another file</button>
                </p>
            </div>
        @endif
    @endforeach

    <template x-for="error in clientErrors" x-bind:key="error.name">
        <div role="alert" class="rounded-xl border-2 border-dashed border-danger bg-danger-soft px-4 py-4 text-center">
            <p class="flex items-center justify-center gap-2 text-sm font-medium text-danger-ink">
                <x-icon name="alert-circle" />
                <span x-text="error.message"></span>
            </p>
            <p class="mt-1 text-xs text-ink-muted">
                <span x-text="error.name"></span> ·
                <button type="button" x-on:click="$refs.input.click()" class="font-medium underline underline-offset-2">Try another file</button>
            </p>
        </div>
    </template>

    <div class="rounded-xl border-2 border-dashed px-4 py-4 text-center transition-colors"
        x-bind:class="uploading || dragging ? 'border-accent bg-accent-soft' : 'border-border-strong hover:bg-accent-soft'"
        x-on:dragover.prevent="dragging = true"
        x-on:dragleave.prevent="dragging = false"
        x-on:drop.prevent="dragging = false; $refs.input.files = $event.dataTransfer.files; $refs.input.dispatchEvent(new Event('change', { bubbles: true }))">
        <input type="file" wire:model="newFiles" multiple x-ref="input" tabindex="-1" aria-label="Attachments" class="sr-only"
            x-on:change="pending = Array.from($event.target.files)">

        <div x-show="! uploading">
            <p class="flex items-center justify-center gap-2 text-sm font-medium">
                <x-icon name="upload" />
                <span>Drop files or <button type="button" x-on:click="$refs.input.click()" class="-my-2.5 inline-block py-2.5 font-medium text-accent-ink underline underline-offset-2">browse</button></span>
            </p>
            <p class="text-xs text-ink-subtle">Up to 10 MB each</p>
        </div>

        <div x-show="uploading" x-cloak role="status">
            <p class="flex items-center justify-center gap-2 text-sm font-medium">
                <x-icon name="loader" class="animate-spin motion-reduce:animate-none" />
                <span class="truncate">Uploading <span x-text="names()"></span></span>
            </p>
            <div class="mt-3">
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-surface-muted" role="progressbar" aria-valuemin="0" aria-valuemax="100" x-bind:aria-valuenow="progress">
                    <div class="h-full rounded-full bg-accent" x-bind:style="'width: ' + progress + '%'"></div>
                </div>
            </div>
            <p class="mt-1 text-xs text-ink-subtle"><span x-text="progress"></span>%</p>
        </div>
    </div>

    @if ($deleting)
        <x-confirm-dialog label="Delete attachment" onClose="$wire.cancelDelete()">
            <h3 class="mt-3 text-lg font-semibold tracking-tight">Delete &ldquo;{{ $deleting->filename }}&rdquo;?</h3>
            <p class="mt-1 text-sm text-ink-muted">The file will be permanently deleted.</p>
            <div class="mt-5 flex flex-wrap justify-end gap-2">
                <x-button variant="secondary" wire:click="cancelDelete">Cancel</x-button>
                <x-button variant="danger" wire:click="deleteAttachment({{ $deleting->id }})" target="deleteAttachment" loading-text="Deleting…">
                    Delete attachment
                </x-button>
            </div>
        </x-confirm-dialog>
    @endif
</div>
