// public/js/chatbot.js
(function ($) {
    'use strict';

    window.Chatbot = {
        urlKirim: '',
        token: '',
        history: [],
        $box: null,
        $input: null,
        $send: null,
        $placeholder: null,
        $typing: null,

        init: function () {
            var $app = $('#chatbot-app');

            this.urlKirim = $app.data('url-kirim');
            this.$box = $('#chat-box');
            this.$input = $('#chat-input');
            this.$send = $('#chat-send');
            this.$placeholder = $('#chat-placeholder');
            this.token = $('#chat-form').find('input[name=_token]').val();

            this.$send.on('click', $.proxy(this.onSubmit, this));
            this.$input.on('keydown', $.proxy(this.onKeydown, this));
            $('#chat-clear').on('click', $.proxy(this.clearChat, this));
        },

        escapeHtml: function (teks) {
            return $('<div>').text(teks).html().replace(/\n/g, '<br>');
        },

        addBubble: function (role, teks, isError) {
            if (this.$placeholder) {
                this.$placeholder.remove();
                this.$placeholder = null;
            }

            var isUser = role === 'user';
            var wrapper = $('<div>').addClass('d-flex mb-3 ' + (isUser ? 'justify-content-end' : 'justify-content-start'));
            var bubble = $('<div>')
                .addClass('p-2 px-3 rounded-3 text-break mw-75 ' +
                    (isError ? 'bg-danger-subtle text-danger' : (isUser ? 'bg-primary text-white' : 'bg-white border shadow-sm')))
                .html(this.escapeHtml(teks));

            wrapper.append(bubble);
            this.$box.append(wrapper);
            this.scrollBottom();

            return wrapper;
        },

        scrollBottom: function () {
            this.$box.scrollTop(this.$box[0].scrollHeight);
        },

        setLoading: function (state) {
            this.$send.prop('disabled', state);
            this.$input.prop('disabled', state);

            if (state) {
                this.$typing = this.addBubble('model', 'Sedang mengetik...');
            } else if (this.$typing) {
                this.$typing.remove();
                this.$typing = null;
            }
        },

        clearChat: function () {
            this.history = [];
            this.$box.html('<div class="text-center text-muted my-4" id="chat-placeholder"><p class="mb-0">Halo! Silakan ketik pesan untuk mulai mengobrol.</p></div>');
            this.$placeholder = $('#chat-placeholder');
            this.$input.val('').focus();
        },

        onKeydown: function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.onSubmit(e);
                return false;
            }
        },

        onSubmit: function (e) {
            if (e && e.preventDefault) {
                e.preventDefault();
            }

            var pesan = $.trim(this.$input.val());
            if (!pesan) return;

            this.kirim(pesan);
        },

        kirim: function (pesan) {
            var self = this;

            this.addBubble('user', pesan);
            this.$input.val('');
            this.setLoading(true);

            $.ajax({
                url: this.urlKirim,
                method: 'POST',
                data: JSON.stringify({
                    message: pesan,
                    history: this.history
                }),
                contentType: 'application/json',
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': this.token }
            })
                .done(function (res) {
                    self.setLoading(false);
                    if (res.ok) {
                        self.addBubble('model', res.text);
                        self.history.push({ role: 'user', text: pesan });
                        self.history.push({ role: 'model', text: res.text });
                    } else {
                        self.addBubble('model', res.text || 'Terjadi kesalahan.', true);
                    }
                })
                .fail(function (xhr) {
                    self.setLoading(false);
                    var json = xhr.responseJSON || {};
                    var teks = json.text || json.message || xhr.responseText || 'Gagal mengirim pesan.';
                    self.addBubble('model', teks, true);
                })
                .always(function () {
                    self.$input.focus();
                });
        }
    };

    $(function () {
        Chatbot.init();
    });
})(jQuery);
