<x-layout title="Notifikasi Saya">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Notifikasi Saya</h1>
            <p class="text-sm text-gray-500">Pemberitahuan tugas, pengumuman, dan aktivitas kelas</p>
        </div>

        @if(auth()->user()->unreadNotifications->count() > 0)
        <form action="{{ route('notifications.read-all') }}" method="POST">
            @csrf
            <button type="submit" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">
                Tandai Semua Telah Dibaca
            </button>
        </form>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden divide-y divide-gray-100">
        @forelse($notifications as $notification)
        <div class="p-4 flex items-center justify-between {{ $notification->read_at ? 'bg-white' : 'bg-blue-50/40 font-medium' }}">
            <div>
                <div class="text-sm text-gray-900">{{ $notification->data['message'] ?? 'Notifikasi baru' }}</div>
                <div class="text-xs text-gray-400 mt-0.5">{{ $notification->created_at->diffForHumans() }}</div>
            </div>

            @if(!$notification->read_at)
            <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                @csrf
                <button type="submit" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-md">
                    Tandai Dibaca
                </button>
            </form>
            @endif
        </div>
        @empty
        <div class="p-8 text-center text-sm text-gray-400">Tidak ada notifikasi.</div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $notifications->links() }}
    </div>
</x-layout>
