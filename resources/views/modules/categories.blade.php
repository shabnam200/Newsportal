{{-- Category module. Drawn inside the Phoenix ".content" area by the script below, using Phoenix's own list-page markup. --}}
<style>
    .cat-module .cat-name-cell { display: flex; align-items: center; column-gap: .5rem; min-width: 14rem; }
    .cat-module .cat-caret { width: 1.5rem; flex: none; padding: 0; border: 0; background: transparent; color: var(--phoenix-tertiary-color); }
    .cat-module .cat-caret-space { display: inline-block; width: 1.5rem; flex: none; }
    .cat-module .cat-caret .fa-caret-right { transition: transform .15s; }
    .cat-module .cat-caret.open .fa-caret-right { transform: rotate(90deg); }
    .cat-module .cat-row-l1 .cat-title { font-weight: 700; }
    .cat-module .cat-image-thumb, .cat-module .cat-image-placeholder { width: 2rem; height: 2rem; flex: none; border: 1px solid var(--phoenix-border-color); border-radius: .35rem; object-fit: cover; }
    .cat-module .cat-image-placeholder { display: inline-flex; align-items: center; justify-content: center; color: var(--phoenix-tertiary-color); background: var(--phoenix-secondary-bg-subtle); }
    #catModal .cat-image-preview { display: block; width: 10rem; height: 7rem; max-width: 100%; object-fit: contain; border: 1px solid var(--phoenix-border-color); border-radius: .4rem; }
    .cat-module .cat-table th { padding: .6rem .75rem; white-space: nowrap; font-size: .75rem; letter-spacing: .04em; text-transform: uppercase; color: var(--phoenix-tertiary-color); border-bottom-width: 1px; }
    .cat-module .cat-table td { padding: .35rem .75rem; vertical-align: middle; line-height: 1.3; }
    .cat-module .cat-table tbody tr:hover > td { background: var(--phoenix-body-highlight-bg, rgba(0, 0, 0, .03)); }
    .cat-module .cat-actions { display: inline-flex; justify-content: flex-end; gap: .35rem; }
    .cat-module .cat-actions .btn, .cat-module .cat-actions .cat-ph { width: 1.75rem; height: 1.75rem; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: .7rem; }
    .cat-module .cat-status-link { text-decoration: none; }
    .cat-toast { position: fixed; top: 5rem; left: 50%; transform: translateX(-50%); z-index: 2100; width: min(34rem, 92vw); }
</style>

<div id="cat-config" class="d-none"
     data-data="{{ route('categories.data') }}"
     data-store="{{ route('categories.store') }}"
     data-update="{{ url('/categories') }}"></div>

<template id="cat-module-tpl">
    <div class="cat-module">
        <div class="mb-4">
            <h2 class="mb-2 text-body-emphasis">Categories</h2>
            <h5 class="text-body-tertiary fw-semibold">Category, sub-category and sub-sub-category in one place</h5>
        </div>

        <div class="row g-3 justify-content-between align-items-end mb-4">
            <div class="col-12 col-sm-auto">
                <div class="d-flex flex-wrap gap-2">
                    <div class="search-box me-2">
                        <form class="position-relative" onsubmit="return false">
                            <input class="form-control search-input search" id="cat-search" type="search" placeholder="Search categories" aria-label="Search" />
                            <span class="fas fa-search search-box-icon"></span>
                        </form>
                    </div>
                    <select class="form-select w-auto" id="cat-status-filter" aria-label="Status filter">
                        <option value="all">All status</option>
                        <option value="1">Active</option>
                        <option value="0">Disabled</option>
                    </select>
                </div>
            </div>
            <div class="col-12 col-sm-auto">
                <button class="btn btn-link text-body px-3 me-1" type="button" id="cat-expand-all">Expand all</button>
                <button class="btn btn-link text-body px-3 me-2" type="button" id="cat-collapse-all">Collapse all</button>
                <button class="btn btn-primary" type="button" id="cat-add"><span class="fas fa-plus me-2"></span>Add category</button>
            </div>
        </div>

        <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-body-emphasis border-top border-bottom border-translucent position-relative top-1">
            <div class="table-responsive scrollbar mx-n1 px-1">
                <table class="table fs-9 mb-0 cat-table">
                    <colgroup>
                        <col>
                        <col style="width:11rem">
                        <col style="width:12rem">
                        <col style="width:7rem">
                        <col style="width:8rem">
                        <col style="width:9.5rem">
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col" class="ps-3">Name</th>
                            <th scope="col">Level</th>
                            <th scope="col">Key</th>
                            <th scope="col" class="text-center">Sub items</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="list" id="cat-tbody"></tbody>
                </table>
            </div>
            <div class="py-5 text-center text-body-tertiary d-none" id="cat-empty">No categories yet. Click <strong>Add category</strong> to create the first one.</div>
        </div>
    </div>
</template>

{{-- Add / edit popup --}}
<div class="modal fade" id="catModal" tabindex="-1" aria-labelledby="catModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="catForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="catModalTitle">Add category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="cat-f-parent_id">Parent</label>
                            <select class="form-select" id="cat-f-parent_id" name="parent_id"></select>
                            <div class="invalid-feedback"></div>
                            <div class="form-text">Leave on "Top level" to create a main category. You can go up to 3 levels.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cat-f-name">Name <span class="text-danger">*</span></label>
                            <input class="form-control" id="cat-f-name" name="name" maxlength="100" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cat-f-en_name">English name</label>
                            <input class="form-control" id="cat-f-en_name" name="en_name" maxlength="100">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cat-f-key_name">Key <span class="text-body-tertiary">(used in links)</span></label>
                            <input class="form-control" id="cat-f-key_name" name="key_name" maxlength="100" placeholder="auto from the English name">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cat-f-title">Title</label>
                            <input class="form-control" id="cat-f-title" name="title" maxlength="100">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="cat-f-keywords">Keywords</label>
                            <input class="form-control" id="cat-f-keywords" name="keywords" maxlength="255">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="cat-f-description">Description</label>
                            <textarea class="form-control" id="cat-f-description" name="description" rows="2" maxlength="255"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="cat-f-image">Category image</label>
                            <input class="form-control" type="file" id="cat-f-image" name="image" accept="image/jpeg,image/png,image/webp,image/gif,image/bmp">
                            <div class="invalid-feedback"></div>
                            <div class="form-text">JPG, PNG, WebP, GIF or BMP; maximum 5 MB. Uploaded images are saved as WebP. Leave empty to keep the current image.</div>
                            <img class="cat-image-preview d-none mt-2" id="cat-image-preview" alt="Category image preview">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="cat-f-status" name="status" checked>
                                <label class="form-check-label" for="cat-f-status">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-phoenix-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="cat-save">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete confirmation --}}
<div class="modal fade" id="catDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Delete <strong id="cat-delete-name"></strong>? This cannot be undone.</p>
                <div class="text-danger fs-9 mt-2 d-none" id="cat-delete-error"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-phoenix-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="cat-delete-confirm">Delete</button>
            </div>
        </div>
    </div>
</div>

@verbatim
<script>
(function () {
    var cfg = document.getElementById('cat-config');
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var LEVEL_LABEL = { 1: 'Category', 2: 'Sub-category', 3: 'Sub-sub-category' };
    var MAX_LEVEL = 3;

    var state = { rows: [], byId: {}, children: {}, q: '', status: 'all', collapsed: {}, editId: null, deleteId: null };
    var el = {};

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function api(method, url, body) {
        var isFormData = body instanceof FormData;
        var headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' };
        if (!isFormData) headers['Content-Type'] = 'application/json';

        return fetch(url, {
            method: method,
            credentials: 'same-origin',
            headers: headers,
            body: isFormData ? body : body ? JSON.stringify(body) : undefined
        }).then(function (res) {
            return res.json().catch(function () { return null; }).then(function (json) {
                if (!res.ok || json === null) {
                    var err = new Error((json && json.message) || 'Something went wrong. Please reload the page and try again.');
                    err.errors = (json && json.errors) || {};
                    throw err;
                }
                return json;
            });
        });
    }

    function toast(message, type) {
        var t = document.createElement('div');
        t.className = 'cat-toast alert alert-subtle-' + (type || 'success') + ' alert-dismissible fade show';
        t.setAttribute('role', 'alert');
        t.innerHTML = esc(message) + '<button type="button" class="btn-close" aria-label="Close"></button>';
        t.querySelector('.btn-close').addEventListener('click', function () { t.remove(); });
        document.body.appendChild(t);
        setTimeout(function () { t.remove(); }, 4000);
    }

    // ---------- data ----------
    function index(rows) {
        state.rows = rows;
        state.byId = {};
        state.children = {};
        rows.forEach(function (r) {
            r.id = +r.id; r.level = +r.level; r.status = +r.status;
            r.parent_id = r.parent_id == null ? null : +r.parent_id;
            state.byId[r.id] = r;
        });
        rows.forEach(function (r) {
            var key = r.parent_id == null ? 0 : r.parent_id;
            (state.children[key] = state.children[key] || []).push(r);
        });
        Object.keys(state.children).forEach(function (k) {
            state.children[k].sort(function (a, b) { return a.name.localeCompare(b.name); });
        });
    }

    function load() {
        return api('GET', cfg.dataset.data).then(function (json) {
            if (!Array.isArray(json.data)) throw new Error('Your session may have expired. Please reload the page.');
            index(json.data);
            render();
        }).catch(function (e) { toast(e.message, 'danger'); });
    }

    function height(id) {            // number of levels in the subtree of id (itself included)
        var kids = state.children[id] || [];
        var h = 0;
        kids.forEach(function (k) { h = Math.max(h, height(k.id)); });
        return h + 1;
    }

    function descendantIds(id, out) {
        out = out || {};
        out[id] = true;
        (state.children[id] || []).forEach(function (k) { descendantIds(k.id, out); });
        return out;
    }

    // ---------- table ----------
    function matches(r) {
        var q = state.q;
        var okQ = !q || [r.name, r.en_name, r.key_name, r.title].some(function (v) { return v && String(v).toLowerCase().indexOf(q) !== -1; });
        var okS = state.status === 'all' || String(r.status) === state.status;
        return okQ && okS;
    }

    function visibleSet() {          // matching rows + their ancestors, so a hit is never shown without its path
        var set = {};
        state.rows.forEach(function (r) {
            if (!matches(r)) return;
            var cur = r;
            while (cur && !set[cur.id]) { set[cur.id] = true; cur = cur.parent_id == null ? null : state.byId[cur.parent_id]; }
        });
        return set;
    }

    function render() {
        var filtering = !!state.q || state.status !== 'all';
        var visible = filtering ? visibleSet() : null;
        var html = '';

        function walk(parentKey) {
            (state.children[parentKey] || []).forEach(function (r) {
                if (visible && !visible[r.id]) return;
                var kids = state.children[r.id] || [];
                var open = filtering || !state.collapsed[r.id];
                html += rowHtml(r, kids.length, open);
                if (kids.length && open) walk(r.id);
            });
        }
        walk(0);

        el.tbody.innerHTML = html;
        var empty = !state.rows.length;
        el.empty.classList.toggle('d-none', !empty);
        if (!empty && !html) {
            el.tbody.innerHTML = '<tr><td colspan="6" class="text-center text-body-tertiary py-5">No category matches your search.</td></tr>';
        }
    }

    function rowHtml(r, kidCount, open) {
        var indent = (r.level - 1) * 1.6;
        var caret = kidCount
            ? '<button type="button" class="cat-caret' + (open ? ' open' : '') + '" data-act="toggle" data-id="' + r.id + '" aria-label="Expand or collapse"><span class="fas fa-caret-right"></span></button>'
            : '<span class="cat-caret-space"></span>';
        var badge = r.status
            ? '<span class="badge badge-phoenix fs-10 badge-phoenix-success"><span class="badge-label">Active</span></span>'
            : '<span class="badge badge-phoenix fs-10 badge-phoenix-secondary"><span class="badge-label">Disabled</span></span>';
        var addChild = r.level < MAX_LEVEL
            ? '<button type="button" class="btn btn-phoenix-secondary" data-act="add-child" data-id="' + r.id + '" title="Add ' + esc(LEVEL_LABEL[r.level + 1]) + '"><span class="fas fa-plus"></span></button>'
            : '<span class="cat-ph"></span>';

        return '<tr class="cat-row-l' + r.level + '">' +
            '<td class="ps-3"><div class="cat-name-cell" style="padding-left:' + indent + 'rem">' + caret +
                (r.image_url
                    ? '<img class="cat-image-thumb" src="' + esc(r.image_url) + '" alt="' + esc(r.name) + '" loading="lazy">'
                    : '<span class="cat-image-placeholder" aria-hidden="true"><span class="fas fa-image"></span></span>') +
                '<div class="cat-title text-body-emphasis">' + esc(r.name) +
                (r.en_name && r.en_name !== r.name ? ' <span class="fs-10 fw-normal text-body-tertiary ms-1">' + esc(r.en_name) + '</span>' : '') + '</div></div></td>' +
            '<td class="text-body-tertiary text-nowrap">' + esc(LEVEL_LABEL[r.level]) + '</td>' +
            '<td class="text-body-tertiary text-break">' + esc(r.key_name) + '</td>' +
            '<td class="text-center">' + (kidCount || '<span class="text-body-tertiary">-</span>') + '</td>' +
            '<td><a href="#" class="cat-status-link" data-act="status" data-id="' + r.id + '" title="Click to ' + (r.status ? 'disable' : 'enable') + '">' + badge + '</a></td>' +
            '<td class="text-end pe-3"><div class="cat-actions">' + addChild +
                '<button type="button" class="btn btn-phoenix-secondary" data-act="edit" data-id="' + r.id + '" title="Edit"><span class="fas fa-pen"></span></button>' +
                '<button type="button" class="btn btn-phoenix-danger" data-act="delete" data-id="' + r.id + '" title="Delete"><span class="fas fa-trash"></span></button>' +
            '</div></td></tr>';
    }

    // ---------- add / edit popup ----------
    function fillParentOptions(selected, editing) {
        var blocked = editing ? descendantIds(editing.id) : {};
        var maxParentLevel = editing ? MAX_LEVEL - height(editing.id) : MAX_LEVEL - 1;
        var html = '<option value="">Top level (main category)</option>';

        function walk(parentKey) {
            (state.children[parentKey] || []).forEach(function (r) {
                if (!blocked[r.id] && r.level <= maxParentLevel) {
                    html += '<option value="' + r.id + '"' + (String(selected) === String(r.id) ? ' selected' : '') + '>' +
                        new Array(r.level).join('\u2003') + (r.level > 1 ? '\u2514 ' : '') + esc(r.name) + '</option>';
                }
                walk(r.id);
            });
        }
        walk(0);
        el.form.elements.parent_id.innerHTML = html;
    }

    function clearErrors() {
        el.form.querySelectorAll('.is-invalid').forEach(function (i) { i.classList.remove('is-invalid'); });
        el.form.querySelectorAll('.invalid-feedback').forEach(function (d) { d.textContent = ''; });
    }

    function setImagePreview(url) {
        if (el.imagePreview.dataset.objectUrl) {
            URL.revokeObjectURL(el.imagePreview.dataset.objectUrl);
            delete el.imagePreview.dataset.objectUrl;
        }

        if (!url) {
            el.imagePreview.removeAttribute('src');
            el.imagePreview.classList.add('d-none');
            return;
        }

        if (url.indexOf('blob:') === 0) el.imagePreview.dataset.objectUrl = url;
        el.imagePreview.src = url;
        el.imagePreview.classList.remove('d-none');
    }

    function showErrors(errors) {
        Object.keys(errors || {}).forEach(function (field) {
            var input = el.form.elements[field];
            if (!input) return;
            input.classList.add('is-invalid');
            var fb = input.parentNode.querySelector('.invalid-feedback');
            if (fb) fb.textContent = errors[field][0];
        });
    }

    function openForm(row, presetParent) {
        clearErrors();
        state.editId = row ? row.id : null;
        el.modalTitle.textContent = row ? 'Edit category' : 'Add category';
        fillParentOptions(row ? row.parent_id : presetParent, row);
        var f = el.form;
        f.elements.name.value = row ? row.name : '';
        f.elements.en_name.value = row && row.en_name ? row.en_name : '';
        f.elements.key_name.value = row ? row.key_name : '';
        f.elements.title.value = row && row.title ? row.title : '';
        f.elements.keywords.value = row && row.keywords ? row.keywords : '';
        f.elements.description.value = row && row.description ? row.description : '';
        f.elements.image.value = '';
        f.elements.status.checked = row ? !!row.status : true;
        setImagePreview(row && row.image_url ? row.image_url : '');
        bootstrap.Modal.getOrCreateInstance(el.modal).show();
        setTimeout(function () { f.elements.name.focus(); }, 300);
    }

    function submitForm(e) {
        e.preventDefault();
        clearErrors();
        var f = el.form;
        var body = new FormData(f);
        body.set('parent_id', f.elements.parent_id.value);
        body.set('status', f.elements.status.checked ? '1' : '0');
        var editing = state.editId;
        var url = editing ? cfg.dataset.update + '/' + editing : cfg.dataset.store;
        if (editing) body.set('_method', 'PUT');

        el.save.disabled = true;
        api('POST', url, body).then(function (json) {
            bootstrap.Modal.getOrCreateInstance(el.modal).hide();
            toast(json.message);
            return load();
        }).catch(function (err) {
            if (err.errors && Object.keys(err.errors).length) showErrors(err.errors); else toast(err.message, 'danger');
        }).then(function () { el.save.disabled = false; });
    }

    // ---------- delete ----------
    function askDelete(row) {
        state.deleteId = row.id;
        document.getElementById('cat-delete-name').textContent = row.name;
        var box = document.getElementById('cat-delete-error');
        box.classList.add('d-none'); box.textContent = '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('catDeleteModal')).show();
    }

    function confirmDelete() {
        var btn = document.getElementById('cat-delete-confirm');
        btn.disabled = true;
        api('DELETE', cfg.dataset.update + '/' + state.deleteId).then(function (json) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('catDeleteModal')).hide();
            toast(json.message);
            return load();
        }).catch(function (err) {
            var box = document.getElementById('cat-delete-error');
            box.textContent = err.message; box.classList.remove('d-none');
        }).then(function () { btn.disabled = false; });
    }

    // ---------- wiring ----------
    function setup(content) {
        var tpl = document.getElementById('cat-module-tpl');
        var node = tpl.content.firstElementChild.cloneNode(true);
        var host = document.getElementById('portal-module-host');
        if (!host) return;
        host.replaceChildren(node);

        el.tbody = document.getElementById('cat-tbody');
        el.empty = document.getElementById('cat-empty');
        el.modal = document.getElementById('catModal');
        el.modalTitle = document.getElementById('catModalTitle');
        el.form = document.getElementById('catForm');
        el.save = document.getElementById('cat-save');
        el.imagePreview = document.getElementById('cat-image-preview');
        document.getElementById('cat-search').addEventListener('input', function (e) { state.q = e.target.value.trim().toLowerCase(); render(); });
        document.getElementById('cat-status-filter').addEventListener('change', function (e) { state.status = e.target.value; render(); });
        document.getElementById('cat-add').addEventListener('click', function () { openForm(null, ''); });
        document.getElementById('cat-expand-all').addEventListener('click', function () { state.collapsed = {}; render(); });
        document.getElementById('cat-collapse-all').addEventListener('click', function () {
            state.rows.forEach(function (r) { if ((state.children[r.id] || []).length) state.collapsed[r.id] = true; });
            render();
        });
        el.form.elements.image.addEventListener('change', function (e) {
            var file = e.target.files && e.target.files[0];
            setImagePreview(file ? URL.createObjectURL(file) : '');
        });
        el.form.addEventListener('submit', submitForm);
        document.getElementById('cat-delete-confirm').addEventListener('click', confirmDelete);

        el.tbody.addEventListener('click', function (e) {
            var t = e.target.closest('[data-act]');
            if (!t) return;
            e.preventDefault();
            var row = state.byId[+t.dataset.id];
            if (!row) return;
            switch (t.dataset.act) {
                case 'toggle': state.collapsed[row.id] = !state.collapsed[row.id]; render(); break;
                case 'add-child': openForm(null, row.id); break;
                case 'edit': openForm(row); break;
                case 'delete': askDelete(row); break;
                case 'status':
                    api('POST', cfg.dataset.update + '/' + row.id + '/toggle').then(function (json) { toast(json.message); return load(); })
                        .catch(function (err) { toast(err.message, 'danger'); });
                    break;
            }
        });

        load();
    }

    // Wait until Vue has drawn the Phoenix page and Bootstrap is loaded, then put the module in.
    var tries = 0;
    var timer = setInterval(function () {
        var content = document.querySelector('#app .content');
        tries++;
        if (content && document.getElementById('portal-module-host') && window.bootstrap && window.bootstrap.Modal) {
            clearInterval(timer);
            setup(content);
        } else if (tries > 300) {
            clearInterval(timer);
        }
    }, 100);
})();
</script>
@endverbatim
