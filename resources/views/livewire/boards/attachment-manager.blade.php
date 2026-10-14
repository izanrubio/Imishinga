<div class="space-y-3">
    <div>
        <input type="file" wire:model="newFiles" multiple
            class="block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">

        <div wire:loading wire:target="newFiles" class="text-xs text-gray-500 mt-1">
            Uploading…
        </div>

        @if ($errors->has('newFiles') || $errors->has('newFiles.*'))
            <ul class="mt-1 space-y-0.5">
                @foreach ($errors->get('newFiles') as $message)
                    <li class="text-sm text-red-600">{{ $message }}</li>
                @endforeach
                @foreach ($errors->get('newFiles.*') as $messages)
                    @foreach ($messages as $message)
                        <li class="text-sm text-red-600">{{ $message }}</li>
                    @endforeach
                @endforeach
            </ul>
        @endif

        <p class="text-xs text-gray-400 mt-1">
            Max 10 MB. Images, PDF, text, CSV, Word, Excel, PowerPoint, or Zip.
        </p>
    </div>

    <div class="space-y-2">
        @forelse ($attachments as $attachment)
            <div wire:key="attachment-{{ $attachment->id }}" class="flex items-center gap-3 border border-gray-100 rounded-md p-2">
                @if ($attachment->isImage())
                    <a href="{{ route('attachments.show', $attachment) }}" target="_blank" class="flex-shrink-0">
                        <img src="{{ route('attachments.show', $attachment) }}" alt="{{ $attachment->filename }}"
                            class="w-10 h-10 object-cover rounded">
                    </a>
                @else
                    <div class="w-10 h-10 flex items-center justify-center rounded bg-gray-100 text-gray-400 flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                            <path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625z" clip-rule="evenodd" />
                        </svg>
                    </div>
                @endif

                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-800 truncate">{{ $attachment->filename }}</p>
                    <p class="text-xs text-gray-400">
                        {{ $attachment->humanSize() }} &middot; {{ $attachment->created_at->format('j M Y, H:i') }} &middot; {{ $attachment->user->name }}
                    </p>
                </div>

                <a href="{{ route('attachments.show', $attachment, ['download' => 1]) }}"
                    class="text-xs text-blue-600 hover:text-blue-800 flex-shrink-0">Download</a>

                <button type="button"
                    x-on:click="if (confirm('Delete this attachment?')) { $wire.deleteAttachment({{ $attachment->id }}) }"
                    class="text-xs text-red-500 hover:text-red-700 flex-shrink-0">Delete</button>
            </div>
        @empty
            <p class="text-xs text-gray-400">No attachments yet.</p>
        @endforelse
    </div>
</div>
