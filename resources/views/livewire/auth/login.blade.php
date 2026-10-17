<main class="flex min-h-dvh items-center justify-center bg-canvas px-4 py-10">
    <div class="w-full max-w-sm">
        <div class="mb-6 flex items-center justify-center gap-2 text-xl font-semibold tracking-tight">
            <span class="flex size-9 items-center justify-center rounded-lg bg-accent text-accent-fg">
                <x-icon name="kanban" class="size-5" />
            </span>
            Imishinga
        </div>

        <form wire:submit="login" class="flex flex-col gap-4 rounded-2xl border border-border bg-surface p-6 shadow-sm">
            <h1 class="text-2xl font-semibold tracking-tight">Log in</h1>

            @error('credentials')
                <div role="alert" class="flex items-start gap-2 rounded-lg bg-danger-soft px-3 py-2.5 text-sm text-danger-ink">
                    <x-icon name="alert-circle" class="mt-0.5 size-4 shrink-0" />
                    {{ $message }}
                </div>
            @enderror

            <x-input label="Email" name="email" id="em" wire:model="email" type="email" autofocus
                placeholder="you@example.com" :error="$errors->first('email')" />

            <x-input label="Password" name="password" id="pw" wire:model="password" type="password"
                :error="$errors->first('password')" />

            <x-button type="submit" target="login" loading-text="Logging in…" class="w-full">
                Log in
            </x-button>
        </form>
    </div>
</main>
