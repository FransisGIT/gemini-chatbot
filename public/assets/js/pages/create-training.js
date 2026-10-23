(function (e) {
    "use strict";

    function t() {
        this.$body = e("body");
    }

    t.prototype.init = function () {
        Dropzone.autoDiscover = false;

        // Initialize banner dropzone
        var bannerDropzone = new Dropzone("#banner-dropzone", {
            url: document.getElementById('training-form').action, // Gunakan action form
            paramName: "banner",
            maxFilesize: 5, // MB
            acceptedFiles: "image/jpeg,image/png,image/jpg,image/gif",
            maxFiles: 1,
            addRemoveLinks: true,
            autoProcessQueue: false,
            uploadMultiple: false,
            parallelUploads: 1,
            init: function () {
                this.on("addedfile", function (file) {
                    if (this.files.length > 1) {
                        this.removeFile(this.files[0]);
                    }
                });
            }
        });

        // Handle form submission
        $('#training-form').submit(function (e) {
            e.preventDefault();
            var form = this;
            var formData = new FormData(form);

            // Add banner file if exists
            if (bannerDropzone.files.length > 0) {
                formData.append('banner', bannerDropzone.files[0]);
            }

            // Gunakan URL dari action form
            $.ajax({
                url: form.action,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    window.location.href = "{{ route('training.index') }}";
                },
                error: function (xhr) {
                    alert('Terjadi kesalahan. Silakan coba lagi.');
                    console.error(xhr.responseText);
                }
            });
        });
    };

    e.FileUpload = new t();
    e.FileUpload.Constructor = t;
})(window.jQuery);

(function () {
    "use strict";
    window.jQuery.FileUpload.init();
})();
