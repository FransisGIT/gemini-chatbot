jQuery.index = {
    data: {
        table: null,
        searchInput: null,
        searchButton: null,
        statusFilter: 'all',
    },
    init: function () {
        var self = this;

        self.data.searchInput = $('#search-products');
        self.data.searchButton = $('.btn-search');

        self.initTable();
        self.setEvents();
    },
    initTable: function () {
        var self = this;

        self.data.table = $('#ohmytable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/admin/products/get-data',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: function (d) {
                    d.search = {
                        value: self.data.searchInput.val(),
                        regex: false
                    };
                    d.status_filter = self.data.statusFilter;
                    return d;
                }
            },
            columns: [
                {
                    data: null,
                    className: 'text-center fw-bold align-middle',
                    width: '10%',
                    render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    },
                },
                {
                    data: null,
                    className: 'text-center align-middle',
                    width: '10%',
                    render: function (data) {
                        return `
                            <div class='d-flex justify-content-center align-items-center'>
                                <img src='/uploads/products/${data.thumbnail}' alt='Thumbnail' class='rounded' width='150' height='150' style="object-fit: cover;"/>
                            </div>
                        `;
                    }
                },
                {
                    data: null,
                    className: 'text-left',
                    width: '30%',
                    render: function (data) {
                        const lang = window.APP_LOCALE;

                        if (lang === 'id') {
                            return data.name.id + '<br><hr>' + data.category.name.id;
                        } else if (lang === 'en') {
                            return data.name.en + '<br><hr>' + data.category.name.en;
                        }
                    }
                },
                {
                    data: null,
                    className: 'text-left',
                    width: '30%',
                    render: function (data) {
                        const lang = window.APP_LOCALE;

                        if (lang === 'id') {
                            return data.description.id || 'Tidak ada deskripsi';
                        } else if (lang === 'en') {
                            return data.description.en || 'No description available';
                        }
                    }
                },
                {
                    data: null,
                    className: 'text-center',
                    width: '10%',
                    render: function (data) {
                        const lang = window.APP_LOCALE;

                        if (data.status === 'aktif') {
                            if (lang === 'id') {
                                return 'Aktif';
                            } else if (lang === 'en') {
                                return 'Active';
                            }
                        } else {
                            if (lang === 'id') {
                                return 'Tidak Aktif';
                            } else if (lang === 'en') {
                                return 'Inactive';
                            }
                        }
                    }
                },
                {
                    data: null,
                    className: 'text-center fw-bold',
                    width: '10%',
                    render: function (data) {
                        return `
                            <div class='d-grid gap-2'>
                                <button class='btn btn-sm btn-soft-warning btn-edit w-100'
                                    data-id='${data.id}' data-category='${data.category.id}'>
                                    <i class='ti ti-pencil align-middle me-1 fs-18'></i>${window.i18n.edit}
                                </button>
                                <button class='btn btn-sm btn-soft-danger btn-hapus w-100'
                                    data-id='${data.id}'>
                                    <i class='ti ti-trash align-middle me-1 fs-18'></i>${window.i18n.delete}
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            scrollY: '500px',
            scrollCollapse: true,
            paging: true,
            pageLength: 10,
            lengthChange: false,
            searching: false,
            ordering: false,
            autoWidth: false,
            language: {
                emptyTable: window.i18n.emptyTable,
                info: window.i18n.info,
                infoEmpty: window.i18n.infoEmpty,
                search: window.i18n.search,
                paginate: {
                    next: window.i18n.paginate.next,
                    previous: window.i18n.paginate.previous
                }
            }
        });
    },
    setEvents: function () {
        var self = this;

        self.data.searchButton.on('click', function () {
            var button = $(this);
            button.attr('data-kt-indicator', 'on');
            button.prop('disabled', true);

            self.data.table.ajax.reload(function () {
                button.removeAttr('data-kt-indicator');
                button.prop('disabled', false);
            });
        });

        self.data.searchInput.keyup(function (e) {
            if (e.keyCode === 13) {
                self.data.searchButton.click();
            }
        });

        $('.filter-status').on('click', function (e) {
            e.preventDefault();
            self.data.statusFilter = $(this).data('status');
            console.log('Status Filter:', self.data.statusFilter);
            self.data.table.ajax.reload();
        });

        $("#ohmytable").on('click', "button.btn-hapus", function () {
            var id = $(this).data("id");

            $.confirm({
                title: window.i18n.delete_title,
                type: 'orange',
                columnClass: 'small',
                content: window.i18n.delete_confirmation,
                buttons: {
                    cancel: {
                        text: window.i18n.action_cancel,
                        btnClass: 'btn-red',
                        keys: ['esc']
                    },
                    confirm: {
                        text: window.i18n.action_confirm,
                        btnClass: 'btn-green',
                        keys: ['enter'],
                        action: function () {
                            $("#id_delete").val(id)
                            $("#form_delete").submit();
                        }
                    }
                }
            });
        });

        $(document).on('click', '.btn-edit', function () {
            var id = $(this).data('id');
            var category = $(this).data('category');

            window.location.href = '/admin/products/' + id + '/edit/' + category;
        });
    }
};

$(document).ready(function () {
    jQuery.index.init();
});
