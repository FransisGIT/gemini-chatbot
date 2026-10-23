{{-- resources/views/user/chatbot.blade.php --}}
@extends('template', ['title' => 'Chatbot'])

@section('content')
    <div class="page-title-head mt-5">
        <div>
            <h4 class="fs-18 text-uppercase fw-bold mb-1">Chatbot AI</h4>
        </div>
    </div>

    <div class="row g-3" id="chatbot-app" data-url-kirim="{{ route('user.dashboard.chatbot.kirim') }}">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Asisten Gemini</h5>
                    <button type="button" class="btn btn-sm btn-soft-secondary" id="chat-clear">Bersihkan Chat</button>
                </div>

                <div class="card-body bg-light overflow-auto" id="chat-box" style="height: 65vh;">
                    <div class="text-center text-muted my-4" id="chat-placeholder">
                        <p class="mb-0">Halo! Silakan ketik pesan untuk mulai mengobrol.</p>
                    </div>
                </div>

                <div class="card-footer">
                    <div id="chat-form" class="d-flex gap-2">
                        @csrf
                        <textarea class="form-control" id="chat-input" rows="1" placeholder="Ketik pesan..." required></textarea>
                        <button type="button" class="btn btn-primary" id="chat-send">Kirim</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/chatbot.js') }}"></script>
@endpush
