<div class="max-w-sm mx-auto mt-24 bg-white p-8 rounded-lg shadow-sm border border-gray-200">
    <h1 class="text-xl font-semibold mb-6">Log in</h1>

    <form wire:submit="login" class="space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input wire:model="email" id="email" type="email" autofocus
                class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input wire:model="password" id="password" type="password"
                class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
            class="w-full bg-blue-600 text-white rounded-md py-2 text-sm font-medium hover:bg-blue-700">
            Log in
        </button>
    </form>
</div>
